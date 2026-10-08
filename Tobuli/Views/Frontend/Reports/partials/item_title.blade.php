<div class="panel-heading">
    <div class="pull-right">
        {{ $report->hasDateFrom() ? Formatter::time()->human($report->getDateFrom()) : '' }}
        {{ $report->hasDateFrom() || $report->hasDateTo() ? ' - ' : '' }}
        {{ $report->hasDateTo() ? Formatter::time()->human($report->getDateTo()) : '' }}
        {{ $report->hasDateFrom() || $report->hasDateTo() ? '(' . Formatter::time()->unit() . ')' : '' }}
    </div>
    <div class="report-bars"></div>
    {{ trans('front.report_type') }}: {{ $report->title() }}
</div>