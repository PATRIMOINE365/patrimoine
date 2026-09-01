<?php

use App\Models\OwnerPayout;
use App\Services\Documents\OwnerPayoutBreakdownService;
use App\Support\OrganisationContext;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A completed payout stops being a question about today's ledger.
 *
 * The receipt was rebuilt on every render from a date window over the
 * live ledger, so a transaction recorded after a payout but dated before
 * it walked into that payout's receipt. Backdating is legitimate in
 * Patrimoine and cannot be prevented; what has to stop is a completed
 * document changing because of it.
 *
 * The composition is written onto the payout when it is made, and the
 * receipt renders from that. This adds the column and fills it in for
 * the payouts that already exist, using the one piece of evidence the
 * database already holds about what was known at the time: when each row
 * was recorded.
 *
 * That reconstruction is as good as it can be and no better. It recovers
 * what was VISIBLE when the payout was made; it cannot recover what a
 * transaction said then if it has been edited since. From here on the
 * stored statement carries that itself.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'owner_payouts',
            function (Blueprint $table): void {
                /*
                 * The frozen statement: the summary figures and the three
                 * itemised tables, exactly as the receipt shows them.
                 */
                $table->json('statement')
                    ->nullable()
                    ->after('notes');

                /*
                 * When the composition was frozen. Distinct from
                 * created_at so a backfilled statement is visibly a
                 * reconstruction rather than a contemporaneous record.
                 */
                $table->timestamp('statement_frozen_at')
                    ->nullable()
                    ->after('statement');
            }
        );

        $breakdown = app(OwnerPayoutBreakdownService::class);

        OwnerPayout::withoutGlobalScopes()
            ->whereNull('statement')
            ->orderBy('id')
            ->chunkById(100, function ($payouts) use ($breakdown): void {
                foreach ($payouts as $payout) {
                    /*
                     * The breakdown reads the owner and the account
                     * through relations that are organisation-scoped, so
                     * the payout's own organisation is bound around it.
                     */
                    try {
                        OrganisationContext::runAs(
                            (int) $payout->organisation_id,
                            function () use ($breakdown, $payout): void {
                                $statement = $breakdown->compose($payout);

                                if ($statement === null) {
                                    return;
                                }

                                $payout->forceFill([
                                    'statement' => $statement,
                                    'statement_frozen_at' => null,
                                ])->saveQuietly();
                            }
                        );
                    } catch (\Throwable) {
                        /*
                         * A payout whose account or owner has since gone
                         * cannot be reconstructed. It keeps a null
                         * statement and the receipt falls back to
                         * composing one, exactly as it does today.
                         */
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table(
            'owner_payouts',
            function (Blueprint $table): void {
                $table->dropColumn([
                    'statement',
                    'statement_frozen_at',
                ]);
            }
        );
    }
};
