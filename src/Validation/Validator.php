<?php

declare(strict_types=1);

namespace App\Validation;

/**
 * A small rule-based validator. Rules are pipe-separated strings, e.g.
 * "required|max:120". This is a convenience only on the client; every one
 * of these rules is re-checked here, server-side, before anything is saved.
 */
final class Validator
{
    /** @var array<string,string> */
    private array $errors = [];

    /**
     * @param array<string,mixed> $data
     * @param array<string,string> $rules
     */
    public function __construct(private array $data, private array $rules)
    {
    }

    /** @return array<string,string> field => error message */
    public function errors(): array
    {
        if ($this->errors === []) {
            $this->run();
        }

        return $this->errors;
    }

    public function passes(): bool
    {
        return $this->errors() === [];
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    private function run(): void
    {
        foreach ($this->rules as $field => $ruleString) {
            $value = $this->data[$field] ?? null;
            $rules = explode('|', $ruleString);
            $isNullable = in_array('nullable', $rules, true);

            if ($isNullable && ($value === null || $value === '')) {
                continue;
            }

            foreach ($rules as $rule) {
                if ($rule === 'nullable') {
                    continue;
                }
                if ($this->checkRule($field, $value, $rule) === false) {
                    break; // one message per field is enough
                }
            }
        }
    }

    private function checkRule(string $field, mixed $value, string $rule): bool
    {
        [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);

        $ok = match ($name) {
            'required' => $value !== null && $value !== '',
            'email' => is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'numeric' => is_numeric($value),
            'integer' => is_numeric($value) && (string) (int) $value === (string) $value,
            'date' => $this->isValidDate((string) $value),
            'max' => mb_strlen((string) $value) <= (int) $param,
            'min' => mb_strlen((string) $value) >= (int) $param,
            'max_value' => (float) $value <= (float) $param,
            'min_value' => (float) $value >= (float) $param,
            'in' => in_array((string) $value, explode(',', (string) $param), true),
            'between' => $this->between((float) $value, (string) $param),
            default => true,
        };

        if (!$ok) {
            $this->errors[$field] = $this->message($field, $name, $param);
        }

        return $ok;
    }

    private function between(float $value, string $param): bool
    {
        [$min, $max] = array_map('floatval', explode(',', $param));

        return $value >= $min && $value <= $max;
    }

    private function isValidDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private function message(string $field, ?string $rule, ?string $param): string
    {
        $label = ucfirst(str_replace('_', ' ', $field));

        return match ($rule) {
            'required' => "{$label} is required.",
            'email' => "{$label} must be a valid email address.",
            'numeric' => "{$label} must be a number.",
            'integer' => "{$label} must be a whole number.",
            'date' => "{$label} must be a valid date (YYYY-MM-DD).",
            'max' => "{$label} must be {$param} characters or fewer.",
            'min' => "{$label} must be at least {$param} characters.",
            'max_value' => "{$label} must be {$param} or less.",
            'min_value' => "{$label} must be {$param} or more.",
            'in' => "{$label} must be one of: {$param}.",
            'between' => "{$label} must be between {$param}.",
            default => "{$label} is invalid.",
        };
    }
}
