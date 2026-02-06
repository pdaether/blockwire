<?php

namespace Pdaether\DropBlockEditor;

use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use Pdaether\DropBlockEditor\Commands\MakeBlockCommand;
use Pdaether\DropBlockEditor\Components\DropBlockEditor;
use Pdaether\DropBlockEditor\Components\Example;
use Pdaether\DropBlockEditor\Components\ExampleButton;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class DropBlockEditorServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('dropblockeditor')
            ->hasConfigFile()
            ->hasViews()
            ->hasCommand(MakeBlockCommand::class);
    }

    public function bootingPackage(): void
    {
        Livewire::component('dropblockeditor', DropBlockEditor::class);
        Livewire::component('dropblockeditor-example', Example::class);
        Livewire::component('dropblockeditor-example-button', ExampleButton::class);

        View::composer('dropblockeditor::editor', function ($view) {
            if (config('dropblockeditor.include_js', true)) {
                $view->jsPath = __DIR__.'/../public/editor.js';
            }

            if (config('dropblockeditor.include_css', true)) {
                $view->cssPath = __DIR__.'/../public/editor.css';
            }
        });
    }
}
