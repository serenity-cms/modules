<?php

namespace Serenity\Modules;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Serenity\Modules\Migrations\ModulesManagerMigration;
use Serenity\ModulesContracts\InstallationRepositoryContract;

class InstallationRepository implements InstallationRepositoryContract {

    /**
     * An array of installed modules.
     *
     * @var array
     */
    protected $installedModules = [];

    /**
     * Reload and recache modules.
     *
     * @return $this
     */
    public function reload()
    {
        if ( ! Schema::hasTable(ModulesManagerMigration::MODULES_TABLE_NAME))
        {
            (new ModulesManagerMigration)->up();
        }

        $this->installedModules = DB::table(ModulesManagerMigration::MODULES_TABLE_NAME)
            ->select('module')
            ->pluck('module', 'module')
            ->toArray();

        return $this;
    }

    /**
     * Mark the module as installed.
     *
     * @param string $moduleName Name of the module.
     *
     * @return $this
     */
    public function install($moduleName)
    {
        if (DB::table(ModulesManagerMigration::MODULES_TABLE_NAME)->where('module', $moduleName)->count() == 0)
        {
            DB::table(ModulesManagerMigration::MODULES_TABLE_NAME)->insert(
                ['module' => $moduleName]
            );

            $this->installedModules[$moduleName] = $moduleName;
        }

        return $this;
    }

    /**
     * Mark the module as uninstalled.
     *
     * @param string $moduleName Name of the module.
     *
     * @return $this
     */
    public function uninstall($moduleName)
    {
        DB::table(ModulesManagerMigration::MODULES_TABLE_NAME)
            ->where('module', $moduleName)
            ->delete();

        unset($this->installedModules[$moduleName]);

        return $this;
    }

    /**
     * Get the array of modules, which are marked as installed.
     *
     * @return array
     */
    public function getInstalled()
    {
        return $this->installedModules;
    }

    /**
     * Determine, if the module is marked as installed.
     *
     * @param string $moduleName Name of the module.
     *
     * @return bool
     */
    public function isInstalled($moduleName)
    {
        return isset($this->installedModules[$moduleName]);
    }

}
