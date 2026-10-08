<ul class="ap-kpi-list">
    @foreach($statuses as $status)
        <li class="ap-kpi ap-kpi--{{ $status['key'] ?? 'default' }}">
            <span class="ap-kpi-dot" aria-hidden="true"></span>
            <span class="ap-kpi-label">{{ $status['label'] }}</span>
            @if(empty($status['url']))
                <b class="ap-kpi-value">{{ $status['data'] }}</b>
            @else
                <a class="ap-kpi-value" href="{{ $status['url'] }}" target="_blank">{{ $status['data'] }}</a>
            @endif
        </li>
    @endforeach
</ul>
