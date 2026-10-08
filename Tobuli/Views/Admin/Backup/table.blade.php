<div class="table-responsive">
    <table class="table table-list" data-toggle="multiCheckbox">
        <thead>
        <tr>
            {!! tableHeaderSort($items->sorting, 'name') !!}
            {!! tableHeaderSort($items->sorting, 'launcher') !!}
            {!! tableHeaderSort($items->sorting, 'date') !!}
            {!! tableHeader('front.progress') !!}
            {!! tableHeader('front.completed') !!}
            {!! tableHeader('validation.attributes.message') !!}
            {!! tableHeader('admin.actions', 'style="text-align: right;"') !!}
        </tr>
        </thead>
        <tbody>
        @php /** @var \Tobuli\Entities\Backup $item */ @endphp
        @forelse ($items->getCollection() as $item)
            <tr>
                <td>{{ $item->name }}</td>
                <td>{{ $item->launcher }}</td>
                <td>{{ Formatter::time()->human($item->created_at) }}</td>
                <td>
                    @php
                        $done = $item->progressDone();
                        $total = $item->progressTotal();
                        $percentage = $total ? (int)($done / $total * 100) : 100;
                    @endphp

                    <div class="progress">
                        <div class="progress-bar"
                             role="progressbar"
                             aria-valuenow="{{ $done }}"
                             aria-valuemin="0"
                             aria-valuemax="{{ $total }}"
                             style="width: {{ $percentage }}%;">
                            {{ $done }} / {{ $total }}
                        </div>
                    </div>
                </td>
                <td>{{ $item->isCompleted() ? trans('global.yes') : trans('global.no') }}</td>
                <td>{{ $item->message }}</td>
                <td class="actions">
                    <i class="btn icon ico-arrow-down"
                       type="button"
                       data-url="{{ route('admin.backup.processes', $item->id) }}"
                       data-toggle="collapse"
                       data-target="#backup-processes-{{ $item->id }}">
                    </i>
                </td>
            </tr>
            <tr class="row-table-inner">
                <td colspan="7" id="backup-processes-{{ $item->id }}" aria-expanded="false" class="collapse"></td>
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

@include('Admin.Layouts.partials.pagination')