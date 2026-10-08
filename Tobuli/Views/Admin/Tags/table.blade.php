<div class="table_error"></div>

<div class="table-responsive">
    <table class="table table-list" data-toggle="multiCheckbox">
        <thead>
        <tr>
            {!! tableHeaderCheckall(['delete_url' => trans('admin.delete_selected')]) !!}

            @if(auth()->user()->isAdmin())
                {!! tableHeaderSort($items->sorting, 'is_common') !!}
                {!! tableHeaderSort($items->sorting, 'user_id', trans('validation.attributes.user')) !!}
            @endif

            {!! tableHeaderSort($items->sorting, 'name') !!}
            {!! tableHeaderSort($items->sorting, 'color') !!}
            {!! tableHeader('admin.actions', 'style="text-align: right;"') !!}
        </tr>
        </thead>
        <tbody>

        @php
            /** @var \Tobuli\Entities\Tag $item */
        @endphp

        @forelse ($items->getCollection() as $item)
            <tr>
                <td>
                    <div class="checkbox">
                        <input type="checkbox" value="{!! $item->id !!}">
                        <label></label>
                    </div>
                </td>

                @if(auth()->user()->isAdmin())
                    <td>{{ $item->is_common ? trans('global.yes') : trans('global.no') }}</td>
                    <td>{{ $item->user->email ?? '' }}</td>
                @endif

                <td>{{ $item->name }}</td>
                <td>{{ $colorOptions[$item->color] ?? $item->color }}</td>
                <td class="actions">

                    @if (auth()->user()->can('edit', $item))
                    <div class="btn-group dropdown droparrow" data-position="fixed">
                        <i class="btn icon edit" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true"></i>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="javascript:" data-modal="tags_edit" data-url="{{ route('admin.tags.edit', $item->id) }}">
                                    {{ trans('global.edit') }}
                                </a>
                            </li>
                        </ul>
                    </div>
                    @endif
                </td>
            </tr>
        @empty
            <tr class="">
                <td class="no-data" colspan="6">
                    {!! trans('admin.no_data') !!}
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

@include("Admin.Layouts.partials.pagination")