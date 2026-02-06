<?php

namespace Pdaether\BlockWire\Parsers;

class Parse
{
    public static function execute(array $properties): string
    {
        $output = '';

        foreach ($properties['parsers'] as $parser) {
            $output = (new $parser($output, $properties['activeBlocks']))
                ->base($properties['base'])
                ->context($properties['context'])
                ->parse()
                ->output();
        }

        return $output;
    }
}
