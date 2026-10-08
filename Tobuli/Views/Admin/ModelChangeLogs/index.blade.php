@extends('admin::Layouts.default')

@section('content')
    <div class="panel panel-default" id="table_model_changes">

        <input type="hidden" name="sorting[sort_by]" value="{{ $items->sorting['sort_by'] }}" data-filter>
        <input type="hidden" name="sorting[sort]" value="{{ $items->sorting['sort'] }}" data-filter>

        <div class="panel-heading">
            <ul class="nav nav-tabs nav-icons pull-right">
                <li role="presentation" class="">
                    <a href="javascript:" type="button" id="csv_export">
                        <i class="fa fa-download" title="{{ trans('front.export_csv') }}"></i>
                    </a>
                </li>
            </ul>

            <div class="panel-title">{{ trans('admin.model_change_logs') }}</div>

            <div class="panel-form">
                <div class="form-group">
                    {!! Form::text('search_phrase', null, [
                            'class' => 'form-control',
                            'placeholder' => trans('admin.search_it'),
                            'data-filter' => 'true']) !!}
                </div>
                <div class="form-group" style="width: 160px">
                    {!! Form::select('search_causer', [], $items->sorting['causer'] ?? null, [
                            'class' => 'form-control',
                            'title' => trans('global.user'),
                            'data-live-search' => 'true',
                            'data-actions-box' => 'true',
                            'data-ajax' => route('devices.users.index'),
                            'data-filter' => 'true']) !!}
                </div>
                <div class="form-group">
                    {!! Form::select('search_descriptions[]', $descriptions, $items->sorting['descriptions'] ?? null, [
                            'class' => 'form-control',
                            'multiple' => 'multiple',
                            'title' => trans('front.action'),
                            'data-filter' => 'true']) !!}
                </div>
                <div class="form-group">
                    {!! Form::text('search_date_from', null, [
                            'class' => 'form-control datetimepicker',
                            'data-filter' => 'true',
                            'placeholder' => trans('validation.attributes.date_from'),
                            'data-date-clear-btn' => 'true']) !!}
                </div>
                <div class="form-group">
                    {!! Form::text('search_date_to', null, [
                            'class' => 'form-control datetimepicker',
                            'data-filter' => 'true',
                            'placeholder' => trans('validation.attributes.date_to'),
                            'data-date-clear-btn' => 'true']) !!}
                </div>
                @if(!empty($items->sorting['subjects']))
                    <div class="form-group">
                        <div class="btn-group">
                            {!! Form::search('search_subjects', $items->sorting['subjects'], [
                                    'id' => 'search_subjects',
                                    'class' => 'form-control',
                                    'readonly' => 'readonly',
                                ]) !!}
                            <span id="search_subjects_clear" class="fa fa-times"></span>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="panel-body" data-table>
            @include('admin::ModelChangeLogs.table')
        </div>
    </div>
@stop

@section('styles')
    <style>
        #search_subjects {
            width: 200px;
        }

        #search_subjects_clear {
            position: absolute;
            right: 5px;
            top: 0;
            bottom: 0;
            height: 14px;
            margin: auto;
            font-size: 14px;
            cursor: pointer;
            color: #ccc;
        }
    </style>
@stop

@section('javascript')
    <script>
        tables.set_config('table_model_changes', {
            url: '{{ request()->fullUrl() }}'
        });

        $("#search_subjects_clear").click(function() {
            let subjects = $("#search_subjects");

            if (!subjects.val()) {
                return;
            }

            subjects.val('');

            window.location.href = '{!! route('admin.model_change_logs.index') !!}';
        });

        $('#csv_export').click(function (e) {
            e.preventDefault();

            let queryParams = '';

            $('input[data-filter], select[data-filter]').each(function () {
                let value = $(this).val();
                let name = $(this).attr("name");

                if (name.startsWith('sorting')) {
                    return;
                }

                if (Array.isArray(value)) {
                    value.forEach(item => queryParams += '&' + name + '=' + item);
                } else {
                    queryParams += '&' + name + '=' + value;
                }
            });

            @if ($searchSubjects = request('search_subjects'))
                queryParams += '&search_subjects[]={!! implode('&search_subjects[]=', $searchSubjects) !!}';
            @endif

            window.location.href = "{!! route('admin.model_change_logs.export') !!}?" + queryParams;
        })
    </script>
@stop