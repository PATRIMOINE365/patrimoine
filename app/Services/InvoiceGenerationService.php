<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Lease;
use App\Services\Accounting\RentInvoiceJournalService;
use App\Services\Documents\DocumentNumberService;
use App\Services\LeaseTerms\LeaseBillingTermsResolver;
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
    public function __construct(
        private readonly RentInvoiceJournalService $rentInvoiceJournal,
        private readonly LeaseBillingTermsResolver $billingTerms,
        private readonly DocumentNumberService $numbers
    ) {}

    /**
     * Generate one Invoice for a specific billing period.
     *
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

            /*
             * V1.0.5 Phase 8D:
             * Billing must use the contractual terms that applied to this
             * billing period. Extending a Lease must never rewrite the
             * financial meaning of an earlier period.
             */
            $billingLease = $this->billingTerms->resolve(
                $lease,
                $periodStart
            );

            $periodEnd = $this->periodEnd(
                $billingLease,
                $periodStart
            );

            /*
             * V1.0.5 Phase 9B:
             * A Lease under controlled termination has a hard billing
             * boundary. No rent period may begin after the Termination Date.
             */
            if (
                $lease->status === 'notice'
                && $lease->termination_date !== null
                && $periodStart->gt($lease->termination_date)
            ) {
                throw new RuntimeException(
                    'Billing period falls after the Lease termination date.'
                );
            }

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
                $billingLease->end_date !== null
                && $periodStart->gt($billingLease->end_date)
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
                ->where('status', '!=', 'cancelled')
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

            $baseAmount = $this->periodRentAmount(
                $billingLease
            );

            $prorationAmount = $this->prorationAmount(
                $billingLease,
                $periodStart,
                $periodEnd,
                $baseAmount
            );

            $grossAmount = $prorationAmount ?? $baseAmount;

            /*
             * V1.0.5 Phase 9B:
             * If this billing period contains the controlled Termination
             * Date, apply the frozen final-rent rule.
             *
             * full:
             *   charge the complete contractual billing period.
             *
             * none:
             *   preserve an operational zero-value Invoice for the final
             *   period. Zero-value rent Invoices do not create Journal
             *   movements.
             *
             * prorate:
             *   charge only the inclusive portion of this billing period
             *   through the Termination Date.
             */
            if (
                $lease->status === 'notice'
                && $lease->termination_date !== null
                && $periodStart->lte($lease->termination_date)
                && $periodEnd->gte($lease->termination_date)
            ) {
                $grossAmount = match (
                    $lease->termination_final_rent_mode
                ) {
                    'full' => $baseAmount,

                    'none' => 0,

                    'prorate' => $this->terminationProrationAmount(
                        $baseAmount,
                        $periodStart,
                        $periodEnd,
                        $lease->termination_date
                    ),

                    default => throw new RuntimeException(
                        'Lease termination final rent mode is invalid.'
                    ),
                };

                $prorationAmount =
                    $lease->termination_final_rent_mode === 'full'
                        ? null
                        : $grossAmount;
            }

            /*
             * Rent carries no VAT.
             *
             * VAT is charged on the managing organisation's fee and billed
             * to the Owner (see OwnerAccountingService::postManagementFee),
             * so the tenant is billed the contractual rent and nothing more.
             * The Lease VAT rate is the fee rate and deliberately does not
             * reach the tenant Invoice.
             *
             * Invoices issued before this change keep the VAT they were
             * posted with: they are frozen accounting records.
             */
            $netAmount = $grossAmount;
            $vatAmount = 0;

            $dueDate = $this->dueDate(
                $billingLease,
                $periodStart
            );

            $invoice = Invoice::create([
                'lease_id' => $lease->id,

                /*
                * Periodic Lease billing always represents contractual rent.
                */
                'type' => 'rent',

                'invoice_number' => $this->nextInvoiceNumber(),
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'issue_date' => $periodStart->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'status' => 'issued',
                'total_amount' => $grossAmount,
                'vat_rate' => 0,
                'net_amount' => $netAmount,
                'vat_amount' => $vatAmount,
                'proration_amount' => $prorationAmount,
                'notes' => null,
            ]);

            /*
             * V1.0.5 Phase 3:
             * Mirror only newly-created post-cutover rent Invoices into the
             * immutable Financial Journal. Before cutover this is a no-op.
             *
             * This call remains inside the Invoice database transaction so
             * Invoice creation and Journal posting succeed or roll back
             * together.
             */
            $this->rentInvoiceJournal->post(
                $invoice
            );

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
            $billingLease = $this->billingTerms->resolve(
                $lease,
                $periodStart
            );

            if (
                $billingLease->end_date !== null
                && $periodStart->gt($billingLease->end_date)
            ) {
                break;
            }

            if (
                $lease->status === 'notice'
                && $lease->termination_date !== null
                && $periodStart->gt($lease->termination_date)
            ) {
                break;
            }

            $periodEnd = $this->periodEnd(
                $billingLease,
                $periodStart
            );

            $exists = Invoice::query()
                ->where('lease_id', $lease->id)
                ->where('status', '!=', 'cancelled')
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
                $billingLease,
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
            'monthly' => $periodStart->copy()->addMonthNoOverflow()->subDay(),

            'quarterly' => $periodStart->copy()->addMonthsNoOverflow(3)->subDay(),

            'bi_yearly' => $periodStart->copy()->addMonthsNoOverflow(6)->subDay(),

            'yearly' => $periodStart->copy()->addYearNoOverflow()->subDay(),

            default => throw new RuntimeException(
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
            'monthly' => $periodStart->copy()->addMonthNoOverflow(),

            'quarterly' => $periodStart->copy()->addMonthsNoOverflow(3),

            'bi_yearly' => $periodStart->copy()->addMonthsNoOverflow(6),

            'yearly' => $periodStart->copy()->addYearNoOverflow(),

            default => throw new RuntimeException(
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

            default => throw new RuntimeException(
                'Unsupported Lease payment frequency.'
            ),
        };

        return $lease->rent_amount * $multiplier;
    }

    /**
     * Calculate the inclusive final-period rent through termination.
     *
     * Patrimoine monetary values remain whole currency units.
     */
    private function terminationProrationAmount(
        int $baseAmount,
        Carbon $periodStart,
        Carbon $periodEnd,
        Carbon $terminationDate
    ): int {
        $periodDays =
            $periodStart->diffInDays($periodEnd) + 1;

        $chargeableDays =
            $periodStart->diffInDays($terminationDate) + 1;

        return (int) round(
            $baseAmount
            * ($chargeableDays / $periodDays)
        );
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
     * Generate the next human-readable Invoice number.
     *
     * The database ID remains the true unique identifier. This number is
     * designed for customer-facing documents.
     *
     * V1.0.10 multi-tenancy: numbering is per organisation.
     *
     * V1.0.36: and it comes from a counter rather than from the invoices.
     * Reading the highest existing INV- number had three faults — two
     * generations could read the same one, "highest" was a text sort that
     * held only while every number was six digits wide, and deleting the
     * newest invoice handed its number to the next one written. The
     * counter cannot be affected by anything that happens to an invoice
     * afterwards. INV-2026-000001.
     */
    private function nextInvoiceNumber(): string
    {
        return $this->numbers->next('INV');
    }
}
