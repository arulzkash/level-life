<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50);
            $table->date('date');
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unique(['user_id', 'type', 'date']);
            $table->index(['type', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
