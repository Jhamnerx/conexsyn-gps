<div class="mappings" data-sensor-input="mapping" data-disablable="#mapping;disable">
    {!! Form::hidden('mappings_fake') !!}
    <div style="display: block; height: 400px;overflow-y: scroll; border: 1px solid #dddddd; margin-bottom: 20px;">
        <table class="table">
            <thead>
            <th style="font-weight: normal">{{ trans('validation.attributes.tag_value') }}</th>
            <th style="font-weight: normal">{{ trans('front.mapped_value') }}</th>
            <th></th>
            <th></th>
            </thead>
            <tbody></tbody>
        </table>
    </div>
    <div class="form-group">
        <div class="row">
            <div class="col-xs-10">
                <div class="form-group">
                    {!! Form::label('vt',trans('validation.attributes.tag_value')) !!}
                    <div class="row">
                        <div class="col-xs-5">
                            {!! Form::select('vt', \Tobuli\Sensors\Extractions\Mapping::getValueTypes(), null, ['class' => 'form-control', 'id' => 'vt_set']) !!}
                        </div>
                        <div class="col-xs-7">
                            <div class="input-group">
                                {!! Form::text('v', null, ['class' => 'form-control', 'placeholder' => trans('global.from'), 'data-disablable' => '#vt_set;hide-disable;2']) !!}
                                <span class="input-group-btn"></span>
                                {!! Form::text('t', null, ['class' => 'form-control', 'placeholder' => trans('global.to'), 'data-disablable' => '#vt_set;hide-disable;2']) !!}
                                <span class="input-group-btn"></span>
                                {!! Form::text('v', null, ['class' => 'form-control', 'placeholder' => trans('validation.attributes.value'), 'data-disablable' => '#vt_set;hide-disable;1']) !!}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    {!! Form::label('mt',trans('front.mapped_value')) !!}
                    <div class="row">
                        <div class="col-xs-5">
                            {!! Form::select('mt', \Tobuli\Sensors\Extractions\Mapping::getMapTypes(), null, ['class' => 'form-control', 'id' => 'mt_set']) !!}
                        </div>
                        <div class="col-xs-7">
                            <div class="input-group">
                                {!! Form::text('mv', null, ['class' => 'form-control', 'placeholder' => '', 'data-disablable' => '#mt_set;hide-disable;1']) !!}
                                <span class="input-group-btn"></span>
                                {!! Form::text('mv', '[value]', ['class' => 'form-control', 'placeholder' => '[value]', 'data-disablable' => '#mt_set;hide-disable;2']) !!}

                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xs-2">
                <div class="form-group">
                    {!! Form::label(null,'&nbsp;') !!}
                    <a href="javascript:" class="btn btn-action btn-block add_mapping" type="button">
                        <i class="icon add" title="{{ trans('global.add') }}"></i>
                    </a>
                </div>
            </div>
            <div class="col-xs-2">
                <div class="form-group" >
                    {!! Form::label(null,'&nbsp;') !!}
                    @php $uid = uniqid() @endphp
                    <div id="container_{{ $uid }}">
                        {!! Form::hidden('icon_id', null) !!}
                        <a href="javascript:"
                           data-url="{!! route('icon.sensor.index') !!}"
                           data-modal="icons-modal"
                           data-parent-id="container_{{ $uid }}"
                           data-current-value=""
                        >
                            <img src="" alt="ICON" onError="this.src = '{!! asset("assets/images/no-icon.png") !!}';"/>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    try {
        let mapIndex;
    } catch (e){};

    mapIndex = 0;

    function mappingRow(mapping) {
        var $row = $('<tr></tr>'),
            $value = $('<td></td>'),
            $mapped = $('<td></td>'),
            $icon = $('<td></td>'),
            $button = $('<td></td>'),
            index = mapIndex++;

        $button.html('<button type="button" class="remove_mapping close"><span aria-hidden="true">×</span></button>');

        switch (mapping.vt) {
            case "1":
                $value.html(mapping.v);
                $button.append($('<input type="hidden" name="mappings['+index+'][v]" value="' + mapping.v + '">'));
                break;
            case "2":
                $value.html(mapping.v + ' .. ' + mapping.t);
                $button.append($('<input type="hidden" name="mappings['+index+'][v]" value="' + mapping.v + '">'));
                $button.append($('<input type="hidden" name="mappings['+index+'][t]" value="' + mapping.t + '">'));
                break;
            case "3":
                $value.html('-');

        }
        $button.append($('<input type="hidden" name="mappings['+index+'][vt]" value="' + mapping.vt + '">'));

        switch (mapping.mt) {
            case "1":
                $mapped.html(mapping.mv);
                $button.append($('<input type="hidden" name="mappings['+index+'][mv]" value="' + mapping.mv + '">'));
                break;
            case "2":
                $mapped.html(mapping.mv);
                $button.append($('<input type="hidden" name="mappings['+index+'][mv]" value="' + mapping.mv + '">'));
                break;
            case "3":
                $mapped.html('[value]');
                break;
        }
        $button.append($('<input type="hidden" name="mappings['+index+'][mt]" value="' + mapping.mt + '">'));

        if (mapping.icon) {
            $icon.append($('<input type="hidden" name="mappings['+index+'][icon]" value="' + mapping.icon + '">'));
            $icon.append($('<img class="sensor-icon" src="'+mapping.url+'">'));
        }

        $row.append($value);
        $row.append($mapped);
        $row.append($icon);
        $row.append($button);

        return $row;
    }
    $(document)
        .off('click', '.add_mapping')
        .on('click', '.add_mapping', function() {
            var error = false;
            var parent = $(this).closest('[data-sensor-input="mapping"]');

            var valueType = parent.find('select[name="vt"]');
            var value = parent.find('input[name="v"]:visible');
            var valueTo = parent.find('input[name="t"]');

            var mapType = parent.find('select[name="mt"]');
            var mapValue = parent.find('input[name="mv"]:visible');
            var icon = parent.find('input[name="icon_id"]');

            if (valueType.val() == 2) {
                value.css('border-color', '#ccc');
                valueTo.css('border-color', '#ccc');

                if (!isNumeric(value.val())) {
                    value.css('border-color', 'red');
                    error = true;
                }

                if (!isNumeric(valueTo.val())) {
                    valueTo.css('border-color', 'red');
                    error = true;
                }

                if (!error && parseFloat(valueTo.val()) < parseFloat(value.val())) {
                    valueTo.css('border-color', 'red');
                    value.css('border-color', 'red');
                    error = true;
                }
            }

            if (error)
                return;

            var data = {
                vt: valueType.val(),
                v: value.val(),
                t: valueTo.val(),
                mt: mapType.val(),
                mv: mapValue.val(),
                icon: null,
                url: null,
            };

            if (icon.val()) {
                data.icon = icon.val();
                data.url = icon.attr('data-path');
            }

            parent.find('table tbody').append(mappingRow(data));
        });

    $(document)
        .off('click', '.remove_mapping')
        .on('click', '.remove_mapping', function() {
            $(this).closest('tr').remove();
        });

    $(document).ready(function () {
        var $modal = $('#sensors_create .modal-content, #sensors_edit .modal-content'),
            $table = $modal.find('table tbody');

        @foreach ($item->mappings ?? [] as $mapping)
            $table.append(mappingRow(JSON.parse('{!! json_encode($mapping) !!}')));
        @endforeach
    });
</script>