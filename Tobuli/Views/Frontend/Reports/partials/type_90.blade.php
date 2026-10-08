@extends('Frontend.Reports.partials.layout')

@section('content')
    <div class="panel panel-default">
        @include('Frontend.Reports.partials.item_heading')

        <div class="panel-body no-padding">
            <table class="table table-hover">
                <thead>
                <tr>
                    <th>Geofence Name</th>
                    <th>Geofence Enter Time</th>
                    <th>Geofence Exit Time</th>
                    <th>Vehicle No</th>
                    <th>Driver Name</th>
                    <th>Driver Employee ID</th>
                    <th>Geofence Lat</th>
                    <th>Geofence Long</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($report->getItems() as $item)
                    <tr>
                        <td>{!! $item['group_geofence'] !!}</td>
                        <td>{!! $item['start_at'] !!}</td>
                        <td>{!! $item['end_at'] !!}</td>
                        <td>{!! $item['plate_number'] !!}</td>
                        <td>{!! $item['driver_name'] !!}</td>
                        <td>{!! $item['driver_rfid'] !!}</td>
                        <td>{!! $item['entry_latitude'] !!}</td>
                        <td>{!! $item['entry_longitude'] !!}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">{{ trans('front.nothing_found_request') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop