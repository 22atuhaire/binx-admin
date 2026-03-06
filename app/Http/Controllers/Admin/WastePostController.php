<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CollectionJob;
use App\Models\User;
use App\Models\WastePost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WastePostController extends Controller
{
    /**
     * Display a listing of all waste posts with filtering.
     */
    public function index(Request $request): View
    {
        $query = WastePost::with(['user', 'latestJob.collector']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by waste type/category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Filter by location
        if ($request->filled('location')) {
            $query->where('location', 'LIKE', '%'.$request->location.'%');
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Search by title or donor name
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'LIKE', '%'.$request->search.'%')
                    ->orWhereHas('user', function ($userQuery) use ($request) {
                        $userQuery->where('name', 'LIKE', '%'.$request->search.'%');
                    });
            });
        }

        $wastePosts = $query->latest()->paginate(20)->withQueryString();

        // Get unique categories for filter dropdown
        $categories = WastePost::distinct()->pluck('category')->sort()->values();

        return view('admin.waste-posts.index', compact('wastePosts', 'categories'));
    }

    /**
     * Display the specified waste post details.
     */
    public function show(WastePost $wastePost): View
    {
        $wastePost->load(['user', 'jobs.collector', 'latestJob.collector']);

        // Get active collectors for assignment
        $activeCollectors = User::where('role', User::ROLE_COLLECTOR)
            ->where('status', User::STATUS_ACTIVE)
            ->whereNull('suspended_at')
            ->orderBy('name')
            ->get();

        return view('admin.waste-posts.show', compact('wastePost', 'activeCollectors'));
    }

    /**
     * Remove the specified waste post from storage.
     */
    public function destroy(WastePost $wastePost): RedirectResponse
    {
        $title = $wastePost->title;
        $wastePost->delete();

        return redirect()->route('admin.waste-posts.index')
            ->with('success', "Waste post '{$title}' deleted successfully.");
    }

    /**
     * Manually assign a collector to a waste post.
     */
    public function assign(Request $request, WastePost $wastePost): RedirectResponse
    {
        $request->validate([
            'collector_id' => ['required', 'exists:users,id'],
        ]);

        // Verify the user is actually a collector
        $collector = User::findOrFail($request->collector_id);
        if (! $collector->isCollector() || ! $collector->isActive()) {
            return redirect()->back()->with('error', 'Selected user is not an active collector.');
        }

        // Check if waste post is already assigned
        if ($wastePost->isTaken() || $wastePost->isCompleted()) {
            return redirect()->back()->with('error', 'This waste post is already assigned or completed.');
        }

        // Create collection job
        CollectionJob::create([
            'waste_post_id' => $wastePost->id,
            'collector_id' => $collector->id,
            'status' => 'pending',
            'assigned_at' => now(),
        ]);

        // Update waste post status
        $wastePost->update(['status' => WastePost::STATUS_TAKEN]);

        return redirect()->back()
            ->with('success', "Waste post assigned to {$collector->name} successfully.");
    }

    /**
     * Cancel a waste post.
     */
    public function cancel(WastePost $wastePost): RedirectResponse
    {
        if ($wastePost->isCompleted()) {
            return redirect()->back()->with('error', 'Cannot cancel a completed waste post.');
        }

        $wastePost->cancel();

        return redirect()->back()
            ->with('success', 'Waste post cancelled successfully.');
    }

    /**
     * Get active collectors for assignment dropdown.
     */
    public function getActiveCollectors()
    {
        return User::where('role', User::ROLE_COLLECTOR)
            ->where('status', User::STATUS_ACTIVE)
            ->whereNull('suspended_at')
            ->select('id', 'name', 'phone', 'address')
            ->orderBy('name')
            ->get();
    }
}
