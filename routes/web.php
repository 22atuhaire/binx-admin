<?php

use App\Http\Controllers\Admin\CollectorController;
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

    // Pending collectors approval
    Route::get('/collectors/pending', [CollectorController::class, 'pending'])->name('collectors.pending');

    // Approve collector
    Route::post('/collectors/{user}/approve', [CollectorController::class, 'approve'])->name('collectors.approve');

    // Reject collector
    Route::post('/collectors/{user}/reject', [CollectorController::class, 'reject'])->name('collectors.reject');

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
