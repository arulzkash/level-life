<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public $withinTransaction = false;
    
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->date('date');

            // Free diary text
            $table->longText('body')->nullable();

            // Optional sections: [{id,title,content}, ...]
            $table->json('sections')->nullable();

            // Reward claim (today only)
            $table->unsignedInteger('xp_awarded')->default(0);
            $table->unsignedInteger('coin_awarded')->default(0);
            $table->timestamp('rewarded_at')->nullable();

            $table->timestamps();

            // 1 entry per day
            $table->unique(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
