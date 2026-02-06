<?php

namespace Pdaether\BlockWire\Components;

use Livewire\Component;

class ExampleButton extends Component
{
    public ?array $properties = null;

    protected $listeners = [
        'editorIsUpdated' => 'editorIsUpdated',
    ];

    public function editorIsUpdated(array $properties): void
    {
        $this->properties = $properties;
    }

    public function save()
    {
        // Example of getting a json string of the active blocks.
        // $activeBlocks = collect($this->properties['activeBlocks'])
        //     ->toJson();

        // If you want to generate the output, you can do:
        // $output = Parse::execute([
        //     'activeBlocks' => $this->properties['activeBlocks'],
        //     'base' => $this->properties['base'],
        //     'context' => 'rendered',
        //     'parsers' => $this->properties['parsers'],
        // ]);
    }

    public function render()
    {
        return <<<'blade'
            <div>
                <button wire:click="save" class="bg-blue-200 text-blue-900 rounded px-3 py-1 text-sm">Save</button>
            </div>
        blade;
    }
}
