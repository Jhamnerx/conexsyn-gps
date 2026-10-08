<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\PermissionException;
use App\Http\Controllers\Api\Frontend\BaseController;
use App\Transformers\UserClient\UserClientFullTransformer;
use Tobuli\Entities\User;
use Tobuli\Services\UserClientService;

class UserClientsController extends BaseController
{
    private User $actingUser;
    private UserClientService $service;

    public function index(int $userId)
    {
        $this->setActingUser($userId);

        $this->checkPermission('view');

        return response()->json(
            \FractalTransformer::item($this->actingUser->client, UserClientFullTransformer::class)->toArray()
        );
    }

    public function store(int $userId)
    {
        $this->setActingUser($userId);

        $this->checkPermission('edit');

        $this->data['user_id'] = $this->actingUser->id;

        $item = $this->service->update($this->data);

        return response()->json(
            \FractalTransformer::item($item, UserClientFullTransformer::class)->toArray()
        );
    }

    private function checkPermission(string $ability): void
    {
        $this->checkException('users', $ability, $this->actingUser);

        if (!$this->user->can($ability, $this->actingUser, 'client_id')) {
            throw new PermissionException();
        }
    }

    private function setActingUser(int $userId): void
    {
        $actingUser = User::userAccessible($this->user)->find($userId);

        if ($actingUser === null) {
            abort(404);
        }

        $this->actingUser = $actingUser;
        $this->service = new UserClientService($actingUser);
    }
}
