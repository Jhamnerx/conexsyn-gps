@extends('front::Reports.partials.layout')

@section('content')
    <div class="panel panel-default">
        @include('front::Reports.partials.item_heading')

        <div class="panel-body no-padding">
            <table class="table table-hover">
                <thead>
                <tr>
                    <th>{{ trans('global.date') }}</th>

                    @foreach($report->metas() as $key => $meta)
                        @if($key !== 'device.group_id')
                            <th>{{ $meta['title'] }}</th>
                        @endif
                    @endforeach

                    <th>{{ trans('front.from') }} (m3)</th>
                    <th>{{ trans('front.to') }} (m3)</th>
                    <th>{{ trans('front.difference') }} (m3)</th>
                    <th>{{ trans('front.from') }} (h)</th>
                    <th>{{ trans('front.to') }} (h)</th>
                    <th>{{ trans('front.difference') }} (h)</th>
                    <th>{{ trans('front.flow_rate') }} m3/h</th>
                    <th>{{ trans('front.flow_rate') }} l/s</th>
                    <th>{{ trans('front.location') }}</th>
                </tr>
                </thead>

                <tbody>
                @php
                    $totals = $report->globalTotals();
                    $items = $report->getItems();
                @endphp

                @foreach($items as $i => $item)
                    @foreach($item['table'] as $rowDate => $row)
                        <tr>
                            <td>{{ $rowDate }}</td>

                            @foreach($item['meta'] as $key => $meta)
                                @if($key !== 'device.group_id')
                                    <td>{{ $meta['value'] }}</td>
                                @endif
                            @endforeach

                            <td>{{ $row['net_amount_min'] }}</td>
                            <td>{{ $row['net_amount_max'] }}</td>
                            <td>{{ $row['net_amount_diff'] }}</td>
                            <td>{{ $row['engine_hours_from'] }}</td>
                            <td>{{ $row['engine_hours_to'] }}</td>
                            <td>{{ $row['engine_hours_diff'] }}</td>
                            <td>{{ $row['rate_m3_h'] }}</td>
                            <td>{{ $row['rate_l_s'] }}</td>
                            <td>{!! $row['location'] !!}</td>
                        </tr>
                    @endforeach

                    <tr>
                        <th></th>
                        @foreach($item['meta'] as $key => $meta)
                            @if($key !== 'device.group_id')
                                <th>{{ $meta['value'] }}</th>
                            @endif
                        @endforeach
                        <th></th>
                        <th></th>
                        <th>{{ $item['totals']['net_amount_diff'] }}</th>
                        <th></th>
                        <th></th>
                        <th>{{ $item['totals']['engine_hours_diff'] }}</th>
                        <th>{{ $item['totals']['rate_m3_h'] }}</th>
                        <th>{{ $item['totals']['rate_l_s'] }}</th>
                        <th></th>
                    </tr>

                    @php $groupId = $item['meta']['device.group_id']['value'] @endphp

                    @if(!isset($items[$i + 1]) || $items[$i + 1]['meta']['device.group_id']['value'] !== $groupId)
                        <tr>
                            <th></th>
                            <th colspan="{{ count($report->metas()) - 1 }}">{{ $groupId ?: trans('front.ungrouped') }}</th>
                            <th></th>
                            <th></th>
                            <th>{{ $totals[$groupId]['net_amount_diff'] }}</th>
                            <th></th>
                            <th></th>
                            <th>{{ $totals[$groupId]['engine_hours_diff'] }}</th>
                            <th>{{ $totals[$groupId]['rate_m3_h'] }}</th>
                            <th>{{ $totals[$groupId]['rate_l_s'] }}</th>
                            <th></th>
                        </tr>
                    @endif
                @endforeach
            </table>
        </div>
    </div>
@stop