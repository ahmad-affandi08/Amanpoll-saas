<?php

use App\Providers\AmanpollServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\BatasLajuServiceProvider;
use App\Providers\DomainServiceProvider;
use App\Providers\PolicyServiceProvider;
use App\Providers\RepositoryServiceProvider;

return [
    AppServiceProvider::class,
    AmanpollServiceProvider::class,
    BatasLajuServiceProvider::class,
    DomainServiceProvider::class,
    RepositoryServiceProvider::class,
    PolicyServiceProvider::class,
];
