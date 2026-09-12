<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    App\Domain\Patient\Providers\PatientDomainServiceProvider::class,
    App\Domain\OPD\Providers\OpdDomainServiceProvider::class,
    App\Domain\IPD\Providers\IpdDomainServiceProvider::class,
    App\Domain\Clinical\Providers\ClinicalDomainServiceProvider::class,
    App\Domain\Laboratory\Providers\LaboratoryDomainServiceProvider::class,
];
