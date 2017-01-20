<?php

namespace Serenity\Modules;

use Illuminate\Support\Facades\App;

use Serenity\Modules\Exceptions\ModuleProtectionException;
use Serenity\Modules\Exceptions\ModuleDoesNotExistException;
use Serenity\Modules\Exceptions\ModuleAlreadyExistsException;
use Serenity\Modules\Exceptions\ModuleAlreadyInstalledException;
use Serenity\Modules\Exceptions\ModuleAlreadyUninstalledException;
use Serenity\Modules\Exceptions\ModuleInstallerDoesNotExistException;
use Serenity\Modules\Exceptions\ModuleInstallerNotCompatibleException;
use Serenity\Modules\Exceptions\ModuleUninstallerDoesNotExistException;
use Serenity\Modules\Exceptions\ModuleUninstallerNotCompatibleException;

use Serenity\ModulesContracts\ModuleContract;
use Serenity\ModulesContracts\InstallerContract;
use Serenity\ModulesContracts\UninstallerContract;
use Serenity\ModulesContracts\ModuleFactoryContract;
use Serenity\ModulesContracts\ModulesManagerContract;
use Serenity\ModulesContracts\InstallationRepositoryContract;

class ModulesManager implements ModulesManagerContract {

    /**
     * Module manager directories.
     *
     * @var array of strings (directories)
     */
    protected $dirs = [];

    /**
     * An array of all modules. Array key is the module name, array value is the module instance.
     *
     * @var array of Serenity\ModulesContracts\ModuleContract
     */
    protected $modules = null;

    /**
     * An array of all installed modules. Array key is the module name, array value is the module instance.
     *
     * @var array of Serenity\ModulesContracts\ModuleContract
     */
    protected $installedModules = null;

    /**
     * An array of all uninstalled modules. Array key is the module name, array value is the module instance.
     *
     * @var array of Serenity\ModulesContracts\ModuleContract
     */
    protected $uninstalledModules = null;

    /**
     * Module factory instance.
     *
     * @var Serenity\ModulesContracts\ModuleFactoryContract
     */
    protected $moduleFactory = null;

    /**
     * Installation repository instance.
     *
     * @var Serenity\ModulesContracts\InstallationRepositoryContract
     */
    protected $installationRepository = null;

    /**
     * Construct the modules manager class.
     */
    public function __construct()
    {
        // Get the module factory instance
        $this->moduleFactory = App::make(ModuleFactoryContract::class);

        // Get the installation repository instance
        $this->installationRepository = App::make(InstallationRepositoryContract::class);
    }

    /**
     * Add multiple directories.
     *
     * @param array $dirs An array of directories.
     *
     * @return $this
     */
    public function addDirs(array $dirs = [])
    {
        // Add dirs
        foreach ($dirs as $dir)
        {
            $this->addDir($dir);
        }

        return $this;
    }

    /**
     * Add one directory.
     *
     * @param string $dir A directory.
     *
     * @return $this
     */
    public function addDir($dir)
    {
        // If we already got the dir, continue
        if ( ! $this->hasDir($dir))
        {
            // Add directory
            $this->dirs[] = $dir;
        }

        return $this;
    }

    /**
     * Determine, if modules manager has the directory.
     *
     * @param string $dir Directoy.
     *
     * @return bool
     */
    public function hasDir($dir)
    {
        return array_search($dir, $this->dirs) !== false;
    }

    /**
     * Remove directory from modules manager.
     *
     * @param string $dir Directory.
     *
     * @return $this
     */
    public function removeDir($dir)
    {
        if (($key = array_search($dir, $this->dirs)) !== false) {
            unset($this->dirs[$key]);
        }

        return $this;
    }

    /**
     * Get all modules manager directories.
     *
     * @return array Modules manager directories.
     */
    public function getDirs()
    {
        return $this->dirs;
    }

    /**
     * Find all modules in set directories.
     *
     * @return array of Serenity\ModulesContracts\ModuleContract
     */
    public function all()
    {
        return $this->modules;
    }

