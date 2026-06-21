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

        $triggerWords = ['hi', 'hello', 'enrollment status', 'check status', 'amis', 'info'];
        if (in_array(strtolower($messageText), $triggerWords) || $session['step'] === 0) {
            $session = [
                'step' => 1,
                'data' => []
            ];
            Cache::put($sessionKey, $session, now()->addMinutes(15));
            
            $this->sendMessage($senderPsid, "Assalamu Alaikum! Ako ang AMIS Virtual Assistant. 🤖\n\nPara ma-check ang iyong enrollment status, pakisagot ang mga sumusunod.\n\nAno ang BUONG PANGALAN (First Name at Last Name) ng Student?");
            return;
        }

        switch ($session['step']) {
            case 1:
                $session['data']['name'] = $messageText;
                $session['step'] = 2;
                Cache::put($sessionKey, $session, now()->addMinutes(15));

                $this->sendMessage($senderPsid, "Salamat. Ano ang GRADE LEVEL na in-enrollan? (Halimbawa: Grade 1, Grade 5, Kinder)");
                break;

            case 2:
                $session['data']['grade'] = $messageText;
                $session['step'] = 3;
                Cache::put($sessionKey, $session, now()->addMinutes(15));

                $this->sendMessage($senderPsid, "Huling hakbang, kailan ang BIRTHDATE ng student? (Format: YYYY-MM-DD, Halimbawa: 2018-05-30)");
                break;

            case 3:
                $birthdate = trim($messageText);
                $name = $session['data']['name'];
                $grade = $session['data']['grade'];

                Cache::forget($sessionKey);

                $this->sendMessage($senderPsid, "Salamat! Chine-check ko na ang record ni {$name} sa aming system. Sandali lamang...");

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
            return "❌ No Record Found\n\nPaumanhin, hindi namin nahanap ang record para sa student na ito sa aming 2026-2027 Enrollment list. Siguraduhing tama ang spelling at birthdate.";
        }

        switch (strtolower($applicant->status)) {
            case 'approved':
            case 'registered':
                return "✅ Account Created\n\nMagandang balita! Approved na ang enrollment ni {$applicant->first_name} {$applicant->last_name} at may account na sa system. Maaari niyo na ring ma-access ang Microsoft 365.";
            
            case 'submitted':
            case 'pending':
            case 'under_review':
                return "⏳ Pending Enrollment\n\nNahanap namin ang record ni {$applicant->first_name} {$applicant->last_name}. Ang status nito ay kasalukuyang PENDING o pinoproseso pa ng admin. Mag-antay ng email o text notification para sa susunod na hakbang.";
            
            default:
                return "❌ No Record Found / Draft Application\n\nHindi pa kumpleto ang pagsusumite ng form para kay {$applicant->first_name}. Mangyaring tapusin ang form sa portal.";
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

        Http::post("https://graph.facebook.com/v19.0/me/messages?access_token={$accessToken}", [
            'recipient' => [
                'id' => $recipientPsid
            ],
            'message' => [
                'text' => $messageText
            ]
        ]);
    }
}
