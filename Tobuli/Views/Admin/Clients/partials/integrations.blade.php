@php /** @var \Tobuli\Entities\User $item */ @endphp

<p class="text-muted"><small>{{ trans('front.user_integrations_hint') }}</small></p>

<div class="form-group">
    <div class="checkbox">
        {!! Form::hidden('is_municipalidad', 0) !!}
        {!! Form::checkbox('is_municipalidad', 1, $item->is_municipalidad) !!}
        {!! Form::label(null, trans('validation.attributes.is_municipalidad')) !!}
    </div>
</div>

<div class="row">
    <div class="col-sm-6">
        <div class="form-group">
            {!! Form::label('ubigeo_muni', trans('validation.attributes.ubigeo_muni').':') !!}
            {!! Form::text('ubigeo_muni', $item->ubigeo_muni, ['class' => 'form-control', 'maxlength' => 6, 'placeholder' => '150101']) !!}
            <small class="text-muted">{{ trans('front.ubigeo_muni_hint') }}</small>
        </div>
    </div>
    <div class="col-sm-6">
        <div class="form-group">
            {!! Form::label('token_muni', trans('validation.attributes.token_muni').':') !!}
            {!! Form::text('token_muni', $item->token_muni, ['class' => 'form-control']) !!}
            <small class="text-muted">{{ trans('front.token_muni_hint') }}</small>
        </div>
    </div>
</div>

<div class="form-group">
    {!! Form::label('codigo_comisaria', trans('validation.attributes.codigo_comisaria').':') !!}
    {!! Form::text('codigo_comisaria', $item->codigo_comisaria, ['class' => 'form-control', 'maxlength' => 50]) !!}
    <small class="text-muted">{{ trans('front.codigo_comisaria_hint') }}</small>
</div>
