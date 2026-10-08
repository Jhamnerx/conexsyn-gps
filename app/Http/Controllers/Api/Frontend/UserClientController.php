<?php

namespace App\Http\Controllers\Api\Frontend;

use App\Exceptions\PermissionException;
use App\Transformers\UserClient\UserClientTransformer;
use Tobuli\Services\UserClientService;

class UserClientController extends BaseController
{
    private UserClientService $service;

    protected function afterAuth($user)
    {
        $this->service = new UserClientService($user);
    }

    public function index()
    {
        $this->checkPermission('view');

        return response()->json(
            \FractalTransformer::item($this->user->client, UserClientTransformer::class)->toArray()
        );
    }

    public function store()
    {
        $this->checkPermission('edit');

        $this->data['user_id'] = $this->user->id;

        $item = $this->service->update($this->data);

        return response()->json(
            \FractalTransformer::item($item, UserClientTransformer::class)->toArray()
        );
    }

    private function checkPermission(string $ability): void
    {
        $this->checkException('users', $ability, $this->user);

        if (!$this->user->can($ability, $this->user, 'client_id')) {
            throw new PermissionException();
        }
    }
}
