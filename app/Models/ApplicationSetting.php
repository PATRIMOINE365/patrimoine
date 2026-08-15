<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Application-wide configuration settings.
 *
 * This model stores settings that apply globally to the Patrimoine
 * application rather than to a specific lease, building, or party.
 *
 * Financial defaults such as the default VAT rate are used when creating
 * new records. They do not retroactively alter existing contractual or
 * accounting records.
 */
class ApplicationSetting extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'managing_organisation_party_id',
        'default_vat_rate',
        'language',
        'currency',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * The VAT rate is stored with two decimal places so rates such as
     * 17.50% can be represented accurately if required in the future.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'default_vat_rate' => 'decimal:2',
        ];
    }

    /**
     * Get the Party representing the organisation managing the
     * Patrimoine installation.
     */
    public function managingOrganisation(): BelongsTo
    {
        return $this->belongsTo(
            Party::class,
            'managing_organisation_party_id'
        );
    }
}
