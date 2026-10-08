<div class="table-responsive">
    <table class="table table-list">
        <thead>
        <tr>
            {!! tableHeader('validation.attributes.title') !!}
            {!! tableHeader('validation.attributes.device_id') !!}
            <th></th>
        </tr>
        </thead>
        <tbody>
        @forelse ($items as $item)
            <tr>
                <td>
                    {{ $item->title }}
                </td>
                <td>
                    {{ $item->device ? $item->device->getDisplayName() : '' }}
                </td>
                <td class="actions">
                    @if (Auth::user()->can('edit', $item))
                        <a href="javascript:"
                           class="btn icon edit"
                           data-url="{!! route('task_sets.edit', $item->id) !!}"
                           data-modal="task_sets_edit"></a>
                    @endif

                    @if (Auth::user()->can('remove', $item))
                        <a href="{{ route('task_sets.destroy', ['action' => 'proceed']) }}"
                           class="js-confirm-link btn icon delete"
                           data-confirm="{{ trans('admin.do_delete') }}"
                           data-id="{{ $item->id }}"
                           data-method="DELETE"></a>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td class="no-data" colspan="2">{!! trans('front.no_data') !!}</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="nav-pagination">
    {!! $items->setPath(route('task_sets.table'))->render() !!}
</div>
