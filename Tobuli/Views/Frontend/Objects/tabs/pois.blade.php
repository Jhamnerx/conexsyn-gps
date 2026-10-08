<div class="tab-pane" id="pois_tab">
    <div class="tab-pane-header">
        <div class="form">
            <div class="input-group">
                <div class="form-group search">
                    {!!Form::text('search', null, ['class' => 'form-control', 'placeholder' => trans('front.search'), 'autocomplete' => 'off'])!!}
                </div>
                @if (Auth::User()->perm('poi', 'edit'))
                    <div class="input-group-btn">
                        <a href="javascript:" class="btn btn-default" data-url="{{ route('pois.index_modal') }}" data-modal="pois_modal">
                            <i class="icon edit"></i>
                        </a>

                        <a href="javascript:" class="btn btn-primary" type="button" onClick="app.pois.create();">
                            <i class="icon add"></i>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="tab-pane-body">
        <div id="ajax-map-icons"></div>
    </div>
</div>

<div class="tab-pane" id="pois_create"></div>

<div class="tab-pane" id="pois_edit"></div>