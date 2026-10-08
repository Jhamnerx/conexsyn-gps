<div class="table_error"></div>
<div class="table-responsive">
    <table class="table table-list" data-toggle="multiCheckbox">
        <thead>
        <tr>
            @php
                $multiActions = [];
                if (Auth::User()->perm('devices', 'remove')) {
                    $multiActions = array_merge($multiActions, ['do_destroy' => trans('admin.delete_selected')]);
                }
                if (Auth::User()->perm('devices', 'edit')) {
                    $multiActions = array_merge($multiActions, [
                        'assign' => trans('admin.assign_selected'),
                        'set_active' => trans('admin.activate_selected'),
                        'set_inactive' => trans('admin.inactivate_selected')
                    ]);
                }
            @endphp

            @if( $multiActions )
                {!! tableHeaderCheckall($multiActions) !!}
            @endif
            {!! tableHeader('validation.attributes.active') !!}
            {!! tableHeaderSort($items->sorting, 'devices.name', 'validation.attributes.name') !!}
            {!! tableHeaderSort($items->sorting, 'devices.imei', 'validation.attributes.imei') !!}
            {!! tableHeader('global.online', 'style="text-align:center;"') !!}
            {!! tableHeaderSort($items->sorting, 'traccar_devices.server_time', 'admin.last_connection') !!}
            @if (Auth::user()->can('view', new \Tobuli\Entities\Device(), 'expiration_date'))
                    {!! tableHeaderSort($items->sorting, 'expiration_date', 'validation.attributes.expiration_date') !!}
            @endif
            {!! tableHeader('validation.attributes.user') !!}
            {!! tableHeader('admin.actions', 'style="text-align: right;"') !!}
        </tr>
        </thead>

        <tbody>
        @if (count($collection = $items->getCollection()))
            @foreach ($collection as $item)
                <tr>
                    @if( $multiActions )
                        <td>
                            <div class="checkbox">
                                <input type="checkbox" value="{!! $item->id !!}">
                                <label></label>
                            </div>
                        </td>
                    @endif
                    <td>
                        <span class="label label-sm label-{!! $item->active ? 'success' : 'danger' !!}">
                            {!! trans('validation.attributes.active') !!}
                        </span>
                    </td>
                    <td>
                        {{ $item->name }}
                    </td>
                    <td>
                        {{ $item->imei }}
                    </td>
                    <td style="text-align: center">
                        <span
                                class="device-status"
                                style="background-color: {{ $item->getStatusColor() }}"
                                data-toggle="tooltip"
                                title="{{ trans("global.{$item->getStatus()}") }}">
                        </span>
                    </td>
                    <td>
                        {{ $item->server_time ? Formatter::time()->human($item->server_time) : trans('front.not_connected') }}
                    </td>
                    @if (Auth::user()->can('view', $item, 'expiration_date'))
                        <td>
                            {{ $item->hasExpireDate() ? Formatter::time()->human($item->expiration_date) : trans('front.unlimited') }}
                        </td>
                    @endif
                    <td class="user-list" title="{{ $item->users_list }}">
                        {{ $item->users_list }}
                    </td>
                    <td class="actions">
                        <div class="btn-group dropdown droparrow" data-position="fixed">
                            <i class="btn icon edit" data-toggle="dropdown" aria-haspopup="true"
                               aria-expanded="true"></i>
                            <ul class="dropdown-menu">
                                @if( Auth::User()->perm('devices', 'edit') )
                                    <li>
                                        <a href="javascript:" data-modal="devices_edit"
                                           data-url="{{ route("devices.edit", [$item->id, 1]) }}">
                                            {{ trans('global.edit') }}
                                        </a>
                                    </li>
                                @endif
                                @if( Auth::User()->perm('devices', 'view') )
                                    <li>
                                        <a href="{{ route('devices.follow_map', [$item->id]) }}" onClick="dialogWindow(event, '{{$item->name}}');" >
                                            {{ trans('front.follow') }}
                                        </a>
                                    </li>
                                @endif
                                @if(Auth::User()->perm('devices', 'view'))
                                    <li>
                                        <a href="javascript:"
                                           data-modal="device_positions_backups"
                                           data-url="{{ route('admin.objects.positions_backups.index', $item->id) }}">
                                            {{ trans('front.positions_backups') }}
                                        </a>
                                    </li>
                                @endif
                                @if( Auth::User()->perm('devices', 'edit') && $item->app_uuid )
                                    <li>
                                        <a href="{{ route("devices.do_reset_app_uuid", $item->id) }}">
                                            {{ trans('front.reset_app_uuid') }}
                                        </a>
                                    </li>
                                @endif
                                @if( Auth::User()->perm('devices', 'remove') )
                                    <li>
                                        <a href="javascript:" data-modal="devices_delete"
                                           data-url="{{ route("devices.do_destroy", ['id' => $item->id]) }}">
                                            {{ trans('global.delete') }}
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </td>
                </tr>
            @endforeach
        @else
            <tr class="">
                <td class="no-data" colspan="7">
                    {!! trans('admin.no_data') !!}
                </td>
            </tr>
        @endif
        </tbody>
    </table>
</div>

@include('admin::Layouts.partials.pagination', ['limitChoice' => 1])