<?php

namespace Pdaether\BlockWire;

use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use Pdaether\BlockWire\Commands\MakeBlockCommand;
use Pdaether\BlockWire\Components\BlockWire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class BlockWireServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('blockwire')
            ->hasConfigFile()
            ->hasViews()
            ->hasCommand(MakeBlockCommand::class);
    }

    public function bootingPackage(): void
    {
        Livewire::component('blockwire', BlockWire::class);

        View::composer('blockwire::editor', function ($view) {
            if (config('blockwire.include_js', true)) {
                $view->jsPath = __DIR__.'/../public/editor.js';
            }

            if (config('blockwire.include_css', true)) {
                $view->cssPath = __DIR__.'/../public/editor.css';
            }
        });
    }
}
