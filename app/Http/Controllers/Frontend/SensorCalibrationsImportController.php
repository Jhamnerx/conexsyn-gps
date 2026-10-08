<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Importers\DeviceSensorCalibrations\DeviceSensorCalibrationsImportManager;

class SensorCalibrationsImportController extends Controller
{
    public function index()
    {
        return view('Frontend.Sensors.partials.calibrations_import');
    }

    public function store(DeviceSensorCalibrationsImportManager $importManager)
    {
        $validator = Validator::make(request()->all(), [
            'file' => 'required|file',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        $data = $importManager->import(request()->file('file'));

        return Response::json([
            'status' => 1,
            'append' => (int)request('append'),
            'data' => $data,
        ]);
    }
}
