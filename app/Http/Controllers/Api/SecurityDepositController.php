<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SettleSecurityDepositRequest;
use App\Models\Lease;
use App\Services\SecurityDepositService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Transactional API controller for final Security Deposit settlement.
 */
class SecurityDepositController extends Controller
{
    /**
     * Finalize the Security Deposit for a Lease.
     *
     * The underlying service calculates:
     *
     * refund = max(0, deposit - deductions)
     * debt   = max(0, deductions - deposit)
     */
    public function settle(
        SettleSecurityDepositRequest $request,
        Lease $lease,
        SecurityDepositService $service
    ): JsonResponse {
        $validated = $request->validated();

        try {
            $settlement = $service->settle(
                lease: $lease,
                settlementDate: $validated['settlement_date'],
                refundVoucherNumber:
                    $validated['refund_voucher_number'] ?? null,
                notes: $validated['notes'] ?? null
            );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'security_deposit' => [
                    $exception->getMessage(),
                ],
            ]);
        }

        return response()->json(
            data: $settlement->load('lease'),
            status: 201
        );
    }
}
