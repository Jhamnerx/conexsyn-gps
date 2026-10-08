@php /** @var \Tobuli\Entities\Device $item */ @endphp
@php
    $canViewMtc = Auth::user()->can('view', $item, 'mtc');
    $canViewMininter = Auth::user()->can('view', $item, 'mininter');
    $canEditMtc = Auth::user()->can('edit', $item, 'mtc');
    $canEditMininter = Auth::user()->can('edit', $item, 'mininter');
    $canEditMininterType = Auth::user()->can('edit', $item, 'mininter_type');
    $mininterUser = $item->exists ? $item->mininterUser() : null;
@endphp

<p class="text-muted"><small>{{ trans('front.integrations_hint') }}</small></p>

@if ($canViewMtc)
    <div class="form-group">
        <div class="checkbox">
            {!! Form::hidden('mtc', 0) !!}
            {!! Form::checkbox('mtc', 1, $item->mtc, $canEditMtc ? [] : ['disabled' => 'disabled']) !!}
            {!! Form::label(null, trans('validation.attributes.mtc')) !!}
        </div>
        <small class="text-muted">{{ trans('front.integration_sutran_hint') }}</small>
    </div>
@endif

@if ($canViewMininter)
    <div class="form-group">
        <div class="checkbox">
            {!! Form::hidden('mininter', 0) !!}
            {!! Form::checkbox('mininter', 1, $item->mininter, $canEditMininter ? [] : ['disabled' => 'disabled']) !!}
            {!! Form::label(null, trans('validation.attributes.mininter')) !!}
        </div>
        <small class="text-muted">{{ trans('front.integration_mininter_hint') }}</small>
    </div>

    <div class="form-group">
        {!! Form::label('mininter_type', trans('validation.attributes.mininter_type').':') !!}
        {!! Form::select(
            'mininter_type',
            \Tobuli\Entities\Device::mininterTypes(),
            $item->mininter_type ?: \Tobuli\Entities\Device::MININTER_TYPE_SERENAZGO,
            ['class' => 'form-control'] + ($canEditMininterType ? [] : ['disabled' => 'disabled'])
        ) !!}
        <small class="text-muted">{{ trans('front.integration_mininter_type_hint') }}</small>
    </div>

    @if ($item->exists)
        <div class="form-group">
            @if ($mininterUser)
                <small class="text-success">
                    {{ trans('front.integration_mininter_user', [
                        'email' => $mininterUser->email,
                        'ubigeo' => $mininterUser->ubigeo_muni ?: '-',
                        'token' => $mininterUser->token_muni ?: '-',
                    ]) }}
                </small>
            @else
                <small class="text-danger">{{ trans('front.integration_mininter_no_user') }}</small>
            @endif
        </div>
    @endif
@endif
