<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a person, organisation, or association that interacts
 * with Patrimoine.
 *
 * A Party is the central identity record in the application.
 * The same Party may hold several roles simultaneously, for example:
 * - Owner
 * - Tenant
 * - Agent
 * - Managing organisation
 *
 * Role assignment is stored separately in the party_roles table.
 */
class Party extends Model
{
    /**
     * Attributes that may be mass assigned.
     *
     * Validation rules will later enforce the fields required for each
     * Party type. For example, a Person requires a name, phone and email,
     * while an Organisation requires its legal and contact information.
     *
     * @var list<string>
     */
    protected $fillable = [
        'type',
        'name',
        'legal_name',
        'phone',
        'alternate_phone',
        'email',
        'address',
        'contact_person_name',
        'contact_person_phone',
        'contact_person_email',
        'id_number',
        'registration_number',
        'vat_tin',
        'bank_name',
        'bank_account_name',
        'bank_account_number',
        'bank_branch',
        'notes',
    ];

    /**
     * Roles currently assigned to this Party.
     */
    public function roles(): HasMany
    {
        return $this->hasMany(PartyRole::class);
    }

    /**
     * Building ownership records belonging to this Party.
     *
     * A Party may own a percentage of several buildings.
     */
    public function buildingOwnerships(): HasMany
    {
        return $this->hasMany(BuildingOwner::class);
    }
}
