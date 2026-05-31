<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 30)->nullable()->unique()->after('name');
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->text('bio')->nullable()->after('streak_resets_total');
        });

        $reserved = [];

        DB::table('users')
            ->select(['id', 'name'])
            ->orderBy('id')
            ->get()
            ->each(function ($user) use (&$reserved) {
                $username = $this->generateUniqueUsername((string) $user->name, (int) $user->id, $reserved);

                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['username' => $username]);
            });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn('bio');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }

    private function generateUniqueUsername(string $name, int $userId, array &$reserved): string
    {
        $base = Str::slug(Str::lower($name), '_');
        $base = $base !== '' ? substr($base, 0, 30) : "user{$userId}";

        $candidate = $base;
        $suffix = 1;

        while (isset($reserved[$candidate])) {
            $suffix++;
            $suffixText = "_{$suffix}";
            $candidate = substr($base, 0, max(1, 30 - strlen($suffixText))).$suffixText;
        }

        $reserved[$candidate] = true;

        return $candidate;
    }
};
