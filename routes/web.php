<?php

use App\Http\Controllers\BadgeDebugController;
use App\Http\Controllers\CompletionLogController;
use App\Http\Controllers\CompletionLogPageController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\HabitController;
use App\Http\Controllers\HabitPageController;
use App\Http\Controllers\InternalReminderController;
use App\Http\Controllers\JournalArchivePageController;
use App\Http\Controllers\JournalPageController;
use App\Http\Controllers\JournalTemplateController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\PublicProfileController;
use App\Http\Controllers\QuestController;
use App\Http\Controllers\QuestPageController;
use App\Http\Controllers\QuestTypeController;
use App\Http\Controllers\TimeBlockController;
use App\Http\Controllers\TimeBlockPageController;
use App\Http\Controllers\TreasuryController;
use App\Http\Controllers\TreasuryLogPageController;
use App\Http\Controllers\TreasuryPurchaseLogController;
use App\Http\Controllers\NoteController;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Inertia\Inertia;

require __DIR__.'/auth.php';

$handbookPageData = function () {
    $read = function (string $file) {
        $path = resource_path("content/{$file}");
        $exists = File::exists($path);

        return [
            'markdown' => $exists
                ? File::get($path)
                : "# Handbook unavailable\n\nThe handbook source file could not be found.",
            'isMissing' => ! $exists,
        ];
    };

    $id = $read('handbook.md');
    $en = $read('handbook_en.md');

    return [
        // Backward-compatible defaults (Indonesian).
        'markdown' => $id['markdown'],
        'isMissing' => $id['isMissing'],
        // Per-language payload for the in-page language toggle.
        'handbooks' => [
            'id' => $id,
            'en' => $en,
        ],
    ];
};

Route::get('/', function () use ($handbookPageData) {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return Inertia::render('Handbook/Public', $handbookPageData());
})->name('home');

Route::get('/u/{username}', [PublicProfileController::class, 'show'])
    ->where('username', '[a-z0-9_]+')
    ->middleware('auth')
    ->name('profile.show');

Route::redirect('/handbook-public', '/')->name('handbook.public');

Route::middleware('auth')->group(function () use ($handbookPageData) {

    // DASHBOARD (page)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // PROFILE SETTINGS
    Route::redirect('/profile', '/settings/profile');
    Route::get('/settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // PUSH NOTIFICATIONS
    Route::post('/push-subscriptions/status', [PushSubscriptionController::class, 'status'])->name('push-subscriptions.status');
    Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store'])->name('push-subscriptions.store');
    Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroy'])->name('push-subscriptions.destroy');
    Route::post('/push-subscriptions/test', [PushSubscriptionController::class, 'test'])->name('push-subscriptions.test');
    Route::patch('/push-subscriptions/settings', [PushSubscriptionController::class, 'updateSettings'])->name('push-subscriptions.settings');

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

    // QUEST TYPES (Custom)
    Route::patch('/quest-types/{questType}', [QuestTypeController::class, 'update'])->name('quest-types.update');
    Route::delete('/quest-types/{questType}', [QuestTypeController::class, 'destroy'])->name('quest-types.destroy');

    // GOALS
    Route::prefix('goals')->group(function () {
        Route::get('/', [GoalController::class, 'index'])->name('goals.index');
        Route::get('/{goal}', [GoalController::class, 'show'])->name('goals.show');
        Route::post('/', [GoalController::class, 'store'])->name('goals.store');
        Route::patch('/{goal}', [GoalController::class, 'update'])->name('goals.update');
        Route::delete('/{goal}', [GoalController::class, 'destroy'])->name('goals.destroy');
        Route::patch('/milestones/{goalMilestone}/toggle', [GoalController::class, 'toggleMilestone'])->name('goal-milestones.toggle');
        Route::post('/{goal}/complete', [GoalController::class, 'complete'])->name('goals.complete');
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
        Route::patch('/{habit}', [HabitController::class, 'update']);
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

    // NOTES
    Route::prefix('notes')->group(function () {
        Route::get('/', [NoteController::class, 'index'])->name('notes.index');
        Route::get('/create', [NoteController::class, 'create'])->name('notes.create');
        Route::post('/', [NoteController::class, 'store'])->name('notes.store');
        Route::get('/{note}', [NoteController::class, 'show'])->name('notes.show');
        Route::put('/{note}', [NoteController::class, 'update'])->name('notes.update');
        Route::delete('/{note}', [NoteController::class, 'destroy'])->name('notes.destroy');
    });

    Route::get('/handbook', function () use ($handbookPageData) {
        return Inertia::render('Handbook/Index', $handbookPageData());
    })->name('handbook');

    Route::get('/debug/badges', [BadgeDebugController::class, 'index']);
});

// Route khusus buat UptimeRobot "nyolek" server
Route::get('/up', function () {
    return response('Up', 200)
        ->header('Content-Type', 'text/plain')
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
});

Route::post('/internal/reminders/morning', [InternalReminderController::class, 'morning'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('internal.reminders.morning');

Route::post('/internal/reminders/evening', [InternalReminderController::class, 'evening'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('internal.reminders.evening');

Route::get('/internal/cron/notifications/morning', [InternalReminderController::class, 'cronMorning'])
    ->name('internal.cron.notifications.morning');

Route::get('/internal/cron/notifications/evening', [InternalReminderController::class, 'cronEvening'])
    ->name('internal.cron.notifications.evening');
