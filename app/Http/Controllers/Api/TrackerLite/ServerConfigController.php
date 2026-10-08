<?php


namespace App\Http\Controllers\Api\TrackerLite;


use App\Http\Controllers\Controller;
use CustomFacades\Appearance;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Tobuli\Helpers\Language;

class ServerConfigController extends Controller
{
    public function view()
    {

        $config = [
            'server' => [
                'name' => Appearance::getSetting('server_name'),
                'description' => Appearance::getSetting('server_description'),
            ],
            'custom_url' => false,
            'servers' => [],
        ];

        $file = storage_path('clientlite.json');

        if (File::exists($file) && $data = json_decode(File::get($file), true)) {
            $config = array_merge_recursive_distinct($config, $data);
        }

        return Response::json($config);
    }
}