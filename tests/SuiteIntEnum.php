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
    protected $Hearts;      // 0 (auto)
    protected $Diamonds;    // 1 (auto)
    protected $Clubs;       // 2 (auto)
    protected $Spades;      // 3 (auto)
    protected $Joker = 100; // explicit
}
