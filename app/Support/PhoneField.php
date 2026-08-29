<?php

namespace App\Support;

use App\Rules\TelephoneCountry;
use App\Rules\TelephoneNumber;

/**
 * The validation rules every telephone field in Patrimoine shares.
 *
 * There are fourteen of them across parties, owners, users, the managing
 * organisation, signup and the platform console. Writing the rules once is
 * what keeps a number recorded on a party dialable in the same way as one
 * recorded on a user.
 */
class PhoneField
{
    /**
     * Rules for the number itself.
     *
     * @param  string  $field  the field this rule set is for, so the country beside it can be found
     * @param  array<int, mixed>  $extra  anything the field adds, such as a conditional requirement
     * @return array<int, mixed>
     */
    public static function number(string $field = 'phone', array $extra = []): array
    {
        return array_merge(
            [
                'nullable',
                'string',
                'max:50',
                new TelephoneNumber(
                    static::countryField($field)
                ),
            ],
            $extra
        );
    }

    /**
     * Rules for a number the record cannot be saved without.
     *
     * @param  array<int, mixed>  $extra
     * @return array<int, mixed>
     */
    public static function requiredNumber(string $field = 'phone', array $extra = []): array
    {
        return array_merge(
            [
                'required',
                'string',
                'max:50',
                new TelephoneNumber(
                    static::countryField($field)
                ),
            ],
            $extra
        );
    }

    /**
     * Rules for the country the number belongs to.
     *
     * @return array<int, mixed>
     */
    public static function country(string $numberField): array
    {
        return [
            'nullable',
            'string',
            'size:2',
            new TelephoneCountry($numberField),
        ];
    }

    /**
     * The name of the country field that belongs to a number field.
     */
    public static function countryField(string $numberField): string
    {
        return $numberField.'_country';
    }
}
