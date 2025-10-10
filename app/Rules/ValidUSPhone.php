<?php

namespace App\Rules;

use Closure;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\NumberParseException;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidUSPhone implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param Closure(string): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $phoneUtil = PhoneNumberUtil::getInstance();

        try {
            $numberProto = $phoneUtil->parse($value, 'US');

            if (!$phoneUtil->isValidNumber($numberProto) && $phoneUtil->getRegionCodeForNumber($numberProto) !== 'US') {
                $fail('Number phone is invalid');

                return;
            }
        } catch (NumberParseException $e) {
            $fail('format invalid.');
        }
    }
}
