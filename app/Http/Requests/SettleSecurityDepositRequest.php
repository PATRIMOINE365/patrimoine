<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate final settlement of a Lease's Security Deposit.
 */
class SettleSecurityDepositRequest extends FormRequest
{
    /**
     * Authorization will later be enforced through Patrimoine permissions.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'settlement_date' => [
                'required',
                'date',
            ],

            'refund_voucher_number' => [
                'nullable',
                'string',
                'max:255',
                'unique:security_deposit_settlements,refund_voucher_number',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }
}
