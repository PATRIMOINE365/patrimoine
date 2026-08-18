<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validate updates to an existing Unit.
 */
class UpdateUnitRequest extends FormRequest
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
        $unit = $this->route('unit');

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

                Rule::unique('units', 'name')
                    ->ignore($unit?->id)
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
        ];
    }
}
