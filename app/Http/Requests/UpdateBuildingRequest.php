<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate updates to a Patrimoine Building.
 *
 * The API expects the full current ownership allocation whenever a
 * Building is updated.
 */
class UpdateBuildingRequest extends FormRequest
{
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
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function ($validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $total = collect($this->input('owners', []))->sum(
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
