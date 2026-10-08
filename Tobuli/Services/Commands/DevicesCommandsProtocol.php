<?php namespace Tobuli\Services\Commands;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Tobuli\Protocols\Manager as ProtocolsManager;
use Illuminate\Database\Eloquent\Collection AS EloquentCollection;

class DevicesCommandsProtocol implements DevicesCommands
{
    private ProtocolsManager $protocolsManager;

    public function __construct()
    {
        $this->protocolsManager = new ProtocolsManager();
    }

    /**
     * @param EloquentCollection|Builder $devices
     */
    public function get($devices, bool $intersect) : Collection
    {
        $bag = collect();

        if ($intersect && $this->hasGprsTemplatesOnly($devices)) {
            return $bag;
        }

        $protocols = $this->getProtocols($devices);

        foreach ($protocols as $protocol) {
            $bag->push(
                collect($this->protocolsManager->protocol($protocol)->getCommands())->keyBy('type')
            );
        }

        if ($intersect) {
            $commands = $bag->pop();
            while ($next = $bag->pop()) {
                $commands = $next->intersectByKeys($commands);
            }
        } else {
            $commands = $bag->collapse();
        }

        if (empty($commands)) {
            $commands = collect();
        }

        return $commands->unique('type')->sortBy('title')->values();
    }

    /**
     * @param Collection|Builder $devices
     * @return bool
     */
    protected function hasGprsTemplatesOnly($devices): bool
    {
        if ($devices instanceof Builder) {
            return (clone $devices)->where('gprs_templates_only', 1)->count();
        }

        return $devices->contains(function($device) {
            return $device->gprs_templates_only;
        });
    }

    protected function getProtocols($devices): Collection
    {
        if ($devices instanceof Builder) {
            return (clone $devices)
                ->where('gprs_templates_only', 0)
                ->traccarJoin()
                ->select(['traccar_devices.protocol'])
                ->distinct()
                ->pluck('traccar_devices.protocol');
        }

        return $devices->filter(function($device) {
            return ! $device->gprs_templates_only;
        })->pluck('protocol')->unique();
    }
}