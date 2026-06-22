<?php

namespace App\Http\Controllers;

use App\Models\TreasuryReward;
use App\Support\CacheBuster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TreasuryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return Inertia::render('Treasury/Index', [
            'profile' => $user->profile,
            'rewards' => $user->treasuryRewards()->latest()->get(),
        ]);
    }

    public function storeReward(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'cost_coin' => ['required', 'integer', 'min:0'],
        ]);

        $request->user()->treasuryRewards()->create($data);

        return redirect()->back();
    }

    public function updateReward(Request $request, TreasuryReward $reward)
    {
        if (! $request->user()->can('update', $reward)) {
            abort(403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'cost_coin' => ['required', 'integer', 'min:0'],
        ]);

        $reward->update($data);

        return redirect()->back();
    }

    public function destroyReward(Request $request, TreasuryReward $reward)
    {
        if (! $request->user()->can('delete', $reward)) {
            abort(403);
        }

        // soft delete
        $reward->delete();

        return redirect()->back();
    }

    public function buy(Request $request, TreasuryReward $reward)
    {
        if (! $request->user()->can('buy', $reward)) {
            abort(403);
        }

        $data = $request->validate([
            'qty' => ['required', 'integer', 'min:1', 'max:999'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $qty = (int) $data['qty'];
        $unitCost = (int) $reward->cost_coin;
        $totalCost = $unitCost * $qty;

        $insufficient = false;

        DB::transaction(function () use ($request, $reward, $data, $qty, $unitCost, $totalCost, &$insufficient) {
            $profile = $request->user()->profile()->lockForUpdate()->first();

            // cek saldo
            if ($profile->coin_balance < $totalCost) {
                $insufficient = true;
                return;
            }

            // kurangi coin
            $profile->coin_balance -= $totalCost;
            $profile->save();

            // log purchase
            $request->user()->treasuryPurchases()->create([
                'treasury_reward_id' => $reward->id,
                'qty' => $qty,
                'unit_cost_coin' => $unitCost,
                'cost_coin' => $totalCost, // total cost (biar histori konsisten walau harga reward berubah)
                'purchased_at' => now(),
                'note' => $data['note'] ?? null,
            ]);
        });

        if ($insufficient) {
            return redirect()->back()->withErrors([
                'coin' => 'Coin tidak cukup untuk membeli reward ini.',
            ]);
        }

        CacheBuster::invalidateNavProfile($request->user()->id);

        return redirect()->back();
    }
}