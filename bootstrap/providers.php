<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\FeatureServiceProvider;
use App\Providers\FortifyServiceProvider;

return [
    AppServiceProvider::class,
    FeatureServiceProvider::class,
    FortifyServiceProvider::class,
];
