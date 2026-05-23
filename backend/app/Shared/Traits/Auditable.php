<?php

namespace App\Shared\Traits;

trait Auditable
{
    protected static function bootAuditable(): void
    {
        static::created(function ($model) {
            static::logAudit($model, 'created');
        });

        static::updated(function ($model) {
            static::logAudit($model, 'updated', $model->getOriginal(), $model->getAttributes());
        });

        static::deleted(function ($model) {
            static::logAudit($model, 'deleted');
        });
    }

    protected static function logAudit($model, string $event, ?array $oldValues = null, ?array $newValues = null): void
    {
        if (app()->runningInConsole() && !app()->runningUnitTests()) {
            return;
        }

        try {
            \App\Modules\Audit\Models\AuditLog::create([
                'business_id' => $model->business_id ?? null,
                'user_id' => auth()->id(),
                'auditable_type' => get_class($model),
                'auditable_id' => $model->getKey(),
                'event' => $event,
                'old_values' => $oldValues ? json_encode($oldValues) : null,
                'new_values' => $newValues ? json_encode($newValues) : null,
                'url' => request()?->fullUrl(),
                'ip_address' => request()?->ip(),
            ]);
        } catch (\Throwable $e) {
            // Silently fail — audit should never break app
            logger()->warning('Audit log failed: ' . $e->getMessage());
        }
    }
}
