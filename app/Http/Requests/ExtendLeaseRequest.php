<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validate the deliberately narrow V1.0.5 Lease Extend contract.
 *
 * Extend is not generic Lease editing. Identity, lifecycle state,
 * opening-financial instructions, proration override and unrelated
 * contractual fields are intentionally unavailable here.
 */
class ExtendLeaseRequest extends FormRequest
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
            'effective_from' => [
                'required',
                'date',
            ],

            'end_date' => [
                'nullable',
                'date',
            ],

            'rent_amount' => [
                'required',
                'integer',
                'min:0',
            ],

            'payment_frequency' => [
                'required',
                Rule::in([
                    'monthly',
                    'quarterly',
                    'bi_yearly',
                    'yearly',
                ]),
            ],

            'due_day' => [
                'nullable',
                'integer',
                'between:1,31',
            ],

            'vat_rate' => [
                'required',
                'numeric',
                'between:0,100',
            ],

            'rent_increment_type' => [
                'required',
                Rule::in([
                    'none',
                    'percentage',
                    'fixed',
                ]),
            ],

            'rent_increment_value' => [
                'required',
                'numeric',
                'min:0',
            ],

            'next_rent_increment_date' => [
                'nullable',
                'date',
            ],

            'notes' => [
                'nullable',
                'string',
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

                $lease = $this->route('lease');

                if ($lease === null) {
                    return;
                }

                /*
                 * "notice" is the current persisted lifecycle state for a
                 * Lease under termination notice. Phase 9 will replace this
                 * with the complete controlled termination workflow.
                 */
                if ($lease->status === 'notice') {
                    $validator->errors()->add(
                        'lease',
                        'A Lease under termination notice cannot be extended until termination is cancelled.'
                    );

                    return;
                }

                /*
                 * A fully Terminated Lease may be reactivated through the
                 * controlled Extend workflow. The mutation service performs
                 * the authoritative occupancy-conflict check while holding
                 * the relevant database locks.
                 */
                if (
                    ! in_array(
                        $lease->status,
                        ['draft', 'active', 'terminated'],
                        true
                    )
                ) {
                    $validator->errors()->add(
                        'lease',
                        'This Lease is not currently eligible for extension.'
                    );

                    return;
                }

                $effectiveFrom =
                    CarbonImmutable::parse(
                        $this->input('effective_from')
                    )->startOfDay();

                $startDate =
                    CarbonImmutable::parse(
                        $lease->start_date
                    )->startOfDay();

                if ($effectiveFrom->lt($startDate)) {
                    $validator->errors()->add(
                        'effective_from',
                        'The extension effective date cannot be before the Lease start date.'
                    );
                }

                if ($this->filled('end_date')) {
                    $endDate =
                        CarbonImmutable::parse(
                            $this->input('end_date')
                        )->startOfDay();

                    if ($endDate->lt($effectiveFrom)) {
                        $validator->errors()->add(
                            'end_date',
                            'The Lease end date cannot be before the extension effective date.'
                        );
                    }
                }

                $incrementType =
                    $this->input(
                        'rent_increment_type'
                    );

                $incrementValue =
                    (float) $this->input(
                        'rent_increment_value',
                        0
                    );

                if (
                    $incrementType === 'none'
                    && $incrementValue != 0
                ) {
                    $validator->errors()->add(
                        'rent_increment_value',
                        'Rent increment value must be zero when no rent increment is configured.'
                    );
                }

                if (
                    $incrementType !== 'none'
                    && $incrementValue <= 0
                ) {
                    $validator->errors()->add(
                        'rent_increment_value',
                        'A configured rent increment requires a value greater than zero.'
                    );
                }

                if (
                    $incrementType !== 'none'
                    && ! $this->filled(
                        'next_rent_increment_date'
                    )
                ) {
                    $validator->errors()->add(
                        'next_rent_increment_date',
                        'A configured rent increment requires the next rent increment date.'
                    );
                }
            },
        ];
    }
}
