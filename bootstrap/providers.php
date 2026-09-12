<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    App\Domain\Patient\Providers\PatientDomainServiceProvider::class,
    App\Domain\OPD\Providers\OpdDomainServiceProvider::class,
    App\Domain\IPD\Providers\IpdDomainServiceProvider::class,
    App\Domain\Clinical\Providers\ClinicalDomainServiceProvider::class,
    App\Domain\Laboratory\Providers\LaboratoryDomainServiceProvider::class,
    App\Domain\Radiology\Providers\RadiologyDomainServiceProvider::class,
    App\Domain\Pharmacy\Providers\PharmacyDomainServiceProvider::class,
    App\Domain\Billing\Providers\BillingDomainServiceProvider::class,
    App\Domain\Inventory\Providers\InventoryDomainServiceProvider::class,
    App\Domain\HR\Providers\HrDomainServiceProvider::class,
    App\Domain\Emergency\Providers\EmergencyDomainServiceProvider::class,
];
