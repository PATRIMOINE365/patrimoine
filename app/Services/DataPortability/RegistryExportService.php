<?php

namespace App\Services\DataPortability;

use App\Models\Building;
use App\Models\Lease;
use App\Models\Party;
use App\Models\Unit;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use RuntimeException;

/**
 * V1.0.7 Registry backup export.
 *
 * Builds machine-restorable exports of the four Registry entity sets:
 *
 * - parties (including their functional roles);
 * - buildings (including their ownership allocation);
 * - units;
 * - leases (contractual terms and lifecycle state only).
 *
 * Financial history (payments, invoices, journal entries, tenant funds,
 * owner ledgers) is INTENTIONALLY out of scope. The Financial Journal is
 * immutable accounting history and must never be recreated from a
 * spreadsheet; only the Registry — the records an Administrator might
 * accidentally delete — is covered by backup/restore.
 *
 * Unlike the localized report exports, these files use STABLE untranslated
 * column keys as their header row. The header is a machine contract:
 * RegistryImportService matches columns by these exact keys, so they must
 * never vary with the organisation language.
 */
class RegistryExportService
{
    /**
     * Entity sets covered by Registry backup/restore.
     *
     * @var list<string>
     */
    public const ENTITIES = [
        'parties',
        'buildings',
        'units',
        'leases',
    ];

    /**
     * Stable export column keys per entity.
     *
     * `roles` and `owners` are derived multi-value columns:
     *
     * - roles:  pipe-joined role values, e.g. "tenant|owner";
     * - owners: pipe-joined "party_id:percentage" pairs,
     *           e.g. "12:60.00|15:40.00".
     *
     * @var array<string, list<string>>
     */
    private const COLUMNS = [
        'parties' => [
            'id',
            'type',
            'name',
            'given_names',
            'surname',
            'legal_name',
            'phone',
            'alternate_phone',
            'email',
            'address',
            'contact_person_name',
            'contact_person_phone',
            'contact_person_email',
            'id_number',
            'registration_number',
            'vat_tin',
            'bank_name',
            'bank_account_name',
            'bank_account_number',
            'bank_branch',
            'notes',
            'roles',
        ],

        'buildings' => [
            'id',
            'name',
            'description',
            'address',
            'location',
            'latitude',
            'longitude',
            'notes',
            'owners',
        ],

        'units' => [
            'id',
            'building_id',
            'name',
            'description',
            'is_commercial',
        ],

        'leases' => [
            'id',
            'unit_id',
            'tenant_id',
            'agent_id',
            'start_date',
            'end_date',
            'status',
            'termination_notice_date',
            'rent_amount',
            'payment_frequency',
            'due_day',
            'vat_rate',
            'proration_amount',
            'security_deposit_amount',
            'advance_payment_amount',
            'rent_reserve_amount',
            'rent_increment_type',
            'rent_increment_value',
            'next_rent_increment_date',
            'management_fee_type',
            'management_fee_value',
            'agent_commission_amount',
            'notes',
        ],
    ];

    /**
     * Stable column keys for one entity set.
     *
     * @return list<string>
     */
    public function columns(
        string $entity
    ): array {
        return self::COLUMNS[$entity]
            ?? throw new RuntimeException(
                "Unknown Registry export entity [{$entity}]."
            );
    }

    /**
     * Every current row of one entity set as stringified export values.
     *
     * Values are normalized so the file can be fed back verbatim into
     * RegistryImportService:
     *
     * - null becomes an empty string;
     * - dates use ISO Y-m-d;
     * - booleans use 0/1.
     *
     * @return list<array<string, string>>
     */
    public function rows(
        string $entity
    ): array {
        return match ($entity) {
            'parties' =>
                $this->partyRows(),

            'buildings' =>
                $this->buildingRows(),

            'units' =>
                $this->unitRows(),

            'leases' =>
                $this->leaseRows(),

            default =>
                throw new RuntimeException(
                    "Unknown Registry export entity [{$entity}]."
                ),
        };
    }

