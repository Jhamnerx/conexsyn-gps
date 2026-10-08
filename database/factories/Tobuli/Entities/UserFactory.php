<?php

namespace Database\Factories\Tobuli\Entities;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Tobuli\Entities\User;
use Tobuli\Services\PermissionService;
use Tobuli\Services\UserService;

class UserFactory extends Factory
{
    protected $model = User::class;

    private static ?PermissionService $permissionService = null;
    private static ?UserService $userService = null;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'email' => $this->faker->unique()->safeEmail,
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token' => Str::random(10),
            'map_id' => config('tobuli.main_settings.default_map'),
            'available_maps' => config('tobuli.main_settings.available_maps'),
            'ungrouped_open' => ['geofence_group' => 1, 'device_group' => 1, 'poi_group' => 1, 'route_group' => 1],
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (User $user) {
            if ($user->group_id !== PermissionService::GROUP_DEMO) {
                $permissions = self::getPermissionService()->getByGroupId($user->group_id);

                self::getUserService()->setPermissions($user, $permissions);
            }
        });
    }

    public function admin()
    {
        return $this->state(fn () => ['group_id' => PermissionService::GROUP_ADMIN]);
    }

    public function user()
    {
        return $this->state(fn () => ['group_id' => PermissionService::GROUP_USER]);
    }

    public function manager()
    {
        return $this->state(fn () => ['group_id' => PermissionService::GROUP_MANAGER]);
    }

    public function demo()
    {
        return $this->state(fn () => ['group_id' => PermissionService::GROUP_DEMO]);
    }

    public function operator()
    {
        return $this->state(fn () => ['group_id' => PermissionService::GROUP_OPERATOR]);
    }

    public function supervisor()
    {
        return $this->state(fn () => ['group_id' => PermissionService::GROUP_SUPERVISOR]);
    }

    public static function getPermissionService(): ?PermissionService
    {
        return self::$permissionService ?? (self::$permissionService = new PermissionService());
    }

    public static function getUserService(): ?UserService
    {
        return self::$userService ?? (self::$userService ?? new UserService());
    }
}
