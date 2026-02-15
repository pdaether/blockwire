# Buttons and Saving

## Built-in button slot in the editor header

You can register custom button components:

```blade
@livewire('blockwire', [
    'buttons' => [
        'example-button',
        App\Livewire\EditorSaveButton::class,
    ],
])
```

These components can listen to `editorIsUpdated`.

## Event payload for saving

`BlockWire` dispatches:

- Event: `editorIsUpdated`
- Payload keys:
  - `activeBlocks`
  - `base`
  - `parsers`

You can capture this and save current JSON/output.

## Save button outside the editor

You do not need to place save buttons in `buttons`.

A separate Livewire component can listen for `editorIsUpdated` and save when clicked.

Minimal listener pattern:

```php
protected $listeners = [
    'editorIsUpdated' => 'onEditorUpdated',
];

public array $editorState = [];

public function onEditorUpdated(array $properties): void
{
    $this->editorState = $properties;
}
```

Then in `save()`, persist `$this->editorState['activeBlocks']`.

## Full-page Livewire pattern

If your page is already a Livewire component and you have your own toolbar:

- render `<livewire:blockwire :buttons="[]" ... />`
- listen to `editorIsUpdated` in parent
- use parent `save()` button

This keeps editor state management in one place.

## Generating rendered output during save

Use parser pipeline:

```php
use Pdaether\BlockWire\Parsers\Parse;

$html = Parse::execute([
    'activeBlocks' => $this->editorState['activeBlocks'],
    'base' => $this->editorState['base'],
    'context' => 'rendered',
    'parsers' => $this->editorState['parsers'],
]);
```

Store both JSON (`activeBlocks`) and rendered HTML if your app needs fast reads.
