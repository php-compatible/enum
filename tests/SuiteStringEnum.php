<?php

namespace tests;

use PhpCompatible\Enum\Enum;
use PhpCompatible\Enum\Value;

/**
 * @method static Value Hearts()
 * @method static Value Diamonds()
 * @method static Value Clubs()
 * @method static Value Spades()
 */
class SuiteStringEnum extends Enum
{
    protected $Hearts = 'Hearts';
    protected $Diamonds = 'Diamonds';
    protected $Clubs = 'Clubs';
    protected $Spades = 'Spades';
}
