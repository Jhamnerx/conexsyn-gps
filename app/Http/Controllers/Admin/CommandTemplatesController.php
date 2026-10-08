<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use CustomFacades\Repositories\TrackerPortRepo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\View;
use Tobuli\Entities\CommandTemplate;
use Tobuli\Entities\Device;
use Tobuli\Entities\DeviceType;
use Tobuli\Entities\UserGprsTemplate;
use Tobuli\Entities\UserSmsTemplate;
use Tobuli\Services\EntityLoader\DevicesGroupLoader;

class CommandTemplatesController extends Controller
{
    /**
     * @var DevicesGroupLoader
     */
    protected $devicesLoader;

    protected function afterAuth($user)
    {
        $this->devicesLoader = new DevicesGroupLoader($user);
        $this->devicesLoader->setRequestKey('devices');
    }

    public function index(Request $request)
    {
        $input = $request->all();

        $sort = $input['sorting'] ?? ['sort_by' => 'title', 'sort' => 'asc'];

        $items = CommandTemplate::common()
            ->search($input['search_phrase'] ?? null)
            ->filter($input)
            ->toPaginator(20, $sort['sort_by'], $sort['sort']);

        return $this->api
            ? $items
            : View::make('admin::CommandTemplates.' . ($request->ajax() ? 'table' : 'index'))
                ->with(compact('items', 'input') + $this->getFormData());
    }

    public function create()
    {
        return View::make('admin::CommandTemplates.create')
            ->with($this->getFormData());
    }

    public function edit(int $id = null)
    {
        $item = $this->getItem($id);

        return View::make('admin::CommandTemplates.edit')
            ->with(compact('item') + $this->getFormData());
    }

    private function getFormData(): array
    {
        $types = [
            UserGprsTemplate::TYPE  => "GPRS",
            UserSmsTemplate::TYPE   => "SMS",
        ];
        $protocols = TrackerPortRepo::getProtocolList();
        $deviceTypes = DeviceType::active()->get()->pluck('title', 'id');

        $adapties = CommandTemplate::getAdapties();
        if (!$this->user->perm('device.protocol', 'view'))
            unset($adapties['protocol']);

        if (!$this->user->perm('device.device_type_id', 'view'))
            unset($adapties['device_types']);

        return compact('types', 'protocols', 'adapties', 'deviceTypes');
    }

    public function store(Request $request)
    {
        $item = new CommandTemplate();

        return $this->saveItem($item);
    }

    public function update(Request $request, int $id = null)
    {
        $item = $this->getItem($id ?: $request->get('id'));

        return $this->saveItem($item);
    }

    private function saveItem(CommandTemplate $item)
    {
        $item->type = $this->data['type'];
        $item->fill($this->data);
        $item->save();

        $item->deviceTypes()->sync(Arr::get($this->data, 'device_types', []));

        if ($this->devicesLoader->hasSelect()) {
            $item->devices()->syncLoader($this->devicesLoader);
        }

        return new JsonResponse(['status' => 1]);
    }

    public function destroy(Request $request, int $id = null)
    {
        $ids = (array)($id ?: $request->get('id'));
        CommandTemplate::common()->whereIn('id', $ids)->delete();

        return new JsonResponse(['status' => 1]);
    }

    private function getItem($id)
    {
        return CommandTemplate::common()->findOrFail($id);
    }

    public function devices(Request $request, $id = null)
    {
        $this->checkException('devices', 'view');

        if ($id && $commandTemplate = CommandTemplate::find($id))
            $this->devicesLoader->setQueryStored($commandTemplate->devices());

        $items = $this->devicesLoader->get();

        return response()->json($items);
    }
}
