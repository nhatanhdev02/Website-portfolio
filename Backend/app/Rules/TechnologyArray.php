<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TechnologyArray implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value)) {
            $fail('The :attribute must be an array.');
            return;
        }

        if (empty($value)) {
            $fail('The :attribute must contain at least one technology.');
            return;
        }

        // Check each technology
        foreach ($value as $index => $technology) {
            if (!is_string($technology)) {
                $fail("The :attribute.{$index} must be a string.");
                return;
            }

            if (strlen($technology) > 50) {
                $fail("The :attribute.{$index} must not exceed 50 characters.");
                return;
            }

            if (empty(trim($technology))) {
                $fail("The :attribute.{$index} cannot be empty.");
                return;
            }
        }

        // Check for duplicates
        $unique = array_unique($value);
        if (count($unique) !== count($value)) {
            $fail('The :attribute must not contain duplicate technologies.');
        }
    }
}
