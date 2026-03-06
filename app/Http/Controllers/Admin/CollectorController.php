<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RejectCollectorRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CollectorController extends Controller
{
    /**
     * Display a listing of pending collectors awaiting approval.
     */
    public function pending(): View
    {
        $pendingCollectors = User::where('role', User::ROLE_COLLECTOR)
            ->where('status', User::STATUS_PENDING)
            ->latest()
            ->paginate(20);

        return view('admin.collectors.pending', compact('pendingCollectors'));
    }

    /**
     * Approve a pending collector application.
     */
    public function approve(User $user): RedirectResponse
    {
        // Verify user is a collector and has pending status
        if (! $user->isCollector() || ! $user->isPending()) {
            return redirect()->back()->with('error', 'Invalid collector status.');
        }

        // Activate the collector
        $user->activate();

        return redirect()->back()->with('success', "Collector {$user->name} approved successfully!");
    }

    /**
     * Reject a pending collector application with a reason.
     */
    public function reject(User $user, RejectCollectorRequest $request): RedirectResponse
    {
        // Verify user is a collector and has pending status
        if (! $user->isCollector() || ! $user->isPending()) {
            return redirect()->back()->with('error', 'Invalid collector status.');
        }

        // Block the collector and store the rejection reason
        $user->update([
            'status' => User::STATUS_BLOCKED,
            'rejection_reason' => $request->validated()['reason'],
        ]);

        return redirect()->back()->with('success', "Collector {$user->name} rejected successfully.");
    }
}
