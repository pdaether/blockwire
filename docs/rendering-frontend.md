# Rendering in Frontend

This is the part missing in many quickstarts: how to use saved BlockWire data in your public site/email output.

## What to store

Store editor state as JSON from `activeBlocks`.

Example DB column: `content_json` (`longText`/`json`).

## Render pipeline

Use `Parse::execute()` when you need final HTML:

```php
use Pdaether\BlockWire\Parsers\Parse;

$activeBlocks = json_decode($page->content_json ?? '[]', true);

$html = Parse::execute([
    'activeBlocks' => $activeBlocks,
    'base' => 'frontend.page-base',
    'context' => 'rendered',
    'parsers' => config('blockwire.parsers'),
]);
```

`context` should be non-`editor` for frontend output (for example `rendered`).

## Return in controller

```php
public function show(Page $page)
{
    $html = Parse::execute([
        'activeBlocks' => json_decode($page->content_json ?? '[]', true),
        'base' => 'frontend.page-base',
        'context' => 'rendered',
        'parsers' => config('blockwire.parsers'),
    ]);

    return view('pages.show', compact('page', 'html'));
}
```

In Blade:

```blade
{!! $html !!}
```

## Security note

`activeBlocks` contains block class names. Never trust unvalidated JSON from untrusted users.

Recommended:

- only allow saving from authenticated editor users
- validate or sanitize incoming JSON before persisting
- keep allowed blocks controlled by app config/component setup

## Base templates for frontend

Create dedicated base templates for frontend output (not the editor shell).

```blade
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>{{ $title ?? 'Page' }}</title>
</head>
<body>
  {!! $slot !!}
</body>
</html>
```
