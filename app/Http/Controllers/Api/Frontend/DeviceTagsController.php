<?php

namespace App\Http\Controllers\Api\Frontend;

use Tobuli\Entities\Device;
use Tobuli\Services\TagService;

class DeviceTagsController extends BaseController
{
    private Device $device;

    public function __construct(
        private TagService $tagService,
    ) {
        parent::__construct();
    }

    protected function afterAuth($user)
    {
        $this->device = Device::findOrFail(request('id'));

        if (!$this->user->can('edit', $this->device, 'tags')) {
            abort(404);
        }

        $this->tagService->setUser($user);
    }

    public function store()
    {
        $this->validate(request(), [
            'tag_id' => 'required',
        ]);

        $this->tagService->attachToModel($this->device, (array)request('tag_id'));

        return ['status' => 1];
    }

    public function destroy()
    {
        $this->validate(request(), [
            'tag_id' => 'required',
        ]);

        $this->tagService->detachFromModel($this->device, (array)request('tag_id'));

        return ['status' => 1];
    }
}
