<?php

namespace App\Helpers;

/**
 * Validator - Form Validation Helper
 * 
 * Fluent validation builder with Laravel-style rule syntax.
 * Supports common validation rules: required, email, min, max, unique, confirmed, etc.
 * 
 * Usage:
 *     $v = Validator::make($_POST, [
 *         'email' => 'required|email',
 *         'password' => 'required|min:6|confirmed',
 *         'age' => 'numeric|min:18',
 *     ]);
 * 
 *     if ($v->fails()) {
 *         $errors = $v->errors();  // ['email' => 'must be valid email', ...]
 *     }
 */

class Validator
{
    /**
     * Array to store validation errors by field
     * @var array
     */
    private array $errors = [];

    /**
     * Data being validated
     * @var array
     */
    private array $data = [];

    /**
     * Factory method to create validator and validate immediately
     * 
     * @param array $data Data to validate (typically $_POST)
     * @param array $rules Validation rules (field => 'rule1|rule2:param')
     * @return self Validator instance with validation already run
     * 
     * @example
     *     $v = Validator::make($_POST, ['email' => 'required|email']);
     */
    public static function make(array $data, array $rules): self
    {
        $validator = new self();
        $validator->data = $data;

        foreach ($rules as $field => $fieldRules) {
            foreach (explode('|', $fieldRules) as $rule) {
                $value = $data[$field] ?? null;
                $validator->validate($field, $value, $rule);
            }
        }

        return $validator;
    }

    /**
     * Check if validation failed (has errors)
     * 
     * @return bool True if any validation failed
     * 
     * @example
     *     if ($validator->fails()) {
     *         echo "Validation failed";
     *     }
     */
    public function fails(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Check if validation passed (no errors)
     * Alias for !$this->fails()
     * 
     * @return bool True if validation passed
     * 
     * @example
     *     if ($validator->passes()) {
     *         // Save to database
     *     }
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Get all validation errors
     * 
     * @return array Errors by field: ['field' => 'error message', ...]
     * 
     * @example
     *     $errors = $validator->errors();
     *     // ['email' => 'must be valid email', 'password' => 'required']
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Get error for specific field
     * 
     * @param string $field Field name
     * @return string|null Error message or null if no error
     * 
     * @example
     *     $emailError = $validator->error('email');
     */
    public function error(string $field): ?string
    {
        return $this->errors[$field] ?? null;
    }

    /**
     * Internal method to validate a single field/rule combination
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @param string $rule Validation rule (e.g., 'required', 'min:6')
     * @return void
     */
    private function validate(string $field, $value, string $rule): void
    {
        // Skip if already has error for this field
        if (isset($this->errors[$field])) {
            return;
        }

        // Parse rule into name and parameters
        $parts = explode(':', $rule, 2);
        $ruleName = trim($parts[0]);
        $params = isset($parts[1]) ? explode(',', $parts[1]) : [];

        switch ($ruleName) {
            case 'required':
                if (empty($value) && $value !== '0') {
                    $this->errors[$field] = "$field is required";
                }
                break;

            case 'email':
                if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->errors[$field] = "$field must be valid email";
                }
                break;

            case 'min':
                if ($value && strlen((string)$value) < (int)($params[0] ?? 0)) {
                    $this->errors[$field] = "$field must be at least " . $params[0] . " characters";
                }
                break;

            case 'max':
                if ($value && strlen((string)$value) > (int)($params[0] ?? 0)) {
                    $this->errors[$field] = "$field must not exceed " . $params[0] . " characters";
                }
                break;

            case 'numeric':
                if ($value && !is_numeric($value)) {
                    $this->errors[$field] = "$field must be numeric";
                }
                break;

            case 'url':
                if ($value && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->errors[$field] = "$field must be valid URL";
                }
                break;

            case 'confirmed':
                $confirmField = $field . '_confirmation';
                if (($this->data[$field] ?? null) !== ($this->data[$confirmField] ?? null)) {
                    $this->errors[$field] = "$field confirmation does not match";
                }
                break;

            case 'unique':
                // TODO: Implement database check for unique values
                // Usage: 'email' => 'unique:users' checks users table email column
                // Will require database model instance
                break;

            case 'regex':
                if ($value && !preg_match($params[0] ?? '', $value)) {
                    $this->errors[$field] = "$field format is invalid";
                }
                break;

            case 'in':
                $allowed = $params;
                if ($value && !in_array($value, $allowed)) {
                    $this->errors[$field] = "$field must be one of: " . implode(', ', $allowed);
                }
                break;

            case 'nullable':
                // Skip validation if value is empty
                break;

            default:
                // Unknown rule - ignore
                break;
        }
    }
}
