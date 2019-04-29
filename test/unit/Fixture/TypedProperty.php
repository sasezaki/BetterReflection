<?php

namespace Roave\BetterReflectionTest\Fixture;

use DateTimeImmutable;

class TypedProperty
{
    public $nonTyped;

    public int $number;
    public ?int $nullableNumber;

    public DateTimeImmutable $date;
}
