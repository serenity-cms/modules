<?php

namespace Serenity\Modules\Facades;

use Illuminate\Support\Facades\Facade;
use Serenity\ModulesContracts\ModulesManagerContract;

class Modules extends Facade {

    protected static function getFacadeAccessor()
    {
        return ModulesManagerContract::class;
    }

}
