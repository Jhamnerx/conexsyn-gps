<?php

namespace App\Transformers\User;

use App\Transformers\BaseTransformer;
use App\Transformers\UserClient\UserClientFullTransformer;
use League\Fractal\Resource\Item;
use Tobuli\Entities\User;

class UserFullTransformer extends BaseTransformer
{
    protected array $includesLoadMap = [
        'client' => 'client',
        'permissions' => [
            'userPermissions',
            'billingPlanPermissions',
            'manager',
            'manager.userPermissions',
            'manager.billingPlanPermissions',
        ],
    ];

    protected $availableIncludes = [
        'client',
        'permissions',
    ];

    protected static function requireLoads()
    {
        return ['manager', 'billing_plan'];
    }

    public function transform(?User $entity): array
    {
        if ($entity === null) {
            return [];
        }

        return [
            'id'                      => $entity->id,
            'active'                  => $entity->active,
            'user_group_id'           => $entity->user_group_id,
            'group_id'                => $entity->group_id,
            'manager_id'              => $entity->manager_id,
            'billing_plan_id'         => $entity->billing_plan_id,
            'map_id'                  => $entity->map_id,
            'devices_limit'           => $entity->devices_limit,
            'email'                   => $entity->email,
            'phone_number'            => $entity->phone_number,
            'subscription_expiration' => $entity->subscription_expiration,
            'loged_at'                => $entity->loged_at,
            'api_hash_expire'         => $entity->api_hash_expire,
            'available_maps'          => $entity->available_maps,
            'sms_gateway_app_date'    => $entity->sms_gateway_app_date,
            'sms_gateway_params'      => $entity->sms_gateway_params,
            'ungrouped_open'          => $entity->ungrouped_open,
            'week_start_day'          => $entity->week_start_day,
            'top_toolbar_open'        => $entity->top_toolbar_open,
            'map_controls'            => $entity->map_controls,
            'created_at'              => $entity->created_at,
            'updated_at'              => $entity->updated_at,
            'unit_of_altitude'        => $entity->unit_of_altitude,
            'lang'                    => $entity->lang,
            'unit_of_distance'        => $entity->unit_of_distance,
            'unit_of_capacity'        => $entity->unit_of_capacity,
            'date_format'             => $entity->date_format,
            'time_format'             => $entity->time_format,
            'duration_format'         => $entity->duration_format,
            'timezone_id'             => $entity->timezone_id,
            'sms_gateway'             => $entity->sms_gateway,
            'sms_gateway_url'         => $entity->sms_gateway_url,
            'settings'                => $entity->settings,
            'login_periods'           => $entity->login_periods,
            'email_verified_at'       => $entity->email_verified_at,
            'phone_verified_at'       => $entity->phone_verified_at,
            'client_id'               => $entity->client_id,
            'company_id'              => $entity->company_id,
            'only_one_session'        => $entity->only_one_session,
            'subusers_count'          => $entity->subusers_count,
            'devices_count'           => $entity->devices_count,
            'role_id'                 => $entity->role_id,
            'manager'                 => $entity->manager,
            'billing_plan'            => $entity->billing_plan,
        ];
    }

    public function includeClient(User $entity): Item
    {
        return $this->item($entity->client, new UserClientFullTransformer(), false);
    }

    public function includePermissions(User $entity): Item
    {
        $permissions = $entity->getPermissions();

        return $this->item($permissions, fn ($item) => $item, false);
    }
}
