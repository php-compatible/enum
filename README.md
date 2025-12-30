# Enum

[![CI](https://github.com/php-compatible/enum/actions/workflows/ci.yml/badge.svg)](https://github.com/php-compatible/enum/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/php-compatible/enum/branch/main/graph/badge.svg)](https://codecov.io/gh/php-compatible/enum)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://github.com/php-compatible/enum/blob/main/LICENSE)

[![PHP 7.2](https://img.shields.io/badge/PHP-7.2-8892BF)](https://github.com/php-compatible/enum/actions/workflows/ci.yml)
[![PHP 7.3](https://img.shields.io/badge/PHP-7.3-8892BF)](https://github.com/php-compatible/enum/actions/workflows/ci.yml)
[![PHP 7.4](https://img.shields.io/badge/PHP-7.4-8892BF)](https://github.com/php-compatible/enum/actions/workflows/ci.yml)
[![PHP 8.0](https://img.shields.io/badge/PHP-8.0-8892BF)](https://github.com/php-compatible/enum/actions/workflows/ci.yml)
[![PHP 8.1](https://img.shields.io/badge/PHP-8.1-8892BF)](https://github.com/php-compatible/enum/actions/workflows/ci.yml)
[![PHP 8.2](https://img.shields.io/badge/PHP-8.2-8892BF)](https://github.com/php-compatible/enum/actions/workflows/ci.yml)
[![PHP 8.3](https://img.shields.io/badge/PHP-8.3-8892BF)](https://github.com/php-compatible/enum/actions/workflows/ci.yml)
[![PHP 8.4](https://img.shields.io/badge/PHP-8.4-8892BF)](https://github.com/php-compatible/enum/actions/workflows/ci.yml)
[![PHP 8.5](https://img.shields.io/badge/PHP-8.5-8892BF)](https://github.com/php-compatible/enum/actions/workflows/ci.yml)

**PHP 8-style enums for PHP 7.2+** - Write enums today, upgrade to native enums tomorrow.

This library provides a PHP 8-compatible enum API that works on PHP 7.2 and above. When you're ready to upgrade to PHP 8.1+, use the included migration tool to automatically convert your enums to native PHP syntax.

## Why Use This Package?

- **Future-proof**: API mirrors PHP 8 enums (`from()`, `tryFrom()`, `cases()`)
- **Zero-friction migration**: Automated upgrade tool converts to native PHP 8 enums
- **IDE-friendly**: CLI tool auto-generates PHPDoc annotations for full autocompletion
- **Flexible**: Case-insensitive access, auto-increment values, string or int backing
- **Human-readable labels**: Built-in conversion from `camelCase` to `Camel Case`
- **Type-safe**: Strict type comparison for value lookups (matches PHP 8 behavior)
- **Well-tested**: 100% code coverage across PHP 7.2 - 8.5

## CLI Tools Included

| Command | Description |
|---------|-------------|
| `vendor/bin/php-compatible-enum-auto-doc` | Auto-generate `@method` PHPDoc annotations for IDE support |
| `vendor/bin/php-compatible-enum-upgrade-to-php8` | Migrate enums to native PHP 8.1+ syntax |

## Installation

```bash
composer require php-compatible/enum
```

## Documentation

Full documentation available at [phpcompatible.dev/docs/enum](https://phpcompatible.dev/docs/enum)

## Usage

### Basic Enum (Auto-increment)

Define enum cases as protected properties. Uninitialized properties auto-increment from 0:

```php
<?php

use PhpCompatible\Enum\Enum;
use PhpCompatible\Enum\Value;

/**
 * @method static Value hearts()
 * @method static Value diamonds()
 * @method static Value clubs()
 * @method static Value spades()
 */
class Suit extends Enum
{
    protected $hearts;   // 0
    protected $diamonds; // 1
    protected $clubs;    // 2
    protected $spades;   // 3
}

echo Suit::hearts()->name;  // "hearts"
echo Suit::hearts()->value; // 0
```

### Mixed Values

Combine auto-increment with explicit values:

```php
class Status extends Enum
{
    protected $draft;        // 0
    protected $pending;      // 1
    protected $published = 10;
    protected $archived;     // 11
}
```

### String Enum

```php
class Color extends Enum
{
    protected $red = 'red';
    protected $green = 'green';
    protected $blue = 'blue';
}

echo Color::red()->name;  // "red"
echo Color::red()->value; // "red"
```

### Case-Insensitive Access

Access enum cases using any naming convention:

```php
class Status extends Enum
{
    protected $pendingReview;
}

// All of these return the same Value instance:
Status::pendingReview();   // camelCase
Status::PendingReview();   // PascalCase
Status::PENDING_REVIEW();  // SCREAMING_SNAKE_CASE
Status::pending_review();  // snake_case

// The name property preserves the original definition:
Status::PENDING_REVIEW()->name; // "pendingReview"
```

### Getting All Cases

```php
foreach (Suit::cases() as $case) {
    echo $case->name . ': ' . $case->value . PHP_EOL;
}
// hearts: 0
// diamonds: 1
// clubs: 2
// spades: 3
```

### Looking Up by Value

Use `from()` and `tryFrom()` to get an enum case from its backing value (PHP 8 compatible):

```php
// from() throws an exception if the value doesn't exist
$case = Suit::from(0);
echo $case->name;  // "hearts"

// tryFrom() returns null if the value doesn't exist
$case = Suit::tryFrom(999);
var_dump($case);  // null

// Type-sensitive: integer 0 won't match string '0'
$case = Suit::tryFrom('0');
var_dump($case);  // null
```

Duplicate backing values are not allowed (matches PHP 8 behavior):

```php
// This will throw LogicException
class Invalid extends Enum
{
    protected $foo = 1;
    protected $bar = 1;  // Duplicate value!
}
```

### Human-Readable Labels

Use `EnumLabel` to convert enum names to human-readable labels:

```php
use PhpCompatible\Enum\EnumLabel;

class TaskStatus extends Enum
{
    protected $pendingReview;
    protected $inProgress;
    protected $onHold;
}

echo EnumLabel::from(TaskStatus::pendingReview()); // "Pending Review"
echo EnumLabel::from(TaskStatus::inProgress());    // "In Progress"
echo EnumLabel::from(TaskStatus::onHold());        // "On Hold"
```

`EnumLabel` handles various naming conventions:

| Input | Output |
|-------|--------|
| `camelCase` | `Camel Case` |
| `PascalCase` | `Pascal Case` |
| `snake_case` | `Snake Case` |
| `SCREAMING_SNAKE` | `Screaming Snake` |
| `ABCValue` | `ABC Value` |

Labels auto-convert to strings:

```php
$label = EnumLabel::from(TaskStatus::pendingReview());

echo "Status: $label";    // "Status: Pending Review"
echo $label->toString();  // "Pending Review"
```

## IDE Support

Add PHPDoc `@method` annotations for autocompletion:

```php
/**
 * @method static Value hearts()
 * @method static Value diamonds()
 * @method static Value clubs()
 * @method static Value spades()
 */
class Suit extends Enum
{
    protected $hearts;
    protected $diamonds;
    protected $clubs;
    protected $spades;
}
```

### Auto-generating Annotations

Use the `php-compatible-enum-auto-doc` CLI tool to automatically generate `@method` annotations:

```bash
# Scan src/ directory (default)
vendor/bin/php-compatible-enum-auto-doc

# Scan a specific directory
vendor/bin/php-compatible-enum-auto-doc app/Enums

# Preview changes without modifying files
vendor/bin/php-compatible-enum-auto-doc --dry-run

# Case style options for method names
vendor/bin/php-compatible-enum-auto-doc                      # camelCase (default): hearts()
vendor/bin/php-compatible-enum-auto-doc --pascal-case        # PascalCase: Hearts()
vendor/bin/php-compatible-enum-auto-doc --snake-case         # snake_case: hearts()
vendor/bin/php-compatible-enum-auto-doc --screaming-snake-case  # SCREAMING_SNAKE_CASE: HEARTS()
```

The tool scans PHP files for classes that use `PhpCompatible\Enum\Enum`, extracts protected properties, and updates the class docblock with appropriate `@method` annotations. Files are listed as they are updated.

### Upgrading to PHP 8 Native Enums

When you're ready to migrate to PHP 8.1+ native enums, use the `php-compatible-enum-upgrade-to-php8` CLI tool:

```bash
# Scan src/ directory (default)
vendor/bin/php-compatible-enum-upgrade-to-php8

# Scan a specific directory
vendor/bin/php-compatible-enum-upgrade-to-php8 app/Enums

# Preview changes without modifying files
vendor/bin/php-compatible-enum-upgrade-to-php8 --dry-run
```

The tool will:

1. **Convert enum class definitions** - Transform `class Status extends Enum` to `enum Status`
2. **Update case declarations** - Convert `protected $draft;` to `case draft;`
3. **Handle backed enums** - Preserve integer or string backing values
4. **Update usages** - Replace `Status::draft()` with `Status::draft` (removes parentheses)
5. **Swap EnumLabel** - Replace `EnumLabel::from()` with `Php8EnumLabel::from()`

**Before:**

```php
use PhpCompatible\Enum\Enum;
use PhpCompatible\Enum\EnumLabel;

class Status extends Enum
{
    protected $draft;
    protected $published = 10;
}

$status = Status::draft();
echo EnumLabel::from($status);
```

**After:**

```php
use PhpCompatible\Enum\Php8EnumLabel;

enum Status: int
{
    case draft = 0;
    case published = 10;
}

$status = Status::draft;
echo Php8EnumLabel::fromEnum($status);
```

## How It Works

- Enum cases are defined as `protected` instance properties
- Properties are immutable from outside the class
- `__callStatic` enables static-style access: `Suit::hearts()`
- A singleton instance is created internally for reflection
- Values are lazily loaded and cached on first access
- `null` (uninitialized) values auto-increment from 0
- Case-insensitive matching allows flexible access styles

## API Reference

### `Enum`

| Method | Returns | Description |
|--------|---------|-------------|
| `CaseName()` | `Value` | Get enum case (case-insensitive) |
| `cases()` | `Value[]` | Get all enum cases |
| `from($value)` | `Value` | Get case by value (throws if not found) |
| `tryFrom($value)` | `Value\|null` | Get case by value (null if not found) |

### `Value`

| Property | Type | Description |
|----------|------|-------------|
| `$name` | `string` | The enum case name (original definition) |
| `$value` | `int\|string\|null` | The enum case value |

### `EnumLabel`

| Method | Returns | Description |
|--------|---------|-------------|
| `from(Value $value)` | `EnumLabel` | Create label from enum value |
| `toString()` | `string` | Get label as string |
| `__toString()` | `string` | Auto string conversion |

### `Php8EnumLabel`

For PHP 8.1+ native enums (extends `EnumLabel`):

| Method | Returns | Description |
|--------|---------|-------------|
| `fromEnum(UnitEnum $case)` | `Php8EnumLabel` | Create label from PHP 8 enum case |
| `toString()` | `string` | Get label as string |
| `__toString()` | `string` | Auto string conversion |

```php
// PHP 8.1+
enum Status {
    case PendingReview;
    case InProgress;
}

echo Php8EnumLabel::fromEnum(Status::PendingReview); // "Pending Review"
```

## Requirements

- PHP 7.2 or higher

## License

MIT
