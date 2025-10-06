<?php

namespace App\Exceptions\Admin;

use Exception;

class ValidationException extends Exception
{
    private array $errors;

    /**
     * Create validation exception with errors
     *
     * @param array $errors
     * @param string $message
     */
    public function __construct(array $errors, string $message = 'Validation failed')
    {
        parent::__construct($message, 422);
        $this->errors = $errors;
    }

    /**
     * Get validation errors
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Create exception for required field missing
     *
     * @param string $field
     * @return static
     */
    public static function requiredFieldMissing(string $field): self
    {
        return new self(
            [$field => ["The {$field} field is required."]],
            "Required field missing: {$field}"
        );
    }

    /**
     * Create exception for invalid field format
     *
     * @param string $field
     * @param string $format
     * @return static
     */
    public static function invalidFormat(string $field, string $format): self
    {
        return new self(
            [$field => ["The {$field} field must be a valid {$format}."]],
            "Invalid format for field: {$field}"
        );
    }

    /**
     * Create exception for field value too long
     *
     * @param string $field
     * @param int $maxLength
     * @return static
     */
    public static function fieldTooLong(string $field, int $maxLength): self
    {
        return new self(
            [$field => ["The {$field} field may not be greater than {$maxLength} characters."]],
            "Field too long: {$field}"
        );
    }

    /**
     * Create exception for invalid choice
     *
     * @param string $field
     * @param array $validChoices
     * @return static
     */
    public static function invalidChoice(string $field, array $validChoices): self
    {
        $choices = implode(', ', $validChoices);
        return new self(
            [$field => ["The selected {$field} is invalid. Valid choices: {$choices}"]],
            "Invalid choice for field: {$field}"
        );
    }
}
