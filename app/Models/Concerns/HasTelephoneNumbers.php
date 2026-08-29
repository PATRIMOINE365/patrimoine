<?php

namespace App\Models\Concerns;

use App\Support\PhoneNumber;

/**
 * Read a record's telephone numbers the way a person reads them.
 *
 * The columns hold E.164, which is what a gateway needs and what nobody
 * wants to see on an invoice. Each number therefore gains a `*_display`
 * companion — `$party->phone_display` beside `$party->phone` — so a
 * document never has to know how the pieces fit together.
 *
 * Implementing classes list their number columns in $telephoneNumbers. The
 * country lives in the matching `*_country` column.
 */
trait HasTelephoneNumbers
{
    /**
     * Resolve the readable form of a telephone column.
     */
    public function getAttribute($key)
    {
        if (
            is_string($key)
            && str_ends_with($key, '_display')
        ) {
            $number = substr(
                $key,
                0,
                -strlen('_display')
            );

            if (in_array($number, $this->telephoneNumbers ?? [], true)) {
                return PhoneNumber::display(
                    parent::getAttribute($number),
                    parent::getAttribute($number.'_country')
                );
            }
        }

        return parent::getAttribute($key);
    }
}
