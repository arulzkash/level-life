<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public $withinTransaction = false;
    
    public function up(): void
    {
        Schema::table('treasury_purchases', function (Blueprint $table) {
            $table->unsignedInteger('qty')->default(1)->after('treasury_reward_id');
            $table->integer('unit_cost_coin')->nullable()->after('qty');
        });

        // Backfill: untuk data lama, anggap qty = 1 dan unit_cost = cost_coin
        DB::table('treasury_purchases')
            ->whereNull('unit_cost_coin')
            ->update(['unit_cost_coin' => DB::raw('cost_coin')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('treasury_purchases', function (Blueprint $table) {
            //
            $table->dropColumn(['qty', 'unit_cost_coin']);
        });
    }
};
