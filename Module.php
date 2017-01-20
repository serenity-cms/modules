<?php

namespace Serenity\Modules;

use Illuminate\Support\Facades\App;
use Serenity\Modules\Facades\Modules;
use Serenity\ModulesContracts\ModuleContract;

class Module implements ModuleContract
{
    /**
     * Path to the root of the module.
     *
     * @var string
     */
    protected $path;

    /**
     * Module name.
     *
     * @var string
     */
    protected $name;

    /**
     * Indicates if the module is protected. Protected modules could not be
     * uninstalled.
     *
     * @var string
     */
    protected $protected;

    /**
     * An array of the module service providers.
     *
     * @var string
     */
    protected $providers;

    /**
     * An array of the module required files. These files will be required (once)
     * during the module booting.
     *
     * @var string
     */
    protected $files;

    /**
     * Full name of the module installer class.
     *
     * @var string
     */
    protected $installer;

    /**
     * Full name of the module uninstaller class.
     *
     * @var string
     */
    protected $uninstaller;

    /**
     * Module constructor.
     *
     * @param string  $path Path to the root of the module.
     * @param string  $name Module name.
     * @param bool  $protected Indicates if the module is protected. Protected modules
     * could not be uninstalled.
     * @param array  $providers An array of the module service providers.
     * @param array  $files An array of the module required files. These files will be
     * required (once) during the module booting.
     * @param string  $installer Full name of the module installer class.
     * @param string  $uninstaller Full name of the module uninstaller class.
     */
    public function __construct(
        $path,
        $name,
        $protected = false,
        array $providers = [],
        array $files = [],
        $installer = null,
        $uninstaller = null)
    {
        $this->path = $path;
        $this->name = $name;
        $this->protected = boolval($protected);
        $this->providers = $providers;
        $this->files = $files;
        $this->installer = $installer;
        $this->uninstaller = $uninstaller;
    }

    /**
     * Get path to the root of the module.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get module name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Determine if the module is installed.
     *
     * @return bool
     */
    public function isInstalled()
    {
        return Modules::isInstalledByInstance($this);
    }

    /**
     * Determine if the module is protected. Protected modules could not be
     * uninstalled.
     *
     * @return bool
     */
    public function isProtected()
    {
        return $this->protected;
    }

    /**
     * Get an array of the module service providers.
     *
     * @return array
     */
    public function getProviders()
    {
        return $this->providers;
    }

    /**
     * Register module service providers to the application.
     *
     * @return void
     */
    public function registerProviders()
    {
        if (is_array($this->providers))
        {
            foreach($this->providers as $provider)
            {
                App::register($provider);
            }
        }
    }

    /**
     * Get an array of the module required files.
     *
     * @return array
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * Load (require once) the module required files.
     *
     * @return void
     */
    public function requireFiles()
    {
        if (is_array($this->files))
        {
            foreach ($this->files as $file)
            {
                require_once $this->path . '/' . $file;
            }
        }
    }

    /**
     * Get full name of the module installer class.
     *
     * @return string
     */
    public function getInstaller()
    {
        return $this->installer;
    }

    /**
     * Install the module.
     *
     * @return void
     */
    public function install()
    {
        return Modules::installByInstance($this);
    }

    /**
     * Get full name of the module uninstaller class.
     *
     * @return string
     */
    public function getUninstaller()
    {
        return $this->uninstaller;
    }

    /**
     * Uninstall the module.
     *
     * @return void
     */
    public function uninstall()
    {
        return Modules::uninstallByInstance($this);
    }
}
