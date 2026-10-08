{{-- Rail de navegación izquierdo (tema AirPatrol).
     Expandido: icono + texto · Colapsado: solo icono · Móvil: solo icono.
     Reemplaza las pestañas que assets/custom/js.js inyectaba en el sidebar. --}}

<script>
    try {
        if (localStorage.getItem('ap.sidenav') === 'collapsed') {
            document.body.classList.add('ap-nav-collapsed');
        }
    } catch (e) {}
</script>

<nav id="sidenav" class="sidenav" aria-label="{{ trans('front.objects') }}">
    <button type="button" class="sidenav-toggle" title="{{ trans('front.collapse') }}"
        onclick="document.body.classList.toggle('ap-nav-collapsed');try{localStorage.setItem('ap.sidenav',document.body.classList.contains('ap-nav-collapsed')?'collapsed':'expanded')}catch(e){}">
        <span class="sn-ico" aria-hidden="true">
            <svg class="sn-chev-l" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M11 17l-5-5 5-5M18 17l-5-5 5-5"/></svg>
            <svg class="sn-chev-r" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M13 17l5-5-5-5M6 17l5-5-5-5"/></svg>
        </span>
        <span class="sn-text">{{ ucfirst(trans('front.collapse')) }}</span>
    </button>

    <ul class="sidenav-menu">
        <li class="active">
            <a href="#objects_tab" data-toggle="tab" title="{{ trans('front.objects') }}">
                <span class="sn-ico" aria-hidden="true">
                    {{-- camión --}}
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10 17h4V6a1 1 0 0 0-1-1H3a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h2"/><path d="M14 9h3.6a1 1 0 0 1 .9.55L20.8 14l1.2.4a1 1 0 0 1 .7.95V16a1 1 0 0 1-1 1h-2"/><circle cx="7.5" cy="17.5" r="1.8"/><circle cx="17.5" cy="17.5" r="1.8"/></svg>
                </span>
                <span class="sn-text">{!! trans('front.objects') !!}</span>
            </a>
        </li>

        @if (Auth::user()->perm('events', 'view'))
            <li>
                <a href="#events_tab" data-toggle="tab" title="{{ trans('front.events') }}">
                    <span class="sn-ico" aria-hidden="true">
                        {{-- actividad / pulso --}}
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 8L9 4l-3 8H2"/></svg>
                    </span>
                    <span class="sn-text">{!! trans('front.events') !!}</span>
                </a>
            </li>
        @endif

        <li>
            <a href="#history_tab" data-toggle="tab" title="{{ trans('front.history') }}">
                <span class="sn-ico" aria-hidden="true">
                    {{-- reloj con flecha (historial) --}}
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 2.6-6.4"/><path d="M3 4v4h4"/><path d="M12 7v5l3.2 1.9"/></svg>
                </span>
                <span class="sn-text">{!! trans('front.history') !!}</span>
            </a>
        </li>

        <li class="sn-sep" role="separator"></li>

        @if (Auth::User()->perm('alerts', 'view'))
            <li>
                <a href="javascript:" data-url="{!! route('alerts.index_modal') !!}" data-modal="alerts" role="button" title="{{ trans('front.alerts') }}">
                    <span class="sn-ico" aria-hidden="true">
                        {{-- campana --}}
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>
                    </span>
                    <span class="sn-text">{!! trans('front.alerts') !!}</span>
                </a>
            </li>
        @endif

        @if (Auth::User()->perm('geofences', 'view'))
            <li>
                <a href="#geofencing_tab" data-toggle="tab" onclick="app.geofences.list();" title="{{ trans('front.geofencing') }}">
                    <span class="sn-ico" aria-hidden="true">
                        {{-- polígono de geocerca con punto --}}
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l8 5.5-3 9.5H7L4 8.5z"/><circle cx="12" cy="11" r="2.2"/></svg>
                    </span>
                    <span class="sn-text">{!! trans('front.geofencing') !!}</span>
                </a>
            </li>
        @endif

        @if (Auth::User()->perm('reports', 'view'))
            <li>
                <a href="javascript:" data-url="{!! route('reports.create') !!}" data-modal="reports_create" role="button" title="{{ trans('front.reports') }}">
                    <span class="sn-ico" aria-hidden="true">
                        {{-- documento con barras --}}
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 17v-3M12 17v-5M16 17v-2"/></svg>
                    </span>
                    <span class="sn-text">{!! trans('front.reports') !!}</span>
                </a>
            </li>
        @endif

        @if (Auth::User()->perm('send_command', 'view'))
            <li>
                <a href="javascript:" data-url="{{ route('send_command.create') }}" data-modal="send_command" role="button" title="{{ trans('front.command') }}">
                    <span class="sn-ico" aria-hidden="true">
                        {{-- avión de papel (enviar comando) --}}
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4z"/></svg>
                    </span>
                    <span class="sn-text">{!! trans('front.command') !!}</span>
                </a>
            </li>
        @endif

        @if (Auth::User()->perm('sharing', 'view'))
            <li>
                <a href="javascript:" data-url="{{ route('sharing.index') }}" data-modal="sharing" role="button" title="{{ trans('front.sharing') }}">
                    <span class="sn-ico" aria-hidden="true">
                        {{-- compartir (nodos) --}}
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="2.6"/><circle cx="6" cy="12" r="2.6"/><circle cx="18" cy="19" r="2.6"/><path d="M8.4 10.7l7.2-4.2M8.4 13.3l7.2 4.2"/></svg>
                    </span>
                    <span class="sn-text">{!! trans('front.sharing') !!}</span>
                </a>
            </li>
        @endif

        @if (Auth::User()->perm('maintenance', 'view'))
            <li>
                <a href="{!! route('maintenance.index') !!}" target="_blank" role="button" title="{{ trans('front.maintenance') }}">
                    <span class="sn-ico" aria-hidden="true">
                        {{-- llave de mantenimiento --}}
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a4.5 4.5 0 0 0 5.9 5.9L17 15.8a2.1 2.1 0 0 1-3 0l-5.8-5.9a2.1 2.1 0 0 1 0-3l3.6-3.5a4.5 4.5 0 0 0 2.9 2.9z" transform="rotate(90 12 12)"/><path d="M4 20l5-5"/></svg>
                    </span>
                    <span class="sn-text">{!! trans('front.maintenance') !!}</span>
                </a>
            </li>
        @endif
    </ul>
</nav>
