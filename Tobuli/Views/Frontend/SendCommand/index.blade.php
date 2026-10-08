<div id="table_sent_commands">
    <input type="hidden" name="sorting[sort_by]" value="{{ $items->sorting['sort_by'] }}" data-filter>
    <input type="hidden" name="sorting[sort]" value="{{ $items->sorting['sort'] }}" data-filter>

    <div class="row">
        <div class="col-md-4">
            <div class="form-group search">
                {!! Form::text(
                        'search_phrase',
                        null,
                        ['class' => 'form-control', 'placeholder' => trans('admin.search_it'), 'data-filter' => 'true']
                    ) !!}
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                {!! Form::select('connection[]', $connections, null, [
                        'class'         => 'form-control',
                        'multiple'      => 'multiple',
                        'title'         => trans('front.connection'),
                        'data-filter'   => 'true'
                    ]) !!}
            </div>
        </div>
    </div>

    <div data-table>
        @include('Frontend.SendCommand.table')
    </div>
</div>

<script>
    tables.set_config('table_sent_commands', {
        url: '{!! route('send_commands.logs.table') !!}',
    });
</script>