<div class="table-responsive">
    <table class="table table-list">
        <thead>
        <tr>
            {!! tableHeader(trans('global.user')) !!}
            {!! tableHeader(trans('front.subject')) !!}
            {!! tableHeaderSort($items->sorting, 'subject_type', trans('front.subject') . ' (' . trans('front.type') . ')') !!}
            {!! tableHeaderSort($items->sorting, 'description', trans('front.action')) !!}
            {!! tableHeaderSort($items->sorting, 'log_name', trans('front.last_value')) !!}
            {!! tableHeader(trans('front.count')) !!}
            {!! tableHeaderSort($items->sorting, 'created_at', trans('global.date')) !!}
            {!! tableHeaderSort($items->sorting, 'ip') !!}
            {!! tableHeader('admin.actions', 'style="text-align: right;"') !!}
        </tr>
        </thead>
        <tbody>
        @php /** @var \Tobuli\Entities\ModelChangeLog $item */ @endphp
        @forelse ($items as $item)
            <tr>
                <td>
                    {!! $item->getCauserName() !!}
                </td>
                <td>
                    {!! $item->getSubjectName() !!}
                </td>
                <td>
                    {!! $item->subject_type !!}
                </td>
                <td>
                    {!! $item->description !!}
                </td>
                <td>
                    {!! $item->log_name !!}
                </td>
                <td>
                    {!! $item->attributesCount() !!}
                </td>
                <td>
                    {!! Formatter::time()->human($item->created_at) !!}
                </td>
                <td>
                    {!! $item->ip !!}
                </td>
                <td class="actions">
                    <div class="btn-group dropdown droparrow" data-position="fixed">
                        <i class="btn icon edit" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true"></i>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="javascript:" data-modal="model_change_diffs_show"
                                   data-url="{{ route('admin.model_change_logs.show', [$item->id, 1]) }}">
                                    {{ trans('front.difference') }}
                                </a>
                            </li>

                            <li>
                                <a href="{{ route('admin.model_change_logs.index', ['search_subjects[]' => $item->subject_type . '-' . $item->subject_id]) }}">
                                    {{ trans('front.subject_logs') }}
                                </a>
                            </li>
                        </ul>
                    </div>
                </td>
            </tr>
        @empty
            <tr class="">
                <td class="no-data" colspan="7">
                    {!! trans('admin.no_data') !!}
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

@include('admin::Layouts.partials.pagination')