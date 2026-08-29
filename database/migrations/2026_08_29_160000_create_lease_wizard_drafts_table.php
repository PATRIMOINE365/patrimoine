<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * V1.0.31: somewhere to put an unfinished assistant.
 *
 * A lease cannot be saved half-made — unit_id and tenant_id are required
 * columns and always have been — so "save as draft" from page three had
 * nowhere to go. What is saved here is the assistant itself: whatever has
 * been filled in so far, resumable from where it was left.
 *
 * Deliberately NOT a lease. Nothing in this table is a business record:
 * no invoice, no journal entry and no report ever reads it, and deleting
 * one costs nothing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lease_wizard_drafts', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('organisation_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
             * Who started it. The name is snapshotted beside the link
             * because it is what the list shows, and it should still read
             * sensibly after the account is gone.
             */
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('author_name', 255);

            /*
             * The assistant's own field values. Shape belongs to the
             * browser: the server keeps it, never interprets it, and
             * validates nothing until the letting is actually created.
             */
            $table->json('payload');

            $table->timestamps();

            $table->index(['organisation_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lease_wizard_drafts');
    }
};
