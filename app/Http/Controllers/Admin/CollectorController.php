<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RejectCollectorRequest;
use App\Http\Requests\SuspendCollectorRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CollectorController extends Controller
{
    /**
     * Display a listing of active collectors.
     */
    public function index(): View
    {
        $collectors = User::where('role', User::ROLE_COLLECTOR)
            ->where('status', User::STATUS_ACTIVE)
            ->withCount(['jobs as completed_jobs' => function (Builder $query) {
                $query->where('status', 'completed');
            }])
            ->latest()
            ->paginate(20);

        return view('admin.collectors.index', compact('collectors'));
    }

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

    /**
     * Suspend an active collector temporarily.
     */
    public function suspend(User $user, SuspendCollectorRequest $request): RedirectResponse
    {
        // Verify user is an active collector
        if (! $user->isCollector() || ! $user->isActive()) {
            return redirect()->back()->with('error', 'Invalid collector status.');
        }

        // Suspend the collector
        $user->suspend($request->validated()['reason']);

        return redirect()->back()->with('success', "Collector {$user->name} suspended successfully.");
    }

    /**
     * Reactivate a suspended collector.
     */
    public function reactivate(User $user): RedirectResponse
    {
        // Verify user is a collector and is suspended
        if (! $user->isCollector() || ! $user->isSuspended()) {
            return redirect()->back()->with('error', 'Collector is not suspended.');
        }

        // Reactivate the collector
        $user->reactivate();

        return redirect()->back()->with('success', "Collector {$user->name} reactivated successfully.");
    }

    /**
     * Display job history for a collector.
     */
    public function jobHistory(User $user): View
    {
        // Verify user is a collector
        if (! $user->isCollector()) {
            abort(404);
        }

        $jobs = $user->jobs()
            ->with('wastePost')
            ->latest()
            ->paginate(20);

        return view('admin.collectors.job-history', compact('user', 'jobs'));
    }
}
