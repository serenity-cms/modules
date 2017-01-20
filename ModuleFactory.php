<?php

namespace Serenity\Modules;

use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Serenity\ModulesContracts\ModuleContract;
use Serenity\ModulesContracts\ModuleFactoryContract;
use Serenity\ModulesContracts\Exceptions\ModuleMetaFileException;

class ModuleFactory implements ModuleFactoryContract {

    /**
     * Create module instance from metafile content.
     *
     * @param string $pathToMetaFile Path to module metafile.
     *
     * @return Serenity\ModulesContracts\ModuleContract
     *
     * @throws ModuleMetaFileException when module metafile is corrupted or cannot be opened.
     */
    public function createModuleInstance($pathToMetaFile)
    {
        // Get metafile JSON content
        try
        {
            $metaFileContent = File::get($pathToMetaFile);
        }
        catch (Exception $e)
        {
            throw new ModuleMetaFileException("Module metafile could not be opened [$pathToMetaFile].");
        }

        // Decode JSON
        $data = json_decode($metaFileContent);

        // Decoding successful ?
        if (json_last_error() !== JSON_ERROR_NONE)
        {
            throw new ModuleMetaFileException("Module has corrupted metafile [$pathToMetaFile].");
        }

        // Set attributes
        $path = dirname($pathToMetaFile);
        $name = $this->getAttribute($data, 'name', '');
        $protected = $this->getAttribute($data, 'protected', false);
        $providers = $this->getAttribute($data, 'providers', []);
        $files = $this->getAttribute($data, 'files', []);
        $installer = $this->getAttribute($data, 'installer', null);
        $uninstaller = $this->getAttribute($data, 'uninstaller', null);

        // Create module
        return App::make(ModuleContract::class, [$path, $name, $protected, $providers, $files, $installer, $uninstaller]);
    }

    /**
     * Get the value from data array on the key or default value if there is no
     * value in the array.
     *
     * @param array $data An array with data from module metafile.
     * @param string $key A key in the data array.
     * @param mix $default The default value, if there is no value in the array.
     *
     * @return mix
     */
    protected function getAttribute($data, $key, $default = null)
    {
        return (isset($data->$key)) ? $data->$key : $default;
    }

}
