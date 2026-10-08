<li>
    {!! Form::open(['url' => $route, 'class' => 'confirmed-delete-form-btn']) !!}
        <button id="confirmed-delete-form-submit-{{ $uid = uniqid() }}" type="submit" style="display: none;"></button>
    {!! Form::close() !!}

    <a href="javascript:" onclick="document.getElementById('confirmed-delete-form-submit-{{ $uid }}').click();">
        @if (empty($content))
            {{ trans('global.delete') }}
        @else
            {!! $content !!}
        @endif
    </a>
</li>