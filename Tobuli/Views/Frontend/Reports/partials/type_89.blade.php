@extends('Frontend.Reports.partials.layout')

@section('content')
    <div class="panel panel-default">
        @include('Frontend.Reports.partials.item_heading')

        <div class="panel-body no-padding">
            <table class="table table-hover">
                <thead>
                <tr>
                    <th>Trip ID</th>
                    <th>Trip Start Date</th>
                    <th>Trip End Date</th>
                    <th>Distance in Kms</th>
                    <th>Trip Driving Time</th>
                    <th>Trip Stop Time</th>
                    <th>Trip Idle Time</th>
                    <th>Vehicle No</th>
                    <th>Driver Name</th>
                    <th>Driver Employee ID</th>
                    <th>Trip Start Coordinates</th>
                    <th>Trip End Coordinates</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($report->getItems() as $item)
                    <tr>
                        <td>{!! $item['trip_id'] !!}</td>
                        <td>{!! $item['start_at'] !!}</td>
                        <td>{!! $item['end_at'] !!}</td>
                        <td>{!! $item['distance'] !!}</td>
                        <td>{!! $item['drive_duration'] !!}</td>
                        <td>{!! $item['stop_duration'] !!}</td>
                        <td>{!! $item['engine_idle'] !!}</td>
                        <td>{!! $item['plate_number'] !!}</td>
                        <td>{!! $item['driver_name'] !!}</td>
                        <td>{!! $item['driver_rfid'] !!}</td>
                        <td>{!! $item['start_coordinates'] !!}</td>
                        <td>{!! $item['end_coordinates'] !!}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12">{{ trans('front.nothing_found_request') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop