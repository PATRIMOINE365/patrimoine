<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validate updates to a Patrimoine application User.
 *
 * Role and active-status changes are subsequently applied through
 * UserAdministrationService so Administrator safeguards remain atomic.
 */
class UpdateUserRequest extends FormRequest
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
        $user = $this->route('user');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->ignore($user),
            ],

            'phone' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
            ],

            'role' => [
                'sometimes',
                'required',
                Rule::enum(UserRole::class),
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}
