<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\EnrollmentApplicant;
use App\Models\Student;
use App\Services\MicrosoftGraphService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

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
            if ($mode === 'subscribe' && $token === config('services.facebook.verify_token')) {
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

        // Direct AMIS ID / Student ID lookup check (runs at any time when not in a flow)
        $cleanMessageText = trim($messageText);
        $isAmisId = preg_match('/^\d{4,8}$/', $cleanMessageText) || preg_match('/^amis-\d+/i', $cleanMessageText) || preg_match('/^amis\d+/i', $cleanMessageText);
        if ($isAmisId && $session['step'] === 0) {
            Cache::forget($sessionKey);
            $this->sendMessage($senderPsid, "Checking ID: {$cleanMessageText}...");
            $statusResult = $this->lookupEnrollmentStatusById($cleanMessageText);
            $this->sendMessage($senderPsid, $statusResult);
            return;
        }

        // Handle inquiries or inquiries payload
        if ($normalizedText === 'inquiries_coming_soon' || $normalizedText === 'inquiries' || $normalizedText === 'inquiries (coming soon)' || str_contains($normalizedText, 'inquiry')) {
            $this->sendMessage($senderPsid, "The Inquiries feature is coming soon! 🚀\n\nFor now, please choose 'Enrollment Status' to check student enrollment status.");
            $this->sendMainMenu($senderPsid);
            return;
        }

        // Handle menu triggers
        $menuTriggers = ['hi', 'hello', 'start', 'get_started', 'menu', 'info', 'amis', 'back', 'restart'];
        if (in_array($normalizedText, $menuTriggers)) {
            $session = [
                'step' => 0,
                'data' => []
            ];
            Cache::put($sessionKey, $session, now()->addMinutes(15));
            $this->sendMainMenu($senderPsid);
            return;
        }

        // Handle enrollment flow start triggers
        $enrollmentTriggers = ['check_enrollment_status', 'enrollment status', 'check status', 'enrollment', 'status'];
        if (in_array($normalizedText, $enrollmentTriggers)) {
            $session = [
                'step' => 100,
                'data' => []
            ];
            Cache::put($sessionKey, $session, now()->addMinutes(15));

            $quickReplies = [
                ['content_type' => 'text', 'title' => 'Search by Name', 'payload' => 'ENROLLMENT_SEARCH_NAME'],
                ['content_type' => 'text', 'title' => 'Search by ID', 'payload' => 'ENROLLMENT_SEARCH_ID'],
                ['content_type' => 'text', 'title' => 'Back to Menu', 'payload' => 'GET_STARTED']
            ];
            $this->sendMessageWithQuickReplies($senderPsid, "📝 Enrollment Status\n\nHow would you like to check the enrollment status?", $quickReplies);
            return;
        }

        // Handle resend credentials triggers
        $resendTriggers = ['resend_credentials', 'resend credentials', 'forgot credentials', 'get credentials', 'resend password', 'reset_password', 'reset password'];
        if (in_array($normalizedText, $resendTriggers)) {
            $session = [
                'step' => 200,
                'data' => []
            ];
            Cache::put($sessionKey, $session, now()->addMinutes(15));

            $quickReplies = [
                ['content_type' => 'text', 'title' => 'Search by Name', 'payload' => 'RESEND_SEARCH_NAME'],
                ['content_type' => 'text', 'title' => 'Search by ID', 'payload' => 'RESEND_SEARCH_ID'],
                ['content_type' => 'text', 'title' => 'Back to Menu', 'payload' => 'GET_STARTED']
            ];
            $this->sendMessageWithQuickReplies($senderPsid, "🔐 Resend Credentials\nNote: use your only official AMIS email @amis.edu.ph\n\nHow would you like to search for the student?", $quickReplies);
            return;
        }

        // Handle search method selections (both payload postbacks and raw text)
        if ($normalizedText === 'enrollment_search_name' || ($session['step'] === 100 && $normalizedText === 'search by name')) {
            $session = [
                'step' => 1,
                'data' => []
            ];
            Cache::put($sessionKey, $session, now()->addMinutes(15));
            $quickReplies = [
                ['content_type' => 'text', 'title' => 'Back to Menu', 'payload' => 'GET_STARTED']
            ];
            $this->sendMessageWithQuickReplies($senderPsid, "Please provide the student's FULL NAME (First Name and Last Name):", $quickReplies);
            return;
        }

        if ($normalizedText === 'enrollment_search_id' || ($session['step'] === 100 && $normalizedText === 'search by id')) {
            $session = [
                'step' => 20,
                'data' => []
            ];
            Cache::put($sessionKey, $session, now()->addMinutes(15));
            $quickReplies = [
                ['content_type' => 'text', 'title' => 'Back to Menu', 'payload' => 'GET_STARTED']
            ];
            $this->sendMessageWithQuickReplies($senderPsid, "Please provide the AMIS Student ID / Student Number:", $quickReplies);
            return;
        }

        if ($normalizedText === 'resend_search_name' || ($session['step'] === 200 && $normalizedText === 'search by name')) {
            $session = [
                'step' => 30,
                'data' => []
            ];
            Cache::put($sessionKey, $session, now()->addMinutes(15));
            $quickReplies = [
                ['content_type' => 'text', 'title' => 'Back to Menu', 'payload' => 'GET_STARTED']
            ];
            $this->sendMessageWithQuickReplies($senderPsid, "Please provide the student's FULL NAME (First Name and Last Name):", $quickReplies);
            return;
        }

        if ($normalizedText === 'resend_search_id' || ($session['step'] === 200 && $normalizedText === 'search by id')) {
            $session = [
                'step' => 40,
                'data' => []
            ];
            Cache::put($sessionKey, $session, now()->addMinutes(15));
            $quickReplies = [
                ['content_type' => 'text', 'title' => 'Back to Menu', 'payload' => 'GET_STARTED']
            ];
            $this->sendMessageWithQuickReplies($senderPsid, "Please provide the AMIS ID or Student ID:", $quickReplies);
            return;
        }

        switch ($session['step']) {
            case 0:
                $this->sendMainMenu($senderPsid);
                break;

            // --- ENROLLMENT STATUS BY NAME FLOW ---
            case 1:
                $session['data']['name'] = $messageText;
                $session['step'] = 2;
                Cache::put($sessionKey, $session, now()->addMinutes(15));

                $quickReplies = [
                    ['content_type' => 'text', 'title' => 'Back to Menu', 'payload' => 'GET_STARTED']
                ];
                $this->sendMessageWithQuickReplies($senderPsid, "What is the GRADE LEVEL applied for? (e.g., Grade 1, Grade 5, Kinder)", $quickReplies);
                break;

            case 2:
                $session['data']['grade'] = $messageText;
                $session['step'] = 3;
                Cache::put($sessionKey, $session, now()->addMinutes(15));

                $quickReplies = [
                    ['content_type' => 'text', 'title' => 'Back to Menu', 'payload' => 'GET_STARTED']
                ];
                $this->sendMessageWithQuickReplies($senderPsid, "What is the student's BIRTHDATE? (Format: MM-DD-YYYY, e.g. 04-30-2020)", $quickReplies);
                break;

            case 3:
                $birthdate = trim($messageText);
                $name = $session['data']['name'];
                $grade = $session['data']['grade'];

                Cache::forget($sessionKey);
                $this->sendMessage($senderPsid, "Checking {$name}...");
                $statusResult = $this->lookupEnrollmentStatus($name, $grade, $birthdate);
                $this->sendMessage($senderPsid, $statusResult);
                $this->sendMainMenu($senderPsid);
                break;

            // --- ENROLLMENT STATUS BY ID FLOW ---
            case 20:
                $session['data']['student_id'] = $messageText;
                $session['step'] = 21;
                Cache::put($sessionKey, $session, now()->addMinutes(15));

                $quickReplies = [
                    ['content_type' => 'text', 'title' => 'Back to Menu', 'payload' => 'GET_STARTED']
                ];
                $this->sendMessageWithQuickReplies($senderPsid, "What is the GRADE LEVEL applied for? (e.g., Grade 1, Grade 5, Kinder)", $quickReplies);
                break;

            case 21:
                $session['data']['grade'] = $messageText;
                $session['step'] = 22;
                Cache::put($sessionKey, $session, now()->addMinutes(15));

                $quickReplies = [
                    ['content_type' => 'text', 'title' => 'Back to Menu', 'payload' => 'GET_STARTED']
                ];
                $this->sendMessageWithQuickReplies($senderPsid, "What is the student's BIRTHDATE? (Format: MM-DD-YYYY, e.g. 04-30-2020)", $quickReplies);
                break;

            case 22:
                $birthdate = trim($messageText);
                $studentId = $session['data']['student_id'];
                $grade = $session['data']['grade'];

                Cache::forget($sessionKey);
                $this->sendMessage($senderPsid, "Checking ID {$studentId}...");
                $statusResult = $this->lookupEnrollmentStatusByIDGradeBirthdate($studentId, $grade, $birthdate);
                $this->sendMessage($senderPsid, $statusResult);
                $this->sendMainMenu($senderPsid);
                break;

            // --- RESEND CREDENTIALS BY NAME FLOW ---
            case 30:
                $session['data']['name'] = $messageText;
                $session['step'] = 31;
                Cache::put($sessionKey, $session, now()->addMinutes(15));

                $quickReplies = [
                    ['content_type' => 'text', 'title' => 'Back to Menu', 'payload' => 'GET_STARTED']
                ];
                $this->sendMessageWithQuickReplies($senderPsid, "What is the GRADE LEVEL? (e.g., Grade 1, Grade 5, Kinder)", $quickReplies);
                break;

            case 31:
                $session['data']['grade'] = $messageText;
                $session['step'] = 32;
                Cache::put($sessionKey, $session, now()->addMinutes(15));

                $quickReplies = [
                    ['content_type' => 'text', 'title' => 'Back to Menu', 'payload' => 'GET_STARTED']
                ];
                $this->sendMessageWithQuickReplies($senderPsid, "To verify your identity, what is the student's BIRTHDATE? (Format: MM-DD-YYYY, e.g. 04-30-2010)", $quickReplies);
                break;

            case 32:
                $birthdate = trim($messageText);
                $name = $session['data']['name'];
                $grade = $session['data']['grade'];

                Cache::forget($sessionKey);
                $this->sendMessage($senderPsid, "Verifying details for {$name}...");
                $result = $this->handleBotResendCredentialsByName($name, $grade, $birthdate);
                $this->sendMessage($senderPsid, $result);
                $this->sendMainMenu($senderPsid);
                break;

            // --- RESEND CREDENTIALS BY ID FLOW ---
            case 40:
                $session['data']['student_id'] = $messageText;
                $session['step'] = 41;
                Cache::put($sessionKey, $session, now()->addMinutes(15));

                $quickReplies = [
                    ['content_type' => 'text', 'title' => 'Back to Menu', 'payload' => 'GET_STARTED']
                ];
                $this->sendMessageWithQuickReplies($senderPsid, "What is the GRADE LEVEL? (e.g., Grade 1, Grade 5, Kinder)", $quickReplies);
                break;

            case 41:
                $session['data']['grade'] = $messageText;
                $session['step'] = 42;
                Cache::put($sessionKey, $session, now()->addMinutes(15));

                $quickReplies = [
                    ['content_type' => 'text', 'title' => 'Back to Menu', 'payload' => 'GET_STARTED']
                ];
                $this->sendMessageWithQuickReplies($senderPsid, "To verify your identity, what is the student's BIRTHDATE? (Format: MM-DD-YYYY, e.g. 04-30-2010)", $quickReplies);
                break;

            case 42:
                $birthdate = trim($messageText);
                $studentId = $session['data']['student_id'];
                $grade = $session['data']['grade'];

                Cache::forget($sessionKey);
                $this->sendMessage($senderPsid, "Verifying details for ID {$studentId}...");
                $result = $this->handleBotResendCredentialsById($studentId, $grade, $birthdate);
                $this->sendMessage($senderPsid, $result);
                $this->sendMainMenu($senderPsid);
                break;

            // --- SELECT METHOD FALLBACKS ---
            case 100:
                if ($normalizedText === 'enrollment_search_name' || $normalizedText === '1' || $normalizedText === 'one' || str_contains($normalizedText, 'name')) {
                    $session = ['step' => 1, 'data' => []];
                    Cache::put($sessionKey, $session, now()->addMinutes(15));
                    $quickReplies = [['content_type' => 'text', 'title' => 'Back to Menu', 'payload' => 'GET_STARTED']];
                    $this->sendMessageWithQuickReplies($senderPsid, "Please provide the student's FULL NAME (First Name and Last Name):", $quickReplies);
                } elseif ($normalizedText === 'enrollment_search_id' || $normalizedText === '2' || $normalizedText === 'two' || str_contains($normalizedText, 'id')) {
                    $session = ['step' => 20, 'data' => []];
                    Cache::put($sessionKey, $session, now()->addMinutes(15));
                    $quickReplies = [['content_type' => 'text', 'title' => 'Back to Menu', 'payload' => 'GET_STARTED']];
                    $this->sendMessageWithQuickReplies($senderPsid, "Please provide the AMIS Student ID / Student Number:", $quickReplies);
                } else {
                    // Smart detection: user typed Name or ID directly
                    if ($isAmisId) {
                        $session = ['step' => 21, 'data' => ['student_id' => $cleanMessageText]];
                        Cache::put($sessionKey, $session, now()->addMinutes(15));
                        $quickReplies = [['content_type' => 'text', 'title' => 'Back to Menu', 'payload' => 'GET_STARTED']];
                        $this->sendMessageWithQuickReplies($senderPsid, "🔍 Smart Lookup: Detected AMIS ID: {$cleanMessageText}.\n\nWhat is the GRADE LEVEL applied for? (e.g., Grade 1, Grade 5, Kinder)", $quickReplies);
                    } else {
                        $session = ['step' => 2, 'data' => ['name' => $cleanMessageText]];
                        Cache::put($sessionKey, $session, now()->addMinutes(15));
                        $quickReplies = [['content_type' => 'text', 'title' => 'Back to Menu', 'payload' => 'GET_STARTED']];
                        $this->sendMessageWithQuickReplies($senderPsid, "🔍 Smart Lookup: Detected Student Name: {$cleanMessageText}.\n\nWhat is the GRADE LEVEL applied for? (e.g., Grade 1, Grade 5, Kinder)", $quickReplies);
                    }
                }
                break;

            case 200:
                if ($normalizedText === 'resend_search_name' || $normalizedText === '1' || $normalizedText === 'one' || str_contains($normalizedText, 'name')) {
                    $session = ['step' => 30, 'data' => []];
                    Cache::put($sessionKey, $session, now()->addMinutes(15));
                    $quickReplies = [['content_type' => 'text', 'title' => 'Back to Menu', 'payload' => 'GET_STARTED']];
                    $this->sendMessageWithQuickReplies($senderPsid, "Please provide the student's FULL NAME (First Name and Last Name):", $quickReplies);
                } elseif ($normalizedText === 'resend_search_id' || $normalizedText === '2' || $normalizedText === 'two' || str_contains($normalizedText, 'id')) {
                    $session = ['step' => 40, 'data' => []];
                    Cache::put($sessionKey, $session, now()->addMinutes(15));
                    $quickReplies = [['content_type' => 'text', 'title' => 'Back to Menu', 'payload' => 'GET_STARTED']];
                    $this->sendMessageWithQuickReplies($senderPsid, "Please provide the AMIS ID or Student ID:", $quickReplies);
                } else {
                    // Smart detection: user typed Name or ID directly
                    if ($isAmisId) {
                        $session = ['step' => 41, 'data' => ['student_id' => $cleanMessageText]];
                        Cache::put($sessionKey, $session, now()->addMinutes(15));
                        $quickReplies = [['content_type' => 'text', 'title' => 'Back to Menu', 'payload' => 'GET_STARTED']];
                        $this->sendMessageWithQuickReplies($senderPsid, "🔍 Smart Lookup: Detected AMIS ID: {$cleanMessageText}.\n\nWhat is the GRADE LEVEL? (e.g., Grade 1, Grade 5, Kinder)", $quickReplies);
                    } else {
                        $session = ['step' => 31, 'data' => ['name' => $cleanMessageText]];
                        Cache::put($sessionKey, $session, now()->addMinutes(15));
                        $quickReplies = [['content_type' => 'text', 'title' => 'Back to Menu', 'payload' => 'GET_STARTED']];
                        $this->sendMessageWithQuickReplies($senderPsid, "🔍 Smart Lookup: Detected Student Name: {$cleanMessageText}.\n\nWhat is the GRADE LEVEL? (e.g., Grade 1, Grade 5, Kinder)", $quickReplies);
                    }
                }
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
        $accessToken = config('services.facebook.page_access_token');

        if (!$accessToken) {
            Log::error("Messenger page access token is not set in config/services.php");
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
     * Send Main Menu with Carousel (Generic Template)
     */
    private function sendMainMenu($recipientPsid)
    {
        $elements = [
            [
                'title' => 'Enrollment Status',
                'subtitle' => 'Check your child\'s enrollment status in our system.',
                'buttons' => [
                    [
                        'type' => 'postback',
                        'title' => 'Check Status',
                        'payload' => 'CHECK_ENROLLMENT_STATUS'
                    ]
                ]
            ],
            [
                'title' => 'Resend Credentials',
                'subtitle' => 'Resend or reset your student Microsoft 365 login details.',
                'buttons' => [
                    [
                        'type' => 'postback',
                        'title' => 'Resend Credentials',
                        'payload' => 'RESEND_CREDENTIALS'
                    ]
                ]
            ],
            [
                'title' => 'Inquiries',
                'subtitle' => 'General inquiries and support (coming soon).',
                'buttons' => [
                    [
                        'type' => 'postback',
                        'title' => 'Inquiries',
                        'payload' => 'INQUIRIES_COMING_SOON'
                    ]
                ]
            ]
        ];

        $this->sendCarouselMessage($recipientPsid, $elements);
    }

    /**
     * Send Message with Buttons (Button Template) back to Facebook Messenger API
     */
    private function sendButtonMessage($recipientPsid, $text, $buttons)
    {
        $accessToken = config('services.facebook.page_access_token');

        if (!$accessToken) {
            Log::error("Messenger page access token is not set in config/services.php");
            return;
        }

        Log::info("Facebook Bot sending button template to PSID {$recipientPsid}: {$text}");

        $response = Http::post("https://graph.facebook.com/v19.0/me/messages?access_token={$accessToken}", [
            'recipient' => [
                'id' => $recipientPsid
            ],
            'message' => [
                'attachment' => [
                    'type' => 'template',
                    'payload' => [
                        'template_type' => 'button',
                        'text' => $text,
                        'buttons' => $buttons
                    ]
                ]
            ]
        ]);

        Log::info("Facebook Send Button Template Response Status: " . $response->status() . " Body: " . $response->body());
    }

    /**
     * Send Carousel Message (Generic Template) back to Facebook Messenger API
     */
    private function sendCarouselMessage($recipientPsid, $elements)
    {
        $accessToken = config('services.facebook.page_access_token');

        if (!$accessToken) {
            Log::error("Messenger page access token is not set in config/services.php");
            return;
        }

        Log::info("Facebook Bot sending carousel template to PSID {$recipientPsid}");

        $response = Http::post("https://graph.facebook.com/v19.0/me/messages?access_token={$accessToken}", [
            'recipient' => [
                'id' => $recipientPsid
            ],
            'message' => [
                'attachment' => [
                    'type' => 'template',
                    'payload' => [
                        'template_type' => 'generic',
                        'elements' => $elements
                    ]
                ]
            ]
        ]);

        Log::info("Facebook Send Carousel Response Status: " . $response->status() . " Body: " . $response->body());
    }

    /**
     * Send Message with floating Quick Replies back to Facebook Messenger API
     */
    private function sendMessageWithQuickReplies($recipientPsid, $text, $quickReplies)
    {
        $accessToken = config('services.facebook.page_access_token');

        if (!$accessToken) {
            Log::error("Messenger page access token is not set in config/services.php");
            return;
        }

        Log::info("Facebook Bot sending message with quick replies to PSID {$recipientPsid}: {$text}");

        $response = Http::post("https://graph.facebook.com/v19.0/me/messages?access_token={$accessToken}", [
            'recipient' => [
                'id' => $recipientPsid
            ],
            'message' => [
                'text' => $text,
                'quick_replies' => $quickReplies
            ]
        ]);

        Log::info("Facebook Send Quick Replies Response Status: " . $response->status() . " Body: " . $response->body());
    }

    /**
     * Setup Messenger Profile (Get Started button & Ice Breakers)
     */
    public function setupMessengerProfile()
    {
        $accessToken = config('services.facebook.page_access_token');

        if (!$accessToken) {
            return response()->json(['error' => 'Messenger token not set in config/services.php'], 500);
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
                    'question' => 'Resend M365 Credentials',
                    'payload' => 'RESEND_CREDENTIALS'
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

    /**
     * Database Lookup Logic by ID/Grade/Birthdate
     */
    private function lookupEnrollmentStatusByIDGradeBirthdate($id, $grade, $birthdate)
    {
        $id = trim($id);
        $normalizedGrade = $this->normalizeGradeLevel($grade);
        $formattedBirthdate = $this->parseBirthdate($birthdate);

        if (!$formattedBirthdate) {
            return "❌ Invalid birthdate format.\n\nPlease try again with a valid format (e.g. MM-DD-YYYY or Month DD, YYYY).";
        }

        $query = EnrollmentApplicant::where(function($q) use ($id) {
            $q->where('amis_student_id', $id)
              ->orWhere('id', $id);
        });

        if ($normalizedGrade) {
            $query->where('grade_level', $normalizedGrade);
        }

        $query->whereDate('date_of_birth', $formattedBirthdate);
        $applicant = $query->first();

        if (!$applicant) {
            // Also try looking up via Student's student_number / email
            $student = Student::where('student_number', $id)
                ->orWhere('school_email', $id)
                ->orWhere('ms_email', $id)
                ->first();
            if ($student && $student->applicant) {
                $applicant = $student->applicant;
                // Double check grade and birthdate for this applicant
                $appGrade = $this->normalizeGradeLevel($applicant->grade_level);
                $appBirthdate = $applicant->date_of_birth;
                if ($appBirthdate && $appBirthdate instanceof \Carbon\Carbon) {
                    $appBirthdate = $appBirthdate->format('Y-m-d');
                } else if ($appBirthdate) {
                    $appBirthdate = date('Y-m-d', strtotime((string)$appBirthdate));
                }
                
                if (($normalizedGrade && $appGrade !== $normalizedGrade) || 
                    ($appBirthdate !== $formattedBirthdate)) {
                    $applicant = null; // Mismatch
                }
            }
        }

        if (!$applicant) {
            return "❌ No Record Found\n\nWe couldn't find any enrollment record with ID: {$id}, Grade: {$grade}, and Birthdate: {$birthdate}. Please verify your details.";
        }

        return $this->formatStatusResponse($applicant);
    }

    /**
     * Helper to perform password reset in MS Graph and database, then email parent
     */
    private function resetMicrosoftPasswordAndEmail(Student $student, EnrollmentApplicant $applicant)
    {
        // Generate temporary password
        $tempPassword = 'Amis@' . strtoupper(Str::random(5)) . rand(10, 99);

        // Update database (reset password_changed_at so it counts as "temporary password")
        $student->update([
            'temp_password'       => $tempPassword,
            'password_changed_at' => null,
            'credentials_sent_at' => now(),
        ]);

        $msSuccess = false;
        $msError = null;

        // Reset password in Microsoft Graph AD
        try {
            $graph = new MicrosoftGraphService();
            $token = (new \ReflectionMethod($graph, 'getAccessToken'))->invoke($graph);
            $response = Http::withToken($token)
                ->patch("https://graph.microsoft.com/v1.0/users/{$student->school_email}", [
                    'passwordProfile' => [
                        'password'                      => $tempPassword,
                        'forceChangePasswordNextSignIn' => true,
                    ],
                ]);
            if ($response->successful()) {
                $msSuccess = true;
            } else {
                $msError = $response->json()['error']['message'] ?? 'Microsoft API error';
            }
        } catch (\Exception $e) {
            Log::error('Failed to reset Microsoft password via Facebook Bot: ' . $e->getMessage());
            $msError = $e->getMessage();
        }

        if (!$msSuccess) {
            return [
                'success' => false,
                'error' => "⚠️ Connection Error\n\nWe verified the account, but failed to sync the password change with Microsoft 365: " . ($msError ?? 'Unknown error') . "\n\nPlease try again later or contact the school administrator."
            ];
        }

        // Try to email the parent
        $parentEmail = $applicant->parent_email ?: $applicant->email;
        if ($parentEmail && $parentEmail !== 'NA') {
            try {
                $html = '<!DOCTYPE html><html><body style="font-family:Inter,Arial,sans-serif;background:#f3f4f6;padding:40px 20px;">
                <table width="520" style="background:white;border-radius:16px;overflow:hidden;margin:0 auto;box-shadow:0 4px 12px rgba(0,0,0,0.08);">
                <tr><td style="background:linear-gradient(135deg,#059669,#047857);padding:28px;text-align:center;">
                    <h2 style="color:white;margin:0;font-size:18px;">Student Credentials Resent</h2>
                    <p style="color:rgba(255,255,255,0.85);font-size:13px;margin:4px 0 0;">Al Munawwara Islamic School</p>
                </td></tr>
                <tr><td style="padding:28px 36px;">
                    <p style="color:#374151;font-size:14px;margin:0 0 20px;">The credentials for student <strong>' . $applicant->first_name . ' ' . $applicant->last_name . '</strong> have been resent via the Facebook chatbot:</p>
                    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:18px;margin-bottom:20px;">
                        <table width="100%">
                            <tr><td style="font-size:13px;color:#6b7280;padding:5px 0;width:140px;">Student Number</td><td style="font-size:15px;font-weight:800;color:#059669;">' . $student->student_number . '</td></tr>
                            <tr><td style="font-size:13px;color:#6b7280;padding:5px 0;">School Email</td><td style="font-size:14px;font-weight:600;color:#111827;">' . $student->school_email . '</td></tr>
                            <tr><td style="font-size:13px;color:#6b7280;padding:5px 0;">New Temp Password</td><td style="font-size:14px;font-weight:600;color:#111827;letter-spacing:0.05em;">' . $tempPassword . '</td></tr>
                        </table>
                    </div>
                    <p style="color:#6b7280;font-size:13px;">Login at <a href="https://portal.office.com" style="color:#059669;">portal.office.com</a> and change your password on first login.</p>
                </td></tr>
                </table></body></html>';
                Mail::html($html, fn($m) => $m->to($parentEmail)->subject('AMIS — Student Credentials Resent'));
            } catch (\Exception $e) {
                Log::error('Failed to send bot password reset email: ' . $e->getMessage());
            }
        }

        return [
            'success' => true,
            'temp_password' => $tempPassword
        ];
    }

    /**
     * Resend/Reset credentials requested by student/parent by providing Name, Grade, Birthdate
     */
    private function handleBotResendCredentialsByName($name, $grade, $birthdate)
    {
        $parts = explode(' ', $name);
        $firstName = $parts[0] ?? '';
        $lastName = count($parts) > 1 ? end($parts) : '';

        $normalizedGrade = $this->normalizeGradeLevel($grade);
        $formattedBirthdate = $this->parseBirthdate($birthdate);

        if (!$formattedBirthdate) {
            return "❌ Invalid birthdate format.\n\nPlease try again with a valid format (e.g. MM-DD-YYYY or Month DD, YYYY).";
        }

        $query = EnrollmentApplicant::where(function($q) use ($firstName, $lastName) {
            $q->where('first_name', 'like', "%{$firstName}%")
              ->where('last_name', 'like', "%{$lastName}%");
        });

        if ($normalizedGrade) {
            $query->where('grade_level', $normalizedGrade);
        }

        $query->whereDate('date_of_birth', $formattedBirthdate);
        $applicant = $query->first();

        if (!$applicant || !$applicant->student) {
            return "❌ Student Account Not Found\n\nWe couldn't find any student account for {$name} with Grade: {$grade} and Birthdate: {$birthdate}. Please verify and try again.";
        }

        $student = $applicant->student;

        // Perform credentials reset
        $resetResult = $this->resetMicrosoftPasswordAndEmail($student, $applicant);

        if (!$resetResult['success']) {
            return $resetResult['error'];
        }

        $tempPassword = $resetResult['temp_password'];

        // Option A: Show only AMIS email and password
        return "✅ Credentials Sent Successfully!\n\nHere are the M365 details:\n📧 School Email: {$student->school_email}\n🔑 Temp Password: {$tempPassword}\n\nPlease login to portal.office.com and change your password on first login.";
    }

    /**
     * Resend/Reset credentials requested by student/parent by providing Student ID, Grade, Birthdate
     */
    private function handleBotResendCredentialsById($studentId, $grade, $birthdate)
    {
        $id = trim($studentId);
        $normalizedGrade = $this->normalizeGradeLevel($grade);
        $formattedBirthdate = $this->parseBirthdate($birthdate);

        if (!$formattedBirthdate) {
            return "❌ Invalid birthdate format.\n\nPlease try again with a valid format (e.g. MM-DD-YYYY or Month DD, YYYY).";
        }

        // Search by ID/Email first
        $student = Student::where('student_number', $id)
            ->orWhere('school_email', $id)
            ->orWhere('ms_email', $id)
            ->first();

        if (!$student) {
            $applicant = EnrollmentApplicant::where('amis_student_id', $id)
                ->orWhere('id', $id)
                ->first();
            if ($applicant) {
                $student = $applicant->student;
            }
        }

        if (!$student || !$student->applicant) {
            return "❌ Student Account Not Found\n\nWe couldn't find any student account with ID: {$id}. Please verify and try again.";
        }

        $applicant = $student->applicant;
        
        // Verify grade and birthdate
        $appGrade = $this->normalizeGradeLevel($applicant->grade_level);
        $appBirthdate = $applicant->date_of_birth;
        if ($appBirthdate && $appBirthdate instanceof \Carbon\Carbon) {
            $appBirthdate = $appBirthdate->format('Y-m-d');
        } else if ($appBirthdate) {
            $appBirthdate = date('Y-m-d', strtotime((string)$appBirthdate));
        }

        if (($normalizedGrade && $appGrade !== $normalizedGrade) || 
            ($appBirthdate !== $formattedBirthdate)) {
            return "❌ Verification Failed\n\nThe grade level or birthdate you provided does not match our records. Please make sure the student ID, grade level, and birthdate are correct.";
        }

        // Perform credentials reset
        $resetResult = $this->resetMicrosoftPasswordAndEmail($student, $applicant);

        if (!$resetResult['success']) {
            return $resetResult['error'];
        }

        $tempPassword = $resetResult['temp_password'];

        // Option B: Show full name, email, password
        return "✅ Credentials Sent Successfully!\n\nHere are the student's M365 details:\n👤 Full Name: {$applicant->first_name} {$applicant->last_name}\n📧 School Email: {$student->school_email}\n🔑 Temp Password: {$tempPassword}\n\nPlease login to portal.office.com and change your password on first login.";
    }
}

