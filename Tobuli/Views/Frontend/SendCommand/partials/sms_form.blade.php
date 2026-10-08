@if (!Auth::User()->sms_gateway)
    <div class="alert alert-danger" role="alert">{!!trans('front.sms_gateway_disabled')!!}</div>
@else
    @if ($devices_sms === 0)
        <div class="alert alert-danger" role="alert">{!!trans('front.no_devices_with_sim_number')!!}</div>
    @endif

    <div class="form-group">
        {!!Form::label('devices', trans('validation.attributes.devices').'*:')!!}
        @if ($devices_sms === 0)
            {!! Form::text('devices[]', null, ['class' => 'form-control', 'disabled' => 'disabled']) !!}
        @else
            {!! Form::select('devices[]', [], null, [
                'class' => "form-control",
                'multiple' => 'multiple',
                'data-live-search' => 'true',
                'data-actions-box' => 'true',
                'data-ajax' => $ajax_url ?? route('devices.index', ['type' => 'sms', 'grouped' => true]),
                'data-selected' => $device_id ?? null
            ]) !!}
        @endif
        <small>{!!trans('front.add_sim_number_info')!!}</small>
    </div>

    <div class="form-group send-command-type">
        {!!Form::label('type', trans('validation.attributes.sms_template_id').':')!!}
        {!!Form::select('type', [], null, [
            'class' => 'form-control',
            'data-commands-url' => $commands_url ?? route('devices.commands', ['connection' => 'sms'])
        ])!!}
        <small>{!!trans('front.add_sms_template_info')!!}</small>
    </div>

    <div class="row attributes"></div>

    <div class="send_command_result" style="display: none;">
        <div>
            <p>{!!trans('front.get_request')!!}:</p>
            <p class="get_request result_parse"></p>
        </div>
        <div>
            <p>{!!trans('front.response')!!}:</p>
            <p class="get_result result_parse"></p>
        </div>
    </div>
@endif