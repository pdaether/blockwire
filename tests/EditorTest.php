<?php

use Livewire\Livewire;
use Pdaether\BlockWire\Components\BlockWire;

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
