<?php

namespace CodebarAg\MFiles\Facades;

use Illuminate\Support\Facades\Facade;

class MFiles extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CodebarAg\MFiles\MFiles::class;
    }
}
