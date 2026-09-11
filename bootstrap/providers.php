<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    App\Domain\Patient\Providers\PatientDomainServiceProvider::class,
];
