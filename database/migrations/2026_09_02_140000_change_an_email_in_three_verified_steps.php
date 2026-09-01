<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * The sign-in email is changed through its own three-step flow: ask with
 * the current password, prove the OLD mailbox, prove the NEW one. This
 * table is that flow's state.
 *
 * The user's row is untouched until the final step: the proposed address
 * lives here, with no account authority of its own — no password reset,
 * no sign-in code ever goes to it while it is only proposed. That is the
 * property the whole design exists for, so it is structural rather than
 * checked: nothing reads a proposed address except the flow itself.
 *
 * Deliberately NOT MfaChallenge. A sign-in challenge has no notion of
 * purpose, and reusing it is exactly how proof minted for one thing ends
 * up authorising another. This record binds every code to one user, one
 * proposed address and one stage.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_change_requests', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
             * Opaque handle the browser holds between the steps. Only the
             * pair (signed-in user + token) reaches the request, so a
             * leaked token alone is useless.
             */
            $table->string('token', 64)->unique();

            $table->string('proposed_email');

            /*
             * One code per stage, stored hashed like a password. The code
             * for the new mailbox is minted only after the old one has
             * answered, so it starts life empty.
             */
            $table->string('current_code_hash');
            $table->string('proposed_code_hash')->nullable();

            /*
             * Expiry of whichever code is outstanding. A resend renews it.
             */
            $table->timestamp('code_expires_at');

            $table->timestamp('current_verified_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            /*
             * Why a request closed without completing, for the audit trail:
             * user, superseded, attempts.
             */
            $table->string('cancelled_reason', 20)->nullable();

            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedTinyInteger('resends')->default(0);
            $table->timestamp('last_sent_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_change_requests');
    }
};
