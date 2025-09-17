<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EndDateAfterStartDate implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $data = request()->all();

        // Check if both start_date_time and end_date_time are present and are arrays
        if (
            (isset($data['start_date_time']) && is_array($data['start_date_time']) &&
                isset($data['end_date_time']) && is_array($data['end_date_time']))
            ||
            (isset($data['end_date_time']) && is_array($data['end_date_time'])
                && isset($data['exam_date_time']) && is_array($data['exam_date_time']))
        ) {

            foreach ($data['start_date_time'] as $index => $startDateTime) {
                if (isset($data['end_date_time'][$index])) {
                    $endDateTime = $data['end_date_time'][$index];
                    if (strtotime($endDateTime) <= strtotime($startDateTime)) {
                        $fail("The end date and time at index " . ($index + 1) . " must be after the start date and time.");
                    }
                }
            }

            foreach ($data['end_date_time'] as $index => $endDateTime) {
                if (isset($data['end_date_time'][$index]) && isset($data['exam_date_time'][$index])) {
                    $examDateTime = $data['exam_date_time'][$index];
                    if (strtotime($examDateTime) <= strtotime($endDateTime)) {
                        $fail("The exam date and time at index " . ($index + 1) . " must be after the end date and time.");
                    }
                }
            }
        } else {
            $fail('Both start_date_time, end_date_time, exam_date_time must be arrays.');
        }
    }
}
