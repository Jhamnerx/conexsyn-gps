<?php namespace App\Http\Controllers\Frontend;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Tobuli\Entities\Device;
use Tobuli\Services\EntityLoader\UsersLoader;

class DeviceUsersController extends Controller
{
    /**
     * @var UsersLoader
     */
    protected $usersLoader;

    protected function afterAuth($user)
    {
        $this->usersLoader = new UsersLoader($user);
        $this->usersLoader->setRequestKey('user_id');
    }

    public function index(Request $request)
    {
        $items = $this->usersLoader->get();

        return response()->json($items);
    }

    public function get(Request $request, $device_id)
    {
        $this->checkException('users', 'show', $this->user);

        $device = Device::find($device_id);

        $this->checkException('devices', 'show', $device);

        $this->usersLoader->setQueryStored($device->users());

        $items = $this->usersLoader->get();

        return response()->json($items);
    }
}
