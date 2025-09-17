<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UrlOrIp implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        //
    }

    public function passes($attribute, $value)
    {
        // Check if it's a valid URL OR a valid IP address
        return filter_var($value, FILTER_VALIDATE_URL) || filter_var($value, FILTER_VALIDATE_IP);
    }

    public function message()
    {
        return 'The field must be a valid URL or IP address.';
    }
}
