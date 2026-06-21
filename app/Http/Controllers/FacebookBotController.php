<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\EnrollmentApplicant;

class FacebookBotController extends Controller
{
    /**
     * Webhook Verification (GET) - Required by Meta
     */
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode && $token) {
            if ($mode === 'subscribe' && $token === env('MESSENGER_VERIFY_TOKEN')) {
                return response($challenge, 200)->header('Content-Type', 'text/plain');
            }
        }

        return response('Forbidden', 403);
    }

    /**
     * Handle Incoming Messages (POST)
     */
    public function handle(Request $request)
    {
        $payload = $request->all();
        Log::info("Facebook Webhook Received:", $payload);

        // Ensure message comes from a page subscription
        if (isset($payload['object']) && $payload['object'] === 'page') {
            foreach ($payload['entry'] as $entry) {
                if (isset($entry['messaging'])) {
                    foreach ($entry['messaging'] as $event) {
                        $senderPsid = $event['sender']['id'];

                        // 1. Handle Postback (like Get Started or Ice Breakers)
                        if (isset($event['postback'])) {
                            $postbackPayload = $event['postback']['payload'] ?? '';
                            $this->processMessage($senderPsid, $postbackPayload);
                        }
                        // 2. Handle normal messages
                        elseif (isset($event['message']) && !isset($event['message']['is_echo'])) {
                            // Check if it's a quick reply payload or standard text
                            $messageText = $event['message']['quick_reply']['payload'] 
                                           ?? $event['message']['text'] 
                                           ?? '';
                            $this->processMessage($senderPsid, trim($messageText));
                        }
                    }
                }
            }
            return response('EVENT_RECEIVED', 200);
        }

        return response('Not Found', 404);
    }

    /**
     * Simple Chatbot State Machine using Laravel Cache
     */
    private function processMessage($senderPsid, $messageText)
    {
        $sessionKey = "fb_bot_session_{$senderPsid}";
        $session = Cache::get($sessionKey, ['step' => 0, 'data' => []]);

        $normalizedText = strtolower(trim($messageText));
        if ($normalizedText === 'inquiries_coming_soon' || $normalizedText === 'inquiries') {
            $this->sendMessage($senderPsid, "The Inquiries feature is coming soon! 🚀\n\nFor now, please choose 'Enrollment Status' to check student enrollment status.");
            return;
        }

        // Direct AMIS ID / Student ID lookup check
        $cleanMessageText = trim($messageText);
        $isAmisId = preg_match('/^\d{4,8}$/', $cleanMessageText) || preg_match('/^amis-\d+/i', $cleanMessageText) || preg_match('/^amis\d+/i', $cleanMessageText);
        if ($isAmisId) {
            Cache::forget($sessionKey);
            $this->sendMessage($senderPsid, "Checking the record for ID: {$cleanMessageText}. Please wait a moment...");
            $statusResult = $this->lookupEnrollmentStatusById($cleanMessageText);
            $this->sendMessage($senderPsid, $statusResult);
            return;
        }

        $triggerWords = [
            'hi', 'hello', 'enrollment status', 'check status', 'amis', 
            'info', 'start', 'status', 'check_enrollment_status', 'get_started'
        ];

        if (in_array($normalizedText, $triggerWords) || $session['step'] === 0) {
            $session = [
                'step' => 1,
                'data' => []
            ];
            Cache::put($sessionKey, $session, now()->addMinutes(15));
            
            $greeting = "Assalamualaikum! I am the AMIS IT Staff.\n\n" .
                        "This is an automated chatbot developed by the AMIS Information Technology Department.\n\n" .
                        "To check your enrollment status, please provide the following:\n\n" .
                        "• Full Name\n" .
                        "• Grade Level\n" .
                        "• Birthdate\n\n" .
                        "We will verify your information and check whether your enrollment is approved, pending, or if an account has already been created.\n\n" .
                        "💡 Tip: If you already have a Student ID or AMIS ID, you can just reply with that ID (e.g. 260000) at any time to check status immediately!";
            
            $this->sendMessage($senderPsid, $greeting);
            
            // Ask the first question
            $this->sendMessage($senderPsid, "What is the student's FULL NAME (First Name and Last Name)?");
            return;
        }

        switch ($session['step']) {
            case 1:
                $session['data']['name'] = $messageText;
                $session['step'] = 2;
                Cache::put($sessionKey, $session, now()->addMinutes(15));

                $this->sendMessage($senderPsid, "Thank you. What is the GRADE LEVEL applied for? (e.g. Grade 1, Grade 5, Kinder)");
                break;

            case 2:
                $session['data']['grade'] = $messageText;
                $session['step'] = 3;
                Cache::put($sessionKey, $session, now()->addMinutes(15));

                $this->sendMessage($senderPsid, "Last step, what is the student's BIRTHDATE? (Format: MM-DD-YYYY, e.g. 04-30-2020)");
                break;

            case 3:
                $birthdate = trim($messageText);
                $name = $session['data']['name'];
                $grade = $session['data']['grade'];

                Cache::forget($sessionKey);

                $this->sendMessage($senderPsid, "Thank you! Checking the record of {$name} in our system. Please wait a moment...");

                $statusResult = $this->lookupEnrollmentStatus($name, $grade, $birthdate);

                $this->sendMessage($senderPsid, $statusResult);
                break;
        }
    }

    /**
     * Direct Database Lookup by ID/Number Logic
     */
    private function lookupEnrollmentStatusById($id)
    {
        $id = trim($id);

        $applicant = EnrollmentApplicant::where('amis_student_id', $id)
            ->orWhere('id', $id)
            ->first();

        if (!$applicant) {
            $student = \App\Models\Student::where('student_number', $id)
                ->orWhere('school_email', $id)
                ->orWhere('ms_email', $id)
                ->first();
            if ($student) {
                $applicant = $student->applicant;
            }
        }

        if (!$applicant) {
            return "❌ No Record Found\n\nWe couldn't find any enrollment record with ID/Number: {$id}. Please make sure the ID/Number is correct.";
        }

        return $this->formatStatusResponse($applicant);
    }

    /**
     * Database Lookup Logic by Name/Grade/Birthdate
     */
    private function lookupEnrollmentStatus($name, $grade, $birthdate)
    {
        $parts = explode(' ', $name);
        $firstName = $parts[0] ?? '';
        $lastName = count($parts) > 1 ? end($parts) : '';

        $normalizedGrade = $this->normalizeGradeLevel($grade);
        
        $query = EnrollmentApplicant::where(function($q) use ($firstName, $lastName) {
            $q->where('first_name', 'like', "%{$firstName}%")
              ->where('last_name', 'like', "%{$lastName}%");
        });

        if ($normalizedGrade) {
            $query->where('grade_level', $normalizedGrade);
        }

        $formattedBirthdate = $this->parseBirthdate($birthdate);
        if ($formattedBirthdate) {
            $query->whereDate('date_of_birth', $formattedBirthdate);
        }

        $applicant = $query->first();

        if (!$applicant) {
            return "❌ No Record Found\n\nWe couldn't find any enrollment record for this student. Please make sure the spelling and birthdate are correct.";
        }

        return $this->formatStatusResponse($applicant);
    }

    /**
     * Format status response string for an applicant
     */
    private function formatStatusResponse($applicant)
    {
        switch (strtolower($applicant->status)) {
            case 'approved':
            case 'registered':
                $student = $applicant->student;
                $msg = "✅ Account Created\n\nGreat news! The enrollment for {$applicant->first_name} {$applicant->last_name} has been approved, and the account is created in the system.";
                
                $amisId = $student->student_number ?? $applicant->amis_student_id ?? null;
                if ($amisId) {
                    $msg .= "\n\nAMIS Student ID: {$amisId}";
                }

                $email = $student->school_email ?? $student->ms_email ?? null;
                $password = $student->temp_password ?? null;

                if ($email) {
                    $msg .= "\n\nMicrosoft 365 Account:\n📧 Username: {$email}";
                    if ($password) {
                        $msg .= "\n🔑 Temp Password: {$password}";
                    }
                } else {
                    $msg .= "\n\nYou can now access Microsoft 365.";
                }

                return $msg;
            
            case 'submitted':
            case 'pending':
            case 'under_review':
                return "⏳ Pending Enrollment\n\nWe found the record for {$applicant->first_name} {$applicant->last_name}. The status is currently PENDING or under review by the admin. Please wait for an email or SMS notification for the next steps.";
            
            default:
                return "❌ No Record Found / Draft Application\n\nThe application form for {$applicant->first_name} is not yet fully submitted. Please complete the form on the enrollment portal.";
        }
    }

    /**
     * Parse various human birthdate formats to standard YYYY-MM-DD
     */
    private function parseBirthdate($input)
    {
        $input = trim($input);

        // 1. Try MM-DD-YYYY / MM/DD/YYYY / MM DD YYYY
        if (preg_match('/^(\d{1,2})[-\/\s](\d{1,2})[-\/\s](\d{4})$/', $input, $matches)) {
            $month = (int)$matches[1];
            $day = (int)$matches[2];
            $year = (int)$matches[3];
            if (checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        // 2. Try YYYY-MM-DD / YYYY/MM/DD / YYYY MM DD
        if (preg_match('/^(\d{4})[-\/\s](\d{1,2})[-\/\s](\d{1,2})$/', $input, $matches)) {
            $year = (int)$matches[1];
            $month = (int)$matches[2];
            $day = (int)$matches[3];
            if (checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        // 3. Fallback to standard strtotime (for April 30, 2020 etc.)
        $timestamp = strtotime($input);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        return null;
    }

    /**
     * Normalize various human-entered grade levels to database format
     */
    private function normalizeGradeLevel($input)
    {
        $input = strtolower(trim($input));

        // Extract any number from the input
        if (preg_match('/(\d+)/', $input, $matches)) {
            $num = (int)$matches[1];
            
            // If the number is 11 or 12, it is definitely a Grade (Grade 11/12)
            if ($num >= 11 && $num <= 12) {
                return "Grade {$num}";
            }
            
            // If the input contains "kinder" or "k" (but not G or grade)
            if (str_contains($input, 'kinder') || str_contains($input, 'kind') || preg_match('/\bk\s*[12]\b/', $input) || preg_match('/^k\s*[12]$/', $input)) {
                if ($num === 1 || $num === 2) {
                    return "Kinder {$num}";
                }
            }

            // Otherwise, check for normal grade levels G1-G10
            if ($num >= 1 && $num <= 12) {
                return "Grade {$num}";
            }
        }

        // Fallbacks if no number is present
        if (str_contains($input, 'kinder') || str_contains($input, 'kind')) {
            if (str_contains($input, '2')) {
                return 'Kinder 2';
            }
            return 'Kinder 1';
        }

        return $input;
    }

    /**
     * Send Message back to Facebook Messenger API
     */
    private function sendMessage($recipientPsid, $messageText)
    {
        $accessToken = env('MESSENGER_PAGE_ACCESS_TOKEN');

        if (!$accessToken) {
            Log::error("Messenger page access token is not set in .env");
            return;
        }

        Log::info("Facebook Bot sending message to PSID {$recipientPsid}: {$messageText}");

        $response = Http::post("https://graph.facebook.com/v19.0/me/messages?access_token={$accessToken}", [
            'recipient' => [
                'id' => $recipientPsid
            ],
            'message' => [
                'text' => $messageText
            ]
        ]);

        Log::info("Facebook Send Response Status: " . $response->status() . " Body: " . $response->body());
    }

    /**
     * Setup Messenger Profile (Get Started button & Ice Breakers)
     */
    public function setupMessengerProfile()
    {
        $accessToken = env('MESSENGER_PAGE_ACCESS_TOKEN');

        if (!$accessToken) {
            return response()->json(['error' => 'Messenger token not set'], 500);
        }

        $response = Http::post("https://graph.facebook.com/v19.0/me/messenger_profile?access_token={$accessToken}", [
            'get_started' => [
                'payload' => 'GET_STARTED'
            ],
            'ice_breakers' => [
                [
                    'question' => 'Enrollment Status',
                    'payload' => 'CHECK_ENROLLMENT_STATUS'
                ],
                [
                    'question' => 'Inquiries (COMING SOON)',
                    'payload' => 'INQUIRIES_COMING_SOON'
                ]
            ]
        ]);

        return response()->json([
            'status' => $response->status(),
            'response' => $response->json(),
        ]);
    }
}
