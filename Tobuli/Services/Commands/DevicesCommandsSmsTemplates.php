<?php namespace Tobuli\Services\Commands;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Tobuli\Entities\User;
use Tobuli\Entities\UserSmsTemplate;
use Tobuli\Protocols\Manager as ProtocolsManager;

class DevicesCommandsSmsTemplates implements DevicesCommands
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
        $templates = UserSmsTemplate::userAccessible($this->user)
            ->byDevices($devices, $intersect)
            ->orderBy('title')
            ->get();

        $displayMessage = $this->user->perm('send_command', 'edit');

        $commands = $this->protocolsManager->protocol(null)
            ->getTemplateCommands($templates, $displayMessage);

        return collect($commands);
    }
}