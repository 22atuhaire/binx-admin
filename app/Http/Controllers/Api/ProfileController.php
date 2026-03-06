<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Get authenticated user's profile.
     */
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'data' => new UserResource($request->user()),
        ]);
    }

    /**
     * Update authenticated user's profile.
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'location' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Get collector earnings.
     */
    public function earnings(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isCollector()) {
            return response()->json([
                'message' => 'Only collectors can view earnings',
            ], 403);
        }

        $earnings = $user->earnings()
            ->with('job.wastePost')
            ->latest()
            ->paginate($request->get('per_page', 20));

        $totalEarnings = $user->earnings()->sum('amount');
        $completedJobs = $user->collectorJobs()
            ->where('status', 'completed')
            ->count();

        return response()->json([
            'data' => $earnings,
            'stats' => [
                'total_earnings' => $totalEarnings,
                'completed_jobs' => $completedJobs,
                'average_per_job' => $completedJobs > 0 ? $totalEarnings / $completedJobs : 0,
            ],
            'meta' => [
                'total' => $earnings->total(),
                'per_page' => $earnings->perPage(),
                'current_page' => $earnings->currentPage(),
                'last_page' => $earnings->lastPage(),
            ],
        ]);
    }

    /**
     * Get user's history (jobs for collectors, posts for donors).
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isCollector()) {
            // Get collector's job history
            $history = $user->collectorJobs()
                ->with(['wastePost.user', 'collector'])
                ->latest()
                ->paginate($request->get('per_page', 20));

            return response()->json([
                'type' => 'collector_jobs',
                'data' => $history,
                'meta' => [
                    'total' => $history->total(),
                    'per_page' => $history->perPage(),
                    'current_page' => $history->currentPage(),
                    'last_page' => $history->lastPage(),
                ],
            ]);
        } else {
            // Get donor's waste post history
            $history = $user->wastePosts()
                ->with('latestJob.collector')
                ->latest()
                ->paginate($request->get('per_page', 20));

            return response()->json([
                'type' => 'donor_posts',
                'data' => $history,
                'meta' => [
                    'total' => $history->total(),
                    'per_page' => $history->perPage(),
                    'current_page' => $history->currentPage(),
                    'last_page' => $history->lastPage(),
                ],
            ]);
        }
    }
}
