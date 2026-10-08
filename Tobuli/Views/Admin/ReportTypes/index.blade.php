@extends('Admin.Layouts.default')

@section('content')
    <div class="panel panel-default">
        <div class="panel-heading">
            <div class="panel-title">{{ trans('admin.report_types') }}</div>
        </div>

        <div class="panel-body">
            <table class="table">
                <thead>
                <th>{{ trans('front.report') }}</th>
                </thead>
                <tbody>

                @php /** @var \Tobuli\Reports\Report $report */ @endphp
                @foreach($reports as $id => $report)
                    <tr>
                        <td>
                            <div class="checkbox">
                                {!! Form::checkbox(
                                        "reports[$id][status]",
                                        1,
                                        isset($user) ? $report->isUserEnabled($user) : $report->isEnabled(),
                                        ['class' => 'report_status'] + ($report->isReasonable() ? [] : ['disabled' => 'disabled'])
                                    ) !!}
                                {!! Form::label("reports[$id][status]", $report->title() ) !!}
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@stop

@section('javascript')
    <script>
        $(document).ready(function() {
            $(document).on('change', '.report_status', function () {
                let checked = +$(this).prop('checked');
                let name = $(this).attr('name');

                let data = {};
                data[name] = checked;

                $.ajax({
                    type: 'POST',
                    dataType: 'html',
                    url: '{!! route('admin.report_types.store') !!}',
                    data: data,
                });
            });
        });
    </script>
@stop