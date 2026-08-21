<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validate creation of a Patrimoine Party.
 *
 * A Party may be:
 * - a person;
 * - an organisation;
 * - an association.
 *
 * Validation rules vary by Party type because the required identifying
 * information differs between natural persons and legal entities.
 */
class StorePartyRequest extends FormRequest
{
    /**
     * Authorization is enforced centrally by Patrimoine capability middleware.
     *
     * For now, API access is allowed while the core application is
     * under development.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for Party creation.
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
             * Person-specific identity.
             *
             * V1.0.7: people carry structured `given_names` + `surname`;
             * the model recomposes the display `name` on save. A plain
             * `name` remains accepted (API compatibility) when no surname
             * is supplied.
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

            /*
             * Organisation/association identity.
             */
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

            /*
             * Primary contact information.
             *
             * Patrimoine 1.0 requires phone and email for a Person.
             */
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

            /*
             * Organisations and associations require a contact person and
             * their phone/email details.
             */
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

            /*
             * Optional identification and registration details.
             */
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

            /*
             * Banking details are optional in V1. They are mainly used
             * when a Party acts as an Owner or Agent.
             */
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
             * A Party may receive several functional roles during creation.
             */
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
