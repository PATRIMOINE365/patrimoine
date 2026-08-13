<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Lease;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Generates tenant rent Invoices from Lease billing terms.
 *
 * Patrimoine V1 billing rules:
 * - billing frequency may be monthly, quarterly, bi-yearly or yearly;
 * - the Lease due day controls Invoice due dates;
 * - due_day defaults to the Lease start-date day when not overridden;
 * - VAT rate is snapshotted onto each Invoice at generation time;
 * - proration may be automatic or explicitly overridden, including zero;
 * - duplicate Invoices for the same Lease billing period are forbidden;
 * - all monetary values remain whole currency units.
 */
class InvoiceGenerationService
{
    /**
     * Generate one Invoice for a specific billing period.
     *
     * @param Lease $lease
     * @param Carbon $periodStart
     *
     * @throws RuntimeException
     */
    public function generate(
        Lease $lease,
        Carbon $periodStart
    ): Invoice {
        return DB::transaction(function () use (
            $lease,
            $periodStart
        ): Invoice {
            $lease = Lease::query()
                ->lockForUpdate()
                ->findOrFail($lease->id);

            if (! in_array($lease->status, ['active', 'notice'], true)) {
                throw new RuntimeException(
                    'Invoices can only be generated for active or notice Leases.'
                );
            }

            $periodStart = $periodStart
                ->copy()
                ->startOfDay();

            $periodEnd = $this->periodEnd(
                $lease,
                $periodStart
            );

            /*
             * Do not generate billing before the Lease begins.
             */
            if ($periodEnd->lt($lease->start_date)) {
                throw new RuntimeException(
                    'Billing period falls before the Lease start date.'
                );
            }

            /*
             * Do not generate a period beginning after the contractual end.
             */
            if (
                $lease->end_date !== null
                && $periodStart->gt($lease->end_date)
            ) {
                throw new RuntimeException(
                    'Billing period falls after the Lease end date.'
                );
            }

            /*
             * The same Lease/period must never be invoiced twice.
             */
            $duplicateExists = Invoice::query()
                ->where('lease_id', $lease->id)
                ->whereDate(
                    'period_start',
                    $periodStart->toDateString()
                )
                ->whereDate(
                    'period_end',
                    $periodEnd->toDateString()
                )
                ->exists();

            if ($duplicateExists) {
                throw new RuntimeException(
                    'An Invoice already exists for this Lease billing period.'
                );
            }

            $baseAmount = $this->periodRentAmount($lease);

            $prorationAmount = $this->prorationAmount(
                $lease,
                $periodStart,
                $periodEnd,
                $baseAmount
            );

            $grossAmount = $prorationAmount ?? $baseAmount;

            /*
             * VAT-inclusive billing:
             *
             * gross = net + VAT
             *
             * The stored VAT rate is snapshotted from the Lease so future
             * rate changes do not alter historical Invoices.
             */
            [$netAmount, $vatAmount] = $this->splitVatInclusiveAmount(
                $grossAmount,
                (float) $lease->vat_rate
            );

            $dueDate = $this->dueDate(
                $lease,
                $periodStart
            );

            $invoice = Invoice::create([
                'lease_id' => $lease->id,

                /*
                * Periodic Lease billing always represents contractual rent.
                */
                'type' => 'rent',

                'invoice_number' =>
                    $this->nextInvoiceNumber(),
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'issue_date' => $periodStart->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'status' => 'issued',
                'total_amount' => $grossAmount,
                'vat_rate' => $lease->vat_rate,
                'net_amount' => $netAmount,
                'vat_amount' => $vatAmount,
                'proration_amount' => $prorationAmount,
                'notes' => null,
            ]);

            return $invoice->refresh();
        });
    }

    /**
     * Generate all billing periods due up to a supplied date.
     *
     * Existing periods are skipped rather than treated as an error.
     *
     * @return array<int, Invoice>
     */
    public function generateDueInvoices(
        Lease $lease,
        Carbon $throughDate
    ): array {
        $lease->refresh();

        if (! in_array($lease->status, ['active', 'notice'], true)) {
            return [];
        }

        $periodStart = $lease->start_date
            ->copy()
            ->startOfDay();

        $throughDate = $throughDate
            ->copy()
            ->startOfDay();

        $generated = [];

        while ($periodStart->lte($throughDate)) {
            if (
                $lease->end_date !== null
                && $periodStart->gt($lease->end_date)
            ) {
                break;
            }

            $periodEnd = $this->periodEnd(
                $lease,
                $periodStart
            );

            $exists = Invoice::query()
                ->where('lease_id', $lease->id)
                ->whereDate(
                    'period_start',
                    $periodStart->toDateString()
                )
                ->whereDate(
                    'period_end',
                    $periodEnd->toDateString()
                )
                ->exists();

            if (! $exists) {
                $generated[] = $this->generate(
                    $lease,
                    $periodStart
                );
            }

            $periodStart = $this->nextPeriodStart(
                $lease,
                $periodStart
            );
        }

        return $generated;
    }

