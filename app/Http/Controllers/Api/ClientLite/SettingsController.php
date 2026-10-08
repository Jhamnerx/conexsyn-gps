<?php


namespace App\Http\Controllers\Api\ClientLite;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Tobuli\Entities\Timezone;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Services\FcmService;

class SettingsController extends Controller
{
    public function view()
    {
        return Response::json([
            'units_of_distance' => toOptions(config('tobuli.units_of_distance')),
            'units_of_capacity' => toOptions(config('tobuli.units_of_capacity')),
            'units_of_altitude' => toOptions(config('tobuli.units_of_altitude')),
            'duration_formats'  => toOptions(config('tobuli.duration_formats')),
            'date_formats'      => toOptions(config('tobuli.date_formats')),
            'time_formats'      => toOptions(config('tobuli.time_formats')),
            'week_start_days'   => toOptions(getWeekStartDays()),
            'timezones'         => toOptions(Timezone::orderBy('order')->pluck('title', 'id')->all()),
        ]);
    }

    public function get()
    {
        return $this->userSettingsResponse();
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'unit_of_distance' => 'in:' . implode(',', array_keys(config('tobuli.units_of_distance'))),
            'unit_of_capacity' => 'in:' . implode(',', array_keys(config('tobuli.units_of_capacity'))),
            'unit_of_altitude' => 'in:' . implode(',', array_keys(config('tobuli.units_of_altitude'))),
            'duration_format'  => 'in:' . implode(',', array_keys(config('tobuli.duration_formats'))),
            'date_format'      => 'in:' . implode(',', array_keys(config('tobuli.date_formats'))),
            'time_format'      => 'in:' . implode(',', array_keys(config('tobuli.time_formats'))),
            'week_start_day'   => 'in:' . implode(',', array_keys(getWeekStartDays())),
            'timezone_id'      => 'exists:timezones,id',
        ]);

        if ($validator && $validator->fails())
            throw new ValidationException($validator->errors());

        $data = $validator->validated();

        if (empty($data)) {
            throw new ValidationException(['id' => trans('front.nothing_found_request')]);
        }

        $this->user->update($data);

        return $this->userSettingsResponse();
    }

    public function setFcmToken(Request $request, FcmService $fcmService)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'project_id' => 'required',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        $fcmService->setFcmToken($this->user, $request->token, $request->project_id);

        return response()->json(['status' => 1]);
    }

    private function userSettingsResponse()
    {
        return Response::json([
            'email' => $this->user->email,
            'demo' => $this->user->isDemo(),
            'unit_of_distance' => $this->user->unit_of_distance,
            'unit_of_capacity' => $this->user->unit_of_capacity,
            'unit_of_altitude' => $this->user->unit_of_altitude,
            'duration_format' => $this->user->duration_format,
            'date_format' => $this->user->date_format,
            'time_format' => $this->user->time_format,
            'week_start_day' => $this->user->week_start_day,
            'timezone_id' => $this->user->timezone_id,
            'permissions' => $this->user->getPermissions(),
        ]);
    }
}