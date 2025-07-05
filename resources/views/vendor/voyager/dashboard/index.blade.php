@php use TCG\Voyager\Facades\Voyager; @endphp
@extends('voyager::master')

@section('content')
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-4">
                {!! app(\App\Voyager\Widgets\UpdateCache::class)->run() !!}
            </div>
        </div>
        <div class="row">
            @foreach(Voyager::widgets() as $widget)
                <div class="col-md-4">
                    AGustinuss here
                    {!! $widget->run() !!}
                </div>
            @endforeach
        </div>
    </div>
@endsection
