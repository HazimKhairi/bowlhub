<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\ParticipantController;
use Illuminate\Support\Facades\Route;

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
Route::get('/admin', [AdminController::class, 'index'])->name('admin');
Route::get('/admin/participants', [AdminController::class, 'participants'])->name('admin.participants');
Route::get('/admin/score/{id}', [AdminController::class, 'editScore'])->name('admin.score.edit');
Route::post('/admin/score/{id}', [AdminController::class, 'updateScore'])->name('admin.score.update');
Route::delete('/admin/participant/{id}', [AdminController::class, 'deleteParticipant'])->name('admin.participant.delete');
