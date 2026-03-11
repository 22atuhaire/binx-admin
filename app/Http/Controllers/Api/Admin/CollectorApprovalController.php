<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CollectorApprovalController extends Controller
{
    /**
     * List all pending collectors.
     */
    public function pending(Request $request): JsonResponse
    {
        $pendingCollectors = User::query()
            ->where('role', User::ROLE_COLLECTOR)
            ->where('status', User::STATUS_PENDING)
            ->latest()
            ->paginate(20);

        return response()->json($pendingCollectors);
    }

    /**
     * Approve a pending collector.
     */
    public function approve(User $user): JsonResponse
    {
        if (! $user->isCollector()) {
            return response()->json([
                'message' => 'User is not a collector.',
            ], 422);
        }

        if (! $user->isPending()) {
            return response()->json([
                'message' => 'Collector is not pending approval.',
            ], 422);
        }

        $user->activate();

        return response()->json([
            'message' => 'Collector approved successfully.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'status' => $user->status,
            ],
        ]);
    }

    /**
     * Reject a pending collector with a reason.
     */
    public function reject(User $user, Request $request): JsonResponse
    {
        if (! $user->isCollector()) {
            return response()->json([
                'message' => 'User is not a collector.',
            ], 422);
        }

        if (! $user->isPending()) {
            return response()->json([
                'message' => 'Collector is not pending approval.',
            ], 422);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        $user->update([
            'status' => User::STATUS_BLOCKED,
            'rejection_reason' => $validated['reason'],
        ]);

        return response()->json([
            'message' => 'Collector rejected successfully.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'status' => $user->status,
                'rejection_reason' => $user->rejection_reason,
            ],
        ]);
    }
}
