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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\Task;

require __DIR__ . '/auth.php';



// Route::get('/dashboard', function () {
//     return Inertia::render('Dashboard', [
//         'message' => 'Hello from Laravel!',
//         'time' => now()->toDateString(),
//     ]);
// })->middleware(['auth']);

Route::get('/dashboard', function (Request $request) {
    $user = $request->user();

    return Inertia::render('Dashboard', [
        'profile' => $user->profile,
        'activeQuests' => $user->quests()
            ->whereIn('status', ['todo', 'in_progress'])
            ->latest()
            ->get(),
    ]);
})->middleware(['auth']);

Route::get('/logs/completions', function (Request $request) {
    $user = $request->user();

    return Inertia::render('Logs/Completions', [
        'logs' => $user->questCompletions()
            ->latest('completed_at')
            ->with('quest:id,name,type')
            ->paginate(20),
    ]);
})->middleware(['auth']);

Route::get('/quests', function (Request $request) {
    $user = $request->user();

    return Inertia::render('Quests/Index', [
        'quests' => $user->quests()->latest()->paginate(20),
    ]);
})->middleware(['auth']);


Route::post('dashboard/message', function (Request $request) {
    $request->validate([
        'message' => 'required|string|max:255',
    ]);

    return redirect('dashboard');
});

Route::middleware('auth')->group(function () {
    Route::get('/tasks', [TaskController::class, 'index']);
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::patch('/tasks/{task}', [TaskController::class, 'toggle']);
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);
});

Route::middleware(['auth'])->group(function () {
    Route::post('/quests', [QuestController::class, 'store']);
    Route::patch('/quests/{quest}/complete', [QuestController::class, 'complete']);
});
