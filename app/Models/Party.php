<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganisation;
use App\Models\Concerns\HasTelephoneNumbers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Concerns\Archivable;

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
    use Archivable;

    use BelongsToOrganisation;
    use HasTelephoneNumbers;

    /**
     * The telephone columns, each with a matching *_country beside it.
     *
     * @var array<int, string>
     */
    protected array $telephoneNumbers = [
        'phone',
        'alternate_phone',
        'contact_person_phone',
    ];

    /**
     * Attributes that may be mass assigned.
     *
     * Validation rules will later enforce the fields required for each
     * Party type. For example, a Person requires a name, phone and email,
     * while an Organisation requires its legal and contact information.
     *
     * @var list<string>
     */
    /**
     * V1.0.34: when this person was erased on request, if they were.
     *
     * Not fillable. Erasure runs through App\Support\PersonalData::erase(),
     * which force-fills it — a mass-assignable "this person is forgotten"
     * flag is not something a form should be able to set.
     */
    protected $casts = [
        'archived_at' => 'datetime',
        'erased_at' => 'datetime',
    ];

    protected $fillable = [
        'type',
        'name',
        'given_names',
        'surname',
        'legal_name',
        'phone',
        'phone_country',
        'alternate_phone',
        'alternate_phone_country',
        'email',
        'email_policy',
        'address',
        'contact_person_name',
        'contact_person_phone',
        'contact_person_phone_country',
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
     * V1.0.7 structured person names.
     *
     * For people, `given_names` + `surname` are the source of truth and
     * the display `name` is always recomposed from them on save, so every
     * existing list, document, export and e-mail keeps working unchanged.
     * Organisations and associations continue to use `name` directly.
     */
    protected static function booted(): void
    {
        static::saving(function (Party $party): void {
            if ($party->type !== 'person') {
                return;
            }

            $composed = trim(
                trim((string) $party->given_names)
                .' '
                .trim((string) $party->surname)
            );

            if ($composed !== '') {
                $party->name = $composed;
            }
        });
    }

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

    /**
     * Leases where this Party is the tenant.
     */
    public function tenantLeases(): HasMany
    {
        return $this->hasMany(
            related: Lease::class,
            foreignKey: 'tenant_id'
        );
    }

    /**
     * Leases where this Party acted as the Agent.
     */
    public function agentLeases(): HasMany
    {
        return $this->hasMany(
            related: Lease::class,
            foreignKey: 'agent_id'
        );
    }

    /**
     * Consolidated owner financial account for this Party, when the Party
     * participates in Patrimoine as an owner.
     */
    public function ownerAccount(): HasOne
    {
        return $this->hasOne(OwnerAccount::class);
    }
}
