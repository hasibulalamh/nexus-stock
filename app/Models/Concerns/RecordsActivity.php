<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

trait RecordsActivity
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->logExcept(['password', 'remember_token', 'api_token', 'token'])
            ->setDescriptionForEvent(fn (string $eventName) => ucfirst($eventName).' '.strtolower(class_basename($this)));
    }
}
