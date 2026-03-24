<?php

namespace Pdaether\BlockWire\Components;

use Illuminate\Support\Str;
use Livewire\Component;
use Pdaether\BlockWire\Blocks\Block;
use Pdaether\BlockWire\Contracts\BlockInterface;
use Pdaether\BlockWire\Parsers\Parse;

class BlockWire extends Component
{
    public ?string $title = null;

    public string $base = 'blockwire::base';

    public string $hash = '';

    public array $parsers = [];

    public string $result = '';

    public $activeBlockIndex = false;

    public array $activeBlocks = [];

    public array $history = [];

    public int $historyIndex = -1;

    public ?array $buttons = null;

    public ?array $blocks = null;

    public ?string $previewMode = null;

    public int|string|null $previewDebounceMs = null;

    public bool $previewDirty = false;

    public string $lastRenderedPreviewFingerprint = '';

    protected bool $shouldRefreshPreview = false;

    protected function normalizeShowFlag(mixed $value): bool
    {
        if ($value === false || $value === 0 || $value === '0') {
            return false;
        }

        if (is_string($value) && strtolower(trim($value)) === 'false') {
            return false;
        }

        return true;
    }

    protected function normalizeActiveBlocks(): void
    {
        $this->activeBlocks = collect($this->activeBlocks)
            ->map(function ($block) {
                if (! is_array($block)) {
                    return $block;
                }

                $block['show'] = $this->normalizeShowFlag($block['show'] ?? true);

                return $block;
            })
            ->values()
            ->all();
    }

    protected function setActiveBlockIndex(int|string|false $value): void
    {
        $this->activeBlockIndex = $value;
        $this->dispatch('activeBlockIndexChanged', $value);
    }

    protected function normalizePreviewMode(?string $value): string
    {
        $value = strtolower(trim((string) $value));

        return in_array($value, ['immediate', 'debounced', 'manual'], true)
            ? $value
            : 'debounced';
    }

    protected function normalizePreviewDebounceMs(int|string|null $value): int
    {
        if ($value === null || $value === '') {
            return 150;
        }

        return max(0, (int) $value);
    }

    protected function previewFingerprint(): string
    {
        return md5(serialize([
            'activeBlocks' => $this->activeBlocks,
            'base' => $this->base,
            'parsers' => $this->parsers,
        ]));
    }

    protected function shouldRenderPreview(): bool
    {
        return $this->shouldRefreshPreview || $this->lastRenderedPreviewFingerprint === '';
    }

    protected function requestPreviewRefresh(): void
    {
        $this->shouldRefreshPreview = true;
    }

    protected function dispatchEditorUpdated(): void
    {
        $this->dispatch('editorIsUpdated', $this->updateProperties());
    }

    protected function dispatchPreviewDirty(): void
    {
        $this->dispatch('blockwirePreviewDirty',
            componentId: $this->getId(),
            mode: $this->previewMode,
            debounceMs: $this->previewDebounceMs,
        );
    }

    protected function dispatchPreviewClean(): void
    {
        $this->dispatch('blockwirePreviewClean', componentId: $this->getId());
    }

    public function updatedActiveBlockIndex(int|string|false $value): void
    {
        $this->dispatch('activeBlockIndexChanged', $value);
    }

    protected $listeners = [
        'blockEditComponentSelected' => 'blockSelected',
        'blockEditComponentUpdated' => 'blockUpdated',
        'refreshComponent' => '$refresh',
    ];

    public function canUndo(): bool
    {
        return $this->historyIndex > 0;
    }

    public function canRedo(): bool
    {
        return $this->historyIndex < count($this->history) - 1;
    }

    public function undo(): void
    {
        if (! $this->canUndo()) {
            return;
        }

        $this->historyIndex--;

        $this->activeBlocks = $this->history[$this->historyIndex]['activeBlocks'];
        $this->setActiveBlockIndex($this->history[$this->historyIndex]['activeBlockIndex']);
        $this->updateHash();
        $this->requestPreviewRefresh();
        $this->dispatchEditorUpdated();
    }

    public function updateHash(): void
    {
        $this->hash = Str::random(10);
    }

    public function redo(): void
    {
        if (! $this->canRedo()) {
            return;
        }

        $this->historyIndex++;

        $this->activeBlocks = $this->history[$this->historyIndex]['activeBlocks'];
        $this->setActiveBlockIndex($this->history[$this->historyIndex]['activeBlockIndex']);
        $this->updateHash();
        $this->requestPreviewRefresh();
        $this->dispatchEditorUpdated();
    }

    public function recordInHistory(): void
    {
        $history = collect($this->history)
            ->slice(0, $this->historyIndex + 1)
            ->push([
                'activeBlocks' => $this->activeBlocks,
                'activeBlockIndex' => $this->activeBlockIndex,
            ])
            ->take(-5)
            ->values();

        $this->history = $history->toArray();

        $this->historyIndex = count($this->history) - 1;
    }

    public function blockUpdated(int $position, array $data): void
    {
        if (! isset($this->activeBlocks[$position])) {
            return;
        }

        if (($this->activeBlocks[$position]['data'] ?? null) === $data) {
            return;
        }

        $this->activeBlocks[$position]['data'] = $data;

        $this->recordInHistory();
        $this->dispatchEditorUpdated();

        if ($this->previewMode === 'immediate') {
            $this->requestPreviewRefresh();

            return;
        }

        $this->previewDirty = true;
        $this->dispatchPreviewDirty();
        $this->skipRender();
    }

