@php /** @var \Tobuli\Reports\Reports\LastLocationReport $report */ @endphp

@extends('Frontend.Reports.partials.layout')

@section('content')
    <div class="panel panel-default">
        @include('Frontend.Reports.partials.item_heading')

        <div class="panel-body no-padding">
            <table class="table table-hover">
                <thead>
                <tr>
                    @foreach($report->metas() as $key => $meta)
                        <th>{{ $meta['title'] }}</th>
                    @endforeach

                    <th>{{ trans('admin.last_connection') }}</th>

                    @if($report->show_addresses)
                        <th>{{ trans('front.address') }}</th>
                    @endif

                    @if($report->hasGeofences())
                        <th>{{ trans('front.geofences') }}</th>
                    @endif

                    <th>{{ trans('front.coordinates') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($report->getItems() as $item)
                    <tr>
                        @foreach($item['meta'] as $key => $meta)
                            <td>{{ $meta['value'] }}</td>
                        @endforeach

                        @if (isset($item['error']))
                            <td colspan="10">{{ $item['error'] }}</td>

                            @continue;
                        @endif

                        <td>{{ $item['last_connection'] }}</td>

                        @if($report->show_addresses)
                            <td>{!! $item['address'] !!}</td>
                        @endif

                        @if($report->hasGeofences())
                            <td>{!! $item['geofences'] !!}</td>
                        @endif

                        <td>{!! $item['coordinates'] !!}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="99">{{ trans('front.nothing_found_request') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop