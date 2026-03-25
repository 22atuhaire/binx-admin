<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WastePostResource;
use App\Models\CollectionJob;
use App\Models\User;
use App\Models\WastePost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WastePostController extends Controller
{
    /**
     * Get all available waste posts with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = WastePost::with(['user', 'latestJob.collector'])
            ->whereIn('status', [WastePost::STATUS_OPEN, WastePost::STATUS_PENDING]);

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
        $validator = Validator::make($request->all(), [
            'waste_types' => ['required', 'array', 'min:1'],
            'waste_types.*' => ['string', 'max:50', Rule::in(WastePost::FOOD_WASTE_TYPES)],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'pickup_time' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string', 'max:1000'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user() ?? $this->resolveGuestDonor();
        $userId = $user->id;

        $wastePost = WastePost::create([
            'user_id' => $userId,
            'donor_id' => $userId,
            'title' => implode(', ', $request->input('waste_types', [])),
            'description' => $request->input('notes') ?? 'Created from mobile app',
            'category' => $request->input('waste_types.0'),
            'location' => $request->input('address'),
            'waste_types' => $request->input('waste_types'),
            'quantity' => $request->input('quantity'),
            'notes' => $request->input('notes'),
            'pickup_time' => $request->input('pickup_time'),
            'address' => $request->input('address'),
            'instructions' => $request->input('instructions'),
            'photos' => $request->input('photos'),
            'status' => WastePost::STATUS_PENDING,
            'collector_id' => null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Waste post created successfully',
            'data' => [
                'id' => $wastePost->id,
                'status' => $wastePost->status,
                'estimated_pickup_time' => null,
            ],
        ], 201);
    }

    private function resolveGuestDonor(): User
    {
        return User::query()->firstOrCreate(
            ['email' => 'guest-donor@binx.local'],
            [
                'name' => 'Guest Donor',
                'password' => Hash::make(Str::random(40)),
                'role' => User::ROLE_DONOR,
                'status' => User::STATUS_ACTIVE,
            ]
        );
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
        $categories = collect(WastePost::FOOD_WASTE_TYPES)
            ->merge(
                WastePost::query()
                    ->distinct()
                    ->pluck('category')
                    ->filter()
                    ->all()
            )
            ->unique()
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
