<?php


namespace App\Http\Controllers\Api\ClientLite;


use App\Http\Controllers\Controller;
use CustomFacades\GeoLocation;
use Illuminate\Support\Facades\Validator;
use Tobuli\Exceptions\ValidationException;

class AddressController extends Controller
{
    public function get()
    {
        $data = request()->all();

        $validator = Validator::make($data, [
            'lat' => 'lat',
            'lng' => 'lng',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        try {
            $location = GeoLocation::byCoordinates($data['lat'], $data['lng']);

            if ($location)
                return ['data' => $location->toArray()];

            return ['data' => null];
        } catch(\Exception $e) {
            return ['status' => 0, 'message' => $e->getMessage()];
        }
    }
}