# enum

Create PHP 8-style enums compatible with PHP 7.2+.

## Installation

```bash
composer require php-compatible/enum
```

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

## Requirements

- PHP 7.2 or higher

## License

MIT
