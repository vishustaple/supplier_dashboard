@extends('layout.app', ['pageTitleCheck' => $pageTitle])
@section('content')
    <div id="layoutSidenav">
        @include('layout.sidenavbar', ['pageTitleCheck' => $pageTitle])
            <div id="layoutSidenav_content">
                <div class="container">
                    <div class="m-1 mb-2 d-md-flex border-bottom pb-3 mb-3 align-items-center justify-content-between">
                        <h3 class="mb-0 ps-2">Power BI</h3>
                        <!-- Button trigger modal -->
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStaticBackdrop"><i class="fa fa-plus" aria-hidden="true"></i> Add Report </button>  
                    </div>
                </div>

                <!-- Modal -->
                <div class="modal fade" id="addStaticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="addStaticBackdropLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <form method="POST" action="{{ route('powerbi.add') }}">
                            @csrf
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="addStaticBackdropLabel">Add Report</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label>Reoprt Title</label>
                                        <input class="form-control" required type="text" placeholder="Title here" name="title">
                                    </div>
                                    <div class="form-group">
                                        <label>Embeded Code</label>
                                        <textarea class="form-control" required placeholder="Add your code here" name="iframe" id="floatingTextarea2" style="height: 100px"></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Submit</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Modal -->
                <div class="modal fade" id="editStaticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="editStaticBackdropLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <form method="POST" action="{{ route('powerbi.update') }}" id="powerbi_edit">
                            @csrf
                            <input type="hidden" id="powerbi_id" name="id">   
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editStaticBackdropLabel">Edit Report</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label>Reoprt Title</label>
                                        <input class="form-control" required type="text" placeholder="Title here" name="titles">
                                    </div>
                                    <div class="form-group">
                                        <label>Embeded Code</label>
                                        <textarea class="form-control" required placeholder="Add your code here" name="iframes" id="floatingTextarea2" style="height: 100px"></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Submit</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <table class="table power_bi_table">
                    <thead>
                        <tr>
                            <th scope="col">Title</th>
                            <th scope="col" style="width: 70%;">Embeded Code</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($data)
                            @foreach($data as $key => $value)
                                <tr>
                                    <td>{{ $value->title }}</td>
                                    <td>{{ $value->iframe }}</td>
                                    <td>
                                        <div class="row justify-content-start">
                                            <div class="col-auto px-0">
                                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-id="{{ $value->id }}" data-title="{{ $value->title }}" data-iframe="{{ $value->iframe }}" data-bs-target="#editStaticBackdrop">
                                                    <i class="fa fa-pencil-square" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                            <div class="col-auto">
                                                <a class="btn btn-danger" href="{{ route('powerbi.delete', ['id' => $value->id]) }}">
                                                    <i class="fa fa-trash" aria-hidden="true"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
        </div>
    </div>
    <script>
        document.getElementById('editStaticBackdrop').addEventListener('show.bs.modal', function (event) {
            $('#powerbi_id').val(event.relatedTarget.getAttribute('data-id'));
            $('input[name="titles"]').val(event.relatedTarget.getAttribute('data-title'));
            $('textarea[name="iframes"]').val(event.relatedTarget.getAttribute('data-iframe'));
        });
    </script>
    <style>
        table.power_bi_table tbody tr td:nth-child(2) {
            max-width: 795px  !important;
            width: 795px  !important;
            white-space: normal !important;
            min-width: 795px  !important;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
@endsection   