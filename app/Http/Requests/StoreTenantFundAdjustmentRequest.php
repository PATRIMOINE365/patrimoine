<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate a V1.0.5 Tenant Fund Adjustment.
 *
 * The operator supplies the balance that should exist.
 *
 * Patrimoine derives:
 * - the signed difference;
 * - ledger direction;
 * - absolute adjustment amount;
 * - today's effective date.
 *
 * Tenant fund adjustments cannot be backdated.
 */
class StoreTenantFundAdjustmentRequest extends FormRequest
{
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
            'corrected_balance' => [
                'required',
                'integer',
            ],

            'reason' => [
                'required',
                'string',
                'max:1000',
            ],

            'reference' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }
}
