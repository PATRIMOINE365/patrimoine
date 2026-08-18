<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validate updates to an existing Patrimoine Party.
 *
 * Updates use the same business requirements as creation. The API expects
 * a complete Party representation for PUT/PATCH-style update requests.
 */
class UpdatePartyRequest extends FormRequest
{
    /**
     * Authorization is enforced centrally by Patrimoine capability middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for Party updates.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => [
                'required',
                Rule::in([
                    'person',
                    'organisation',
                    'association',
                ]),
            ],

            'name' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(
                    fn (): bool => $this->input('type') === 'person'
                ),
            ],

            'legal_name' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(
                    fn (): bool => in_array(
                        $this->input('type'),
                        ['organisation', 'association'],
                        true
                    )
                ),
            ],

            'phone' => [
                'nullable',
                'string',
                'max:50',
                Rule::requiredIf(
                    fn (): bool => $this->input('type') === 'person'
                ),
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
                Rule::requiredIf(
                    fn (): bool => $this->input('type') === 'person'
                ),
            ],

            'address' => [
                'nullable',
                'string',
            ],

            'contact_person_name' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(
                    fn (): bool => in_array(
                        $this->input('type'),
                        ['organisation', 'association'],
                        true
                    )
                ),
            ],

            'contact_person_phone' => [
                'nullable',
                'string',
                'max:50',
                Rule::requiredIf(
                    fn (): bool => in_array(
                        $this->input('type'),
                        ['organisation', 'association'],
                        true
                    )
                ),
            ],

            'contact_person_email' => [
                'nullable',
                'email',
                'max:255',
                Rule::requiredIf(
                    fn (): bool => in_array(
                        $this->input('type'),
                        ['organisation', 'association'],
                        true
                    )
                ),
            ],

            'id_number' => [
                'nullable',
                'string',
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

            'roles' => [
                'sometimes',
                'array',
            ],

            'roles.*' => [
                'string',
                'distinct',
                Rule::in([
                    'tenant',
                    'owner',
                    'agent',
                    'managing_organisation',
                ]),
            ],
        ];
    }
}
