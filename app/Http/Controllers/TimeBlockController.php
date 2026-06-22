<?php

namespace App\Http\Controllers;

use App\Models\TimeBlock;
use App\Support\CacheBuster;
use Illuminate\Http\Request;

class TimeBlockController extends Controller
{
    //
    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'title' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        // rule: end_time > start_time
        if ($data['end_time'] <= $data['start_time']) {
            return redirect()->back()->withErrors([
                'end_time' => 'end_time harus lebih besar dari start_time',
            ]);
        }

        $request->user()->timeBlocks()->create([
            'date' => $data['date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'title' => $data['title'],
            'note' => $data['note'] ?? null,
        ]);

        CacheBuster::onTimeblockMutate($request->user()->id);

        return redirect()->back();
    }

    public function update(Request $request, TimeBlock $timeBlock)
    {
        abort_unless($request->user()->can('update', $timeBlock), 403);

        $data = $request->validate([
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'title' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($data['end_time'] <= $data['start_time']) {
            return redirect()->back()->withErrors([
                'end_time' => 'end_time harus lebih besar dari start_time',
            ]);
        }

        $timeBlock->update([
            'date' => $data['date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'title' => $data['title'],
            'note' => $data['note'] ?? null,
        ]);

        CacheBuster::onTimeblockMutate($request->user()->id);

        return redirect()->back();
    }

    public function destroy(Request $request, TimeBlock $timeBlock)
    {
        abort_unless($request->user()->can('update', $timeBlock), 403);

        $timeBlock->delete();

        CacheBuster::onTimeblockMutate($request->user()->id);

        return redirect()->back();
    }
}
