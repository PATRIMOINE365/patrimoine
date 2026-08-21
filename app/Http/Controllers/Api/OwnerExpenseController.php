<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOwnerExpenseRequest;
use App\Models\OwnerExpense;
use App\Services\ActivityLogService;
use App\Services\FinancialActivitySnapshotService;
use App\Services\OwnerAccountingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Transactional API controller for owner-funded property expenses.
 */
class OwnerExpenseController extends Controller
{
    /**
     * Record an expense and immediately allocate it across Building owners.
     */
    public function store(
        StoreOwnerExpenseRequest $request,
        OwnerAccountingService $service,
        ActivityLogService $activityLog,
        FinancialActivitySnapshotService $activitySnapshots
    ): JsonResponse {
        try {
            $result = DB::transaction(
                function () use (
                    $request,
                    $service,
                    $activityLog,
                    $activitySnapshots,
                ): array {
                    $expense =
                        OwnerExpense::create(
                            $request->validated()
                        );

                    try {
                        $transactions =
                            $service->allocateExpense(
                                $expense
                            );
                    } catch (RuntimeException $exception) {
                        throw ValidationException::withMessages([
                            'expense' => [
                                $exception->getMessage(),
                            ],
                        ]);
                    }

                    $expense =
                        $expense
                            ->refresh()
                            ->load([
                                'building',
                                'unit',
                            ]);

                    /*
                     * OwnerExpense + allocated OwnerTransactions +
                     * Financial Journal + Activity Log are atomic.
                     */
                    $activityLog->record(
                        action: 'owner_expense.recorded',
                        request: $request,
                        entityType: 'owner_expense',
                        entityId: $expense->id,
                        entityLabel: 'Owner expense #'.$expense->id,
                        snapshot:
                            $activitySnapshots
                                ->ownerExpense(
                                    $expense
                                ),
                    );

                    return [
                        'expense' =>
                            $expense,

                        'owner_transactions' =>
                            $transactions,
                    ];
                }
            );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'expense' => [
                    $exception->getMessage(),
                ],
            ]);
        }

        return response()->json(
            data: $result,
            status: 201
        );
    }

}
