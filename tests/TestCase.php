<?php

namespace Pdaether\DropBlockEditor\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Livewire\Livewire;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Pdaether\DropBlockEditor\DropBlockEditor;
use Pdaether\DropBlockEditor\DropBlockEditorServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Pdaether\\DropBlockEditor\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        $this
            ->registerLivewireComponents();
    }

    protected function getPackageProviders($app)
    {
        return [
            LivewireServiceProvider::class,
            DropBlockEditorServiceProvider::class,
        ];
    }

    private function registerLivewireComponents(): self
    {
        Livewire::component('dropblockeditor', DropBlockEditor::class);

        return $this;
    }
}
