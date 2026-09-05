<?php

namespace App\Http\Requests;

use App\Models\Party;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate updates to a Patrimoine Building.
 *
 * The API expects the full current ownership allocation whenever a
 * Building is updated.
 */
class UpdateBuildingRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'address' => [
                'nullable',
                'string',
            ],

            'location' => [
                'nullable',
                'string',
                'max:255',
            ],

            'latitude' => [
                'nullable',
                'numeric',
                'between:-90,90',
            ],

            'longitude' => [
                'nullable',
                'numeric',
                'between:-180,180',
            ],

            'notes' => [
                'nullable',
                'string',
            ],

            'owners' => [
                'required',
                'array',
                'min:1',
            ],

            'owners.*.party_id' => [
                'required',
                'integer',
                'distinct',
                \App\Rules\OrganisationOwned::exists('parties'),
            ],

            'owners.*.ownership_percentage' => [
                'required',
                'numeric',
                'gt:0',
                'lte:100',
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

                $total = collect($this->input('owners', []))->sum(
                    fn (array $owner): float => (float) $owner['ownership_percentage']
                );

                if (abs($total - 100.0) > 0.001) {
                    $validator->errors()->add(
                        'owners',
                        __('api.validation.building_ownership_total')
                    );
                }
            },

            /*
             * V1.0.50: every owner must carry the owner role.
             *
             * The picker on screen only offers owners, so the rule was
             * never written down here, and a tenant-only party sent
             * straight to the API was accepted as a landlord — and given
             * an owner account, and listed among the owners.
             */
            function ($validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $owners = collect($this->input('owners', []));

                $ownerIds = $owners
                    ->pluck('party_id')
                    ->map(fn ($id): int => (int) $id)
                    ->all();

                $withRole = Party::query()
                    ->whereIn('id', $ownerIds)
                    ->whereHas(
                        'roles',
                        fn ($query) => $query->where('role', 'owner')
                    )
                    ->pluck('id')
                    ->map(fn ($id): int => (int) $id)
                    ->all();

                foreach ($owners as $index => $owner) {
                    if (in_array((int) $owner['party_id'], $withRole, true)) {
                        continue;
                    }

                    $validator->errors()->add(
                        'owners.'.$index.'.party_id',
                        __('api.validation.owner_role_required')
                    );
                }
            },
        ];
    }
}
