<?php

namespace App\Support\Modules;

/**
 * Menyimpan daftar modul aktif agar bisa dibaca seeder dan halaman admin.
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
     * Gabungan permission semua modul.
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
