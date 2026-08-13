<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate one itemized Security Deposit deduction.
 */
class StoreSecurityDepositDeductionRequest extends FormRequest
{
    /**
     * Authorization is currently enforced by the authenticated
     * Property Manager API route group.
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
            'description' => [
                'required',
                'string',
                'max:255',
            ],

            'amount' => [
                'required',
                'integer',
                'min:1',
            ],

            'deduction_date' => [
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
