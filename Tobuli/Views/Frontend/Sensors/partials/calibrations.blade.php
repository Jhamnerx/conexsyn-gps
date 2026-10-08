<div class="calibrations" data-sensor-input="calibration" data-disablable="#calibration;disable">
    <div class="form-group">
        <div class="checkbox">
            {!! Form::hidden('skip_calibration', 0) !!}
            {!! Form::checkbox('skip_calibration', 1, $item->skip_calibration ?? null) !!}
            {!! Form::label('skip_calibration', trans('validation.attributes.skip_calibration')) !!}
        </div>
    </div>
    {!! Form::hidden('calibrations_fake') !!}
    <div style="display: block; height: 400px;overflow-y: scroll; border: 1px solid #dddddd; margin-bottom: 20px;">
        <table class="table">
            <thead>
            <th style="font-weight: normal">{{ trans('validation.attributes.tag_value') }}</th>
            <th style="font-weight: normal">{{ trans('front.calibrated_value') }}</th>
            <th>
                <a href="javascript:"
                   class="btn btn-xs btn-action"
                   data-url="{{ route('sensor_calibrations.import_modal') }}"
                   data-modal="calibrations_import">
                    <i class="icon upload" title="{{ trans('front.import') }}"></i>
                </a>
            </th>
            </thead>
            <tbody></tbody>
        </table>
    </div>
    <div class="form-group">
        <div class="row">
            <div class="col-xs-5">
                {!! Form::label('x',trans('validation.attributes.tag_value')) !!}
                {!! Form::text('x', null, ['class' => 'form-control']) !!}
            </div>
            <div class="col-xs-5">
                {!! Form::label('y',trans('front.calibrated_value')) !!}
                {!! Form::text('y', null, ['class' => 'form-control']) !!}
            </div>
            <div class="col-xs-2">
                {!! Form::label(null,'&nbsp;') !!}
                <a href="javascript:" class="btn btn-action btn-block add_calibration" type="button"><i class="icon add" title="{{ trans('global.add') }}"></i></a>
            </div>
        </div>
    </div>
</div>

<script>
    function calibrationRow(x, y) {
        return '<tr><td>' + x + '<input type="hidden" name="calibrations[' + x + ']" value="' + y + '"></td><td>' + y + '<input type="hidden" name="ys[' + y + ']" value="1"></td><td><button type="button" class="remove_calibration close"><span aria-hidden="true">×</span></button></td></tr>';
    }

    function isValidCalibration(container, value) {
        return isNumeric(value) && !container.find('input[name="calibrations[' + value + ']"]').length;
    }

    function isValidY(container, value) {
        return isNumeric(value) && !container.find('input[name="ys[' + value + ']"]').length;
    }

    $(document)
        .off('click', '.add_calibration')
        .on('click', '.add_calibration', function() {
            var parent = $(this).closest('[data-sensor-input="calibration"]');

            var x = parent.find('input[name="x"]');
            var y = parent.find('input[name="y"]');
            var x_val = x.val();
            var y_val = y.val();
            var error = false;

            x.css('border-color', '#ccc');
            y.css('border-color', '#ccc');

            if (!isValidCalibration(parent, x_val)) {
                x.css('border-color', 'red');
                error = true;
            }

            if (!isValidY(parent, y_val)) {
                y.css('border-color', 'red');
                error = true;
            }

            if (error)
                return;

            parent.find('table tbody').append(calibrationRow(x_val, y_val));
        });

    $(document)
        .off('click', '.remove_calibration')
        .on('click', '.remove_calibration', function() {
            $(this).closest('tr').remove();
        });

    $(document).ready(function () {
        var $modal = $('#sensors_create .modal-content, #sensors_edit .modal-content'),
            $table = $modal.find('table tbody');
        @foreach ($item->calibrations ?? [] as $key => $value)
            $table.append(calibrationRow({{ $key }}, {{ $value }}));
        @endforeach
    });

    function calibrations_import_modal_callback(res) {
        if (res.status != 1) {
            return;
        }

        let modalSensor = $('#sensors_create .modal-content, #sensors_edit .modal-content');
        let tableSensor = modalSensor.find('.calibrations table tbody');

        if (tableSensor.length !== 1) {
            return;
        }

        if (res.append != 1) {
            tableSensor.html('');
        }

        for (let i in res.data) {
            let xVal = res.data[i].x;
            let yVal = res.data[i].y;

            if (isValidCalibration(tableSensor, xVal) && isValidY(tableSensor, yVal)) {
                tableSensor.append(calibrationRow(xVal, yVal));
            }
        }
    }
</script>