<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitSettings\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use Spatie\LaravelSettings\Settings;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\text;

/**
 * `make:alfakit-settings-page ManageGeneral GeneralSettings`: a settings page, plus the
 * settings class when it doesn't exist yet. The form gets a field per typed property of
 * an existing settings class.
 */
#[AsCommand(name: 'make:alfakit-settings-page', description: 'Create an AlfaKit settings page (and its Spatie settings class)')]
class MakeSettingsPageCommand extends Command
{
    protected $signature = 'make:alfakit-settings-page {name? : The page class name, e.g. ManageGeneral}
        {settings? : The settings class, e.g. GeneralSettings or App\\Settings\\GeneralSettings}
        {--group= : The settings group of a new settings class (default: derived from its name)}
        {--panel= : The panel the page belongs to}
        {--force : Overwrite existing files}';

    public function handle(Filesystem $files): int
    {
        $name = Str::studly((string) ($this->argument('name') ?? text('Page name', 'ManageGeneral', required: true)));
        $settings = (string) ($this->argument('settings') ?? Str::of($name)->replaceStart('Manage', '')->replaceEnd('Settings', '')->append('Settings'));
        $settingsClass = str_contains($settings, '\\') ? ltrim($settings, '\\') : 'App\\Settings\\'.Str::studly($settings);
        $settingsBaseClass = class_basename($settingsClass);
        $force = (bool) $this->option('force');

        if (! class_exists($settingsClass)) {
            $group = (string) ($this->option('group') ?? Str::snake(Str::replaceEnd('Settings', '', $settingsBaseClass)));

            $this->writeStub($files, 'settings', $this->classPath($settingsClass), [
                'namespace' => Str::beforeLast($settingsClass, '\\'),
                'class' => $settingsBaseClass,
                'group' => $group !== '' ? $group : 'general',
            ], $force);
        }

        [$fields, $imports] = $this->fieldsFor($settingsClass);

        $suffix = $this->panelNamespaceSuffix();
        $namespace = $this->generationNamespace().$suffix.'\\Pages';

        $this->writeStub($files, 'settings-page', $this->generationPath().str_replace('\\', '/', $suffix)."/Pages/{$name}.php", [
            'namespace' => $namespace,
            'class' => $name,
            'title' => Str::headline(Str::replaceStart('Manage', '', $name)),
            'settingsClass' => $settingsClass,
            'settingsBaseClass' => $settingsBaseClass,
            'imports' => implode("\n", array_map(static fn (string $class): string => "use {$class};", $imports)),
            'fields' => $fields,
        ], $force);

        return self::SUCCESS;
    }

    /**
     * A form field per typed public property of the settings class, and the classes to
     * import; a commented example when the class has none (or doesn't exist yet).
     *
     * @return array{0: string, 1: list<string>}
     */
    protected function fieldsFor(string $settingsClass): array
    {
        $fields = [];
        $imports = [];

        if (class_exists($settingsClass) && is_subclass_of($settingsClass, Settings::class)) {
            foreach ((new ReflectionClass($settingsClass))->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
                if ($property->isStatic() || $property->getDeclaringClass()->getName() === Settings::class) {
                    continue;
                }

                [$component, $field] = $this->fieldForProperty($property);
                $imports[] = "AlfaSix\\Alfakit\\Forms\\Components\\{$component}";
                $fields[] = "            {$field},";
            }
        }

        if ($fields === []) {
            $imports[] = 'AlfaSix\\Alfakit\\Forms\\Components\\TextInput';
            $fields[] = "            // TextInput::make('site_name')->required(),";
        }

        $imports = array_values(array_unique($imports));
        sort($imports);

        return [implode("\n", $fields), $imports];
    }

    /**
     * @return array{0: string, 1: string} The component class and its PHP code
     */
    protected function fieldForProperty(ReflectionProperty $property): array
    {
        $name = $property->getName();
        $type = $property->getType();
        $typeName = $type instanceof ReflectionNamedType ? $type->getName() : null;
        $required = $type !== null && ! $type->allowsNull() ? '->required()' : '';

        return match ($typeName) {
            'bool' => ['Toggle', "Toggle::make('{$name}')"],
            'int' => ['TextInput', "TextInput::make('{$name}')->integer(){$required}"],
            'float' => ['TextInput', "TextInput::make('{$name}')->numeric(){$required}"],
            'array' => ['TagsInput', "TagsInput::make('{$name}')"],
            default => ['TextInput', "TextInput::make('{$name}'){$required}"],
        };
    }

    /**
     * @param  array<string, string>  $replacements
     */
    protected function writeStub(Filesystem $files, string $stub, string $path, array $replacements, bool $force): void
    {
        if ($files->exists($path) && ! $force) {
            $this->components->warn(sprintf('%s already exists, skipping. Use --force to overwrite.', Str::after($path, base_path().'/')));

            return;
        }

        $published = base_path("stubs/alfakit-settings/{$stub}.stub");
        $contents = $files->get(file_exists($published) ? $published : dirname(__DIR__, 2)."/stubs/{$stub}.stub");

        foreach ($replacements as $key => $value) {
            $contents = str_replace("{{ {$key} }}", $value, $contents);
        }

        // An empty import list leaves a blank line behind.
        $contents = (string) preg_replace("/\n{3,}/", "\n\n", $contents);

        $files->ensureDirectoryExists(dirname($path));
        $files->put($path, $contents);

        $this->components->info(sprintf('Created %s', Str::after($path, base_path().'/')));
    }

    /**
     * Where a class of the app's `App\` namespace lives.
     */
    protected function classPath(string $class): string
    {
        $relative = str_replace('\\', '/', Str::after($class, 'App\\'));

        return Str::startsWith($class, 'App\\') ? app_path("{$relative}.php") : base_path(str_replace('\\', '/', $class).'.php');
    }

    protected function generationNamespace(): string
    {
        return rtrim((string) config('alfakit.file_generation.namespace', 'App\\Alfakit'), '\\');
    }

    protected function generationPath(): string
    {
        return rtrim((string) config('alfakit.file_generation.path', app_path('Alfakit')), '/');
    }

    /**
     * `\Admin` when the app has several panels (like the other generators).
     */
    protected function panelNamespaceSuffix(): string
    {
        $panelId = $this->option('panel') ?? alfakit()->getDefaultPanel()?->getId();

        if (! is_string($panelId) || count(alfakit()->getPanels()) <= 1) {
            return '';
        }

        return '\\'.Str::studly($panelId);
    }
}
