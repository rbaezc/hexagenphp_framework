<?php
namespace HexaGen\Core\Validation;

use HexaGen\Core\Database\DatabaseConnection;

class Validator
{
    private array $errors = [];

    public function validate(array $data, array $rules, array $messages = [], array $attributes = []): bool
    {
        $this->errors = [];

        foreach ($rules as $field => $fieldRules) {
            $label    = $attributes[$field] ?? $field;
            $value    = $data[$field] ?? null;
            $ruleList = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;

            // 'sometimes' — skip entire field if not present in data
            if (in_array('sometimes', $ruleList, true) && !array_key_exists($field, $data)) {
                continue;
            }

            // 'nullable' — if empty, skip remaining rules
            $nullable = in_array('nullable', $ruleList, true);

            foreach ($ruleList as $rule) {
                if ($rule === 'sometimes' || $rule === 'nullable') {
                    continue;
                }

                if ($rule === 'required') {
                    if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                        $this->addError($field, 'required', "El campo $label es obligatorio.", $messages);
                        break;
                    }
                    continue;
                }

                // If empty and nullable, skip remaining rules
                if (($value === null || $value === '') && $nullable) {
                    break;
                }

                // Skip non-required rules on empty optional fields
                if ($value === null || $value === '') {
                    continue;
                }

                match (true) {
                    $rule === 'email'    => $this->validateEmail($field, $label, $value, $messages),
                    $rule === 'numeric'  => $this->validateNumeric($field, $label, $value, $messages),
                    $rule === 'integer'  => $this->validateInteger($field, $label, $value, $messages),
                    $rule === 'string'   => $this->validateString($field, $label, $value, $messages),
                    $rule === 'array'    => $this->validateArray($field, $label, $value, $messages),
                    $rule === 'boolean'  => $this->validateBoolean($field, $label, $value, $messages),
                    $rule === 'url'      => $this->validateUrl($field, $label, $value, $messages),
                    $rule === 'ip'       => $this->validateIp($field, $label, $value, $messages),
                    $rule === 'ipv4'     => $this->validateIpv4($field, $label, $value, $messages),
                    $rule === 'ipv6'     => $this->validateIpv6($field, $label, $value, $messages),
                    $rule === 'uuid'     => $this->validateUuid($field, $label, $value, $messages),
                    $rule === 'json'     => $this->validateJson($field, $label, $value, $messages),
                    $rule === 'accepted' => $this->validateAccepted($field, $label, $value, $messages),
                    $rule === 'confirmed'=> $this->validateConfirmed($field, $label, $value, $data, $messages),
                    $rule === 'date'     => $this->validateDate($field, $label, $value, 'Y-m-d', $messages),
                    str_starts_with($rule, 'min:')          => $this->validateMin($field, $label, $value, (int)substr($rule, 4), $messages),
                    str_starts_with($rule, 'max:')          => $this->validateMax($field, $label, $value, (int)substr($rule, 4), $messages),
                    str_starts_with($rule, 'size:')         => $this->validateSize($field, $label, $value, (int)substr($rule, 5), $messages),
                    str_starts_with($rule, 'between:')      => $this->validateBetween($field, $label, $value, substr($rule, 8), $messages),
                    str_starts_with($rule, 'digits:')       => $this->validateDigits($field, $label, $value, (int)substr($rule, 7), $messages),
                    str_starts_with($rule, 'digits_between:')=> $this->validateDigitsBetween($field, $label, $value, substr($rule, 15), $messages),
                    str_starts_with($rule, 'in:')           => $this->validateIn($field, $label, $value, substr($rule, 3), $messages),
                    str_starts_with($rule, 'not_in:')       => $this->validateNotIn($field, $label, $value, substr($rule, 7), $messages),
                    str_starts_with($rule, 'regex:')        => $this->validateRegex($field, $label, $value, substr($rule, 6), $messages),
                    str_starts_with($rule, 'date:')         => $this->validateDate($field, $label, $value, substr($rule, 5), $messages),
                    str_starts_with($rule, 'after:')        => $this->validateAfter($field, $label, $value, substr($rule, 6), $messages),
                    str_starts_with($rule, 'before:')       => $this->validateBefore($field, $label, $value, substr($rule, 7), $messages),
                    str_starts_with($rule, 'same:')         => $this->validateSame($field, $label, $value, substr($rule, 5), $data, $messages),
                    str_starts_with($rule, 'different:')    => $this->validateDifferent($field, $label, $value, substr($rule, 10), $data, $messages),
                    str_starts_with($rule, 'starts_with:')  => $this->validateStartsWith($field, $label, $value, substr($rule, 12), $messages),
                    str_starts_with($rule, 'ends_with:')    => $this->validateEndsWith($field, $label, $value, substr($rule, 10), $messages),
                    str_starts_with($rule, 'required_if:')  => $this->validateRequiredIf($field, $label, $value, substr($rule, 12), $data, $messages),
                    str_starts_with($rule, 'unique:')       => $this->validateUnique($field, $label, $value, substr($rule, 7), $messages),
                    str_starts_with($rule, 'exists:')       => $this->validateExists($field, $label, $value, substr($rule, 7), $messages),
                    default => null,
                };
            }
        }

