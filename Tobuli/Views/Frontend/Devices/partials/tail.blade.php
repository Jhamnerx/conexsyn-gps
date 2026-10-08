<div class="form-group">
    {!!Form::label('tail_color', trans('validation.attributes.tail_color').':')!!}
    {!!Form::text('tail_color', $item->tail_color, ['class' => 'form-control colorpicker'])!!}
</div>

<div class="form-group">
    {!!Form::label('tail_length', trans('validation.attributes.tail_length').' (0-10 '.trans('front.last_points').'):')!!}
    {!!Form::text('tail_length', $item->tail_length, ['class' => 'form-control'])!!}
</div>