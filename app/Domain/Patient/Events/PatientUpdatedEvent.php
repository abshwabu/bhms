<?php

namespace App\Domain\Patient\Events;

use App\Domain\Patient\Models\Patient;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PatientUpdatedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Patient $patient, public array $changedFields = [])
    {
    }
}
