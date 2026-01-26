<?php

namespace App\Http\Controllers;

use App\Models\TreasuryReward;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Cache;

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
        abort_unless($reward->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'cost_coin' => ['required', 'integer', 'min:0'],
        ]);

        $reward->update($data);

        return redirect()->back();
    }

    public function destroyReward(Request $request, TreasuryReward $reward)
    {
        abort_unless($reward->user_id === $request->user()->id, 403);

        // soft delete
        $reward->delete();

        return redirect()->back();
    }

    public function buy(Request $request, TreasuryReward $reward)
    {
        // ownership check
        abort_unless($reward->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'qty' => ['required', 'integer', 'min:1', 'max:999'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $profile = $request->user()->profile;

        $qty = (int) $data['qty'];
        $unitCost = (int) $reward->cost_coin;
        $totalCost = $unitCost * $qty;

        // cek saldo
        if ($profile->coin_balance < $totalCost) {
            return redirect()->back()->withErrors([
                'coin' => 'Coin tidak cukup untuk membeli reward ini.',
            ]);
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

        Cache::forget("nav_profile:{$request->user()->id}");

        return redirect()->back();
    }
}
