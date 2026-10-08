@extends('Frontend.Layouts.modal')

@section('title', trans('global.edit'))
@php /** @var \Tobuli\Entities\Tag $item */ @endphp
@section('body')
    {!! Form::open(['route' => 'admin.tags.update', 'method' => 'PUT']) !!}
    {!! Form::hidden('id', $item->id) !!}

    <div class="row">
        <div class="col-sm-12">
            <div class="form-group">
                {!! Form::label('name', trans('validation.attributes.name') . ':') !!}
                {!! Form::text('name', $item->name, ['class' => 'form-control']) !!}
            </div>
        </div>

        <div class="col-sm-12">
            <div class="form-group">
                {!! Form::label('color', trans('validation.attributes.color') . ':') !!}
                {!! Form::select('color', $colorOptions, $item->color, ['class' => 'form-control']) !!}
            </div>
        </div>
    </div>

    {!! Form::close() !!}
@stop