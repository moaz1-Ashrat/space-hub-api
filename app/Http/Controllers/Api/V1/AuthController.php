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
use App\Http\Resources\AuthResource;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;

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
                    'password' => $data['password'], // hashed by User cast
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

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        $user = User::where('email', $credentials['email'])
            ->firstOrFail()
            ->load(['customer', 'spaceOwner', 'admin']);

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
        ]);
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
}
