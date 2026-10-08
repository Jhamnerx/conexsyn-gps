<?php

Route::group(['middleware' => [], 'namespace' => 'Api\ClientLite', 'prefix' => 'clientlite'], function () {
    Route::get('server_config',[
        'uses' => 'ServerConfigController@view',
    ]);
    Route::get('config',[
        'uses' => 'ServerConfigController@view',
    ]);

    Route::post('token', ['uses' => 'Auth\AccessTokenController@token']);
    Route::post('refresh', ['uses' => 'Auth\AccessTokenController@refresh']);
    Route::post('forgot', ['uses' => 'Auth\ForgotPasswordController@store']);

    Route::group(['middleware' => ['auth:api', 'active_subscription']], function () {

        Route::post('logout', [
            'uses' => function () {
                return \Illuminate\Support\Facades\Response::json([], 201);
            }
        ]);

        Route::get('user', ['uses' => 'SettingsController@get']);
        Route::get('user/settings', ['uses' => 'SettingsController@view']);
        Route::post('user/settings', ['uses' => 'SettingsController@store']);
        Route::post('user/fcm_token', ['uses' => 'SettingsController@setFcmToken']);

        Route::get('devices', ['uses' => 'DevicesController@devices']);
        Route::get('devices/groups', ['uses' => 'DevicesController@groups']);
        Route::get('devices/map', ['uses' => 'DevicesController@map']);
        Route::get('devices/latest', ['uses' => 'DevicesController@latest']);
        Route::get('device/{device_id}', ['uses' => 'DevicesController@get']);
        Route::post('devices/active', ['uses' => 'DevicesController@active']);

        Route::get('commands', ['uses' => 'SendCommandController@view']);
        Route::post('commands', ['uses' => 'SendCommandController@store']);

        Route::get('history', ['uses' => 'HistoryController@get']);

        Route::get('address', ['uses' => 'AddressController@get']);

        Route::get('events', ['uses' => 'EventsController@index']);
        Route::get('geofences/map', ['uses' => 'GeofencesController@map']);
        Route::get('pois/map', ['uses' => 'PoisController@map']);
        Route::get('routes/map', ['uses' => 'RoutesController@map']);
    });
});

Route::group(['middleware' => [], 'namespace' => 'Api\TrackerLite', 'prefix' => 'trackerlite'], function () {

    Route::get('server_config',[
        'uses' => 'ServerConfigController@view',
    ]);

    Route::post('token', ['uses' => 'Auth\AccessTokenController@token']);
    Route::post('refresh', ['uses' => 'Auth\AccessTokenController@refresh']);

    Route::group(['middleware' => ['auth.tracker:token']], function () {
        Route::get('config', ['uses' => 'ApiController@config']);
        Route::post('fcm_token', ['uses' => 'ApiController@setFcmToken']);
        Route::post('fcm_test', ['uses' => 'ApiController@testFcmToken']);

        Route::get('tasks', ['uses' => 'TasksController@getTasks']);
        Route::get('tasks/statuses', ['uses' => 'TasksController@getStatuses']);
        Route::put('tasks/{id}', ['uses' => 'TasksController@update']);
        Route::get('tasks/signature/{taskStatusId}', ['uses' => 'TasksController@getSignature']);

        Route::get('task-set/{id}', ['uses' => 'TasksSetController@index']);
        Route::get('task-set/{id}/locations', ['uses' => 'TasksSetController@taskSetLocations', 'as' => 'trackerlite.task-set.locations']);
        Route::get('task-set/{id}/{location}/tasks', ['uses' => 'TasksSetController@locationTasks', 'as' => 'trackerlite.task-set-location.tasks']);

        Route::get('chat/init', ['uses' => 'ChatController@initChat']);
        Route::get('chat/users', ['uses' => 'ChatController@getChattableObjects']);
        Route::get('chat/messages', ['uses' => 'ChatController@getMessages', 'as' => 'trackerlite.chat.messages']);
        Route::post('chat/message', ['uses' => 'ChatController@createMessage']);

        Route::post('position/image/upload', ['uses' => 'MediaController@uploadImage']);
        Route::get('media_categories', ['uses' => 'MediaCategoryController@getList']);
    });
});