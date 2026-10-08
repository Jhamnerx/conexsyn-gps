<div class="table-responsive">
    <table class="table table-list" data-toggle="multiCheckbox">
        <thead>
        <tr>
            @if (Auth::User()->perm('routes', 'remove'))
                {!! tableHeaderCheckall([
                    'destroy' => trans('admin.delete_selected'),
                    'set_active' => trans('front.set_visible'),
                    'set_inactive' => trans('front.set_invisible'),
                ]) !!}
            @endif
            {!! tableHeaderSort($items->sorting, 'active', trans('validation.attributes.visible')) !!}
            {!! tableHeaderSort($items->sorting, 'name') !!}
            {!! tableHeader('validation.attributes.color', 'style="width: 1px;"') !!}
            <th></th>
        </tr>
        </thead>
        <tbody>
        @php /** @var \Tobuli\Entities\Route $item */ @endphp
        @forelse ($items as $item)
            <tr>
                @if( Auth::User()->perm('routes', 'remove') )
                    <td>
                        <div class="checkbox">
                            <input type="checkbox" value="{!! $item->id !!}">
                            <label></label>
                        </div>
                    </td>
                @endif
                <td>
                    <span class="route-status-toggle label label-sm label-{!! $item->active ? 'success' : 'danger' !!}"
                          role="button"
                    >
                        {!! trans('validation.attributes.visible') !!}
                    </span>
                </td>
                <td>
                    {{ $item->name }}
                </td>
                <td>
                    <span class="label" style="background-color: {!! $item->color !!}">&emsp;</span>
                </td>
                <td class="actions">
                    <div class="btn-group dropdown droparrow" data-position="fixed">
                        <i class="btn icon edit" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true"></i>
                        <ul class="dropdown-menu">
                            <li>
                                <a href='javascript:'
                                   data-dismiss="modal"
                                   onclick="app.routes.edit({{ $item->id }});">
                                    {{ trans('global.edit') }}
                                </a>
                            </li>
                            @include('front::Layouts.partials.confirmed_delete.menu_item', ['route' => route('routes.destroy', $item->id)])
                        </ul>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td class="no-data" colspan="5">{!! trans('front.no_routes') !!}</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="nav-pagination">
    @if (count($items))
        {!! $items->setPath(route('routes.table'))->render() !!}
    @endif
</div>

<script>
    $(document).ready(function () {
        $('.route-status-toggle').on('click', function() {
            $.ajax({
                type: 'POST',
                dataType: 'json',
                data: {
                    id: $(this).data('id'),
                    active: $(this).data('value') ? 0 : 1,
                },
                url: '{{ route('routes.change_active') }}',
                success: function(res){
                    if (res.status == 1) {
                        tables.get('table_routes');
                    }
                }
            });
        })
    });
</script>