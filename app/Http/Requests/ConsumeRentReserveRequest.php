<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate Rent Reserve consumption against an Invoice.
 */
class ConsumeRentReserveRequest extends FormRequest
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
            'invoice_id' => [
                'required',
                'integer',
                'exists:invoices,id',
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
        ];
    }
}
