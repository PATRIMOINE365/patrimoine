<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validate creation of a Patrimoine application User.
 *
 * Password setup is intentionally excluded here. V1.0.3 invitation and
 * password-setup links are implemented by the dedicated invitation workflow.
 */
class StoreUserRequest extends FormRequest
{
    /**
     * Authorization is enforced by the manage_users capability middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize identifiers before validation and persistence.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge([
                'email' => strtolower(
                    trim((string) $this->input('email'))
                ),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            /*
             * V1.0.7: users carry structured `given_names` + `surname`;
             * the model recomposes the display `name` on save. A plain
             * `name` remains accepted when no surname is supplied.
             */
            'name' => [
                'nullable',
                'string',
                'max:255',
                'required_without:surname',
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

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],

            'phone' => [
                'nullable',
                'string',
                'max:50',
            ],

            'role' => [
                'required',
                Rule::enum(UserRole::class),
            ],

            /*
             * New users are active by default. Administrators may explicitly
             * create an inactive account when operationally required.
             */
            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}
