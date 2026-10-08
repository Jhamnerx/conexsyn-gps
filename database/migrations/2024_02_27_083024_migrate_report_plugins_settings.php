<?php

use Illuminate\Database\Migrations\Migration;
use Tobuli\Helpers\Settings\SettingsDB;
use Tobuli\Reports\Reports\AutomonCustomReport;
use Tobuli\Reports\Reports\BirlaCustomReport;
use Tobuli\Reports\Reports\CartDailyCleaningReport;
use Tobuli\Reports\Reports\ObjectHistoryReport;
use Tobuli\Reports\Reports\OfflineDeviceReport;
use Tobuli\Reports\Reports\OverspeedCustomReport;
use Tobuli\Reports\Reports\OverspeedCustomSummaryReport;
use Tobuli\Reports\Reports\OverspeedsSpeedECMReport;
use Tobuli\Reports\Reports\RoutesReport;
use Tobuli\Reports\Reports\RoutesSummarizedReport;
use Tobuli\Reports\Reports\SpeedCompareGpsEcmReport;
use Tobuli\Reports\Reports\SpeedReport;

class MigrateReportPluginsSettings extends Migration
{
    private const REPORTS_PLUGINS = [
        AutomonCustomReport::TYPE_ID            => ['inverted' => false, 'plugin' => 'automon_report'],
        BirlaCustomReport::TYPE_ID              => ['inverted' => false, 'plugin' => 'birla_report'],
        CartDailyCleaningReport::TYPE_ID        => ['inverted' => false, 'plugin' => 'report_cart_cleaning_daily'],
        ObjectHistoryReport::TYPE_ID            => ['inverted' => false, 'plugin' => 'object_history_report'],
        OfflineDeviceReport::TYPE_ID            => ['inverted' => false, 'plugin' => 'offline_objects_report'],
        OverspeedCustomReport::TYPE_ID          => ['inverted' => false, 'plugin' => 'overspeed_custom_report'],
        OverspeedCustomSummaryReport::TYPE_ID   => ['inverted' => false, 'plugin' => 'overspeed_custom_report'],
        OverspeedsSpeedECMReport::TYPE_ID       => ['inverted' => false, 'plugin' => 'speed_compare_gps_ecm_report'],
        SpeedCompareGpsEcmReport::TYPE_ID       => ['inverted' => false, 'plugin' => 'speed_compare_gps_ecm_report'],
        SpeedReport::TYPE_ID                    => ['inverted' => false, 'plugin' => 'speed_report'],
        RoutesReport::TYPE_ID                   => ['inverted' => true, 'plugin' => 'routes_report'],
        RoutesSummarizedReport::TYPE_ID         => ['inverted' => true, 'plugin' => 'routes_report'],
    ];

    private SettingsDB $settingsDb;

    public function __construct()
    {
        $this->settingsDb = new SettingsDB();
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach (self::REPORTS_PLUGINS as $id => $data) {
            $value = $this->settingsDb->get("plugins.{$data['plugin']}.status");

            if ($value === null) {
                continue;
            }

            if ($data['inverted']) {
                $value = !$value;
            }

            $this->settingsDb->forget("plugins.{$data['plugin']}");
            settings("reports.$id.status", $value);
        }

        //clean up plugins
        $plugins = [
            'show_object_info_after',
            'report_drives_stops_simlified',
            'report_stops',
            'report_travelsheet_custom'
        ];
        foreach ($plugins as $plugin) {
            $this->settingsDb->forget("plugins.{$plugin}");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
