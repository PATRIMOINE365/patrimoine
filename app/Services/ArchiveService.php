<?php

namespace App\Services;

use App\Models\Building;
use App\Models\Lease;
use App\Models\Party;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Putting a record out of the way, and bringing it back.
 *
 * Patrimoine refuses to delete anything the accounting still refers to,
 * which is correct and also unhelpful: the operator rarely wants the
 * record gone, they want it off the screen. Archiving does that and only
 * that. The row is untouched, so every invoice, receipt, journal entry and
 * audit line still names it; it simply stops appearing in the lists and in
 * the pickers that build new records, so nobody is offered a tenant who
 * left in 2019 when writing a new lease.
 *
 * It is deliberately NOT applied through a global scope. A scope would
 * reach the reports and the documents as well, and a set of accounts whose
 * totals move when somebody tidies a list is a set of accounts nobody can
 * trust. The lists opt in, one query at a time, through notArchived().
 */
class ArchiveService
{
    /**
     * The kinds of record that can be archived, by their public name.
     *
     * @var array<string, class-string<Model>>
     */
    public const KINDS = [
        'party' => Party::class,
        'building' => Building::class,
        'unit' => Unit::class,
        'lease' => Lease::class,
    ];

    public function __construct(
        private BusinessRecordDeletionService $deletion
    ) {}

    /**
     * Hide a record from the lists and the pickers.
     */
    public function archive(Model $record, User $user): void
    {
        if ($record->archived_at !== null) {
            return;
        }

        $record->forceFill([
            'archived_at' => Carbon::now(),
            'archived_by_user_id' => $user->id,
        ])->save();
    }

    /**
     * Put it back.
     */
    public function restore(Model $record): void
    {
        if ($record->archived_at === null) {
            return;
        }

        $record->forceFill([
            'archived_at' => null,
            'archived_by_user_id' => null,
        ])->save();
    }

    /**
     * Whether this record offers Archive rather than Delete.
     *
     * The two are alternatives, never both: a record that can still be
     * deleted has nothing to archive away from, and one that cannot is
     * exactly what archiving is for.
     */
    public function isArchivable(Model $record): bool
    {
        return $record->archived_at === null
            && ! $this->deletion->isDeletable($record);
    }

    /**
     * Everything this organisation has archived, newest first.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listing(): array
    {
        $rows = [];

        foreach (self::KINDS as $kind => $class) {
            /** @var Builder $query */
            $query = $class::query()
                ->whereNotNull('archived_at')
                ->with('archivedBy:id,name');

            if ($kind === 'unit') {
                $query->with('building:id,name');
            }

            if ($kind === 'lease') {
                $query->with(['unit:id,name,building_id', 'tenant:id,name']);
            }

            foreach ($query->get() as $record) {
                $rows[] = [
                    'kind' => $kind,
                    'id' => $record->id,
                    'label' => $this->label($kind, $record),
                    'context' => $this->context($kind, $record),
                    'archived_at' => $record->archived_at?->toIso8601String(),
                    'archived_by' => $record->archivedBy?->name,
                ];
            }
        }

        usort(
            $rows,
            fn (array $a, array $b): int => strcmp(
                (string) $b['archived_at'],
                (string) $a['archived_at']
            )
        );

        return $rows;
    }

    /**
     * What to call the record on the archive page.
     */
    private function label(string $kind, Model $record): string
    {
        return match ($kind) {
            'party' => (string) $record->name,
            'building' => (string) $record->name,
            'unit' => (string) $record->name,
            'lease' => trim(
                ($record->unit?->name ?? '')
                .' — '
                .($record->tenant?->name ?? '')
            ),
            default => (string) $record->id,
        };
    }

    /**
     * The line under it, so a name on its own is not the only clue.
     */
    private function context(string $kind, Model $record): ?string
    {
        return match ($kind) {
            'party' => $record->type,
            'unit' => $record->building?->name,
            'lease' => $record->status,
            default => null,
        };
    }
}
