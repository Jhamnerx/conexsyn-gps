@extends('Frontend.Dashboard.Blocks.options_layout')

@section('fields')
    @foreach($options['colors'] as $key => $color)
        <div class="form-group">
        {!! Form::color('dashboard[blocks][device_overview][options][colors]['.$key.']', $color, ['class' => '']) !!}
        {!! Form::label('dashboard[blocks][device_overview][options][colors]['.$key.']', trans('front.'.$key)) !!}
        </div>
    @endforeach
@overwrite


