<?php


namespace App\Http\Controllers\Api\ClientLite;


use App\Http\Controllers\Controller;
use CustomFacades\Appearance;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Tobuli\Helpers\Language;

class ServerConfigController extends Controller
{
    public function view()
    {
        $config = array_merge_recursive_distinct(
            $this->getServerConfig(),
            $this->getConfig()
        );

        $file = storage_path('clientlite.json');

        if (File::exists($file) && $data = json_decode(File::get($file), true)) {
            $config = array_merge_recursive_distinct($config, $data);
        }

        return Response::json($config);
    }

    protected function getServerConfig()
    {
        return [
            'server' => [
                'name' => Appearance::getSetting('server_name'),
                'description' => Appearance::getSetting('server_description'),
            ],
            'registration' => [
                'status' => settings('main_settings.allow_users_registration') ? true : false,
                'url' => route('registration.create'),
            ],
            'demo' => null,
            'custom_url' => false,
            'servers' => [],
            //deprecated
            'demo_url' => null,
        ];
    }

    protected function getConfig()
    {
        return [
            'map' => [
                'center' => [
                    'lat' => floatval(Appearance::getSetting('map_center_latitude')),
                    'lng' => floatval(Appearance::getSetting('map_center_longitude'))
                ],
                'zoom' => intval(Appearance::getSetting('map_zoom_level')),
            ],
            'history' => [
                'address' => boolval(settings('plugins.history_section_address.status')),
            ],
        ];
    }
}