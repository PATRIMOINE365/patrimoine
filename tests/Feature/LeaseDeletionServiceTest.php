<?php

namespace Tests\Feature;

use App\Models\Lease;
use App\Models\User;
use App\Services\LeaseDeletion\LeaseDeletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LeaseDeletionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rejects_wrong_typed_confirmation(): void
    {
        [$lease, $user, $request] =
            $this->context();

        $this->expectException(
            ValidationException::class
        );

        app(LeaseDeletionService::class)->delete(
            lease: $lease,
            actor: $user,
            request: $request,
            reason: 'Entered in error.',
            confirmation: 'delete',
            currentPassword: 'Password123!',
        );
    }

    public function test_it_rejects_wrong_current_password(): void
    {
        [$lease, $user, $request] =
            $this->context();

        $this->expectException(
            ValidationException::class
        );

        app(LeaseDeletionService::class)->delete(
            lease: $lease,
            actor: $user,
            request: $request,
            reason: 'Entered in error.',
            confirmation: 'DELETE',
            currentPassword: 'WrongPassword!',
        );
    }

    public function test_failure_after_journal_reversals_rolls_back_everything(): void
    {
        [$lease, $user, $request] =
            $this->context();

        $leaseId = $lease->id;

        $beforeJournal =
            DB::table('journal_entries')->count();

        $beforeActivity =
            DB::table('activity_logs')->count();

        try {
            app(LeaseDeletionService::class)->delete(
                lease: $lease,
                actor: $user,
                request: $request,
                reason: 'Rollback test.',
                confirmation: 'DELETE',
                currentPassword: 'Password123!',
                failureHook: static function (
                    string $stage
                ): void {
                    if (
                        $stage ===
                        'after_journal_reversals'
                    ) {
                        throw new \RuntimeException(
                            'Injected failure.'
                        );
                    }
                },
            );

            self::fail(
                'Expected injected failure.'
            );
        } catch (\RuntimeException $exception) {
            self::assertSame(
                'Injected failure.',
                $exception->getMessage()
            );
        }

        self::assertDatabaseHas(
            'leases',
            ['id' => $leaseId]
        );

        self::assertSame(
            $beforeJournal,
            DB::table('journal_entries')->count()
        );

        self::assertSame(
            $beforeActivity,
            DB::table('activity_logs')->count()
        );
    }

    public function test_failure_after_operational_delete_restores_lease(): void
    {
        [$lease, $user, $request] =
            $this->context();

        $leaseId = $lease->id;

        try {
            app(LeaseDeletionService::class)->delete(
                lease: $lease,
                actor: $user,
                request: $request,
                reason: 'Rollback operational deletion.',
                confirmation: 'DELETE',
                currentPassword: 'Password123!',
                failureHook: static function (
                    string $stage
                ): void {
                    if (
                        $stage ===
                        'after_operational_delete'
                    ) {
                        throw new \RuntimeException(
                            'Injected operational failure.'
                        );
                    }
                },
            );

            self::fail(
                'Expected injected failure.'
            );
        } catch (\RuntimeException $exception) {
            self::assertSame(
                'Injected operational failure.',
                $exception->getMessage()
            );
        }

        self::assertDatabaseHas(
            'leases',
            ['id' => $leaseId]
        );
    }

    public function test_simple_safe_lease_deletion_is_audited(): void
    {
        [$lease, $user, $request] =
            $this->context();

        $leaseId = $lease->id;

        $result =
            app(LeaseDeletionService::class)->delete(
                lease: $lease,
                actor: $user,
                request: $request,
                reason: 'Lease was created in error.',
                confirmation: 'DELETE',
                currentPassword: 'Password123!',
            );

        self::assertDatabaseMissing(
            'leases',
            ['id' => $leaseId]
        );

        self::assertDatabaseHas(
            'activity_logs',
            [
                'action' => 'lease.deleted',
                'entity_type' => 'lease',
                'entity_id' => (string) $leaseId,
            ]
        );

        self::assertDatabaseHas(
            'journal_entries',
            [
                'id' =>
                    $result[
                        'informational_journal_entry_id'
                    ],
                'entry_kind' => 'informational',
                'transaction_type' =>
                    'lease_deletion',
                'source_type' => 'lease',
                'source_id' => $leaseId,
            ]
        );
    }

    /**
     * @return array{Lease, User, Request}
     */
    private function context(): array
    {
        /*
         * Reuse the application's normal factories where available. The
         * existing Lease deletion service tests already establish that a
         * factory-created Lease is a safe minimal deletion candidate.
         */
        $user = User::factory()->create([
            'password' =>
                Hash::make('Password123!'),
        ]);

        $tenant =
            \App\Models\Party::query()
                ->create([
                    'type' =>
                        'person',

                    'name' =>
                        'Phase 10D4 Tenant',
                ]);

        $building =
            \App\Models\Building::query()
                ->create([
                    'name' =>
                        'Phase 10D4 Building',
                ]);

        $unit =
            \App\Models\Unit::query()
                ->create([
                    'building_id' =>
                        $building->id,

                    'name' =>
                        'Phase 10D4 Unit',
                ]);

        $lease =
            Lease::query()
                ->create([
                    'unit_id' =>
                        $unit->id,

                    'tenant_id' =>
                        $tenant->id,

                    'start_date' =>
                        '2026-01-01',

                    'end_date' =>
                        '2026-12-31',

                    'status' =>
                        'draft',

                    'rent_amount' =>
                        1000,

                    'payment_frequency' =>
                        'monthly',

                    'due_day' =>
                        1,

                    'vat_rate' =>
                        0,

                    'proration_amount' =>
                        0,

                    'security_deposit_amount' =>
                        0,

                    'advance_payment_amount' =>
                        0,

                    'rent_reserve_amount' =>
                        0,

                    'management_fee_type' =>
                        'none',

                    'management_fee_value' =>
                        0,

                    'agent_commission_amount' =>
                        0,
                ]);

        $request = Request::create(
            '/api/leases/'.$lease->id,
            'DELETE'
        );

        $request->setUserResolver(
            static fn () => $user
        );

        return [
            $lease,
            $user,
            $request,
        ];
    }
}
