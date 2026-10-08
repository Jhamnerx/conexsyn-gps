@extends('front::Reports.partials.layout')

@section('content')
    <div class="panel panel-default">
        @include('front::Reports.partials.item_heading')

        <div class="panel-body no-padding">
            <table class="table table-hover">
                <thead>
                <tr>
                    <th>{{ trans('front.driver') }}</th>
                    @foreach($report->metas() as $meta)
                        <th>{{ $meta['title'] }}</th>
                    @endforeach
                    <th>{{ trans('front.start') }}</th>
                    <th>{{ trans('front.end') }}</th>
                    <th>{{ trans('global.distance') }}</th>
                    <th>{{ trans('front.move_duration') }}</th>
                    <th>{{ trans('front.stop_duration') }}</th>
                    <th>{{ trans('front.engine_hours') }}</th>
                    <th>{{ trans('front.idle_duration') }}</th>
                    <th>{{ trans('front.rpm_max') }}</th>
                    <th>{{ trans('front.top_speed') }}</th>
                    <th>{{ trans('front.average_speed') }}</th>
                    <th>{{ trans('front.overspeeds_count') }}</th>
                    <th>{{ trans('front.harsh_braking_count') }}</th>
                    <th>{{ trans('front.harsh_acceleration_count') }}</th>
                    <th>{{ trans('front.harsh_turning_count') }}</th>
                    <th>{{ trans('front.driver_note') }}</th>
                </tr>
                </thead>

                <tbody>
                @forelse ($report->getItems() as $item)
                    <tr>
                        <td>{{ $item['drivers'] }}</td>
                        @foreach($item['meta'] as $key => $meta)
                            <td>{{ $meta['value'] }}</td>
                        @endforeach
                        <td>{{ $item['start_at'] }}</td>
                        <td>{{ $item['end_at'] }}</td>
                        <td>{{ $item['distance'] }}</td>
                        <td>{{ $item['drive_duration'] }}</td>
                        <td>{{ $item['stop_duration'] }}</td>
                        <td>{{ $item['engine_hours'] }}</td>
                        <td>{{ $item['engine_idle'] }}</td>
                        <td>{{ $item['rpm_max'] }}</td>
                        <td>{{ $item['speed_max'] }}</td>
                        <td>{{ $item['speed_avg'] }}</td>
                        <td>{{ $item['overspeed_count'] }}</td>
                        <td>{{ $item['harsh_breaking_count'] }}</td>
                        <td>{{ $item['harsh_acceleration_count'] }}</td>
                        <td>{{ $item['harsh_turning_count'] }}</td>
                        <td></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 15 + count($report->metas()) }}">
                            {{ trans('front.nothing_found_request') }}
                        </td>
                    </tr>
                @endforelse
                </tbody>

                @if($totals = $report->globalTotals())
                    <tfoot>
                        <tr>
                            <td></td>
                            @foreach($item['meta'] as $key => $meta)
                                <td></td>
                            @endforeach
                            <td></td>
                            <td></td>
                            <td>{{ $totals['distance'] }}</td>
                            <td>{{ $totals['drive_duration'] }}</td>
                            <td>{{ $totals['stop_duration'] }}</td>
                            <td>{{ $totals['engine_hours'] }}</td>
                            <td>{{ $totals['engine_idle'] }}</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td>{{ $totals['overspeed_count'] }}</td>
                            <td>{{ $totals['harsh_breaking_count'] }}</td>
                            <td>{{ $totals['harsh_acceleration_count'] }}</td>
                            <td>{{ $totals['harsh_turning_count'] }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
@stop
