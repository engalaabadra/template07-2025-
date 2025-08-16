<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IdsRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Case 1: value is exactly "all"
        if ($value === 'all') {
            return;
        }

        // Case 2: value is an array
        if (is_array($value)) {
            if (count($value) === 0) {
                $fail(__('messages.IDs array cannot be empty.'));
                return;
            }

            foreach ($value as $id) {
                if (!is_numeric($id) || (int) $id != $id) {
                    $fail(__('messages.Each ID must be an integer.'));
                    return;
                }
            }
            return;
        }

        // Invalid case
        $fail(__('messages.IDs must be either "all" or a non-empty array of integers.'));
    }
}
