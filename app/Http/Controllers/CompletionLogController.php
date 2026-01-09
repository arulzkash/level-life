<?php

namespace App\Http\Controllers;

use App\Models\QuestCompletion;
use Illuminate\Http\Request;

class CompletionLogController extends Controller
{
    public function update(Request $request, QuestCompletion $completion)
    {
        // pastikan log milik user (simple authorization)
        abort_unless($completion->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $completion->update([
            'note' => $data['note'] ?? null,
        ]);

        return redirect()->back();
    }
}
