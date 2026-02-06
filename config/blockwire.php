<?php

return [

    /*
    |-----------------------------------------------------------------------------
    | Customize Editor
    |-----------------------------------------------------------------------------
    |
    | include_js: Whether to load the editor JavaScript in the editor template.
    | include_css: Whether to load the editor CSS in the editor template.
    | preview_css: Public path to CSS to override in the preview.
    | show_source_button: Whether to show the source code view button in the editor.
    | brand: Custom branding for the editor.
    |
    */

    'include_js' => true,

    'include_css' => true,

    'preview_css' => null,

    'show_source_button' => true,

    'brand' => [
        'logo' => '<svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">   <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" /> </svg> ',
        'colors' => [
            'topbar_bg' => 'bg-white text-gray-800',
            'active_border' => 'blue-500',
        ],
    ],

    /*
    |--------------------------------------------------------------------------------
    | Blocks
    |--------------------------------------------------------------------------------
    |
    | Here you may register the blocks that should be available in the editor by
    | default. However, you choose to override available blocks by declaring
    | them in the in the livewire blockwire component.
    |
    */

    'blocks' => [
        Pdaether\BlockWire\Blocks\Example::class,
    ],

    /*
    |--------------------------------------------------------------------------------
    | Buttons
    |--------------------------------------------------------------------------------
    |
    | Here you may register the buttons that should be available in the editor by
    | default. However, you choose to override available buttons by declaring
    | them in the in the livewire blockwire component.
    |
    */

    'buttons' => [
        'blockwire-example-button',
    ],

    /*
    |-----------------------------------------------------------------------------
    | Register Parsers
    |-----------------------------------------------------------------------------
    |
    | Here you may register the parsers that the base template and blocks
    | should go through before they are ready as output.
    |
    */

    'parsers' => [
        // Pdaether\BlockWire\Parsers\Mjml::class,
        Pdaether\BlockWire\Parsers\Html::class,
        Pdaether\BlockWire\Parsers\Editor::class,
    ],

    /*
    |-----------------------------------------------------------------------------
    | MJML
    |-----------------------------------------------------------------------------
    |
    | Here you can define the settings for the MJML parsing. Choosing the API
    | requires username and password, while choosing Node, requires you
    | to install the MJML package.
    |
    */

    'mjml' => [
        'method' => env('BLOCKWIRE_MJML_METHOD', 'api'),

        'binary' => env('BLOCKWIRE_MJML_BINARY', '../node_modules/.bin/mjml'),

        'api' => [
            'url' => env('BLOCKWIRE_MJML_API_URL', 'https://api.mjml.io/v1/render'),
            'username' => env('BLOCKWIRE_MJML_API_USERNAME'),
            'password' => env('BLOCKWIRE_MJML_API_PASSWORD'),
        ],
    ],

    'node_binary' => env('BLOCKWIRE_NODE_BINARY', '/usr/local/bin/node'),
];
