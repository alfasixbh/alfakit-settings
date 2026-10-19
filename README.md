# AlfaKit Settings

<p><picture><source media="(prefers-color-scheme: dark)" srcset="https://raw.githubusercontent.com/alfasixbh/alfakit/main/art/alfasix/alfa-six-micro-white.svg"><img src="https://raw.githubusercontent.com/alfasixbh/alfakit/main/art/alfasix/alfa-six-micro-navy.svg" alt="" width="24" height="18" align="top"></picture> <sub>by <a href="https://alfasixbh.com">Alfa Six</a></sub></p>

> This is a read-only split of the [AlfaKit monorepo](https://github.com/alfasixbh/alfakit/tree/main/packages/plugins/settings). Please open issues and pull requests there.

Settings pages for [Spatie Laravel Settings](https://github.com/spatie/laravel-settings) in [AlfaKit](https://alfakit.dev) panels. They render in React, Vue and Svelte with AlfaKit's built-in components.

## Installation

```bash
composer require alfasix/alfakit-settings
```

Install [spatie/laravel-settings](https://github.com/spatie/laravel-settings) and run its migration.

## Usage

Generate a page (and the settings class, when it doesn't exist yet):

```bash
php artisan make:alfakit-settings-page ManageGeneral GeneralSettings
```

```php
use AlfaSix\Alfakit\Forms\Components\TextInput;
use AlfaSix\Alfakit\Schemas\Schema;
use AlfaSix\AlfakitSettings\Pages\SettingsPage;

class ManageGeneral extends SettingsPage
{
    protected static string $settings = GeneralSettings::class;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('site_name')->required(),
        ]);
    }
}
```

The form is filled from the settings and the save button writes them back, with `mutateFormDataBeforeFill()`, `mutateFormDataBeforeSave()`, `beforeSave()` / `afterSave()` hooks, a saved notification and `canAccess()` authorisation.

Discovered pages work as they are; the plugin registers pages explicitly and can group them:

```php
use AlfaSix\AlfakitSettings\SettingsPlugin;

$panel->plugin(SettingsPlugin::make()->pages([ManageGeneral::class])->navigationGroup('Settings'));
```

Full documentation: [alfakit.dev/docs/plugins/settings](https://alfakit.dev/docs/plugins/settings).

## License

MIT. See [LICENSE.md](LICENSE.md).
