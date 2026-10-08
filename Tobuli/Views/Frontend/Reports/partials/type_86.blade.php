@extends('front::Reports.partials.layout')

@section('content')
    @php
        $char = 'B';
        $line = 0;
        $colsCount = 10 + count($report->metas());
    @endphp


    <div class="panel panel-default">
        @include('front::Reports.partials.item_heading')

        <div class="panel-body no-padding">
            <table class="table table-hover">
                <thead>
                    @php $line++ @endphp

                    <tr>
                        <th>{{ trans('front.driver') }}</th>
                        @foreach($report->metas() as $meta)
                            @php $char++ @endphp
                            <th>{{ $meta['title'] }}</th>
                        @endforeach
                        <th>{{ trans('front.driving_score') }} (%)</th>
                        <th>{{ trans('front.distance_driver') }}</th>
                        <th>{{ trans('front.overspeed_count') }}</th>
                        <th>{{ trans('front.overspeed_score') }}</th>
                        <th>{{ trans('front.harsh_acceleration_count') }}</th>
                        <th>{{ trans('front.harsh_acceleration_score') }}</th>
                        <th>{{ trans('front.harsh_braking_count') }}</th>
                        <th>{{ trans('front.harsh_braking_score') }}</th>
                        <th>{{ trans('front.harsh_turning_count') }}</th>
                        <th>{{ trans('front.harsh_turning_score') }}</th>
                    </tr>
                </thead>

                <tbody>

                @php
                    $charDist = chr(ord($char) + 1);
                    $charSpeedCt = chr(ord($char) + 2);
                    $charSpeedSc = chr(ord($char) + 3);
                    $charHaCt = chr(ord($char) + 4);
                    $charHaSc = chr(ord($char) + 5);
                    $charHbCt = chr(ord($char) + 6);
                    $charHbSc = chr(ord($char) + 7);
                    $charHtCt = chr(ord($char) + 8);
                    $charHtSc = chr(ord($char) + 9);
                @endphp

                @forelse($report->getItems() as $driverData)
                    @foreach($driverData as $row)
                        @php $line++ @endphp

                        <tr>
                            @if (in_array($report->getFormat(), ['xls', 'xlsx']))
                                <td>{{ $row['drivers'] }}</td>

                                @foreach($row['meta'] as $deviceProperty)
                                    <td>{{ $deviceProperty['value'] }}</td>
                                @endforeach

                                @php $dist = $charDist . $line @endphp

                                <td>=MAX(0, 100 - {{ $charSpeedSc.$line }} - {{ $charHaSc.$line }} - {{ $charHbSc.$line }} - {{ $charHtSc.$line }})</td>
                                <td>{{ $row['distance'] }}</td>
                                <td>{{ $row['count_overspeed'] }}</td>
                                <td>=IF({{ $dist }},{{ $charSpeedCt.$line }}/{{ $dist }}*100,0)</td>
                                <td>{{ $row['ha'] }}</td>
                                <td>=IF({{ $dist }},{{ $charHaCt.$line }}/{{ $dist }}*100,0)</td>
                                <td>{{ $row['hb'] }}</td>
                                <td>=IF({{ $dist }},{{ $charHbCt.$line }}/{{ $dist }}*100,0)</td>
                                <td>{{ $row['ht'] }}</td>
                                <td>=IF({{ $dist }},{{ $charHtCt.$line }}/{{ $dist }}*100,0)</td>
                            @else
                                <td>{{ $row['drivers'] }}</td>

                                @foreach($row['meta'] as $deviceProperty)
                                    <td>{{ $deviceProperty['value'] }}</td>
                                @endforeach

                                <td>{{ $row['rag'] }}</td>
                                <td>{{ $row['distance'] }}</td>
                                <td>{{ $row['count_overspeed'] }}</td>
                                <td>{{ $row['score_overspeed'] }}</td>
                                <td>{{ $row['ha'] }}</td>
                                <td>{{ $row['score_harsh_a'] }}</td>
                                <td>{{ $row['hb'] }}</td>
                                <td>{{ $row['score_harsh_b'] }}</td>
                                <td>{{ $row['ht'] }}</td>
                                <td>{{ $row['score_harsh_t'] }}</td>
                            @endif
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="{{ $colsCount }}">
                            {{ trans('front.nothing_found_request') }}
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($report->getFormat() != 'xls')
        <div class="panel panel-default">
            <div class="panel-body no-padding" style="padding: 0px;">
                <table class="table" style="table-layout: auto; margin-bottom: 0px;">
                    <tbody>
                    <tr>
                        <td style="width: 150px;">D</td>
                        <td>{{ trans('front.distance_driver') }}</td>
                    </tr>
                    <tr>
                        <td style="width: 150px;">OC</td>
                        <td>{{ trans('front.overspeed_count') }}</td>
                    </tr>
                    <tr>
                        <td style="width: 150px;">OS = OC / D * 100</td>
                        <td>{{ trans('front.overspeed_score') }}</td>
                    </tr>
                    <tr>
                        <td style="width: 150px;">AC</td>
                        <td>{{ trans('front.harsh_acceleration_count') }}</td>
                    </tr>
                    <tr>
                        <td style="width: 150px;">AS = AC / D * 100</td>
                        <td>{{ trans('front.harsh_acceleration_score') }}</td>
                    </tr>
                    <tr>
                        <td style="width: 150px;">BC</td>
                        <td>{{ trans('front.harsh_braking_count') }}</td>
                    </tr>
                    <tr>
                        <td style="width: 150px;">BS = BC / D * 100</td>
                        <td>{{  trans('front.harsh_braking_score') }}</td>
                    </tr>
                    <tr>
                        <td style="width: 150px;">TC</td>
                        <td>{{ trans('front.harsh_turning_count') }}</td>
                    </tr>
                    <tr>
                        <td style="width: 150px;">TS = TC / D * 100</td>
                        <td>{{  trans('front.harsh_turning_score') }}</td>
                    </tr>
                    <tr>
                        <td style="width: 150px;">R = 100 - (OS + AS + BS + TS)</td>
                        <td>{{ trans('front.driving_score') }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@stop