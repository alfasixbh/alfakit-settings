<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitSettings\Tests\Fixtures;

use AlfaSix\Alfakit\Forms\Components\TextInput;
use AlfaSix\Alfakit\Forms\Components\Toggle;
use AlfaSix\Alfakit\Schemas\Schema;
use AlfaSix\AlfakitSettings\Pages\SettingsPage;

class ManageGeneral extends SettingsPage
{
    protected static string $settings = GeneralSettings::class;

    protected static ?string $slug = 'general';

    public static bool $allowed = true;

    /** @var list<string> */
    public static array $hooks = [];

    public static function canAccess(): bool
    {
        return static::$allowed;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('site_name')->required()->maxLength(50),
            Toggle::make('maintenance_mode'),
            TextInput::make('per_page')->integer()->required(),
        ]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['site_name'] = strtoupper((string) $data['site_name']);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['site_name'] = trim((string) $data['site_name']).'!';

        return $data;
    }

    protected function beforeSave(): void
    {
        static::$hooks[] = 'beforeSave';
    }

    protected function afterSave(): void
    {
        static::$hooks[] = 'afterSave';
    }
}
