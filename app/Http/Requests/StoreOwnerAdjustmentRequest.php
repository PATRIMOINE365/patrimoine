<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate a V1.0.5 Owner Account Adjustment.
 *
 * The operator supplies the balance that should exist.
 *
 * Patrimoine derives:
 *
 * - the difference;
 * - credit/debit direction;
 * - absolute adjustment amount;
 * - today's effective date.
 *
 * Direction, delta amount and transaction date are therefore deliberately
 * absent from the public Adjustment command.
 */
class StoreOwnerAdjustmentRequest extends FormRequest
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
