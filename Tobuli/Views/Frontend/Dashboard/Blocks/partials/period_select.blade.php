<p>{{ trans('front.time_period') }}</p>

@foreach([
    'day' => ucfirst(trans('front.day')),
    'week' => trans('front.week'),
    'month' => ucfirst(trans('front.month')),
] as $key => $name)
    @if(empty($excluded) || !in_array($key, $excluded))
        <div class="radio">
            {!! Form::radio("dashboard[blocks][$block][options][period]", $key, $period === $key) !!}
            {!! Form::label(null, $name) !!}
        </div>
    @endif
@endforeach