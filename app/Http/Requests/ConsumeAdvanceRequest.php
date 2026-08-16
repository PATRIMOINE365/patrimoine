<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate consumption of a tenant Consumable Advance against rent.
 */
class ConsumeAdvanceRequest extends FormRequest
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
