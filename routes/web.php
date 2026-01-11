<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\TaskController;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\QuestController;
use App\Http\Controllers\QuestPageController;

use App\Http\Controllers\CompletionLogController;
use App\Http\Controllers\CompletionLogPageController;
use App\Http\Controllers\HabitController;
use App\Http\Controllers\TreasuryController;
use App\Http\Controllers\TreasuryLogPageController;
use App\Http\Controllers\TreasuryPurchaseLogController;


require __DIR__ . '/auth.php';

Route::middleware('auth')->group(function () {

    // DASHBOARD (page)
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // QUESTS
    Route::prefix('quests')->group(function () {
        // pages
        Route::get('/', [QuestPageController::class, 'index']);

        // actions
        Route::post('/', [QuestController::class, 'store']);
        Route::patch('/{quest}', [QuestController::class, 'update']);
        Route::patch('/{quest}/complete', [QuestController::class, 'complete']);
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
        // Route::get('/', [HabitPageController::class, 'index']); // nanti Step 3C
        // Route::post('/', [HabitController::class, 'store']);    // nanti Step 3C
        Route::patch('/{habit}/toggle', [HabitController::class, 'toggleToday']);
        // Route::patch('/{habit}/archive', [HabitController::class, 'archive']); // nanti Step 3C
    });

    // TASKS
    Route::prefix('tasks')->group(function () {
        Route::get('/', [TaskController::class, 'index']);
        Route::post('/', [TaskController::class, 'store']);
        Route::patch('/{task}', [TaskController::class, 'toggle']);
        Route::delete('/{task}', [TaskController::class, 'destroy']);
    });
});
