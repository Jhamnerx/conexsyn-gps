<div class="modal-dialog">
    <div class="modal-content" id="table_icons">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><span>×</span></button>
            <h4 class="modal-title">
                <i class="icon search"></i> {{ trans('front.icon') }}
            </h4>
        </div>
        <div class="modal-body">
            @php
                $urlTable = $url . '/table';
                $firstType = array_key_first($types);
                /** @var \Tobuli\Entities\DeviceIcon $currentIcon */
            @endphp

            {!! Form::hidden('icon_id', $currentIcon->id ?? 0) !!}

            <ul class="nav nav-tabs nav-default" role="tablist">
                @foreach($types as $type => $title)
                    <li @if($type === $firstType) id="activatible-tab" @endif>
                        <a href="#icons-list" role="tab" data-toggle="tab" data-url="{{ $urlTable . "/$type" }}">
                            {!! $title !!}
                        </a>
                    </li>
                @endforeach

                @if($useNothing)
                    <li class="active">
                        <div>
                            <div class="checkbox">
                                {!! Form::label('use_nothing', trans('global.use_nothing').':') !!}

                            </div>
                        </div>
                    </li>
                @endif
            </ul>

            <div class="tab-content">
                <div id="icons-list" class="tab-pane active"></div>
            </div>
        </div>
        <div class="modal-footer">
            <div class="buttons">
                <button type="button" class="btn btn-action" id="apply-icon">{!!trans('global.apply')!!}</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">{!!trans('global.cancel')!!}</button>

                @if($defaultIcon ?? null)
                    {!! Form::radio('icon_id', $defaultIcon->id, !$currentIcon || $currentIcon->id == $defaultIcon->id, [
                        'id' => 'default_icon_id',
                        'data-path' => asset($defaultIcon->path),
                        'class' => 'hidden'
                    ]) !!}
                    <button type="button" class="btn btn-danger" id="use-default">{!!trans('global.use_default')!!}</button>
                @endif

                @if($nothingIcon ?? null)
                    {!! Form::radio('icon_id', $nothingIcon->id, !$currentIcon || $currentIcon->id == $nothingIcon->id, [
                        'id' => 'nothing_icon_id',
                        'data-path' => asset($nothingIcon->path),
                        'class' => 'hidden'
                    ]) !!}
                    <button type="button" class="btn btn-danger" id="use-nothing">{!!trans('global.use_default')!!}</button>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
    tables.set_config('icons-list', {
        url:'{!! $urlTable !!}'
    });

    var $container = $("#{{ $parentId }}"),
        $input = $('input', $container),
        $image = $('img', $container);

    $(document).ready( function() {

        $('#activatible-tab a').trigger('click');

        $('#icons-modal #use-default').on('click', function() {
            $('#default_icon_id').prop("checked", true);
            $('#icons-modal #apply-icon').trigger('click');
        });

        $('#icons-modal #use-nothing').on('click', function() {
            $('#nothing_icon_id').prop("checked", true);
            $('#icons-modal #apply-icon').trigger('click');
        });

        $('#icons-modal #apply-icon').on('click', function() {
            var $icon = $('#table_icons input[name="icon_id"]:checked');

            $input.val($icon.val());
            $input.attr('data-path', $icon.data('path'));
            $image.attr('src', $icon.data('path'));

            $('#icons-modal').modal('hide');
        });
    });
</script>