<?php

namespace App\Services\Reports;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;

/**
 * Read-only tenant Payment report.
 *
 * This report deliberately contains tenant Payments only.
 * Owner deposits remain part of the owner-accounting domain and are not
 * combined with tenant Payments as they were in the legacy Payments
 * operational workspace.
 */
class PaymentReportService
{
    /**
     * Generate the filtered Payment report.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function generate(array $filters = []): array
    {
        $query = Payment::query()
            ->with([
                'lease.tenant',
                'lease.unit.building',
            ]);

        $this->applyFilters(
            $query,
            $filters
        );

        $payments = $query
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->get();

        return [
            'filters' => $filters,

            'summary' => [
                'payment_count' => $payments->count(),
                'total_received' => (int) $payments->sum('amount'),
            ],

            'payments' => $payments
                ->map(
                    fn (Payment $payment): array => $this->serializePayment($payment)
                )
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  Builder<Payment>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(
        Builder $query,
        array $filters
    ): void {
        if (! empty($filters['from'])) {
            $query->whereDate(
                'payment_date',
                '>=',
                $filters['from']
            );
        }

        if (! empty($filters['to'])) {
            $query->whereDate(
                'payment_date',
                '<=',
                $filters['to']
            );
        }

        if (! empty($filters['tenant_id'])) {
            $tenantId = (int) $filters['tenant_id'];

            $query->whereHas(
                'lease',
                fn (Builder $leaseQuery) => $leaseQuery->where(
                    'tenant_id',
                    $tenantId
                )
            );
        }

        if (! empty($filters['lease_id'])) {
            $query->where(
                'lease_id',
                (int) $filters['lease_id']
            );
        }

        if (! empty($filters['building_id'])) {
            $buildingId = (int) $filters['building_id'];

            $query->whereHas(
                'lease.unit',
                fn (Builder $unitQuery) => $unitQuery->where(
                    'building_id',
                    $buildingId
                )
            );
        }

        if (! empty($filters['unit_id'])) {
            $unitId = (int) $filters['unit_id'];

            $query->whereHas(
                'lease',
                fn (Builder $leaseQuery) => $leaseQuery->where(
                    'unit_id',
                    $unitId
                )
            );
        }

        if (! empty($filters['payment_method'])) {
            $query->where(
                'payment_method',
                $filters['payment_method']
            );
        }

        if (! empty($filters['cash_receiver'])) {
            $cashReceiver =
                trim(
                    (string) $filters['cash_receiver']
                );

            $query->where(
                function (Builder $receiverQuery) use ($cashReceiver): void {
                    $receiverQuery
                        ->where(
                            'cash_receiver_name',
                            'like',
                            '%'.$cashReceiver.'%'
                        )
                        ->orWhere(
                            function (Builder $legacyQuery) use ($cashReceiver): void {
                                $legacyQuery
                                    ->whereNull('cash_receiver_name')
                                    ->where(
                                        'collector_name',
                                        'like',
                                        '%'.$cashReceiver.'%'
                                    );
                            }
                        );
                }
            );
        }

        if (! empty($filters['reference'])) {
            $reference =
                trim(
                    (string) $filters['reference']
                );

            $query->where(
                function (Builder $referenceQuery) use ($reference): void {
                    $referenceQuery
                        ->where(
                            'reference',
                            'like',
                            '%'.$reference.'%'
                        )
                        ->orWhere(
                            'id',
                            ctype_digit($reference)
                                ? (int) $reference
                                : -1
                        );
                }
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function serializePayment(
        Payment $payment
    ): array {
        $lease = $payment->lease;
        $tenant = $lease?->tenant;
        $unit = $lease?->unit;
        $building = $unit?->building;

        return [
            'id' => $payment->id,

            /*
             * V1.0.50: the receipt number, which is the organisation's
             * own, rather than PAY- and the installation-wide id.
             */
            'payment_number' => $payment->receiptNumber(),

            'payment_date' => $payment->payment_date?->toDateString(),

            'amount' => (int) $payment->amount,

            'payment_method' => $payment->payment_method,

            'reference' => $payment->reference,

            'cash_receiver_name' => $payment->cash_receiver_name
                ?? $payment->collector_name,

            'tenant' => [
                'id' => $tenant?->id,

                'name' => $tenant?->name
                    ?? $tenant?->legal_name,
            ],

            'lease' => [
                'id' => $lease?->id,
            ],

            'building' => [
                'id' => $building?->id,
                'name' => $building?->name,
            ],

            'unit' => [
                'id' => $unit?->id,
                'name' => $unit?->name,
            ],

            'receipt_endpoint' => '/api/payments/'
                .$payment->id
                .'/receipt',
        ];
    }
}
