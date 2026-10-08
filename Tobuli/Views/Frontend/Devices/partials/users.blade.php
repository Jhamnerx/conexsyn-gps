<div class="form-group">
    {!! Form::label('user_id', trans('validation.attributes.user').':') !!}
    {!! Form::select('user_id[]', [], auth()->user()->id, [
        'class' => 'form-control multiexpand half',
        'multiple' => 'multiple',
        'data-live-search' => 'true',
        'data-actions-box' => 'true',
        'data-ajax' => isset($item->id) ? route('devices.users.get', $item->id) : route('devices.users.index')
        ]) !!}
</div>