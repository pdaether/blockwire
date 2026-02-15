# Parsers and MJML

## Parser order matters

BlockWire runs parsers in the order defined in config:

```php
'parsers' => [
    Pdaether\BlockWire\Parsers\Html::class,
    Pdaether\BlockWire\Parsers\Editor::class,
],
```

## Default behavior

- `Html` builds content from your block classes and wraps with `base`.
- `Editor` injects editor UI CSS/overlays only when context is `editor`.

For frontend rendering (`context` != `editor`), `Editor` returns content unchanged.

## MJML support

Switch to MJML parser if you build email templates:

```php
'parsers' => [
    Pdaether\BlockWire\Parsers\Mjml::class,
    Pdaether\BlockWire\Parsers\Editor::class,
],
```

### MJML modes

`config/blockwire.php`:

- `mjml.method = api` -> uses MJML API credentials
- `mjml.method = node` -> uses local Node + MJML CLI

Also configure:

- `node_binary`
- `mjml.binary`
- or `mjml.api.url`, `mjml.api.username`, `mjml.api.password`

## Performance tips

- HTML parser is fastest.
- MJML adds overhead (API/network or local process time).
- Consider caching rendered output after save instead of rendering on every request.
