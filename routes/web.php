<?php

use App\Http\Controllers\Admin\CollectorController;
use App\Http\Controllers\Admin\WastePostController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin routes - Only accessible by authenticated users with admin role
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Admin dashboard
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    // Pending collectors approval (must come before collectors/{user} routes)
    Route::get('/collectors/pending', [CollectorController::class, 'pending'])->name('collectors.pending');

    // Active collectors management
    Route::get('/collectors', [CollectorController::class, 'index'])->name('collectors.index');

    // Collector-specific actions
    Route::post('/collectors/{user}/suspend', [CollectorController::class, 'suspend'])->name('collectors.suspend');
    Route::post('/collectors/{user}/reactivate', [CollectorController::class, 'reactivate'])->name('collectors.reactivate');
    Route::post('/collectors/{user}/approve', [CollectorController::class, 'approve'])->name('collectors.approve');
    Route::post('/collectors/{user}/reject', [CollectorController::class, 'reject'])->name('collectors.reject');
    Route::get('/collectors/{user}/job-history', [CollectorController::class, 'jobHistory'])->name('collectors.job-history');

    // Waste posts management
    Route::get('/waste-posts', [WastePostController::class, 'index'])->name('waste-posts.index');
    Route::get('/waste-posts/{wastePost}', [WastePostController::class, 'show'])->name('waste-posts.show');
    Route::delete('/waste-posts/{wastePost}', [WastePostController::class, 'destroy'])->name('waste-posts.destroy');
    Route::post('/waste-posts/{wastePost}/assign', [WastePostController::class, 'assign'])->name('waste-posts.assign');
    Route::post('/waste-posts/{wastePost}/cancel', [WastePostController::class, 'cancel'])->name('waste-posts.cancel');

    // Block user
    Route::post('/users/{user}/block', function (\App\Models\User $user) {
        $user->block();

        return redirect()->back()->with('success', 'User blocked successfully!');
    })->name('users.block');

    // Activate user
    Route::post('/users/{user}/activate', function (\App\Models\User $user) {
        $user->activate();

        return redirect()->back()->with('success', 'User activated successfully!');
    })->name('users.activate');

    // All users
    Route::get('/users', function () {
        $users = \App\Models\User::latest()->paginate(20);

        return view('admin.users.index', compact('users'));
    })->name('users.index');
});

require __DIR__.'/auth.php';
