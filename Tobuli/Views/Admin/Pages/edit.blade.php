@extends('Frontend.Layouts.modal')

@section('title', trans('global.edit'))

@section('body')
    @php /** @var \Tobuli\Entities\Page $item */ @endphp
    {!! Form::open(['route' => ['admin.pages.update', $item->id], 'method' => 'PUT']) !!}

    <div class="row">
        <div class="col-sm-12">
            <div class="form-group">
                {!! Form::label('slug', trans('validation.attributes.slug') . ':') !!}
                {!! Form::text('slug', $item->slug, ['class' => 'form-control']) !!}
            </div>
        </div>

        <div class="col-sm-12">
            <div class="form-group">
                {!! Form::label('title', trans('validation.attributes.title') . ':') !!}
                {!! Form::text('title', $item->title, ['class' => 'form-control']) !!}
            </div>
        </div>

        <div class="col-sm-12">
            <div class="form-group">
                {!! Form::label('title', trans('validation.attributes.note') . ':') !!}
                {!! Form::textarea('content', $item->content, ['class' => 'form-control wysihtml5', 'style' => 'height:400px']) !!}
            </div>
        </div>
    </div>

    <script type="text/javascript">
        $('.wysihtml5').wysihtml5({"image": false});
    </script>

    {!! Form::close() !!}
@stop
