@extends('Frontend.Layouts.modal')
@section('modal_class', 'modal-lg')

@section('title')
    <i class="icon icon-fa fa-clock-o"></i> {!!trans('front.command_schedule')!!}
@stop

@section('body')
    {!!Form::open(['route' => ['command_schedules.update', $command_schedule], 'method' => 'PUT'])!!}
    {!!Form::hidden('event', 'command')!!}

    <div class="row">
        <div class="col-md-6">

            <div class="form-group">
                {!!Form::label('connection', trans('front.connection') . ':')!!}
                {!!Form::select('connection', $connections, $command_schedule->connection, ['class' => 'form-control', 'id' => 'connection'])!!}
            </div>

            <div class="connection" data-connection="gprs" hidden>
                @include('Frontend.SendCommand.partials.gprs_form', [
                    'ajax_url'  => route('command_schedules.devices', ['schedule' => $command_schedule->id]),
                    'commands_url' => route('command_schedules.commands', ['schedule' => $command_schedule->id]),
                ])
            </div>

            <div class="connection" data-connection="sms" hidden>
                @include('Frontend.SendCommand.partials.sms_form', [
                    'ajax_url'  => route('command_schedules.devices',  ['schedule' => $command_schedule->id, 'type' => 'sms']),
                    'commands_url' => route('command_schedules.commands', ['schedule' => $command_schedule->id, 'connection' => 'sms']),
                    'devices_sms' => $devices_sms
                ])
            </div>

        </div>

        <div class="col-md-6">
            @include('Frontend.Schedules.edit_fields', ['schedule' => $command_schedule->schedule])
        </div>
    </div>

    {!!Form::close()!!}
@stop

<script type="text/javascript">
    $(document).ready(function () {
        var scheduleParameters = JSON.parse('{!! json_encode($command_schedule->parameters) !!}'),
            scheduleConnection = '{{ $command_schedule->connection }}';

        scheduleParameters.type = '{{ $command_schedule->command }}';

        $('.schedule-type').hide();
        $('.schedule-type#' + $('input[name="schedule_type"]:checked').attr('value')).show();

        $('#connection').on('change', function (e) {
            var $this = $(this),
                connection = $this.val(),
                $container = $this.closest('form');

            $('*[data-connection]', $container).each(function () {
                if ($(this).attr('data-connection') == connection) {
                    $('input, select, textarea', this).prop( "disabled", false );
                    $('select', this).selectpicker('refresh');
                    return $(this).show();
                } else {
                    $('input, select, textarea', this).prop( "disabled", true );
                    $('select', this).selectpicker('refresh');
                    return $(this).hide();
                }
            });
        }).trigger('change');

        $(document).on('ajax.bs.select', '*[data-connection] select[name="devices[]"]', function () {
            sendCommands[scheduleConnection].setValues(scheduleParameters);

            $(this).trigger('change');
        });

        /*
        $(document).on('loaded.bs.select', '*[data-connection] select[name="type"]', function () {
            for (var i in scheduleParameters) {
                $('[name="' + i + '"]').val(scheduleParameters[i]);
                $('select').selectpicker('refresh');
            }
        });

         */
    });
</script>