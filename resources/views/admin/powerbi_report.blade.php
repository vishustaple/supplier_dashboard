@extends('layout.app', ['pageTitleCheck' => $pageTitle])
@section('content')
    <div id="layoutSidenav">
        @include('layout.sidenavbar', ['pageTitleCheck' => $pageTitle])
            <div id="layoutSidenav_content">
                <div class="m-1 d-md-flex border-bottom pb-3 mb-3 flex-md-row align-items-center justify-content-between">
                    <h3 class="mb-0 ps-2">{{ $pageTitle }}</h3>
                </div>
                <div class="container">
                    {!! $data->iframe !!}
                </div>
                @include('layout.footer')
        </div>
    </div>
@endsection  