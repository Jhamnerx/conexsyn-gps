<?php
/**
 * Created by PhpStorm.
 * User: antanas
 * Date: 18.3.12
 * Time: 17.44
 */

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use CustomFacades\GeoLocation;
use Illuminate\Support\Facades\Validator;
use Tobuli\Exceptions\ValidationException;

class AddressController extends Controller
{
    public function get()
    {
        $data = $this->validatePoint(request()->all());

        return GeoLocation::resolveLocationHandled($this->user, $data['lat'], $data['lng']);
    }

    public function search()
    {
        $q = $this->validateQuery(request()->all());

        try {
            $location = GeoLocation::byAddress($q);

            if ($location)
                return ['status' => 1, 'location' => $location->toArray()];

            return ['status' => 0, 'error' => trans('front.nothing_found_request')];
        } catch(\Exception $e) {
            return ['status' => 0, 'error' => $e->getMessage()];
        }
    }

    public function autocomplete()
    {
        $q = $this->validateQuery(request()->all());

        try {
            $locations = GeoLocation::listByAddress($q);
        } catch (\Exception $e) {
            $locations = [];
        }

        return response()->json(
            array_map(function($location){ return $location->toArray();}, $locations)
        );
    }

    public function reverse()
    {
        $data = $this->validatePoint(request()->all());

        try {
            $location = GeoLocation::byCoordinates($data['lat'], $data['lng']);

            if ($location)
                return ['status' => 1, 'location' => $location->toArray()];

            return ['status' => 0, 'error' => trans('front.nothing_found_request')];
        } catch(\Exception $e) {
            return ['status' => 0, 'error' => $e->getMessage()];
        }
    }

    public function map()
    {
        $data = request()->all();

        $validator = Validator::make($data, [
            'lat' => 'lat',
            'lng' => 'lng',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        $lat = $data['lat'] ?? null;
        $lng = $data['lng'] ?? null;

        $data['coords'] = $lat && $lng ? '['.$lat.', '.$lng.']' : null;

        return view('front::Addresses.index')->with($data);
    }

    private function validatePoint(array $data)
    {
        $validator = Validator::make($data, [
            'lat' => 'required|lat',
            'lng' => 'required_without:lon|lng',
            'lon' => 'required_without:lng|lng',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        $validated = $validator->validated();

        if (array_key_exists('lon', $validated)) {
            $validated['lng'] = $validated['lon'];
        }

        return $validated;
    }

    private function validateQuery(array $data)
    {
        $validator = Validator::make($data, [
            'q' => 'required|string',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        return $data['q'];
    }
}
