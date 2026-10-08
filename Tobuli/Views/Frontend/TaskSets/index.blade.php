@if (Auth::user()->perm('task_sets', 'edit'))
    <div class="action-block">
        <a href="javascript:"
           class="btn btn-action"
           data-url="{!! route('task_sets.create') !!}"
           data-modal="task_sets_create"
           type="button"
        >
            <i class="icon add"></i> {{ trans('global.add_new') }}
        </a>
    </div>
@endif

<div data-table>
    @include('Frontend.TaskSets.table')
</div>

<script>
    tables.set_config('setup-form-task-set-list', {
        url:'{{ route('task_sets.table') }}'
    });
    function task_sets_create_modal_callback() {
        tables.get('setup-form-task-set-list');
    }
    function task_sets_edit_modal_callback() {
        tables.get('setup-form-task-set-list');
    }
    function task_sets_destroy_modal_callback() {
        tables.get('setup-form-task-set-list');
    }
</script>