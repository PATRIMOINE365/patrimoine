<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validate an exceptional manual owner-ledger adjustment.
 *
 * Adjustments must always carry an explicit reason for auditability.
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
            'direction' => [
                'required',
                Rule::in([
                    'credit',
                    'debit',
                ]),
            ],

            'amount' => [
                'required',
                'integer',
                'gt:0',
            ],

            'transaction_date' => [
                'required',
                'date',
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
