<div class="table-responsive">
    <table class="table table-list" data-toggle="multiCheckbox">
        <thead>
        <tr>
            @if (Auth::User()->perm('poi', 'remove'))
                {!! tableHeaderCheckall([
                    'destroy' => trans('admin.delete_selected'),
                    'set_active' => trans('front.set_visible'),
                    'set_inactive' => trans('front.set_invisible'),
                ]) !!}
            @endif
            {!! tableHeaderSort($items->sorting, 'active', trans('validation.attributes.visible')) !!}
            {!! tableHeaderSort($items->sorting, 'name') !!}
            <th></th>
        </tr>
        </thead>
        <tbody>
        @php /** @var \Tobuli\Entities\Poi $item */ @endphp
        @forelse ($items as $item)
            <tr>
                @if( Auth::User()->perm('poi', 'remove') )
                    <td>
                        <div class="checkbox">
                            <input type="checkbox" value="{!! $item->id !!}">
                            <label></label>
                        </div>
                    </td>
                @endif
                <td>
                    <span class="poi-status-toggle label label-sm label-{!! $item->active ? 'success' : 'danger' !!}"
                          role="button"
                    >
                        {!! trans('validation.attributes.visible') !!}
                    </span>
                </td>
                <td>
                    {{ $item->name }}
                </td>
                <td class="actions">
                    <div class="btn-group dropdown droparrow" data-position="fixed">
                        <i class="btn icon edit" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true"></i>
                        <ul class="dropdown-menu">
                            <li>
                                <a href='javascript:'
                                   data-dismiss="modal"
                                   onclick="app.pois.edit({{ $item->id }});">
                                    {{ trans('global.edit') }}
                                </a>
                            </li>
                            @include('front::Layouts.partials.confirmed_delete.menu_item', ['route' => route('pois.destroy', $item->id)])
                        </ul>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td class="no-data" colspan="5">{!! trans('front.no_pois') !!}</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="nav-pagination">
    @if (count($items))
        {!! $items->setPath(route('pois.table'))->render() !!}
    @endif
</div>

<script>
    $(document).ready(function () {
        $('.poi-status-toggle').on('click', function() {
            $.ajax({
                type: 'POST',
                dataType: 'json',
                data: {
                    id: $(this).data('id'),
                    active: $(this).data('value') ? 0 : 1,
                },
                url: '{{ route('pois.change_active') }}',
                success: function(res){
                    if (res.status == 1) {
                        tables.get('table_pois');
                    }
                }
            });
        })
    });
</script>