    /**
     * Find the module by it's name.
     *
     * @param string $moduleName Name of the module.
     *
     * @return Serenity\ModulesContracts\ModuleContract
     *
     * @throws Serenity\Modules\Exceptions\ModuleDoesNotExistException
     */
    public function get($moduleName)
    {
        if ( ! $this->has($moduleName)) {
            throw new ModuleDoesNotExistException("Module [$moduleName] does not exist.");
        }

        return $this->modules[$moduleName];
    }

    /**
     * Find all installed modules in set directories.
     *
     * @return array of Serenity\ModulesContracts\ModuleContract
     */
    public function installed()
    {
        return $this->installedModules;
    }

    /**
     * Find all uninstalled modules in set directories.
     *
     * @return array of Serenity\ModulesContracts\ModuleContract
     */
    public function uninstalled()
    {
        return $this->uninstalledModules;
    }

    /**
     * Determine, if the module exists.
     *
     * @param string $moduleName Name of the module.
     *
     * @return bool
     */
    public function has($moduleName)
    {
        return (isset($this->modules[$moduleName]));
    }

    /**
     * Reload and recache modules.
     *
     * @return $this
     *
     * @throws Serenity\Modules\Exceptions\ModuleAlreadyExistsException when multiple modules with the same name are found.
     */
    public function reload()
    {
        // Clear arrays
        $this->modules = [];
        $this->installedModules = [];
        $this->uninstalledModules = [];

        // Reload installation repository
        $this->installationRepository->reload();

        // Find all modules
        $found = [];

        foreach ($this->dirs as $dir)
        {
            $found = array_merge($found, glob($dir . '/' . ModuleContract::METAFILE));
        }

        // Create modules
        foreach ($found as $pathToMetaFile)
        {
            // Create module
            $module = $this->moduleFactory->createModuleInstance($pathToMetaFile);

            // Get module name
            $moduleName = $module->getName();

            // Do we already have this module with the same name ?
            if (isset($this->modules[$moduleName]))
            {
                throw new ModuleAlreadyExistsException("Module [$moduleName] already exists.");
                continue;
            }

            // Add module to arrays
            $this->modules[$moduleName] = $module;

            if ($this->installationRepository->isInstalled($moduleName))
            {
                $this->installedModules[$moduleName] = $module;
            }
            else
            {
                $this->uninstalledModules[$moduleName] = $module;
            }
        }

        return $this;
    }

    /**
     * Install all uninstalled modules.
     *
     * @return $this
     */
    public function installAll()
    {
        foreach ($this->uninstalled() as $module)
        {
            $this->installByInstance($module);
        }

        return $this;
    }

    /**
     * Install the module.
     *
     * @param string $moduleName Name of the module.
     *
     * @return $this
     *
     * @throws Serenity\Modules\Exceptions\ModuleAlreadyInstalledException
     * @throws Serenity\Modules\Exceptions\ModuleInstallerDoesNotExistException
     * @throws Serenity\Modules\Exceptions\ModuleInstallerNotCompatibleException
     */
    public function install($moduleName)
    {
        return $this->installByInstance($this->get($moduleName));
    }

    /**
     * Install the module by it's instance.
     *
     * @param Serenity\ModulesContracts\ModuleContract $module Module instance.
     *
     * @return $this
     *
     * @throws Serenity\Modules\Exceptions\ModuleAlreadyInstalledException
     * @throws Serenity\Modules\Exceptions\ModuleInstallerDoesNotExistException
     * @throws Serenity\Modules\Exceptions\ModuleInstallerNotCompatibleException
     */
    public function installByInstance(ModuleContract $module)
    {
        // Is the module already installed ?
        if ($this->isInstalledByInstance($module))
        {
            throw new ModuleAlreadyInstalledException("Module [{$module->getName()}] is already installed.");
        }

        // Get installer
        $installer = $module->getInstaller();

        if ( ! is_null($installer))
        {
            // Does installer class exist ?
            if ( ! class_exists($installer))
            {
                throw new ModuleInstallerDoesNotExistException("Installer class [$installer] does not exist.");
            }

            // Does installer class implement the contract ?
            $installerImplements = class_implements($installer);
            if ( ! isset($installerImplements[InstallerContract::class]))
            {
                throw new ModuleInstallerNotCompatibleException("Installer class [$installer] must implement [Serenity\ModulesContracts\InstallerContract] interface.");
            }

            // Call installer
            call_user_func([$installer, 'install'], $module, $this);
        }

        // Get module name
        $moduleName = $module->getName();

        // Update installation repository
        $this->installationRepository->install($moduleName);

        // Update cache arrays
        unset($this->uninstalledModules[$moduleName]);
        $this->installedModules[$moduleName] = $module;

        return $this;
    }

