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

    <div class="panel panel-default" id="table_sms_gateway">

        <div class="panel-heading">
            <div class="panel-title"><i class="icon"></i> {!! trans('front.widgets') !!} </div>
        </div>

        {!! Form::open(['route' => 'admin.widgets.store', 'method' => 'POST']) !!}

        <div class="panel-body">
            <div class="checkbox">
                {!! Form::hidden('default', 0) !!}
                {!! Form::checkbox('default', 1, !empty($widgets['default']), ['id' => 'default_widgets']) !!}
                {!! Form::label('default', trans('front.default') . ' ' .trans('front.widgets')) !!}
            </div>

            <hr>

            <div data-disablable="#default_widgets;enable">
                <div class="checkbox">
                    {!! Form::checkbox('status', 1, !empty($widgets['status'])) !!}
                    {!! Form::label('status', trans('front.enable_widgets')) !!}
                </div>

                <div id="setup-widgets-list" class="row">
                    @if ( !empty($widgets['list']) )
                        @foreach($widgets['list'] as $widget)
                            @if ( !empty($widgets_list[$widget]) )
                                <div class="col-md-3">
                                    <div class="dashboard-widget clearfix">
                                        <div class="pull-left">
                                            <div class="checkbox-inline">
                                                {!! Form::checkbox('list[]', $widget, true) !!}
                                                {!! Form::label(null, $widgets_list[$widget]) !!}
                                            </div>
                                        </div>
                                        <div class="pull-right">
                                            <div class="sortdragger btn btn-xs">
                                                <i class="fa fa-sort"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    @endif

                    @foreach($widgets_list as $widget => $title)
                        @if ( empty($widgets['list']) )
                            <div class="col-md-3">
                                <div class="dashboard-widget clearfix">
                                    <div class="pull-left">
                                        <div class="checkbox-inline">
                                            {!! Form::checkbox('list[]', $widget, true) !!}
                                            {!! Form::label(null, $title) !!}
                                        </div>
                                    </div>
                                    <div class="pull-right">
                                        <div class="sortdragger btn btn-xs">
                                            <i class="fa fa-sort"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @elseif ( !in_array($widget, $widgets['list']) )
                            <div class="col-md-3">
                                <div class="dashboard-widget clearfix">
                                    <div class="pull-left">
                                        <div class="checkbox-inline">
                                            {!! Form::checkbox('list[]', $widget, false) !!}
                                            {!! Form::label(null, $title) !!}
                                        </div>
                                    </div>
                                    <div class="pull-right">
                                        <div class="sortdragger btn btn-xs">
                                            <i class="fa fa-sort"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        <div class="panel-footer">
            <button type="submit" class="btn btn-action">Save</button>
        </div>

        {!! Form::close() !!}
    </div>
@stop


@section('javascript')
    <script>
        $( "#setup-widgets-list" ).sortable({
            handle: ".sortdragger"
        });
    </script>
@stop
