<?php
return [
    'version' => '3.7.13',
    'key'  => env('key', 'Hdaiohaguywhga12344hdsbsdsfsd'),
    'type' => env('APP_TYPE', 'ss3'),

    'logs_path' => env('logs_path', '/opt/traccar/logs'),
    'media_path' => env('media_path', '/var/www/html/requestPhoto/'),

    'log_send_mail_template' => env('LOG_SEND_MAIL_TEMPLATE', false),
    'fallback_send_mail_template' => env('FALLBACK_SEND_MAIL_TEMPLATE', true),

    'geocoder_cache_driver' => env('GEOCODER_CACHE_DRIVER', 'sqlite'),

    'payments_error_log' => env('PAYMENTS_ERROR_LOG', false),
    'check_service_expire_log' => env('CHECK_SERVICE_EXPIRE_LOG', false),

    'fuel_check_lock_log' => env('FUEL_CHECK_LOCK_LOG', false),

    'positions_ip_log' => env('POSITIONS_IP_LOG', false),

    'device' => [
        'expiration_offset' => 0,
        'status_colors' => [
            'colors' => [
                'moving' => 'green',
                'stopped' => 'yellow',
                'offline' => 'red',
                'engine' => 'yellow',
                'blocked' => 'black',
            ]
        ],
        'tail' => [
            'length' => 5,
            'color' => '#33CC33'
        ],
        'min_moving_speed' => 6,
        'min_fuel_fillings' => 10,
        'min_fuel_thefts' => 10,
        'icon_id' => 0,
    ],

    'map_controls' => [
        'clusterDevice' => true,
        'showDevice' => true,
        'showRoutes' => true,
        'showPoi' => true,
        'showTail' => true,
        'showNames' => true,
        'showHistoryRoute' => true,
        'showHistoryArrow' => true,
        'showHistoryStop' => true,
        'showHistoryEvent' => true,
        'showAircraft' => false,
    ],

    'main_settings' => [
        'server_name' => 'GPS Server',
        'server_description' => 'GPS Tracking System for Personal Use or Business',
        'available_maps' => [
            "2"  => 2,
        ],
        'default_language' => 'en',
        'default_timezone' => 16,
        'default_date_format' => 'Y-m-d',
        'default_time_format' => 'H:i:s',
        'default_duration_format' => 'standart',
        'default_unit_of_distance' => 'km',
        'default_unit_of_capacity' => 'lt',
        'default_unit_of_altitude' => 'mt',
        'default_map' => 2,
        'default_object_online_timeout' => 5,
        'default_object_inactive_timeout' => 7200, //5 days in minutes
        'default_fuel_avg_per' => 'distance',
        'allow_users_registration' => 0,

        'devices_limit' => 5,
        'subscription_expiration_after_days' => 30,
        'enable_plans' => 0,
        'allow_user_change_plan' => 1,
        'default_billing_plan' => '',
        'dst' => NULL,
        'dst_date_from' => '',
        'dst_date_to' => '',
        'map_center_latitude' => '51.505',
        'map_center_longitude' => '-0.09',
        'map_zoom_level' => 19,
        'map_show_device_speed' => true,
        'user_permissions' => [],
        'geocoder_cache_enabled' => 1,
        'geocoder_cache_days' => 90,
        'geocoders' => [
            'primary' => [
                'api' => 'default',
                'api_key' => '',
            ],
        ],
        'lbs' => [
            'provider' => '',
            'api_key' => '',
        ],
        'expire_notification' => [
            'channels' => [
                'email' => true,
                'sms' => true,
            ],
            'active_before' => 0,
            'active_after'  => 0,

            'days_before'   => 10,
            'days_after'    => 10,

            'repeat_expiring_each_days' => 0,
            'repeat_expired_each_days' => 0,
        ],

        'streetview_api' => null,
        'streetview_key' => '',
        'device_cameras_days' => 30,

        'template_color' => 'light-blue',
        'welcome_text' => null,
        'bottom_text' => null,
        'apple_store_link' => null,
        'google_play_link' => null,
        'enable_device_plans' => 0,
        'email_verification' => 0,
        'phone_verification' => 0,
        'custom_registration_fields' => [
            'enabled' => 0,
            'fields' => [
                'email'             => ['present' => 0, 'required' => 0],
                'phone_number'      => ['present' => 0, 'required' => 0],
                'client' => [
                    'first_name'    => ['present' => 0, 'required' => 0],
                    'last_name'     => ['present' => 0, 'required' => 0],
                    'personal_code' => ['present' => 0, 'required' => 0],
                    'birth_date'    => ['present' => 0, 'required' => 0],
                    'address'       => ['present' => 0, 'required' => 0],
                ],
            ],
            'email_domain' => env('EMAIL_DOMAIN', 'example.com'),
        ],
    ],
    'max_speed' => env('MAX_SPEED_LIMIT', 300),
    'min_time_gap' => env('MIN_TIME_GAP', 600),
    'prev_position_device_object' => env('PREV_POSITION_DEVICE_OBJECT', false),
    'apply_network_data' => env('APPLY_NETWORK_DATA', true),
    'overwrite_invalid'  => env('OVERWRITE_INVALID', true),
    'device_sidebar_query' => env('DEVICE_SIDEBAR_QUERY', true),
    'entityloader_store_query' => env('ENTITYLOADER_STORE_QUERY', false),
    'alerts_throw_user_device' => env('ALERTS_THROW_USER_DEVICE', true),

    # Minutes before device is offline
    'device_offline_minutes' => 3,
    'check_frequency' => env('APP_CHECK_FREQUENCY', 5),
    'check_chat_frequency' => env('APP_CHECK_CHAT_FREQUENCY', 5),
    'check_chat_unread_frequency' => env('APP_CHECK_CHAT_UNREAD_FREQUENCY', 60),
    'frontend_login' => 'https://localhost',
    'frontend_subscriptions' => 'https://localhost',
    'frontend_change_password' => 'localhost',
    'frontend_curl' => 'https://localhost',
    'frontend_curl_password' => env('FRONTEND_PASSWORD', ''),

    'password' => [
        'min_length' => 8,
        'length' => 12,
        'includes' => [
            //'lowercase',
            //'uppercase',
            'numbers',
            //'specials'
        ],
        'change_required_current' => false,
    ],

    'plans' => [],
    'min_database_clear_days' => 30,
    'history_max_period_days' => env('MAX_HISTORY_PERIOD_DAYS', 31),
    'history_show_invalid' => env('HISTORY_SHOW_INVALID', true),
    'additional_protocols' => [
        'gpsdata' => 'gpsdata',
        'ios' => 'ios',
        'android' => 'android'
    ],
    'protocols' => [
        'gt02' => [
            'mergeable' => true
        ],
        'gt06' => [
            'mergeable' => true
        ],
        'gt062' => [
            'mergeable' => true
        ],
        'gps103' => [
            'mergeable' => true
        ],
        'h02' => [
            'mergeable' => true
        ],
        'eelink' => [
            'mergeable' => true
        ],
        'xirgo' => [
            'mergeable' => true
        ],
        'tk103' => [
            'mergeable' => true
        ],
        'gl200' => [
            'mergeable' => true,
            'expects' => ['power']
        ],
        'wialon' => [
            'mergeable' => true
        ],
        'aquila' => [
            'mergeable' => true
        ],
        'dualcam' => [
            'overwrite' => 'teltonika'
        ]
    ],
    'sensors' => [],
    'units_of_distance' => [],
    'units_of_capacity' => [],
    'units_of_altitude' => [],
    'date_formats' => [
        'Y-m-d' => 'yyyy-mm-dd',
        'm-d-Y' => 'mm-dd-yyyy',
        'd-m-Y' => 'dd-mm-yyyy'
    ],
    'time_formats' => [
        'H:i:s' => '24 hour clock',
        'h:i:s A' => 'AM/PM',
    ],
    'duration_formats' => [
        'standart' => 'h min s',
        'hh:mm:ss' => 'hh:mm:ss',
    ],
    'object_online_timeouts' => [],


    'numeric_sensors' => [
        'battery',
        'temperature',
        'temperature_calibration',
        'tachometer',
        'fuel_consumption',
        'fuel_tank_calibration',
        'fuel_tank',
        'satellites',
        'odometer',
        'gsm',
        'numerical',
        'load',
        'load_calibration',
        'speed_ecm',
    ],
    'listview_fields' => [
        'name' => [
            'field' => 'name',
            'class' => 'device'
        ],
        'imei' => [
            'field' => 'imei',
            'class' => 'device'
        ],
        'status' => [
            'field' => 'status',
            'class' => 'device'
        ],
        'speed' => [
            'field' => 'speed',
            'class' => 'device'
        ],
        'time' => [
            'field' => 'time',
            'class' => 'device'
        ],
        'protocol' => [
            'field' => 'protocol',
            'class' => 'device'
        ],
        'position' => [
            'field' => 'position',
            'class' => 'device'
        ],
        'address' => [
            'field' => 'address',
            'class' => 'device'
        ],
        'sim_number' => [
            'field' => 'sim_number',
            'class' => 'device'
        ],
        'device_model' => [
            'field' => 'device_model',
            'class' => 'device'
        ],
        'plate_number' => [
            'field' => 'plate_number',
            'class' => 'device'
        ],
        'vin' => [
            'field' => 'vin',
            'class' => 'device'
        ],
        'registration_number' => [
            'field' => 'registration_number',
            'class' => 'device'
        ],
        'object_owner' => [
            'field' => 'object_owner',
            'class' => 'device'
        ],
        'additional_notes' => [
            'field' => 'additional_notes',
            'class' => 'device'
        ],
        'active' => [
            'field' => 'active',
            'class' => 'device'
        ],
        'device_type_id' => [
            'field' => 'device_type_id',
            'class' => 'device'
        ],
        'group' => [
            'field' => 'group',
            'class' => 'device'
        ],
        'fuel' => [
            'field' => 'fuel',
            'class' => 'device'
        ],
        'stop_duration' => [
            'field' => 'stop_duration',
            'class' => 'device'
        ],
        'idle_duration' => [
            'field' => 'idle_duration',
            'class' => 'device'
        ],
        'ignition_duration' => [
            'field' => 'ignition_duration',
            'class' => 'device'
        ],
        'last_event_title' => [
            'field' => 'last_event_title',
            'class' => 'device'
        ],
        'last_event_type' => [
            'field' => 'last_event_type',
            'class' => 'device'
        ],
        'last_event_time' => [
            'field' => 'last_event_time',
            'class' => 'device'
        ],
        'sim_activation_date' => [
            'field' => 'sim_activation_date',
            'class' => 'device'
        ],
        'sim_expiration_date' => [
            'field' => 'sim_expiration_date',
            'class' => 'device'
        ],
        'installation_date' => [
            'field' => 'installation_date',
            'class' => 'device'
        ],
        'expiration_date' => [
            'field' => 'expiration_date',
            'class' => 'device'
        ],
        'distance' => [
            'field' => 'distance',
            'class' => 'device'
        ],
    ],
    'listview' => [
        'columns' => [
            'name' => [
                'field' => 'name',
                'class' => 'device'
            ],
            'status' => [
                'field' => 'status',
                'class' => 'device'
            ],
            'time' => [
                'field' => 'time',
                'class' => 'device'
            ],
            'position' => [
                'field' => 'position',
                'class' => 'device'
            ]
        ]
    ],

    'plugins' => [
        'object_listview' => [
            'status' => 0,
        ],
        'device_blocked' => [
            'status' => 0,
        ],
        'business_private_drive' => [
            'status' => 0,
            'options' => [
                'business_color' => [
                    'value' => 'blue'
                ],
                'private_color' => [
                    'value' => 'red'
                ],
                'schedule' => [
                    'enabled' => false,
                    'periods' => [],
                ],
            ]
        ],
        'route_color' => [
            'status' => 0,
            'options' => [
                'value' => 'orange',
                'value_2' => 'red',
                'value_3' => 'black',
            ]
        ],
        'additional_installation_fields' => [
            'status' => 0,
            'options' => [
                'installation_date_default_today' => false,
            ],
        ],
        'annual_sim_expiration' => [
            'status' => 0,
            'options' => [
                'days' => 365
            ],
        ],
        'renew_sim_expiration' => [
            'status' => 0,
        ],
        'display_sim_expired' => [
            'status' => 0,
        ],
        'beacons' => [
            'status' => 0,
            'options' => [
                'detection_speed' => 0,
                'log' => [
                    'current' => true,
                    'history' => false,
                    'detach_on_no_position_data' => false,
                ],
            ]
        ],
        'send_sim_expiration_notification' => [
            'status' => 0,
        ],
        'device_move_animation' => [
            'status' => 0
        ],
        'device_widget_total_distance' => [
            'status' => 0
        ],
        'device_widget_full_address' => [
            'status' => 0
        ],
        'alert_sharing' => [
            'status' => 0,
            'options' => [
                'duration' => [
                    'active' => false,
                    'value' => null,
                ],
                'delete_after_expiration' => [
                    'status' => false,
                ],
            ],
        ],
        'locking_widget' => [
            'status' => 0,
            'options' => [
                'parameter' => 'status',
                'value_on' => 'true',
                'value_off' => 'false',
            ],
        ],
        'call_actions' => [
            'status' => 0,
        ],
        'create_only_expired_objects' => [
            'status' => 0,
            'options' => [
                'offset_type' => '',
                'offset' => '',
            ],
        ],
        'recent_events' => [
            'status' => 0,
        ],
        'sim_blocking' => [
            'status' => 0,
            'options' => [
                'provider' => '',
                'username' => '',
                'token' => '',
                'account_sid' => '',
            ]
        ],
        'geofence_size' => [
            'status' => 0,
        ],
        'geofence_over_address' => [
            'status' => 0,
        ],
        'history_section_address' => [
            'status' => 0,
        ],
        'event_section_address' => [
            'status' => 0,
        ],
        'event_section_alert' => [
            'status' => 0,
        ],
        'moving_geofence' => [
            'status' => 0,
        ],
        'device_driver_reset_engine' => [
            'status' => 0,
        ],
        'reset_driver_on_unknown_rfid' => [
            'status' => 0,
        ],
        'user_api_tab' => [
            'status' => 0,
        ],
        'send_command_speed_limit' => [
            'status' => 0,
            'options' => [
                'speed_limit' => 100,
                'commands' => [],
                'messages' => '',
            ]
        ],
        'geofences_speed_limit' => [
            'status' => 0,
        ],
        'device_attached_to_creator' => [
            'status' => 0,
        ],
        'overspeed_only_engine_on' => [
            'status' => 0,
        ],
        'task_status_auto_change_by_device_position' => [
            'status' => 0,
            'options' => [
                'delivery_radius' => 100,
                'delivery_duration' => 60,
                'delivery_status' => 9,
                'pickup_radius' => 100,
                'pickup_duration' => 60,
                'pickup_status' => 2,
            ],
        ],
    ],

    'process' => [
        'insert_timeout' => env('PROC_INSERT_TIMEOUT', 60),
        'insert_limit' => env('PROC_INSERT_LIMIT', 30),
        'reportdaily_timeout' => env('PROC_REPORT_TIMEOUT', 180),
        'reportdaily_limit' => env('PROC_REPORT_LIMIT', 2),
        'send_event_timeout' => env('PROC_SEND_EVENT_TIMEOUT', 120),
        'send_event_limit' => env('PROC_SEND_EVENT_LIMIT', 2),
        'send_event_take' => env('PROC_SEND_EVENT_TAKE', 50),
    ],

    'template_colors' => [
        'gpsubicar'         => 'GPSUbicar',
        'gpsubicar-dark'    => 'GPSUbicar Nocturno',
        'light-blue'        => 'Light Blue',
        'light-green'       => 'Light Green',
        'light-red'         => 'Light Red',
        'light-orange'      => 'Light Orange',
        'light-pink'        => 'Light Pink',
        'light-win10-blue'  => 'Light Win10 Blue',
        'light-ocean-blue'  => 'Light Ocean Blue',
        'light-tropical-blue' => 'Light Tropical Blue',
        'light-indigo'      => 'Light Indigo',
        'light-black'       => 'Light Black',
        'dark-blue'         => 'Dark Blue',
        'dark-green'        => 'Dark Green',
        'dark-red'          => 'Dark Red',
        'dark-orange'       => 'Dark Orange',
        'dark-pink'         => 'Dark Pink',
        'dark-win10-blue'   => 'Dark Win10 Blue',
        'dark-ocean-blue'   => 'Dark Ocean Blue',
        'dark-tropical-blue' => 'Dark Tropical Blue',
        'dark-indigo'       => 'Dark Indigo',
    ],

    'widgets' => [
        'default' => true,
        'status' => true,
        'list' => [
            'device',
            'sensors',
            'services',
            'camera'
        ]
    ],

    'db_clear' => [
        'status' => true,
        'days'   => 90,
        'from'   => 'server_time'
    ],

    'limits' => [
        'alert_phones'          => env('LIMIT_ALERT_PHONES', 5),
        'alert_emails'          => env('LIMIT_ALERT_EMAILS', 5),
        'alert_webhooks'        => env('LIMIT_ALERT_WEBHOOKS', 2),
        'geofence_groups'       => env('LIMIT_GEOFENCE_GROUPS', 50),
        'report_emails'         => env('LIMIT_REPORT_EMAILS', 5),
        'command_sms_devices'   => env('LIMIT_COMMAND_DEVICES', 10),
        'command_gprs_devices'  => env('LIMIT_COMMAND_GPRS_DEVICES', 0),
        'forward_ips'           => env('LIMIT_FORWARD_IPS', 5),
    ],

    'languages' => [
        'en' => [
            'key'    => 'en',
            'iso'    => 'en',
            'iso3'   => 'eng',
            'title'  => 'English(USA)',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'en.png',
            'locale' => 'en_US'
        ],
        'au' => [
            'key'    => 'au',
            'iso'    => 'en',
            'iso3'   => 'eng',
            'title'  => 'Australian',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'au.png',
            'locale' => 'en_AU'
        ],
        'az' => [
            'key'    => 'az',
            'iso'    => 'az',
            'iso3'   => 'aze',
            'title'  => 'Azerbaijan',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'az.png',
            'locale' => 'az_AZ'
        ],
        'ar' => [
            'key'    => 'ar',
            'iso'    => 'ar',
            'iso3'   => 'ara',
            'title'  => 'Arabic',
            'active' => true,
            'dir'    => 'rtl',
            'flag'   => 'ar.png',
            'locale' => 'ar_AE'
        ],
        'sk' => [
            'key'    => 'sk',
            'iso'    => 'sk',
            'iso3'   => 'slo',
            'title'  => 'Slovakian',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'sk.png',
            'locale' => 'sk'
        ],
        'th' => [
            'key'    => 'th',
            'iso'    => 'th',
            'iso3'   => 'tha',
            'title'  => 'Thai',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'th.png',
            'locale' => 'th'
        ],
        'nl' => [
            'key'    => 'nl',
            'iso'    => 'nl',
            'iso3'   => 'dut',
            'title'  => 'Dutch',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'nl.png',
            'locale' => 'nl_NL'
        ],
        'de' => [
            'key'    => 'de',
            'iso'    => 'de',
            'iso3'   => 'ger',
            'title'  => 'German',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'de.png',
            'locale' => 'de_DE'
        ],
        'gr' => [
            'key'    => 'gr',
            'iso'    => 'el',
            'iso3'   => 'gre',
            'title'  => 'Greek',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'gr.png',
            'locale' => 'el'
        ],
        'pl' => [
            'key'    => 'pl',
            'iso'    => 'pl',
            'iso3'   => 'pol',
            'title'  => 'Polish',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'pl.png',
            'locale' => 'pl'
        ],
        'uk' => [
            'key'    => 'uk',
            'iso'    => 'en',
            'iso3'   => 'eng',
            'title'  => 'English(UK)',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'uk.png',
            'locale' => 'en_GB'
        ],
        'fr' => [
            'key'    => 'fr',
            'iso'    => 'fr',
            'iso3'   => 'fre',
            'title'  => 'French',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'fr.png',
            'locale' => 'fr_FR'
        ],
        'br' => [
            'key'    => 'br',
            'iso'    => 'pt',
            'iso3'   => 'por',
            'title'  => 'Brazilian',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'br.png',
            'locale' => 'pt_BR'
        ],
        'pt' => [
            'key'    => 'pt',
            'iso'    => 'pt',
            'iso3'   => 'por',
            'title'  => 'Portuguese',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'pt.png',
            'locale' => 'pt_PT'
        ],
        'es' => [
            'key'    => 'es',
            'iso'    => 'es',
            'iso3'   => 'spa',
            'title'  => 'Spanish',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'es.png',
            'locale' => 'es_ES'
        ],
        'it' => [
            'key'    => 'it',
            'iso'    => 'it',
            'iso3'   => 'ita',
            'title'  => 'Italian',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'it.png',
            'locale' => 'it_IT'
        ],
        'ch' => [
            'key'    => 'ch',
            'iso'    => 'es',
            'iso3'   => 'spa',
            'title'  => 'Chile',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'ch.png',
            'locale' => 'es_CL'
        ],
        'sr' => [
            'key'    => 'sr',
            'iso'    => 'sr',
            'iso3'   => 'srp',
            'title'  => 'Serbian',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'sr.png',
            'locale' => 'sr_SP'
        ],
        'fi' => [
            'key'    => 'fi',
            'iso'    => 'fi',
            'iso3'   => 'fin',
            'title'  => 'Finnish',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'fi.png',
            'locale' => 'fi'
        ],
        'dk' => [
            'key'    => 'dk',
            'iso'    => 'dk',
            'iso3'   => 'dan',
            'title'  => 'Danish',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'dk.png',
            'locale' => 'da'
        ],
        'ph' => [
            'key'    => 'ph',
            'iso'    => 'en',
            'iso3'   => 'eng',
            'title'  => 'Philippines',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'ph.png',
            'locale' => 'en_PH'
        ],
        'sv' => [
            'key'    => 'sv',
            'iso'    => 'sv',
            'iso3'   => 'swe',
            'title'  => 'Swedish',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'sv.png',
            'locale' => 'sv_SE'
        ],
        'ro' => [
            'key'    => 'ro',
            'iso'    => 'ro',
            'iso3'   => 'rum',
            'title'  => 'Romanian',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'ro.png',
            'locale' => 'ro'
        ],
        'bg' => [
            'key'    => 'bg',
            'iso'    => 'bg',
            'iso3'   => 'bul',
            'title'  => 'Bulgarian',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'bg.png',
            'locale' => 'bg'
        ],
        'hr' => [
            'key'    => 'hr',
            'iso'    => 'hr',
            'iso3'   => 'hrv',
            'title'  => 'Croatian',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'hr.png',
            'locale' => 'hr'
        ],
        'cw' => [
            'key'    => 'cw',
            'iso'    => 'pt',
            'iso3'   => 'por',
            'title'  => 'Papiamento',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'cw.png',
            'locale' => 'pt_PT'
        ],
        'id' => [
            'key'    => 'id',
            'iso'    => 'id',
            'iso3'   => 'ind',
            'title'  => 'Indonesian',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'id.png',
            'locale' => 'id'
        ],
        'ru' => [
            'key'    => 'ru',
            'iso'    => 'ru',
            'iso3'   => 'rus',
            'title'  => 'Russian',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'ru.png',
            'locale' => 'ru_RU'
        ],
        'mk' => [
            'key'    => 'mk',
            'iso'    => 'mk',
            'iso3'   => 'mac',
            'title'  => 'Macedonian',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'mk.png',
            'locale' => 'mk'
        ],
        'ir' => [
            'key'    => 'ir',
            'iso'    => 'fa',
            'iso3'   => 'per',
            'title'  => 'Persian',
            'active' => true,
            'dir'    => 'rtl',
            'flag'   => 'ir.png',
            'locale' => 'fa'
        ],
        'cn' => [
            'key'    => 'cn',
            'iso'    => 'zh',
            'iso3'   => 'chi',
            'title'  => 'Chinese',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'cn.png',
            'locale' => 'zh_CN'
        ],
        'nz' => [
            'key'    => 'nz',
            'iso'    => 'en',
            'iso3'   => 'eng',
            'title'  => 'New Zealand',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'nz.png',
            'locale' => 'en_NZ'
        ],
        'cz' => [
            'key'    => 'cz',
            'iso'    => 'cs',
            'iso3'   => 'cze',
            'title'  => 'Czech',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'cz.png',
            'locale' => 'cs'
        ],
        'he' => [
            'key'    => 'he',
            'iso'    => 'he',
            'iso3'   => 'heb',
            'title'  => 'Hebrew',
            'active' => true,
            'dir'    => 'rtl',
            'flag'   => 'il.png',
            'locale' => 'he'
        ],
        'hu' => [
            'key'    => 'hu',
            'iso'    => 'hu',
            'iso3'   => 'hun',
            'title'  => 'Hungarian',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'hu.png',
            'locale' => 'hu'
        ],
        'ka' => [
            'key'    => 'ka',
            'iso'    => 'ka',
            'iso3'   => 'geo',
            'title'  => 'Georgian',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'ka.png',
            'locale' => 'ka'
        ],
        'no' => [
            'key'    => 'no',
            'iso'    => 'no',
            'iso3'   => 'nor',
            'title'  => 'Norwegian',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'no.png',
            'locale' => 'no_NO'
        ],
        'my' => [
            'key'    => 'my',
            'iso'    => 'my',
            'iso3'   => 'bur',
            'title'  => 'Burmese',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'my.png',
            'locale' => 'my'
        ],
        'ca' => [
            'key'    => 'ca',
            'iso'    => 'ca',
            'iso3'   => 'cat',
            'title'  => 'Catalan',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'catalonia.png',
            'locale' => 'ca'
        ],
        'tr' => [
            'key'    => 'tr',
            'iso'    => 'tr',
            'iso3'   => 'tur',
            'title'  => 'Turkish',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'tr.png',
            'locale' => 'tr'
        ],
        'ku' => [
            'key'    => 'ku',
            'iso'    => 'ku',
            'iso3'   => 'kur',
            'title'  => 'Kurdish',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'ku.png',
            'locale' => 'ku'
        ],
        'ja' => [
            'key'    => 'ja',
            'iso'    => 'ja',
            'iso3'   => 'jpn',
            'title'  => 'Japanese',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'jp.png',
            'locale' => 'ja'
        ],
        'ms' => [
            'key'    => 'ms',
            'iso'    => 'ms',
            'iso3'   => 'may',
            'title'  => 'Malay',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'malaysia.png',
            'locale' => 'ms'
        ],
        'si' => [
            'key'    => 'si',
            'iso'    => 'si',
            'iso3'   => 'sin',
            'title'  => 'Sinhala',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'sin.png',
            'locale' => 'si'
        ],
        'lo' => [
            'key'    => 'lo',
            'iso'    => 'lo',
            'iso3'   => 'lao',
            'title'  => 'Lao',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'la.png',
            'locale' => 'lo'
        ],
        'mn' => [
            'key'    => 'mn',
            'iso'    => 'mn',
            'iso3'   => 'mon',
            'title'  => 'Mongolian',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'mn.png',
            'locale' => 'mn'
        ],
        'ta' => [
            'key'    => 'ta',
            'iso'    => 'ta',
            'iso3'   => 'tam',
            'title'  => 'Tamil',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'sin.png',
            'locale' => 'ta_IN'
        ],
        'hi' => [
            'key'    => 'hi',
            'iso'    => 'hi',
            'iso3'   => 'hin',
            'title'  => 'Hindi',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'in.png',
            'locale' => 'hi_IN'
        ],
        'ne' => [
            'key'    => 'ne',
            'iso'    => 'ne',
            'iso3'   => 'nep',
            'title'  => 'Nepali',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'np.png',
            'locale' => 'ne_NP'
        ],
        'sl' => [
            'key'    => 'sl',
            'iso'    => 'sl',
            'iso3'   => 'slv',
            'title'  => 'Slovene',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'si.png',
            'locale' => 'sl_SI'
        ],
        'lt' => [
            'key'    => 'lt',
            'iso'    => 'lt',
            'iso3'   => 'lit',
            'title'  => 'Lithuanian',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'lt.png',
            'locale' => 'lt_LT'
        ],
        'lv' => [
            'key'    => 'lv',
            'iso'    => 'lv',
            'iso3'   => 'lav',
            'title'  => 'Latvian',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'lv.png',
            'locale' => 'lv_LV'
        ],
        'al' => [
            'key'    => 'al',
            'iso'    => 'sq',
            'iso3'   => 'sqi',
            'title'  => 'Albanian',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'al.png',
            'locale' => 'sq_AL'
        ],
        'bn' => [
            'key'    => 'bn',
            'iso'    => 'bn',
            'iso3'   => 'ben',
            'title'  => 'Bengali',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'bd.png',
            'locale' => 'bn_BN'
        ],
        'ps' => [
            'key'    => 'ps',
            'iso'    => 'ps',
            'iso3'   => 'pus',
            'title'  => 'Pashto',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'af.png',
            'locale' => 'ps_PS'
        ],
        'km' => [
            'key'    => 'km',
            'iso'    => 'km',
            'iso3'   => 'khm',
            'title'  => 'Khmer',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'kh.png',
            'locale' => 'km_KH'
        ],
        'vi' => [
            'key'    => 'vi',
            'iso'    => 'vi',
            'iso3'   => 'vie',
            'title'  => 'Vietnamese',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'vn.png',
            'locale' => 'vi_VN'
        ],
        'ua' => [
            'key'    => 'ua',
            'iso'    => 'uk',
            'iso3'   => 'ukr',
            'title'  => 'Ukrainian',
            'active' => true,
            'dir'    => 'ltr',
            'flag'   => 'ua.png',
            'locale' => 'uk_UA'
        ],
    ],

    'sms_gateway' => [
        'enabled'               => false,
        'use_as_system_gateway' => false,
        'request_method'        => '',
        'sms_gateway_url'       => '',
        'custom_headers'        => '',
        'authentication'        => '0',
        'username'              => '',
        'password'              => '',
        'encoding'              => '',
        'auth_id'               => '',
        'auth_token'            => '',
        'senders_phone'         => '',
        'user_id'               => null
    ],

    'users_default_sms_gateway' => [
        'sms_gateway'           => false,
        'request_method'        => '',
        'sms_gateway_url'       => '',
        'custom_headers'        => '',
        'authentication'        => '0',
        'username'              => '',
        'password'              => '',
        'encoding'              => '',
        'auth_id'               => '',
        'auth_token'            => '',
        'senders_phone'         => '',
    ],

    'external_url' => [
        'enabled' => false,
        'external_url' => '',
    ],

    'position_notifications' => [
        'send_to' => env('POSITION_RESULT_NOTIFICATION', 'user'),
        'related_user_oldest_record_ago' => 5 * 60,
    ],
    'dualcam' => [
        'enabled' => env('DUALCAM', true),
    ],
    'login_periods' => [
        'enabled' => env('LOGIN_PERIODS', false),
    ],

    'payments' => [
        'gateways' => [
            'paypal'              => 0,
            'stripe'              => 0,
            'braintree'           => 0,
            'paydunya'            => 0,
            'mobile_direct_debit' => 0,
            'twocheckout'         => 0,
            'paysera'             => 0,
            'asaas'               => 0,
        ],

        'paypal' => [
            'currency'      => '',
            'payment_name'  => '',
            'client_id'     => '',
            'secret'        => '',
            'mode'          => '',
        ],

        'stripe' => [
            'public_key'  => '',
            'secret_key'  => '',
            'currency'    => '',
            'webhook_key' => '',
        ],

        'braintree' => [
            'environment'           => 'sandbox',
            'merchantId'            => '',
            'publicKey'             => '',
            'privateKey'            => '',
            'merchant_account_id'   => null,
            '3d_secure'             => false,
            'plans' => [
                // server_billing_plan_id => braintree_plan_id
            ],

        ],

        'paydunya' => [
            'mode'          => '',
            'master_key'    => '',
            'public_key'    => '',
            'private_key'   => '',
            'token'         => '',
            'payment_name'  => ''
        ],

        'mobile_direct_debit' => [
            'url'         => '',
            'api_key'     => '',
            'merchant_id' => '',
            'product_id'  => ''
        ],

        'twocheckout' => [
            'front_url' => 'https://www.2checkout.com',
            'api_url' => 'https://api.2checkout.com/rest/6.0',
            'merchant_code' => '',
            'secret_key' => '',
            'demo_mode' => false,
        ],

        'paysera' => [
            'project_id'    => '',
            'project_psw'   => '',
            'verify_id'     => '',
            'currency'      => '',
            'environment'   => 'sandbox',
        ],

        'kevin' => [
            'client_id'         => '',
            'client_secret'     => '',
            'endpoint_secret'   => '',
            'currency'          => '',
            'language'          => 'en',
            'receiver_name'     => '',
            'receiver_iban'     => '',
        ],

        'asaas' => [
            'environment'   => 'sandbox',
            'api_key'       => '',
            'access_token'  => '',
        ],
    ],

    'backups' => [
        'type'         => 'auto',
        'period'       => 1,
        'hour'         => '00:00',
        'ftp_server'   => null,
        'ftp_username' => null,
        'ftp_password' => null,
        'ftp_port'     => null,
        'ftp_path'     => null,
    ],

    'exports' => [
        'formats' => [
            'csv' => 'CSV',
            'xls' => 'XLS'
        ]
    ],

    'dashboard' => [
        'enabled' => 0,
        'blocks'  => [
            'device_activity'      => [
                'enabled' => 1,
                'options' => [],
            ],
            'latest_events'        => [
                'enabled' => 1,
                'options' => [
                    'period' => 'day',
                ],
            ],
            'device_status_counts' => [
                'enabled' => 1,
                'options' => [],
            ],
            'latest_tasks'         => [
                'enabled' => 1,
                'options' => [
                    'period' => 'day',
                ],
            ],
            'device_distance'      => [
                'enabled' => 1,
                'options' => [
                    'devices' => [],
                    'period' => 'day',
                ],
            ],
            'device_overview'      => [
                'enabled' => 0,
                'options' => [
                    'colors' => [
                        'move'            => '#52BE80',
                        'idle'            => '#5DADE2',
                        'stop'            => '#F7DC6F',
                        'offline'         => '#EC7063',
                        'inactive'        => '#D7DBDD',
                        'never_connected' => '#AF7AC5',
                    ],
                ],
            ]
        ],
    ],

    'model_change_log' => [
        \Tobuli\Entities\Device::class => [
            'attributes' => ['*'],
        ],
        \Tobuli\Entities\User::class => [
            'attributes' => ['*'],
            'attributes_to_ignore' => [
                'map_id',
                'map_controls',
                'created_at',
                'updated_at',
                'loged_at',
            ],
        ],
        \Tobuli\Entities\Alert::class => [
            'attributes' => ['*'],
            'include_hidden' => true,
        ],
        \Tobuli\Entities\Report::class => [
            'attributes' => ['*'],
        ],
        \Tobuli\Entities\Geofence::class => [
            'attributes' => ['*'],
            'attributes_to_ignore' => ['created_at', 'updated_at', 'coordinates'],
        ],
        \Tobuli\Entities\Route::class => [
            'attributes' => ['*'],
            'attributes_to_ignore' => ['created_at', 'updated_at', 'coordinates'],
        ],
        \Tobuli\Entities\Poi::class => [
            'attributes' => ['*'],
        ],
        \Tobuli\Entities\Event::class => [
            'attributes' => ['*'],
        ],
        \Tobuli\Entities\Task::class => [
            'attributes' => ['*'],
        ],
        \Tobuli\Entities\DeviceService::class => [
            'attributes' => ['*'],
        ],
        \Tobuli\Entities\DeviceSensor::class => [
            'attributes' => ['*'],
            'attributes_to_ignore' => ['created_at', 'updated_at', 'value_set_at', 'value_changed_at'],
        ],
        \Tobuli\Entities\SensorGroupSensor::class => [
            'attributes' => ['*'],
            'attributes_to_ignore' => ['created_at', 'updated_at', 'value_set_at', 'value_changed_at'],
        ],
        'login' => [
            'enable_successful' => true,
            'enable_failed' => 3, // number of failed attempts to log after
            //  'methods' => ['simple', ], // list methods defined at AuthLoginEventHandler::resolveLoginMethod
        ],
        'request' => [
            'enabled' => env('REQUEST_LOGS', false),
            'global' => [
                'parameters' => [
                    'except' => ['_', 'page', 'limit', '_token', 'url', 'modal'],
                ],
            ],
            'paths' => [
                'included' => [
                    [
                        'path' => 'objects/sidebar',
                        'only_with_parameters' => true,
                    ],
                ],
                'excluded' => [
                    'api/*',
                    'device/widgets/*',
                    'address',
                    'gpsdata_insert',
                    'objects/items',
                    'objects/items_json',
                    '/',
                    'authentication/create',
                ],
            ],
        ],
    ],

    'weekdays'    => [
        'monday'    => 'front.monday',
        'tuesday'   => 'front.tuesday',
        'wednesday' => 'front.wednesday',
        'thursday'  => 'front.thursday',
        'friday'    => 'front.friday',
        'saturday'  => 'front.saturday',
        'sunday'    => 'front.sunday',
    ],

    'device_configuration' => [
        'delay' => env('DEVICE_CONFIGURATION_DELAY', 5),
    ],

    'user_login_methods' => [
        'general' => [
            'user_individual_config' => false,
            'login_methods' => [
                \Tobuli\Services\Auth\EmailAuth::getKey() => true,
                \Tobuli\Services\Auth\PhoneAuth::getKey() => false,
                \Tobuli\Services\Auth\AzureAuth::getKey() => false,
            ],
        ],
        'config' => [
            \Tobuli\Services\Auth\AzureAuth::getKey() => [
                'client_id' => '',
                'client_secret' => '',
                'tenant_id' => '',
            ],
        ],
    ],

    'currency' => [
        'symbol' => '$',
    ],

    'extra_required_fields' => [
        'device' => [/* 'field' => 'required_if' */],
    ],

    'validation' => [
        'regex_phone' => env('REGEX_PHONE', "/^\+?\d[0-9]{7,15}$/"),
    ],

    'reports' => [
        \Tobuli\Reports\Reports\AutomonCustomReport::TYPE_ID => ['status' => false],
        \Tobuli\Reports\Reports\BirlaCustomReport::TYPE_ID => ['status' => false],
        \Tobuli\Reports\Reports\CartDailyCleaningReport::TYPE_ID => ['status' => false],
        \Tobuli\Reports\Reports\ObjectHistoryReport::TYPE_ID => ['status' => false],
        \Tobuli\Reports\Reports\OfflineDeviceReport::TYPE_ID => ['status' => false],
        \Tobuli\Reports\Reports\OverspeedCustomReport::TYPE_ID => ['status' => false],
        \Tobuli\Reports\Reports\OverspeedCustomSummaryReport::TYPE_ID => ['status' => false],
        \Tobuli\Reports\Reports\OverspeedsSpeedECMReport::TYPE_ID => ['status' => false],
        \Tobuli\Reports\Reports\SpeedCompareGpsEcmReport::TYPE_ID => ['status' => false],
        \Tobuli\Reports\Reports\SpeedReport::TYPE_ID => ['status' => false],
        \Tobuli\Reports\Reports\RoutesReport::TYPE_ID => ['status' => true],
        \Tobuli\Reports\Reports\RoutesSummarizedReport::TYPE_ID => ['status' => true],
        \Tobuli\Reports\Reports\UsersDevicesReport::TYPE_ID => ['status' => false],
    ],
];
