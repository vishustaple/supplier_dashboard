@extends('layout.app', ['pageTitleCheck' => $pageTitle])

@section('content')
<div id="layoutSidenav">
    @include('layout.sidenavbar', ['pageTitleCheck' => $pageTitle])
    <div id="layoutSidenav_content">
        <div class="container">
            <div class="m-1 mb-2 d-md-flex border-bottom pb-3 mb-3 align-items-center justify-content-between">
                <h3 class="mb-0 ">{{ $pageTitle }}</h3>
                <a href="{{ route('commission.add-view') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Add Commissions</a>
                <!-- <button id="downloadCommissionCsvBtn" class="btn-success btn" title="Csv Download"><i class="fa-solid me-2 fa-file-csv"></i>Download</button>
                    </div> -->
            </div>
            <table class="data_table_files" id="commission_data">
                <thead>
                    <tr>
                        <th>Customer Name</th>
                        <th>Customer Number</th>
                        <th>Supplier</th>
                        <th>Account Name</th>
                        <th>Commission</th>
                        <th>Sales Repersantative</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>
        <div class="modal fade" id="editcommisionModal" tabindex="-1" aria-labelledby="editAccountModalLabel" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editAccountModalLabel">Edit Commission</h5>
        <!-- Close icon -->
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
                      <div class="alert alert-success mx-2" id="editsuccessMessage" style="display:none;">
                        </div>
                        <div  id="editerrorMessage" >
                        </div>
                        <form action="{{route('accountname.edit')}}" method="post" id="edit_account_name">
                            <div class="modal-body">
                                <div class="form-group">
                                @csrf

                                <div class="modal_input_wrap">
                                <input type="hidden" name="commission_id" id="account_id" value="">
                                <label>Commission</label>
                                </div>

                                <div class="modal_input_wrap pb-3">
                                <input type="text" placeholder="Enter Account Name" class="form-control" name="account_name" id="account_name" value="">
                                <div id="account_name_error"></div>
                                </div>
                                 
                               
                                
                                <div class="row m-0 pb-3">
                                <div class="form-check col-md-6">
                                    <input class="form-check-input" type="radio"  value="1" name="parent_check" id="flexRadioDefault1">
                                    <label class="form-check-label" for="flexRadioDefault1">
                                        Manually Add Parent
                                    </label>
                                    </div>
                                    <div class="form-check col-md-6">
                                    <input class="form-check-input" type="radio" value="2" name="parent_check" id="flexRadioDefault2" checked>
                                    <label class="form-check-label" for="flexRadioDefault2">
                                        Using Select Add Parent
                                    </label>
                                </div>
                                </div>

                                
                                <div class="div1 form-group row" style="display:none; ">
                                <div class="pb-3">
                                    <label>Parent Name</label>
                                    <input type="text" placeholder="Enter Account Name" class="form-control" name="parent_name1" id="parent_name" value="">
                                    </div>
                                    <div class="">
                                    <label>Parent Number</label>
                                    <input type="text" placeholder="Enter Account Name" class="form-control" name="parent_id1" id="parent_id" value="">
                                </div>
                                </div>

                                
            </div>
        </div>
      <div class="modal-footer">
        <!-- <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button> -->
        <button type="submit" class="btn btn-primary">Save changes</button>
      </div>
      </form>   
    </div>
  </div>
</div>



    </div>
</div>

<style>
    div#page-loader {
        top: 0;
        left: 0;
        position: fixed;
        width: 100%;
        height: 100%;
        background: #00000080;
        z-index: 999999;
    }

    div#page-loader-wrap {
        text-align: center;
        /* vertical-align: center !important; */
        margin-top: 20%;
    }
</style>

<!-- Include Date Range Picker JavaScript -->
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.js"></script>
<script>
    $(document).ready(function() {
        // Button click event
        $('#import_form').on('submit', function () {
            event.preventDefault();
            // Initiate DataTable AJAX request
            $('#commission_data').DataTable().ajax.reload();
        });

        // DataTable initialization
        var dataTable = $('#commission_data').DataTable({
            oLanguage: {
                sProcessing: '<div id="page-loader"><div id="page-loader-wrap"><div class="spinner-grow text-primary" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-success" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-danger" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-warning" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-info" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-light" role="status"><span class="sr-only">Loading...</span></div></div></div>'
            },
            processing: true,
            serverSide: true,
            pageLength: 50,
            ajax: {
                url: '{{ route("commission.filter") }}',
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
            },
            beforeSend: function() {
                // Show both the DataTables processing indicator and the manual loader before making the AJAX request
                $('.dataTables_processing').show();
                $('#manualLoader').show();
            },
            complete: function() {
                // Hide both the DataTables processing indicator and the manual loader when the DataTable has finished loading
                $('.dataTables_processing').hide();
                $('#manualLoader').hide();
            },
            columns: [
                { data: 'customer_name', name: 'customer_name' },
                { data: 'customer_number', name: 'customer_number' },
                { data: 'supplier_name', name: 'supplier_name' },
                { data: 'account_name', name: 'account_name' },
                { data: 'commission', name: 'commission' },
                { data: 'sales_rep', name: 'sales_rep' },
                { data: 'start_date', name: 'start_date' },
                { data: 'end_date', name: 'end_date' },
                { data: 'id', name: 'id', 'orderable': false, 'searchable': false }
            ],
        });

        $('#commission_data_length').hide();
        $('#downloadCommissionCsvBtn').on('click', function () {
            // Trigger CSV download
            downloadCommissionCsv();
        });

        function downloadCommissionCsv() {
            // You can customize this URL to match your backend route for CSV download
            var csvUrl = '{{ route("commission.export-csv") }}';
            csvUrl += '?search=' + dataTable.search();
            // Open a new window to download the CSV file
            window.open(csvUrl, '_blank');
        }
    });
</script>
@endsection