<?php

namespace Pdaether\BlockWire\Contracts;

interface BlockInterface
{
    /**
     * Render the block content
     */
    public function render(): string|\Illuminate\View\View;

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
     */
    public function getCategory(): ?string;

    /**
     * Set block data
     */
    public function data(array $data): static;

    /**
     * Convert block to array representation
     */
    public function toArray(): array;
}
