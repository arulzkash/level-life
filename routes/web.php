<?php

// use App\Http\Controllers\ProfileController;
// use Illuminate\Foundation\Application;
// use Illuminate\Support\Facades\Route;
// use Inertia\Inertia;

// Route::get('/', function () {
//     return Inertia::render('Welcome', [
//         'canLogin' => Route::has('login'),
//         'canRegister' => Route::has('register'),
//         'laravelVersion' => Application::VERSION,
//         'phpVersion' => PHP_VERSION,
//     ]);
// });

// Route::get('/dashboard', function () {
//     return Inertia::render('Dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

// Route::middleware('auth')->group(function () {
//     Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
//     Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
//     Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
// });
use App\Http\Controllers\QuestController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\CompletionLogController;
use App\Http\Controllers\TreasuryController;
use App\Http\Controllers\TreasuryPurchaseLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\QuestPageController;
use App\Http\Controllers\CompletionLogPageController;
use App\Http\Controllers\TreasuryLogPageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\Task;
use Illuminate\Support\Carbon;

require __DIR__ . '/auth.php';

Route::middleware('auth')->group(function () {
    Route::get('/tasks', [TaskController::class, 'index']);
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::patch('/tasks/{task}', [TaskController::class, 'toggle']);
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);
});

Route::middleware(['auth'])->group(function () {
    Route::post('/quests', [QuestController::class, 'store']);
    Route::patch('/quests/{quest}/complete', [QuestController::class, 'complete']);
    Route::patch('/quests/{quest}', [QuestController::class, 'update']);
});

Route::middleware(['auth'])->group(function () {
    Route::get('/treasury', [TreasuryController::class, 'index']);
    Route::post('/treasury/rewards', [TreasuryController::class, 'storeReward']);
    Route::patch('/treasury/rewards/{reward}/buy', [TreasuryController::class, 'buy']);
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/quests', [QuestPageController::class, 'index']);
    Route::get('/logs/completions', [CompletionLogPageController::class, 'index']);
    Route::patch('/logs/completions/{completion}', [CompletionLogController::class, 'update']);
    Route::get('/logs/treasury', [TreasuryLogPageController::class, 'index']);
    Route::patch('/logs/treasury/{purchase}', [TreasuryPurchaseLogController::class, 'update']);
});
