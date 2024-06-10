<!-- resources/views/queries/edit.blade.php -->
@extends('layout.app', ['pageTitleCheck' => $pageTitle])
@section('content')
    <div id="layoutSidenav">
        @include('layout.sidenavbar', ['pageTitleCheck' => $pageTitle])
        <div id="layoutSidenav_content">
            <div class="container">
                <div class="m-1 mb-2 d-md-flex border-bottom pb-3 mb-3 align-items-center justify-content-between">
                    <h3 class="mb-0 ">{{ $pageTitle }}</h3>
                    <a href="{{ route('queries.create') }}" class="btn btn-primary">
                    <i class="fa-solid fa-plus"></i> Edit Saved Query</a>
                    <a href="{{ route('queries.index')}}" class="btn btn-secondary border-0 bg_yellow" title="Back"><i class="fas fa-arrow-left me-2"></i>Back</a>
                </div>
                <form method="POST" action="{{ route('queries.update', $query->id) }}">
                    @csrf
                    @method('PUT')
                    <div class="form-floating mb-2">
                        <input class="form-control" placeholder="Write a Title here" id="floatingTitle" name="title" value="{{ $query->title }}" />
                        <label for="floatingTitle">Title</label>
                    </div>
                    <div class="form-floating mb-2">
                        <textarea class="form-control" placeholder="Write a query here" id="floatingTextarea" name="query">{{ $query->query }}</textarea>
                        <label for="floatingTextarea">Query</label>
                    </div>
                    <button class="btn btn-primary float-right" type="submit">Update Query</button>
                </form>
            </div>
        </div>
    </div>
@endsection
