<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validate creation of a Patrimoine Building.
 *
 * Building ownership is supplied as an array of owner Party IDs and
 * ownership percentages. The application requires the percentages to
 * total exactly 100%.
 */
class StoreBuildingRequest extends FormRequest
{
    /**
     * Authorization is enforced centrally by Patrimoine capability middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for Building creation.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'address' => [
                'nullable',
                'string',
            ],

            'location' => [
                'nullable',
                'string',
                'max:255',
            ],

            'latitude' => [
                'nullable',
                'numeric',
                'between:-90,90',
            ],

            'longitude' => [
                'nullable',
                'numeric',
                'between:-180,180',
            ],

            'notes' => [
                'nullable',
                'string',
            ],

            /*
             * Every Building must have at least one owner in Patrimoine V1.
             */
            'owners' => [
                'required',
                'array',
                'min:1',
            ],

            'owners.*.party_id' => [
                'required',
                'integer',
                'distinct',
                \App\Rules\OrganisationOwned::exists('parties'),
            ],

            'owners.*.ownership_percentage' => [
                'required',
                'numeric',
                'gt:0',
                'lte:100',
            ],
        ];
    }

    /**
     * Validate rules that depend on the complete ownership collection.
     *
     * @param  Validator  $validator
     */
    public function after(): array
    {
        return [
            function ($validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $owners = $this->input('owners', []);

                $total = collect($owners)->sum(
                    fn (array $owner): float => (float) $owner['ownership_percentage']
                );

                if (abs($total - 100.0) > 0.001) {
                    $validator->errors()->add(
                        'owners',
                        __('api.validation.building_ownership_total')
                    );
                }
            },
        ];
    }
}
