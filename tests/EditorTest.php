<?php

use Livewire\Livewire;
use Pdaether\BlockWire\Blocks\Example;
use Pdaether\BlockWire\Components\BlockWire;
use Pdaether\BlockWire\Parsers\Editor;
use Pdaether\BlockWire\Parsers\Html;
use Pdaether\BlockWire\Parsers\Parse;
use Pdaether\BlockWire\Tests\Fixtures\Parsers\CountingHtmlParser;

beforeEach(function () {
    config()->set('blockwire.parsers', [
        Html::class,
        Editor::class,
    ]);
    config()->set('blockwire.preview.mode', 'debounced');
    config()->set('blockwire.preview.debounce_ms', 150);

    CountingHtmlParser::reset();
});

it('can render the editor', function () {
    Livewire::test(BlockWire::class, [
        'title' => 'The name of the campaign',
    ])
        ->assertSet('title', 'The name of the campaign');
});

it('displays the example button', function () {
    Livewire::test(BlockWire::class, [
        'title' => 'The name of the campaign',
    ])
        ->assertSee('Save');
});

it('displays the example block', function () {
    Livewire::test(BlockWire::class, [
        'title' => 'The name of the campaign',
    ])
        ->assertSee('Example');
});

it('displays the active block', function () {
    Livewire::test(BlockWire::class, [
        'title' => 'The name of the campaign',
    ])
        ->call('insertBlock', 0)
        ->assertSee('Drop it like it\'s hot');
});

it('activates a newly inserted block by default', function () {
    Livewire::test(BlockWire::class, [
        'title' => 'The name of the campaign',
    ])
        ->call('insertBlock', 0, 0)
        ->assertSet('activeBlockIndex', 0);
});

it('activates a newly inserted block when dropped after another block', function () {
    Livewire::test(BlockWire::class, [
        'title' => 'The name of the campaign',
    ])
        ->call('insertBlock', 0)
        ->call('insertBlock', 0, 0, 'after')
        ->assertSet('activeBlockIndex', 1);
});

it('can undo and redo a change', function () {
    Livewire::test(BlockWire::class, [
        'title' => 'The name of the campaign',
    ])
        ->call('insertBlock', 0)
        ->assertSee('Drop it like it\'s hot')
        ->call('undo')
        ->assertDontSee('Drop it like it\'s hot')
        ->call('redo')
        ->assertSee('Drop it like it\'s hot');
});

it('can delete a block', function () {
    Livewire::test(BlockWire::class, [
        'title' => 'The name of the campaign',
    ])
        ->call('insertBlock', 0)
        ->assertSee('Drop it like it\'s hot')
        ->call('deleteBlock', 0)
        ->assertDontSee('Drop it like it\'s hot');
});

it('sets initial active blocks when set', function () {
    Livewire::test(BlockWire::class, [
        'title' => 'The name of the campaign',
        'activeBlocks' => json_decode('[{"data":{"title":"Picking my way out of here", "content": "One Song At A Time"},"class":"Pdaether\\\\BlockWire\\\\Blocks\\\\Example"}]', true),
    ])
        ->assertSee('One Song At A Time');
});

it('can toggle block visibility and stores show flag in json', function () {
    $editor = Livewire::test(BlockWire::class, [
        'title' => 'The name of the campaign',
    ])
        ->call('insertBlock', 0)
        ->assertSet('activeBlocks.0.show', true)
        ->call('toggleBlockVisibility')
        ->assertSet('activeBlocks.0.show', false);

    expect($editor->instance()->getJsonSnapshot())->toContain('"show": false');
});

it('does not render hidden blocks in rendered context', function () {
    $html = Parse::execute([
        'activeBlocks' => [
            [
                'data' => [
                    'title' => 'Visible title',
                    'content' => 'Visible content',
                ],
                'class' => Example::class,
                'show' => true,
            ],
            [
                'data' => [
                    'title' => 'Hidden title',
                    'content' => 'Hidden content',
                ],
                'class' => Example::class,
                'show' => false,
            ],
        ],
        'base' => '{{ $slot }}',
        'context' => 'rendered',
        'parsers' => config('blockwire.parsers'),
    ]);

    expect($html)
        ->toContain('Visible content')
        ->not->toContain('Hidden content');
});

it('renders legacy blocks in rendered context when show flag is missing', function () {
    $html = Parse::execute([
        'activeBlocks' => [
            [
                'data' => [
                    'title' => 'Legacy title',
                    'content' => 'Legacy content',
                ],
                'class' => Example::class,
            ],
        ],
        'base' => '{{ $slot }}',
        'context' => 'rendered',
        'parsers' => config('blockwire.parsers'),
    ]);

    expect($html)->toContain('Legacy content');
});

it('still renders hidden blocks in editor context', function () {
    $html = Parse::execute([
        'activeBlocks' => [
            [
                'data' => [
                    'title' => 'Hidden title',
                    'content' => 'Hidden content',
                ],
                'class' => Example::class,
                'show' => false,
            ],
        ],
        'base' => '<html><head></head><body>{!! $slot !!}</body></html>',
        'context' => 'editor',
        'parsers' => config('blockwire.parsers'),
    ]);

    expect($html)
        ->toContain('Hidden content')
        ->toContain('data-show="0"');
});

