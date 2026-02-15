# Setup Editor

## Minimal setup in Blade

```blade
@livewire('blockwire', [
    'title' => 'Page Builder',
])
```

## Optional component parameters

You can pass these from Blade:

- `title` (`string`)
- `base` (`string`)
- `activeBlocks` (`array`)
- `blocks` (`array`)
- `buttons` (`array`)

Example:

```blade
@livewire('blockwire', [
    'title' => 'Campaign Editor',
    'base' => 'mail.templates.newsletter',
    'activeBlocks' => json_decode($model->content_json ?? '[]', true),
    'blocks' => [
        App\BlockWire\Blocks\Hero::class,
        App\BlockWire\Blocks\Faq::class,
    ],
    'buttons' => [],
])
```

## About `base`

`base` is the wrapping template used by parsers. The template should print `slot` content:

```blade
<html>
  <body>
    {!! $slot !!}
  </body>
</html>
```

You can also pass a Blade string as `base` if needed.

## Using BlockWire in a full-page Livewire component

Inside your full-page Livewire Blade view:

```blade
<livewire:blockwire
    :title="$title"
    :active-blocks="$activeBlocks"
    :base="$base"
    :buttons="[]"
/>
```

This is useful when your page already has its own layout, toolbar and save flow.
