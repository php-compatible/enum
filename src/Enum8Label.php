<?php

namespace PhpCompatible\Enum;

/**
 * Converts PHP 8 native enum case names to human-readable labels.
 *
 * Requires PHP 8.1+
 *
 * Handles various naming conventions:
 * - snake_case: `A_VALUE` → `A Value`
 * - camelCase: `aValue` → `A Value`
 * - PascalCase: `AValue` → `A Value`
 * - Acronyms: `ABCValue` → `ABC Value`
 *
 * @example
 * enum Status {
 *     case PendingReview;
 *     case InProgress;
 * }
 *
 * echo Enum8Label::from(Status::PendingReview); // "Pending Review"
 */
class Enum8Label
{
    /**
     * @var string
     */
    private $label;

    /**
     * @param string $label
     */
    private function __construct(string $label)
    {
        $this->label = $label;
    }

    /**
     * Create a label from a PHP 8 native enum case.
     *
     * @param \UnitEnum $case The enum case to create a label from
     * @return self
     */
    public static function from(\UnitEnum $case): self
    {
        return new self(self::humanize($case->name));
    }

    /**
     * Convert a name to a human-readable label.
     *
     * @param string $name
     * @return string
     */
    private static function humanize(string $name): string
    {
        // Handle snake_case: split by underscore and title case
        if (strpos($name, '_') !== false) {
            $words = explode('_', $name);
            $words = array_map(function ($word) {
                return ucfirst(strtolower($word));
            }, $words);
            return implode(' ', $words);
        }

        // Handle camelCase/PascalCase
        $result = $name;

        // Insert space before consecutive uppercase followed by lowercase (e.g., ABCValue -> ABC Value)
        $result = preg_replace('/([A-Z]+)([A-Z][a-z])/', '$1 $2', $result);

        // Insert space between lowercase and uppercase (e.g., aValue -> a Value)
        $result = preg_replace('/([a-z])([A-Z])/', '$1 $2', $result);

        // Title case each word, preserving uppercase acronyms
        $words = explode(' ', trim($result));
        $words = array_map(function ($word) {
            // If word is all uppercase (acronym), keep it
            if (preg_match('/^[A-Z]+$/', $word)) {
                return $word;
            }
            // Otherwise, title case it
            return ucfirst(strtolower($word));
        }, $words);

        return implode(' ', $words);
    }

    /**
     * Convert to string automatically.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->label;
    }

    /**
     * Get the label as a string.
     *
     * @return string
     */
    public function toString(): string
    {
        return $this->label;
    }
}
