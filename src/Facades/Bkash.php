<?php

namespace Tiash\LaravelBkash\Facades;

use Illuminate\Support\Facades\Facade;

class Bkash extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'bkash';
    }
}