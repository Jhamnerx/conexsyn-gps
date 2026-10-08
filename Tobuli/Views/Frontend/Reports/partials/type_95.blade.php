@extends('Frontend.Reports.partials.layout')

@section('content')
    <div class="panel panel-default">
        @include('Frontend.Reports.partials.item_heading')

        <div class="panel-body no-padding">
            <table class="table table-hover">
                <thead>
                <tr>
                    @php $metaCount = count($report->metas()) @endphp

                    @foreach($report->metas() as $key => $meta)
                        <th>{{ $meta['title'] }}</th>
                    @endforeach

                    <th>{{ trans('front.start') }}</th>
                    <th>{{ trans('front.end') }}</th>
                    <th>{{ trans('front.duration') }}</th>
                    <th>{{ trans('front.stop_position') }}</th>
                    <th>{{ trans('front.coordinates') }}</th>
                </tr>
                </thead>

                <tbody>
                @foreach ($report->getItems() as $item)
                    @if (isset($item['error']))
                        <tr>
                            @foreach($item['meta'] as $key => $meta)
                                <td>{{ $meta['value'] }}</td>
                            @endforeach

                            <td colspan="{{ $metaCount + 5 }}">{{ $item['error'] }}</td>
                        </tr>

                        @continue;
                    @endif

                    @foreach ($item['table'] as $row)
                        <tr>
                            @foreach($item['meta'] as $key => $meta)
                                <td>{{ $meta['value'] }}</td>
                            @endforeach

                            <td>{{ $row['start_at'] }}</td>
                            <td>{{ $row['end_at'] }}</td>
                            <td>{{ $row['duration'] }}</td>
                            <td>{!! $row['location'] !!}</td>
                            <td>{!! $row['coordinates'] !!}</td>
                        </tr>
                    @endforeach
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@stop
