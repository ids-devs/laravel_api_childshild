<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterClinicUserRequest;
use App\Models\ClinicUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

/**
 * @group Authentication
 * JWT-based authentication for dashboard users (clinics, ONGs, government, admin).
 */
class AuthController extends BaseController
{
    /**
     * Login
     *
     * Authenticate a dashboard user and receive a JWT token.
     *
     * @bodyParam email string required The user's email. Example: admin@childshield.mz
     * @bodyParam password string required The user's password. Example: secret123
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Login successful",
     *   "data": {
     *     "access_token": "eyJ0...",
     *     "token_type": "bearer",
     *     "expires_in": 3600,
     *     "user": { "id": 1, "name": "Admin", "email": "admin@childshield.mz", "roles": ["super-admin"] }
     *   }
     * }
     * @response 401 { "success": false, "message": "Invalid credentials" }
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        $user = ClinicUser::where('email', $credentials['email'])->active()->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return $this->error('Invalid credentials', 401);
        }

        try {
            $token = JWTAuth::fromUser($user);
        } catch (JWTException $e) {
            return $this->error('Could not create token', 500);
        }

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        return $this->success([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => config('jwt.ttl') * 60,
            'user'         => [
                'id'                => $user->id,
                'name'              => $user->name,
                'email'             => $user->email,
                'organization_type' => $user->organization_type,
                'roles'             => $user->getRoleNames(),
                'permissions'       => $user->getAllPermissions()->pluck('name'),
            ],
        ], 'Login successful');
    }

    /**
     * Register dashboard user
     *
     * Create a new dashboard user account (admin only or open registration with invite).
     * @authenticated
     * @middleware auth:api
     */
    public function register(RegisterClinicUserRequest $request): JsonResponse
    {
        $this->authorize('create', ClinicUser::class);

        $user = ClinicUser::create([
            'name'              => $request->name,
            'email'             => $request->email,
            'password'          => Hash::make($request->password),
            'organization_name' => $request->organization_name,
            'organization_type' => $request->organization_type,
            'location_id'       => $request->location_id,
        ]);

        $user->assignRole($request->organization_type);

        return $this->success([
            'user' => $user->only(['id', 'name', 'email', 'organization_type']),
        ], 'User created successfully', 201);
    }

    /**
     * Get authenticated user profile
     * @authenticated
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        return $this->success([
            'id'                => $user->id,
            'name'              => $user->name,
            'email'             => $user->email,
            'organization_name' => $user->organization_name,
            'organization_type' => $user->organization_type,
            'location'          => $user->location ? [
                'id' => $user->location->id,
                'province_id' => $user->location->province_id,
                'province' => $user->location->province?->name,
                'district_id' => $user->location->district_id,
                'district' => $user->location->district?->name,
            ] : null,
            'roles'             => $user->getRoleNames(),
            'permissions'       => $user->getAllPermissions()->pluck('name'),
        ]);
    }

    /**
     * Refresh JWT token
     * @authenticated
     */
    public function refresh(): JsonResponse
    {
        try {
            $token = JWTAuth::refresh();
            return $this->success(['access_token' => $token, 'token_type' => 'bearer']);
        } catch (JWTException $e) {
            return $this->error('Token refresh failed', 401);
        }
    }

    /**
     * Logout
     * @authenticated
     */
    public function logout(): JsonResponse
    {
        JWTAuth::invalidate(JWTAuth::getToken());
        return $this->success(null, 'Logged out successfully');
    }
}
