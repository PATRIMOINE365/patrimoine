<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Store single-use V1.0.3 User invitation tokens.
 *
 * Only a SHA-256 hash of the secret token is persisted. The plain-text
 * token exists only long enough to construct the invitation link.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'user_invitations',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId('user_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table
                    ->string('token_hash', 64)
                    ->unique();

                $table->timestamp('expires_at');

                /*
                 * accepted_at makes successful consumption explicit while
                 * invalidated_at records Administrator resend invalidation.
                 */
                $table
                    ->timestamp('accepted_at')
                    ->nullable();

                $table
                    ->timestamp('invalidated_at')
                    ->nullable();

                $table->timestamps();

                $table->index([
                    'user_id',
                    'expires_at',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('user_invitations');
    }
};
