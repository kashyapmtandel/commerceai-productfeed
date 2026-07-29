<?php

use App\Http\Controllers\AiFixController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\FeedController;
use Illuminate\Support\Facades\Route;

// ─── Landing ──────────────────────────────────────────────────────────────────
Route::get('/', fn() => auth()->check()
    ? redirect()->route('dashboard')
    : view('welcome')
)->name('home');

// ─── GitHub OAuth ─────────────────────────────────────────────────────────────
Route::get('/auth/github',          [SocialiteController::class, 'redirectToGithub'])->name('auth.github');
Route::get('/auth/github/callback', [SocialiteController::class, 'handleGithubCallback'])->name('auth.github.callback');
Route::post('/logout',              [SocialiteController::class, 'logout'])->name('logout');

// ─── Authenticated Routes ─────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [FeedController::class, 'index'])->name('dashboard');

    Route::prefix('feeds')->name('feeds.')->group(function () {
        Route::post('/',             [FeedController::class, 'upload'])->name('upload');
        Route::get('/{feed}',        [FeedController::class, 'show'])->name('show');
        Route::get('/{feed}/status', [FeedController::class, 'status'])->name('status');
        Route::get('/{feed}/export', [FeedController::class, 'export'])->name('export');
        Route::delete('/{feed}',     [FeedController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('feeds/{feed}/rows/{row}')->name('feeds.rows.')->group(function () {
        Route::post('/ai-suggest', [AiFixController::class, 'suggest'])->name('ai-suggest');
        Route::post('/ai-apply',   [AiFixController::class, 'applyAiFix'])->name('ai-apply');
        Route::post('/manual-fix', [AiFixController::class, 'manualFix'])->name('manual-fix');
    });
});
