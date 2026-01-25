<?php

namespace App\Http\Controllers;

use App\Models\Habit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HabitController extends Controller
{
    // --- HELPER UNTUK HAPUS CACHE ---
    private function clearDashboardCache($user)
    {
        $today = now()->toDateString();
        Cache::forget("dashboard:habits:{$user->id}:{$today}");
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
        ]);

        $request->user()->habits()->create([
            'name' => $data['name'],
            'start_date' => $data['start_date'] ?? now()->toDateString(),
            'end_date' => null,
        ]);

        // INVALIDASI CACHE: Biar habit baru muncul
        $this->clearDashboardCache($request->user());

        return redirect()->back();
    }

    public function toggleToday(Request $request, Habit $habit)
    {
        abort_unless($habit->user_id === $request->user()->id, 403);

        $today = now()->toDateString();

        if ($habit->start_date > $today) return redirect()->back();
        if ($habit->end_date && $habit->end_date < $today) return redirect()->back();

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

        // INVALIDASI CACHE: Biar status centang & streak update
        $this->clearDashboardCache($request->user());

        return redirect()->back();
    }

    public function toggleDate(Request $request, Habit $habit)
    {
        abort_unless($habit->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $date = $data['date'];
        $maxDate = now()->toDateString();
        
        if ($habit->end_date && $habit->end_date < $maxDate) {
            $maxDate = $habit->end_date;
        }

        if ($date < $habit->start_date) return redirect()->back();
        if ($date > $maxDate) return redirect()->back();

        $entry = $request->user()->habitEntries()
            ->where('habit_id', $habit->id)
            ->whereDate('date', $date)
            ->first();

        if ($entry) {
            $entry->delete();
        } else {
            $request->user()->habitEntries()->create([
                'habit_id' => $habit->id,
                'date' => $date,
            ]);
        }

        // INVALIDASI CACHE: Karena ubah tanggal lama bisa memutus/menyambung streak
        $this->clearDashboardCache($request->user());

        return redirect()->back();
    }

    public function update(Request $request, Habit $habit)
    {
        abort_unless($habit->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $habit->update([
            'name' => $data['name'],
        ]);

        // INVALIDASI CACHE: Biar nama baru muncul di dashboard
        $this->clearDashboardCache($request->user());

        return redirect()->back();
    }

    public function archive(Request $request, Habit $habit)
    {
        abort_unless($habit->user_id === $request->user()->id, 403);

        if ($habit->end_date) return redirect()->back();

        $habit->update(['end_date' => now()->toDateString()]);

        // INVALIDASI CACHE: Biar habit hilang dari list aktif
        $this->clearDashboardCache($request->user());

        return redirect()->back();
    }

    public function unarchive(Request $request, Habit $habit)
    {
        abort_unless($habit->user_id === $request->user()->id, 403);

        if (is_null($habit->end_date)) return redirect()->back();

        $habit->update(['end_date' => null]);

        // INVALIDASI CACHE: Biar habit muncul lagi
        $this->clearDashboardCache($request->user());

        return redirect()->back();
    }
}
