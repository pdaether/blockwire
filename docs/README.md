# BlockWire Documentation

## Start Here

- [Installation](./installation.md)
- [Setup Editor](./setup-editor.md)
- [Making Blocks](./blocks.md)
- [Buttons and Saving](./buttons-and-saving.md)
- [Rendering in Frontend](./rendering-frontend.md)
- [Parsers and MJML](./parsers-and-mjml.md)
- [Configuration Reference](./configuration.md)

## Project Defaults

1. The generator creates block classes in `app/BlockWire/Blocks`.
2. The generator creates edit components in `App\\BlockWire\\Forms` by default.
3. You can override edit component namespace with `blockwire.edit_component_namespace`.
4. The package keeps the editor output and frontend output separate via parser context (`editor` vs non-editor).

## Typical Flow

1. Install and publish config.
2. Render `<livewire:blockwire ...>` on your admin/editor page.
3. Create custom blocks with `php artisan blockwire:make`.
4. Save `activeBlocks` JSON in your own model.
5. Render frontend HTML on demand using `Parse::execute`.

## Need a quick practical recipe?

Read:

- [Setup Editor](./setup-editor.md)
- [Buttons and Saving](./buttons-and-saving.md)
- [Rendering in Frontend](./rendering-frontend.md)
