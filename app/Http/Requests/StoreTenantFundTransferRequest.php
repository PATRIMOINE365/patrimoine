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
                \App\Rules\OrganisationOwned::exists('tenant_fund_accounts'),
            ],

            'destination_account_id' => [
                'required',
                'integer',
                \App\Rules\OrganisationOwned::exists('tenant_fund_accounts'),
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
