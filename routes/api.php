<?php

use App\Http\Controllers\Api\Admin\CollectorApprovalController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\CollectionJobController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\WastePostController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'timestamp' => now()]);
});
// Collector registration (public)
Route::post('/collector/register', [AuthController::class, 'collectorRegister']);
Route::post('/collector/login', [AuthController::class, 'collectorLogin']);

// Authentication routes (public)
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('auth:sanctum');
    Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
});

// Waste posts endpoints
Route::prefix('waste-posts')->group(function () {
    // Public endpoints
    Route::get('/', [WastePostController::class, 'index']);
    Route::get('/{wastePost}', [WastePostController::class, 'show']);
    Route::get('/{wastePost}/location', [WastePostController::class, 'location']);
    Route::post('/', [WastePostController::class, 'store']);

    // Authenticated endpoints
    Route::middleware('auth:sanctum')->group(function () {
        Route::put('/{wastePost}', [WastePostController::class, 'update']);
        Route::delete('/{wastePost}', [WastePostController::class, 'destroy']);

        // Collector claiming/assigning job
        Route::post('/{wastePost}/claim', [WastePostController::class, 'claim']);
    });
});

// Collection jobs endpoints (authenticated only)
Route::prefix('jobs')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [CollectionJobController::class, 'index']);
    Route::get('/{job}', [CollectionJobController::class, 'show']);
    Route::put('/{job}/status', [CollectionJobController::class, 'updateStatus']);
    Route::post('/{job}/complete', [CollectionJobController::class, 'complete']);
});

// Profile endpoints (authenticated only)
Route::prefix('profile')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [ProfileController::class, 'show']);
    Route::put('/', [ProfileController::class, 'update']);
    Route::get('/earnings', [ProfileController::class, 'earnings']);
    Route::get('/history', [ProfileController::class, 'history']);
});

// Admin collector approval endpoints (authenticated admin only)
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/collectors/pending', [CollectorApprovalController::class, 'pending']);
    Route::post('/collectors/{user}/approve', [CollectorApprovalController::class, 'approve']);
    Route::post('/collectors/{user}/reject', [CollectorApprovalController::class, 'reject']);
});

// Public utility endpoints
Route::get('/categories', [WastePostController::class, 'categories']);
Route::get('/locations', [WastePostController::class, 'locations']);
