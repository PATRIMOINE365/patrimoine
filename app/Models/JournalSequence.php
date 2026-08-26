<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganisation;
use Illuminate\Database\Eloquent\Model;

/**
 * Internal annual sequence used to allocate permanent Journal references.
 */
class JournalSequence extends Model
{
    use BelongsToOrganisation;

    /**
     * @var list<string>
     */
    protected $fillable = [
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
