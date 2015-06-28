<?php

namespace Optimus\LaravelBoilerplate\Provider;

use Illuminate\Support\ServiceProvider as BaseProvider;

class LaravelServiceProvider extends BaseProvider {

    public function register()
    {
        $this->loadConfig();
        $this->registerAssets();
    }

    public function boot()
    {
        $this->loadLangFile();
    }

    private function registerAssets()
    {
        $this->publishes([
            __DIR__.'/../config/package.php' => config_path('package.php')
        ]);
    }

    private function loadConfig()
    {
        if ($this->app['config']->get('package') === null) {
            $this->app['config']->set('package', require __DIR__.'/../config/package.php');
        }
    }

    private function loadLangFile()
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'package');
    }

}