    /**
     * Calculate the end date for a billing period.
     */
    private function periodEnd(
        Lease $lease,
        Carbon $periodStart
    ): Carbon {
        return match ($lease->payment_frequency) {
            'monthly' =>
                $periodStart->copy()->addMonthNoOverflow()->subDay(),

            'quarterly' =>
                $periodStart->copy()->addMonthsNoOverflow(3)->subDay(),

            'bi_yearly' =>
                $periodStart->copy()->addMonthsNoOverflow(6)->subDay(),

            'yearly' =>
                $periodStart->copy()->addYearNoOverflow()->subDay(),

            default =>
                throw new RuntimeException(
                    'Unsupported Lease payment frequency.'
                ),
        };
    }

    /**
     * Return the first day of the next billing period.
     */
    private function nextPeriodStart(
        Lease $lease,
        Carbon $periodStart
    ): Carbon {
        return match ($lease->payment_frequency) {
            'monthly' =>
                $periodStart->copy()->addMonthNoOverflow(),

            'quarterly' =>
                $periodStart->copy()->addMonthsNoOverflow(3),

            'bi_yearly' =>
                $periodStart->copy()->addMonthsNoOverflow(6),

            'yearly' =>
                $periodStart->copy()->addYearNoOverflow(),

            default =>
                throw new RuntimeException(
                    'Unsupported Lease payment frequency.'
                ),
        };
    }

    /**
     * Convert monthly Lease rent into the amount for one billing period.
     */
    private function periodRentAmount(Lease $lease): int
    {
        $multiplier = match ($lease->payment_frequency) {
            'monthly' => 1,
            'quarterly' => 3,
            'bi_yearly' => 6,
            'yearly' => 12,

            default =>
                throw new RuntimeException(
                    'Unsupported Lease payment frequency.'
                ),
        };

        return $lease->rent_amount * $multiplier;
    }

    /**
     * Determine the Invoice due date.
     *
     * If due_day is NULL, the Lease start-date day is used.
     *
     * For months shorter than the requested day, the last valid day of
     * that month is used.
     */
    private function dueDate(
        Lease $lease,
        Carbon $periodStart
    ): Carbon {
        $dueDay = $lease->due_day
            ?? $lease->start_date->day;

        $dueDate = $periodStart
            ->copy()
            ->startOfMonth();

        $day = min(
            $dueDay,
            $dueDate->daysInMonth
        );

        return $dueDate->day($day);
    }

    /**
     * Calculate first-period proration.
     *
     * Rules:
     * - explicit Lease proration_amount always wins, including zero;
     * - automatic proration applies only to the first billing period when
     *   the Lease begins after the billing period start;
     * - later periods use the full billing-period rent.
     *
     * Returns NULL where no proration should be stored.
     */
    private function prorationAmount(
        Lease $lease,
        Carbon $periodStart,
        Carbon $periodEnd,
        int $baseAmount
    ): ?int {
        /*
         * Explicit override, including zero.
         */
        if ($lease->proration_amount !== null) {
            if (
                $periodStart->isSameDay(
                    $lease->start_date->copy()->startOfDay()
                )
            ) {
                return (int) $lease->proration_amount;
            }

            return null;
        }

        /*
         * No automatic proration is required when the billing period starts
         * on the Lease start date.
         */
        if (
            $periodStart->isSameDay(
                $lease->start_date->copy()->startOfDay()
            )
        ) {
            return null;
        }

        /*
         * Automatic proration applies only where the Lease starts inside
         * the requested first period.
         */
        if (
            ! $lease->start_date->betweenIncluded(
                $periodStart,
                $periodEnd
            )
        ) {
            return null;
        }

        $totalDays = $periodStart
            ->diffInDays($periodEnd)
            + 1;

        $occupiedDays = $lease->start_date
            ->copy()
            ->startOfDay()
            ->diffInDays($periodEnd)
            + 1;

        return (int) round(
            $baseAmount
            * $occupiedDays
            / $totalDays
        );
    }

    /**
     * Split a VAT-inclusive amount into net and VAT portions.
     *
     * Integer rounding is deliberate because Patrimoine stores whole
     * currency units only.
     *
     * @return array{0:int,1:int}
     */
    private function splitVatInclusiveAmount(
        int $grossAmount,
        float $vatRate
    ): array {
        if ($vatRate <= 0) {
            return [
                $grossAmount,
                0,
            ];
        }

        $netAmount = (int) round(
            $grossAmount
            / (1 + ($vatRate / 100))
        );

        $vatAmount = $grossAmount - $netAmount;

        return [
            $netAmount,
            $vatAmount,
        ];
    }

    /**
     * Generate the next human-readable Invoice number.
     *
     * The database ID remains the true unique identifier. This number is
     * designed for customer-facing documents and may later be replaced by
     * a configurable numbering policy.
     */
    private function nextInvoiceNumber(): string
    {
        $nextId = ((int) Invoice::query()->max('id')) + 1;

        return sprintf(
            'INV-%06d',
            $nextId
        );
    }
}
