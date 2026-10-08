<table class="table">
    <thead>
        <tr>
            <th>{{ trans('validation.attributes.project_id') }}</th>
            <th>{{ trans('global.success') }}</th>
            <th>{{ trans('global.failed') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse($stats as $projectId => $stat)
            <tr>
                <td>{!! $projectId ?: '<i>In-built</i>' !!}</td>
                <td>{{ $stat['success'] }}</td>
                <td>{{ $stat['failed'] }}</td>
            </tr>

            @foreach($stat['errors'] as $e)
                <td colspan="3" class="alert-danger">{{ $e }}</td>
            @endforeach
        @empty
            <tr>
                <td colspan="3" class="alert-danger">
                    {{ $error ?? trans('front.unexpected_error') }}
                </td>
            </tr>
        @endforelse
    </tbody>
</table>