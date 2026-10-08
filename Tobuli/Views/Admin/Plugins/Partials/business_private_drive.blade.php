<div class="form-group">
    {!! Form::label('business_drive_color', trans('front.drive_business').' '.trans('validation.attributes.color')) !!}
    {!!Form::text('plugins[' . $plugin->key . '][options][business_color][value]', $plugin->options['business_color']['value'], ['class' => 'form-control colorpicker'])!!}
</div>

<div class="form-group">
    {!! Form::label('private_drive_color', trans('front.drive_private').' '.trans('validation.attributes.color')) !!}
    {!!Form::text('plugins[' . $plugin->key . '][options][private_color][value]', $plugin->options['private_color']['value'], ['class' => 'form-control colorpicker'])!!}
</div>

<div class="form-group">
    <div class="checkbox">
        {!! Form::hidden('plugins[' . $plugin->key . '][options][schedule][enabled]', 0) !!}
        {!! Form::checkbox('plugins[' . $plugin->key . '][options][schedule][enabled]', 1, $plugin->options['schedule']['enabled'], ['id' => $plugin->key . '_schedule']) !!}
        {!! Form::label(null, trans('front.business_time')) !!}
    </div>
</div>

<div data-disablable="#{{ $plugin->key }}_schedule;disable">
    @include('Frontend.Alerts.partials.schedules', [
        'schedules' => (new \Tobuli\Services\ScheduleService($plugin->options['schedule']['periods'] ?? []))->getFormSchedules(auth()->user()),
        'schedulesInputName' => 'plugins[' . $plugin->key . '][options][schedule][periods]',
    ])
</div>

@push('javascript')
<script>
    $(document).ready(function() {
        var dragger = new Dragger();
        dragger.int();

        $(document).on('change', '#{{ $plugin->key }}_schedule', function() {
            dragger.disable();

            if ($(this).is(':checked')) {
                dragger.enable();
            }
        });

        $('#{{ $plugin->key }}_schedule').trigger('change');
    });
</script>
@endpush