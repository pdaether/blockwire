# Changelog

All notable changes to `blockwire` will be documented in this file.

## Unreleased

## 2.3.0 - 2026-03-24

- Improve scrolling in forms
- Document new config values
- Add debounce function for rendering

## 2.2.0 - 2026-03-24

- Improve drop zone

## 2.1.0 - 2026-02-26

- Add a visibility flag to blocks

## 2.0.1 - 2026-02-19

- Activate new blocks by default

## 2.0.0 - 2026-02-16

- Upgrade to Livewire ^4.0 (⚠️ breaking change)

## 1.0.1 - 2026-02-15

- Add documentation
- Update README

## 1.0.0 - 2026-02-14

- Rebranding package to BlockWire
- Replace Laravel Mix with Vite
- Add undo and redo buttons
- Add action buttons to the block overlay
- Display block name on mouse over and for active block
- Add a permanent border to the selected element
- Add button for getting the source code (JSON)
- Add BlockInterface
- Add types to increase type safety
- Use full class names for block edit components
- Make the side panel resizable
- Move display options to the header
- Put form classes in dedicated namespace
- Fix broken layout on smaller screens in tablet mode
- Fix re-ordering in history

## 0.4.0 - 2024-03-12

- add support for laravel 11 (thanks @abr4xas)
- added types (⚠️ potential breaking change) (thanks @abr4xas)

## 0.3.1 - 2024-03-06

- hide blocks sidebar with x-show to prevent dom changes (related to https://github.com/jeffreyvr/blockwire/discussions/30)

## 0.3.0 - 2023-11-17

- added tablet preview (thanks @JesusChrist69)
- categories for blocks (thanks @JesusChrist69)

## 0.2.0 - 2023-08-29

- upgrade to Livewire v3
- requires php 8.1 or higher
- `php artisan blockwire:make` puts Livewire component in `App\Livewire` namespace unless otherwise configured
- `php artisan blockwire:make` now assumes you want an edit component by default

## 0.1.3 - 2023-06-23

- added `preview_css` config option (thanks @TechTomaz)
- fix path make block command (lowercase `app`) (thanks @Carnicero90)

## 0.1.2 - 2023-05-12

- allow html parser base template to be a string

## 0.1.1 - 2023-05-11

- fix not passing update properties to buttons on initial load

## 0.1.0 - 2023-05-10

- first pre release
