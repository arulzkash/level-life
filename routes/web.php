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

    $query = $user->quests();

    // FILTERS
    if ($status = $request->query('status')) {
        $query->where('status', $status);
    }

    if ($type = $request->query('type')) {
        $query->where('type', $type);
    }

    if (!is_null($request->query('repeatable'))) {
        $query->where('is_repeatable', $request->boolean('repeatable'));
    }

    // SORT (whitelist)
    $allowedSorts = ['name', 'due_date', 'xp_reward', 'coin_reward', 'completed_at', 'created_at'];
    $sort = $request->query('sort', 'created_at');
    $dir = $request->query('dir', 'desc');

    if (!in_array($sort, $allowedSorts, true)) {
        $sort = 'created_at';
    }
    if (!in_array($dir, ['asc', 'desc'], true)) {
        $dir = 'desc';
    }

    $query->orderBy($sort, $dir);

    return Inertia::render('Quests/Index', [
        'quests' => $query->paginate(20)->withQueryString(),
        'filters' => [
            'status' => $request->query('status', ''),
            'type' => $request->query('type', ''),
            'repeatable' => $request->query('repeatable', ''), // '' | '1' | '0'
            'sort' => $sort,
            'dir' => $dir,
        ],
        'typeOptions' => $user->quests()
            ->select('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type'),
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
    Route::patch('/quests/{quest}', [QuestController::class, 'update']);
});
