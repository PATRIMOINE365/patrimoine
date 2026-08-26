<?php

namespace App\Http\Requests;

use App\Models\Lease;
use App\Models\Party;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validate updates to an existing Patrimoine Lease.
 *
 * The API expects a complete Lease representation during updates.
 */
class UpdateLeaseRequest extends FormRequest
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
            'unit_id' => [
                'required',
                'integer',
                \App\Rules\OrganisationOwned::exists('units'),
            ],

            'tenant_id' => [
                'required',
                'integer',
                \App\Rules\OrganisationOwned::exists('parties'),
            ],

            'agent_id' => [
                'nullable',
                'integer',
                'different:tenant_id',
                \App\Rules\OrganisationOwned::exists('parties'),
            ],

            'start_date' => [
                'required',
                'date',
            ],

            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],

            'status' => [
                'required',
                Rule::in([
                    'draft',
                    'active',
                    'notice',
                    'terminated',
                ]),
            ],

            'termination_notice_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
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

            'proration_amount' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'security_deposit_amount' => [
                'required',
                'integer',
                'min:0',
            ],

            /*
            * Contractual Advance Payment.
            *
            * This represents the amount agreed in the Lease and does not create a
            * tenant-fund balance until an actual Payment is received and classified.
            */
            'advance_payment_amount' => [
                'required',
                'integer',
                'min:0',
            ],

            /*
            * Contractual portion of Advance Payment protected as Rent Reserve.
            */
            'rent_reserve_amount' => [
                'required',
                'integer',
                'min:0',
            ],
            /*
            * Historical opening-payment information.
            *
            * These values are operational instructions rather than Lease columns.
            * They allow an existing Lease that was entered before this functionality
            * existed to reconstruct an Advance Payment that had already been received.
            */
            'advance_received' => [
                'required',
                'boolean',
            ],

            'advance_received_date' => [
                Rule::requiredIf(
                    fn (): bool => $this->boolean('advance_received')
                ),
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],

            'advance_received_method' => [
                Rule::requiredIf(
                    fn (): bool => $this->boolean('advance_received')
                ),
                'nullable',
                Rule::in([
                    'cash',
                    'bank_transfer',
                    'momo',
                ]),
            ],

            'advance_received_reference' => [
                'nullable',
                'string',
                'max:255',
            ],

            'advance_received_collector' => [
                Rule::requiredIf(
                    fn (): bool => $this->boolean('advance_received')
                        && $this->input(
                            'advance_received_method'
                        ) === 'cash'
                ),
                'nullable',
                'string',
                'max:255',
            ],
            /*
            * Rent increment configuration.
            */
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
                'after_or_equal:start_date',
            ],

            'management_fee_type' => [
                'required',
                Rule::in([
                    'none',
                    'percentage',
                    'fixed',
                ]),
            ],

            'management_fee_value' => [
                'required',
                'numeric',
                'min:0',
            ],

            'agent_commission_amount' => [
                'required',
                'integer',
                'min:0',
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

                $this->validateTenantRole($validator);
                $this->validateAgentRole($validator);
                $this->validateNoticeState($validator);
                $this->validateAdvanceTerms($validator);
                $this->validateRentIncrement($validator);
                $this->validateManagementFee($validator);
                $this->validateAgentCommission($validator);
                $this->validateUnitAvailability($validator);
                $this->validateReceivedAdvance($validator);
            },
        ];
    }

    private function validateTenantRole($validator): void
    {
        $tenant = Party::find($this->integer('tenant_id'));

        if (
            $tenant !== null
            && ! $tenant->roles()->where('role', 'tenant')->exists()
        ) {
            $validator->errors()->add(
                'tenant_id',
                __('api.validation.tenant_role_required')
            );
        }
    }

    private function validateAgentRole($validator): void
    {
        if (! $this->filled('agent_id')) {
            return;
        }

        $agent = Party::find($this->integer('agent_id'));

        if (
            $agent !== null
            && ! $agent->roles()->where('role', 'agent')->exists()
        ) {
            $validator->errors()->add(
                'agent_id',
                __('api.validation.agent_role_required')
            );
        }
    }

    private function validateNoticeState($validator): void
    {
        $lease = $this->route('lease');

        /*
         * V1.0.5 Phase 9:
         * An existing Lease may no longer enter termination notice through
         * generic editing. The dedicated termination workflow owns that
         * lifecycle transition and its required metadata.
         */
        if (
            $lease !== null
            && $lease->status !== 'notice'
            && $this->input('status') === 'notice'
        ) {
            $validator->errors()->add(
                'status',
                'Use the controlled termination workflow to place a Lease under termination notice.'
            );

            return;
        }

        if (
            $this->input('status') === 'notice'
            && ! $this->filled('termination_notice_date')
        ) {
            $validator->errors()->add(
                'termination_notice_date',
                __('api.validation.notice_date_required')
            );
        }
    }

    private function validateManagementFee($validator): void
    {
        $type = $this->input('management_fee_type');
        $value = (float) $this->input('management_fee_value', 0);

        if ($type === 'none' && $value !== 0.0) {
            $validator->errors()->add(
                'management_fee_value',
                __('api.validation.management_fee_none_zero')
            );
        }

        if ($type === 'percentage' && $value > 100) {
            $validator->errors()->add(
                'management_fee_value',
                __('api.validation.management_fee_percentage_max')
            );
        }
    }

    private function validateAgentCommission($validator): void
    {
        if (
            $this->integer('agent_commission_amount') > 0
            && ! $this->filled('agent_id')
        ) {
            $validator->errors()->add(
                'agent_id',
                __('api.validation.agent_required_for_commission')
            );
        }
    }

    /**
     * Ignore the Lease currently being updated when checking occupancy.
     */
    private function validateUnitAvailability($validator): void
    {
        if (! in_array($this->input('status'), ['active', 'notice'], true)) {
            return;
        }

        /** @var Lease|null $currentLease */
        $currentLease = $this->route('lease');

        $exists = Lease::query()
            ->where('unit_id', $this->integer('unit_id'))
            ->whereIn('status', ['active', 'notice'])
            ->when(
                $currentLease !== null,
                fn ($query) => $query->where('id', '!=', $currentLease->id)
            )
            ->exists();

        if ($exists) {
            $validator->errors()->add(
                'unit_id',
                __('api.validation.unit_active_lease')
            );
        }
    }

    /**
     * Ensure the contractual Rent Reserve is part of, and cannot exceed,
     * the total contractual Advance Payment.
     */
    private function validateAdvanceTerms($validator): void
    {
        $advancePayment =
            $this->integer(
                'advance_payment_amount'
            );

        $rentReserve =
            $this->integer(
                'rent_reserve_amount'
            );

        if ($rentReserve > $advancePayment) {
            $validator->errors()->add(
                'rent_reserve_amount',
                __('api.validation.rent_reserve_exceeds_advance')
            );
        }
    }

    /**
     * Ensure rent-increment fields remain internally consistent.
     */
    private function validateRentIncrement($validator): void
    {
        $type =
            $this->input(
                'rent_increment_type'
            );

        $value =
            (float) $this->input(
                'rent_increment_value',
                0
            );

        $dateSupplied =
            $this->filled(
                'next_rent_increment_date'
            );

        if (
            $type === 'none'
            && $value !== 0.0
        ) {
            $validator->errors()->add(
                'rent_increment_value',
                __('api.validation.rent_increment_none_zero')
            );
        }

        if (
            $type === 'none'
            && $dateSupplied
        ) {
            $validator->errors()->add(
                'next_rent_increment_date',
                __('api.validation.rent_increment_none_date')
            );
        }

        if (
            $type !== 'none'
            && $value <= 0
        ) {
            $validator->errors()->add(
                'rent_increment_value',
                __('api.validation.rent_increment_value_required')
            );
        }

        if (
            $type !== 'none'
            && ! $dateSupplied
        ) {
            $validator->errors()->add(
                'next_rent_increment_date',
                __('api.validation.rent_increment_date_required')
            );
        }

        if (
            $type === 'percentage'
            && $value > 100
        ) {
            $validator->errors()->add(
                'rent_increment_value',
                __('api.validation.rent_increment_percentage_max')
            );
        }
    }

    /**
     * Validate reconstruction of a historically received Advance Payment.
     */
    private function validateReceivedAdvance($validator): void
    {
        if (! $this->boolean('advance_received')) {
            return;
        }

        $advanceAmount =
            $this->integer(
                'advance_payment_amount'
            );

        if ($advanceAmount <= 0) {
            $validator->errors()->add(
                'advance_payment_amount',
                __('api.validation.advance_received_positive')
            );
        }

        if (
            $this->filled('advance_received_date')
            && $this->filled('start_date')
            && $this->date('advance_received_date')
                ->lt($this->date('start_date'))
        ) {
            $validator->errors()->add(
                'advance_received_date',
                __('api.validation.advance_received_before_lease')
            );
        }
    }
}
