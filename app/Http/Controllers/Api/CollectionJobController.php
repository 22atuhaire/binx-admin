<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CollectionJobResource;
use App\Models\CollectionJob;
use App\Models\WastePost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CollectionJobController extends Controller
{
    /**
     * Get all jobs for the authenticated collector.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isCollector()) {
            return response()->json([
                'message' => 'Only collectors can view jobs',
            ], 403);
        }

        $status = $request->get('status'); // pending, in_progress, completed, all

        $query = CollectionJob::where('collector_id', $user->id)
            ->with(['wastePost.user', 'collector']);

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $jobs = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => CollectionJobResource::collection($jobs),
            'meta' => [
                'total' => $jobs->total(),
                'per_page' => $jobs->perPage(),
                'current_page' => $jobs->currentPage(),
                'last_page' => $jobs->lastPage(),
            ],
        ]);
    }

    /**
     * Get a single job details.
     */
    public function show(Request $request, CollectionJob $job): JsonResponse
    {
        $user = $request->user();

        if ($job->collector_id !== $user->id && ! $user->isAdmin()) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $job->load(['wastePost.user', 'collector']);

        return response()->json([
            'data' => new CollectionJobResource($job),
        ]);
    }

    /**
     * Update job status.
     */
    public function updateStatus(Request $request, CollectionJob $job): JsonResponse
    {
        $user = $request->user();

        if ($job->collector_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:pending,in_progress,completed,cancelled'],
        ]);

        // Status progression validation
        $validTransitions = [
            'pending' => ['in_progress', 'cancelled'],
            'in_progress' => ['completed', 'cancelled'],
            'completed' => [],
            'cancelled' => [],
        ];

        if (! in_array($validated['status'], $validTransitions[$job->status])) {
            return response()->json([
                'message' => "Cannot transition from {$job->status} to {$validated['status']}",
            ], 422);
        }

        $job->status = $validated['status'];

        if ($validated['status'] === 'completed') {
            $job->completed_at = now();
            // Update waste post status
            $job->wastePost->update(['status' => WastePost::STATUS_COMPLETED]);
        }

        $job->save();

        return response()->json([
            'message' => 'Job status updated successfully',
            'data' => new CollectionJobResource($job->load(['wastePost.user', 'collector'])),
        ]);
    }

    /**
     * Mark job as completed.
     */
    public function complete(Request $request, CollectionJob $job): JsonResponse
    {
        $user = $request->user();

        if ($job->collector_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        if ($job->status === 'completed') {
            return response()->json([
                'message' => 'Job is already completed',
            ], 422);
        }

        $job->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Update waste post status
        $job->wastePost->update(['status' => WastePost::STATUS_COMPLETED]);

        return response()->json([
            'message' => 'Job completed successfully',
            'data' => new CollectionJobResource($job->load(['wastePost.user', 'collector'])),
        ]);
    }
}
