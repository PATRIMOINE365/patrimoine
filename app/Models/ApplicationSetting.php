<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Singleton Patrimoine application configuration.
 *
 * Patrimoine 1.0 is single-tenant and therefore references one managing
 * organisation Party for application-wide business identity.
 */
class ApplicationSetting extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'managing_organisation_party_id',
    ];

    /**
     * Managing organisation configured for this Patrimoine installation.
     */
    public function managingOrganisation(): BelongsTo
    {
        return $this->belongsTo(
            related: Party::class,
            foreignKey: 'managing_organisation_party_id'
        );
    }
}
