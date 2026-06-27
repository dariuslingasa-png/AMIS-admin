<?php

namespace App\Http\Controllers;

use App\Models\EnrollmentApplicant;
use App\Models\Student;
use App\Models\StudentSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ChatbotController extends Controller
{
    public function chat(Request $request)
    {
        $request->validate([
            'messages' => 'required|array|max:10',
            'messages.*.sender' => 'required|string|in:user,bot',
            'messages.*.text' => 'required|string|max:500',
        ]);

        $messages = array_slice($request->input('messages'), -10);
        $directReply = $this->answerGradeAdviserQuestion($this->latestUserMessage($messages));

        if ($directReply) {
            return response()->json([
                'reply' => $directReply,
            ]);
        }

        // Form messages payload for DeepSeek API
        $messagesPayload = [];

        // 1. Prepend the System Prompt to guide the model behavior
        $messagesPayload[] = [
            'role' => 'system',
            'content' => "You are the AMIS Assistant, a helpful AI desk assistant for Al Munawwara Islamic School (AMIS). Your purpose is to assist students, parents, and visitors with inquiries related to enrollment, admissions, requirements, grade levels, class section, official adviser, assigned subject teachers, and available teacher/adviser contact information. " .
                         "CRITICAL RULE: If a user asks about anything unrelated to AMIS, school inquiries, or enrollment (such as general knowledge, programming, history, math, writing essays, recipes, or unrelated topics), you MUST politely refuse to answer and say exactly: 'I can't share that, sorry AMIS-ian.' " .
                         "Keep your answers concise, clear, and friendly. Answer in English only. Do not use any markdown formatting other than bolding using **double asterisks**. " .
                         "If the user wants to check their enrollment status, section, adviser, subject teachers, or adviser/teacher contact info, you must use the check_enrollment_status tool. If their AMIS Student ID and last name already appear in the conversation, use them. Otherwise, ask for their AMIS Student ID and last name. Do not ask users to search by name only. " .
                         "When the tool returns adviser, subject teachers, or contact_info, include those details. If a field says Coming soon, show it exactly as Coming soon."
        ];

        // 2. Map existing conversation history
        foreach ($messages as $msg) {
            $role = ($msg['sender'] === 'user') ? 'user' : 'assistant';
            $messagesPayload[] = [
                'role' => $role,
                'content' => $msg['text']
            ];
        }

        $apiKey = env('DEEPSEEK_API_KEY');

        if (empty($apiKey)) {
            Log::error('DeepSeek API Key is missing.');
            return response()->json([
                'error' => 'Configuration error. API key is missing.'
            ], 500);
        }

        // Define tools for the model
        $tools = [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'check_enrollment_status',
                    'description' => 'Check the enrollment or application status of a student and return class section, adviser, assigned subject teachers, and available adviser/teacher contact information.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'last_name' => [
                                'type' => 'string',
                                'description' => 'Last name of the student. Required with student_id.'
                            ],
                            'student_id' => [
                                'type' => 'string',
                                'description' => 'AMIS Student ID or student number, e.g., 260001. Required.'
                            ],
                        ],
                        'required' => ['student_id', 'last_name'],
                    ]
                ]
            ]
        ];

        try {
            // Call DeepSeek Chat Completion API with tools
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.deepseek.com/chat/completions', [
                'model' => 'deepseek-chat',
                'messages' => $messagesPayload,
                'temperature' => 0.5,
                'max_tokens' => 400,
                'tools' => $tools
            ]);

            if ($response->failed()) {
                Log::error('DeepSeek API failed: ' . $response->body());
                return response()->json([
                    'error' => 'Failed to retrieve response from assistant.'
                ], 502);
            }

            $data = $response->json();
            $message = $data['choices'][0]['message'] ?? null;

            if ($message && !empty($message['tool_calls'])) {
                // The model requested to call a tool
                $toolCall = $message['tool_calls'][0];
                $functionName = $toolCall['function']['name'] ?? '';
                $arguments = json_decode($toolCall['function']['arguments'] ?? '{}', true);

                if ($functionName === 'check_enrollment_status') {
                    // 1. Execute the tool
                    $result = $this->checkEnrollmentStatus($arguments);

                    // 2. Append the assistant's tool call message
                    $messagesPayload[] = $message;

                    // 3. Append the tool response message
                    $messagesPayload[] = [
                        'role' => 'tool',
                        'tool_call_id' => $toolCall['id'],
                        'content' => json_encode($result)
                    ];

                    // 4. Request the second completion from DeepSeek to formulate the reply
                    $secondResponse = Http::withHeaders([
                        'Authorization' => "Bearer {$apiKey}",
                        'Content-Type' => 'application/json',
                    ])->timeout(30)->post('https://api.deepseek.com/chat/completions', [
                        'model' => 'deepseek-chat',
                        'messages' => $messagesPayload,
                        'temperature' => 0.5,
                        'max_tokens' => 400,
                    ]);

                    if ($secondResponse->failed()) {
                        Log::error('DeepSeek Tool Completion failed: ' . $secondResponse->body());
                        return response()->json([
                            'error' => 'Failed to process assistant response.'
                        ], 502);
                    }

                    $secondData = $secondResponse->json();
                    $reply = $secondData['choices'][0]['message']['content'] ?? '';

                    return response()->json([
                        'reply' => $reply
                    ]);
                }
            }

            $reply = $message['content'] ?? '';

            return response()->json([
                'reply' => $reply
            ]);

        } catch (\Exception $e) {
            Log::error('Chatbot error: ' . $e->getMessage());
            return response()->json([
                'error' => 'An error occurred while connecting to the assistant.'
            ], 500);
        }
    }

    /**
     * Securely queries the database for student enrollment status using Eloquent.
     * Only returns non-sensitive fields to the AI.
     */
    private function checkEnrollmentStatus(array $args)
    {
        $lastName = trim($args['last_name'] ?? '');
        $studentId = trim($args['student_id'] ?? '');

        if ($studentId === '' || $lastName === '') {
            return [
                'status' => 'Needs Verification',
                'message' => 'Please provide both AMIS Student ID and last name to check enrollment status.'
            ];
        }

        $student = Student::with('applicant')
            ->where('student_number', $studentId)
            ->first();

        $applicant = $student?->applicant;

        if (! $applicant) {
            $applicant = EnrollmentApplicant::where('amis_student_id', $studentId)
                ->orWhere('id', $studentId)
                ->first();
        }

        if (! $applicant || $this->normalizeName($applicant->last_name ?? '') !== $this->normalizeName($lastName)) {
            return [
                'status' => 'Not Found',
                'message' => 'No verified enrollment record matched those details. Please check the AMIS Student ID and last name, or submit a support ticket.'
            ];
        }

        $section = $this->findStudentSection($student)?->section;

        return [
            'status' => 'Found',
            'grade_level' => $applicant->grade_level,
            'enrollment_status' => $applicant->status,
            'section' => $section?->section_title ?? 'Coming soon',
            'adviser' => $this->buildAdviserInfo($student, $applicant, $section),
            'subject_teachers' => $this->buildSubjectTeachersInfo($section),
        ];
    }

    private function latestUserMessage(array $messages): string
    {
        for ($index = count($messages) - 1; $index >= 0; $index--) {
            if (($messages[$index]['sender'] ?? null) === 'user') {
                return trim((string) ($messages[$index]['text'] ?? ''));
            }
        }

        return '';
    }

    private function answerGradeAdviserQuestion(string $message): ?string
    {
        if ($message === '' || ! preg_match('/\badvise[ro]r?\b/i', $message)) {
            return null;
        }

        $grade = $this->extractGradeLevelFromText($message);

        if (! $grade) {
            return null;
        }

        $adviser = $this->configuredAdviserForGrade($grade);

        if (! $adviser || empty($adviser['name'])) {
            return "**{$grade} adviser:** Coming soon";
        }

        $teacherName = $this->formatTeacherName($adviser['name']);
        $email = $this->teacherEmailFromUsers($this->cleanTeacherName($adviser['name'])) ?? 'Coming soon';

        return "**{$grade} adviser:** {$teacherName}\n\n" .
            "**Contact info:**\n" .
            "Official email: {$email}\n" .
            "Gmail: Coming soon\n" .
            "Facebook: Coming soon\n" .
            "WhatsApp: Coming soon";
    }

    private function extractGradeLevelFromText(string $message): ?string
    {
        if (preg_match('/\bgrade\s*(1[0-2]|[1-9])\b/i', $message, $matches)) {
            return 'Grade ' . (int) $matches[1];
        }

        if (preg_match('/\bg\s*(1[0-2]|[1-9])\b/i', $message, $matches)) {
            return 'Grade ' . (int) $matches[1];
        }

        if (preg_match('/\bkinder(?:garten)?\s*([12])\b/i', $message, $matches)) {
            return 'Kinder ' . (int) $matches[1];
        }

        if (preg_match('/\bk\s*([12])\b/i', $message, $matches)) {
            return 'Kinder ' . (int) $matches[1];
        }

        return null;
    }

    private function findStudentSection(?Student $student): ?StudentSection
    {
        if (! $student) {
            return null;
        }

        return StudentSection::where('student_id', $student->id)
            ->where('ms_status', 'enrolled')
            ->with(['section.subjects'])
            ->first();
    }

    private function buildAdviserInfo(?Student $student, EnrollmentApplicant $applicant, $section): array
    {
        $adviser = null;

        if ($section && Schema::hasTable('class_advisory_assignments')) {
            $dbAdviser = DB::table('class_advisory_assignments')
                ->where('section_id', $section->id)
                ->where('status', 'active')
                ->first();

            if ($dbAdviser) {
                $adviser = [
                    'name' => $dbAdviser->teacher_name,
                    'email' => $dbAdviser->teacher_email,
                ];
            }
        }

        if (! $adviser) {
            $grade = $section?->grade_level ?? $student?->grade_level ?? $applicant->grade_level;
            $adviser = $this->configuredAdviserForGrade($grade);
        }

        if (! $adviser || empty($adviser['name'])) {
            return [
                'name' => 'Coming soon',
                'contact_info' => $this->comingSoonContactInfo(),
            ];
        }

        $cleanName = $this->cleanTeacherName($adviser['name']);
        $email = $adviser['email'] ?? $this->teacherEmailFromUsers($cleanName) ?? 'Coming soon';
        $contactInfo = [
            'official_email' => $email,
            'gmail' => $adviser['gmail'] ?? 'Coming soon',
            'facebook' => $adviser['fb_url'] ?? 'Coming soon',
            'whatsapp' => $adviser['whatsapp'] ?? 'Coming soon',
        ];

        if (str_contains(strtolower($adviser['name']), 'ethel') && str_contains(strtolower($adviser['name']), 'lorraine')) {
            $contactInfo['gmail'] = 'eljustiniane.amis@gmail.com';
            $contactInfo['facebook'] = 'https://www.facebook.com/elijstnn';
            $contactInfo['whatsapp'] = '09451075043';
        }

        return [
            'name' => $this->formatTeacherName($adviser['name']),
            'contact_info' => $contactInfo,
        ];
    }

    private function buildSubjectTeachersInfo($section): array
    {
        if (! $section || $section->subjects->isEmpty()) {
            return [[
                'subject' => 'Coming soon',
                'teacher_name' => 'Coming soon',
                'contact_info' => $this->comingSoonContactInfo(),
            ]];
        }

        return $section->subjects
            ->map(function ($subject) {
                $teacherName = trim((string) $subject->teacher_name);

                if ($teacherName === '') {
                    return [
                        'subject' => $subject->subject_name,
                        'teacher_name' => 'Coming soon',
                        'contact_info' => $this->comingSoonContactInfo(),
                    ];
                }

                $cleanName = $this->cleanTeacherName($teacherName);

                return [
                    'subject' => $subject->subject_name,
                    'teacher_name' => $this->formatTeacherName($teacherName),
                    'contact_info' => [
                        'official_email' => $this->teacherEmailFromUsers($cleanName) ?? 'Coming soon',
                        'gmail' => 'Coming soon',
                        'facebook' => 'Coming soon',
                        'whatsapp' => 'Coming soon',
                    ],
                ];
            })
            ->values()
            ->all();
    }

    private function configuredAdviserForGrade(?string $grade): ?array
    {
        if (! $grade) {
            return null;
        }

        $normalizedGrade = $this->normalizeGradeLevel($grade);
        $advisers = array_merge(
            config('class_advisories.elementary', []),
            config('class_advisories.high_school', [])
        );

        foreach ($advisers as $adviser) {
            if ($this->normalizeGradeLevel($adviser['grade_level'] ?? '') === $normalizedGrade) {
                return [
                    'name' => $adviser['teacher'] ?? null,
                ];
            }
        }

        return null;
    }

    private function teacherEmailFromUsers(string $cleanName): ?string
    {
        if ($cleanName === '' || ! Schema::hasTable('users')) {
            return null;
        }

        $teacherUser = DB::table('users')
            ->where('role', 'teacher')
            ->where(function ($query) use ($cleanName) {
                $query->where('name', $cleanName)
                    ->orWhere('name', 'like', '%' . $cleanName . '%');
            })
            ->first();

        return $teacherUser?->email;
    }

    private function comingSoonContactInfo(): array
    {
        return [
            'official_email' => 'Coming soon',
            'gmail' => 'Coming soon',
            'facebook' => 'Coming soon',
            'whatsapp' => 'Coming soon',
        ];
    }

    private function cleanTeacherName(?string $name): string
    {
        return trim(str_ireplace('TEACHER ', '', (string) $name));
    }

    private function formatTeacherName(?string $name): string
    {
        $cleanName = $this->cleanTeacherName($name);

        return $cleanName === '' ? 'Coming soon' : 'Teacher ' . ucwords(strtolower($cleanName));
    }

    private function normalizeGradeLevel(string $grade): string
    {
        $grade = trim($grade);

        if (preg_match('/^G(\d{1,2})$/i', $grade, $matches)) {
            return 'grade ' . (int) $matches[1];
        }

        if (preg_match('/^K(\d)$/i', $grade, $matches)) {
            return 'kinder ' . (int) $matches[1];
        }

        return strtolower(preg_replace('/\s+/', ' ', $grade));
    }

    private function normalizeName(string $value): string
    {
        return preg_replace('/[^a-z0-9]/', '', mb_strtolower($value));
    }
}
