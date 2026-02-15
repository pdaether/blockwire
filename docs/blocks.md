# Making Blocks

## Generate a block

```bash
php artisan blockwire:make Faq
```

By default this creates:

- Block class: `app/BlockWire/Blocks/Faq.php`
- Edit component: `app/BlockWire/Forms/Faq.php`

If you need a static/non-editable block:

```bash
php artisan blockwire:make Divider --without-edit-component
```

## Edit component namespace

BlockWire uses `blockwire.edit_component_namespace`.

Default:

```php
'edit_component_namespace' => 'App\\BlockWire\\Forms',
```

Change it in `config/blockwire.php` if you prefer another location.

## Block anatomy

A block class defines:

- `title`
- optional `icon`
- optional `category`
- `data` defaults
- `blockEditComponent` (Livewire class)
- `render()` output

Example:

```php
class Faq extends Block implements BlockInterface
{
    public string $title = 'FAQ';

    public string $category = 'Content';

    public array $data = [
        'headline' => 'Frequently Asked Questions',
        'items' => [],
    ];

    public string $blockEditComponent = \App\BlockWire\Forms\Faq::class;

    public function render(): string
    {
        return <<<'blade'
            <section>
                <h2>{{ $headline }}</h2>
            </section>
        blade;
    }
}
```

## Edit form anatomy

Edit forms extend `BlockEditComponent`. It already syncs normal field changes through `updated()`.

When you mutate arrays in custom methods (for example `addItem()` / `removeItem()`), dispatch manually:

```php
$this->dispatch('blockEditComponentUpdated', $this->position, $this->data);
```

## Register blocks

In `config/blockwire.php`:

```php
'blocks' => [
    App\BlockWire\Blocks\Faq::class,
],
```

Or override per editor instance:

```blade
@livewire('blockwire', [
    'blocks' => [
        App\BlockWire\Blocks\Faq::class,
    ],
])
```
