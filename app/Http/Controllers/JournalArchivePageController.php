<?php

namespace App\Http\Controllers;

use App\Models\JournalEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class JournalArchivePageController extends Controller
{
    private function todayKey(): string
    {
        return Carbon::now('Asia/Jakarta')->toDateString(); // YYYY-MM-DD
    }

    public function index(Request $request)
    {
        $user = $request->user();

        // month = YYYY-MM (Jakarta). default = bulan ini (Jakarta)
        $month = $request->query('month', Carbon::now('Asia/Jakarta')->format('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = Carbon::now('Asia/Jakarta')->format('Y-m');
        }

        $start = Carbon::createFromFormat('Y-m', $month, 'Asia/Jakarta')->startOfMonth()->toDateString();
        $end   = Carbon::createFromFormat('Y-m', $month, 'Asia/Jakarta')->endOfMonth()->toDateString();

        // Ambil list tanggal yg ada entry (buat dot di kalender)
        $filledDays = JournalEntry::where('user_id', $user->id)
            ->whereBetween('date', [$start, $end])
            ->orderBy('date')
            ->get(['date'])
            ->map(fn($e) => $e->date->toDateString())
            ->values();

        // Optional: recent entries (buat “quick jump”)
        $recent = JournalEntry::where('user_id', $user->id)
            ->orderByDesc('date')
            ->limit(10)
            ->get(['id', 'date', 'rewarded_at'])
            ->map(fn($e) => [
                'id' => $e->id,
                'date' => $e->date->toDateString(),
                'rewarded_at' => optional($e->rewarded_at)?->toISOString(),
            ]);

        return Inertia::render('Journal/Archive', [
            'month' => $month,                 // YYYY-MM
            'todayKey' => $this->todayKey(),   // YYYY-MM-DD
            'filledDays' => $filledDays,       // array of YYYY-MM-DD
            'recent' => $recent,               // optional
        ]);
    }
}
