@extends('Frontend.Layouts.default')

@section('header-menu-items')
    @if ( Auth::User() )
        <li>
            <a href="{{ route('logout') }}">
                <i class="icon logout"></i> <span class="text">{{ trans('global.log_out') }}</span>
            </a>
        </li>
    @endif
@stop

@section('content')
    @if (Session::has('message'))
        <div class="alert alert-danger alert-dismissible">
            {!! Session::get('message') !!}
        </div>
    @endif
    @if (Session::has('success'))
        <div class="alert alert-success alert-dismissible">
            {!! Session::get('success') !!}
        </div>

        <script>
            setTimeout(function () {
                window.location.href = "{{ route('objects.index') }}";
            }, 2000);
        </script>
    @endif

    <h1>{!! trans('front.renew_upgrade') !!}</h1>

    <div class="plans">
        @foreach($plans as $plan)
            @php $route = route('payments.order', ['type' => 'billing_plan', 'plan_id' => $plan->id, 'entity_type' => 'user']) @endphp

            <div class="plan-col">
                @if($template = $plan->buildTemplate($permissions['main'], $route))
                    {!! $template !!}
                @else
                    <div class="plan">
                        <div class="plan-heading">
                            <div class="plan-title">{{ $plan->title }}</div>
                        </div>

                        <div class="plan-body">
                            <table class="table">
                                <tbody>
                                    <tr>
                                        <td>{{ trans('validation.attributes.objects') }}</td>
                                        <td>{{ $plan->objects }}</td>
                                    </tr>
                                    <tr>
                                        <td>{{ trans('front.duration') }}</td>
                                        <td>{{ $plan->duration_value }} {{ trans('front.'.$plan->duration_type) }}</td>
                                    </tr>
                                    <tr>
                                        <td>{{ trans('validation.attributes.price') }}</td>
                                        <td>{{ float($plan->price) }}</td>
                                    </tr>

                                    @foreach($permissions['main'] as $permission => $modes)
                                        <tr>
                                            <td>{{ trans('front.' . $permission) }}</td>
                                            <td><i class="icon check {{ $plan->perm($permission, 'view') ? '' : 'disabled' }}"></i>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="plan-footer">
                            <a href="{{ $route }}" class="btn btn-action btn-plan">
                                {{ $plan->id == Auth::User()->billing_plan_id ? trans('front.renew') : trans('front.upgrade') }}
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@stop