it('renders the preview once on initial mount', function () {
    config()->set('blockwire.parsers', [CountingHtmlParser::class]);

    Livewire::test(BlockWire::class, [
        'title' => 'The name of the campaign',
    ]);

    expect(CountingHtmlParser::$parseCount)->toBe(1);
});

it('does not rebuild preview for selection-only changes', function () {
    config()->set('blockwire.parsers', [CountingHtmlParser::class]);

    $editor = Livewire::test(BlockWire::class, [
        'title' => 'The name of the campaign',
    ])
        ->call('insertBlock', 0);

    expect(CountingHtmlParser::$parseCount)->toBe(2);

    $editor
        ->call('blockSelected', 0)
        ->assertNotDispatched('editorIsUpdated');

    expect(CountingHtmlParser::$parseCount)->toBe(2);
});

it('marks preview dirty without rebuilding it for debounced content edits', function () {
    config()->set('blockwire.parsers', [CountingHtmlParser::class]);

    $editor = Livewire::test(BlockWire::class, [
        'title' => 'The name of the campaign',
        'previewMode' => 'debounced',
    ])
        ->call('insertBlock', 0);

    CountingHtmlParser::reset();

    $editor
        ->call('blockUpdated', 0, [
            'title' => 'Updated title',
            'content' => 'Updated content',
        ])
        ->assertSet('activeBlocks.0.data.title', 'Updated title')
        ->assertSet('previewDirty', true)
        ->assertDispatched('editorIsUpdated')
        ->assertDispatched('blockwirePreviewDirty', function ($event, $params) {
            return $event === 'blockwirePreviewDirty'
                && $params['mode'] === 'debounced'
                && $params['debounceMs'] === 150;
        });

    expect(CountingHtmlParser::$parseCount)->toBe(0);
});

it('refreshes the preview on demand for debounced edits', function () {
    config()->set('blockwire.parsers', [CountingHtmlParser::class]);

    $editor = Livewire::test(BlockWire::class, [
        'title' => 'The name of the campaign',
        'previewMode' => 'debounced',
    ])
        ->call('insertBlock', 0);

    CountingHtmlParser::reset();

    $editor->call('blockUpdated', 0, [
        'title' => 'Updated title',
        'content' => 'Updated content',
    ]);

    expect(CountingHtmlParser::$parseCount)->toBe(0);

    $editor
        ->call('refreshPreview')
        ->assertSet('previewDirty', false)
        ->assertDispatched('blockwirePreviewClean');

    expect(CountingHtmlParser::$parseCount)->toBe(1);
});

it('keeps manual preview stale until explicitly refreshed', function () {
    config()->set('blockwire.parsers', [CountingHtmlParser::class]);

    $editor = Livewire::test(BlockWire::class, [
        'title' => 'The name of the campaign',
        'previewMode' => 'manual',
    ])
        ->call('insertBlock', 0);

    CountingHtmlParser::reset();

    $editor
        ->call('blockUpdated', 0, [
            'title' => 'Updated title',
            'content' => 'Updated content',
        ])
        ->assertSet('previewDirty', true);

    expect(CountingHtmlParser::$parseCount)->toBe(0);

    $editor->call('blockSelected', 0);

    expect(CountingHtmlParser::$parseCount)->toBe(0);

    $editor
        ->call('refreshPreview')
        ->assertSet('previewDirty', false);

    expect(CountingHtmlParser::$parseCount)->toBe(1);
});

it('refreshes preview immediately for structural changes even when preview is dirty', function () {
    config()->set('blockwire.parsers', [CountingHtmlParser::class]);

    $editor = Livewire::test(BlockWire::class, [
        'title' => 'The name of the campaign',
        'previewMode' => 'manual',
    ])
        ->call('insertBlock', 0);

    CountingHtmlParser::reset();

    $editor->call('blockUpdated', 0, [
        'title' => 'Updated title',
        'content' => 'Updated content',
    ]);

    expect(CountingHtmlParser::$parseCount)->toBe(0);

    $editor
        ->call('toggleBlockVisibility')
        ->assertSet('previewDirty', false)
        ->assertDispatched('editorIsUpdated')
        ->assertDispatched('blockwirePreviewClean');

    expect(CountingHtmlParser::$parseCount)->toBe(1);
});

it('dispatches editor updates for content and structural changes but not selection-only changes', function () {
    $editor = Livewire::test(BlockWire::class, [
        'title' => 'The name of the campaign',
        'previewMode' => 'debounced',
    ]);

    $editor
        ->call('insertBlock', 0)
        ->assertDispatched('editorIsUpdated');

    $editor
        ->call('blockSelected', 0)
        ->assertNotDispatched('editorIsUpdated');

    $editor
        ->call('blockUpdated', 0, [
            'title' => 'Updated title',
            'content' => 'Updated content',
        ])
        ->assertDispatched('editorIsUpdated');
});
