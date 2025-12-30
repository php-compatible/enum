# enum

Create PHP 8-style enums compatible with PHP 7.2+.

## Installation

```bash
composer require php-compatible/enum
```

## Usage

### Integer Enum (Auto-increment)

```php
<?php

use PhpCompatible\Enum\Enum;
use PhpCompatible\Enum\Value;

/**
 * @method static Value Hearts()
 * @method static Value Diamonds()
 * @method static Value Clubs()
 * @method static Value Spades()
 */
class Suit extends Enum
{
    const Hearts = Value::AUTO;   // 0
    const Diamonds = Value::AUTO; // 1
    const Clubs = Value::AUTO;    // 2
    const Spades = Value::AUTO;   // 3
}

// Access enum cases
$hearts = Suit::Hearts();

echo $hearts->name;  // "Hearts"
echo $hearts->value; // 0
```

### Integer Enum (Explicit Values)

```php
<?php

use PhpCompatible\Enum\Enum;
use PhpCompatible\Enum\Value;

/**
 * @method static Value Draft()
 * @method static Value Published()
 * @method static Value Archived()
 */
class Status extends Enum
{
    const Draft = 10;
    const Published = 20;
    const Archived = 30;
}

echo Status::Published()->value; // 20
```

### String Enum

```php
<?php

use PhpCompatible\Enum\Enum;
use PhpCompatible\Enum\Value;

/**
 * @method static Value Red()
 * @method static Value Green()
 * @method static Value Blue()
 */
class Color extends Enum
{
    const Red = 'red';
    const Green = 'green';
    const Blue = 'blue';
}

echo Color::Red()->name;  // "Red"
echo Color::Red()->value; // "red"
```

### Mixed Values (Auto-increment with Explicit)

```php
<?php

use PhpCompatible\Enum\Enum;
use PhpCompatible\Enum\Value;

/**
 * @method static Value Hearts()
 * @method static Value Diamonds()
 * @method static Value Clubs()
 * @method static Value Spades()
 * @method static Value Joker()
 */
class Card extends Enum
{
    const Hearts = Value::AUTO;   // 0
    const Diamonds = Value::AUTO; // 1
    const Clubs = Value::AUTO;    // 2
    const Spades = Value::AUTO;   // 3
    const Joker = 100;            // 100
}
```

## IDE Support

Add PHPDoc `@method` annotations to your enum class for IDE autocompletion:

```php
/**
 * @method static Value Hearts()
 * @method static Value Diamonds()
 */
class Suit extends Enum
{
    // ...
}
```

## Requirements

- PHP 7.2 or higher

## License

MIT
