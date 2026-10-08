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
                            <th>{{ trans('validation.attributes.date') }}</th>
                            <th>{{ trans('front.hour') }}</th>
                            <th>{{ trans('front.net_cumulative_flow') }} m3</th>
                            <th>{{ trans('front.flow_rate') }} m3/h</th>
                            <th>{{ trans('front.speed') }} m/s</th>
                            <th>{{ trans('front.location') }}</th>
                        </tr>
                        </thead>

                        <tbody>
                        @foreach ($item['table'] as $date => $table)
                            @foreach ($table as $hour => $row)
                                <tr>
                                    <td>{{ $date }}</td>
                                    <td>{{ $hour }}</td>
                                    <td>{{ $row['net_amount'] }}</td>
                                    <td>{{ $row['flow_rate'] }}</td>
                                    <td>{{ $row['speed'] }}</td>
                                    <td>{!! $row['location'] !!}</td>

                                    @php $date = null @endphp
                                </tr>
                            @endforeach
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endforeach
@stop