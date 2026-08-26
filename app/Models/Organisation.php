<?php

namespace App\Models;

use Database\Factories\OrganisationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A Patrimoine customer: one letting agency (or landlord) whose entire
 * portfolio, users and financial history live behind one impermeable
 * organisation boundary.
 *
 * The Organisation itself is deliberately thin. Business identity
 * (legal name, address, banking) continues to live on the managing
 * organisation Party inside the tenant's own data; this row exists for
 * tenancy, licensing and platform administration.
 */
class Organisation extends Model
{
    /** @use HasFactory<OrganisationFactory> */
    use HasFactory;

    /**
     * Attributes that may be mass assigned.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'status',
        'trial_ends_on',

        /*
         * Only the platform bootstrap command and tests ever assign
         * this; no HTTP endpoint mass-assigns raw input into
         * Organisation.
         */
        'is_platform',
    ];

    /**
     * Convert stored values to appropriate PHP representations.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trial_ends_on' => 'date',
            'is_platform' => 'boolean',
        ];
    }

    /**
     * Application users belonging to this organisation.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Licences issued to this organisation, newest first.
     */
    public function licenses(): HasMany
    {
        return $this->hasMany(License::class)->latest('id');
    }

    /**
     * Whether members of this organisation may sign in and work.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * V1.0.11: the internal Kality Ltd staff organisation, excluded
     * from every customer-facing count, list and licensing rule.
     */
    public function isPlatform(): bool
    {
        return (bool) $this->is_platform;
    }

    /**
     * Query scope: customer organisations only.
     */
    public function scopeCustomers($query)
    {
        return $query->where('is_platform', false);
    }

    /**
     * Whether the introductory Professional trial is still running.
     */
    public function onTrial(): bool
    {
        return $this->trial_ends_on !== null
            && $this->trial_ends_on->endOfDay()->gte(Carbon::now());
    }
}
