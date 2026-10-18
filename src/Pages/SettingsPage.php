<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitSettings\Pages;

use AlfaSix\Alfakit\Actions\Action;
use AlfaSix\Alfakit\Notifications\Notification;
use AlfaSix\Alfakit\Pages\Page;
use AlfaSix\Alfakit\Schemas\Schema;
use AlfaSix\Alfakit\Support\Enums\Color;
use AlfaSix\AlfakitSettings\SettingsPlugin;
use Illuminate\Support\Arr;
use LogicException;
use Spatie\LaravelSettings\Settings;
use UnitEnum;

/**
 * A page that edits a Spatie settings class: the form is filled from the settings and
 * saving writes them back.
 *
 * ```php
 * class ManageGeneral extends SettingsPage
 * {
 *     protected static string $settings = GeneralSettings::class;
 *
 *     public function form(Schema $schema): Schema
 *     {
 *         return $schema->components([TextInput::make('site_name')->required()]);
 *     }
 * }
 * ```
 */
abstract class SettingsPage extends Page
{
    /**
     * The settings class the page edits.
     *
     * @var class-string<Settings>
     */
    protected static string $settings;

    protected static ?string $navigationIcon = 'settings';

    /**
     * @return class-string<Settings>
     */
    public static function getSettingsClass(): string
    {
        if (! isset(static::$settings)) {
            throw new LogicException('Set `protected static string $settings` on '.static::class.'.');
        }

        return static::$settings;
    }

    /**
     * The page's own navigation group, else the `SettingsPlugin`'s.
     */
    public static function getNavigationGroup(): string|UnitEnum|null
    {
        $group = parent::getNavigationGroup();

        if ($group !== null) {
            return $group;
        }

        $panel = alfakit()->getCurrentOrDefaultPanel();
        $plugin = $panel->hasPlugin(SettingsPlugin::ID) ? $panel->getPlugin(SettingsPlugin::ID) : null;

        return $plugin instanceof SettingsPlugin ? $plugin->getNavigationGroup() : null;
    }

    /**
     * The settings, resolved from the container (loaded from their repository).
     */
    public function getSettings(): Settings
    {
        return app(static::getSettingsClass());
    }

    protected function getFormOperation(): string
    {
        return 'edit';
    }

    /**
     * Fill the form from the settings, through `mutateFormDataBeforeFill()`. Only the values
     * the form's fields read reach the browser.
     */
    protected function fillFormState(Schema $form): void
    {
        $data = $this->mutateFormDataBeforeFill($this->getSettings()->toArray());

        $keys = array_values(array_unique(array_map(
            static fn (int|string $path): string => explode('.', (string) $path)[0],
            array_keys($form->getFlatFields(withHidden: true)),
        )));

        $form->fill(Arr::only($data, $keys));
    }

    /**
     * @param  array<string, mixed>  $data  The settings' values
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $data;
    }

    /**
     * @param  array<string, mixed>  $data  Validated form data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $data;
    }

    protected function getFormActions(): array
    {
        return [$this->getSaveFormAction()];
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')
            ->label(__('alfakit-settings::settings.save'))
            ->color(Color::Primary)
            ->button()
            ->keyBindings(['mod+s'])
            ->form(fn (): array => $this->getForm()?->getComponents(withHidden: true) ?? [])
            ->fillForm(fn (): array => $this->getForm()?->getRawState() ?? [])
            ->beforeFormValidated(fn () => $this->callHook('beforeValidate'))
            ->afterFormValidated(fn () => $this->callHook('afterValidate'))
            ->action(fn (array $data): mixed => $this->save($data));
    }

    /**
     * Runs after validation: `beforeSave()`, `mutateFormDataBeforeSave()`, saving the
     * settings, `afterSave()`, then the saved notification. Call `$this->halt()` from a
     * hook to stop without saving.
     *
     * @param  array<string, mixed>  $data  Validated form data
     */
    public function save(array $data): mixed
    {
        abort_unless(static::canAccess(), 403);

        $this->callHook('beforeSave', [$data]);

        $data = $this->mutateFormDataBeforeSave($data);
        $settings = $this->getSettings();

        // Only the settings' own properties; other form values (confirmations…) are dropped.
        $settings->fill(Arr::only($data, array_keys($settings->toArray())));
        $settings->save();

        $this->callHook('afterSave');

        $this->getSavedNotification()?->send();

        $url = $this->getRedirectUrl();

        return $url !== null ? redirect()->to($url) : back();
    }

    protected function getSavedNotification(): ?Notification
    {
        $title = $this->getSavedNotificationTitle();

        return $title === null ? null : Notification::make()->success()->title($title);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('alfakit-settings::settings.saved');
    }

    /**
     * Where to go after saving; null stays on the page.
     */
    protected function getRedirectUrl(): ?string
    {
        return null;
    }
}
