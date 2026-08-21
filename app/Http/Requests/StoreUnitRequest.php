<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validate creation of a Unit within a Building.
 */
class StoreUnitRequest extends FormRequest
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
            'building_id' => [
                'required',
                'integer',
                'exists:buildings,id',
            ],

            'name' => [
                'required',
                'string',
                'max:255',

                /*
                 * Unit names/numbers must be unique within a Building.
                 */
                Rule::unique('units', 'name')
                    ->where(
                        fn ($query) => $query->where(
                            'building_id',
                            $this->input('building_id')
                        )
                    ),
            ],

            'description' => [
                'nullable',
                'string',
            ],

            // V1.0.7: units may be marked commercial (shop/office).
            'is_commercial' => [
                'nullable',
                'boolean',
            ],
        ];
    }
}
