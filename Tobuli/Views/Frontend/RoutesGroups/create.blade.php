@extends('Frontend.Layouts.modal')

@section('title')
    {!! trans('global.add') !!}
@stop

@section('body')

    {!! Form::open(['route' => ['route_groups.store'], 'method' => 'POST']) !!}
    <div class="form-group">
        {!! Form::label('title', trans('validation.attributes.title').':') !!}
        {!! Form::text('title', null, ['class' => 'form-control']) !!}
    </div>

    <div class="form-group">
        {!! Form::label('routes', trans('front.route').':') !!}
        {!! Form::select('routes[]', $routes->pluck('name', 'id')->all(), null, ['class' => 'form-control multiexpand', 'multiple' => 'multiple', 'data-live-search' => 'true', 'data-actions-box' => 'true']) !!}
    </div>
    {!! Form::close() !!}

    <script>
        function route_groups_create_modal_callback() {
            app.routes.list();
        }
    </script>
@stop

@section('buttons')
    <button type="button" class="btn btn-action update">{!!trans('global.save')!!}</button>
    <button type="button" class="btn btn-default" data-dismiss="modal">{!!trans('global.cancel')!!}</button>
@stop