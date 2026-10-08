@extends('front::Reports.partials.layout')

@section('content')
    <div class="panel panel-default">
        @include('front::Reports.partials.item_heading')

        <div class="panel-body no-padding">
            <table class="table table-hover">
                <thead>
                <tr>
                    @foreach($report->metas() as $key => $meta)
                        <th rowspan="2">{{ $meta['title'] }}</th>
                    @endforeach

                    <th rowspan="2">{{ trans('validation.attributes.status') }}</th>
                    <th rowspan="2">{{ trans('front.start') }}</th>
                    <th rowspan="2">{{ trans('front.end') }}</th>
                    <th rowspan="2">{{ trans('front.duration') }}</th>
                    <th rowspan="2">{{ trans('front.engine_idle') }}</th>
                    <th rowspan="2">{{ trans('front.driver') }}</th>
                    <th colspan="3">{{ trans('front.stop_position') }}</th>

                    @if ($report->zones_instead)
                        <th rowspan="2">{{ trans('front.geofences') }}</th>
                    @endif
                </tr>
                <tr>
                    <th>{{ trans('front.route_length') }}</th>
                    <th>{{ trans('front.top_speed') }}</th>
                    <th>{{ trans('front.average_speed') }}</th>
                </tr>
                </thead>

                <tbody>
                @php $colWidth = 9 + (int)$report->zones_instead @endphp

                @foreach($report->getItems() as $item)
                    @if(empty($item['table']))
                        <tr>
                            @foreach($item['meta'] as $key => $meta)
                                <td>{{ $meta['value'] }}</td>
                            @endforeach

                            <td colspan="{{ $colWidth }}">
                                {{ trans('front.nothing_found_request') }}
                            </td>
                        </tr>
                        @continue
                    @endif

                    @foreach($item['table'] as $row)
                        <tr>
                            @foreach($item['meta'] as $key => $meta)
                                <td>{{ $meta['value'] }}</td>
                            @endforeach

                            <td>{{ $row['status'] }}</td>
                            <td>{{ $row['start_at'] }}</td>
                            <td>{{ $row['end_at'] }}</td>
                            <td>{{ $row['duration'] }}</td>
                            <td>{{ $row['engine_idle'] }}</td>
                            <td>{{ $row['drivers'] }}</td>

                            @if ($row['group_key'] === 'drive')
                                <td>{{ $row['distance'] }}</td>
                                <td>{{ $row['speed_max'] }}</td>
                                <td>{{ $row['speed_avg'] }}</td>
                            @else
                                <td colspan="3">{!! $row['location'] !!}</td>
                            @endif

                            @if ($report->zones_instead)
                                <td>{{ \Illuminate\Support\Arr::get($row, 'geofences_in') }}</td>
                            @endif
                        </tr>
                    @endforeach
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@stop
