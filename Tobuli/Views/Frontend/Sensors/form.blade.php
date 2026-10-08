<div class="row">
    <div class="col-md-12 sen-data-fields">
        <div class="form-group">
            {!! Form::label('sensor_name', trans('validation.attributes.sensor_name').':') !!}
            {!! Form::text('sensor_name', $item->name ?? null, ['class' => 'form-control']) !!}
        </div>

        <div class="form-group">
            {!! Form::label('sensor_type', trans('validation.attributes.sensor_template').':') !!}
            {!! Form::select('sensor_type', $sensors, $item->type ?? null, ['class' => 'form-control', 'id' => 'sensor_type']) !!}
        </div>

        <div data-sensor-input="shown_value_by">
            <div class="form-group">
                {!! Form::label('shown_value_by', trans('validation.attributes.shown_value_by').':') !!}
                {!! Form::select('shown_value_by', [], null, ['class' => 'form-control', 'data-value' => $item->shown_value_by ?? null]) !!}
            </div>
        </div>

        <div class="form-group" data-sensor-input="tag_name">
            {!! Form::label('tag_name', trans('validation.attributes.tag_name').':') !!}
            <div class="input-group" id="sensor_parameters">
                {!! Form::select('tag_name', $parameters ?? [], $item->tag_name ?? null, ['class' => 'form-control', 'data-custom-input' => "true", 'data-custom-value' => $item->tag_name ?? null]) !!}
            </div>
        </div>

        <div data-sensor-input="unit_type">
            <div class="form-group">
                {!! Form::label('unit_type', trans('validation.attributes.unit_type').':') !!}
                {!! Form::select('unit_type', \Tobuli\Sensors\Types\EngineHours::unitTypes()->pluck('title', 'key')->prepend(trans('front.none'), ''), $item->unit_type ?? null, ['class' => 'form-control']) !!}
            </div>
        </div>

        <div class="form-group" data-sensor-input="skip_empty">
            <div class="checkbox">
                {!! Form::hidden('skip_empty', 0) !!}
                {!! Form::checkbox('skip_empty', 1, $item->skip_empty ?? null) !!}
                {!! Form::label('skip_empty', trans('validation.attributes.skip_empty')) !!}
            </div>
        </div>

        <div class="form-group" data-sensor-input="reset_blank">
            <div class="checkbox">
                {!! Form::hidden('reset_blank', 0) !!}
                {!! Form::checkbox('reset_blank', 1, $item->reset_blank ?? null) !!}
                {!! Form::label('reset_blank', trans('validation.attributes.reset_blank')) !!}
            </div>
        </div>

        <div class="form-group" data-sensor-input="bin">
            <div class="checkbox">
                {!! Form::hidden('decbin', 0) !!}
                {!! Form::checkbox('decbin', 1, $item->decbin ?? null, ['class' => 'convert-bin']) !!}
                {!! Form::label('decbin', trans('front.convert_dec_bin')) !!}
            </div>
        </div>

        <div class="form-group" data-sensor-input="bin">
            <div class="checkbox">
                {!! Form::hidden('hexbin', 0) !!}
                {!! Form::checkbox('hexbin', 1, $item->hexbin ?? null, ['class' => 'convert-bin']) !!}
                {!! Form::label('hexbin', trans('front.convert_hex_bin')) !!}
            </div>
        </div>

        <div class="form-group" data-sensor-input="ascii">
            <div class="checkbox">
                {!! Form::hidden('ascii', 0) !!}
                {!! Form::checkbox('ascii', 1, $item->ascii ?? null) !!}
                {!! Form::label('ascii', trans('front.ascii')) !!}
            </div>
        </div>

        <div class="form-group" data-sensor-input="calibration">
            <div class="checkbox">
                {!! Form::hidden('calibration', 0) !!}
                {!! Form::checkbox('calibration', 1, !empty($item->calibrations ?? null), ['id' => 'calibration']) !!}
                {!! Form::label('calibration', trans('front.calibration')) !!}
            </div>
        </div>

        <div data-sensor-input="bitcut">
            <div class="form-group">
                {!! Form::label('bitcut', trans('front.bitcut').':') !!}
                <div class="input-group">
                    <div class="checkbox input-group-btn">
                        {!! Form::checkbox(null, 1, !empty($item->bitcut), ['id' => 'bitcut_set']) !!}
                        {!! Form::label(null) !!}
                    </div>
                    {!! Form::text('bitcut[start]', $item->bitcut['start'] ?? null, ['class' => 'form-control', 'placeholder' => trans('validation.attributes.bitcut_start'), 'data-disablable' => '#bitcut_set;disable']) !!}
                    <span class="input-group-btn"></span>
                    {!! Form::text('bitcut[count]', $item->bitcut['count'] ?? null, ['class' => 'form-control', 'placeholder' => trans('validation.attributes.bitcut_count'), 'data-disablable' => '#bitcut_set;disable']) !!}
                    <span class="input-group-btn"></span>
                    {!! Form::select('bitcut[base]', \Tobuli\Sensors\Extractions\BitCut::bases()->pluck('title', 'key'), $item->bitcut['base'] ?? null, ['class' => 'form-control', 'data-disablable' => '#bitcut_set;disable']) !!}
                </div>
            </div>
        </div>

        <div data-sensor-input="setflag">
            <div class="form-group">
                {!! Form::label('setflag', trans('front.setflag').':') !!}
                <div class="input-group">
                    <div class="checkbox input-group-btn">
                        {!! Form::checkbox('setflag', 1, !empty($item->setflag_count), ['id' => 'setflag_set']) !!}
                        {!! Form::label(null) !!}
                    </div>
                    {!! Form::text('setflag_start', $item->setflag_start ?? null, ['class' => 'form-control', 'placeholder' => trans('validation.attributes.on_setflag_1'), 'data-disablable' => '#setflag_set;disable']) !!}
                    <span class="input-group-btn"></span>
                    {!! Form::text('setflag_count', $item->setflag_count ?? null, ['class' => 'form-control', 'placeholder' => trans('validation.attributes.on_setflag_2'), 'data-disablable' => '#setflag_set;disable']) !!}
                </div>
            </div>
        </div>

        <div data-sensor-input="formula">
            <div class="form-group">
                {!! Form::label('formula', trans('validation.attributes.formula').':') !!}
                <div class="input-group">
                    <div class="checkbox input-group-btn">
                        {!! Form::checkbox(null, 1, !empty($item->formula), ['id' => 'formula_set']) !!}
                        {!! Form::label(null) !!}
                    </div>
                    {!! Form::text('formula', (empty($item->formula) ? \Tobuli\Sensors\Extractions\Formula::PLACEHOLDER : $item->formula), ['class' => 'form-control', 'data-disablable' => '#formula_set;disable']) !!}
                </div>
                <span class="explanation">{{ trans('front.formula_example') }}</span>
            </div>
        </div>

        <div data-sensor-input="minmax">
            <div class="row">
                <div class="col-md-6 col-sm-6">
                    <div class="form-group">
                        {!! Form::label('min_value', trans('validation.attributes.min_value').':') !!}
                        {!! Form::text('min_value', $item->min_value ?? null, ['class' => 'form-control']) !!}
                    </div>
                </div>
                <div class="col-md-6 col-sm-6">
                    <div class="form-group">
                        {!! Form::label('max_value', trans('validation.attributes.max_value').':') !!}
                        {!! Form::text('max_value', $item->max_value ?? null, ['class' => 'form-control']) !!}
                    </div>
                </div>
            </div>
        </div>

        <div data-sensor-input="full_tank">
            <div class="form-group">
                {!! Form::label('fuel_tank_name', trans('validation.attributes.fuel_tank_name').':') !!}
                {!! Form::text('fuel_tank_name', $item->fuel_tank_name ?? null, ['class' => 'form-control']) !!}
            </div>

            <div class="form-group" data-disablable="#calibration;show-enable">
                {!! Form::label('parameters', trans('validation.attributes.parameters').':') !!}
                <div class="row">
                    <div class="col-md-6 col-sm-6">
                        {!! Form::text('full_tank', $item->full_tank ?? null, ['class' => 'form-control', 'placeholder' => trans('validation.attributes.full_tank')]) !!}
                    </div>
                    <div class="col-md-6 col-sm-6">
                        {!! Form::text('full_tank_value', $item->full_tank_value ?? null, ['class' => 'form-control', 'placeholder' => trans('validation.attributes.tag_value')]) !!}
                    </div>
                </div>
            </div>
        </div>

        @include('Frontend.Sensors.partials.logic')

        <div class="form-group" data-sensor-input="mapping">
            <div class="checkbox">
                {!! Form::hidden('mapping', 0) !!}
                {!! Form::checkbox('mapping', 1, !empty($item->mappings ?? null), ['id' => 'mapping']) !!}
                {!! Form::label('mapping', trans('front.mapping')) !!}
            </div>
        </div>

        <div data-sensor-input="unit" class="form-group">
            {!! Form::label('unit_of_measurement', trans('validation.attributes.unit_of_measurement').':') !!}
            {!! Form::text('unit_of_measurement', $item->unit_of_measurement ?? null, ['class' => 'form-control']) !!}
        </div>

        <div data-sensor-input="odometer_unit" class="form-group">
            {!! Form::label('odometer_value_unit', trans('validation.attributes.unit_of_measurement').':') !!}
            {!! Form::select('odometer_value_unit', ['km' => trans('front.km'), 'mi' => trans('front.mi')], $item->odometer_value_unit ?? null, ['class' => 'form-control']) !!}
        </div>

        <div data-sensor-input="value">
            <div class="form-group">
                {!! Form::label('value', trans('validation.attributes.value').':') !!}
                <div class="input-group">
                    <div class="checkbox input-group-btn">
                        {!! Form::checkbox(null, 1, null, ['id' => 'value_set']) !!}
                        {!! Form::label(null) !!}
                    </div>
                    {!! Form::text('value', $item->value ?? 0, ['class' => 'form-control', 'data-disablable' => '#value_set;disable']) !!}
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-6" data-sensor-input="add_to_history">
                <div class="form-group">
                    <div class="checkbox">
                        {!! Form::checkbox('add_to_history', 1, $item->add_to_history ?? null) !!}
                        {!! Form::label('add_to_history', trans('front.add_to_history')) !!}
                    </div>
                </div>
            </div>

            <div class="col-sm-6" data-sensor-input="add_to_graph">
                <div class="form-group">
                    <div class="checkbox">
                        {!! Form::checkbox('add_to_graph', 1, $item->add_to_graph ?? null) !!}
                        {!! Form::label('add_to_graph', trans('front.add_to_graph')) !!}
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('value', trans('front.preview').':') !!}
            <div class="input-group">
                {!! Form::text('input', null, ['class' => 'form-control', 'placeholder' => trans('validation.attributes.value')]) !!}
                <div class="input-group-btn">
                    <a href="javascript:" class="btn btn-action btn-preview" type="button">
                        <i class="icon send" title="{{ trans('front.preview') }}"></i>
                    </a>
                </div>
                <span class="input-group-btn"></span>
                {!! Form::text(null, null, ['class' => 'form-control', 'id' => 'preview-result', 'readonly' => 'true']) !!}
            </div>
        </div>
    </div>
    <div class="col-md-6 sen-cal-fields">
        @include('Frontend.Sensors.partials.calibrations')
        @include('Frontend.Sensors.partials.mapping')
    </div>
