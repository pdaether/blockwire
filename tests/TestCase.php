<?php

namespace Pdaether\BlockWire\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Livewire\Livewire;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Pdaether\BlockWire\BlockWire;
use Pdaether\BlockWire\BlockWireServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Pdaether\\BlockWire\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        $this
            ->registerLivewireComponents();
    }

    protected function getPackageProviders($app)
    {
        return [
            LivewireServiceProvider::class,
            BlockWireServiceProvider::class,
        ];
    }

    private function registerLivewireComponents(): self
    {
        Livewire::component('blockwire', BlockWire::class);

        return $this;
    }
}
