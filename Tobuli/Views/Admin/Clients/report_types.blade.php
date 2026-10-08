<div class="form-group">
    <div class="checkbox">
        {!! Form::hidden('report_types') !!}
        {!! Form::checkbox('report_types_config', 1, $hasConfig, ['id' => 'edit_report_types_config']) !!}
        {!! Form::label('report_types_config', trans('front.user_individual_config')) !!}
    </div>
</div>

<hr>

<div data-disablable="#edit_report_types_config;hide-disable">
    {!! Form::label('report_types[]', trans('admin.report_types').':') !!}
    {!! Form::select(
            'report_types[]',
            $reportTypes,
            $reportTypesSelected,
            ['class' => 'form-control multiexpand half', 'data-live-search' => 'true', 'data-actions-box' => 'true', 'multiple' => 'multiple']
        ) !!}
</div>
