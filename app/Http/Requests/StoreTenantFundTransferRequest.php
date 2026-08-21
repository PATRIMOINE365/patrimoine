<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTenantFundTransferRequest extends FormRequest
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
            'source_account_id' => [
                'required',
                'integer',
                'exists:tenant_fund_accounts,id',
            ],

            'destination_account_id' => [
                'required',
                'integer',
                'exists:tenant_fund_accounts,id',
                'different:source_account_id',
            ],

            'amount' => [
                'required',
                'integer',
                'gt:0',
            ],

            'reason' => [
                'required',
                'string',
                'max:500',
            ],

            'reference' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }
}
