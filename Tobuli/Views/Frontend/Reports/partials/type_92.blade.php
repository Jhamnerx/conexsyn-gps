@extends('Frontend.Reports.partials.layout')

@section('content')
    <div class="panel panel-default">
        @include('Frontend.Reports.partials.item_heading')

        <div class="panel-body no-padding">
            <table class="table table-hover">
                <thead>
                <tr>
                    <th>Trip ID</th>
                    <th>Date</th>
                    <th>Event Type</th>
                    <th>Vehicle No</th>
                    <th>Driver Name</th>
                    <th>Driver Employee ID</th>
                    <th>Event Key</th>
                    <th>Event start Location</th>
                    <th>Event End Location</th>
                    <th>Event Start Date</th>
                    <th>Event End Date</th>
                    <th>Event Duration</th>
                    <th>Event Distance (KMs)</th>
                    <th>Event Threshold</th>
                    <th>Event Start Coordinates</th>
                    <th>Event End Coordinates</th>
                    <th>Event Max Value</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($report->getItems() as $item)
                    <tr>
                        <td>{!! $item['trip_id'] !!}</td>
                        <td>{!! $item['date'] !!}</td>
                        <td>{!! $item['type'] !!}</td>
                        <td>{!! $item['plate_number'] !!}</td>
                        <td>{!! $item['driver_name'] !!}</td>
                        <td>{!! $item['driver_rfid'] !!}</td>
                        <td>{!! $item['event_key'] !!}</td>
                        <td>{!! $item['location_start'] !!}</td>
                        <td>{!! $item['location_end'] !!}</td>
                        <td>{!! $item['start_at'] !!}</td>
                        <td>{!! $item['end_at'] !!}</td>
                        <td>{!! $item['duration'] !!}</td>
                        <td>{!! $item['distance'] !!}</td>
                        <td>{!! $item['threshold'] !!}</td>
                        <td>{!! $item['start_coordinates'] !!}</td>
                        <td>{!! $item['end_coordinates'] !!}</td>
                        <td>{!! $item['max_value'] !!}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="16">{{ trans('front.nothing_found_request') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop