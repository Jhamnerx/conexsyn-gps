<div class="table_error"></div>
<div class="table-responsive">
    <table class="table table-list" data-toggle="multiCheckbox">
        <thead>
        <tr>
            {!! tableHeader('validation.attributes.default') !!}
            {!! tableHeaderSort($items->sorting, 'title') !!}
            {!! tableHeaderSort($items->sorting, 'project_id') !!}
            {!! tableHeaderSort($items->sorting, 'tokens_count') !!}
            {!! tableHeader('admin.actions', 'style="text-align: right;"') !!}
        </tr>
        </thead>

        <tbody>
        @php /** @var \Tobuli\Entities\FcmConfiguration $item */ @endphp
        @forelse ($items->getCollection() as $item)
            <tr>
                <td>
                    {{ $item->is_default ? trans('global.yes') : trans('global.no') }}
                </td>
                <td>
                    {{ $item->title }}
                </td>
                <td>
                    {{ $item->project_id }}
                </td>
                <td>
                    {{ $item->tokens_count }}
                </td>
                <td class="actions">
                    <div class="btn-group dropdown droparrow" data-position="fixed">
                        <i class="btn icon edit" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true"></i>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="javascript:"
                                   data-modal="fcm_configurations_edit"
                                   data-url="{{ route('admin.fcm_configurations.edit', [$item->id]) }}"
                                >
                                    {{ trans('global.edit') }}
                                </a>
                            </li>

                            @if(!$item->is_default)
                                <li>
                                    <a href="{{ route('admin.fcm_configurations.update.default', ['id' => $item->id, 'action' => 'proceed']) }}"
                                       class="js-confirm-link"
                                       data-confirm="{{ trans('front.set_as_default') }}?"
                                       data-method="DELETE"
                                    >
                                        {{ trans('front.set_as_default') }}
                                    </a>
                                </li>
                            @endif

                            <li>
                                <a href="{{ route('admin.fcm_configurations.destroy', ['action' => 'proceed']) }}"
                                   class="js-confirm-link"
                                   data-confirm="{{ trans('admin.do_delete') }}"
                                   data-id="{{ $item->id }}"
                                   data-method="DELETE">
                                    {{ trans('global.delete') }}
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