<?php

namespace tests;

use PhpCompatible\Enum\Enum;
use PhpCompatible\Enum\Value;

/**
 * @method static Value Hearts()
 * @method static Value Diamonds()
 * @method static Value Clubs()
 * @method static Value Spades()
 * @method static Value Joker()
 */
class SuiteIntEnum extends Enum
{
    const Hearts = Value::AUTO; // test auto incrementing
    const Diamonds = Value::AUTO;
    const Clubs = Value::AUTO;
    const Spades = Value::AUTO;

    const Joker = 100; // test when user also specifies a const value
}