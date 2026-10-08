<?php namespace Tobuli\Services\Commands;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Tobuli\Entities\User;
use Tobuli\Entities\UserGprsTemplate;
use Tobuli\Protocols\Manager as ProtocolsManager;

class DevicesCommandsGprsTemplates implements DevicesCommands
{
    private ProtocolsManager $protocolsManager;
    private User $user;

    public function __construct(User $user)
    {
        $this->protocolsManager = new ProtocolsManager();
        $this->user = $user;
    }

    /**
     * @param EloquentCollection|Builder $devices
     */
    public function get($devices, bool $intersect): Collection
    {
        $templates = UserGprsTemplate::userAccessible($this->user)
            ->byDevices($devices, $intersect)
            ->orderBy('title')
            ->get();

        $displayMessage = $this->user->perm('send_command', 'edit') && !$this->hasGprsTemplatesOnly($devices);

        $commands = $this->protocolsManager->protocol(null)
            ->getTemplateCommands($templates, $displayMessage);

        return collect($commands);
    }

    /**
     * @param Builder|EloquentCollection $devices
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
}