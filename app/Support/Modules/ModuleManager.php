<?php

namespace App\Support\Modules;

/**
 * Keeps the list of enabled modules for seeders and admin pages.
 */
class ModuleManager
{
    /** @var array<int, ModuleServiceProvider> */
    protected array $modules = [];

    public function register(ModuleServiceProvider $module): void
    {
        $this->modules[$module->name()] = $module;
    }

    /** @return array<string, ModuleServiceProvider> */
    public function all(): array
    {
        return $this->modules;
    }

    /**
     * Permissions of every module combined.
     *
     * @return array<string, array<int, string>>
     */
    public function permissions(): array
    {
        return collect($this->modules)
            ->flatMap(fn (ModuleServiceProvider $module) => $module->permissions())
            ->all();
    }
}
