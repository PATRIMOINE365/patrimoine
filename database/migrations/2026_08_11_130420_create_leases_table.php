<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the leases table.
 *
 * A lease represents the contractual relationship between exactly one
 * tenant and exactly one unit.
 *
 * Important financial design rule:
 * This table stores contractual terms and configuration only.
 * Actual money received, rent reserve balances, security deposit
 * movements, invoices, receipts and owner accounting will be recorded
 * separately in the financial transaction layer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leases', function (Blueprint $table) {
            $table->id();

            /*
             * Every lease covers exactly one Unit.
             *
             * A Unit may have multiple historical leases, but Patrimoine
             * will later prevent overlapping active leases through
             * application-level validation.
             */
            $table->foreignId('unit_id')
                ->constrained()
                ->restrictOnDelete();

            /*
             * Tenant is a Party.
             *
             * Patrimoine 1.0 allows exactly one tenant per lease.
             * The Party should also carry the "tenant" PartyRole.
             */
            $table->foreignId('tenant_id')
                ->constrained('parties')
                ->restrictOnDelete();

            /*
             * Optional Agent associated with the lease transaction.
             *
             * The Party should carry the "agent" PartyRole.
             */
            $table->foreignId('agent_id')
                ->nullable()
                ->constrained('parties')
                ->nullOnDelete();

            /*
             * Contract period.
             *
             * end_date is nullable to support leases without a fixed
             * termination date.
             */
            $table->date('start_date');
            $table->date('end_date')->nullable();

            /*
             * Current lease lifecycle state.
             *
             * draft      = prepared but not yet active
             * active     = currently in force
             * notice     = termination notice has been recorded
             * terminated = lease has ended
             */
            $table->enum('status', [
                'draft',
                'active',
                'notice',
                'terminated',
            ])->default('draft');

            /*
             * Date on which termination notice was received or issued.
             *
             * Rent Reserve consumption will eventually begin according
             * to the termination workflow rather than simply because
             * reserve funds exist.
             */
            $table->date('termination_notice_date')->nullable();

            /*
             * Contractual rent amount for one billing period.
             *
             * Patrimoine stores monetary amounts as whole currency units
             * with no decimal values.
             */
            $table->unsignedBigInteger('rent_amount');

            /*
             * Determines how frequently rent is invoiced.
             */
            $table->enum('payment_frequency', [
                'monthly',
                'quarterly',
                'bi_yearly',
                'yearly',
            ])->default('monthly');

            /*
             * Optional override for the day rent becomes due.
             *
             * When NULL, the due day is derived from start_date.
             *
             * Example:
             * start_date = 15 August -> due on the 15th.
             * due_day = 1            -> override and invoice on the 1st.
             */
            $table->unsignedTinyInteger('due_day')->nullable();

            /*
             * VAT is included in configured monetary amounts.
             *
             * Default Patrimoine rate is 18%, but individual leases may
             * override it, including setting VAT to 0%.
             */
            $table->decimal('vat_rate', 5, 2)->default(18.00);

            /*
             * Proration controls the amount charged for an initial or
             * final partial billing period.
             *
             * NULL means Patrimoine should calculate the prorated amount.
             * Any explicit value, including 0, overrides that calculation.
             */
            $table->unsignedBigInteger('proration_amount')->nullable();

            /*
             * Contractual security deposit requirement.
             *
             * The actual receipt, deductions and eventual refund will
             * be represented by financial transactions later.
             */
            $table->unsignedBigInteger('security_deposit_amount')
                ->default(0);

            /*
             * Management fee configuration.
             *
             * "none"       = no management fee
             * "percentage" = management_fee_value represents a %
             * "fixed"      = management_fee_value represents a currency
             *                amount for the applicable accounting period
             *
             * Actual fee recognition/deduction will happen in the
             * financial/accounting layer.
             */
            $table->enum('management_fee_type', [
                'none',
                'percentage',
                'fixed',
            ])->default('none');

            $table->decimal('management_fee_value', 12, 2)
                ->default(0);

            /*
             * One-time commission agreed with the Agent at lease creation.
             *
             * This is the contractual amount. Its deduction from owner
             * funds will later be represented by the owner ledger.
             */
            $table->unsignedBigInteger('agent_commission_amount')
                ->default(0);

            $table->text('notes')->nullable();

            $table->timestamps();

            /*
             * Common lookup paths for occupancy, tenant history,
             * billing and reporting.
             */
            $table->index(['unit_id', 'status']);
            $table->index(['tenant_id', 'status']);
            $table->index('start_date');
            $table->index('end_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leases');
    }
};