        return empty($this->errors);
    }

    private function addError(string $field, string $rule, string $default, array $messages): void
    {
        $key     = "{$field}.{$rule}";
        $message = $messages[$key] ?? $messages[$field] ?? $default;
        $this->errors[$field][] = $message;
    }

    private function validateEmail(string $f, string $l, mixed $v, array $m): void
    {
        if (!filter_var($v, FILTER_VALIDATE_EMAIL)) {
            $this->addError($f, 'email', "El campo $l debe ser un correo electrónico válido.", $m);
        }
    }

    private function validateNumeric(string $f, string $l, mixed $v, array $m): void
    {
        if (!is_numeric($v)) {
            $this->addError($f, 'numeric', "El campo $l debe ser un valor numérico.", $m);
        }
    }

    private function validateInteger(string $f, string $l, mixed $v, array $m): void
    {
        if (filter_var($v, FILTER_VALIDATE_INT) === false) {
            $this->addError($f, 'integer', "El campo $l debe ser un número entero.", $m);
        }
    }

    private function validateString(string $f, string $l, mixed $v, array $m): void
    {
        if (!is_string($v)) {
            $this->addError($f, 'string', "El campo $l debe ser texto.", $m);
        }
    }

    private function validateArray(string $f, string $l, mixed $v, array $m): void
    {
        if (!is_array($v)) {
            $this->addError($f, 'array', "El campo $l debe ser un arreglo.", $m);
        }
    }

    private function validateBoolean(string $f, string $l, mixed $v, array $m): void
    {
        if (!in_array($v, [true, false, 0, 1, '0', '1', 'true', 'false'], true)) {
            $this->addError($f, 'boolean', "El campo $l debe ser verdadero o falso.", $m);
        }
    }

    private function validateUrl(string $f, string $l, mixed $v, array $m): void
    {
        if (!filter_var($v, FILTER_VALIDATE_URL)) {
            $this->addError($f, 'url', "El campo $l debe ser una URL válida.", $m);
        }
    }

    private function validateIp(string $f, string $l, mixed $v, array $m): void
    {
        if (!filter_var($v, FILTER_VALIDATE_IP)) {
            $this->addError($f, 'ip', "El campo $l debe ser una dirección IP válida.", $m);
        }
    }

    private function validateIpv4(string $f, string $l, mixed $v, array $m): void
    {
        if (!filter_var($v, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $this->addError($f, 'ipv4', "El campo $l debe ser una dirección IPv4 válida.", $m);
        }
    }

    private function validateIpv6(string $f, string $l, mixed $v, array $m): void
    {
        if (!filter_var($v, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $this->addError($f, 'ipv6', "El campo $l debe ser una dirección IPv6 válida.", $m);
        }
    }

    private function validateUuid(string $f, string $l, mixed $v, array $m): void
    {
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', (string)$v)) {
            $this->addError($f, 'uuid', "El campo $l debe ser un UUID válido.", $m);
        }
    }

    private function validateJson(string $f, string $l, mixed $v, array $m): void
    {
        json_decode((string)$v);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->addError($f, 'json', "El campo $l debe ser una cadena JSON válida.", $m);
        }
    }

    private function validateAccepted(string $f, string $l, mixed $v, array $m): void
    {
        if (!in_array($v, ['yes', 'on', '1', 1, true, 'true'], true)) {
            $this->addError($f, 'accepted', "El campo $l debe ser aceptado.", $m);
        }
    }

    private function validateConfirmed(string $f, string $l, mixed $v, array $data, array $m): void
    {
        if ($v !== ($data[$f . '_confirmation'] ?? null)) {
            $this->addError($f, 'confirmed', "El campo $l no coincide con su confirmación.", $m);
        }
    }

    private function validateMin(string $f, string $l, mixed $v, int $min, array $m): void
    {
        $fail = is_numeric($v) ? ((float)$v < $min) : (mb_strlen((string)$v) < $min);
        if ($fail) {
            $this->addError($f, 'min', "El campo $l debe ser al menos $min.", $m);
        }
    }

    private function validateMax(string $f, string $l, mixed $v, int $max, array $m): void
    {
        $fail = is_numeric($v) ? ((float)$v > $max) : (mb_strlen((string)$v) > $max);
        if ($fail) {
            $this->addError($f, 'max', "El campo $l no debe superar $max.", $m);
        }
    }

    private function validateSize(string $f, string $l, mixed $v, int $size, array $m): void
    {
        $actual = is_array($v) ? count($v) : (is_numeric($v) ? (float)$v : mb_strlen((string)$v));
        if ($actual !== $size) {
            $this->addError($f, 'size', "El campo $l debe tener exactamente $size.", $m);
        }
    }

    private function validateBetween(string $f, string $l, mixed $v, string $params, array $m): void
    {
        [$min, $max] = array_map('floatval', explode(',', $params, 2));
        $actual = is_numeric($v) ? (float)$v : mb_strlen((string)$v);
        if ($actual < $min || $actual > $max) {
            $this->addError($f, 'between', "El campo $l debe estar entre $min y $max.", $m);
        }
    }

    private function validateDigits(string $f, string $l, mixed $v, int $n, array $m): void
    {
        if (!preg_match('/^\d{' . $n . '}$/', (string)$v)) {
            $this->addError($f, 'digits', "El campo $l debe tener exactamente $n dígitos.", $m);
        }
    }

    private function validateDigitsBetween(string $f, string $l, mixed $v, string $params, array $m): void
    {
        [$min, $max] = explode(',', $params, 2);
        if (!preg_match('/^\d{' . (int)$min . ',' . (int)$max . '}$/', (string)$v)) {
            $this->addError($f, 'digits_between', "El campo $l debe tener entre $min y $max dígitos.", $m);
        }
    }

    private function validateIn(string $f, string $l, mixed $v, string $params, array $m): void
    {
        $allowed = explode(',', $params);
        if (!in_array((string)$v, $allowed, true)) {
            $this->addError($f, 'in', "El campo $l debe ser uno de: " . implode(', ', $allowed) . ".", $m);
        }
    }

    private function validateNotIn(string $f, string $l, mixed $v, string $params, array $m): void
    {
        $forbidden = explode(',', $params);
        if (in_array((string)$v, $forbidden, true)) {
            $this->addError($f, 'not_in', "El valor del campo $l no está permitido.", $m);
        }
    }

    private function validateRegex(string $f, string $l, mixed $v, string $pattern, array $m): void
    {
        if (!preg_match($pattern, (string)$v)) {
            $this->addError($f, 'regex', "El campo $l no tiene el formato correcto.", $m);
        }
    }

    private function validateDate(string $f, string $l, mixed $v, string $format, array $m): void
    {
        $d = \DateTime::createFromFormat($format, (string)$v);
        if (!$d || $d->format($format) !== (string)$v) {
            $this->addError($f, 'date', "El campo $l no es una fecha válida (formato: $format).", $m);
        }
    }

    private function validateAfter(string $f, string $l, mixed $v, string $date, array $m): void
    {
        $after = strtotime($date);
        $val   = strtotime((string)$v);
        if ($val === false || $after === false || $val <= $after) {
            $this->addError($f, 'after', "El campo $l debe ser una fecha posterior a $date.", $m);
        }
    }

    private function validateBefore(string $f, string $l, mixed $v, string $date, array $m): void
    {
        $before = strtotime($date);
        $val    = strtotime((string)$v);
        if ($val === false || $before === false || $val >= $before) {
            $this->addError($f, 'before', "El campo $l debe ser una fecha anterior a $date.", $m);
        }
    }

    private function validateSame(string $f, string $l, mixed $v, string $other, array $data, array $m): void
    {
        if ($v !== ($data[$other] ?? null)) {
            $this->addError($f, 'same', "El campo $l debe coincidir con $other.", $m);
        }
    }

    private function validateDifferent(string $f, string $l, mixed $v, string $other, array $data, array $m): void
    {
        if ($v === ($data[$other] ?? null)) {
            $this->addError($f, 'different', "El campo $l debe ser diferente a $other.", $m);
        }
    }

    private function validateStartsWith(string $f, string $l, mixed $v, string $params, array $m): void
    {
        $prefixes = explode(',', $params);
        foreach ($prefixes as $prefix) {
            if (str_starts_with((string)$v, $prefix)) return;
        }
        $this->addError($f, 'starts_with', "El campo $l debe comenzar con uno de: $params.", $m);
    }

    private function validateEndsWith(string $f, string $l, mixed $v, string $params, array $m): void
    {
        $suffixes = explode(',', $params);
        foreach ($suffixes as $suffix) {
            if (str_ends_with((string)$v, $suffix)) return;
        }
        $this->addError($f, 'ends_with', "El campo $l debe terminar con uno de: $params.", $m);
    }

    private function validateRequiredIf(string $f, string $l, mixed $v, string $params, array $data, array $m): void
    {
        [$otherField, $otherValue] = explode(',', $params, 2);
        if (isset($data[$otherField]) && (string)$data[$otherField] === $otherValue) {
            if ($v === null || $v === '') {
                $this->addError($f, 'required_if', "El campo $l es obligatorio cuando $otherField es $otherValue.", $m);
            }
        }
    }

    private function validateUnique(string $f, string $l, mixed $v, string $params, array $m): void
    {
        $parts  = explode(',', $params);
        $table  = $parts[0] ?? '';
        $column = $parts[1] ?? $f;
        $except = $parts[2] ?? null; // ID to ignore (for updates)
        $exceptColumn = $parts[3] ?? 'id';

        if (!$table || !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $table)
            || !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $column)) {
            $this->addError($f, 'unique', "Configuración de unique inválida para $l.", $m);
            return;
        }

        try {
            $pdo  = (new DatabaseConnection())->getPdo();
            $sql  = "SELECT COUNT(*) FROM `{$table}` WHERE `{$column}` = :val";
            $bind = [':val' => $v];

            if ($except !== null && preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $exceptColumn)) {
                $sql   .= " AND `{$exceptColumn}` != :except";
                $bind[':except'] = $except;
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($bind);
            if ((int)$stmt->fetchColumn() > 0) {
                $this->addError($f, 'unique', "El valor para el campo $l ya está registrado.", $m);
            }
        } catch (\Throwable) {}
    }

    private function validateExists(string $f, string $l, mixed $v, string $params, array $m): void
    {
        $parts  = explode(',', $params);
        $table  = $parts[0] ?? '';
        $column = $parts[1] ?? $f;

        if (!$table || !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $table)
            || !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $column)) {
            $this->addError($f, 'exists', "Configuración de exists inválida para $l.", $m);
            return;
        }

        try {
            $pdo  = (new DatabaseConnection())->getPdo();
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM `{$table}` WHERE `{$column}` = :val");
            $stmt->execute([':val' => $v]);
            if ((int)$stmt->fetchColumn() === 0) {
                $this->addError($f, 'exists', "El valor seleccionado para $l no es válido.", $m);
            }
        } catch (\Throwable) {}
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
