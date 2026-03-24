<?php

namespace Pdaether\BlockWire\Tests\Fixtures\Parsers;

use Pdaether\BlockWire\Parsers\Html;

class CountingHtmlParser extends Html
{
    public static int $parseCount = 0;

    public static function reset(): void
    {
        self::$parseCount = 0;
    }

    public function parse(): static
    {
        self::$parseCount++;

        return parent::parse();
    }
}
