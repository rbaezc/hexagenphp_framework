<?php
namespace HexaGen\Core\Validation;

use HexaGen\Core\Database\DatabaseConnection;

class Validator
{
    private array $errors = [];

    /**
     * Validate data against specified rules.
     *
     * @param array $data Input data
     * @param array $rules Rule list (e.g. ['email' => 'required|email|unique:users,email'])
     * @return bool True if validation passes, false otherwise
     */
    public function validate(array $data, array $rules): bool
    {
        $this->errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            $ruleList = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;

            foreach ($ruleList as $rule) {
                // 1. Required rule
                if ($rule === 'required') {
                    if ($value === null || $value === '') {
                        $this->errors[$field][] = "El campo $field es obligatorio.";
                        break; // If field is missing and required, skip other rules
                    }
                }

                if ($value === null || $value === '') {
                    continue; // Skip validation for optional fields that are empty
                }

                // 2. Email rule
                if ($rule === 'email') {
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $this->errors[$field][] = "El campo $field debe ser un correo electrónico válido.";
                    }
                }

                // 3. Min rule
                elseif (str_starts_with($rule, 'min:')) {
                    $min = (int)substr($rule, 4);
                    if (is_numeric($value)) {
                        if ($value < $min) {
                            $this->errors[$field][] = "El campo $field debe ser al menos $min.";
                        }
                    } else {
                        if (strlen((string)$value) < $min) {
                            $this->errors[$field][] = "El campo $field debe tener al menos $min caracteres.";
                        }
                    }
                }

                // 4. Max rule
                elseif (str_starts_with($rule, 'max:')) {
                    $max = (int)substr($rule, 4);
                    if (is_numeric($value)) {
                        if ($value > $max) {
                            $this->errors[$field][] = "El campo $field no debe superar $max.";
                        }
                    } else {
                        if (strlen((string)$value) > $max) {
                            $this->errors[$field][] = "El campo $field no debe superar los $max caracteres.";
                        }
                    }
                }

                // 5. Numeric rule
                elseif ($rule === 'numeric') {
                    if (!is_numeric($value)) {
                        $this->errors[$field][] = "El campo $field debe ser un valor numérico.";
                    }
                }

                // 6. Unique database rule (unique:table,column)
                elseif (str_starts_with($rule, 'unique:')) {
                    $paramStr = substr($rule, 7);
                    $parts = explode(',', $paramStr);
                    $table = $parts[0] ?? '';
                    $column = $parts[1] ?? $field;

                    if ($table !== '') {
                        try {
                            $db = new DatabaseConnection();
                            $pdo = $db->getPdo();
                            
                            // Safe query checking count
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM `{$table}` WHERE `{$column}` = :val");
                            $stmt->execute([':val' => $value]);
                            
                            if ((int)$stmt->fetchColumn() > 0) {
                                $this->errors[$field][] = "El valor para el campo $field ya está registrado.";
                            }
                        } catch (\Throwable $e) {
                            // Suppress database connection exception or table missing warnings
                        }
                    }
                }
            }
        }

        return empty($this->errors);
    }

    /**
     * Get array of validation errors.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
