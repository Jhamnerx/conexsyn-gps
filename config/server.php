<?php
return [
    'device_limit' => env('limit'),
    'floating_ip' => env('FLOATING_IP'),
    'device_memory_limit' => env('OBJECT_MEMORY_LIMIT', '512M'),
    'report_memory_limit' => env('REPORT_MEMORY_LIMIT', '2048M'),
    'login_redirect_route' => env('LOGIN_REDIRECT_ROUTE', null),
    'entity_loader_page_limit' => env('ENTITY_LOADER_PAGE_LIMIT', 100),

    'throttle' => [
        'api' => [
            'login' => env('THROTTLE_API_LOGIN', '60,1'),
            'password_reset' => env('THROTTLE_WEB_PASSWORD_RESET', '60,1'),
        ],
        'web' => [
            'login' => env('THROTTLE_WEB_LOGIN', '60,1'),
            'password_reset' => env('THROTTLE_WEB_PASSWORD_RESET', '60,1'),
        ]
    ],
];