<?php

namespace App\Rules;

use App\Support\PhoneNumber;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The country a telephone number was dialled from.
 *
 * A number cannot be stored without one: the calling code is what makes it
 * dialable, and +1 alone can never say whether a flag should read Canada or
 * the United States. The country is therefore required whenever a number is
 * given, and must be the country that number's calling code belongs to.
 */
class TelephoneCountry implements DataAwareRule, ValidationRule
{
    /**
     * Run even when the field is absent.
     *
     * A caller who sends a number and simply omits the country is exactly
     * the case worth catching, and Laravel skips a non-implicit rule on a
     * missing attribute — so without this the rule would never speak.
     */
    public bool $implicit = true;

    /**
     * @var array<string, mixed>
     */
    private array $data = [];

    /**
     * @param  string  $numberField  the field holding the number itself
     */
    public function __construct(
        private readonly string $numberField
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
        $number = data_get(
            $this->data,
            $this->numberField
        );

        $hasNumber = is_string($number)
            && trim($number) !== '';

        if (! $hasNumber) {
            return;
        }

        if (! is_string($value) || ! PhoneNumber::knows($value)) {
            $fail(
                __('api.validation.telephone_country_required')
            );

            return;
        }

        /*
         * A number that does not start with the chosen country's calling
         * code is not a country problem — the pair is simply wrong, and
         * saying so names both halves at once.
         */
        $code = PhoneNumber::codeFor($value);

        if (! str_starts_with((string) $number, '+'.$code)) {
            $fail(
                __('api.validation.telephone_number_invalid')
            );
        }
    }
}
