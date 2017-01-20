# Serenity CMS

## Modules Package

## Installation

Use [Composer](https://getcomposer.org) to install the package:

```
composer require serenity-cms/modules
```

## Add the Service Provider and the Class Alias

Open up `config/app.php` and add the following line under `providers` array:

```php
'providers' => [
    Serenity\Modules\Providers\ModulesServiceProvider::class,
],
```

Then, add the following line under `aliases` array:

```php
'aliases' => [
    'Modules' => Serenity\Modules\Facades\Modules::class,
],
```

## Configuration

Run the following artisan command:

```
php artisan vendor:publish
```

Then, you can configure this package in `config/modules.php` configuration file.

## Creating modules

Create file `serenity-module.json` in the `workbench/{SOME_VENDOR_NAME}/{SOME_PACKAGE_NAME}` directory. Replace `{SOME_VENDOR_NAME}` and `{SOME_PACKAGE_NAME}` with your values. The file should contains JSON looks like this:
```
{
    "name": "Module Name",
    "protected": false,
    "providers": [
        "Test\\Test\\MyServiceProvider"
    ],
    "files": [
        "start.php"
    ],
    "installer": "Test\\Test\\Installer",
    "uninstaller": null
}
```

### JSON Attributes

 - `name (string)` - The unique module name.
 - `protected (boolean)` - Determine, if the module is protected. Protected modules cannot be uninstalled, only installed.
 - `providers (array of string)` - An array with full class paths to the module service providers.
 - `files (array of string)` - An array of the module required files. These files will be required (once) during the installed module booting.
 - `installer (string)` - Full class path to the installer class. This class must implement the `Serenity\ModulesContracts\Contracts\InstallerContract`.
 - `uninstaller (string)` - Full class path to the installer class. This class must implement the `Serenity\ModulesContracts\Contracts\UninstallerContract`.

## Creating (Un)Installer

```
use Serenity\ModulesContracts\Contracts\ModuleContract;
use Serenity\ModulesContracts\Contracts\InstallerContract;
use Serenity\ModulesContracts\Contracts\UninstallerContract;
use Serenity\ModulesContracts\Contracts\ModulesManagerContract;

class MyInstaller implements InstallerContract, UninstallerContract
{
    /**
     * Install the module.
     *
     * @param  Serenity\ModulesContracts\Contracts\ModuleContract  $module
     * @param  Serenity\ModulesContracts\Contracts\ModulesManagerContract  $modulesManager
     */
    static function install(ModuleContract $module, ModulesManagerContract $modulesManager)
    {
        // Do something ...
    }

    /**
     * Uninstall the module.
     *
     * @param  Serenity\ModulesContracts\Contracts\ModuleContract  $module
     * @param  Serenity\ModulesContracts\Contracts\ModulesManagerContract  $modulesManager
     */
    static function uninstall(ModuleContract $module, ModulesManagerContract $modulesManager)
    {
        // Do something ...
    }
}
```
