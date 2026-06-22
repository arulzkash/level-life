<?php

namespace App\Http\Controllers;

use App\Models\TreasuryPurchase;
use Illuminate\Http\Request;

class TreasuryPurchaseLogController extends Controller
{
    //
    public function update(Request $request, TreasuryPurchase $purchase)
    {
        // ownership check
        abort_unless($request->user()->can('update', $purchase), 403);

        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $purchase->update([
            'note' => $data['note'] ?? null,
        ]);

        return redirect()->back();
    }
}
