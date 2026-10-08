@extends('Frontend.Reports.partials.layout')

@section('content')
    <div class="panel panel-default">
        @include('Frontend.Reports.partials.item_heading')

        <div class="panel-body no-padding">
            <table class="table table-hover">
                <thead>
                <tr>
                    <th>Vehicle No</th>
                    <th>Days since last communication</th>
                    <th>Date</th>
                    <th>Last Communication Date</th>
                    <th>Site Name / Transporter Name</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($report->getItems() as $item)
                    <tr>
                        <td>{!! $item['plate_number'] !!}</td>
                        <td>{!! $item['days'] !!}</td>
                        <td>{!! $item['date'] !!}</td>
                        <td>{!! $item['time'] !!}</td>
                        <td>{!! $item['object_owner'] !!}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">{{ trans('front.nothing_found_request') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop