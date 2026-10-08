<table class="table table-condensed">
    <tr>
        {!! tableHeader('validation.attributes.processed') !!}
        {!! tableHeader('validation.attributes.skipped') !!}
        {!! tableHeader('global.total') !!}
        {!! tableHeader('front.attempts') !!}
        {!! tableHeader('front.status') !!}
        {!! tableHeader('validation.attributes.created_at') !!}
        {!! tableHeader('validation.attributes.reserved_at') !!}
        {!! tableHeader('validation.attributes.completed_at') !!}
        {!! tableHeader('validation.attributes.failed_at') !!}
    </tr>
    <tbody>
    @php /** @var \Tobuli\Entities\BackupProcess $item */ @endphp
    @forelse ($items as $item)
        <tr>
            <td>{{ $item->processed }}</td>
            <td>{{ $item->skipped }}</td>
            <td>{{ $item->total }}</td>
            <td>{{ $item->attempt }}</td>
            <td>{{ $item->getTranslatedStatus() }}</td>
            <td>{{ Formatter::time()->human($item->created_at) }}</td>
            <td>{{ Formatter::time()->human($item->reserved_at) }}</td>
            <td>{{ Formatter::time()->human($item->completed_at) }}</td>
            <td>{{ Formatter::time()->human($item->failed_at) }}</td>
        </tr>
    @empty
        <tr>
            <td class="no-data" colspan="8">
                {{ trans('admin.no_data') }}
            </td>
        </tr>
    @endforelse
    </tbody>
</table>