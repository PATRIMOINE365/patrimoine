<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate configuration of Patrimoine's managing organisation.
 *
 * The API intentionally accepts organisation details directly rather than
 * requiring the caller to create a Party first. The controller creates or
 * updates the underlying Party transparently.
 */
class UpdateManagingOrganisationRequest extends FormRequest
{
    /**
     * Authorization is enforced by the Property Manager route middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for the managing organisation.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'legal_name' => [
                'required',
                'string',
                'max:255',
            ],

            'address' => [
                'required',
                'string',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:50',
            ],

            'alternate_phone' => [
                'nullable',
                'string',
                'max:50',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'contact_person_name' => [
                'required',
                'string',
                'max:255',
            ],

            'contact_person_phone' => [
                'required',
                'string',
                'max:50',
            ],

            'contact_person_email' => [
                'required',
                'email',
                'max:255',
            ],

            'registration_number' => [
                'nullable',
                'string',
                'max:255',
            ],

            'vat_tin' => [
                'nullable',
                'string',
                'max:255',
            ],

            'bank_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'bank_account_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'bank_account_number' => [
                'nullable',
                'string',
                'max:255',
            ],

            'bank_branch' => [
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
