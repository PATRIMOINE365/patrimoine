<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganisation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An unfinished guided assistant.
 *
 * A lease cannot be saved half-made, so this holds the assistant itself:
 * whatever has been filled in so far, resumable from where it was left.
 * It is not a business record — nothing reads it but the assistant, and
 * discarding one costs nothing.
 */
class LeaseWizardDraft extends Model
{
    use BelongsToOrganisation;

    protected $fillable = [
        'user_id',
        'author_name',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    /**
     * Who started it, while the account still exists.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * What the list shows.
     *
     * Whoever started it and when — the two things that tell one
     * unfinished assistant from another. The date is formatted where it
     * is read, in the reader's language, so only the parts are sent.
     */
    public function label(): string
    {
        return trim($this->author_name);
    }
}
