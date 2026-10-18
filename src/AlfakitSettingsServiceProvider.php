<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitSettings;

use AlfaSix\AlfakitSettings\Commands\MakeSettingsPageCommand;
use Illuminate\Support\ServiceProvider;

class AlfakitSettingsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadTranslationsFrom(dirname(__DIR__).'/resources/lang', 'alfakit-settings');

        if ($this->app->runningInConsole()) {
            $this->commands([MakeSettingsPageCommand::class]);

            $this->publishes([
                dirname(__DIR__).'/stubs' => base_path('stubs/alfakit-settings'),
            ], 'alfakit-settings-stubs');
        }
    }
}
