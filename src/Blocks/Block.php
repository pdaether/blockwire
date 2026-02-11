<?php

namespace Pdaether\BlockWire\Blocks;

use Illuminate\View\View;
use Pdaether\BlockWire\Contracts\BlockInterface;

abstract class Block implements BlockInterface
{
    public string $title;

    public string $icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 7.5A2.25 2.25 0 017.5 5.25h9a2.25 2.25 0 012.25 2.25v9a2.25 2.25 0 01-2.25 2.25h-9a2.25 2.25 0 01-2.25-2.25v-9z" /></svg>';

    /**
     * The Livewire edit component class for this block.
     * Can be a full class name (e.g., \App\Livewire\MyBlockEdit::class)
     * or a registered alias (e.g., 'my-block-edit').
     */
    public string $blockEditComponent;

    public ?string $category = null;

    public array $data = [];

    abstract public function render(): string|\Illuminate\View\View;

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function data(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function makeView(): bool|string
    {
        if ($this->render() instanceof View) {
            return file_get_contents($this->render()->getPath());
        }

        return $this->render();
    }

    public static function fromName(string $name, mixed ...$args): static
    {
        return new $name($args);
    }

    public function toArray(): array
    {
        return [
            'data' => $this->data,
            'class' => get_class($this),
            'category' => $this->category,
        ];
    }
}
