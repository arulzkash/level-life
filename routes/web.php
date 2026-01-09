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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\Task;
use Illuminate\Support\Carbon;

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

    // --- filters ---
    $period = $request->query('period', 'all'); // all|today|7d|month|custom
    $date = $request->query('date');            // YYYY-MM-DD
    $from = $request->query('from');            // YYYY-MM-DD
    $to = $request->query('to');                // YYYY-MM-DD

    $query = $user->questCompletions()->with('quest:id,name,type');

    // Priority: date (single day) > range (from-to) > period
    if ($date) {
        $query->whereDate('completed_at', $date);
        $period = 'custom';
    } elseif ($from || $to) {
        $start = $from ? Carbon::parse($from)->startOfDay() : Carbon::minValue();
        $end   = $to ? Carbon::parse($to)->endOfDay() : now()->endOfDay();
        $query->whereBetween('completed_at', [$start, $end]);
        $period = 'custom';
    } else {
        if ($period === 'today') {
            $query->whereDate('completed_at', now()->toDateString());
        } elseif ($period === '7d') {
            $start = now()->subDays(6)->startOfDay();
            $end = now()->endOfDay();
            $query->whereBetween('completed_at', [$start, $end]);
        } elseif ($period === 'month') {
            $start = now()->startOfMonth();
            $end = now()->endOfDay();
            $query->whereBetween('completed_at', [$start, $end]);
        }
        // all = no filter
    }

    // --- sort ---
    $allowedSorts = ['completed_at', 'xp_awarded', 'coin_awarded', 'created_at'];
    $sort = $request->query('sort', 'completed_at');
    $dir = $request->query('dir', 'desc');

    if (!in_array($sort, $allowedSorts, true)) $sort = 'completed_at';
    if (!in_array($dir, ['asc', 'desc'], true)) $dir = 'desc';

    $query->orderBy($sort, $dir);

    return Inertia::render('Logs/Completions', [
        'logs' => $query->paginate(20)->withQueryString(),
        'filters' => [
            'period' => $period,
            'date' => $date ?? '',
            'from' => $from ?? '',
            'to' => $to ?? '',
            'sort' => $sort,
            'dir' => $dir,
        ],
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



Route::patch('/logs/completions/{completion}', [CompletionLogController::class, 'update'])
    ->middleware(['auth']);

