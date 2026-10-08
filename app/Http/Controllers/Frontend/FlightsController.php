<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Tobuli\Helpers\FlightsInfoService;

class FlightsController extends Controller
{
    public function __construct(
        private FlightsInfoService $flightsInfoService
    ) {
        parent::__construct();
    }

    protected function afterAuth($user)
    {
        if (!$user->able('flights_info')) {
            abort(404);
        }
    }

    public function __invoke(Request $request)
    {
        $lat = $request->float('lat');
        $lon = $request->float('lon');

        try {
            $data = $this->flightsInfoService->getFlights($lat, $lon);
            return response()->json($data);
        } catch (\Throwable) {
            return response()->json(['error' => 'Failed to get flight data'], 500);
        }
    }
}
