<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Redirect;
use Tobuli\Plugins\Contracts\NormalizationAware;
use Tobuli\Plugins\Contracts\ValidationAware;
use Tobuli\Plugins\PluginsProvider;

class PluginsController extends BaseController
{
    private PluginsProvider $provider;

    public function __construct(PluginsProvider $provider)
    {
        parent::__construct();

        $this->provider = $provider;
    }

    public function index()
    {
        $settings = settings('plugins');

        $plugins = [];

        foreach($settings as $key => $plugin) {

            if ($key == 'beacons' && !config('addon.beacons'))
                continue;

            $plugins[] = (object)[
                'key'    => $key,
                'status' => $plugin['status'],
                'options'=> empty($plugin['options']) ? [] : $plugin['options'],
                'name'   => trans('front.' . $key)
            ];
        }

        return View::make('admin::Plugins.index')->with(compact('plugins'));
    }

    public function save()
    {
        $input = Request::all();

        $errors = [];

        foreach ($input['plugins'] as $key => &$pluginInput) {
            $plugin = $this->provider->get($key);

            if (!$plugin) {
                continue;
            }

            if ($plugin instanceof ValidationAware) {
                $pluginErrors = array_map(
                    fn ($error) => trans("front.$key") . ' - ' . $error,
                    $plugin->validate($pluginInput)->all()
                );

                $errors = array_merge($errors, $pluginErrors);
            }

            if ($plugin instanceof NormalizationAware) {
                $plugin->normalize($pluginInput);
            }
        }

        if ($errors) {
            return Redirect::route('admin.plugins.index')->withInput()->withErrors($errors);
        }

        settings('plugins', $input['plugins']);

        return Redirect::route('admin.plugins.index')->withSuccess(trans('front.successfully_saved'));
    }
}
