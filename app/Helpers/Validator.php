<?php

namespace App\Helpers;

class Validator
{
    private array $errors = [];
    private array $data = [];
    private static ?\PDO $pdo = null;

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

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function error(string $field): ?string
    {
        return $this->errors[$field] ?? null;
    }

    private function validate(string $field, mixed $value, string $rule): void
    {
        $parts = explode(':', $rule, 2);
        $ruleName = $parts[0];
        $params = isset($parts[1]) ? explode(',', $parts[1]) : [];

        switch ($ruleName) {
            case 'required':
                if ($value === null || $value === '') {
                    $this->errors[$field] = "$field is required";
                }
                break;

            case 'email':
                if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->errors[$field] = "$field must be a valid email";
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
                if (empty($params[0])) {
                    break;
                }

                $table = $params[0];
                $column = $params[1] ?? $field;
                $exceptId = $params[2] ?? null;

                $identifierPattern = '/^[a-zA-Z0-9_]+$/';
                if (!preg_match($identifierPattern, $table) || !preg_match($identifierPattern, $column)) {
                    $this->errors[$field] = "$field uniqueness check failed";
                    break;
                }

                $pdo = self::pdo();
                if (!$pdo) {
                    $this->errors[$field] = "$field uniqueness check failed";
                    break;
                }

                try {
                    $sql = "SELECT COUNT(*) AS cnt FROM `{$table}` WHERE `{$column}` = :val";
                    $qParams = ['val' => $value];

                    if ($exceptId !== null && $exceptId !== '') {
                        $sql .= " AND `id` != :except";
                        $qParams['except'] = $exceptId;
                    }

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($qParams);
                    $row = $stmt->fetch();

                    if ($row && (int)($row['cnt'] ?? 0) > 0) {
                        $this->errors[$field] = "$field must be unique";
                    }
                    } catch (\Throwable $e) {
                        $this->errors[$field] = "$field uniqueness check failed";
                    }
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
                break;

            default:
                break;
        }
    }

    private static function pdo(): ?\PDO
    {
        if (self::$pdo instanceof \PDO) {
            return self::$pdo;
        }

        $envPath = __DIR__ . '/../../config/env.php';
        if (!file_exists($envPath)) {
            return null;
        }

        $env = require $envPath;

        try {
            $host = $env['DB_HOST'] ?? '127.0.0.1';
            $db   = $env['DB_NAME'] ?? '';
            $user = $env['DB_USER'] ?? 'root';
            $pass = $env['DB_PASS'] ?? '';

            $dsn = "mysql:host={$host};dbname={$db};charset=utf8mb4";
            self::$pdo = new \PDO($dsn, $user, $pass, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (\Throwable $e) {
            return null;
        }

        return self::$pdo;
    }
}
