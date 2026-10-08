<div data-table>
    <div class="icon-list">
        @foreach($items as $item)
            <div class="checkbox-inline">
                {!! Form::radio('icon_id', $item->id, null, ['data-path' => asset($item->path)]) !!}
                <label>
                    <img src="{!! asset($item->path) !!}" alt="ICON" data-path="{!! asset($item->path) !!}"/>
                </label>
            </div>
        @endforeach
    </div>

    @include('admin::Layouts.partials.pagination')
</div>