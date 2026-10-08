@extends('Frontend.Reports.partials.layout')

@section('content')
    <div class="panel panel-default">
        @include('Frontend.Reports.partials.item_heading')

        <div class="panel-body no-padding">
            <table class="table table-hover">
                <thead>
                <tr>
                    @foreach($report->metas() as $meta)
                        <th>{{ $meta['title'] }}</th>
                    @endforeach
                        <th>{{ trans('front.zone_in') }}</th>
                        <th>{{ trans('front.zone_out') }}</th>
                        <th>{{ trans('front.duration') }}</th>
                        <th>{{ trans('global.distance') }}</th>
                        <th>{{ trans('validation.attributes.geofence_name') }}</th>
                        <th>{{ trans('front.position') }}</th>
                    @for($i = 1; $i < ($report->getMaxDriversPerGroup()+1); $i++)
                        <th>{{ trans('front.driver') . " #$i" }}</th>
                    @endfor
                </tr>
                </thead>
                <tbody>
                @foreach ($report->getItems() as $item)

                    @if (isset($item['error']))
                        <tr>
                            @foreach($item['meta'] as $key => $meta)
                                <td>{{ $meta['value'] }}</td>
                            @endforeach
                                <td colspan="{{ 6 + $report->getMaxDriversPerGroup() }}">{{ $item['error'] }}</td>
                        </tr>
                    @else
                        @foreach($item['table']['rows'] as $row)
                            <tr>
                                @foreach($item['meta'] as $key => $meta)
                                    <td>{{ $meta['value'] }}</td>
                                @endforeach
                                <td>{{ $row['start_at'] }}</td>
                                <td>{{ $row['end_at'] }}</td>
                                <td>{{ $row['duration'] }}</td>
                                <td>{{ $row['distance'] }}</td>
                                <td>{{ $row['group_geofence'] }}</td>
                                <td>{!! $row['location'] !!}</td>
                                @for($i = 1; $i < ($report->getMaxDriversPerGroup()+1); $i++)
                                    <td>{{ $row['driver_' . $i] ?? '' }}</td>
                                @endfor
                            </tr>
                        @endforeach
                    @endif

                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@stop