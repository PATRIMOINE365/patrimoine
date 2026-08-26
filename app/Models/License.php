<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One issued licence entitling an Organisation to a Patrimoine plan for
 * a period of time.
 *
 * Licences are issued and extended by the platform operator (Kality
 * Ltd); Patrimoine V1.1.0 has no self-service purchase flow. A licence
 * with no expiry date is perpetual: the migration grandfathers existing
 * installations this way.
 *
 * The organisation's effective plan is always resolved through
 * LicensingService rather than read from this table directly, because
 * trial entitlement and the Free fallback are not licence rows.
 */
class License extends Model
{
    /**
     * Attributes that may be mass assigned.
     *
     * @var list<string>
     */
    protected $fillable = [
        'organisation_id',
        'plan',
        'starts_on',
        'expires_on',
        'notes',
    ];

    /**
     * Convert stored values to appropriate PHP representations.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'expires_on' => 'date',
        ];
    }

    /**
     * The organisation this licence was issued to.
     */
    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    /**
     * Whether this licence entitles the organisation today.
     */
    public function coversToday(): bool
    {
        $today = Carbon::now();

        if ($this->starts_on !== null && $this->starts_on->startOfDay()->gt($today)) {
            return false;
        }

        return $this->expires_on === null
            || $this->expires_on->endOfDay()->gte($today);
    }
}
