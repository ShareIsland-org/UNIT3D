<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('users')
            ->select(['id'])
            ->whereNull('irc_key')
            ->orderBy('id')
            ->chunkById(100, function ($users): void {
                foreach ($users as $user) {
                    DB::table('users')
                        ->where('id', '=', $user->id)
                        ->update(['irc_key' => bin2hex(random_bytes(16))]);
                }
            });

        DB::table('users')
            ->select(['id', 'irc_key'])
            ->orderBy('id')
            ->chunkById(100, function ($users): void {
                $rows = [];

                foreach ($users as $user) {
                    if (DB::table('irckeys')->where('user_id', '=', $user->id)->exists()) {
                        continue;
                    }

                    $rows[] = [
                        'user_id'    => $user->id,
                        'content'    => $user->irc_key,
                        'created_at' => now(),
                        'deleted_at' => null,
                    ];
                }

                if ($rows !== []) {
                    DB::table('irckeys')->insert($rows);
                }
            });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('irc_key')->nullable(false)->change();
            $table->unique('irc_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique('users_irc_key_unique');
            $table->string('irc_key')->nullable()->change();
        });
    }
};
