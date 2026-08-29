<?php

namespace App\Rules;

use App\Support\PhoneNumber;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A telephone number we could actually dial.
 *
 * The browser sends the country and the national number joined, so what
 * arrives here is E.164 or it is wrong.
 *
 * When no country was chosen at all this rule says nothing: the country
 * field reports that on its own, and one problem should produce one
 * sentence rather than two saying it differently.
 */
class TelephoneNumber implements DataAwareRule, ValidationRule
{
    /**
     * @var array<string, mixed>
     */
    private array $data = [];

    /**
     * @param  string|null  $countryField  the field naming the country, when there is one
     */
    public function __construct(
        private readonly ?string $countryField = null
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @param  Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if ($this->countryField !== null) {
            $country = data_get(
                $this->data,
                $this->countryField
            );

            if (! PhoneNumber::knows(is_string($country) ? $country : null)) {
                return;
            }
        }

        if (! is_string($value) || ! PhoneNumber::isE164($value)) {
            $fail(
                __('api.validation.telephone_number_invalid')
            );
        }
    }
}
