<?php

namespace TheCoati\CfxSocialite;

use Carbon\Laravel\ServiceProvider;
use Illuminate\Contracts\Container\BindingResolutionException;
use Laravel\Socialite\Contracts\Factory;
use TheCoati\CfxSocialite\Commands\KeysCommand;

class CfxServiceProvider extends ServiceProvider
{
    /**
     * Register CFX services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/cfx.php', 'cfx');
    }

    /**
     * Bootstrap CFX services.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                KeysCommand::class,
            ]);
        }

        $this->publishes([
            __DIR__.'/../config/cfx.php' => config_path('cfx.php')
        ], 'cfx-config');

        $socialite = $this->app->make(Factory::class);

        $socialite->extend('cfx', function ($app) use ($socialite) {
            $config = $app['config']['cfx'];

            return $socialite->buildProvider(CfxProvider::class, $config);
        });
    }
}
