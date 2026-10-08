<div class="form-group">
    <div class="checkbox">
        {!! Form::hidden('plugins['.$plugin->key.'][options][installation_date_default_today]', 0) !!}
        {!! Form::checkbox(
            'plugins['.$plugin->key.'][options][installation_date_default_today]',
            1,
            $plugin->options['installation_date_default_today'] ?? false)
        !!}
        {!! Form::label(
            'plugins['.$plugin->key.'][options][installation_date_default_today]',
            trans('validation.attributes.installation_date_default_today'))
        !!}
    </div>
</div>
