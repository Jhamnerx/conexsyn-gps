@if (!Auth::User()->perm('send_command', 'view'))
    <div class="alert alert-danger" role="alert">{{ trans('front.dont_have_permission') }}</div>
@else
    <div class="form-group">
        {!!Form::label('devices[]', trans('validation.attributes.devices').':')!!}
        {!! Form::select('devices[]', [], null, [
            'class' => "form-control",
            'multiple' => 'multiple',
            'data-live-search' => 'true',
            'data-actions-box' => 'true',
            'data-ajax' => $ajax_url ?? route('devices.index', ['grouped' => true]),
            'data-selected' => $device_id ?? null
        ]) !!}
    </div>
    <div class="form-group send-command-type">
        {!!Form::label('type', trans('validation.attributes.type').':')!!}
        {!!Form::select('type', [], null, [
            'class' => 'form-control',
            'data-commands-url' => $commands_url ?? route('devices.commands')
        ])!!}
    </div>
    <div class="row attributes"></div>
@endif