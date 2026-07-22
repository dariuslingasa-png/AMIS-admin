<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class SelfieVerificationController extends Controller
{
    public function index()
    {
        return view('selfie-verification');
    }

    public function start(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'grade_level' => 'required|string|max:100',
        ]);

        $sessionId = Str::uuid()->toString();

        $data = [
            'session_id' => $sessionId,
            'full_name' => mb_strtoupper($validated['full_name']),
            'grade_level' => $validated['grade_level'],
            'status' => 'pending',
            'selfie_url' => null,
            'completed_at' => null,
        ];

        Cache::put("selfie_session_{$sessionId}", $data, 3600);

        $sessionUrl = route('selfie.session', ['session_id' => $sessionId]);

        return response()->json([
            'success' => true,
            'session_id' => $sessionId,
            'session_url' => $sessionUrl,
            'qr_code_url' => 'https://quickchart.io/qr?text=' . urlencode($sessionUrl) . '&dark=047857&light=ffffff&margin=1&format=png&size=300',
        ]);
    }

    public function sessionView($sessionId)
    {
        $data = Cache::get("selfie_session_{$sessionId}");

        if (!$data) {
            return redirect()->route('selfie.index')->with('error', 'Session expired or invalid.');
        }

        return view('selfie-capture', [
            'session' => $data,
        ]);
    }

    public function checkStatus($sessionId)
    {
        $data = Cache::get("selfie_session_{$sessionId}");

        if (!$data) {
            return response()->json(['status' => 'expired']);
        }

        return response()->json([
            'status' => $data['status'],
            'selfie_url' => $data['selfie_url'],
            'full_name' => $data['full_name'],
            'grade_level' => $data['grade_level'],
            'completed_at' => $data['completed_at'],
        ]);
    }

    public function upload(Request $request, $sessionId)
    {
        $data = Cache::get("selfie_session_{$sessionId}");

        if (!$data) {
            return response()->json(['success' => false, 'message' => 'Session expired or invalid.'], 404);
        }

        $request->validate([
            'image_data' => 'required|string',
        ]);

        $imageData = $request->input('image_data');

        if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
            $imageData = substr($imageData, strpos($imageData, ',') + 1);
            $imageData = base64_decode($imageData);

            if ($imageData === false) {
                return response()->json(['success' => false, 'message' => 'Invalid image payload.'], 400);
            }
        } else {
            return response()->json(['success' => false, 'message' => 'Invalid image format.'], 400);
        }

        $filename = "selfies/selfie_{$sessionId}_" . time() . ".png";
        Storage::disk('public')->put($filename, $imageData);
        $publicUrl = Storage::disk('public')->url($filename);

        $data['status'] = 'completed';
        $data['selfie_url'] = $publicUrl;
        $data['completed_at'] = now()->format('Y-m-d h:i A');

        Cache::put("selfie_session_{$sessionId}", $data, 3600);

        return response()->json([
            'success' => true,
            'selfie_url' => $publicUrl,
            'message' => 'Selfie verified and submitted successfully!',
        ]);
    }
}
