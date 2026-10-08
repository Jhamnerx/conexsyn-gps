@extends('Frontend.Reports.partials.layout')

@section('content')
    <div class="panel panel-default">
        @include('Frontend.Reports.partials.item_heading')

        <div class="panel-body no-padding">
            <table class="table table-hover">
                <thead>
                <tr>
                    <th>Driver Name</th>
                    <th>Driver Employee ID</th>
                    <th>Site Name / Transporter Name</th>
                    <th>Date</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($report->getItems() as $item)
                    <tr>
                        <td>{{ $item['driver_name'] }}</td>
                        <td>{{ $item['driver_rfid'] }}</td>
                        <td>{{ $item['object_owner'] }}</td>
                        <td>{{ $item['date'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">{{ trans('front.nothing_found_request') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop