<?php

namespace Pdaether\BlockWire\Components;

use Livewire\Component;

class BlockEditComponent extends Component
{
    public int $position;

    public array $block;

    public array $data = [];

    public function mount()
    {
        foreach ($this->block['data'] as $key => $value) {
            $this->data[$key] = $value;
        }
    }

    public function updated()
    {
        $this->dispatch('blockEditComponentUpdated', $this->position, $this->data);
    }
}
