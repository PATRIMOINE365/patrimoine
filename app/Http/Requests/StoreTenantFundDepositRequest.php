<?php

namespace App\Http\Requests;

use App\Models\Lease;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantFundDepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lease_id' => [
                'required',
                'integer',
                'exists:leases,id',
            ],

            'fund_type' => [
                'required',
                Rule::in([
                    'rent_reserve',
                    'consumable_advance',
                    'security_deposit',
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

            'payment_method' => [
                'required',
                Rule::in([
                    'cash',
                    'bank_transfer',
                    'momo',
                ]),
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

    public function after(): array
    {
        return [
            function ($validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $lease = Lease::find(
                    $this->integer('lease_id')
                );

                if ($lease === null) {
                    return;
                }

                if ($lease->status === 'draft') {
                    $validator->errors()->add(
                        'lease_id',
                        __('api.validation.payment_draft_lease')
                    );
                }
            },
        ];
    }
}
