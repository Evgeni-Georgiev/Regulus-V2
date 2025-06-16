<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\PasswordComplexity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class PasswordResetController extends Controller
{
    /**
     * Send password reset link
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email' => ['required', 'string', 'email'],
            ]);

            // Check if user exists
            $user = User::where('email', $validated['email'])->first();

            if (!$user) {
                // Don't reveal if email exists or not for security
                return response()->json([
                    'message' => 'If an account with that email exists, we have sent a password reset link.',
                ]);
            }

            // Send password reset link
            $status = Password::sendResetLink($validated);

            if ($status === Password::RESET_LINK_SENT) {
                return response()->json([
                    'message' => 'Password reset link sent to your email address.',
                ]);
            }

            return response()->json([
                'message' => 'Unable to send password reset link. Please try again.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send password reset link.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Reset password
     */
    public function resetPassword(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'token' => ['required', 'string'],
                'email' => ['required', 'string', 'email'],
                'password' => ['required', 'string', new PasswordComplexity()],
                'password_confirmation' => ['required', 'string', 'same:password'],
            ]);

            $status = Password::reset(
                $validated,
                function (User $user, string $password) {
                    $user->forceFill([
                        'password' => Hash::make($password)
                    ])->setRememberToken(Str::random(60));

                    $user->save();

                    // Revoke all existing tokens for security
                    $user->tokens()->delete();
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return response()->json([
                    'message' => 'Password reset successfully. Please login with your new password.',
                ]);
            }

            // Handle different error cases
            $message = match ($status) {
                Password::INVALID_TOKEN => 'Invalid or expired password reset token.',
                Password::INVALID_USER => 'No user found with this email address.',
                default => 'Password reset failed. Please try again.',
            };

            return response()->json([
                'message' => $message,
                'errors' => [
                    'email' => [$message]
                ]
            ], Response::HTTP_UNPROCESSABLE_ENTITY);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Password reset failed.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Change password for authenticated user
     */
    public function changePassword(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'current_password' => ['required', 'string'],
                'password' => ['required', 'string', new PasswordComplexity()],
                'password_confirmation' => ['required', 'string', 'same:password'],
            ]);

            $user = $request->user();

            // Verify current password
            if (!Hash::check($validated['current_password'], $user->password)) {
                return response()->json([
                    'message' => 'Current password is incorrect.',
                    'errors' => [
                        'current_password' => ['Current password is incorrect.']
                    ]
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Update password
            $user->update([
                'password' => Hash::make($validated['password']),
                'remember_token' => Str::random(60),
            ]);

            // Revoke all existing tokens except current one for security
            $currentToken = $request->user()->currentAccessToken();
            $user->tokens()->where('id', '!=', $currentToken->id)->delete();

            return response()->json([
                'message' => 'Password changed successfully.',
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Password change failed.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
