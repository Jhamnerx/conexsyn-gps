@extends('Frontend.Layouts.modal')

@section('title')
    <i class="icon event"></i> {{ trans('global.add') }}
@stop

@section('body')
    <ul class="nav nav-tabs nav-default" role="tablist">
        <li class="active"><a href="#plan-form-main" role="tab" data-toggle="tab">{{ trans('front.main') }}</a></li>

        @if(config('addon.plan_templates'))
            <li>
                <a href="#plan-form-template" role="tab" data-toggle="tab">
                    {{ trans('validation.attributes.template') }}
                </a>
            </li>
        @endif
    </ul>

    {!!Form::open(['route' => 'admin.device_plan.store', 'method' => 'POST'])!!}
        @include('Admin.DevicePlans.form')
    {!! Form::close() !!}
@stop
