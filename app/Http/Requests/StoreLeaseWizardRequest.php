<?php

namespace App\Http\Requests;

use App\Rules\OrganisationOwned;
use Illuminate\Foundation\Http\FormRequest;

/**
 * V1.0.29 guided lease creation.
 *
 * The wizard walks somebody through a whole letting in one sitting — the
 * property, the owners, the tenant, the agent and the lease itself — and
 * writes NOTHING until the last page. This request therefore validates
 * the SHAPE of that submission only: which blocks exist, and whether the
 * records they point at are ours.
 *
 * The contents of each block are validated by the very request classes
 * the individual forms use (StorePartyRequest, StoreBuildingRequest,
 * StoreUnitRequest, StoreLeaseRequest), delegated to from
 * LeaseWizardService inside the transaction. That is deliberate: the
 * wizard can never drift from the forms, and a rejection there rolls the
 * whole thing back, leaving nothing half-created behind.
 */
class StoreLeaseWizardRequest extends FormRequest
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
            /*
             * Property. Either an existing building, or the attributes
             * for a new one.
             */
            'building' => [
                'required',
                'array',
            ],

            'building.id' => [
                'nullable',
                'integer',
                OrganisationOwned::exists('buildings'),
                'required_without:building.attributes',
            ],

            'building.attributes' => [
                'nullable',
                'array',
                'required_without:building.id',
            ],

            /*
             * Unit. An existing unit always belongs to the building
             * chosen above; the service verifies that rather than
             * trusting the client.
             */
            'unit' => [
                'required',
                'array',
            ],

            'unit.id' => [
                'nullable',
                'integer',
                OrganisationOwned::exists('units'),
                'required_without:unit.attributes',
            ],

            'unit.attributes' => [
                'nullable',
                'array',
                'required_without:unit.id',
            ],

            /*
             * Owners. Required only when the building does not already
             * have them — the wizard skips that page otherwise, and the
             * service enforces the same rule server-side.
             */
            'owners' => [
                'sometimes',
                'array',
            ],

            'owners.*.id' => [
                'nullable',
                'integer',
                OrganisationOwned::exists('parties'),
                'required_without:owners.*.attributes',
            ],

            'owners.*.attributes' => [
                'nullable',
                'array',
                'required_without:owners.*.id',
            ],

            'owners.*.ownership_percentage' => [
                'required',
                'numeric',
                'min:0.01',
                'max:100',
            ],

            /*
             * Tenant. Exactly one is required for a lease.
             */
            'tenant' => [
                'required',
                'array',
            ],

            'tenant.id' => [
                'nullable',
                'integer',
                OrganisationOwned::exists('parties'),
                'required_without:tenant.attributes',
            ],

            'tenant.attributes' => [
                'nullable',
                'array',
                'required_without:tenant.id',
            ],

            /*
             * Agent. Optional in full: the wizard page can be skipped.
             */
            'agent' => [
                'nullable',
                'array',
            ],

            'agent.id' => [
                'nullable',
                'integer',
                OrganisationOwned::exists('parties'),
            ],

            'agent.attributes' => [
                'nullable',
                'array',
            ],

            /*
             * The lease block carries every field the ordinary Lease form
             * carries, minus the three identifiers the wizard is in the
             * middle of creating.
             */
            'lease' => [
                'required',
                'array',
            ],
        ];
    }
}
