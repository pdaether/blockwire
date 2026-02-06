<?php

namespace Pdaether\BlockWire\Contracts;

interface BlockInterface
{
    /**
     * Render the block content
     *
     * @return string|\Illuminate\View\View
     */
    public function render();

    /**
     * Get block data
     */
    public function getData(): array;

    /**
     * Get block title
     */
    public function getTitle(): string;

    /**
     * Get block icon
     */
    public function getIcon(): ?string;

    /**
     * Get block category
     *
     * @return mixed
     */
    public function getCategory();

    /**
     * Set block data
     */
    public function data(array $data): static;

    /**
     * Convert block to array representation
     */
    public function toArray(): array;
}
