<?php
/**
 * Created by PhpStorm.
 * User: Linas
 * Date: 11/22/2017
 * Time: 6:42 PM
 */

namespace Tobuli\Helpers\Settings;

use Tobuli\Helpers\Settings\SettingsModel;

trait Settingable
{
    protected $instanceSettings;

    protected function getSettingsParent()
    {
        return app('Tobuli\Helpers\Settings\SettingsDB');
    }

    protected function initSetting()
    {
        if ( ! $this->instanceSettings) {
            $this->instanceSettings = new SettingsModel();
            $this->instanceSettings->setParent($this->getSettingsParent());

            $this->instanceSettings->setValues( $this->settings );
        }

        $prefix = $this->getSettigsPrefix();

        $this->instanceSettings->setPrefix($prefix);

        return $this->instanceSettings;
    }

    public function getSettigsPrefix()
    {
        return 'Settings' . get_class($this) . $this->getKey();
    }

    public function getSettingsAttribute($value)
    {
        return json_decode($value, true);
    }

    public function setSettingsAttribute($value)
    {
        $this->attributes['settings'] = json_encode($value);
    }

    public function getSettings($key, $merge = true) {
        return $this->initSetting()->get($key, $merge);
    }

    public function setSettings($key, $value, $merge = true) {

        $this->initSetting()->set($key, $value, $merge);

        $this->settings = $this->initSetting()->getValues();

        $this->save();
    }
}