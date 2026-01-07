<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TaskController extends Controller
{
    //
    public function index(Request $request)
    {
        return inertia('Tasks/Index', [
            'tasks' => $request->user()->tasks()->latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $request->user()->tasks()->create([
            'title' => $request->title,
        ]);

        return redirect('/tasks');
    }

    public function toggle(Request $request, Task $task)
    {
        abort_if($task->user_id !== $request->user()->id, 403);

        $task->update([
            'completed' => ! $task->completed,
        ]);

        return redirect('/tasks');
    }

    public function destroy(Request $request, Task $task)
    {
        abort_if($task->user_id !== $request->user()->id, 403);

        $task->delete();

        return redirect('/tasks');
    }
}
