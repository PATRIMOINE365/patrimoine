<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validate scheduling of a future rent increment on a Lease.
 *
 * Shape validation only. The deeper business rules — lease status, the
 * single-pending-increment rule, the minimum 12-month interval, and the
 * requirement that the new rent exceeds the current rent — are enforced
 * authoritatively by RentIncrementService::schedule().
 */
class StoreRentIncrementRequest extends FormRequest
{
    /**
     * Authorization is enforced centrally by Patrimoine capability middleware.
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
            'increment_type' => [
                'required',
                Rule::in([
                    'percentage',
                    'fixed',
                ]),
            ],

            'increment_value' => [
                'required',
                'numeric',
                'gt:0',

                /*
                 * A percentage increment above 100% is almost certainly a
                 * data-entry error; fixed amounts have no upper bound here.
                 */
                Rule::when(
                    fn (): bool => $this->input('increment_type') === 'percentage',
                    ['max:100']
                ),
            ],

            'effective_date' => [
                'required',
                'date_format:Y-m-d',
            ],
        ];
    }
}
