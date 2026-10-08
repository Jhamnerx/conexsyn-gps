<div class="table_error"></div>
<div class="table-responsive">
    <table class="table table-list" data-toggle="multiCheckbox">
        <thead>
        <tr>
            {!! tableHeaderCheckall([
                'prompt_delete_url' => trans('admin.delete_selected'),
                'prompt_delete_all_url' => trans('admin.delete_all'),
            ]) !!}
            {!! tableHeader('validation.attributes.name') !!}
            {!! tableHeader('validation.attributes.type') !!}
            {!! tableHeader('validation.attributes.format') !!}
            {!! tableHeader('admin.size') !!}
            @if ($showUser)
                {!! tableHeader('global.user') !!}
            @endif
            {!! tableHeader('validation.attributes.send_to_email') !!}
            {!! tableHeader('global.is_send') !!}
            @if ($showFtp)
                {!! tableHeader('global.is_uploaded') !!}
                {!! tableHeader('global.title_danger') !!}
            @endif
            {!! tableHeader('admin.actions', 'style="text-align: right;"') !!}
        </tr>
        </thead>
        <tbody>

        @php
            $yes = trans('global.yes');
            $no = trans('global.no');
        @endphp

        @forelse ($logs as $log)
            <tr>
                <td>
                    <div class="checkbox">
                        <input type="checkbox" value="{!! $log->id !!}">
                        <label></label>
                    </div>
                </td>
                <td>
                    {{ $log->title }}
                </td>
                <td>
                    {{ $log->type_text }}
                </td>
                <td>
                    {{ $log->format_text }}
                </td>
                <td>
                    {{ formatBytes( $log->size ) }}
                </td>
                @if ($showUser)
                    <td>
                        {{ $log->user->email ?? '' }}
                    </td>
                @endif
                <td>
                    {{ $log->email }}
                </td>
                @if ($showFtp)
                    <td>
                        {{ $log->is_send ? $yes : $no }}
                    </td>
                    <td>
                        {{ $log->is_upload ? $yes : $no }}
                    </td>
                    <td>
                        {!! $log->error ? tooltipMark($log->error) : '' !!}
                    </td>
                @else
                    <td>
                        <span title="{{ $log->error }}">{{ $log->is_send ? $yes : $no }}</span>
                    </td>
                @endif
                <td class="actions">
                    <div class="btn-group dropdown droparrow" data-position="fixed">
                        <i class="btn icon edit" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true"></i>
                        <ul class="dropdown-menu">
                            <li><a href="{{ route('admin.report_logs.edit', $log->id) }}">{{ trans('admin.download') }}</a></li>
                            <li>
                                <a href="{{ route('admin.report_logs.destroy') }}"
                                   class="js-confirm-link"
                                   data-confirm="{{ trans('admin.do_delete') }}"
                                   data-id="{{ $log->id }}"
                                   data-method="DELETE">
                                    {{ trans('global.delete') }}
                                </a>
                            </li>
                        </ul>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ $showFtp ? 11 : 9 }}">
                    {{ trans('admin.no_data') }}
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

@include("Admin.Layouts.partials.pagination", ['items' => $logs])