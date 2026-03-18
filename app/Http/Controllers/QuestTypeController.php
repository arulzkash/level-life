<?php

namespace App\Http\Controllers;

use App\Models\QuestType;
use Illuminate\Http\Request;

class QuestTypeController extends Controller
{
    /**
     * Update a custom quest type color.
     */
    public function update(Request $request, QuestType $questType)
    {
        if ($questType->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'color' => ['required', 'string', 'max:7', 'regex:/^#[a-fA-F0-9]{6}$/'],
        ]);

        $questType->update(['color' => $validated['color']]);

        return redirect()->back();
    }

    /**
     * Delete a custom quest type.
     * Only the owner can delete their own type.
     */
    public function destroy(Request $request, QuestType $questType)
    {
        // Authorization: hanya owner yang bisa hapus
        if ($questType->user_id !== $request->user()->id) {
            abort(403);
        }

        $questType->delete();

        return redirect()->back();
    }
}
