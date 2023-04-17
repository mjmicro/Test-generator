<?php

namespace App\Providers;

use App\TestGenerator;
use App\TestGeneratorCmd;
use Illuminate\Support\ServiceProvider;

class TestGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                TestGenerator::class,
                TestGeneratorCmd::class
            ]);
        }
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}