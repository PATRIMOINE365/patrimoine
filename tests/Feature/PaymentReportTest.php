<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Lease;
use App\Models\Party;
use App\Models\PartyRole;
use App\Models\Payment;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApiUser;
use Tests\TestCase;

class PaymentReportTest extends TestCase
{
    use AuthenticatesApiUser;
    use RefreshDatabase;

    public function test_payment_report_requires_authentication(): void
    {
        $this->getJson(
            '/api/reports/payments'
        )->assertUnauthorized();
    }

    public function test_payment_report_returns_tenant_payment_projection(): void
    {
        $context =
            $this->createPaymentReportFixture();

        $this->authenticateApiUser();

        $this
            ->getJson(
                '/api/reports/payments'
            )
            ->assertOk()
            ->assertJsonPath(
                'summary.payment_count',
                1
            )
            ->assertJsonPath(
                'summary.total_received',
                5000
            )
            ->assertJsonPath(
                'payments.0.id',
                $context['payment']->id
            )
            ->assertJsonPath(
                'payments.0.amount',
                5000
            )
            ->assertJsonPath(
                'payments.0.payment_method',
                'cash'
            )
            ->assertJsonPath(
                'payments.0.reference',
                'TEST-001'
            )
            ->assertJsonPath(
                'payments.0.cash_receiver_name',
                'Test Receiver'
            )
            ->assertJsonPath(
                'payments.0.tenant.id',
                $context['tenant']->id
            )
            ->assertJsonPath(
                'payments.0.tenant.name',
                'Payment Report Tenant'
            )
            ->assertJsonPath(
                'payments.0.lease.id',
                $context['lease']->id
            )
            ->assertJsonPath(
                'payments.0.building.id',
                $context['building']->id
            )
            ->assertJsonPath(
                'payments.0.unit.id',
                $context['unit']->id
            )
            ->assertJsonPath(
                'payments.0.receipt_endpoint',
                '/api/payments/'
                .$context['payment']->id
                .'/receipt'
            );
    }

    public function test_payment_report_filters_by_date_and_payment_method(): void
    {
        $this->createPaymentReportFixture();

        $this->authenticateApiUser();

        $this
            ->getJson(
                '/api/reports/payments'
                .'?from=2026-08-01'
                .'&to=2026-08-31'
                .'&payment_method=cash'
            )
            ->assertOk()
            ->assertJsonPath(
                'summary.payment_count',
                1
            )
            ->assertJsonPath(
                'summary.total_received',
                5000
            );

        $this
            ->getJson(
                '/api/reports/payments'
                .'?payment_method=bank_transfer'
            )
            ->assertOk()
            ->assertJsonPath(
                'summary.payment_count',
                0
            )
            ->assertJsonPath(
                'summary.total_received',
                0
            );
    }

    public function test_payment_report_filters_by_tenant(): void
    {
        $context =
            $this->createPaymentReportFixture();

        $this->authenticateApiUser();

        $this
            ->getJson(
                '/api/reports/payments'
                .'?tenant_id='
                .$context['tenant']->id
            )
            ->assertOk()
            ->assertJsonPath(
                'summary.payment_count',
                1
            );

        $this
            ->getJson(
                '/api/reports/payments'
                .'?tenant_id=999999'
            )
            ->assertUnprocessable();
    }

    public function test_payment_report_filters_by_lease(): void
    {
        $context =
            $this->createPaymentReportFixture();

        $this->authenticateApiUser();

        $this
            ->getJson(
                '/api/reports/payments'
                .'?lease_id='
                .$context['lease']->id
            )
            ->assertOk()
            ->assertJsonPath(
                'summary.payment_count',
                1
            );
    }

    public function test_payment_report_filters_by_building(): void
    {
        $context =
            $this->createPaymentReportFixture();

        $this->authenticateApiUser();

        $this
            ->getJson(
                '/api/reports/payments'
                .'?building_id='
                .$context['building']->id
            )
            ->assertOk()
            ->assertJsonPath(
                'summary.payment_count',
                1
            );
    }

    public function test_payment_report_filters_by_unit(): void
    {
        $context =
            $this->createPaymentReportFixture();

        $this->authenticateApiUser();

        $this
            ->getJson(
                '/api/reports/payments'
                .'?unit_id='
                .$context['unit']->id
            )
            ->assertOk()
            ->assertJsonPath(
                'summary.payment_count',
                1
            );
    }

    public function test_payment_report_filters_by_cash_receiver(): void
    {
        $this->createPaymentReportFixture();

        $this->authenticateApiUser();

        $this
            ->getJson(
                '/api/reports/payments'
                .'?cash_receiver=Test%20Receiver'
            )
            ->assertOk()
            ->assertJsonPath(
                'summary.payment_count',
                1
            );

        $this
            ->getJson(
                '/api/reports/payments'
                .'?cash_receiver=Someone%20Else'
            )
            ->assertOk()
            ->assertJsonPath(
                'summary.payment_count',
                0
            );
    }

    public function test_payment_report_filters_by_reference_or_payment_number(): void
    {
        $context =
            $this->createPaymentReportFixture();

        $this->authenticateApiUser();

        $this
            ->getJson(
                '/api/reports/payments'
                .'?reference=TEST-001'
            )
            ->assertOk()
            ->assertJsonPath(
                'summary.payment_count',
                1
            );

        $this
            ->getJson(
                '/api/reports/payments'
                .'?reference='
                .$context['payment']->id
            )
            ->assertOk()
            ->assertJsonPath(
                'summary.payment_count',
                1
            );

        $this
            ->getJson(
                '/api/reports/payments'
                .'?reference=DOES-NOT-EXIST'
            )
            ->assertOk()
            ->assertJsonPath(
                'summary.payment_count',
                0
            );
    }

    public function test_payment_report_rejects_reversed_date_range(): void
    {
        $this->createPaymentReportFixture();

        $this->authenticateApiUser();

        $this
            ->getJson(
                '/api/reports/payments'
                .'?from=2026-08-31'
                .'&to=2026-08-01'
            )
            ->assertUnprocessable();
    }

    /**
     * Build a minimum, valid Tenant Payment reporting context.
     *
     * @return array{
     *     building: Building,
     *     unit: Unit,
     *     tenant: Party,
     *     lease: Lease,
     *     payment: Payment
     * }
     */
    private function createPaymentReportFixture(): array
    {
        $building =
            Building::create([
                'name' => 'Payment Report Building',
            ]);

        $unit =
            Unit::create([
                'building_id' => $building->id,

                'name' => 'Payment Report Unit',
            ]);

        $tenant =
            Party::create([
                'type' => 'person',

                'name' => 'Payment Report Tenant',

                'phone' => '0200097001',

                'email' => 'payment-report-tenant@example.test',
            ]);

        PartyRole::create([
            'party_id' => $tenant->id,

            'role' => 'tenant',
        ]);

        $lease =
            Lease::create([
                'unit_id' => $unit->id,

                'tenant_id' => $tenant->id,

                'start_date' => '2026-01-01',

                'rent_amount' => 5000,

                'status' => 'active',
            ]);

        $payment =
            Payment::create([
                'lease_id' => $lease->id,

                'amount' => 5000,

                'payment_date' => '2026-08-15',

                'payment_method' => 'cash',

                'cash_receiver_name' => 'Test Receiver',

                'reference' => 'TEST-001',
            ]);

        return compact(
            'building',
            'unit',
            'tenant',
            'lease',
            'payment'
        );
    }
}
