<?php


namespace App\Http\Controllers\Api\ClientLite;

use App\Transformers\ClientLite\HistoryTransformer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Tobuli\Entities\Device;
use Tobuli\Entities\Timezone;
use Tobuli\Exceptions\ValidationException;
use Formatter;
use Tobuli\History\Actions\AppendRouteColor;
use Tobuli\History\Actions\Distance;
use Tobuli\History\Actions\Drivers;
use Tobuli\History\Actions\DriveStop;
use Tobuli\History\Actions\Duration;
use Tobuli\History\Actions\EngineHours;
use Tobuli\History\Actions\Fuel;
use Tobuli\History\Actions\GroupDriveStop;
use Tobuli\History\Actions\GroupEvent;
use Tobuli\History\Actions\Positions;
use Tobuli\History\Actions\Speed;
use Tobuli\History\DeviceHistory;
use Tobuli\Services\FractalTransformerService;

ini_set('memory_limit', '-1');
set_time_limit(600);

class HistoryController extends Controller
{
    protected $transformerService;

    public function __construct(FractalTransformerService $transformerService)
    {
        parent::__construct();

        $this->transformerService = $transformerService;
    }

    public function get(Request $request)
    {
        $this->checkException('history', 'view');

        $validator = Validator::make($request->all(), [
            'device_id' => 'required',
            'from'      => 'required|date',
            'to'        => 'required|date|after:from',
        ]);

        if ($validator && $validator->fails())
            throw new ValidationException($validator->errors());


        $device = Device::find($request->get('device_id'));

        $this->checkException('devices', 'own', $device);

        if ($device->isExpired())
            throw new ValidationException(['id' =>  trans('front.expired')]);

        $date_from = Formatter::time()->reverse($request->get('from'));
        $date_to   = Formatter::time()->reverse($request->get('to'));

        if (Carbon::parse($date_from)->diffInDays($date_to) > Config::get('tobuli.history_max_period_days'))
            throw new ValidationException([
                'from' => strtr(trans('front.to_large_date_diff'), [':days' => Config::get('tobuli.history_max_period_days')])
            ]);

        $data = $this->getHistory($device, $date_from, $date_to);

        return response()->json(
            $this->transformerService->item($data, HistoryTransformer::class)->toArray()
        );
    }

    protected function getHistory($device, $from, $to)
    {
        $history = new DeviceHistory($device);
        $history->setConfig([
            'stop_seconds'      => 180,
            'stop_speed'        => $device->min_moving_speed,
            'min_fuel_fillings' => $device->min_fuel_fillings,
            'min_fuel_thefts'   => $device->min_fuel_thefts,
        ]);
        $history->setRange($from, $to);

        $history->registerActions([
            AppendRouteColor::class,
            DriveStop::class,
            Duration::class,
            Distance::class,
            Speed::class,
            Fuel::class,
            EngineHours::class,
            Drivers::class,
            Positions::class,
            GroupDriveStop::class,
            GroupEvent::class,
        ]);

        return $history->get();
    }
}