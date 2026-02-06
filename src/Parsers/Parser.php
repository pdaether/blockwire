<?php

namespace Pdaether\DropBlockEditor\Parsers;

use Illuminate\Support\Facades\Blade;

abstract class Parser
{
    public $output;

    public $context = 'editor';

    public $base;

    public $blockArguments = [];

    public function __construct(public $input, public $blocks = [])
    {
        //
    }

    public function parse()
    {
        return $this;
    }

    public function base($base)
    {
        $this->base = $base;

        return $this;
    }

    public function context($context)
    {
        $this->context = $context;

        return $this;
    }

    public function output()
    {
        return $this->output;
    }

    public function setBlockArguments($key, $value, $method = null)
    {
        if ($method) {
            $this->blockArguments[$key][$method] = $value;
        } else {
            $this->blockArguments[$key] = $value;
        }
    }

    public function prepareBlockForEditor(array $args)
    {
        $activeBorderColor = config('dropblockeditor.brand.colors.active_border', 'blue-500');

        $colorMap = [
            'blue-500' => '#3b82f6',
            'red-500' => '#ef4444',
            'green-500' => '#22c55e',
            'yellow-500' => '#eab308',
            'purple-500' => '#a855f7',
            'pink-500' => '#ec4899',
            'indigo-500' => '#6366f1',
            'gray-500' => '#6b7280',
            'orange-500' => '#f97316',
            'teal-500' => '#14b8a6',
        ];

        $borderColor = $colorMap[$activeBorderColor] ?? '#3b82f6';

        $args = collect([
            'blockHtml' => $args['blockHtml'],
            'id' => $args['id'],
            'before' => '<div drag-item draggable="true" class="[&_*]:pointer-events-none relative hover:opacity-75 hover:cursor-pointer before:opacity-0 hover:before:opacity-100 before:absolute before:top-0 before:left-0 before:w-full before:h-full before:border-2 before:border-gray-400 after:opacity-0 after:absolute after:bg-gray-400 after:left-0 after:w-full [&.active]:before:opacity-100" style="--active-border-color: '.$borderColor.'" data-block="'.$args['id'].'">',
            'actions' => '<div class="block-actions"><button class="action-clone" title="Clone" aria-label="Clone"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75" /></svg></button><button class="action-delete" title="Delete" aria-label="Delete"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button></div><span class="block-label">'.($args['title'] ?? 'Block').'</span>',
            'after' => '</div>',
            'wrap' => [],
        ])
            ->map(function ($value, $key) {
                if (! isset($this->blockArguments[$key])) {
                    return $value;
                }

                if (isset($this->blockArguments[$key]['prepend'])) {
                    return "{$this->blockArguments[$key]['prepend']} {$value}";
                }

                if (isset($this->blockArguments[$key]['append'])) {
                    return "{$value} {$this->blockArguments[$key]['append']}";
                }

                if (isset($this->blockArguments[$key]['wrap'])) {
                    return "{$this->blockArguments[$key]['wrap']['before']} {$value} {$this->blockArguments[$key]['wrap']['after']}";
                }

                return $this->blockArguments[$key];
            })->toArray();

        return "{$args['before']}{$args['blockHtml']}{$args['actions']}{$args['after']}";
    }

    public function dropPlaceholderHtml()
    {
        return '<div drop-placeholder class="h-full min-h-[200px] text-gray-600 text-lg flex items-center justify-center"><p>'.__('Drop your block here...').'</p></div>';
    }

    public function createBaseView($attributes)
    {
        if (view()->exists($this->base)) {
            return view($this->base, $attributes)->render();
        }

        return Blade::render($this->base, $attributes);
    }
}
