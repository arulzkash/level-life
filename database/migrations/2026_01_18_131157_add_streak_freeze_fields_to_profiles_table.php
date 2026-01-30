<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public $withinTransaction = false;
    
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->unsignedInteger('streak_current')->default(0);
            $table->unsignedInteger('streak_best')->default(0);

            $table->date('streak_maintained_through')->nullable();
            $table->date('last_active_date')->nullable();

            $table->date('freezes_used_week_start')->nullable();
            $table->unsignedTinyInteger('freezes_used_count')->default(0);

            $table->unsignedInteger('freezes_used_total')->default(0);
            $table->unsignedInteger('streak_resets_total')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn([
                'streak_current',
                'streak_best',
                'streak_maintained_through',
                'last_active_date',
                'freezes_used_week_start',
                'freezes_used_count',
                'freezes_used_total',
                'streak_resets_total',
            ]);
        });
    }
};
