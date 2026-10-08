<?php

namespace Tobuli\Helpers\Alerts\Check;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tobuli\Entities\Alert;
use Tobuli\Entities\Device;

class DeviceExpirationAlertCheck extends AlertCheck
{
    private array $cases;

    public function __construct(Device $device, Alert $alert)
    {
        parent::__construct($device, $alert);

        $this->cases = Alert::getAvailableCases();
    }

    public function checkEvents($position, $prevPosition)
    {
        if (!$this->check()) {
            return null;
        }

        $event = $this->getEvent();

        $event->type = $this->alert->case;

        switch ($this->alert->case) {
            case Alert::CASE_EXPIRED:
                $event->message = trans('front.expired');
                break;
            case Alert::CASE_EXPIRING:
                $event->message = trans('front.expiring');
                break;
            case Alert::CASE_EXPIRED_SIM:
                $event->message = trans('front.sim_expired');
                break;
            case Alert::CASE_EXPIRING_SIM:
                $event->message = trans('front.sim_expiring');
                break;
        }

        return [$event];
    }

    public function check()
    {
        if (!in_array($this->alert->case, $this->cases)) {
            return false;
        }

        if (empty($this->device->pivot->fired_at)) {
            return true;
        }

        switch ($this->alert->case) {
            case Alert::CASE_EXPIRED:
            case Alert::CASE_EXPIRING:
                return $this->checkDevice();

            case Alert::CASE_EXPIRED_SIM:
            case Alert::CASE_EXPIRING_SIM:
                return $this->checkSim();

            default:
                throw new \InvalidArgumentException('Unsupported case: ' . $this->alert->case);
        }
    }

    private function checkDevice(): bool
    {
        if (!$this->device->hasExpireDate()) {
            return false;
        }

        return $this->alert->case === Alert::CASE_EXPIRED
            ? $this->isExpired($this->device->expiration_date)
            : $this->isExpiring($this->device->expiration_date);
    }

    private function checkSim(): bool
    {
        if (!$this->device->hasSimExpireDate()) {
            return false;
        }

        return $this->alert->case === Alert::CASE_EXPIRED_SIM
            ? $this->isExpired($this->device->sim_expiration_date)
            : $this->isExpiring($this->device->sim_expiration_date);
    }

    private function isExpired(string $expirationDate): bool
    {
        $firedAt = $this->device->pivot->fired_at;

        if ($firedAt && ($firedAt >= $expirationDate)) {
            return false;
        }

        $minExpirationDate = Carbon::now()->subDays($this->alert->days);

        if ($extraTime = settings('main_settings.extra_expiration_time')) {
            $minExpirationDate->subSeconds($extraTime);
        }

        return $expirationDate <= $minExpirationDate;
    }

    private function isExpiring(string $expirationDate): bool
    {
        $firedAt = $this->device->pivot->fired_at;
        $preExpireStart = Carbon::parse($expirationDate)->subDays($this->alert->days);

        // event should be fired between `days` to ED and ED
        // if event was fired in this date range, quit
        if ($firedAt && ($firedAt <= $expirationDate) && $preExpireStart->lte($firedAt)) {
            return false;
        }

        $dateFrom = Carbon::now();
        $dateTo = Carbon::now()->addDays($this->alert->days);

        if ($extraTime = settings('main_settings.extra_expiration_time')) {
            $dateFrom->subSeconds($extraTime);
            $dateTo->subSeconds($extraTime);
        }

        return $dateFrom->lte($expirationDate)
            && $dateTo->gte($expirationDate);
    }

    /**
     * @param  Relation|Builder  $query
     * @return Relation|Builder
     */
    public static function filterDevices($query, Alert $alert)
    {
        $method = 'filter' . Str::studly($alert->case);

        return self::$method($query, $alert);
    }

    /**
     * @param  Relation|Builder  $query
     * @return Relation|Builder
     */
    private static function filterExpiredDevice($query, Alert $alert)
    {
        return $query->isExpiredBefore($alert->days)
            ->where(fn (Builder $query) => $query
                ->whereNull('alert_device.fired_at')
                ->orWhereColumn('alert_device.fired_at', '<', 'devices.expiration_date')
            );
    }

    /**
     * @param  Relation|Builder  $query
     * @return Relation|Builder
     */
    private static function filterExpiringDevice($query, Alert $alert)
    {
        return $query->isExpiringAfter($alert->days)
            ->where(fn (Builder $query) => $query
                ->whereNull('alert_device.fired_at')
                ->orWhere(fn (Builder $query) => $query
                    ->whereColumn('alert_device.fired_at', '>', 'devices.expiration_date')
                    ->whereRaw("alert_device.fired_at < DATE_SUB(devices.expiration_date, INTERVAL {$alert->days} DAY)")
                )
            );
    }

    /**
     * @param  Relation|Builder  $query
     * @return Relation|Builder
     */
    private static function filterExpiredSim($query, Alert $alert)
    {
        return $query->isSimExpiredBefore($alert->days)
            ->where(fn (Builder $query) => $query
                ->whereNull('alert_device.fired_at')
                ->orWhereColumn('alert_device.fired_at', '<', 'devices.sim_expiration_date')
            );
    }

    /**
     * @param  Relation|Builder  $query
     * @return Relation|Builder
     */
    private static function filterExpiringSim($query, Alert $alert)
    {
        return $query->isSimExpiringAfter($alert->days)
            ->where(fn (Builder $query) => $query
                ->whereNull('alert_device.fired_at')
                ->orWhere(fn (Builder $query) => $query
                    ->whereColumn('alert_device.fired_at', '>', 'devices.sim_expiration_date')
                    ->whereRaw("alert_device.fired_at < DATE_SUB(devices.sim_expiration_date, INTERVAL {$alert->days} DAY)")
                )
            );
    }
}