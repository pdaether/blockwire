<?php

namespace Pdaether\BlockWire\Parsers;

interface ParserInterface
{
    public function base(string $base): static;

    public function context(string $context): static;

    public function parse(): static;

    public function output(): string;
}
