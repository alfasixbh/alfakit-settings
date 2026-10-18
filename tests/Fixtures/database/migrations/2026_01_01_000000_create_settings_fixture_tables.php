<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Spatie\LaravelSettings\Migrations\SettingsMigrator;

return new class extends Migration
{
    public function up(): void
    {
        (require dirname(__DIR__, 7).'/vendor/spatie/laravel-settings/database/migrations/create_settings_table.php.stub')->up();

        $migrator = app(SettingsMigrator::class);
        $migrator->add('general.site_name', 'AlfaKit');
        $migrator->add('general.maintenance_mode', false);
        $migrator->add('general.per_page', 25);
        $migrator->add('general.api_secret', 'top-secret');
    }
};
