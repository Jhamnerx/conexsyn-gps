@if ($plans->isEmpty())
    <div class="row">
        <div class="col-sm-12">
            <div class="alert alert-danger">
                {{ trans('front.device_plans_no_data') }}
            </div>
        </div>
    </div>
@else

    <div class="row">
        <ul class="nav nav-tabs nav-background nav-justified @if ($plans->count() < 2) hidden @endif" role="tablist">
        @foreach($plans as $durationType => $typePlans)
            <li @if ($loop->first) class="active" @endif>
                <a href="#duration_type_{{ $durationType }}" role="tab" data-toggle="tab">
                    {{ trans('front.duration_type_' . $durationType) }}
                </a>
            </li>
        @endforeach
        </ul>
    </div>

    <br>

    <div class="tab-content">
        @foreach($plans as $durationType => $typePlans)
        <div id="duration_type_{{ $durationType }}" class="tab-pane @if ($loop->first) active @endif" role="tabpanel">
            @foreach ($typePlans->chunk(3) as $chunk)
                <div class="row">
                @foreach ($chunk as $plan)
                    <div class="col-sm-4">
                        @include('Frontend.DevicePlans.plan')
                    </div>
                @endforeach
                </div>
            @endforeach
        </div>
        @endforeach
    </div>
@endif
