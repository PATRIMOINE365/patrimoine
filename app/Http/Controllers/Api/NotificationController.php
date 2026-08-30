<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * V1.0.7 notification center.
 *
 * Patrimoine notifications are DERIVED from operational data on demand —
 * there is no stored notification queue to drift out of sync. The bell
 * shows what needs attention right now:
 *
 * - overdue rent (count + total outstanding)
 * - invoices becoming due within 7 days
 * - unpaid tenant expense invoices and owner expense bills (V1.0.8)
 * - leases expiring within 90 days
 * - scheduled rent increments effective within 60 days
 * - the release announcement (existing last_seen_release mechanism)
 */
class NotificationController extends Controller
{
    public function index(
        Request $request,
        DashboardService $dashboard
    ): JsonResponse {
        $asOf = now();

        $overdue = $dashboard->overdueInvoices($asOf);
        $upcoming = $dashboard->upcomingInvoices($asOf, 7);
        $expiring = $dashboard->expiringLeases($asOf, 90);
        $increments = $dashboard->upcomingIncrements($asOf, 60);

        $notifications = [];

        if ($overdue->isNotEmpty()) {
            $notifications[] = [
                'kind' => 'rent_overdue',
                'severity' => 'danger',
                'count' => $overdue->count(),
                'amount' => $overdue->sum(
                    fn ($invoice): int => $invoice->outstandingAmount()
                ),
            ];
        }

        if ($upcoming->isNotEmpty()) {
            $notifications[] = [
                'kind' => 'rent_due_soon',
                'severity' => 'warning',
                'count' => $upcoming->count(),
                'amount' => $upcoming->sum(
                    fn ($invoice): int => $invoice->outstandingAmount()
                ),
            ];
        }

        /*
         * V1.0.8: expenses stopped settling themselves — surface every
         * open expense receivable so it gets paid through the Pay flow.
         */
        $unpaidExpenses = $dashboard->unpaidExpenseInvoices();

        if ($unpaidExpenses->isNotEmpty()) {
            $notifications[] = [
                'kind' => 'expenses_unpaid',
                'severity' => 'warning',
                'count' => $unpaidExpenses->count(),
                'amount' => $unpaidExpenses->sum(
                    fn ($invoice): int => $invoice->outstandingAmount()
                ),
            ];
        }

        $unpaidBills = $dashboard->unpaidOwnerExpenseBills();

        if ($unpaidBills->isNotEmpty()) {
            $notifications[] = [
                'kind' => 'owner_bills_unpaid',
                'severity' => 'warning',
                'count' => $unpaidBills->count(),
                'amount' => $unpaidBills->sum(
                    fn ($bill): int => $bill->outstandingAmount()
                ),
            ];
        }

        /*
         * V1.0.35: money taken but not yet filed.
         *
         * A payment settles invoices oldest first; anything left over
         * waits to be classified into a tenant fund. Until it is, the
         * tenant is ahead and does not look it, and nothing anywhere
         * showed the money. This is what chases it.
         */
        $unclassified = $dashboard->unclassifiedPayments();

        if ($unclassified->isNotEmpty()) {
            $notifications[] = [
                'kind' => 'payments_unclassified',
                'severity' => 'warning',
                'count' => $unclassified->count(),
                'amount' => $unclassified->sum(
                    fn ($payment): int => $dashboard->remainingUnclassified($payment)
                ),
            ];
        }

        if ($expiring->isNotEmpty()) {
            $notifications[] = [
                'kind' => 'leases_expiring',
                'severity' => 'info',
                'count' => $expiring->count(),
            ];
        }

        if ($increments->isNotEmpty()) {
            $notifications[] = [
                'kind' => 'increments_upcoming',
                'severity' => 'info',
                'count' => $increments->count(),
            ];
        }

        /*
         * The release announcement keeps its dedicated read-state flow
         * (POST /api/auth/release-notification/read); it is exposed here
         * so the bell can show one consolidated unread badge.
         */
        $release = (string) config('patrimoine.release');

        $notifications[] = [
            'kind' => 'release_notes',
            'severity' => 'info',
            'release' => $release,
            'unread' => $request->user()?->last_seen_release !== $release,
        ];

        return response()->json([
            'as_of' => $asOf->toDateString(),
            'notifications' => $notifications,
        ]);
    }
}
