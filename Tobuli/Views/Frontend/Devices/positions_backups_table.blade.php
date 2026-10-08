<table class="table table-list">
    <tr>
        {!! tableHeader('front.folder') !!}
        {!! tableHeader('admin.actions') !!}
    </tr>
    <tbody>
    @php /** @var \Tobuli\Helpers\Backup\FileMeta $folder */ @endphp
    @forelse ($folders as $folder)
        @php
            $folderName = $folder->getName();
        @endphp
        <tr>
            <td>
                {{ $folderName }}
            </td>
            <td>
                {!! Form::open(['url' => route('admin.objects.positions_backups.download', $id), 'method' => 'POST', 'id' => $folderName, 'style' => 'display:inline']) !!}
                {!! Form::hidden('folder', $folderName) !!}
                {!! Form::submit(trans('admin.download'), ['class' => 'btn btn-sm']) !!}
                {!! Form::close() !!}

                <button class="btn btn-sm btn-primary uploaders" data-folder="{{ $folderName }}">
                    {{ trans('front.upload') }}
                </button>
            </td>
        </tr>
    @empty
        <tr>
            <td class="no-data" colspan="2">
                {{ trans('admin.no_data') }}
            </td>
        </tr>
    @endforelse
    </tbody>
</table>

<div class="nav-pagination">
    @if ($folders->total())
        {!! $folders->render() !!}
    @endif
</div>

<script>
    $(document).ready(function() {
        $('.uploaders').unbind().click(function () {
            $(this).prop('disabled', true);

            $.ajax({
                type: 'POST',
                url: '{{ route('admin.objects.positions_backups.upload', $id) }}',
                data: {
                    folder: $(this).data('folder')
                },
                success: function () {
                    toastr.success('{{ trans('front.upload_initiated') }}');
                }
            });
        });
    });
</script>