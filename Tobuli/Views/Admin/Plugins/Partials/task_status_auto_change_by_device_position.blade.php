<div class="row">
    <div class="col-sm-6">
        <div class="form-group">
            {!! Form::label('plugins['.$plugin->key.'][options][delivery_duration]', trans('validation.attributes.delivery_ac_duration') . ' (' . trans('front.second_short') . ')') !!}
            {!! Form::text('plugins['.$plugin->key.'][options][delivery_duration]', $plugin->options['delivery_duration'], ['class' => 'form-control']) !!}
        </div>

        <div class="form-group">
            {!! Form::label('plugins['.$plugin->key.'][options][delivery_radius]', trans('validation.attributes.delivery_ac_radius')  . ' (' . trans('front.mt') . ')') !!}
            {!! Form::text('plugins['.$plugin->key.'][options][delivery_radius]', $plugin->options['delivery_radius'], ['class' => 'form-control']) !!}
        </div>

        <div class="form-group">
            {!! Form::label('plugins['.$plugin->key.'][options][delivery_status]', trans('validation.attributes.delivery_ac_status')) !!}
            {!! Form::select(
                'plugins['.$plugin->key.'][options][delivery_status]',
                \Tobuli\Entities\TaskStatus::getList(),
                $plugin->options['delivery_status'],
                ['class' => 'form-control'])
            !!}
        </div>
    </div>
    <div class="col-sm-6">
        <div class="form-group">
            {!! Form::label('plugins['.$plugin->key.'][options][pickup_duration]', trans('validation.attributes.pickup_ac_duration') . ' (' . trans('front.second_short') . ')') !!}
            {!! Form::text('plugins['.$plugin->key.'][options][pickup_duration]', $plugin->options['pickup_duration'], ['class' => 'form-control']) !!}
        </div>

        <div class="form-group">
            {!! Form::label('plugins['.$plugin->key.'][options][pickup_radius]', trans('validation.attributes.pickup_ac_radius')  . ' (' . trans('front.mt') . ')') !!}
            {!! Form::text('plugins['.$plugin->key.'][options][pickup_radius]', $plugin->options['pickup_radius'], ['class' => 'form-control']) !!}
        </div>

        <div class="form-group">
            {!! Form::label('plugins['.$plugin->key.'][options][pickup_status]', trans('validation.attributes.pickup_ac_status')) !!}
            {!! Form::select(
                'plugins['.$plugin->key.'][options][pickup_status]',
                \Tobuli\Entities\TaskStatus::getList(),
                $plugin->options['pickup_status'],
                ['class' => 'form-control'])
            !!}
        </div>
    </div>
</div>

