<?php

use Illuminate\Support\Facades\Route;
use Ocpi\Support\Server\Middlewares\IdentifyParty;
use Ocpi\Support\Server\Middlewares\IdentifyVersion;
use Ocpi\Support\Server\Middlewares\LogRequest;

Route::middleware([
    'api',
    IdentifyParty::class,
    IdentifyVersion::class,
    LogRequest::class,
])
    ->prefix('ocpi/emsp')
    ->name('ocpi.emsp.')
    ->group(function () {
        Route::prefix('2.1.1')
            ->name('2_1_1.');
    });