<?php namespace Tobuli\Services\Commands;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Validator;
use Tobuli\Entities\Device;
use Tobuli\Entities\User;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Collection AS EloquentCollection;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Protocols\Commands;
use Tobuli\Services\EntityLoader\EnityLoader;

class CommandService
{
    /**
     * @var User
     */
    private $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function validate($devices, $data, $type = SendCommandService::CONNECTION_GPRS)
    {
        switch ($type) {
            case null:
            case SendCommandService::CONNECTION_GPRS:
                return $this->validateGPRS($devices, $data);

            case SendCommandService::CONNECTION_SMS:
                return $this->validateSMS($devices, $data);

            default:
                throw new \RuntimeException("Unknown commands type '$type'");
        }
    }

    /**
     * @param EloquentCollection|Device|Device[]|EnityLoader $devices
     * @param $data
     * @throws ValidationException
     */
    public function validateGPRS($devices, $data)
    {
        $validator = Validator::make($data, Commands::validationRules(
            $data['type'],
            $this->getGprsCommands($devices)
        ));

        if ($validator->fails())
            throw new ValidationException($validator->messages());
    }

    /**
     * @param EloquentCollection|Device|Device[]|EnityLoader $devices
     * @param $data
     * @throws ValidationException
     */
    public function validateSMS($devices, $data)
    {
        $validator = Validator::make($data, [
            'message' => 'required_if:type,custom|string'
        ]);

        if ($validator->fails())
            throw new ValidationException($validator->messages());
    }

    public function getCommands($devices, $intersect = false, $type = SendCommandService::CONNECTION_GPRS)
    {
        switch ($type) {
            case null:
            case SendCommandService::CONNECTION_GPRS:
                return $this->getGprsCommands($devices, $intersect);

            case SendCommandService::CONNECTION_SMS:
                return $this->getSmsCommands($devices, $intersect);

            default:
                throw new \RuntimeException("Unknown commands type '$type'");
        }
    }

    /**
     * @param EloquentCollection|Device|Device[]|EnityLoader $devices
     * @throws \Exception
     */
    public function getGprsCommands($devices, bool $intersect = false): Collection
    {
        $devices = $this->normalize($devices);

        if ($devices instanceof Collection) {
            $devices->load(['traccar']);
        }

        $list = $this->merge(collect([
            (new DevicesCommandsProtocol())->get($devices, $intersect),
            (new DevicesCommandsGprsTemplates($this->user))->get($devices, $intersect),
        ]));

        return $this->filterCommands($list);
    }

    /**
     * @param EloquentCollection|Device|Device[]|EnityLoader $devices
     * @throws \Exception
     */
    public function getSmsCommands($devices, bool $intersect = false): Collection
    {
        $devices = $this->normalize($devices);

        $list = $this->merge(collect([
            (new DevicesCommandsSmsCustom($this->user))->get($devices, $intersect),
            (new DevicesCommandsSmsTemplates($this->user))->get($devices, $intersect),
        ]));

        return $this->filterCommands($list);
    }

    /**
     * @return Builder|EloquentCollection
     * @throws \Exception
     */
    protected function normalize($devices)
    {
        if ($devices instanceof EnityLoader) {
            return $this->normalizeEntityLoader($devices);
        }

        return $this->normalizeCollection($devices);
    }

    protected function normalizeEntityLoader(EnityLoader $devices): Builder
    {
        $devices = $devices->getQuerySelected();

        if ($devices instanceof Relation) {
            $devices = $devices->getQuery();
        }

        return $devices;
    }

    /**
     * @param EloquentCollection|Device|Device[] $devices
     * @throws \Exception
     */
    protected function normalizeCollection($devices) : EloquentCollection
    {
        switch(true) {
            case $devices instanceof EloquentCollection:
                return $devices;
            case $devices instanceof Device:
                return new EloquentCollection([$devices]);
            case is_array($devices):
                return new EloquentCollection($devices);
        }

        throw new \Exception('Devices type not support');
    }

    protected function merge(Collection $list): Collection
    {
        return $list->collapse();
    }

    protected function filterCommands(Collection $list): Collection
    {
        if (!$this->user->perm('send_command', 'edit')) {
            $list = $list->filter(function ($command) {
                return !in_array($command['type'], ['custom', 'serial']);
            });
        }

        return collect($list->values()->all());
    }
}