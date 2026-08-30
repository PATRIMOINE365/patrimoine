<?php

use App\Support\DocumentSequenceBackfill;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * V1.0.36: one counter behind every document number.
 *
 * Ten series each grew their own way of finding the next number, and nine
 * of them found it by reading the highest one already issued. That is
 * wrong three times over:
 *
 *   - two requests read the same highest number and both return it, so
 *     the composite unique key refuses the second write;
 *   - the highest is found by sorting the number as TEXT, which is only
 *     the highest while every number is exactly six digits wide;
 *   - deleting the newest document lets the next one REUSE its number,
 *     which for an accounting series is worse than an error, because two
 *     different documents then carry the same reference and nothing
 *     complains.
 *
 * The tenth series, the Financial Journal, has always done it properly:
 * a row per organisation per year holding the next number, taken under a
 * row lock. This table is that mechanism, generalised — and the journal's
 * own sequence is copied into it here so there is one counter behind
 * everything rather than two mechanisms doing the same job.
 *
 * The seeding lives in DocumentSequenceBackfill so it can be tested
 * against books that already have numbers in them, which is the half of
 * this change that touches real customer documents.
 *
 * `journal_sequences` is deliberately left in place, unused. It costs a
 * few rows and it makes this migration reversible without having to
 * invent the numbers back.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_sequences', function (Blueprint $table): void {
            $table->id();

            $table->unsignedBigInteger('organisation_id');

            /*
             * The prefix without its punctuation: INV, EXP, JRN. Short,
             * because it is a key rather than a label.
             */
            $table->string('series', 8);

            $table->unsignedSmallInteger('year');

            $table->unsignedBigInteger('next_number')->default(1);

            $table->timestamps();

            $table->unique(
                ['organisation_id', 'series', 'year'],
                'document_sequences_org_series_year_unique'
            );
        });

        DocumentSequenceBackfill::run();
    }

    public function down(): void
    {
        Schema::dropIfExists('document_sequences');
    }
};
