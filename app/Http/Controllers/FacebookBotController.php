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

                        if (isset($event['message']) && !isset($event['message']['is_echo'])) {
                            $messageText = trim($event['message']['text'] ?? '');
                            $this->processMessage($senderPsid, $messageText);
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

        $triggerWords = ['hi', 'hello', 'enrollment status', 'check status', 'amis', 'info', 'start', 'status'];
        if (in_array(strtolower($messageText), $triggerWords) || $session['step'] === 0) {
            $session = [
                'step' => 1,
                'data' => []
            ];
            Cache::put($sessionKey, $session, now()->addMinutes(15));
            
            $this->sendMessage($senderPsid, "Assalamu Alaikum! I am the AMIS Virtual Assistant. 🤖\n\nTo check the student's enrollment status, please answer the following questions.\n\nWhat is the student's FULL NAME (First Name and Last Name)?");
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

                $this->sendMessage($senderPsid, "Last step, what is the student's BIRTHDATE? (Format: YYYY-MM-DD, e.g. 2018-05-30)");
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
     * Database Lookup Logic
     */
    private function lookupEnrollmentStatus($name, $grade, $birthdate)
    {
        $parts = explode(' ', $name);
        $firstName = $parts[0] ?? '';
        $lastName = count($parts) > 1 ? end($parts) : '';

        $cleanGrade = preg_replace('/[^0-9]/', '', $grade);
        
        $query = EnrollmentApplicant::where(function($q) use ($firstName, $lastName) {
            $q->where('first_name', 'like', "%{$firstName}%")
              ->where('last_name', 'like', "%{$lastName}%");
        });

        try {
            $formattedBirthdate = date('Y-m-d', strtotime($birthdate));
            $query->whereDate('date_of_birth', $formattedBirthdate);
        } catch (\Exception $e) {
            // Bypass date filter if parse error
        }

        $applicant = $query->first();

        if (!$applicant) {
            return "❌ No Record Found\n\nWe couldn't find any enrollment record for this student. Please make sure the spelling and birthdate are correct.";
        }

        switch (strtolower($applicant->status)) {
            case 'approved':
            case 'registered':
                return "✅ Account Created\n\nGreat news! The enrollment for {$applicant->first_name} {$applicant->last_name} has been approved, and the account is created in the system. You can now access Microsoft 365.";
            
            case 'submitted':
            case 'pending':
            case 'under_review':
                return "⏳ Pending Enrollment\n\nWe found the record for {$applicant->first_name} {$applicant->last_name}. The status is currently PENDING or under review by the admin. Please wait for an email or SMS notification for the next steps.";
            
            default:
                return "❌ No Record Found / Draft Application\n\nThe application form for {$applicant->first_name} is not yet fully submitted. Please complete the form on the enrollment portal.";
        }
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
}
