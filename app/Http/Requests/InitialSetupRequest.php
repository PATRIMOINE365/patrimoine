<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Validate Patrimoine's one-time initial installation.
 *
 * This request deliberately covers only the bootstrap data required to:
 *
 * - create the first Administrator;
 * - create the Managing Organisation;
 * - select the organisation-wide language and currency.
 *
 * It is not a general user-registration request.
 */
class InitialSetupRequest extends FormRequest
{
    /**
     * Setup availability is enforced by InitialSetupController.
     *
     * The endpoint itself must remain callable before authentication because
     * no application user exists on a genuine fresh installation.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Initial setup always starts in English, but the selected language is
     * persisted for subsequent login and normal application presentation.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            /*
             * First Administrator.
             */
            'administrator_name' => [
                'required',
                'string',
                'max:255',
            ],

            'administrator_email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'administrator_password' => [
                'required',
                'confirmed',

                Password::min(12)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],

            /*
             * Managing Organisation identity.
             *
             * These mirror the fields that are mandatory when the same
             * organisation is configured later through Settings.
             */
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

            /*
             * Existing Patrimoine financial default.
             */
            'default_vat_rate' => [
                'required',
                'numeric',
                'between:0,100',
            ],

            /*
             * V1.0.2 organisation-wide presentation settings.
             *
             * Language and currency are deliberately independent.
             */
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
        ];
    }
}
