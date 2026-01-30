<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public $withinTransaction = false;
    public function up()
    {
        Schema::table('quests', function (Blueprint $table) {
            $table->integer('position')->default(0)->after('status');
        });

        // BACKFILL existing records
        DB::statement('UPDATE quests SET position = id');
    }

    public function down()
    {
        Schema::table('quests', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
};
