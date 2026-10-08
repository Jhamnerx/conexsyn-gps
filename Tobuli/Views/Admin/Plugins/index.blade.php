@extends('Admin.Layouts.default')

@section('content')
    @if (Session::has('errors'))
        <div class="alert alert-danger">
            <ul>
                @foreach (Session::get('errors')->all() as $error)
                    <li>{!! $error !!}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="panel panel-default">

        <div class="panel-heading">
            <div class="panel-title">{{ trans('admin.plugins') }}</div>
        </div>

        <div class="panel-body" data-table>
            {!! Form::open(array('route' => 'admin.plugins.save', 'method' => 'POST', 'class' => 'form form-horizontal', 'id' => 'plugin-form')) !!}

            <div class="list-group">
            @foreach($plugins as $plugin)
                <div class="col-lg-4 col-md-6 list-group-item">
                    <div class="">
                        @if(View::exists('Admin.Plugins.Partials.'.$plugin->key))
                            <div class="pull-right">
                                <a class="btn btn-xs" type="button" data-target="#modal-options-{{ $plugin->key }}" data-toggle="modal">
                                    <i class="icon edit"></i>
                                </a>

                                <div class="modal fade" id="modal-options-{{ $plugin->key }}">
                                    <div class="modal-dialog modal-md">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                {{ $plugin->name }}
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                                            </div>
                                            <div class="modal-body">
                                                @include('Admin.Plugins.Partials.'.$plugin->key)
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="checkbox-inline">
                            {!! Form::checkbox('plugins['.$plugin->key.'][status]', 1, $plugin->status) !!}
                            {!! Form::label($plugin->name) !!}
                        </div>

                    </div>
                </div>
            @endforeach
            </div>

            {!! Form::close() !!}
        </div>

        <div class="panel-footer">
            <button type="submit" class="btn btn-action" onClick="$('#plugin-form').submit();">{{ trans('global.save') }}</button>
        </div>

    </div>
@stop