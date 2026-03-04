<?php

namespace Worldesports\MultiTenancy\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Worldesports\MultiTenancy\MultiTenancy
 */
class MultiTenancy extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Worldesports\MultiTenancy\MultiTenancy::class;
    }
}
