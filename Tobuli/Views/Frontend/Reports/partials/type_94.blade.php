@extends('Frontend.Reports.partials.layout')

@section('content')
    @foreach ($report->getItems() as $item)
        <div class="panel panel-default">
            @include('front::Reports.partials.item_heading')

            @if (isset($item['error']))
                @include('front::Reports.partials.item_empty')
            @elseif (!empty($item['table']))
                <div class="panel-body no-padding">
                    <table class="table table-hover">
                        <thead>
                        <tr>
                            <th>{{ trans('front.geofence_exit') }}</th>
                            <th>{{ trans('front.geofence_entry') }}</th>
                            <th>{{ trans('front.duration') }}</th>
                            <th>{{ trans('global.distance') }}</th>
                            <th>{{ trans('front.geofence') }}</th>
                            <th>{{ trans('front.drivers') }}</th>
                            @if($report->show_addresses)
                                <th>{{ trans('front.address') }}</th>
                            @endif
                        </tr>
                        </thead>

                        <tbody>
                        @foreach ($item['table'] as $row)
                            <tr>
                                <td>{{ $row['start_at'] }}</td>
                                <td>{{ $row['end_at'] }}</td>
                                <td>{{ $row['duration'] }}</td>
                                <td>{{ $row['distance'] }}</td>
                                <td>{{ $row['group_geofence'] }}</td>
                                <td>{{ $row['drivers'] }}</td>
                                @if($report->show_addresses)
                                    <th>{{ $row['location'] }}</th>
                                @endif
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endforeach
@stop