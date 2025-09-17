<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class RequiredIfEnabled implements ValidationRule
{
    private $otherField;
    private $otherValue;

    public function __construct($otherField, $otherValue)
    {
        $this->otherField = $otherField;
        $this->otherValue = $otherValue;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Extract the index from the attribute, e.g., 'exam_date_time.0'
        if (preg_match('/\d+/', $attribute, $matches)) {
            $index = $matches[0];

            // Check the corresponding value of the other field
            $otherFieldValue = request($this->otherField . '.' . $index);

            if ($otherFieldValue == $this->otherValue && empty($value)) {
                $fail("The $attribute field is required when {$this->otherField}[$index] is {$this->otherValue}.");
            }
        }
    }
}
