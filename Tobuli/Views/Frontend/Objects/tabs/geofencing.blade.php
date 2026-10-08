<div class="tab-pane" id="geofencing_tab">
    <div class="tab-pane-header">
        <div class="form">
            <div class="input-group">
                <div class="form-group search">
                    {!!Form::text('search', null, ['class' => 'form-control', 'placeholder' => trans('front.search'), 'autocomplete' => 'off'])!!}
                </div>
                @if (Auth::User()->perm('geofences', 'edit'))
                <span class="input-group-btn">
                    <a href="javascript:" class="btn btn-default" data-url="{{ route('geofences.index_modal') }}" data-modal="geofences_modal">
                        <i class="icon edit"></i>
                    </a>

                    <a href="javascript:" class="btn btn-primary" type="button" onClick="app.geofences.create();">
                        <i class="icon add"></i>
                    </a>
                </span>
                @endif
            </div>
        </div>
    </div>

    <div class="tab-pane-body">
        <div id="ajax-geofences"></div>
    </div>
</div>

<div class="tab-pane" id="geofencing_create"></div>

<div class="tab-pane" id="geofencing_edit"></div>