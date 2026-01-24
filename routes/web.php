<?php

use App\Http\Controllers\BadgeDebugController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\TaskController;
use App\Http\Controllers\ProfileController;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\QuestController;
use App\Http\Controllers\QuestPageController;

use App\Http\Controllers\CompletionLogController;
use App\Http\Controllers\CompletionLogPageController;
use App\Http\Controllers\HabitController;
use App\Http\Controllers\HabitPageController;
use App\Http\Controllers\JournalArchivePageController;
use App\Http\Controllers\JournalPageController;
use App\Http\Controllers\JournalTemplateController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\TimeBlockController;
use App\Http\Controllers\TimeBlockPageController;
use App\Http\Controllers\TreasuryController;
use App\Http\Controllers\TreasuryLogPageController;
use App\Http\Controllers\TreasuryPurchaseLogController;

require __DIR__ . '/auth.php';

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});



Route::middleware('auth')->group(function () {

    // DASHBOARD (page)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');;

    // PROFILE
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // QUESTS
    Route::prefix('quests')->group(function () {
        // pages
        Route::get('/', [QuestPageController::class, 'index']);

        // actions
        Route::post('/', [QuestController::class, 'store']);
        Route::patch('/reorder', [QuestController::class, 'reorder'])->name('quests.reorder');
        Route::patch('/{quest}', [QuestController::class, 'update']);
        Route::patch('/{quest}/complete', [QuestController::class, 'complete']);
        Route::delete('/{quest}', [QuestController::class, 'destroy']);
        
    });

    // LOGS
    Route::prefix('logs')->group(function () {
        // completion log page + edit note
        Route::get('/completions', [CompletionLogPageController::class, 'index']);
        Route::patch('/completions/{completion}', [CompletionLogController::class, 'update']);

        // treasury log page + edit note
        Route::get('/treasury', [TreasuryLogPageController::class, 'index']);
        Route::patch('/treasury/{purchase}', [TreasuryPurchaseLogController::class, 'update']);
    });

    // TREASURY
    Route::prefix('treasury')->group(function () {
        // page
        Route::get('/', [TreasuryController::class, 'index']);

        // rewards actions
        Route::post('/rewards', [TreasuryController::class, 'storeReward']);
        Route::patch('/rewards/{reward}/buy', [TreasuryController::class, 'buy']);
        Route::patch('/rewards/{reward}', [TreasuryController::class, 'updateReward']);
        Route::delete('/rewards/{reward}', [TreasuryController::class, 'destroyReward']);
    });

    // HABIT
    Route::prefix('habits')->group(function () {
        // pages
        Route::get('/', [HabitPageController::class, 'index']);
        Route::get('/{habit}', [HabitPageController::class, 'show']);

        // actions
        Route::post('/', [HabitController::class, 'store']);
        Route::patch('/{habit}/toggle', [HabitController::class, 'toggleToday']);
        Route::patch('/{habit}/archive', [HabitController::class, 'archive']);
        Route::patch('/{habit}/unarchive', [HabitController::class, 'unarchive']);

        // monthly view toggle by date (payload date)
        Route::patch('/{habit}/entries/toggle', [HabitController::class, 'toggleDate']);
    });

    // TIMEBLOCK
    Route::prefix('timeblocks')->group(function () {
        // page
        Route::get('/', [TimeBlockPageController::class, 'index']);

        // actions
        Route::post('/', [TimeBlockController::class, 'store']);
        Route::patch('/{timeBlock}', [TimeBlockController::class, 'update']);
        Route::delete('/{timeBlock}', [TimeBlockController::class, 'destroy']);
    });

    // LEADERBOARD
    Route::get('/leaderboard', [LeaderboardController::class, 'page']);
    Route::get('/api/leaderboard', [LeaderboardController::class, 'index']);

    // JOURNAL
    Route::get('/journal', [JournalPageController::class, 'index'])->name('journal.index');
    Route::put('/journal', [JournalPageController::class, 'save'])->name('journal.save');

    Route::post('/journal/templates', [JournalTemplateController::class, 'store'])->name('journal.templates.store');
    Route::delete('/journal/templates/{template}', [JournalTemplateController::class, 'destroy'])->name('journal.templates.destroy');

    Route::get('/journal/archive', [JournalArchivePageController::class, 'index']);

    Route::get('/debug/badges', [BadgeDebugController::class, 'index']);
});

// Route khusus buat UptimeRobot "nyolek" server
Route::get('/up', function () {
    return 'Up';
});