@extends('front::Layouts.modal')

@section('title')
    {!! trans('global.add') !!}
@stop

@section('body')

    {!! Form::open(['route' => ['geofences_groups.store'], 'method' => 'POST']) !!}
    <div class="form-group">
        {!! Form::label('title', trans('validation.attributes.title').':') !!}
        {!! Form::text('title', null, ['class' => 'form-control']) !!}
    </div>

    <div class="form-group">
        {!! Form::label('geofences', trans('front.geofence').':') !!}
        {!! Form::select('geofences[]', $geofences->pluck('name', 'id')->all(), null, ['class' => 'form-control multiexpand', 'multiple' => 'multiple', 'data-live-search' => 'true', 'data-actions-box' => 'true']) !!}
    </div>
    {!! Form::close() !!}

    <script>
        function geofences_groups_create_modal_callback() {
            app.geofences.list();
        }
    </script>
@stop

@section('buttons')
    <button type="button" class="btn btn-action update">{!!trans('global.save')!!}</button>
    <button type="button" class="btn btn-default" data-dismiss="modal">{!!trans('global.cancel')!!}</button>
@stop