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

                <!-- Modal Add New Power Bi Report -->
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
                                        <label>Report Title</label>
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

                <!-- Modal Edit Power Bi Report -->
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
                                        <label>Report Title</label>
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
                <div class="form-group form-check ml-2">
                    <input type="checkbox" value="1" class="checkbox form-check-input" id="exampleCheck1">
                    <label class="form-check-label" for="exampleCheck1">Show Only Deleted Report</label>
                </div>
                <table class="table power_bi_table" id="power_bi_data"></table>
        @include('layout.footer')
        </div>
    </div>
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
    <script>
        $('#exampleCheck1').change(function(){
            $('#power_bi_data').DataTable().ajax.reload();
            powerbidataTable.draw();
        });

        function hideColumns() {
            if ($('#exampleCheck1:checked').val() == 1) {
                powerbidataTable.column('id:name').visible(false);
                powerbidataTable.column('deleted_at:name').visible(true);
                powerbidataTable.column('deleted_by:name').visible(true);
                powerbidataTable.column('created_by:name').visible(false);
            } else {
                powerbidataTable.column('id:name').visible(true);
                powerbidataTable.column('deleted_at:name').visible(false);
                powerbidataTable.column('deleted_by:name').visible(false);
                powerbidataTable.column('created_by:name').visible(true);
            }
        }

        // DataTable initialization
        var powerbidataTable = $('#power_bi_data').DataTable({
            oLanguage: {
                sProcessing: '<div id="page-loader"><div id="page-loader-wrap"><div class="spinner-grow text-primary" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-success" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-danger" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-warning" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-info" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-light" role="status"><span class="sr-only">Loading...</span></div></div></div>'
            },
            processing: true,
            serverSide: true,
            lengthMenu: [40],
            searching: false,
            paging: true,
            pageLength: 40,
            ajax: {
                url: '{{ route("power_bi.show.ajax") }}',
                type: 'POST',
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                data: function (d) {
                    d.check = $('#exampleCheck1:checked').val();
                },
            },

            columns: [
                { data: 'title', name: 'title', title: 'Title' },
                { data: 'iframe', name: 'iframe' ,title: 'Embeded Code' },
                { data: 'deleted_at', name: 'deleted_at', title: 'Deleted At' },
                { data: 'created_by', name: 'created_by', title: 'Created By',  orderable: false, searchable: false },
                { data: 'deleted_by', name: 'deleted_by', title: 'Deleted By',  orderable: false, searchable: false },
                { data: 'id', name: 'id', title: 'Action', orderable: false, searchable: false },
            ],

            rowCallback: function(row, data, index) {
                // Loop through each cell in the row
                $('td', row).each(function() {
                    // Check if the cell contains a button with a specific class
                    if ($(row).find('div.delete').length === 0) {
                        $(row).css('background-color','#f09b9b');
                    }
                });
            },

            fnDrawCallback: function( oSettings ) {
                hideColumns();
            },
        });

        document.getElementById('editStaticBackdrop').addEventListener('show.bs.modal', function (event) {
            $('#powerbi_id').val(event.relatedTarget.getAttribute('data-id'));
            $('input[name="titles"]').val(event.relatedTarget.getAttribute('data-title'));
            $('textarea[name="iframes"]').val(event.relatedTarget.getAttribute('data-iframe'));
        });

        function deletePowerBI(id, title='') {
            swal.fire({
                title: title,
                text: "Are you sure you want to delete this report?",
                icon: "question",
                showCancelButton: true,
                confirmButtonText: 'YES',
                cancelButtonText: 'NO',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "{{ route('powerbi.delete')}}/"+id+""; // Change this to the redirect URL
                }
            });  
        }
    </script>
@endsection   