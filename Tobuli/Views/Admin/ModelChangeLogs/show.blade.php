@inject('outputBeautifier', 'Tobuli\Services\OutputBeautifierService')

@extends('Frontend.Layouts.modal')

@section('title')
    {!! trans('front.difference') !!}
@stop

@section('body')
    {!! Form::open(['route' => ['admin.model_change_logs.show', $item->id], 'method' => 'GET']) !!}

    <div class="form-group">
        {!! Form::label('new_values', trans('front.new_values').':') !!}
        {!! Form::textarea(
            'new_values',
            $outputBeautifier->arrayToKeyValueText($item->properties['attributes'] ?? null),
            ['readonly' => true, 'class' => 'form-control', 'rows' => 15]
        ) !!}
    </div>

    <div class="form-group">
        {!! Form::label('old_values', trans('front.old_values').':') !!}
        {!! Form::textarea(
            'old_values',
            $outputBeautifier->arrayToKeyValueText($item->properties['old'] ?? null),
            ['readonly' => true, 'class' => 'form-control', 'rows' => 15]
        ) !!}
    </div>

    {!! Form::close() !!}
@stop

@section('buttons')
    <button type="button" class="btn btn-default" data-dismiss="modal">
        {!! trans('global.close') !!}
    </button>
@stop