<?php

namespace App\Http\Requests;

use App\Models\Lease;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validate recording of a tenant Payment.
 *
 * Payments are transactional records rather than simple CRUD objects.
 * Once a Payment is accepted, Patrimoine will immediately attempt FIFO
 * allocation against outstanding Invoices for the selected Lease.
 */
class StorePaymentRequest extends FormRequest
{
    /**
     * Authorization is enforced centrally by Patrimoine capability middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for tenant Payment creation.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'lease_id' => [
                'required',
                'integer',
                'exists:leases,id',
            ],

            /*
             * Patrimoine stores monetary values as whole currency units.
             */
            'amount' => [
                'required',
                'integer',
                'gt:0',
            ],

            /*
             * Backdated payments are intentionally supported.
             */
            'payment_date' => [
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
             * Reference is optional for all payment methods.
             */
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
     * Perform business validation that requires Lease state.
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

                $lease = Lease::find(
                    $this->integer('lease_id')
                );

                if ($lease === null) {
                    return;
                }

                /*
                 * Payments should not be recorded against draft Leases.
                 *
                 * Active, notice and terminated Leases may still receive
                 * payments because arrears can legitimately be settled
                 * after termination.
                 */
                if ($lease->status === 'draft') {
                    $validator->errors()->add(
                        'lease_id',
                        __('api.validation.payment_draft_lease')
                    );
                }
            },
        ];
    }
}