    /**
     * Determine, if the module is installed.
     *
     * @param string $moduleName Name of the module.
     *
     * @return bool
     */
    public function isInstalled($moduleName)
    {
        return (isset($this->installedModules[$moduleName]));
    }

    /**
     * Determine, if the module is installed by it's instance.
     *
     * @param Serenity\ModulesContracts\ModuleContract $module Module instance.
     *
     * @return
     */
    public function isInstalledByInstance(ModuleContract $module)
    {
        return $this->isInstalled($module->getName());
    }

    /**
     * Uninstall all installed modules.
     *
     * @return $this
     */
    public function uninstallAll()
    {
        foreach ($this->installed() as $module)
        {
            $this->uninstallByInstance($module);
        }

        return $this;
    }

    /**
     * Uninstall the module.
     *
     * @param string $moduleName Name of the module.
     *
     * @return $this
     *
     * @throws Serenity\Modules\Exceptions\ModuleProtectionException
     * @throws Serenity\Modules\Exceptions\ModuleAlreadyUninstalledException
     * @throws Serenity\Modules\Exceptions\ModuleUninstallerDoesNotExistException
     * @throws Serenity\Modules\Exceptions\ModuleUninstallerNotCompatibleException
     */
    public function uninstall($moduleName)
    {
        return $this->uninstallByInstance($this->get($moduleName));
    }

    /**
     * Uninstall the module by it's instance.
     *
     * @param Serenity\ModulesContracts\ModuleContract $module Module instance.
     *
     * @return $this
     *
     * @throws Serenity\Modules\Exceptions\ModuleProtectionException
     * @throws Serenity\Modules\Exceptions\ModuleAlreadyUninstalledException
     * @throws Serenity\Modules\Exceptions\ModuleUninstallerDoesNotExistException
     * @throws Serenity\Modules\Exceptions\ModuleUninstallerNotCompatibleException
     */
    public function uninstallByInstance(ModuleContract $module)
    {
        // Is the module protected ? We can not uninstall protected modules.
        if ($module->isProtected())
        {
            throw new ModuleProtectionException("Module [{$module->getName()}] cannot be uninstalled, because it is protected.");
        }

        // Is the module already uninstalled ?
        if ( ! $this->isInstalledByInstance($module))
        {
            throw new ModuleAlreadyUninstalledException("Module [{$module->getName()}] is already uninstalled.");
        }

        // Get uninstaller
        $uninstaller = $module->getUninstaller();

        if ( ! is_null($uninstaller))
        {
            // Does uninstaller class exist ?
            if ( ! class_exists($uninstaller))
            {
                throw new ModuleUninstallerDoesNotExistException("Uninstaller class [$uninstaller] does not exist.");
            }

            // Does uninstaller class implement the contract ?
            $uninstallerImplements = class_implements($uninstaller);
            if ( ! isset($uninstallerImplements[UninstallerContract::class]))
            {
                throw new ModuleUninstallerNotCompatibleException("Uninstaller class [$uninstaller] must implement [Serenity\ModulesContracts\UninstallerContract] interface.");
            }

            // Call uninstaller
            call_user_func([$uninstaller, 'uninstall'], $module, $this);
        }

        // Get module name
        $moduleName = $module->getName();

        // Update installation repository
        $this->installationRepository->uninstall($moduleName);

        // Update cache arrays
        unset($this->installedModules[$moduleName]);
        $this->uninstalledModules[$moduleName] = $module;

        return $this;
    }

}
