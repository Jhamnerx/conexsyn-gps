<div class="table_error"></div>
<div class="table-responsive">
    <table class="table table-list" data-toggle="multiCheckbox">
        <thead>
        <tr>
            {!! tableHeaderCheckall(['destroy' => trans('admin.delete_selected')]) !!}
            {!! tableHeaderSort($items->sorting, 'email', 'validation.attributes.email') !!}
            {!! tableHeader('validation.attributes.user') !!}
            {!! tableHeader('validation.attributes.readonly') !!}
            {!! tableHeader('admin.actions', 'style="text-align: right;"') !!}
        </tr>
        </thead>

        <tbody>
        @php /** @var \Tobuli\Entities\UserSecondaryCredentials $item */ @endphp
        @forelse ($items->getCollection() as $item)
            <tr>
                <td>
                    <div class="checkbox">
                        <input type="checkbox" value="{!! $item->id !!}">
                        <label></label>
                    </div>
                </td>
                <td>
                    {{ $item->email }}
                </td>
                <td>
                    {{ $item->user->email }}
                </td>
                <td>
                    {{ $item->readonly ? trans('global.yes') : trans('global.no') }}
                </td>
                <td class="actions">
                    @php
                        $canEdit = auth()->user()->can('edit', $item->user);
                        $canRemove = auth()->user()->can('destroy', $item->user);
                    @endphp

                    @if($canEdit || $canRemove)
                        <div class="btn-group dropdown droparrow" data-position="fixed">
                            <i class="btn icon edit" data-toggle="dropdown" aria-haspopup="true"
                               aria-expanded="true"></i>
                            <ul class="dropdown-menu">
                                @if($canEdit)
                                    <li>
                                        <a href="javascript:" data-modal="secondary_credentials_edit"
                                           data-url="{{ route("admin.secondary_credentials.edit", [$item->id]) }}">
                                            {{ trans('global.edit') }}
                                        </a>
                                    </li>
                                @endif

                                @if($canRemove)
                                    <li>
                                        <a href="{{ route('admin.secondary_credentials.destroy', ['action' => 'proceed']) }}"
                                           class="js-confirm-link"
                                           data-confirm="{{ trans('admin.do_delete') }}"
                                           data-id="{{ $item->id }}"
                                           data-method="DELETE">
                                            {{ trans('global.delete') }}
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    @endif
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