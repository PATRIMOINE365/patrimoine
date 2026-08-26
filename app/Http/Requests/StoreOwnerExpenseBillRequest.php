<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate creation of an owner expense bill.
 *
 * A bill charges one or more expense lines DIRECTLY to the OwnerAccount
 * addressed by the route, without Building context. Each line carries
 * its own description and whole-currency amount.
 */
class StoreOwnerExpenseBillRequest extends FormRequest
{
    /**
     * Authorization is enforced centrally by Patrimoine capability middleware.
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
            /*
             * V1.0.8: the unified expense flow always names the
             * Building the cost belongs to, and whether it bills only
             * this owner or splits across all owners by percentage.
             */
            'building_id' => [
                'required',
                'integer',
                \App\Rules\OrganisationOwned::exists('buildings'),
            ],

            'split' => [
                'required',
                'in:single,split',
            ],

            'bill_date' => [
                'required',
                'date_format:Y-m-d',
            ],

            'notes' => [
                'nullable',
                'string',
            ],

            'lines' => [
                'required',
                'array',
                'min:1',
            ],

            'lines.*.description' => [
                'required',
                'string',
                'max:255',
            ],

            'lines.*.amount' => [
                'required',
                'integer',
                'gt:0',
            ],
        ];
    }
}
