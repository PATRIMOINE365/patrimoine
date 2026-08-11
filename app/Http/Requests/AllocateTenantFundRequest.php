<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validate classification of unapplied tenant Payment funds.
 *
 * Patrimoine allows unapplied money to be deliberately transferred into:
 * - Rent Reserve;
 * - Consumable Advance;
 * - Security Deposit.
 *
 * The actual available amount is validated inside TenantFundController
 * against the Payment's current unallocated balance.
 */
class AllocateTenantFundRequest extends FormRequest
{
    /**
     * Authorization will later be enforced through Patrimoine permissions.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for allocating unapplied Payment funds.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'fund_type' => [
                'required',
                Rule::in([
                    'rent_reserve',
                    'consumable_advance',
                    'security_deposit',
                ]),
            ],

            /*
             * Monetary values remain whole currency units.
             */
            'amount' => [
                'required',
                'integer',
                'gt:0',
            ],

            /*
             * The accounting date may be backdated where appropriate.
             */
            'transaction_date' => [
                'required',
                'date',
            ],

            'reference' => [
                'nullable',
                'string',
                'max:255',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }
}