</div>
<script>
    var types = jQuery.parseJSON('{!! json_encode($types)  !!}');

    $(document)
        .off('click', '.btn-preview')
        .on('click', '.btn-preview', function(e) {
            e.preventDefault();
            var $form = $(this).closest('form'),
                $container = $(this).closest('.form-group');

            $.ajax({
                type: 'POST',
                url: '{!! route('sensors.preview') !!}',
                dataType: "json",
                data: $form.serializeArray(),
                beforeSend: function() {
                    loader.add($container);
                },
                success: function(res){
                    if (res.status) {
                        $('#preview-result').val(res.formatted);
                    }
                },
                complete: function() {
                    loader.remove($container);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    handlerFail(jqXHR, textStatus, errorThrown);
                }
            });
        });

    $(document)
        .off('change', 'input[name="mapping"]')
        .on('change', 'input[name="mapping"]', function() {
            if ($(this).prop('checked')) {
                calibationShow();
            } else {
                calibationHide();
            }
        });

    $(document)
        .off('change', 'input[name="calibration"]')
        .on('change', 'input[name="calibration"]', function() {
            if ($(this).prop('checked')) {
                calibationShow();
            } else {
                calibationHide();
            }
        });

    $(document)
        .off('change', 'select[name="sensor_type"]')
        .on('change', 'select[name="sensor_type"]', function() {
            $('.help-block.error').remove();
            var type = getSensorType($(this).val());

            if (!type)
                return;

            if (type.shown_by) {
                setShownBy(type);
            } else {
                setInputs(type);
            }
        });

    $(document)
        .off('change', 'select[name="shown_value_by"]')
        .on('change', 'select[name="shown_value_by"]', function() {
            $('.help-block.error').remove();
            var type = getSensorType($('select[name="sensor_type"]').val());

            if (!type)
                return;

            setInputs(type);
        });

    function getSensorType(key) {
        var type = null;
        $.each(types, function(index, value) {
            if (value.type  == key) {
                type = value;
                return false;
            }
        });
        return type;
    }

    function setShownBy(type) {
        if (!type.shown_by) {
            return;
        }

        var $select = $('select[name="shown_value_by"]');

        $select.html('');

        $.each(type.shown_by, function( key, title ) {
            $select.append('<option value="' + key + '">' + title + '</option>');
        });

        if ($select.attr('data-value')) {
            $select.val($select.attr('data-value'));
        }

        $select
            .trigger('change')
            .selectpicker('refresh');
    }

    function setInputs(type) {
        calibationHide();
        $('[data-sensor-input]').each(function() {
            inputStatus($(this).attr('data-sensor-input'), false);
        });

        var showBy = type.shown_by;
        var inputs;

        if (showBy) {
            inputStatus("shown_value_by", true);
            inputs = type.inputs[$('select[name="shown_value_by"]').val()];
        }

        if (!inputs) {
            inputs = type.inputs[Object.keys(type.inputs)];
        }

        $.each(inputs, function(key, status) {
            inputStatus(key, status);
        });
    }

    function calibationShow() {
        $('#sensors_create, #sensors_edit').find('.modal-dialog').addClass('modal-lg');
        $('.sen-cal-fields').show();
        $('.sen-data-fields').removeClass('col-md-12').addClass('col-md-6');
    }
    function calibationHide() {
        $('#sensors_create, #sensors_edit').find('.modal-dialog').removeClass('modal-lg');
        $('.sen-cal-fields').hide();
        $('.sen-data-fields').removeClass('col-md-6').addClass('col-md-12');
    }

    function inputStatus(key, status, element) {
        var $block = $('[data-sensor-input="'+key+'"]');
        if (status) {
            $block.show();
            $block.find('input, select').prop('disabled', false);

            $block.find('input, select').not('select[name="shown_value_by"]').trigger('change');
        } else {
            $block.hide();
            $block.find('input, select')
                .prop('disabled', true);
        }
    }

    $(document).ready(function () {
        $('select[name="sensor_type"]').trigger('change');
        //$('input[name="calibration"]').trigger('change');
        //$('input[name="mapping"]').trigger('change');
    });
</script>