<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitSettings\Tests\Fixtures;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $site_name;

    public bool $maintenance_mode;

    public int $per_page;

    public ?string $api_secret;

    public static function group(): string
    {
        return 'general';
    }
}
