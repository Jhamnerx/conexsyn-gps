<?php

namespace Tobuli\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pipeline\Pipeline;
use Spatie\Activitylog\ActivityLogger;
use Spatie\Activitylog\EventLogBag;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Tobuli\Entities\DisplayInterface;
use Tobuli\Entities\ModelChangeLog;

trait ChangeLogs
{
    use LogsActivity;

    public static array $logAttributes;
    public static array $logAttributesToIgnore = ['created_at', 'updated_at'];
    public static bool $logFillable;
    public static bool $logUnguarded;
    public static bool $logOnlyDirty = true;
    public static bool $submitEmptyLogs = false;
    public static bool $logPaused = false;

    public ?ModelChangeLog $lastActivity = null;

    public function getActivitylogOptions(): LogOptions
    {
        $options = (new LogOptions())
            ->logOnly(static::$logAttributes)
            ->logExcept(static::$logAttributesToIgnore)
            ->dontLogIfAttributesChangedOnly(static::$logAttributesToIgnore)
            ->useLogName($this instanceof DisplayInterface ? $this->getDisplayName() : '')
        ;
        $options->submitEmptyLogs = static::$submitEmptyLogs;

        if (isset(static::$logFillable)) {
            $options->logFillable = static::$logFillable;
        }

        if (isset(static::$logUnguarded)) {
            $options->logUnguarded = static::$logUnguarded;
        }

        if (isset(static::$logOnlyDirty)) {
            $options->logOnlyDirty = static::$logOnlyDirty;
        }

        return $options;
    }

    protected static function bootLogsActivity()
    {
        static::eventsToBeRecorded()->each(function ($eventName) {
            if ($eventName === 'updated') {
                static::updating(function (Model $model) {
                    $oldValues = (new static())->setRawAttributes($model->getRawOriginal());
                    $model->oldAttributes = static::logChanges($oldValues);
                });
            }

            return static::$eventName(function (Model $model) use ($eventName) {
                $class = get_class($model);

                if (!empty($class::$logPaused)) {
                    return;
                }

                $model->activitylogOptions = $model->getActivitylogOptions();

                if (! $model->shouldLogEvent($eventName)) {
                    return;
                }

                //tmp
                if (!auth()->user()) {
                    return;
                }

                $description = $model->getDescriptionForEvent($eventName);

                $logName = $model->getLogNameToUse($eventName);

                if ($description == '') {
                    return;
                }

                $changes = $model->attributeValuesToBeLogged($eventName);

                if ($eventName === 'deleted') {
                    $changes['attributes'] = $changes['old'];
                    unset($changes['old']);
                }

                if ($model->isLogEmpty($changes) && ! $model->activitylogOptions->submitEmptyLogs) {
                    return;
                }

                /** @var ModelChangeLog $lastActivity */
                $lastActivity = $model->lastActivity;

                if (!$lastActivity) {
                    // User can define a custom pipelines to mutate, add or remove from changes
                    // each pipe receives the event carrier bag with changes and the model in
                    // question every pipe should manipulate new and old attributes.
                    $event = app(Pipeline::class)
                        ->send(new EventLogBag($eventName, $model, $changes, $model->activitylogOptions))
                        ->through(static::$changesPipes)
                        ->thenReturn();

                    // Actual logging
                    $logger = app(ActivityLogger::class)
                        ->useLog($logName)
                        ->event($eventName)
                        ->performedOn($model)
                        ->withProperties($event->changes);

                    if (method_exists($model, 'tapActivity')) {
                        $logger->tap([$model, 'tapActivity'], $eventName);
                    }

                    $model->lastActivity = $logger->log($description);

                    // Reset log options so the model can be serialized.
                    $model->activitylogOptions = null;

                    return;
                }

                $properties = $lastActivity->properties;
                $hasNew = isset($properties['attributes']) && isset($changes['attributes']);
                $hasOld = isset($properties['old']) && isset($changes['old']);

                if ($hasNew) {
                    $properties['attributes'] = array_merge($properties['attributes'], $changes['attributes']);
                }

                if ($hasOld) {
                    $properties['old'] = array_merge($changes['old'], $properties['old']);
                }

                $lastActivity->properties = $properties;
                $lastActivity->save();
            });
        });
    }
}