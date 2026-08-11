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
        OwnerAccountingService $service
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

        return response()->json(
            data: $result,
            status: 201
        );
    }
}
