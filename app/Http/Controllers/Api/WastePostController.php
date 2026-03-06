<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WastePostResource;
use App\Models\CollectionJob;
use App\Models\WastePost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WastePostController extends Controller
{
    /**
     * Get all available waste posts with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = WastePost::with(['user', 'latestJob.collector'])->where('status', WastePost::STATUS_OPEN);

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Filter by location (partial match)
        if ($request->filled('location')) {
            $query->where('location', 'LIKE', '%'.$request->location.'%');
        }

        // Search by title
        if ($request->filled('search')) {
            $query->where('title', 'LIKE', '%'.$request->search.'%')
                ->orWhere('description', 'LIKE', '%'.$request->search.'%');
        }

        $wastePosts = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => WastePostResource::collection($wastePosts),
            'meta' => [
                'total' => $wastePosts->total(),
                'per_page' => $wastePosts->perPage(),
                'current_page' => $wastePosts->currentPage(),
                'last_page' => $wastePosts->lastPage(),
            ],
        ]);
    }

    /**
     * Get a single waste post details.
     */
    public function show(WastePost $wastePost): JsonResponse
    {
        $wastePost->load(['user', 'jobs.collector', 'latestJob.collector']);

        return response()->json([
            'data' => new WastePostResource($wastePost),
        ]);
    }

    /**
     * Get waste post location coordinates (for maps API).
     */
    public function location(WastePost $wastePost): JsonResponse
    {
        return response()->json([
            'id' => $wastePost->id,
            'location' => $wastePost->location,
            'latitude' => null, // TODO: Implement geocoding
            'longitude' => null, // TODO: Implement geocoding
        ]);
    }

    /**
     * Create a new waste post (donor only).
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isDonor()) {
            return response()->json([
                'message' => 'Only donors can create waste posts',
            ], 403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'category' => ['required', 'string', 'max:100'],
            'quantity' => ['nullable', 'string', 'max:100'],
            'location' => ['required', 'string', 'max:255'],
        ]);

        $wastePost = WastePost::create([
            'user_id' => $user->id,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'category' => $validated['category'],
            'quantity' => $validated['quantity'],
            'location' => $validated['location'],
            'status' => WastePost::STATUS_OPEN,
        ]);

        // TODO: Handle image upload if provided

        return response()->json([
            'message' => 'Waste post created successfully',
            'data' => new WastePostResource($wastePost->load('user')),
        ], 201);
    }

    /**
     * Update a waste post (donor only).
     */
    public function update(Request $request, WastePost $wastePost): JsonResponse
    {
        $user = $request->user();

        if ($wastePost->user_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        if (! $wastePost->isOpen()) {
            return response()->json([
                'message' => 'Can only update open waste posts',
            ], 422);
        }

        $validated = $request->validate([
            'title' => ['string', 'max:255'],
            'description' => ['string', 'max:1000'],
            'category' => ['string', 'max:100'],
            'quantity' => ['nullable', 'string', 'max:100'],
            'location' => ['string', 'max:255'],
        ]);

        $wastePost->update($validated);

        return response()->json([
            'message' => 'Waste post updated successfully',
            'data' => new WastePostResource($wastePost->load('user')),
        ]);
    }

    /**
     * Delete a waste post (donor or admin only).
     */
    public function destroy(Request $request, WastePost $wastePost): JsonResponse
    {
        $user = $request->user();

        if ($wastePost->user_id !== $user->id && ! $user->isAdmin()) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $wastePost->delete();

        return response()->json([
            'message' => 'Waste post deleted successfully',
        ]);
    }

    /**
     * Claim/assign a waste post (collector only).
     */
    public function claim(Request $request, WastePost $wastePost): JsonResponse
    {
        $user = $request->user();

        if (! $user->isCollector()) {
            return response()->json([
                'message' => 'Only collectors can claim waste posts',
            ], 403);
        }

        if ($user->isSuspended()) {
            return response()->json([
                'message' => 'Your account is suspended',
            ], 403);
        }

        if (! $wastePost->isOpen()) {
            return response()->json([
                'message' => 'This waste post is no longer available',
            ], 422);
        }

        // Check if user already has an active job for this post
        $existingJob = CollectionJob::where('waste_post_id', $wastePost->id)
            ->where('collector_id', $user->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->first();

        if ($existingJob) {
            return response()->json([
                'message' => 'You have already claimed this waste post',
            ], 422);
        }

        // Create collection job
        $job = CollectionJob::create([
            'waste_post_id' => $wastePost->id,
            'collector_id' => $user->id,
            'status' => 'pending',
            'assigned_at' => now(),
        ]);

        // Update waste post status
        $wastePost->update(['status' => WastePost::STATUS_TAKEN]);

        return response()->json([
            'message' => 'Waste post claimed successfully',
            'data' => [
                'job_id' => $job->id,
                'waste_post' => new WastePostResource($wastePost->load('user')),
            ],
        ], 201);
    }

    /**
     * Get available waste categories.
     */
    public function categories(): JsonResponse
    {
        $categories = WastePost::distinct()
            ->pluck('category')
            ->sort()
            ->values();

        return response()->json([
            'categories' => $categories,
        ]);
    }

    /**
     * Get common locations (for autocomplete).
     */
    public function locations(): JsonResponse
    {
        $locations = WastePost::distinct()
            ->pluck('location')
            ->sort()
            ->values()
            ->take(50);

        return response()->json([
            'locations' => $locations,
        ]);
    }
}
