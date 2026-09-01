<?php

namespace App\Http\Requests;

use App\Models\Lease;
use App\Models\Party;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validate creation of a Patrimoine Lease.
 *
 * Patrimoine V1 rules enforced here include:
 * - exactly one Unit per Lease;
 * - exactly one tenant Party;
 * - optional Agent Party;
 * - valid payment frequency and due day;
 * - valid Lease dates;
 * - consistent management-fee configuration;
 * - no overlapping active/notice Lease on the same Unit.
 */
class StoreLeaseRequest extends FormRequest
{
    /**
     * Authorization is enforced centrally by Patrimoine capability middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for Lease creation.
     *
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

            /*
             * NULL means the due day is inherited from start_date.
             */
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

            /*
             * NULL means automatically calculate proration.
             * Zero is a valid explicit override.
             */
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
            * These fields do not belong to the leases table. They instruct
            * Patrimoine to reconstruct money that was already received before
            * the Lease was entered into the application.
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
     * Perform validation that depends on multiple fields or related models.
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

    /**
     * Tenant Party must carry the tenant role.
     */
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

    /**
     * Agent Party, when supplied, must carry the agent role.
     */
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

    /**
     * A Lease in notice state must have a termination notice date.
     */
    private function validateNoticeState($validator): void
    {
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

    /**
     * Management-fee values must match the configured fee type.
     */
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

    /**
     * Agent commission requires an Agent Party.
     */
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
     * Prevent a Unit from having more than one simultaneously active Lease.
     *
     * Draft and terminated Leases do not block a new active Lease.
     */
    private function validateUnitAvailability($validator): void
    {
        if (! in_array($this->input('status'), ['active', 'notice'], true)) {
            return;
        }

        $exists = Lease::query()
            ->where('unit_id', $this->integer('unit_id'))
            ->whereIn('status', ['active', 'notice'])
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
     * Validate historical Advance Payment reconstruction.
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
         * Historical money cannot have been received before the Lease itself
         * began because it would not yet belong to this Lease.
         */
        /*
         * V1.0.43: the date is no longer bounded by the Lease start.
         * Money receivable at Lease creation — the advance and the
         * security deposit — is routinely taken before the tenancy
         * begins, and refusing that date only produced a receipt with a
         * date nobody recognised.
         */
    }
}
