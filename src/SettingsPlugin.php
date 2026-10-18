<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitSettings;

use AlfaSix\Alfakit\Contracts\Plugin;
use AlfaSix\Alfakit\Panel;
use AlfaSix\AlfakitSettings\Pages\SettingsPage;
use Closure;
use UnitEnum;

/**
 * Registers settings pages on a panel and gives them a shared navigation group. Settings
 * pages found by `discoverPages()` work without the plugin.
 *
 * ```php
 * $panel->plugin(SettingsPlugin::make()->pages([ManageGeneral::class])->navigationGroup('Settings'));
 * ```
 */
class SettingsPlugin implements Plugin
{
    public const ID = 'settings';

    /** @var array<class-string<SettingsPage>> */
    protected array $pages = [];

    protected string|UnitEnum|Closure|null $navigationGroup = null;

    public static function make(): static
    {
        return new static;
    }

    /**
     * The plugin registered on the current panel.
     */
    public static function get(): static
    {
        /** @var static */
        return alfakit()->getCurrentOrDefaultPanel()->getPlugin(self::ID);
    }

    public function getId(): string
    {
        return self::ID;
    }

    /**
     * @param  array<class-string<SettingsPage>>  $pages
     */
    public function pages(array $pages): static
    {
        $this->pages = [...$this->pages, ...$pages];

        return $this;
    }

    /**
     * @return array<class-string<SettingsPage>>
     */
    public function getPages(): array
    {
        return $this->pages;
    }

    /**
     * The navigation group of every settings page that doesn't set its own.
     */
    public function navigationGroup(string|UnitEnum|Closure|null $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function getNavigationGroup(): string|UnitEnum|null
    {
        $group = $this->navigationGroup instanceof Closure ? ($this->navigationGroup)() : $this->navigationGroup;

        return $group instanceof UnitEnum || is_string($group) ? $group : null;
    }

    public function register(Panel $panel): void
    {
        if ($this->pages !== []) {
            $panel->pages($this->pages);
        }
    }

    public function boot(Panel $panel): void {}
}
