@extends('layout.app', ['pageTitleCheck' => $pageTitle])
@section('content')
    <div id="layoutSidenav">
        @include('layout.sidenavbar', ['pageTitleCheck' => $pageTitle])
        <div id="layoutSidenav_content">
            <div class="container">
                <div class="m-1 mb-2 d-md-flex border-bottom pb-3 mb-3 align-items-center justify-content-between">
                    <h3 class="mb-0 ">{{ $pageTitle }}</h3>
                    <a href="{{ route('queries.create') }}" class="btn btn-primary">
                    <i class="fa-solid fa-plus"></i> Show Saved Query</a>
                    <a href="{{ route('queries.index')}}" class="btn btn-secondary border-0 bg_yellow" title="Back"><i class="fas fa-arrow-left me-2"></i>Back</a>
                </div>
                <h1>{{ $query->query }}</h1>
            </div>
        </div>
    </div>
@endsection