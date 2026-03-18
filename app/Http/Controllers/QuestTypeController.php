<?php

namespace App\Http\Controllers;

use App\Models\QuestType;
use Illuminate\Http\Request;

class QuestTypeController extends Controller
{
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
