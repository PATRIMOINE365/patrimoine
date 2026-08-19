<?php

namespace App\Services\Accounting;

use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Records immutable zero-value informational events in the Financial Journal.
 *
 * These entries intentionally contain no debit/credit lines because no money
 * moved. V1.0.5 principally requires this capability to document destructive
 * Lease deletion after the associated accounting reversals have been posted.
 */
class JournalInformationalEventService
{
    public function __construct(
        private readonly JournalNumberService $numbers
    ) {
    }

    /**
     * @param  array<string, mixed>|null  $snapshot
     * @param  array<string, mixed>|null  $metadata
     */
    public function record(
        string $transactionType,
        string $description,
        ?int $actorUserId = null,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?array $snapshot = null,
        ?array $metadata = null,
        ?string $journalDate = null
    ): JournalEntry {
        $transactionType = trim($transactionType);
        $description = trim($description);

        if ($transactionType === '') {
            throw ValidationException::withMessages([
                'transaction_type' =>
                    'Journal transaction type is required.',
            ]);
        }

        if ($description === '') {
            throw ValidationException::withMessages([
                'description' =>
                    'Journal description is required.',
            ]);
        }

        if (
            $sourceId !== null
            && $sourceId <= 0
        ) {
            throw ValidationException::withMessages([
                'source_id' =>
                    'Journal source ID must be positive.',
            ]);
        }

        return DB::transaction(function () use (
            $transactionType,
            $description,
            $actorUserId,
            $sourceType,
            $sourceId,
            $snapshot,
            $metadata,
            $journalDate
        ): JournalEntry {
            $date = $journalDate !== null
                ? \Illuminate\Support\Carbon::parse($journalDate)
                : now();

            $actorName = null;

            if ($actorUserId !== null) {
                $actorName = User::query()
                    ->whereKey($actorUserId)
                    ->value('name');
            }

            /*
             * Informational events use the same permanent annual Journal
             * sequence as financial entries.
             */
            $journalNumber = $this->numbers->next(
                (int) $date->year
            );

            return JournalEntry::create([
                'journal_number' =>
                    $journalNumber,

                'entry_kind' =>
                    JournalEntry::KIND_INFORMATIONAL,

                'journal_date' =>
                    $date->toDateString(),

                'posted_at' =>
                    now(),

                'transaction_type' =>
                    $transactionType,

                'description' =>
                    $description,

                'source_type' =>
                    $sourceType,

                'source_id' =>
                    $sourceId,

                'actor_user_id' =>
                    $actorUserId,

                'actor_name_snapshot' =>
                    $actorName,

                'snapshot' =>
                    $snapshot,

                'metadata' =>
                    $metadata,
            ]);
        });
    }
}
