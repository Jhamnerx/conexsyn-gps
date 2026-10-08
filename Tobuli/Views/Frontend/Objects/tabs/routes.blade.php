<div class="tab-pane" id="routes_tab">
    <div class="tab-pane-header">
        <div class="form">
            <div class="input-group">
                <div class="form-group search">
                    {!!Form::text('search', null, ['class' => 'form-control', 'placeholder' => trans('front.search'), 'autocomplete' => 'off'])!!}
                </div>
                @if (Auth::User()->perm('routes', 'edit'))
                    <div class="input-group-btn">
                        <a href="javascript:" class="btn btn-default" data-url="{{ route('routes.index_modal') }}" data-modal="routes_modal">
                            <i class="icon edit"></i>
                        </a>

                        <a href="javascript:" class="btn btn-primary" type="button" onClick="app.routes.create();">
                            <i class="icon add"></i>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="tab-pane-body">
        <div id="ajax-routes"></div>
    </div>
</div>

<div class="tab-pane" id="routes_create"></div>

<div class="tab-pane" id="routes_edit"></div>