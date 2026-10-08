<div id="device_activity" style="width: 200px; height: 300px; margin: auto;"></div>

<script type='text/javascript'>
    $(document).ready(function () {
        $.plot('#device_activity',
            {!! json_encode($statuses) !!},
            {
                series: {
                    pie: {
                        innerRadius: 0.35,
                        show: true
                    }
                },
                legend: {
                    show: false,
                },
            });

        $('#device_activity').css('width', 'auto');
    });
</script>