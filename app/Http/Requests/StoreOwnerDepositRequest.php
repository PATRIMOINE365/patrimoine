<?php

namespace App\Http\Requests;

use App\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validate money received from a property owner.
 *
 * Owner deposits are actual incoming funds and may be either:
 *
 * - general funding;
 * - funding intended for property expenses;
 * - funding intended specifically for repairs / maintenance;
 * - other documented owner funding.
 *
 * A Building and Unit may optionally be supplied so the deposit retains
 * useful operational context while the owner's financial balance remains
 * consolidated at OwnerAccount level.
 */
class StoreOwnerDepositRequest extends FormRequest
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
            'amount' => [
                'required',
                'integer',
                'gt:0',
            ],

            'transaction_date' => [
                'required',
                'date',
            ],

            'payment_method' => [
                'required',
                Rule::in([
                    'cash',
                    'bank_transfer',
                    'momo',
                ]),
            ],

            /*
             * The purpose explains why the owner supplied the money.
             *
             * This does not automatically create an expense.
             */
            'deposit_purpose' => [
                'required',
                Rule::in([
                    'general_funding',
                    'property_expense',
                    'repair_maintenance',
                    'other',
                ]),
            ],

            /*
             * Property context is optional because an owner may simply top up
             * their overall account rather than funding one particular asset.
             */
            'building_id' => [
                'nullable',
                'integer',
                'exists:buildings,id',
            ],

            'unit_id' => [
                'nullable',
                'integer',
                'exists:units,id',
            ],

            'reference' => [
                'nullable',
                'string',
                'max:255',
            ],

            /*
             * Cash must identify who physically received the money.
             */
            'collector_name' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(
                    fn (): bool =>
                        $this->input('payment_method') === 'cash'
                ),
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }

    /**
     * Perform validation involving related records.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function ($validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $this->validateUnitBelongsToBuilding(
                    $validator
                );
            },
        ];
    }

    /**
     * A supplied Unit must belong to the supplied Building.
     *
     * Requiring Building whenever Unit is present also prevents ambiguous
     * property attribution in the financial register.
     */
    private function validateUnitBelongsToBuilding(
        $validator
    ): void {
        if (! $this->filled('unit_id')) {
            return;
        }

        if (! $this->filled('building_id')) {
            $validator->errors()->add(
                'building_id',
                'A Building is required when a Unit is selected.'
            );

            return;
        }

        $unit = Unit::find(
            $this->integer('unit_id')
        );

        if (
            $unit !== null
            && $unit->building_id
                !== $this->integer('building_id')
        ) {
            $validator->errors()->add(
                'unit_id',
                'Selected Unit does not belong to the selected Building.'
            );
        }
    }
}
