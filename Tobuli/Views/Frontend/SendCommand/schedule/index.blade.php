<div data-table>
    @include('Frontend.SendCommand.schedule.table')
</div>

<script>
    tables.set_config('schedule', {
        url:'{{ route('command_schedules.table') }}',
    });

    function command_schedule_modal_callback() {
        tables.get('schedule');
    }

</script>