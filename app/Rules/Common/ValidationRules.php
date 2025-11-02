<?php

namespace App\Rules\Common;

use Illuminate\Validation\Rule;

/**
 * Common validation rules used across the application
 * Promotes DRY principle by centralizing reusable validation logic
 */
class ValidationRules
{
    /**
     * Email validation rules with optional uniqueness check
     */
    public static function email(bool $unique = false, ?int $ignoreId = null): array
    {
        $rules = ['email', 'max:255'];

        if ($unique) {
            $uniqueRule = Rule::unique('users', 'email');
            if ($ignoreId) {
                $uniqueRule->ignore($ignoreId);
            }
            $rules[] = $uniqueRule;
        }

        return $rules;
    }

    /**
     * Password validation rules
     */
    public static function password(bool $confirmed = true): array
    {
        $rules = ['string', 'min:8', 'max:255'];

        if ($confirmed) {
            $rules[] = 'confirmed';
        }

        return $rules;
    }

    /**
     * Name validation rules (first name, last name, etc.)
     */
    public static function name(): string
    {
        return 'string|max:255';
    }

    /**
     * Notes/Description validation rules
     */
    public static function notes(bool $nullable = true): string
    {
        return ($nullable ? 'nullable|' : '') . 'string|max:1000';
    }

    /**
     * Long text validation rules
     */
    public static function longText(bool $nullable = true): string
    {
        return ($nullable ? 'nullable|' : '') . 'string|max:5000';
    }

    /**
     * Duration validation rules (in minutes)
     */
    public static function duration(int $min = 1, int $max = 600): string
    {
        return "integer|min:{$min}|max:{$max}";
    }

    /**
     * Calories validation rules
     */
    public static function calories(int $min = 1, int $max = 5000): string
    {
        return "integer|min:{$min}|max:{$max}";
    }

    /**
     * Rating validation rules (1-5 stars)
     */
    public static function rating(): string
    {
        return 'integer|min:1|max:5';
    }

    /**
     * Weight validation rules (in kg)
     */
    public static function weight(int $min = 1, int $max = 500): string
    {
        return "numeric|min:{$min}|max:{$max}";
    }

    /**
     * Height validation rules (in cm)
     */
    public static function height(int $min = 50, int $max = 300): string
    {
        return "integer|min:{$min}|max:{$max}";
    }

    /**
     * Age validation rules
     */
    public static function age(int $min = 13, int $max = 120): string
    {
        return "integer|min:{$min}|max:{$max}";
    }

    /**
     * Date validation rules
     */
    public static function date(bool $nullable = false): string
    {
        return ($nullable ? 'nullable|' : '') . 'date';
    }

    /**
     * Boolean validation rules
     */
    public static function boolean(bool $nullable = false): string
    {
        return ($nullable ? 'nullable|' : '') . 'boolean';
    }

    /**
     * Gender validation rules
     */
    public static function gender(): array
    {
        return ['string', Rule::in(['male', 'female', 'other'])];
    }

    /**
     * Activity level validation rules
     */
    public static function activityLevel(): array
    {
        return ['string', Rule::in(['sedentary', 'light', 'moderate', 'active', 'very_active'])];
    }

    /**
     * Fitness goal validation rules
     */
    public static function fitnessGoal(): array
    {
        return ['string', Rule::in(['lose_weight', 'maintain', 'gain_muscle', 'improve_fitness'])];
    }

    /**
     * Difficulty level validation rules
     */
    public static function difficulty(): array
    {
        return ['string', Rule::in(['beginner', 'intermediate', 'advanced'])];
    }

    /**
     * Positive integer validation
     */
    public static function positiveInteger(): string
    {
        return 'integer|min:1';
    }

    /**
     * Non-negative integer validation
     */
    public static function nonNegativeInteger(): string
    {
        return 'integer|min:0';
    }

    /**
     * Percentage validation (0-100)
     */
    public static function percentage(): string
    {
        return 'numeric|min:0|max:100';
    }

    /**
     * URL validation
     */
    public static function url(bool $nullable = true): string
    {
        return ($nullable ? 'nullable|' : '') . 'url|max:255';
    }

    /**
     * Phone number validation
     */
    public static function phone(bool $nullable = true): string
    {
        return ($nullable ? 'nullable|' : '') . 'string|max:20';
    }

    /**
     * JSON validation
     */
    public static function json(bool $nullable = true): string
    {
        return ($nullable ? 'nullable|' : '') . 'json';
    }

    /**
     * Array validation
     */
    public static function array(bool $nullable = false): string
    {
        return ($nullable ? 'nullable|' : '') . 'array';
    }

    /**
     * Exists validation (check if ID exists in table)
     */
    public static function exists(string $table, string $column = 'id'): array
    {
        return [Rule::exists($table, $column)];
    }
}
