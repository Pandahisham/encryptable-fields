<?php

namespace TomLegkov\EncryptableFields;

use Illuminate\Support\ServiceProvider;

class EncryptableFieldsServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config.php' => config_path('encryptable-fields.php'),
        ], 'config');
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/config.php', 'encryptable-fields');
    }

}