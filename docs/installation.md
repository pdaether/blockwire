# Installation

## Requirements

- PHP `^8.4`
- Livewire `^3.3`
- Laravel components via `illuminate/contracts` `^10|^11|^12`

## Install package

```bash
composer require pdaether/blockwire
```

## Publish configuration

```bash
php artisan vendor:publish --tag="blockwire-config"
```

This publishes `config/blockwire.php`.

## Optional: publish views

```bash
php artisan vendor:publish --tag="blockwire-views"
```

Publish views if you want to customize the editor template.

## Verify setup

Render the component once in any Blade file:

```blade
@livewire('blockwire', [
    'title' => 'BlockWire Demo',
])
```

If the editor appears, installation is complete.
