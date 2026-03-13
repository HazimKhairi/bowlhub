<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\ParticipantController;
use Illuminate\Support\Facades\Route;

// Admin Authentication Routes
Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

// Home route
Route::get('/', [HomeController::class, 'index'])->name('home');

// Registration routes
Route::get('/daftar', [ParticipantController::class, 'create'])->name('registration');
Route::post('/daftar', [ParticipantController::class, 'store'])->name('registration.store');

// Leaderboard routes
Route::get('/kedudukan', [LeaderboardController::class, 'index'])->name('leaderboard');
Route::get('/api/leaderboard/{eventType}/{gender}', [LeaderboardController::class, 'getEventResults']);
Route::get('/api/leaderboard/medal-standings', [LeaderboardController::class, 'getMedalStandings']);

// Admin routes
Route::prefix('admin')->middleware('admin')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('admin');
    Route::get('/participants', [AdminController::class, 'participants'])->name('admin.participants');
    Route::get('/score/{id}', [AdminController::class, 'editScore'])->name('admin.score.edit');
    Route::post('/score/{id}', [AdminController::class, 'updateScore'])->name('admin.score.update');
    Route::delete('/participant/{id}', [AdminController::class, 'deleteParticipant'])->name('admin.participant.delete');
    Route::post('/participant/{id}/approve', [AdminController::class, 'approveParticipant'])->name('admin.participant.approve');
});
