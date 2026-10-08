<?php


return [
    'gateways' => [
        'get'   => 'GET',
        'post'  => 'POST',
        'app'   => 'SMS gateway app',
        'plivo' => 'Plivo'
    ],
    'encodings' => [
        ''      => 'No',
        'json'  => 'JSON',
        'query' => 'Query'
    ],
    'authentications' => [
        0 => 'No',
        1 => 'Yes'
    ],
    'curl_timeout' => env('SMS_CURL_TIMEOUT', 5)
];