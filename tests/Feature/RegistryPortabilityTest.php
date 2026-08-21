<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\Building;
use App\Models\Lease;
use App\Models\Party;
use App\Models\Unit;
use App\Models\User;
use App\Services\DataPortability\RegistryExportService;
use App\Services\DataPortability\RegistryImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Freeze V1.0.7 Registry backup export and idempotent restore behavior.
 *
 * The Registry (parties, buildings + ownership, units, leases) is the
 * only portable data set; financial history is immutable and has no
 * import path. Restore must be idempotent and must never duplicate,
 * corrupt or evict live records.
 */
class RegistryPortabilityTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    */

    public function test_registry_portability_requires_authentication(): void
    {
        $this
            ->getJson('/api/registry/export?entity=parties&format=csv')
            ->assertUnauthorized();

        $this
            ->postJson('/api/registry/import')
            ->assertUnauthorized();
    }

    public function test_registry_portability_is_administrator_only(): void
    {
        foreach (
            [
                UserRole::PropertyManager,
                UserRole::Viewer,
            ] as $role
        ) {
            Sanctum::actingAs(
                User::factory()->create([
                    'role' => $role,
                ])
            );

            $this
                ->get('/api/registry/export?entity=parties&format=csv')
                ->assertForbidden();

            $this
                ->get('/api/registry/export/full')
                ->assertForbidden();

            $this
                ->get('/api/registry/export/pdf?entity=parties')
                ->assertForbidden();

            $this
                ->post('/api/registry/import')
                ->assertForbidden();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Export
    |--------------------------------------------------------------------------
    */

    public function test_csv_export_uses_stable_headers_and_contains_every_row(): void
    {
        $this->registry();

        Sanctum::actingAs(
            $this->administrator()
        );

        $expectedCounts = [
            'parties' => 2,
            'buildings' => 1,
            'units' => 2,
            'leases' => 1,
        ];

        $export =
            app(
                RegistryExportService::class
            );

        foreach ($expectedCounts as $entity => $expected) {
            $response =
                $this
                    ->get(
                        "/api/registry/export?entity={$entity}&format=csv"
                    )
                    ->assertOk()
                    ->assertHeader(
                        'content-type',
                        'text/csv; charset=UTF-8'
                    );

            $content =
                (string) $response->getContent();

            /*
             * BOM prefix for spreadsheet applications.
             */
            $this->assertStringStartsWith(
                "\xEF\xBB\xBF",
                $content
            );

            $lines =
                array_values(
                    array_filter(
                        explode(
                            "\n",
                            trim(
                                substr($content, 3)
                            )
                        )
                    )
                );

            /*
             * The header row is the stable machine contract used by the
             * import — exact keys, exact order.
             */
            $this->assertSame(
                implode(
                    ',',
                    $export->columns($entity)
                ),
                trim($lines[0]),
                "Unexpected {$entity} header."
            );

            $this->assertCount(
                $expected + 1,
                $lines,
                "Unexpected {$entity} row count."
            );
        }

        $this->assertDatabaseHas(
            'activity_logs',
            [
                'action' => 'registry.exported',
                'entity_type' => 'registry',
            ]
        );
    }

    public function test_derived_columns_carry_roles_and_ownership_pairs(): void
    {
        $context =
            $this->registry();

        Sanctum::actingAs(
            $this->administrator()
        );

        $partiesCsv =
            (string) $this
                ->get(
                    '/api/registry/export?entity=parties&format=csv'
                )
                ->assertOk()
                ->getContent();

        $this->assertStringContainsString(
            'owner|tenant',
            $partiesCsv
        );

        $buildingsCsv =
            (string) $this
                ->get(
                    '/api/registry/export?entity=buildings&format=csv'
                )
                ->assertOk()
                ->getContent();

        /*
         * Pairs are sorted by party id for byte-stable exports; the
         * tenant Party is created before the owner organisation.
         */
        $this->assertStringContainsString(
            $context['tenant']->id
            .':40.00|'
            .$context['owner']->id
            .':60.00',
            $buildingsCsv
        );
    }

    public function test_xlsx_export_and_full_workbook_download(): void
    {
        $this->registry();

        Sanctum::actingAs(
            $this->administrator()
        );

        $single =
            $this
                ->get(
                    '/api/registry/export?entity=parties&format=xlsx'
                )
                ->assertOk()
                ->assertHeader(
                    'content-type',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                );

        $this->assertStringStartsWith(
            'PK',
            (string) $single->getContent()
        );

        $full =
            $this
                ->get(
                    '/api/registry/export/full'
                )
                ->assertOk()
                ->assertHeader(
                    'content-type',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                );

        $this->assertStringStartsWith(
            'PK',
            (string) $full->getContent()
        );

        $exportEvent =
            ActivityLog::query()
                ->where(
                    'action',
                    'registry.exported'
                )
                ->latest('id')
                ->firstOrFail();

        $this->assertSame(
            'all',
            $exportEvent->metadata['entity']
        );

        $this->assertSame(
            2,
            $exportEvent
                ->metadata['record_counts']['parties']
        );
    }

    public function test_pdf_export_renders_a_review_document(): void
    {
        $this->registry();

        Sanctum::actingAs(
            $this->administrator()
        );

        $response =
            $this
                ->get(
                    '/api/registry/export/pdf?entity=leases'
                )
                ->assertOk()
                ->assertHeader(
                    'content-type',
                    'application/pdf'
                );

        $this->assertStringStartsWith(
            '%PDF',
            (string) $response->getContent()
        );
    }

    public function test_export_validates_entity_and_format(): void
    {
        Sanctum::actingAs(
            $this->administrator()
        );

        $this
            ->getJson(
                '/api/registry/export?entity=payments&format=csv'
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'entity',
            ]);

        $this
            ->getJson(
                '/api/registry/export?entity=parties&format=pdf'
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'format',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Idempotence
    |--------------------------------------------------------------------------
    */

    public function test_reimporting_an_unchanged_export_changes_nothing(): void
    {
        $this->registry();

        $export =
            app(
                RegistryExportService::class
            );

        $import =
            app(
                RegistryImportService::class
            );

        $expectedCounts = [
            'parties' => 2,
            'buildings' => 1,
            'units' => 2,
            'leases' => 1,
        ];

        foreach ($expectedCounts as $entity => $expected) {
            $result =
                $import->import(
                    $entity,
                    $export->rows($entity)
                );

            $this->assertSame(
                [
                    'created' => 0,
                    'updated' => 0,
                    'unchanged' => $expected,
                    'skipped' => [],
                ],
                $result,
                "Import of unchanged {$entity} was not idempotent."
            );

            $this->assertDatabaseCount(
                $entity,
                $expected
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Restore after deletion
    |--------------------------------------------------------------------------
    */

    public function test_deleted_party_is_recreated_with_new_id_and_roles(): void
    {
        $this->registry();

        $standalone =
            Party::create([
                'type' => 'person',
                'given_names' => 'Kwesi',
                'surname' => 'Appiah',
                'phone' => '0244000099',
                'email' => 'kwesi@example.test',
            ]);

        $standalone->roles()->create([
            'role' => 'tenant',
        ]);

        $standalone->roles()->create([
            'role' => 'agent',
        ]);

        $backup =
            app(
                RegistryExportService::class
            )->rows('parties');

        $oldId =
            $standalone->id;

        /*
         * The accidental deletion: unreferenced, so the safety rules
         * would have allowed it. Roles cascade with the Party.
         */
        $standalone->delete();

        $result =
            app(
                RegistryImportService::class
            )->import(
                'parties',
                $backup
            );

        $this->assertSame(
            1,
            $result['created']
        );

        $this->assertSame(
            2,
            $result['unchanged']
        );

        $restored =
            Party::query()
                ->where(
                    'email',
                    'kwesi@example.test'
                )
                ->firstOrFail();

        /*
         * Recreated fresh — ids are never forced.
         */
        $this->assertNotSame(
            $oldId,
            $restored->id
        );

        $this->assertSame(
            'Kwesi Appiah',
            $restored->name
        );

        $this->assertEqualsCanonicalizing(
            [
                'agent',
                'tenant',
            ],
            $restored->roles
                ->pluck('role')
                ->all()
        );
    }

    public function test_unit_restore_remaps_a_recreated_building_id(): void
    {
        $this->registry();

        $building =
            Building::create([
                'name' => 'Remap House',
            ]);

        $unit =
            Unit::create([
                'building_id' => $building->id,
                'name' => 'Remap Shop',
                'is_commercial' => true,
            ]);

        $export =
            app(
                RegistryExportService::class
            );

        $buildingBackup =
            $export->rows('buildings');

        $unitBackup =
            $export->rows('units');

        $oldBuildingId =
            $building->id;

        $unit->delete();

        $building->delete();

        /*
         * Occupy the deleted Building's id with an unrelated record so
         * the exported id now points at a DIFFERENT building. The import
         * must detect the incompatible natural key, recreate "Remap
         * House" under a fresh id, and remap the Unit's foreign key.
         */
        Building::create([
            'name' => 'Unrelated Newcomer',
        ]);

        $import =
            app(
                RegistryImportService::class
            );

        $buildingResult =
            $import->import(
                'buildings',
                $buildingBackup
            );

        $this->assertSame(
            1,
            $buildingResult['created']
        );

        /*
         * SAME import run (same service instance): the id map built while
         * importing buildings carries over into the units import.
         */
        $unitResult =
            $import->import(
                'units',
                $unitBackup
            );

        $this->assertSame(
            1,
            $unitResult['created']
        );

        $this->assertSame(
            [],
            $unitResult['skipped']
        );

        $recreatedBuilding =
            Building::query()
                ->where(
                    'name',
                    'Remap House'
                )
                ->firstOrFail();

        $this->assertNotSame(
            $oldBuildingId,
            $recreatedBuilding->id
        );

        $restoredUnit =
            Unit::query()
                ->where(
                    'name',
                    'Remap Shop'
                )
                ->firstOrFail();

        $this->assertSame(
            $recreatedBuilding->id,
            $restoredUnit->building_id
        );

        $this->assertTrue(
            $restoredUnit->is_commercial
        );
    }

    public function test_restore_never_alters_existing_ownership_percentages(): void
    {
        $context =
            $this->registry();

        $backup =
            app(
                RegistryExportService::class
            )->rows('buildings');

        /*
         * Ownership changed after the backup was taken. A restore must
         * treat the live allocation as authoritative.
         */
        $context['building']
            ->ownerships()
            ->where(
                'party_id',
                $context['owner']->id
            )
            ->update([
                'ownership_percentage' => '75.00',
            ]);

        app(
            RegistryImportService::class
        )->import(
            'buildings',
            $backup
        );

        $this->assertDatabaseHas(
            'building_owners',
            [
                'building_id' => $context['building']->id,
                'party_id' => $context['owner']->id,
                'ownership_percentage' => '75.00',
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Lease safety
    |--------------------------------------------------------------------------
    */

    public function test_active_lease_import_is_skipped_when_unit_is_occupied(): void
    {
        $context =
            $this->registry();

        /*
         * A backup row describing a DIFFERENT active Lease on the Unit
         * that already carries the live active Lease. Restore must never
         * evict the current tenant.
         */
        $result =
            app(
                RegistryImportService::class
            )->import(
                'leases',
                [
                    [
                        'id' => '',
                        'unit_id' => (string) $context['unit']->id,
                        'tenant_id' => (string) $context['owner']->id,
                        'start_date' => '2026-05-01',
                        'status' => 'active',
                        'rent_amount' => '30000',
                    ],
                ]
            );

        $this->assertSame(
            0,
            $result['created']
        );

        $this->assertCount(
            1,
            $result['skipped']
        );

        $this->assertSame(
            2,
            $result['skipped'][0]['row']
        );

        $this->assertStringContainsString(
            'active',
            $result['skipped'][0]['reason']
        );

        $this->assertDatabaseCount(
            'leases',
            1
        );
    }

    public function test_lease_import_validates_status_against_the_enum(): void
    {
        $context =
            $this->registry();

        $result =
            app(
                RegistryImportService::class
            )->import(
                'leases',
                [
                    [
                        'id' => '',
                        'unit_id' => (string) $context['vacantUnit']->id,
                        'tenant_id' => (string) $context['tenant']->id,
                        'start_date' => '2026-05-01',
                        'status' => 'suspended',
                        'rent_amount' => '30000',
                    ],
                ]
            );

        $this->assertSame(
            0,
            $result['created']
        );

        $this->assertStringContainsString(
            'status',
            $result['skipped'][0]['reason']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | HTTP import + dry run
    |--------------------------------------------------------------------------
    */

    public function test_dry_run_reports_counts_without_touching_the_database(): void
    {
        $this->registry();

        $standalone =
            Party::create([
                'type' => 'person',
                'given_names' => 'Efua',
                'surname' => 'Owusu',
                'phone' => '0244000077',
                'email' => 'efua@example.test',
            ]);

        $standalone->roles()->create([
            'role' => 'owner',
        ]);

        $csv =
            app(
                RegistryExportService::class
            )->csv('parties');

        $standalone->delete();

        Sanctum::actingAs(
            $this->administrator()
        );

        $dryRun =
            $this
                ->post(
                    '/api/registry/import',
                    [
                        'file' =>
                            UploadedFile::fake()
                                ->createWithContent(
                                    'parties.csv',
                                    $csv
                                ),

                        'entity' => 'parties',

                        'dry_run' => '1',
                    ]
                )
                ->assertOk();

        $dryRun
            ->assertJsonPath('dry_run', true)
            ->assertJsonPath('created', 1)
            ->assertJsonPath('unchanged', 2);

        /*
         * The dry run rolled everything back.
         */
        $this->assertDatabaseMissing(
            'parties',
            [
                'email' => 'efua@example.test',
            ]
        );

        $importEvent =
            ActivityLog::query()
                ->where(
                    'action',
                    'registry.imported'
                )
                ->latest('id')
                ->firstOrFail();

        $this->assertTrue(
            $importEvent->metadata['dry_run']
        );

        $this->assertSame(
            1,
            $importEvent
                ->metadata['counts']['created']
        );

        /*
         * The same file imported for real restores the Party.
         */
        $this
            ->post(
                '/api/registry/import',
                [
                    'file' =>
                        UploadedFile::fake()
                            ->createWithContent(
                                'parties.csv',
                                $csv
                            ),

                    'entity' => 'parties',

                    'dry_run' => '0',
                ]
            )
            ->assertOk()
            ->assertJsonPath('dry_run', false)
            ->assertJsonPath('created', 1);

        $this->assertDatabaseHas(
            'parties',
            [
                'email' => 'efua@example.test',
            ]
        );
    }

    public function test_import_rejects_a_file_missing_required_columns(): void
    {
        Sanctum::actingAs(
            $this->administrator()
        );

        $this
            ->post(
                '/api/registry/import',
                [
                    'file' =>
                        UploadedFile::fake()
                            ->createWithContent(
                                'broken.csv',
                                "foo,bar\n1,2\n"
                            ),

                    'entity' => 'parties',
                ],
                [
                    'Accept' => 'application/json',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'file',
            ]);

        /*
         * A rejected file must not record an import event.
         */
        $this->assertDatabaseMissing(
            'activity_logs',
            [
                'action' => 'registry.imported',
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Fixtures
    |--------------------------------------------------------------------------
    */

    /**
     * One small but complete Registry:
     *
     * - 2 parties (tenant person, owner organisation) with roles;
     * - 1 building owned 60/40 by both parties;
     * - 2 units (one commercial with an active lease, one vacant);
     * - 1 active lease.
     *
     * @return array{
     *     tenant: Party,
     *     owner: Party,
     *     building: Building,
     *     unit: Unit,
     *     vacantUnit: Unit,
     *     lease: Lease
     * }
     */
    private function registry(): array
    {
        $tenant =
            Party::create([
                'type' => 'person',
                'given_names' => 'Ama',
                'surname' => 'Mensah',
                'phone' => '0244000001',
                'email' => 'ama@example.test',
            ]);

        $tenant->roles()->create([
            'role' => 'tenant',
        ]);

        $owner =
            Party::create([
                'type' => 'organisation',
                'name' => 'Volta Holdings',
                'legal_name' => 'Volta Holdings Ltd',
                'contact_person_name' => 'Kofi Boateng',
                'contact_person_phone' => '0244000002',
                'contact_person_email' => 'kofi@example.test',
            ]);

        $owner->roles()->create([
            'role' => 'owner',
        ]);

        $tenant->roles()->create([
            'role' => 'owner',
        ]);

        $building =
            Building::create([
                'name' => 'Volta House',
                'location' => 'Accra',
            ]);

        $building->ownerships()->create([
            'party_id' => $owner->id,
            'ownership_percentage' => '60.00',
        ]);

        $building->ownerships()->create([
            'party_id' => $tenant->id,
            'ownership_percentage' => '40.00',
        ]);

        $unit =
            Unit::create([
                'building_id' => $building->id,
                'name' => 'Shop 1',
                'is_commercial' => true,
            ]);

        $vacantUnit =
            Unit::create([
                'building_id' => $building->id,
                'name' => 'Apartment 2',
            ]);

        $lease =
            Lease::create([
                'unit_id' => $unit->id,
                'tenant_id' => $tenant->id,
                'start_date' => '2026-01-01',
                'status' => 'active',
                'rent_amount' => 50000,
                'payment_frequency' => 'monthly',
                'security_deposit_amount' => 100000,
            ]);

        return [
            'tenant' => $tenant,
            'owner' => $owner,
            'building' => $building,
            'unit' => $unit,
            'vacantUnit' => $vacantUnit,
            'lease' => $lease,
        ];
    }

    private function administrator(): User
    {
        return User::factory()->create([
            'role' => UserRole::Administrator,

            'is_active' => true,

            'email_verified_at' => now(),
        ]);
    }
}
