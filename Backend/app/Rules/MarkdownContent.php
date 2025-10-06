<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MarkdownContent implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('The :attribute must be a string.');
            return;
        }

        // Check for potentially dangerous HTML/script tags
        $dangerousTags = ['<script', '<iframe', '<object', '<embed', '<form', '<input'];
        foreach ($dangerousTags as $tag) {
            if (stripos($value, $tag) !== false) {
                $fail('The :attribute contains potentially dangerous HTML tags.');
                return;
            }
        }

        // Check for basic markdown structure validity
        // Count opening and closing brackets for links and images
        $openBrackets = substr_count($value, '[');
        $closeBrackets = substr_count($value, ']');
        $openParens = substr_count($value, '(');
        $closeParens = substr_count($value, ')');

        if ($openBrackets !== $closeBrackets) {
            $fail('The :attribute has unmatched square brackets in markdown links/images.');
            return;
        }

        // Allow some flexibility in parentheses as they can be used in regular text
        $parenDiff = abs($openParens - $closeParens);
        if ($parenDiff > 5) { // Allow some tolerance
            $fail('The :attribute has significantly unmatched parentheses in markdown links.');
        }
    }
}
