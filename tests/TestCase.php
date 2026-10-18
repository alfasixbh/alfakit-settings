<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitSettings\Tests;

use AlfaSix\Alfakit\Tests\Fixtures\Models\User;
use AlfaSix\Alfakit\Tests\TestCase as BaseTestCase;
use AlfaSix\AlfakitSettings\AlfakitSettingsServiceProvider;
use AlfaSix\AlfakitSettings\Tests\Fixtures\SettingsPanelProvider;
use Illuminate\Testing\TestResponse;
use LogicException;
use Pest\TestSuite;
use Spatie\LaravelSettings\LaravelSettingsServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            LaravelSettingsServiceProvider::class,
            AlfakitSettingsServiceProvider::class,
            SettingsPanelProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('settings.default_repository', 'database');
        $app['config']->set('settings.auto_discover_settings', []);
        $app['config']->set('settings.cache.enabled', false);
    }

    protected function defineDatabaseMigrations(): void
    {
        parent::defineDatabaseMigrations();

        $this->loadMigrationsFrom(__DIR__.'/Fixtures/database/migrations');
    }

    /**
     * The running test (Pest binds `$this` to it; this keeps static analysis typed).
     */
    public static function current(): static
    {
        $test = TestSuite::getInstance()->test;

        if (! $test instanceof static) {
            throw new LogicException('No test of this suite is running.');
        }

        return $test;
    }

    public function admin(): User
    {
        return User::query()->create(['name' => 'Admin', 'email' => 'admin'.uniqid().'@example.com', 'password' => 'password']);
    }

    public function inertiaGet(string $url): TestResponse
    {
        return $this->get($url, ['X-Inertia' => 'true', 'X-Inertia-Version' => '']);
    }

    /**
     * @return array<string, mixed>
     */
    public function pageProps(string $url): array
    {
        return $this->inertiaGet($url)->assertOk()->json('props.page');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function runAction(string $token, string $action, array $data = []): TestResponse
    {
        return $this->post('/settings/_alfakit/actions', ['token' => $token, 'action' => $action, 'data' => $data], [
            'X-Inertia' => 'true', 'X-Inertia-Version' => '', 'Accept' => 'application/json',
        ]);
    }
}
