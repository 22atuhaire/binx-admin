<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(Request $request): JsonResponse
    {
        if (! $request->filled('password_confirmation') && $request->filled('confirm_password')) {
            $request->merge([
                'password_confirmation' => $request->input('confirm_password'),
            ]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'phone' => ['nullable', 'string', 'max:20'],
            'location' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'in:donor,collector'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'location' => $validated['location'] ?? null,
            'role' => $validated['role'],
            'status' => User::STATUS_PENDING,
            'email_verified_at' => now(), // Auto-verify for API
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'user' => new UserResource($user),
            'token' => $token,
        ], 201);
    }

    /**
     * Register a new collector.
     */
    public function collectorRegister(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255|unique:users',
            'password' => ['required', Password::defaults()],
            'vehicle_type' => 'required|string|max:100',
            // 'id_document' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120', // Max 5MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Handle ID document upload
        $idDocumentPath = null;
        if ($request->hasFile('id_document')) {
            $idDocumentPath = $request->file('id_document')->store('id_documents', 'public');
        }

        // Create collector user
        $user = User::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => User::ROLE_COLLECTOR,
            'vehicle_type' => $request->vehicle_type,
            'id_document' => $idDocumentPath,
            'status' => User::STATUS_PENDING,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Registration submitted for approval',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'role' => $user->role,
                'vehicle_type' => $user->vehicle_type,
                'status' => $user->status,
            ],
        ], 201);
    }

    /**
     * Login a collector.
     */
    public function collectorLogin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required'],
        ]);

        $user = User::query()->where('phone', $validated['phone'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        if (! $user->isCollector()) {
            return response()->json([
                'message' => 'Only collectors can log in here',
            ], 403);
        }

        if ($user->isBlocked()) {
            return response()->json([
                'message' => 'Your account has been blocked',
            ], 403);
        }

        $user->tokens()->delete();

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    /**
     * Login a user.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required_without:phone', 'nullable', 'email'],
            'phone' => ['required_without:email', 'nullable', 'string'],
            'password' => ['required'],
        ]);

        // Try to find user by email or phone
        $user = User::query()
            ->where(function ($query) use ($request, $validated) {
                if ($request->filled('email')) {
                    $query->where('email', $validated['email']);
                }
                if ($request->filled('phone')) {
                    $query->orWhere('phone', $validated['phone']);
                }
            })
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        // Check if user is blocked
        if ($user->isBlocked()) {
            return response()->json([
                'message' => 'Your account has been blocked',
            ], 403);
        }

        // Revoke existing tokens
        $user->tokens()->delete();

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    /**
     * Logout a user.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout successful',
        ]);
    }

    /**
     * Refresh the authentication token.
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();

        // Revoke old token
        $request->user()->currentAccessToken()->delete();

        // Create new token
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Token refreshed successfully',
            'token' => $token,
        ]);
    }

    /**
     * Get current authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user()),
        ]);
    }
}
