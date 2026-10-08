<div data-sensor-input="logic_on">

    <div class="form-group">
        <div class="checkbox">
            {!! Form::checkbox('setflag', 1, !empty($item->on_tag_count), ['id' => 'logic_setflag_set']) !!}
            {!! Form::label('setflag', trans('front.setflag')) !!}
        </div>
    </div>

    @php
    $condTypes = [
        '1' => trans('front.event_type_1'),
        '2' => trans('front.event_type_2'),
        '3' => trans('front.event_type_3')
    ];
    @endphp

    <div class="form-group" data-sensor-input="logic_on">
        {!! Form::label('on_type', trans('validation.attributes.on_value').':') !!}
        <div class="row">
            <div class="col-md-4 col-xs-4">
                {!! Form::select('on_type', $condTypes, $item->on_type ?? null, ['class' => 'form-control']) !!}
            </div>
            <div class="col-md-8 col-xs-4">
                <div class="input-group">
                    {!! Form::text('on_tag_start', $item->on_tag_start ?? null, ['class' => 'form-control', 'placeholder' => trans('validation.attributes.on_setflag_1'), 'data-disablable' => '#logic_setflag_set;hide-disable']) !!}
                    <span class="input-group-btn"></span>
                    {!! Form::text('on_tag_count', $item->on_tag_count ?? null, ['class' => 'form-control', 'placeholder' => trans('validation.attributes.on_setflag_2'), 'data-disablable' => '#logic_setflag_set;hide-disable']) !!}
                    <span class="input-group-btn"></span>
                    {!! Form::text('on_tag_value', $item->on_tag_value ?? null, ['class' => 'form-control', 'placeholder' => trans('validation.attributes.tag_value')]) !!}
                </div>
            </div>
        </div>
    </div>

    <div class="form-group" data-sensor-input="logic_off">
        {!! Form::label('off_type', trans('validation.attributes.off_value').':') !!}
        <div class="row">
            <div class="col-md-4 col-xs-4">
                {!! Form::select('off_type', $condTypes, $item->off_type ?? null, ['class' => 'form-control']) !!}
            </div>
            <div class="col-md-8 col-xs-4">
                <div class="input-group">
                    {!! Form::text('off_tag_start', $item->off_tag_start ?? null, ['class' => 'form-control', 'placeholder' => trans('validation.attributes.on_setflag_1'), 'data-disablable' => '#logic_setflag_set;hide-disable']) !!}
                    <span class="input-group-btn"></span>
                    {!! Form::text('off_tag_count', $item->off_tag_count ?? null, ['class' => 'form-control', 'placeholder' => trans('validation.attributes.on_setflag_2'), 'data-disablable' => '#logic_setflag_set;hide-disable']) !!}
                    <span class="input-group-btn"></span>
                    {!! Form::text('off_tag_value', $item->off_tag_value ?? null, ['class' => 'form-control', 'placeholder' => trans('validation.attributes.tag_value')]) !!}
                </div>
            </div>
        </div>
    </div>

</div>