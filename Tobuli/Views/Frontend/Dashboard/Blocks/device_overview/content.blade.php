<div class="row">
    @foreach($statuses as $status)
        <div class="col-xs-6 col-sm-4 col-md-2">
            <a class="stat-box" style="background-color: {{ $status['color'] }}" href="{{ $status['url'] }}" target="_blank">
                <div class="title">{{ $status['label'] }}</div>
                <div class="count">{{ $status['data'] }}</div>
                <div class="link">{{ trans('global.view_details') }}</div>
            </a>
        </div>
    @endforeach
</div>

<div class="row">
    @if(Auth::user()->perm('events', 'view'))
        <div class="col-sm-6">
            @include("Frontend.Dashboard.Blocks.device_overview.events")
        </div>
    @endif

    <div class="col-sm-6">
        @include("Frontend.Dashboard.Blocks.device_overview.graph")
    </div>
</div>

<script type='text/javascript'>
    if ( typeof _static_device_overview === "undefined") {
        var _static_device_overview = true;
    }

    if (_static_device_overview && $('#dashboard').is(':visible')) {
        _static_device_overview = false;
        setTimeout(function () {
            _static_device_overview = true;
            app.dashboard.loadBlockContent('device_overview', true);
        }, 10000);
    }
</script>
