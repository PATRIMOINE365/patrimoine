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
     * Authorization will later be enforced by Patrimoine permissions.
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
                'exists:units,id',
            ],

            'tenant_id' => [
                'required',
                'integer',
                'exists:parties,id',
            ],

            'agent_id' => [
                'nullable',
                'integer',
                'different:tenant_id',
                'exists:parties,id',
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
                $this->validateManagementFee($validator);
                $this->validateAgentCommission($validator);
                $this->validateUnitAvailability($validator);
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
                'Selected Party must have the tenant role.'
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
                'Selected Party must have the agent role.'
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
                'Termination notice date is required when Lease status is notice.'
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
                'Management fee value must be zero when management fee type is none.'
            );
        }

        if ($type === 'percentage' && $value > 100) {
            $validator->errors()->add(
                'management_fee_value',
                'Percentage management fee cannot exceed 100%.'
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
                'An Agent is required when an agent commission is configured.'
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
                'This Unit already has an active Lease.'
            );
        }
    }
}
