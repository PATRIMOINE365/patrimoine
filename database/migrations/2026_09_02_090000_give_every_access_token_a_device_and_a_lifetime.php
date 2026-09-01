<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * An access token becomes a device.
 *
 * Until now a token was a row with a name nobody set, no expiry and no
 * record of where it came from. That was survivable while the only client
 * was a browser tab that threw its token away when it closed. It stops
 * being survivable the moment a token lives in a phone's keychain: a
 * phone is lost, sold or handed to a colleague, and the credential on it
 * outlives all three.
 *
 * Two things change here. Every token learns what it was minted for — the
 * client, the platform, the application version, the address it was asked
 * from — so a person can look at a list and recognise the handset they
 * left in a taxi. And every token gains a ceiling it cannot be slid past,
 * beside the idle window that ordinary use keeps pushing forward.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'personal_access_tokens',
            function (Blueprint $table): void {
                /*
                 * web | mobile | api. What asked for the token, which is
                 * what decides how long it may live.
                 */
                $table->string('client_type', 20)
                    ->nullable()
                    ->after('abilities');

                /*
                 * ios | android | web, as declared by the client.
                 */
                $table->string('platform', 20)
                    ->nullable()
                    ->after('client_type');

                $table->string('app_version', 30)
                    ->nullable()
                    ->after('platform');

                $table->string('created_ip', 45)
                    ->nullable()
                    ->after('app_version');

                $table->string('last_used_ip', 45)
                    ->nullable()
                    ->after('created_ip');

                /*
                 * The line the sliding idle window may not cross. Sign in
                 * again after this, whatever the device has been doing.
                 */
                $table->timestamp('absolute_expires_at')
                    ->nullable()
                    ->after('last_used_at');
            }
        );

        /*
         * Tokens minted before this release have no lifetime at all. They
         * are given one now rather than left as the single category of
         * credential that never dies: the idle window runs from whenever
         * the token was last actually used, and the ceiling from the day
         * it was created.
         *
         * A token that has already been idle longer than the window is
         * therefore expired on arrival, which is the correct outcome —
         * the browser holds its token in sessionStorage, so anything that
         * old belongs to a tab that closed long ago.
         */
        $idle = (int) config('patrimoine.tokens.web.idle', 60 * 12);
        $absolute = (int) config('patrimoine.tokens.web.absolute', 60 * 24 * 30);

        DB::table('personal_access_tokens')
            ->whereNull('client_type')
            ->update([
                'client_type' => 'web',
            ]);

        DB::table('personal_access_tokens')
            ->whereNull('expires_at')
            ->orderBy('id')
            ->chunkById(500, function ($tokens) use ($idle, $absolute): void {
                foreach ($tokens as $token) {
                    $lastUsed = $token->last_used_at ?? $token->created_at;

                    DB::table('personal_access_tokens')
                        ->where('id', $token->id)
                        ->update([
                            'expires_at' => $lastUsed === null
                                ? now()
                                : \Illuminate\Support\Carbon::parse($lastUsed)
                                    ->addMinutes($idle),

                            'absolute_expires_at' => $token->created_at === null
                                ? now()
                                : \Illuminate\Support\Carbon::parse($token->created_at)
                                    ->addMinutes($absolute),
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table(
            'personal_access_tokens',
            function (Blueprint $table): void {
                $table->dropColumn([
                    'client_type',
                    'platform',
                    'app_version',
                    'created_ip',
                    'last_used_ip',
                    'absolute_expires_at',
                ]);
            }
        );
    }
};
