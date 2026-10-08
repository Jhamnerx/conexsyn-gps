<?php namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Tobuli\Entities\TrackerPort;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Helpers\Tracker;
use Tobuli\Validation\AdminTrackerPortsFormValidator;

class PortsController extends BaseController {
    /**
     * @var AdminTrackerPortsFormValidator
     */
    private $adminTrackerPortsFormValidator;

    function __construct(AdminTrackerPortsFormValidator $adminTrackerPortsFormValidator) {
        parent::__construct();
        $this->adminTrackerPortsFormValidator = $adminTrackerPortsFormValidator;
    }

    public function index(Request $request) {
        $ports = DB::table('tracker_ports')->whereNull('parent')->get()->all();

        return View::make('admin::Ports.'.($request->ajax() ? 'table' : 'index'))->with(compact('ports'));
    }

    public function edit($name)
    {
        $item = TrackerPort::with('childPorts')->firstWhere('name', $name);

        if (!$item) {
            throw new ValidationException(trans('global.not_found'));
        }

        $childPorts = $item->childPorts
            ->pluck('child_name', 'port')
            ->all();

        $settings = settings("protocols.$item->name");

        return View::make('admin::Ports.edit')->with(compact('item', 'settings', 'childPorts'));
    }

    public function update($port_name, Request $request) {
        $input = $request->all();
        $item = DB::table('tracker_ports')->where('name', '=', $port_name)->first();

        $port = trim($input['port']);
        $extras = $input['extra'];

        $this->adminTrackerPortsFormValidator->validate('update', $input, $item->name);

        $arr = [];
        foreach ($extras as $extra) {
            $name = trim($extra['name']);
            $value = trim($extra['value']);
            if (empty($name) || empty($value))
                continue;

            $arr[$name] = $value;
        }

        $extra = json_encode($arr);

        DB::table('tracker_ports')->where('name', '=', $port_name)->update([
            'active' => isset($input['active']),
            'port' => $port,
            'extra' => $extra,
        ]);

        DB::table('tracker_ports')->where('parent', '=', $port_name)->delete();
        DB::table('tracker_ports')->insert(array_map(fn ($item) => [
            'active' => isset($input['active']),
            'port' => $item['port'],
            'name' => $port_name . '-' . $item['name'],
            'parent' => $port_name,
            'extra' => $extra,
        ], array_filter($input['children'], fn ($item) => $item['port'] && $item['name'])));

        $settings = settings("protocols.$port_name");
        $settings = empty($settings) ? [] : $settings;
        $settings = array_merge($settings, $request->input('settings', []));
        settings("protocols.$port_name", $settings);


        return response()->json(['status' => 1]);
    }

    public function doUpdateConfig() {
        return View::make('admin::Ports.do_update_config');
    }

    public function updateConfig() {
        $tracker = new Tracker();
        $tracker->config()->update();
        $tracker->actor($this->user)->restart();

        Session::flash('message', trans('admin.successfully_updated_restarted'));

        return response()->json(['status' => 1]);
    }

    public function doResetDefault() {
        return View::make('admin::Ports.do_reset_default');
    }

    public function resetDefault() {
        DB::table('tracker_ports')->delete();
        parsePorts();

        $tracker = new Tracker();
        $tracker->config()->update();
        $tracker->actor($this->user)->restart();

        Session::flash('message', trans('admin.successfully_reset_default'));

        return response()->json(['status' => 1]);
    }
}
