<?php

namespace App\Http\Requests;

use App\Support\PhoneField;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
     * Authorization is enforced centrally by capability middleware before validation.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Preserve compatibility with V1.0.1 clients that do not yet submit
     * organisation presentation settings.
     *
     * Missing values receive the configured compatibility defaults. Explicit
     * values are left untouched so validation still rejects unsupported
     * languages or currencies.
     */
    protected function prepareForValidation(): void
    {
        $values = [];

        if (! $this->has('language')) {
            $values['language'] =
                config(
                    'patrimoine.defaults.language',
                    'en'
                );
        }

        if (! $this->has('currency')) {
            $values['currency'] =
                config(
                    'patrimoine.defaults.currency',
                    'GHS'
                );
        }

        if ($values !== []) {
            $this->merge(
                $values
            );
        }
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

            'phone' => PhoneField::number('phone'),

            'phone_country' => PhoneField::country('phone'),

            'alternate_phone' => PhoneField::number('alternate_phone'),

            'alternate_phone_country' => PhoneField::country('alternate_phone'),

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

            'contact_person_phone' => PhoneField::requiredNumber('contact_person_phone'),

            'contact_person_phone_country' => PhoneField::country('contact_person_phone'),

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

            'default_vat_rate' => [
                'required',
                'numeric',
                'between:0,100',
            ],

            'language' => [
                'required',
                'string',
                Rule::in(
                    array_keys(
                        config(
                            'patrimoine.languages',
                            []
                        )
                    )
                ),
            ],

            'currency' => [
                'required',
                'string',
                Rule::in(
                    array_keys(
                        config(
                            'patrimoine.currencies',
                            []
                        )
                    )
                ),
            ],

            /*
             * V1.0.29: the organisation-wide switch for everything
             * Patrimoine sends to tenants, owners and agents.
             */
            'party_emails_enabled' => [
                'sometimes',
                'boolean',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }
}
