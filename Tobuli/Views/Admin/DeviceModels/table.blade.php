<div class="table_error"></div>
<div class="table-responsive">
    <table class="table table-list" data-toggle="multiCheckbox">
        <thead>
        <tr>
            {!! tableHeader('validation.attributes.active') !!}
            {!! tableHeaderSort($items->sorting, 'title') !!}
            {!! tableHeaderSort($items->sorting, 'model') !!}
            {!! tableHeaderSort($items->sorting, 'protocol') !!}
            {!! tableHeader('admin.actions', 'style="text-align: right;"') !!}
        </tr>
        </thead>

        <tbody>
        @php /** @var \Tobuli\Entities\DeviceModel $item */ @endphp
        @forelse ($items->getCollection() as $item)
            <tr>
                <td>
                    {{ $item->active ? trans('global.yes') : trans('global.no') }}
                </td>
                <td>
                    {{ $item->title }}
                </td>
                <td>
                    {{ $item->model }}
                </td>
                <td>
                    {{ $item->protocol }}
                </td>
                <td class="actions">
                    <div class="btn-group dropdown droparrow" data-position="fixed">
                        <i class="btn icon edit" data-toggle="dropdown" aria-haspopup="true"
                           aria-expanded="true"></i>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="javascript:" data-modal="device_models_edit"
                                   data-url="{{ route("admin.device_models.edit", [$item->id]) }}">
                                    {{ trans('global.edit') }}
                                </a>
                            </li>
                        </ul>
                    </div>
                </td>
            </tr>
        @empty
            <tr class="">
                <td class="no-data" colspan="5">
                    {!! trans('admin.no_data') !!}
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

@include("admin::Layouts.partials.pagination")