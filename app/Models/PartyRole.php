<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Assigns a functional role to a Party.
 *
 * Roles are separated from the Party record because one Party may
 * participate in Patrimoine in several capacities at the same time.
 *
 * Example:
 * A person may simultaneously be an Owner and an Agent.
 */
class PartyRole extends Model
{
    /**
     * Attributes that may be mass assigned.
     *
     * @var list<string>
     */
    protected $fillable = [
        'party_id',
        'role',
    ];

    /**
     * The Party to which this role belongs.
     */
    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }
}
