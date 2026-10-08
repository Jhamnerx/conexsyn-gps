<?php

namespace Tobuli\Reports\Reports;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Tobuli\Entities\User;
use Tobuli\Reports\DeviceReport;

class UsersDevicesReport extends DeviceReport
{
    const TYPE_ID = 97;

    protected $disableFields = [
        'period',
        'date_from',
        'from_time',
        'date_to',
        'to_time',
        'geofences',
        'speed_limit',
        'stops',
        'show_addresses',
        'zones_instead',
    ];

    protected bool $allDevices = true;
    protected $deviceless = true;

    public function typeID()
    {
        return self::TYPE_ID;
    }

    public function title()
    {
        return trans('front.report_users_devices');
    }

    protected function defaultMetas()
    {
        return [];
    }

    protected function generate()
    {
        $devices = (clone $this->devicesQuery)->select('devices.id');
        $devicesCount = $devices->count();

        if ($devicesCount < 1000) {
            $devices = $devices->pluck('id')->toArray();
        }

        $chunkSize = match (true) {
            $devicesCount >= 20000 => 5,
            $devicesCount >= 5000 => 10,
            $devicesCount >= 1000 => 100,
            default => 1000,
        };

        User::select(['id', 'email'])
            ->userAccessible($this->user)
            ->orderBy('email')
            ->chunk($chunkSize, function (Collection $users) use ($devices) {
                $users->load(['devices' => fn (Relation $query) => $query
                    ->select('plate_number')
                    ->whereIn('id', $devices)
                ]);

                /** @var User $user */
                foreach ($users as $user) {
                    if ($this->skip_blank_results && $user->devices->isEmpty()) {
                        continue;
                    }

                    $this->items[] = [
                        'email' => $user->email,
                        'plate_numbers' => $user->devices
                            ->pluck('plate_number')
                            ->map(fn ($plateNumber) => $plateNumber ?: '-')
                            ->implode(', '),
                    ];
                }
            });
    }

    public static function isUserEnabled(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }
}