<?php

namespace Pdaether\BlockWire\Parsers;

use Illuminate\Support\Facades\Blade;
use Pdaether\BlockWire\Blocks\Block;

class Html extends Parser implements ParserInterface
{
    public function parse()
    {
        $content = $this->dropPlaceholderHtml();

        $blocks = collect($this->blocks)
            ->map(function ($block) {
                return Block::fromName($block['class'])
                    ->data($block['data']);
            })
            ->map(function ($block, $key) {
                $view = $block->makeView();

                if ($this->context === 'editor') {
                    $view = $this->prepareBlockForEditor(['id' => $key, 'blockHtml' => $view, 'title' => $block->getTitle()]);
                }

                return Blade::render($view, $block->getData());
            })
            ->values();

        if (! $blocks->isEmpty()) {
            $content = $blocks->implode("\n");
        }

        $this->output = $this->createBaseView(['slot' => $content]);

        return $this;
    }
}
