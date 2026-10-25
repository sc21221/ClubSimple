<?php

declare(strict_types=1);

namespace Clubmanager\Core;

/**
 * Input Validation Service
 *
 * Provides data validation rules including strict IBAN (Mod-97) and BIC checking.
 *
 * @package Clubmanager\Core
 * @author  Reinhard Schiessl
 */
class Validator
{
    /**
     * @var array<string, array<int, string>> Validation errors
     */
    protected array $errors = [];

    /**
     * @var array<string, mixed> Data under validation
     */
    protected array $data = [];

    /**
     * Validate data against specified rules.
     *
     * @param array<string, mixed> $data
     * @param array<string, string|array<int, string>> $rules
     * @return static
     */
    public static function make(array $data, array $rules): static
    {
        $validator = new static();
        $validator->data = $data;
        $validator->validate($rules);
        return $validator;
    }

    /**
     * Check if validation passed.
     *
     * @return bool
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Check if validation failed.
     *
     * @return bool
     */
    public function fails(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Get all validation errors.
     *
     * @return array<string, array<int, string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Get first error for a field or null.
     *
     * @param string $field
     * @return string|null
     */
    public function first(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    /**
     * Perform rule validation.
     *
     * @param array<string, string|array<int, string>> $rules
     * @return void
     */
    protected function validate(array $rules): void
    {
        foreach ($rules as $field => $ruleSet) {
            $rulesList = is_array($ruleSet) ? $ruleSet : explode('|', $ruleSet);
            $value = $this->data[$field] ?? null;

            foreach ($rulesList as $rule) {
                $params = [];
                if (str_contains($rule, ':')) {
                    [$ruleName, $paramStr] = explode(':', $rule, 2);
                    $params = explode(',', $paramStr);
                } else {
                    $ruleName = $rule;
                }

                $this->applyRule($field, $value, trim($ruleName), $params);
            }
        }
    }

    /**
     * Apply a specific rule.
     *
     * @param string $field
     * @param mixed $value
     * @param string $rule
     * @param array<int, string> $params
     * @return void
     */
    protected function applyRule(string $field, mixed $value, string $rule, array $params): void
    {
        $valStr = is_string($value) ? trim($value) : $value;
        $isEmpty = $valStr === null || $valStr === '';

        if ($rule === 'required' && $isEmpty) {
            $this->addError($field, "Das Feld {$field} ist erforderlich.");
            return;
        }

        // If field is empty and not required, skip further checks
        if ($isEmpty) {
            return;
        }

        switch ($rule) {
            case 'email':
                if (!filter_var($valStr, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "Das Feld {$field} muss eine gültige E-Mail-Adresse sein.");
                }
                break;

            case 'numeric':
                if (!is_numeric($valStr)) {
                    $this->addError($field, "Das Feld {$field} muss eine Zahl sein.");
                }
                break;

            case 'date':
                if (!strtotime((string) $valStr)) {
                    $this->addError($field, "Das Feld {$field} muss ein gültiges Datum sein.");
                }
                break;

            case 'min':
                $min = (int) ($params[0] ?? 0);
                if (is_numeric($valStr)) {
                    if ((float)$valStr < $min) {
                        $this->addError($field, "Das Feld {$field} muss mindestens {$min} sein.");
                    }
                } elseif (mb_strlen((string)$valStr) < $min) {
                    $this->addError($field, "Das Feld {$field} muss mindestens {$min} Zeichen lang sein.");
                }
                break;

            case 'max':
                $max = (int) ($params[0] ?? 255);
                if (is_numeric($valStr)) {
                    if ((float)$valStr > $max) {
                        $this->addError($field, "Das Feld {$field} darf höchstens {$max} sein.");
                    }
                } elseif (mb_strlen((string)$valStr) > $max) {
                    $this->addError($field, "Das Feld {$field} darf maximal {$max} Zeichen haben.");
                }
                break;

            case 'in':
                if (!in_array((string) $valStr, $params, true)) {
                    $this->addError($field, "Auswahl für {$field} ist ungültig.");
                }
                break;

            case 'iban':
                if (!static::validateIban((string) $valStr)) {
                    $this->addError($field, "Die eingegebene IBAN ist ungültig.");
                }
                break;

            case 'bic':
                if (!static::validateBic((string) $valStr)) {
                    $this->addError($field, "Die eingegebene BIC ist ungültig.");
                }
                break;
        }
    }

    /**
     * Add validation error message.
     *
     * @param string $field
     * @param string $message
     * @return void
     */
    protected function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    /**
     * Validate IBAN with ISO 7064 Modulo 97-10 check.
     *
     * @param string $iban
     * @return bool
     */
    public static function validateIban(string $iban): bool
    {
        $clean = strtoupper(preg_replace('/[\s-]+/', '', $iban));
        if (strlen($clean) < 15 || strlen($clean) > 34) {
            return false;
        }

        // Move first 4 characters to the end
        $reordered = substr($clean, 4) . substr($clean, 0, 4);

        // Convert letters to numbers (A=10, B=11, ... Z=35)
        $numericStr = '';
        $len = strlen($reordered);
        for ($i = 0; $i < $len; $i++) {
            $char = $reordered[$i];
            if (ctype_alpha($char)) {
                $numericStr .= (ord($char) - 55);
            } elseif (ctype_digit($char)) {
                $numericStr .= $char;
            } else {
                return false;
            }
        }

        // Perform modulo 97 operation on large number string
        $remainder = 0;
        $chunkSize = 7;
        for ($i = 0; $i < strlen($numericStr); $i += $chunkSize) {
            $part = (string) $remainder . substr($numericStr, $i, $chunkSize);
            $remainder = ((int) $part) % 97;
        }

        return $remainder === 1;
    }

    /**
     * Validate SWIFT / BIC code format (8 or 11 alphanumeric characters).
     *
     * @param string $bic
     * @return bool
     */
    public static function validateBic(string $bic): bool
    {
        $clean = strtoupper(trim($bic));
        return (bool) preg_match('/^[A-Z]{4}[A-Z]{2}[A-Z0-9]{2}([A-Z0-9]{3})?$/', $clean);
    }
}
