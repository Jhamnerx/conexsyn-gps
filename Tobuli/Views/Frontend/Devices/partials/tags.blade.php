@php $tagsOptions = \Tobuli\Entities\Tag::userAccessible(auth()->user())->pluck('name', 'id')->all() @endphp

<div class="form-group">
    {!! Form::label('tags[]', trans('validation.attributes.tags').':') !!}
    {!! Form::select('tags[]', $tagsOptions, $item->exists ? $item->tags->pluck('id')->all() : null, [
        'class' => 'form-control multiexpand',
        'multiple' => 'multiple',
        'data-live-search' => 'true',
        'data-actions-box' => 'true'])
    !!}
</div>