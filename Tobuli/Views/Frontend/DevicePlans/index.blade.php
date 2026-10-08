@extends('Frontend.Layouts.modal')

@section('modal_class', 'modal-md')

@section('title')
    <i class="icon devices text-primary"></i> {{ trans('admin.device_plans') }}
@stop

@section('body')
    <div class="row">
        <div class="col-sm-12">
            <div class="form-group">
                {!! Form::label('entity', trans('validation.attributes.device_id')) !!}
                {!! Form::select('entity', $devices, $device_id, ['class' => 'form-control', 'id' => 'entity-select'], $devices_attributes) !!}
            </div>
        </div>
    </div>

    <div id="device-plans">
        @include('Frontend.DevicePlans.plans')
    </div>

    <script>
        $(document).off('change', '#entity-select');
        $(document).on('change', '#entity-select', function() {
            var entity = $(this).val();
            app.loadOn('./device_plans/plans/'+entity, '#device-plans', null, false);
        });
    </script>
@stop

@section('buttons')
    <button type="button" class="btn btn-default" data-dismiss="modal">{!! trans('global.cancel') !!}</button>
@stop
