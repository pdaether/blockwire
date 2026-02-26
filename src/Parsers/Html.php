<?php

namespace Pdaether\BlockWire\Parsers;

use Illuminate\Support\Facades\Blade;
use Pdaether\BlockWire\Blocks\Block;

class Html extends Parser implements ParserInterface
{
    public function parse(): static
    {
        $content = $this->dropPlaceholderHtml();

        $blocks = collect($this->blocks)
            ->filter(fn (array $block) => $this->shouldRenderBlock($block))
            ->map(function ($block, $key) {
                $blockComponent = Block::fromName($block['class'])
                    ->data($block['data']);

                $view = $blockComponent->makeView();

                if ($this->context === 'editor') {
                    $view = $this->prepareBlockForEditor([
                        'id' => $key,
                        'blockHtml' => $view,
                        'title' => $blockComponent->getTitle(),
                        'show' => $block['show'] ?? true,
                    ]);
                }

                return Blade::render($view, $blockComponent->getData());
            })
            ->values();

        if (! $blocks->isEmpty()) {
            $content = $blocks->implode("\n");
        }

        $this->output = $this->createBaseView(['slot' => $content]);

        return $this;
    }
}
