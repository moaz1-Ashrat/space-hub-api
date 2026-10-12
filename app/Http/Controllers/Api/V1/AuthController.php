<?php

namespace App\Http\Controllers\Api\V1;

use Throwable;
use App\Models\User;
use App\Models\Customer;
use App\Models\SpaceOwner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use App\Http\Resources\AuthResource;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Requests\Auth\ChangePasswordRequest;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        try {
            [$user, $token] = DB::transaction(function () use ($request) {
                $data = $request->validated();

                $user = User::create([
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'email' => $data['email'],
                    'password' => $data['password'],
                    'phone' => $data['phone'],
                    'gender' => $data['gender'],
                    'role' => $data['role'] === 'owner' ? 'space_owner' : 'customer',
                ]);

                if ($data['role'] === 'customer') {
                    Customer::create([
                        'id' => $user->id,
                        'favorite' => $data['favorite'] ?? null,
                    ]);
                }

                if ($data['role'] === 'owner') {
                    $normalizedTaxNumber = preg_replace('/\D/', '', $data['tax_registration_number']);

                    SpaceOwner::create([
                        'id' => $user->id,
                        'tax_registration_number' => $normalizedTaxNumber,
                    ]);
                }

                $token = $user->createToken('auth_token')->plainTextToken;

                return [$user->load(['customer', 'spaceOwner', 'admin']), $token];
            });

            return response()->json([
                'message' => 'Registered successfully',
                'data' => new AuthResource($user),
                'profile' => [
                    'customer' => $user->customer,
                    'space_owner' => $user->spaceOwner,
                    'admin' => $user->admin,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        if (! Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = User::where('email', $credentials['email'])
            ->firstOrFail()
            ->load(['customer', 'spaceOwner', 'admin']);

        if (! $user->is_active) {
            return response()->json([
                'message' => 'Your account has been suspended',
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Logged in successfully',
            'data' => new AuthResource($user),
            'profile' => [
                'customer' => $user->customer,
                'space_owner' => $user->spaceOwner,
                'admin' => $user->admin,
            ],
            'token' => $token,
            'token_type' => 'Bearer',
        ], 200);
    }

    public function logout(Request $request)
    {
        $token = $request->user()?->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load(['customer', 'spaceOwner', 'admin']);

        return response()->json([
            'message' => 'Authenticated user',
            'data' => new AuthResource($user),
            'profile' => [
                'customer' => $user->customer,
                'space_owner' => $user->spaceOwner,
                'admin' => $user->admin,
            ],
        ]);
    }

    /**
     * 3A: Send password reset link.
     */
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        // Always return generic message for security
        Password::sendResetLink($request->only('email'));

        return response()->json([
            'message' => 'If this email exists, a password reset link has been sent.',
        ], 200);
    }

    /**
     * 3B: Reset password using token.
     */
    public function resetPassword(ResetPasswordRequest $request)
    {
        $status = Password::reset(
            $request->validated(),
            function (User $user, string $password) {
                $user->password = $password;
                $user->save();

                // Revoke all tokens for security
                $user->tokens()->delete();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Password reset successfully.',
            ], 200);
        }

        return response()->json([
            'message' => __($status),
        ], 422);
    }

    /**
     * 3C: Update authenticated user's profile.
     */
    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $user->update($request->validated());
        $user->load(['customer', 'spaceOwner', 'admin']);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'data' => new AuthResource($user),
            'profile' => [
                'customer' => $user->customer,
                'space_owner' => $user->spaceOwner,
                'admin' => $user->admin,
            ],
        ]);
    }

    /**
     * 3D: Change password (keep current session).
     */
    public function changePassword(ChangePasswordRequest $request)
    {
        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect.',
            ], 422);
        }

        $user->password = $request->new_password;
        $user->save();

        // Revoke all other tokens except current one
        $currentToken = $user->currentAccessToken();
        if ($currentToken) {
            $user->tokens()->where('id', '!=', $currentToken->id)->delete();
        }

        return response()->json([
            'message' => 'Password changed successfully.',
        ]);
    }
}