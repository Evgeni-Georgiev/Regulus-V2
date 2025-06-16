<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class EmailVerificationController extends Controller
{
    /**
     * Verify email address
     */
    public function verify(Request $request, $id, $hash): JsonResponse
    {
        try {
            $user = User::findOrFail($id);

            // Check if the hash matches
            if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
                return response()->json([
                    'message' => 'Invalid verification link.',
                ], 400);
            }

            // Check if email is already verified
            if ($user->hasVerifiedEmail()) {
                return response()->json([
                    'message' => 'Email address is already verified.',
                ], 200);
            }

            // Mark email as verified
            if ($user->markEmailAsVerified()) {
                event(new Verified($user));
            }

            return response()->json([
                'message' => 'Email address verified successfully.',
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Email verification failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resend email verification notification
     */
    public function resend(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Check if email is already verified
            if ($user->hasVerifiedEmail()) {
                return response()->json([
                    'message' => 'Email address is already verified.',
                ], 200);
            }

            // Send verification email
            $user->sendEmailVerificationNotification();

            return response()->json([
                'message' => 'Verification email sent successfully.',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send verification email.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check email verification status
     */
    public function status(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            return response()->json([
                'verified' => $user->hasVerifiedEmail(),
                'email' => $user->email,
                'verified_at' => $user->email_verified_at,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to check verification status.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
