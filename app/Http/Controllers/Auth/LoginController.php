<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class LoginController extends Controller
{
    /**
     * Handle user login
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email' => ['required', 'string', 'email'],
                'password' => ['required', 'string'],
                'remember' => ['boolean'],
            ]);

            // Rate limiting
            $key = 'login.' . $request->ip();
            if (RateLimiter::tooManyAttempts($key, 5)) {
                $seconds = RateLimiter::availableIn($key);
                return response()->json([
                    'message' => 'Too many login attempts. Please try again in ' . $seconds . ' seconds.',
                ], Response::HTTP_TOO_MANY_REQUESTS);
            }

            // Find user by email
            $user = User::where('email', $validated['email'])->first();

            // Check credentials
            if (!$user || !Hash::check($validated['password'], $user->password)) {
                RateLimiter::hit($key, 60); // Lock for 1 minute

                return response()->json([
                    'message' => 'The provided credentials are incorrect.',
                    'errors' => [
                        'email' => ['The provided credentials are incorrect.']
                    ]
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Clear rate limiting on successful login
            RateLimiter::clear($key);

            // Create token with appropriate expiration
            $tokenName = 'auth-token';
            $expiresAt = $validated['remember'] ?? false ? now()->addDays(30) : now()->addDay();

            $token = $user->createToken($tokenName, ['*'], $expiresAt)->plainTextToken;

            return response()->json([
                'message' => 'Login successful',
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                ],
                'token' => $token,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Login failed',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Handle user logout
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            // Revoke the current access token
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Logout successful',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Logout failed',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Handle logout from all devices
     */
    public function logoutAll(Request $request): JsonResponse
    {
        try {
            // Revoke all tokens for the user
            $request->user()->tokens()->delete();

            return response()->json([
                'message' => 'Logged out from all devices successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Logout from all devices failed',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get authenticated user data
     */
    public function user(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve user data',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
