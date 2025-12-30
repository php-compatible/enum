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
 * echo Php8EnumLabel::fromEnum(Status::PendingReview); // "Pending Review"
 */
class Php8EnumLabel extends EnumLabel
{
    /**
     * Create a label from a PHP 8 native enum case.
     *
     * @param \UnitEnum $case The enum case to create a label from
     * @return self
     */
    public static function fromEnum(\UnitEnum $case): self
    {
        return new self(static::humanize($case->name));
    }
}
