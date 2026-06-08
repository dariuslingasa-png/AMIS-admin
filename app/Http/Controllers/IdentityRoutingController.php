<?php

namespace App\Http\Controllers;

use App\Exceptions\AccountNotRegisteredException;
use App\Models\LinkedIdentity;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class IdentityRoutingController extends Controller
{
    /**
     * POST /api/identity/login
     * Receive Azure AD ID Token or decoded email & tid to authenticate and route.
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $email = null;
            $tid = null;

            // 1. If raw token is provided, decode it locally
            if ($request->has('token')) {
                $payload = $this->decodeJwtPayload((string) $request->input('token'));
                if ($payload) {
                    $email = $payload['email'] ?? $payload['preferred_username'] ?? $payload['upn'] ?? null;
                    $tid = $payload['tid'] ?? null;
                }
            } else {
                // Otherwise accept direct email and tid (already decoded)
                $email = $request->input('email');
                $tid = $request->input('tid');
            }

            if (empty($email)) {
                return response()->json([
                    'error' => 'ERR_INVALID_TOKEN',
                    'message' => 'Token email is missing or token is invalid.'
                ], 400);
            }

            $email = strtolower($email);

            // 2. QUERY: Lookup LinkedIdentities where microsoft_email == token.email
            // We use indexed lookup to avoid lag
            $identity = LinkedIdentity::where('microsoft_email', $email)->first();

            if (!$identity) {
                throw new AccountNotRegisteredException();
            }

            // Fetch the associated User
            $user = $identity->user;
            if (!$user) {
                throw new AccountNotRegisteredException();
            }

            // Check if user status is active
            if (($user->account_status ?? 'verified') !== 'verified') {
                return response()->json([
                    'error' => 'ERR_ACCOUNT_DISABLED',
                    'message' => 'Your account is currently disabled.'
                ], 403);
            }

            // Fetch role
            $role = strtoupper((string) $user->role);

            // 3. Grant access token and route
            if ($role === 'TEACHER') {
                $route = '/portal/teacher/dashboard';
            } elseif ($role === 'STUDENT') {
                $route = '/portal/student/dashboard';
            } else {
                throw new AccountNotRegisteredException();
            }

            // Log user into Laravel session (if session-based auth is used)
            Auth::login($user);
            $request->session()->regenerate();

            // Generate a secure access token
            $accessToken = bin2hex(random_bytes(32));

            return response()->json([
                'access_token' => $accessToken,
                'token_type' => 'Bearer',
                'route' => $route,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => $user->role,
                    'email' => $user->email,
                ]
            ], 200);

        } catch (AccountNotRegisteredException $e) {
            // Re-throw or render directly to meet specs
            return $e->render($request);
        } catch (\Exception $e) {
            Log::error('OIDC Login error: ' . $e->getMessage());
            return response()->json([
                'error' => 'ERR_INTERNAL_SERVER_ERROR',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/identity/link
     * Link secondary Microsoft account (legacy) to authenticated user.
     */
    public function link(Request $request): JsonResponse
    {
        // 1. CONTEXT: Capture active current_user_id from the secure session
        // Must be authenticated
        $currentUser = Auth::user() ?? $request->user();

        if (!$currentUser) {
            return response()->json([
                'error' => 'ERR_UNAUTHORIZED',
                'message' => 'Authentication required.'
            ], 401);
        }

        // Must have an existing 'GENERATED' email linked
        $hasGeneratedLink = LinkedIdentity::where('user_id', $currentUser->id)
            ->where('identity_type', 'GENERATED')
            ->exists();

        if (!$hasGeneratedLink) {
            return response()->json([
                'error' => 'ERR_GENERATED_IDENTITY_REQUIRED',
                'message' => 'Active user session must have a GENERATED email linked first.'
            ], 403);
        }

        try {
            $secondaryEmail = null;
            $secondaryTid = null;

            // 2. INTERCEPT: Receive secondary Azure AD token payload
            if ($request->has('token')) {
                $payload = $this->decodeJwtPayload((string) $request->input('token'));
                if ($payload) {
                    $secondaryEmail = $payload['email'] ?? $payload['preferred_username'] ?? $payload['upn'] ?? null;
                    $secondaryTid = $payload['tid'] ?? null;
                }
            } else {
                $secondaryEmail = $request->input('email');
                $secondaryTid = $request->input('tid');
            }

            if (empty($secondaryEmail) || empty($secondaryTid)) {
                return response()->json([
                    'error' => 'ERR_INVALID_SECONDARY_TOKEN',
                    'message' => 'Secondary token must contain email and tid.'
                ], 400);
            }

            $secondaryEmail = strtolower($secondaryEmail);

            // 3. SANITIZATION: Verify secondary token.email does not already exist in LinkedIdentities
            $emailExists = LinkedIdentity::where('microsoft_email', $secondaryEmail)->exists();

            if ($emailExists) {
                return response()->json([
                    'error' => 'ERR_EMAIL_ALREADY_LINKED',
                    'message' => 'The legacy email is already linked to an account.'
                ], 422);
            }

            // 4. MUTATION: Insert legacy mapping in LinkedIdentities
            LinkedIdentity::create([
                'user_id' => $currentUser->id,
                'microsoft_email' => $secondaryEmail,
                'tenant_id' => $secondaryTid,
                'identity_type' => 'LEGACY',
            ]);

            // 5. RESPONSE: Return Status 200 Success.
            return response()->json([
                'status' => 'success',
                'message' => 'Legacy identity linked successfully.'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Account Linking error: ' . $e->getMessage());
            return response()->json([
                'error' => 'ERR_INTERNAL_SERVER_ERROR',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Decode JWT Payload locally (base64 URL decode)
     */
    private function decodeJwtPayload(string $jwt): ?array
    {
        $parts = explode('.', $jwt);
        if (count($parts) < 2) {
            return null;
        }
        $payload = base64_decode(strtr($parts[1], '-_', '+/'));
        return json_decode((string) $payload, true);
    }
}
