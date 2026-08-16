<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOwnerExpenseRequest;
use App\Models\OwnerExpense;
use App\Services\OwnerAccountingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use App\Services\ActivityLogService;
use App\Services\FinancialActivitySnapshotService;

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
        $result = DB::transaction(
            function () use ($request, $service): array {
                $expense = OwnerExpense::create(
                    $request->validated()
                );

                try {
                    $transactions = $service->allocateExpense(
                        $expense
                    );
                } catch (RuntimeException $exception) {
                    throw ValidationException::withMessages([
                        'expense' => [
                            $exception->getMessage(),
                        ],
                    ]);
                }

                return [
                    'expense' => $expense->refresh()->load([
                        'building',
                        'unit',
                    ]),
                    'owner_transactions' => $transactions,
                ];
            }
        );

        $expense = $result['expense'];

        $activityLog->record(
            action: 'owner_expense.recorded',
            request: $request,
            entityType: 'owner_expense',
            entityId: $expense->id,
            entityLabel: 'Owner expense #'.$expense->id,
            snapshot: $activitySnapshots->ownerExpense($expense),
        );

        return response()->json(
            data: $result,
            status: 201
        );
    }
}
