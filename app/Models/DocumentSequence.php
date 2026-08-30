<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganisation;
use Illuminate\Database\Eloquent\Model;

/**
 * The counter behind one series, for one organisation, for one year.
 *
 * Never read to find out what the last number was — the row is taken
 * under a lock by DocumentNumberService and nothing else should touch it.
 */
class DocumentSequence extends Model
{
    use BelongsToOrganisation;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'series',
        'year',
        'next_number',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'next_number' => 'integer',
        ];
    }
}
