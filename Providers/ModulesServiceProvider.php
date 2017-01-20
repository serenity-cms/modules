<?php

namespace Serenity\Modules\Providers;

use Illuminate\Support\ServiceProvider;

use Serenity\ModulesContracts\ModuleContract;
use Serenity\ModulesContracts\ModuleFactoryContract;
use Serenity\ModulesContracts\ModulesManagerContract;
use Serenity\ModulesContracts\InstallationRepositoryContract;

use Serenity\Modules\Module;
use Serenity\Modules\ModuleFactory;
use Serenity\Modules\ModulesManager;
use Serenity\Modules\InstallationRepository;

class ModulesServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the modules service.
     *
     * @return void
     */
    public function boot(ModulesManagerContract $modulesManager)
    {
        // Add dirs where to find modules from config
        foreach (config('modules.dirs') as $dir)
        {
            $modulesManager->addDir(base_path($dir));
        }

        // Load modules
        $modulesManager->reload();

        // Boot installed modules
        foreach ($modulesManager->installed() as $module)
        {
            // Register module providers
            $module->registerProviders();

            // Require module files
            $module->requireFiles();
        }
    }

    /**
     * Register the modules service.
     *
     * @return void
     */
    public function register()
    {
        // Publish configuration file
        $this->publishes([
            __DIR__ . '/../Config/modules.php' => config_path('modules.php'),
        ], 'config');

        // Merge configuration file
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/modules.php', 'modules'
        );

        // Bind InstallationRepositoryContract implementation
        $this->app->bind(InstallationRepositoryContract::class, InstallationRepository::class, true);

        // Bind ModulesManagerContract implementation
        $this->app->bind(ModulesManagerContract::class, ModulesManager::class, true);

        // Bind ModuleContract implementation
        $this->app->bind(ModuleContract::class, Module::class);

        // Bind ModuleFactoryContract implementation
        $this->app->bind(ModuleFactoryContract::class, ModuleFactory::class, true);
    }
}
