<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OwnerTransaction;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Provides the unified incoming-payment register used by the
 * Patrimoine Payments workspace.
 *
 * Patrimoine has two distinct sources of incoming money:
 *
 * - tenant Payments recorded against Leases;
 * - owner deposits recorded in the owner ledger.
 *
 * These remain separate accounting domains internally. This controller
 * only normalizes them into one read-only operational register for the
 * browser interface.
 */
class PaymentRegisterController extends Controller
{
    /**
     * Return current-month payment summary and a paginated register of
     * incoming tenant and owner money.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source' => [
                'nullable',
                Rule::in([
                    'tenant',
                    'owner',
                ]),
            ],

            'payment_method' => [
                'nullable',
                Rule::in([
                    'cash',
                    'bank_transfer',
                    'momo',
                ]),
            ],

            'from' => [
                'nullable',
                'date',
            ],

            'to' => [
                'nullable',
                'date',
                'after_or_equal:from',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'between:1,100',
            ],
        ]);

        $tenantQuery =
            $this->tenantPaymentQuery(
                paymentMethod: $validated['payment_method']
                    ?? null,

                from: $validated['from']
                    ?? null,

                to: $validated['to']
                    ?? null
            );

        $ownerQuery =
            $this->ownerDepositQuery(
                paymentMethod: $validated['payment_method']
                    ?? null,

                from: $validated['from']
                    ?? null,

                to: $validated['to']
                    ?? null
            );

        $source =
            $validated['source']
            ?? null;

        /*
         * Keep the underlying tenant and owner accounting tables separate.
         *
         * UNION ALL is used only for this read model. No financial data is
         * copied or duplicated.
         */
        if ($source === 'tenant') {
            $registerQuery =
                $tenantQuery;
        } elseif ($source === 'owner') {
            $registerQuery =
                $ownerQuery;
        } else {
            $registerQuery =
                $tenantQuery->unionAll(
                    $ownerQuery
                );
        }

        $transactions =
            DB::query()
                ->fromSub(
                    $registerQuery,
                    'payment_register'
                )
                ->orderByDesc(
                    'transaction_date'
                )
                ->orderByDesc(
                    'id'
                )
                ->paginate(
                    perPage: (int) (
                        $validated['per_page']
                        ?? 25
                    )
                );

