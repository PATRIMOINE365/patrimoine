<?php

namespace App\Http\Requests;

use App\Support\PhoneField;
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

            /*
             * V1.0.7: see StorePartyRequest — structured names for people,
             * plain `name` accepted only when no surname is supplied.
             */
            'name' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(
                    fn (): bool => $this->input('type') === 'person'
                        && ! $this->filled('surname')
                ),
            ],

            'given_names' => [
                'nullable',
                'string',
                'max:255',
            ],

            'surname' => [
                'nullable',
                'string',
                'max:255',
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

            'phone' => PhoneField::number('phone', [
                Rule::requiredIf(
                    fn (): bool => $this->input('type') === 'person'
                ),
            ]),

            'phone_country' => PhoneField::country('phone'),

            'alternate_phone' => PhoneField::number('alternate_phone'),

            'alternate_phone_country' => PhoneField::country('alternate_phone'),

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

            'contact_person_phone' => PhoneField::number('contact_person_phone', [
                Rule::requiredIf(
                    fn (): bool => in_array(
                        $this->input('type'),
                        ['organisation', 'association'],
                        true
                    )
                ),
            ]),

            'contact_person_phone_country' => PhoneField::country('contact_person_phone'),

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


            /*
             * V1.0.29 per-Party exception to the organisation-wide email
             * switch. 'inherit' follows Settings, 'always' keeps emailing
             * this Party while the organisation is silent, and 'never'
             * excludes it while the organisation is sending.
             */
            'email_policy' => [
                'sometimes',
                'nullable',
                'string',
                Rule::in([
                    'inherit',
                    'always',
                    'never',
                ]),
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
