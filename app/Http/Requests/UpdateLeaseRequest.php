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
             * V1.0.43 — receiving the Security Deposit.
             *
             * Entering a deposit on the Lease receives it: the money goes
             * into the Lease's own Security Deposit account, which has
             * existed since V1.0.8 and was never funded by anything.
             *
             * All three stay nullable rather than required-with-amount,
             * because this request is also how a Lease is EDITED, and by
             * then the deposit has usually already been taken. Absent, the
             * Lease start date and a bank transfer stand in.
             *
             * The date is deliberately unbounded by the Lease start.
             * A deposit is normally what secures the unit, weeks before
             * anybody moves in.
             */
            'security_deposit_received_date' => [
                'nullable',
                'date',
            ],

            'security_deposit_received_method' => [
                'nullable',
                Rule::in([
                    'cash',
                    'bank_transfer',
                    'momo',
                    'cheque',
                ]),
            ],

            'security_deposit_received_reference' => [
                'nullable',
                'string',
                'max:255',
            ],

            /*
             * As with the advance, the cashier for a cash deposit is
             * always the signed-in user; the controller overwrites
             * whatever arrives here.
             */
            'security_deposit_received_collector' => [
                'nullable',
                'string',
                'max:255',
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

            /*
             * V1.0.43: deliberately NOT after_or_equal:start_date.
             *
             * Money changes hands before a tenancy begins all the time — a
             * deposit and the first advance are usually what secures the
             * unit in the first place, weeks before anybody moves in. The
             * rule used to refuse exactly that, so an operator entering a
             * real payment had to lie about its date to get it accepted,
             * and the receipt then said something that had not happened.
             */
            'advance_received_date' => [
                Rule::requiredIf(
                    fn (): bool => $this->boolean('advance_received')
                ),
                'nullable',
                'date',
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
                    'cheque',
                ]),
            ],

            'advance_received_reference' => [
                'nullable',
                'string',
                'max:255',
            ],

            /*
             * NOT required, deliberately. The cashier for a cash advance
             * is ALWAYS the signed-in user: both LeaseController and
             * LeaseWizardService overwrite whatever arrives here with
             * $request->user()->name before anything is written. Asking
             * for it as well meant the value was demanded and then thrown
             * away — and the lease assistant, which had no such field on
             * any of its ten pages, was refused with "The cashier field
             * is required", naming a field nobody could see or fill.
             */
            'advance_received_collector' => [
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

        /*
         * V1.0.43: the date is no longer bounded by the Lease start.
         * Money receivable at Lease creation — the advance and the
         * security deposit — is routinely taken before the tenancy
         * begins, and refusing that date only produced a receipt with a
         * date nobody recognised.
         */
    }
}
