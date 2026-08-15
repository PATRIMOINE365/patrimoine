<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate creation of an owner-funded property expense.
 *
 * The expense is recorded against a Building and may optionally target
 * a specific Unit. OwnerAccountingService later distributes the expense
 * according to Building ownership percentages.
 */
class StoreOwnerExpenseRequest extends FormRequest
{
    /**
     * Authorization will later be enforced through Patrimoine permissions.
     */
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

            'unit_id' => [
                'nullable',
                'integer',
                'exists:units,id',
            ],

            'description' => [
                'required',
                'string',
                'max:255',
            ],

            'amount' => [
                'required',
                'integer',
                'gt:0',
            ],

            'expense_date' => [
                'required',
                'date',
            ],

            'reference' => [
                'nullable',
                'string',
                'max:255',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }

    /**
     * Ensure an optionally supplied Unit actually belongs to the selected
     * Building.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function ($validator): void {
                if (
                    $validator->errors()->isNotEmpty()
                    || ! $this->filled('unit_id')
                ) {
                    return;
                }

                $belongsToBuilding = \App\Models\Unit::query()
                    ->where('id', $this->integer('unit_id'))
                    ->where(
                        'building_id',
                        $this->integer('building_id')
                    )
                    ->exists();

                if (! $belongsToBuilding) {
                    $validator->errors()->add(
                        'unit_id',
                        __('api.validation.unit_not_in_building')
                    );
                }
            },
        ];
    }
}
