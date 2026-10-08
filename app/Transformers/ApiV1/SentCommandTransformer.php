<?php

namespace App\Transformers\ApiV1;

use App\Transformers\BaseTransformer;
use App\Transformers\Device\DeviceListTransformer;
use App\Transformers\User\UserBasicTransformer;
use League\Fractal\Resource\Item;
use Tobuli\Entities\SentCommand;
use Tobuli\Helpers\Formatter\Facades\Formatter;

class SentCommandTransformer extends BaseTransformer
{
    protected array $includesLoadMap = [
        'device' => 'device',
        'user' => 'user',
    ];

    protected $availableIncludes = [
        'device',
        'user',
    ];

    protected static function requireLoads()
    {
        return ['template'];
    }

    public function transform(?SentCommand $entity): ?array
    {
        if (!$entity) {
            return null;
        }

        return [
            'id'            => $entity->id,
            'user_id'       => $entity->user_id,
            'imei'          => $entity->device_imei,
            'connection'    => $entity->connection,
            'command_title' => $entity->command_title,
            'parameters'    => $entity->parameters,
            'response'      => $entity->response,
            'status'        => $entity->status,
            'created_at'    => Formatter::time()->human($entity->created_at),
            'updated_at'    => Formatter::time()->human($entity->updated_at),
        ];
    }

    public function includeDevice(SentCommand $entity): Item
    {
        return $this->item($entity->device, new DeviceListTransformer(), false);
    }

    public function includeUser(SentCommand $entity): Item
    {
        return $this->item($entity->user, new UserBasicTransformer(), false);
    }
}