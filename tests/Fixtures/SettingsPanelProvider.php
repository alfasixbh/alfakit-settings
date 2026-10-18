<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitSettings\Tests\Fixtures;

use AlfaSix\Alfakit\Panel;
use AlfaSix\Alfakit\PanelProvider;
use AlfaSix\AlfakitSettings\SettingsPlugin;

class SettingsPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('settings')
            ->path('settings')
            ->login()
            ->middleware(['web'])
            ->plugin(SettingsPlugin::make()->pages([ManageGeneral::class])->navigationGroup('Configuration'));
    }
}