    public function process(): void
    {
        if (! $this->shouldRenderPreview()) {
            return;
        }

        $wasPreviewDirty = $this->previewDirty;
        $fingerprint = $this->previewFingerprint();

        if ($fingerprint !== $this->lastRenderedPreviewFingerprint) {
            $this->result = Parse::execute([
                'activeBlocks' => $this->activeBlocks,
                'base' => $this->base,
                'context' => 'editor',
                'parsers' => $this->parsers,
            ]);

            $this->lastRenderedPreviewFingerprint = $fingerprint;
        }

        $this->previewDirty = false;
        $this->shouldRefreshPreview = false;

        if ($wasPreviewDirty) {
            $this->dispatchPreviewClean();
        }
    }

    public function blockSelected($blockId): void
    {
        $this->setActiveBlockIndex($blockId);
    }

    public function cloneBlock(?int $blockId = null): void
    {
        $index = $blockId !== null ? $blockId : $this->activeBlockIndex;

        if (! isset($this->activeBlocks[$index])) {
            return;
        }

        $clone = $this->activeBlocks[$index];

        $this->activeBlocks[] = $clone;

        $this->setActiveBlockIndex(array_key_last($this->activeBlocks));

        $this->recordInHistory();
        $this->requestPreviewRefresh();
        $this->dispatchEditorUpdated();
    }

    public function toggleBlockVisibility(?int $blockId = null): void
    {
        $index = $blockId !== null ? $blockId : $this->activeBlockIndex;

        if (! isset($this->activeBlocks[$index])) {
            return;
        }

        $visible = $this->normalizeShowFlag($this->activeBlocks[$index]['show'] ?? true);
        $this->activeBlocks[$index]['show'] = ! $visible;

        $this->recordInHistory();
        $this->requestPreviewRefresh();
        $this->dispatchEditorUpdated();
    }

    public function deleteBlock(?int $blockId = null): void
    {
        $index = $blockId !== null ? $blockId : $this->activeBlockIndex;

        if (! isset($this->activeBlocks[$index])) {
            return;
        }

        $this->setActiveBlockIndex(false);

        unset($this->activeBlocks[$index]);

        $this->activeBlocks = array_values($this->activeBlocks);

        $this->recordInHistory();
        $this->requestPreviewRefresh();
        $this->dispatchEditorUpdated();
    }

    public function getBlockFromClassName(string $name): BlockInterface
    {
        return Block::fromName($name);
    }

    public function getActiveBlock(): bool|BlockInterface
    {
        if (isset($this->activeBlockIndex) && $this->activeBlockIndex === false) {
            return false;
        }

        return Block::fromName($this->activeBlocks[$this->activeBlockIndex]['class'])
            ->data($this->activeBlocks[$this->activeBlockIndex]['data']);
    }

    public function mount(): void
    {
        $this->parsers = config('blockwire.parsers', []);

        $this->blocks = collect(! is_null($this->blocks) ? $this->blocks : config('blockwire.blocks', []))
            ->map(fn ($block) => (new $block)->toArray())
            ->all();

        $this->buttons = ! is_null($this->buttons) ? $this->buttons : config('blockwire.buttons', []);
        $this->previewMode = $this->normalizePreviewMode($this->previewMode ?? config('blockwire.preview.mode', 'debounced'));
        $this->previewDebounceMs = $this->normalizePreviewDebounceMs($this->previewDebounceMs ?? config('blockwire.preview.debounce_ms', 150));

        $this->normalizeActiveBlocks();

        $this->updateHash();
        $this->requestPreviewRefresh();

        $this->recordInHistory();
    }

    public function reorder(array $ids): void
    {
        $this->activeBlocks = collect($ids)
            ->map(function ($id) {
                return $this->activeBlocks[$id];
            })
            ->all();

        $this->recordInHistory();
        $this->requestPreviewRefresh();
        $this->dispatchEditorUpdated();
    }

    public function insertBlock(int $id, ?int $index = null, ?string $placement = null): void
    {
        $block = $this->blocks[$id];

        if ($index === null) {
            $this->setActiveBlockIndex(count($this->activeBlocks));
            $this->activeBlocks[] = $block;
            $this->recordInHistory();
            $this->requestPreviewRefresh();
            $this->dispatchEditorUpdated();

            return;
        }

        $newIndex = $placement === 'before'
            ? max($index - 1, 0)
            : $index + 1;

        $newIndex = min(max($newIndex, 0), count($this->activeBlocks));

        $this->activeBlocks = array_merge(array_slice($this->activeBlocks, 0, $newIndex), [$block], array_slice($this->activeBlocks, $newIndex));
        $this->setActiveBlockIndex($newIndex);

        $this->recordInHistory();
        $this->requestPreviewRefresh();
        $this->dispatchEditorUpdated();
    }

    public function refreshPreview(): void
    {
        $this->requestPreviewRefresh();
    }

    public function prepareActiveBlockKey(int|false $activeBlockIndex): string
    {
        return "{$activeBlockIndex}-{$this->hash}";
    }

    public function updateProperties(): array
    {
        return [
            'base' => $this->base,
            'parsers' => $this->parsers,
            'activeBlocks' => $this->activeBlocks,
        ];
    }

    public function getJsonSnapshot(): string
    {
        return json_encode($this->activeBlocks, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function render()
    {
        $this->process();

        return view('blockwire::editor', [
            'activeBlock' => $this->getActiveBlock(),
        ]);
    }
}
