<?php

namespace App\Http\Controllers;

use App\Models\Habit;
use Illuminate\Http\Request;

class HabitController extends Controller
{
    public function toggleToday(Request $request, Habit $habit)
    {
        // ownership check
        abort_unless($habit->user_id === $request->user()->id, 403);

        // habit harus aktif dan sudah mulai
        $today = now()->toDateString();

        if ($habit->start_date > $today) {
            return redirect()->back();
        }

        if ($habit->end_date && $habit->end_date < $today) {
            return redirect()->back();
        }

        // toggle entry (create kalau belum ada, delete kalau sudah ada)
        $entry = $request->user()->habitEntries()
            ->where('habit_id', $habit->id)
            ->whereDate('date', $today)
            ->first();

        if ($entry) {
            $entry->delete();
        } else {
            $request->user()->habitEntries()->create([
                'habit_id' => $habit->id,
                'date' => $today,
            ]);
        }

        return redirect()->back();
    }
}

