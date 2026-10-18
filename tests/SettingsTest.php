<?php

declare(strict_types=1);

use AlfaSix\Alfakit\Notifications\Notification;
use AlfaSix\AlfakitSettings\SettingsPlugin;
use AlfaSix\AlfakitSettings\Tests\Fixtures\GeneralSettings;
use AlfaSix\AlfakitSettings\Tests\Fixtures\ManageGeneral;
use AlfaSix\AlfakitSettings\Tests\TestCase as SettingsTestCase;
use Illuminate\Filesystem\Filesystem;

uses(SettingsTestCase::class);

beforeEach(function (): void {
    ManageGeneral::$allowed = true;
    ManageGeneral::$hooks = [];
});

function stTest(): SettingsTestCase
{
    return SettingsTestCase::current();
}

function stToken(): string
{
    stTest()->actingAs(stTest()->admin());

    return stTest()->pageProps('/settings/general')['token'];
}

it('registers the plugin and its pages on a panel', function (): void {
    $panel = alfakit()->getPanel('settings');

    expect($panel->hasPlugin('settings'))->toBeTrue()
        ->and($panel->getPlugin('settings'))->toBeInstanceOf(SettingsPlugin::class)
        ->and($panel->getPages())->toContain(ManageGeneral::class);
});

it('fills the form from the settings, without the values the form does not show', function (): void {
    stTest()->actingAs(stTest()->admin());

    $page = stTest()->pageProps('/settings/general');
    $form = $page['content'][0];

    expect($form['type'])->toBe('Form')
        ->and($form['operation'])->toBe('edit')
        ->and($form['state'])->toBe(['site_name' => 'ALFAKIT', 'maintenance_mode' => false, 'per_page' => 25])
        ->and(array_column($form['actions'], 'name'))->toBe(['save'])
        ->and(json_encode($page))->not->toContain('top-secret');

    alfakit()->setCurrentPanel(alfakit()->getPanel('settings'));

    expect(ManageGeneral::getNavigationGroup())->toBe('Configuration');
});

it('saves the settings', function (): void {
    $token = stToken();

    stTest()->runAction($token, 'save', ['site_name' => ' My site ', 'maintenance_mode' => true, 'per_page' => 50])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $settings = app(GeneralSettings::class);

    expect($settings->site_name)->toBe('My site!')
        ->and($settings->maintenance_mode)->toBeTrue()
        ->and($settings->per_page)->toBe(50)
        ->and($settings->api_secret)->toBe('top-secret')
        ->and(ManageGeneral::$hooks)->toBe(['beforeSave', 'afterSave']);

    Notification::assertNotified('Saved');
});

it('validates the form before saving', function (): void {
    $token = stToken();

    stTest()->postJson('/settings/_alfakit/actions', ['token' => $token, 'action' => 'save', 'data' => ['site_name' => '', 'maintenance_mode' => false, 'per_page' => 10]])
        ->assertStatus(422)
        ->assertJsonValidationErrors('site_name');

    expect(app(GeneralSettings::class)->site_name)->toBe('AlfaKit');
});

it('denies the page and its save action without access', function (): void {
    $token = stToken();

    ManageGeneral::$allowed = false;

    stTest()->inertiaGet('/settings/general')->assertForbidden();
    stTest()->postJson('/settings/_alfakit/actions', ['token' => $token, 'action' => 'save', 'data' => ['site_name' => 'Hacked', 'maintenance_mode' => true, 'per_page' => 1]])
        ->assertForbidden();

    expect(app(GeneralSettings::class)->site_name)->toBe('AlfaKit');
});

it('generates a settings page with a field per setting', function (): void {
    $files = new Filesystem;
    $directory = storage_path('framework/alfakit-settings-generator');
    config(['alfakit.file_generation.path' => $directory, 'alfakit.file_generation.namespace' => 'App\\Alfakit']);
    $page = "{$directory}/Settings/Pages/ManageGeneral.php";

    try {
        stTest()->artisan('make:alfakit-settings-page', ['name' => 'ManageGeneral', 'settings' => GeneralSettings::class, '--panel' => 'settings'])
            ->assertSuccessful();

        $contents = $files->get($page);

        expect($contents)
            ->toContain('namespace App\Alfakit\Settings\Pages;')
            ->toContain('class ManageGeneral extends SettingsPage')
            ->toContain('protected static string $settings = GeneralSettings::class;')
            ->toContain('use AlfaSix\AlfakitSettings\Tests\Fixtures\GeneralSettings;')
            ->toContain("TextInput::make('site_name')->required(),")
            ->toContain("Toggle::make('maintenance_mode'),")
            ->toContain("TextInput::make('per_page')->integer()->required(),")
            ->toContain("TextInput::make('api_secret'),")
            ->toContain('use AlfaSix\Alfakit\Forms\Components\Toggle;');
    } finally {
        $files->deleteDirectory($directory);
    }
});

it('generates the settings class when it does not exist', function (): void {
    $files = new Filesystem;
    $directory = storage_path('framework/alfakit-settings-generator');
    config(['alfakit.file_generation.path' => $directory]);
    $page = "{$directory}/Settings/Pages/ManageFooter.php";
    $settings = app_path('Settings/FooterSettings.php');

    try {
        stTest()->artisan('make:alfakit-settings-page', ['name' => 'ManageFooter', '--panel' => 'settings'])->assertSuccessful();

        expect($files->get($settings))
            ->toContain('namespace App\Settings;')
            ->toContain('class FooterSettings extends Settings')
            ->toContain("return 'footer';")
            ->and($files->get($page))
            ->toContain('use App\Settings\FooterSettings;')
            ->toContain("// TextInput::make('site_name')->required(),");
    } finally {
        $files->deleteDirectory($directory);
        $files->delete($settings);
    }
});
