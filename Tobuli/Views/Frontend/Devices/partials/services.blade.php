<div class="action-block">
    <a href="javascript:" class="btn btn-action" data-url="{!!route('services.create', $item->id)!!}"
       data-modal="services_create" type="button">
        <i class="icon add"></i> {{ trans('front.add_service') }}
    </a>
</div>

<div data-table>
    @include('Frontend.Services.table')
</div>