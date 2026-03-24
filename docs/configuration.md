# Configuration Reference

Published file: `config/blockwire.php`

## Editor settings

- `include_js` (`bool`): include package editor JS inline in editor view.
- `include_css` (`bool`): include package editor CSS inline in editor view.
- `preview_css` (`string|null`): custom CSS file from `public/` used in preview iframe.
- `show_source_button` (`bool`): show JSON source modal button.
- `preview.mode` (`string`): preview refresh strategy. Supported values: `immediate`, `debounced`, `manual`.
- `preview.debounce_ms` (`int`): idle delay before a debounced preview refresh runs.

## Edit component namespace

- `edit_component_namespace` (`string`): namespace for generated edit components.
- Default: `App\\BlockWire\\Forms`.

This controls `php artisan blockwire:make` output location for edit components.

## Branding

- `brand.logo` (`string`): HTML/SVG logo rendered in editor header.
- `brand.colors.topbar_bg` (`string`): Tailwind classes for top bar background/text.
- `brand.colors.active_border` (`string`): active block border color token.

## Blocks and buttons

- `blocks` (`array<class-string>`): default available blocks.
- `buttons` (`array<class-string|string>`): default editor header buttons.

Both can be overridden per component instance.

## Preview behavior

Default:

```php
[
    'mode' => 'debounced',
    'debounce_ms' => 150,
]
```

- `immediate`: refresh the preview on every content edit.
- `debounced`: queue content edits and refresh after a short idle pause.
- `manual`: keep the preview stale until the editor user clicks refresh.

Structural actions such as insert, clone, delete, reorder, visibility toggles, undo, and redo always refresh immediately.

## Parsers

- `parsers` (`array<class-string>`): pipeline that transforms block data to HTML.

Default:

```php
[
    Pdaether\BlockWire\Parsers\Html::class,
    Pdaether\BlockWire\Parsers\Editor::class,
]
```

## MJML

- `mjml.method`: `api` or `node`
- `mjml.binary`: path to MJML binary
- `mjml.api.url`, `mjml.api.username`, `mjml.api.password`
- `node_binary`: Node executable path

## Practical recommendation

Keep separate base templates for:

- editor preview (inside BlockWire)
- frontend/public rendering
- email rendering (if using MJML)

This keeps output predictable and easy to style.
