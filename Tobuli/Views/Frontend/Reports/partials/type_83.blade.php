@extends('front::Reports.partials.layout')

@section('content')
    <div class="panel panel-default">
        @include('front::Reports.partials.item_heading')

        <div class="panel-body no-padding">
            <table class="table table-hover">
                <thead>
                <tr>
                    @foreach($report->metas() as $meta)
                        <th>{{ $meta['title'] }}</th>
                    @endforeach
                    <th>{{ trans('front.consumption') }} m³</th>
                    <th>{{ trans('front.hours') }}</th>
                    <th>{{ trans('front.flow_rate') }} m³/h</th>
                    <th>{{ trans('front.flow_rate') }} L/s</th>
                    <th>{{ trans('front.location') }}</th>
                </tr>
                </thead>

                <tbody>
                @forelse($report->getItems() as $item)
                    <tr>
                        @foreach($item['meta'] as $key => $meta)
                            <td>{{ $meta['value'] }}</td>
                        @endforeach

                        @if(isset($item['error']))
                            <td colspan="5">{{ $item['error'] }}</td>
                        @else
                            <td>{{ $item['fuel_consumption_custom'] }}</td>
                            <td>{{ $item['engine_hours_custom'] }}</td>
                            <td>{{ $item['flow_rate_m3_h'] }}</td>
                            <td>{{ $item['flow_rate_l_s'] }}</td>
                            <td>{!! $item['location'] !!}</td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 5 + count($report->metas()) }}">
                            {{ trans('front.nothing_found_request') }}
                        </td>
                    </tr>
                @endforelse
                </tbody>

                @if($totals = $report->globalTotals())
                    <tfoot>
                    <tr>
                        <th colspan="{{ count($report->metas()) }}">{{ trans('global.total') }}</th>
                        <th>{{ $totals['fuel_consumption_custom'] }}</th>
                        <th>{{ $totals['engine_hours_custom'] }}</th>
                        <th>{{ $totals['flow_rate_m3_h'] }}</th>
                        <th>{{ $totals['flow_rate_l_s'] }}</th>
                        <th></th>
                    </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
@stop