    /**
     * @return list<array<string, string>>
     */
    private function partyRows(): array
    {
        return Party::query()
            ->with('roles')
            ->orderBy('id')
            ->get()
            ->map(
                fn (Party $party): array => [
                    'id' =>
                        (string) $party->id,

                    'type' =>
                        (string) $party->type,

                    'name' =>
                        (string) ($party->name ?? ''),

                    'given_names' =>
                        (string) ($party->given_names ?? ''),

                    'surname' =>
                        (string) ($party->surname ?? ''),

                    'legal_name' =>
                        (string) ($party->legal_name ?? ''),

                    'phone' =>
                        (string) ($party->phone ?? ''),

                    'alternate_phone' =>
                        (string) ($party->alternate_phone ?? ''),

                    'email' =>
                        (string) ($party->email ?? ''),

                    'address' =>
                        (string) ($party->address ?? ''),

                    'contact_person_name' =>
                        (string) ($party->contact_person_name ?? ''),

                    'contact_person_phone' =>
                        (string) ($party->contact_person_phone ?? ''),

                    'contact_person_email' =>
                        (string) ($party->contact_person_email ?? ''),

                    'id_number' =>
                        (string) ($party->id_number ?? ''),

                    'registration_number' =>
                        (string) ($party->registration_number ?? ''),

                    'vat_tin' =>
                        (string) ($party->vat_tin ?? ''),

                    'bank_name' =>
                        (string) ($party->bank_name ?? ''),

                    'bank_account_name' =>
                        (string) ($party->bank_account_name ?? ''),

                    'bank_account_number' =>
                        (string) ($party->bank_account_number ?? ''),

                    'bank_branch' =>
                        (string) ($party->bank_branch ?? ''),

                    'notes' =>
                        (string) ($party->notes ?? ''),

                    /*
                     * Sorted for byte-stable exports: the same Registry
                     * always produces the same file.
                     */
                    'roles' =>
                        $party->roles
                            ->pluck('role')
                            ->sort()
                            ->implode('|'),
                ]
            )
            ->all();
    }

    /**
     * @return list<array<string, string>>
     */
    private function buildingRows(): array
    {
        return Building::query()
            ->with('ownerships')
            ->orderBy('id')
            ->get()
            ->map(
                fn (Building $building): array => [
                    'id' =>
                        (string) $building->id,

                    'name' =>
                        (string) $building->name,

                    'description' =>
                        (string) ($building->description ?? ''),

                    'address' =>
                        (string) ($building->address ?? ''),

                    'location' =>
                        (string) ($building->location ?? ''),

                    'latitude' =>
                        (string) ($building->latitude ?? ''),

                    'longitude' =>
                        (string) ($building->longitude ?? ''),

                    'notes' =>
                        (string) ($building->notes ?? ''),

                    /*
                     * "party_id:percentage" pairs, e.g. "12:60.00|15:40.00".
                     */
                    'owners' =>
                        $building->ownerships
                            ->sortBy('party_id')
                            ->map(
                                fn ($ownership): string =>
                                    $ownership->party_id
                                    .':'
                                    .$ownership->ownership_percentage
                            )
                            ->implode('|'),
                ]
            )
            ->all();
    }

    /**
     * @return list<array<string, string>>
     */
    private function unitRows(): array
    {
        return Unit::query()
            ->orderBy('id')
            ->get()
            ->map(
                fn (Unit $unit): array => [
                    'id' =>
                        (string) $unit->id,

                    'building_id' =>
                        (string) $unit->building_id,

                    'name' =>
                        (string) $unit->name,

                    'description' =>
                        (string) ($unit->description ?? ''),

                    'is_commercial' =>
                        $unit->is_commercial
                            ? '1'
                            : '0',
                ]
            )
            ->all();
    }

    /**
     * @return list<array<string, string>>
     */
    private function leaseRows(): array
    {
        return Lease::query()
            ->orderBy('id')
            ->get()
            ->map(
                fn (Lease $lease): array => [
                    'id' =>
                        (string) $lease->id,

                    'unit_id' =>
                        (string) $lease->unit_id,

                    'tenant_id' =>
                        (string) $lease->tenant_id,

                    'agent_id' =>
                        $lease->agent_id === null
                            ? ''
                            : (string) $lease->agent_id,

                    'start_date' =>
                        $lease->start_date->format('Y-m-d'),

                    'end_date' =>
                        $lease->end_date?->format('Y-m-d')
                            ?? '',

                    'status' =>
                        (string) $lease->status,

                    'termination_notice_date' =>
                        $lease->termination_notice_date
                            ?->format('Y-m-d')
                            ?? '',

                    'rent_amount' =>
                        (string) $lease->rent_amount,

                    'payment_frequency' =>
                        (string) $lease->payment_frequency,

                    'due_day' =>
                        $lease->due_day === null
                            ? ''
                            : (string) $lease->due_day,

                    'vat_rate' =>
                        (string) $lease->vat_rate,

                    'proration_amount' =>
                        $lease->proration_amount === null
                            ? ''
                            : (string) $lease->proration_amount,

                    'security_deposit_amount' =>
                        (string) $lease->security_deposit_amount,

                    'advance_payment_amount' =>
                        (string) $lease->advance_payment_amount,

                    'rent_reserve_amount' =>
                        (string) $lease->rent_reserve_amount,

                    'rent_increment_type' =>
                        (string) $lease->rent_increment_type,

                    'rent_increment_value' =>
                        (string) $lease->rent_increment_value,

                    'next_rent_increment_date' =>
                        $lease->next_rent_increment_date
                            ?->format('Y-m-d')
                            ?? '',

                    'management_fee_type' =>
                        (string) $lease->management_fee_type,

                    'management_fee_value' =>
                        (string) $lease->management_fee_value,

                    'agent_commission_amount' =>
                        (string) $lease->agent_commission_amount,

                    'notes' =>
                        (string) ($lease->notes ?? ''),
                ]
            )
            ->all();
    }

    /**
     * One entity set as UTF-8 CSV (BOM-prefixed for spreadsheet apps).
     */
    public function csv(
        string $entity
    ): string {
        $stream =
            fopen(
                'php://temp',
                'r+'
            );

        if ($stream === false) {
            throw new RuntimeException(
                'Unable to create Registry CSV stream.'
            );
        }

        $columns =
            $this->columns(
                $entity
            );

        fputcsv(
            $stream,
            $columns,
            ',',
            '"',
            ''
        );

        foreach (
            $this->rows(
                $entity
            ) as $row
        ) {
            fputcsv(
                $stream,
                array_map(
                    static fn (
                        string $key
                    ): string =>
                        $row[$key]
                        ?? '',
                    $columns
                ),
                ',',
                '"',
                ''
            );
        }

        rewind(
            $stream
        );

        $contents =
            stream_get_contents(
                $stream
            );

        fclose(
            $stream
        );

        if ($contents === false) {
            throw new RuntimeException(
                'Unable to read Registry CSV.'
            );
        }

        return "\xEF\xBB\xBF"
            .$contents;
    }

    /**
     * One entity set as a single-sheet XLSX workbook.
     */
    public function xlsx(
        string $entity
    ): string {
        return $this->workbook([
            $entity,
        ]);
    }

    /**
     * The complete Registry backup: ONE workbook with one sheet per entity,
     * each sheet named after its entity set.
     */
    public function xlsxAll(): string
    {
        return $this->workbook(
            self::ENTITIES
        );
    }

    /**
     * Write one XLSX workbook containing one named sheet per entity.
     *
     * @param list<string> $entities
     */
    private function workbook(
        array $entities
    ): string {
        $path =
            tempnam(
                sys_get_temp_dir(),
                'patrimoine-registry-'
            );

        if ($path === false) {
            throw new RuntimeException(
                'Unable to create temporary XLSX file.'
            );
        }

        $writer =
            new Writer();

        try {
            $writer->openToFile(
                $path
            );

            foreach ($entities as $index => $entity) {
                /*
                 * The writer opens with one implicit current sheet;
                 * additional entities each get their own named sheet.
                 */
                $sheet =
                    $index === 0
                        ? $writer->getCurrentSheet()
                        : $writer->addNewSheetAndMakeItCurrent();

                $sheet->setName(
                    $entity
                );

                $columns =
                    $this->columns(
                        $entity
                    );

                $writer->addRow(
                    Row::fromValues(
                        $columns
                    )
                );

                foreach (
                    $this->rows(
                        $entity
                    ) as $row
                ) {
                    $writer->addRow(
                        Row::fromValues(
                            array_map(
                                static fn (
                                    string $key
                                ): string =>
                                    $row[$key]
                                    ?? '',
                                $columns
                            )
                        )
                    );
                }
            }

            $writer->close();

            $contents =
                file_get_contents(
                    $path
                );

            if ($contents === false) {
                throw new RuntimeException(
                    'Unable to read generated XLSX file.'
                );
            }

            return $contents;
        } finally {
            if (is_file($path)) {
                @unlink(
                    $path
                );
            }
        }
    }
}
