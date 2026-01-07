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

require __DIR__.'/auth.php';

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\Task;

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard', [
        'message' => 'Hello from Laravel!',
        'time' => now()->toDateString(),
    ]);
})->middleware(['auth']);

Route::post('dashboard/message', function (Request $request) {
    $request->validate([
        'message' => 'required|string|max:255',
    ]);

    return redirect('dashboard');
});

Route::get('/tasks', function (Request $request) {
    return Inertia::render('Tasks/Index', [
        'tasks' => $request->user()->tasks()->latest()->get(),
    ]);
})->middleware(['auth']);

Route::post('/tasks', function (Request $request) {
    $request->validate([
        'title' => 'required|string|max:255',
    ]);

    $request->user()->tasks()->create([
        'title' => $request->title,
    ]);

    return redirect('/tasks');
})->middleware(['auth']);

Route::patch('/tasks/{task}', function (Request $request, Task $task) {
    abort_if($task->user_id !== $request->user()->id, 403);


    $task->update([
        'completed' => ! $task->completed,
    ]);

    return redirect('/tasks');
})->middleware(['auth']);

Route::delete('/tasks/{task}', function (Request $request, Task $task) {
    abort_if($task->user_id !== $request->user()->id, 403);

    $task->delete();

    return redirect('/tasks');
})->middleware(['auth']);