        return response()->json([
            'summary' => $this->currentMonthSummary(),

            'transactions' => $transactions,
        ]);
    }

    /**
     * Build the normalized tenant-payment portion of the register.
     */
    private function tenantPaymentQuery(
        ?string $paymentMethod = null,
        ?string $from = null,
        ?string $to = null
    ) {
        return DB::table(
            'payments as payments'
        )
            /*
             * V1.1.0 multi-tenancy: raw register queries bypass the
             * Eloquent organisation scope, so the tenant constraint is
             * applied explicitly here.
             */
            ->where(
                'payments.organisation_id',
                \App\Support\OrganisationContext::id()
            )
            ->join(
                'leases as leases',
                'leases.id',
                '=',
                'payments.lease_id'
            )
            ->join(
                'parties as tenants',
                'tenants.id',
                '=',
                'leases.tenant_id'
            )
            ->join(
                'units as units',
                'units.id',
                '=',
                'leases.unit_id'
            )
            ->join(
                'buildings as buildings',
                'buildings.id',
                '=',
                'units.building_id'
            )
            ->when(
                $paymentMethod !== null,
                fn ($query) => $query->where(
                    'payments.payment_method',
                    $paymentMethod
                )
            )
            ->when(
                $from !== null,
                fn ($query) => $query->whereDate(
                    'payments.payment_date',
                    '>=',
                    $from
                )
            )
            ->when(
                $to !== null,
                fn ($query) => $query->whereDate(
                    'payments.payment_date',
                    '<=',
                    $to
                )
            )
            ->select([
                'payments.id as id',

                DB::raw(
                    "'tenant' as source"
                ),

                DB::raw(
                    "'Tenant Payment' as transaction_type"
                ),

                'payments.payment_date as transaction_date',
                'payments.amount as amount',
                'payments.payment_method as payment_method',
                'payments.reference as reference',
                DB::raw('COALESCE(payments.cash_receiver_name, payments.collector_name) as collector_name'),
                'payments.notes as notes',

                DB::raw(
                    'NULL as deposit_purpose'
                ),

                'leases.id as lease_id',
                'buildings.id as building_id',
                'units.id as unit_id',

                DB::raw(
                    "COALESCE(
                        tenants.name,
                        tenants.legal_name,
                        'Tenant'
                    ) as payer_name"
                ),

                'buildings.name as building_name',
                'units.name as unit_name',

                DB::raw(
                    "CONCAT(
                        '/api/payments/',
                        payments.id,
                        '/receipt'
                    ) as receipt_endpoint"
                ),
            ]);
    }

    /**
     * Build the normalized owner-deposit portion of the register.
     */
    private function ownerDepositQuery(
        ?string $paymentMethod = null,
        ?string $from = null,
        ?string $to = null
    ) {
        return DB::table(
            'owner_transactions as owner_transactions'
        )
            /*
             * V1.1.0 multi-tenancy: raw register queries bypass the
             * Eloquent organisation scope, so the tenant constraint is
             * applied explicitly here.
             */
            ->where(
                'owner_transactions.organisation_id',
                \App\Support\OrganisationContext::id()
            )
            ->join(
                'owner_accounts as owner_accounts',
                'owner_accounts.id',
                '=',
                'owner_transactions.owner_account_id'
            )
            ->join(
                'parties as owners',
                'owners.id',
                '=',
                'owner_accounts.party_id'
            )
            ->leftJoin(
                'buildings as buildings',
                'buildings.id',
                '=',
                'owner_transactions.building_id'
            )
            ->leftJoin(
                'units as units',
                'units.id',
                '=',
                'owner_transactions.unit_id'
            )
            ->where(
                'owner_transactions.direction',
                'credit'
            )
            ->where(
                'owner_transactions.category',
                'owner_deposit'
            )
            ->when(
                $paymentMethod !== null,
                fn ($query) => $query->where(
                    'owner_transactions.payment_method',
                    $paymentMethod
                )
            )
            ->when(
                $from !== null,
                fn ($query) => $query->whereDate(
                    'owner_transactions.transaction_date',
                    '>=',
                    $from
                )
            )
            ->when(
                $to !== null,
                fn ($query) => $query->whereDate(
                    'owner_transactions.transaction_date',
                    '<=',
                    $to
                )
            )
            ->select([
                'owner_transactions.id as id',

                DB::raw(
                    "'owner' as source"
                ),

                DB::raw(
                    "'Owner Deposit' as transaction_type"
                ),

                'owner_transactions.transaction_date as transaction_date',
                'owner_transactions.amount as amount',
                'owner_transactions.payment_method as payment_method',
                'owner_transactions.reference as reference',
                DB::raw('COALESCE(owner_transactions.cash_receiver_name, owner_transactions.collector_name) as collector_name'),
                'owner_transactions.notes as notes',
                'owner_transactions.deposit_purpose as deposit_purpose',

                DB::raw(
                    'NULL as lease_id'
                ),

                'owner_transactions.building_id as building_id',
                'owner_transactions.unit_id as unit_id',

                DB::raw(
                    "COALESCE(
                        owners.name,
                        owners.legal_name,
                        'Owner'
                    ) as payer_name"
                ),

                'buildings.name as building_name',
                'units.name as unit_name',

                DB::raw(
                    "CONCAT(
                        '/api/owner-deposits/',
                        owner_transactions.id,
                        '/receipt'
                    ) as receipt_endpoint"
                ),
            ]);
    }

    /**
     * Calculate the four headline metrics displayed above the register.
     *
     * These cards deliberately describe the current calendar month rather
     * than changing with register filters.
     *
     * @return array<string, int>
     */
    private function currentMonthSummary(): array
    {
        $monthStart =
            now()
                ->startOfMonth()
                ->toDateString();

        $monthEnd =
            now()
                ->endOfMonth()
                ->toDateString();

        $tenantAmount =
            (int) Payment::query()
                ->whereBetween(
                    'payment_date',
                    [
                        $monthStart,
                        $monthEnd,
                    ]
                )
                ->sum('amount');

        $tenantTransactions =
            Payment::query()
                ->whereBetween(
                    'payment_date',
                    [
                        $monthStart,
                        $monthEnd,
                    ]
                )
                ->count();

        $ownerAmount =
            (int) OwnerTransaction::query()
                ->where(
                    'direction',
                    'credit'
                )
                ->where(
                    'category',
                    'owner_deposit'
                )
                ->whereBetween(
                    'transaction_date',
                    [
                        $monthStart,
                        $monthEnd,
                    ]
                )
                ->sum('amount');

        $ownerTransactions =
            OwnerTransaction::query()
                ->where(
                    'direction',
                    'credit'
                )
                ->where(
                    'category',
                    'owner_deposit'
                )
                ->whereBetween(
                    'transaction_date',
                    [
                        $monthStart,
                        $monthEnd,
                    ]
                )
                ->count();

        return [
            'received_this_month' => $tenantAmount
                + $ownerAmount,

            'tenant_payments' => $tenantAmount,

            'owner_deposits' => $ownerAmount,

            'transactions' => $tenantTransactions
                + $ownerTransactions,
        ];
    }
}
