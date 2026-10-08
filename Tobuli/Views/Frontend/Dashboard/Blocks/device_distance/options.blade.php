@extends('Frontend.Dashboard.Blocks.options_layout')

@section('fields')
    @include('Frontend.Dashboard.Blocks.partials.period_select', [
        'block'  => 'device_distance',
        'period' => $options['period'],
        'excluded' => ['month']
    ])
@overwrite


