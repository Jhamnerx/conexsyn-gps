<div class="form-group">
    {!! Form::label('sensor_group_id', trans('validation.attributes.sensor_group_id').':') !!}
    {!! Form::select('sensor_group_id', $sensor_groups, null, ['class' => 'form-control']) !!}
</div>
