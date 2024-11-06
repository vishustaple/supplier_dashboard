@extends('layout.app', ['pageTitleCheck' => $pageTitle])
 @section('content')
 <div id="layoutSidenav">
    @include('layout.sidenavbar', ['pageTitleCheck' => $pageTitle])
    <div id="layoutSidenav_content">
        <div class="container">
            <div class="m-1 mb-2 row align-items-start justify-content-between">
                <div class="col-md-4">
                    <h3 class="mb-0 ">{{ $pageTitle }}</h3>
                </div>
            </div>
            <form  id="import_form"  enctype="multipart/form-data">
                @csrf
                <div class="row align-items-end py-3 border-top border-bottom mb-3">
                    <div class="form-group col-md-2 mb-0">
                        <label for="supplier">Select Supplier:</label>
                        <select id="supplier" name="supplier" class="form-control" required> 
                            <option value="" selected>--Select--</option>
                            @if(isset($categorySuppliers))
                                @foreach($categorySuppliers as $categorySupplier)
                                    @if($categorySupplier->id != 7)
                                        <option value="{{ $categorySupplier->id }}">{{ $categorySupplier->supplier_name }}</option>
                                    @endif
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="form-check relative col-2  mb-0">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="rebate_check" value="1" id="volume_rebate_check" checked>
                            <label class="form-check-label" id="volume_rebate_check_label" for="volume_rebate_check">Volume Rebate</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="rebate_check" value="2" id="incentive_rebate_check" checked>
                            <label class="form-check-label" id="incentive_rebate_check_label" for="incentive_rebate_check">Incentive Rebate</label>
                        </div>
                    </div>
                    <!-- <div class="form-group relative col-md-3 mb-0">  
                        <label for="enddate">Select Date:</label>
                        <input class="form-control" id="enddate" name="dates" placeholder="Enter Your End Date " >
                    </div> -->
                    <div class="form-group relative  mb-3 row">
                        <div class="col-6">
                            <label for="startdate">Select Start Date:</label>
                            <input class="form-control" id="startdate" name="dates" placeholder="Enter Your Start Date " >
                        </div>  
                        <div class="col-6">
                            <label for="enddate">Select End Date:</label>
                            <input class="form-control" id="enddate" name="dates" placeholder="Enter Your End Date " >
                        </div>
                    </div>
                    <div class="col-5 mt-2 text-end">
                        <button type="submit" class="btn btn-primary m-1">Submit</button>
                        <button id="downloadCsvBtn" class="btn-success btn m-1" title="Csv Download"><i class="fa-solid me-2 fa-file-csv"></i>Download</button>
                        <button id="downloadPdfBtn" class="btn-danger btn m-1 disabled" title="Pdf Download"><i class="fa-solid me-2 fa-file-pdf"></i>PDF</button>
                    </div>
                </div>
            </form>
        </div>
        <div class="row justify-content-end py-3 header_bar" style="display:none !important;">
            <div class="col-md-4 card shadow border-0">
            <h6 class="d-flex total_amount_header justify-content-between">Total Spend: <b style="color:#000;" id="total_spend"></b></h6>
                <h6 class="d-flex total_amount_header justify-content-between">Qualified Spend: <b style="color:#000;" id="qualified_spend"></b></h6>
                <h6 class="d-flex volume_rebate_header justify-content-between">Total Volume Rebate: <b style="color:#000;" id="volume_rebate"></b></h6>
                <h6 class="d-flex incentive_rebate_header justify-content-between">Total Incentive Rebate: <b style="color:#000;" id="incentive_rebate"></b></h6>
                <h6 class="d-flex justify-content-between">Start Date: <b style="color:#000;" id="startDates"></b></h6>
                <h6 class="d-flex justify-content-between">End Date: <b style="color:#000;" id="endDates"></b></h6>
            </div>
        </div>
        <table id="supplier_report_data" class="data_table_files"></table>
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
        margin-top: 20%;
    }

    #consolidated_supplier_data{
        display:block;
        overflow-x:auto;
    }

    #consolidated_supplier_data thead tr th {
        white-space: nowrap;
    }
</style>
<!-- Include Date Range Picker JavaScript -->
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<script>
    $(document).ready(function() {
        var defaultStartDate = moment().subtract(1, 'month').startOf('month'), // Default start date (1 month ago)
        defaultEndDate = moment(); // Default end date (today)
        
        // Set the default start date in the input
        $('#startdate').val(defaultStartDate.format('MM/DD/YYYY'));
        
        // Set the default end date in the input
        $('#enddate').val(defaultEndDate.format('MM/DD/YYYY'));

        $('#select_dates').on('change', function(){
            var selectValue = $(this).val();
            
            if (selectValue == 0) {
                $('#start_date').prop('disabled', false);
                $('#end_date').prop('disabled', false);
            } else {
                $('#start_date').prop('disabled', true);
                $('#end_date').prop('disabled', true);
            }
        });

        var selectedValues = $('#supplier').val();
        if (selectedValues == 1) {
                // Grand & Toy
            var startOfQuarter1 = moment().month(5).date(1),  // June 1st
            endOfQuarter1 = moment().month(7).date(31),  // August 31st
            startOfQuarter2 = moment().month(8).date(1),  // September 1st
            endOfQuarter2 = moment().month(10).date(30),  // November 30th
            startOfQuarter3 = moment().month(11).date(1),  // December 1st
            endOfQuarter3 = moment().month(1).date(moment().year() % 4 === 0 ? 29 : 28),  // February 28th (or 29th in a leap year)
            startOfQuarter4 = moment().month(2).date(1),  // March 1st
            endOfQuarter4 = moment().month(4).date(31),  // May 31st
            // Grand & Toy

            ranges = {
                // 'Last Quarter': [moment().subtract(3, 'month').startOf('quarter'), moment().subtract(3, 'month').endOf('quarter')],
                'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                'Last 6 Months': [moment().subtract(6, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                'Quarter 1': [startOfQuarter1, endOfQuarter1],
                'Quarter 2': [startOfQuarter2, endOfQuarter2],
                'Quarter 3': [startOfQuarter3, endOfQuarter3],
                'Quarter 4': [startOfQuarter4, endOfQuarter4],
            };
            $('#startdate').daterangepicker({
                autoApply: true,
                showDropdowns: true,
                singleDatePicker: true,
                showCustomRangeLabel: true,
                minYear: moment().subtract(7, 'years').year(),
                maxYear: moment().add(7, 'years').year(),
                ranges: ranges,
            }, function(start, end, label) {
                // If a custom range is selected, populate both startDate and endDate
                if (
                    label == 'Quarter 1' || 
                    label == 'Quarter 2' || 
                    label == 'Quarter 3' || 
                    label == 'Quarter 4' || 
                    label === 'Last Year' ||
                    label === 'Last Quarter' ||
                    label === 'Last Quarter' ||
                    label === 'Last 6 Months'
                ) {
                    $('#startdate').val(start.format('MM/DD/YYYY')); // Set start date
                    $('#enddate').val(end.format('MM/DD/YYYY')); // Set end date
                } else {
                    // If a normal date is picked, only set the startDate
                    $('#startdate').val(start.format('MM/DD/YYYY'));
                }
            });

        } else if (selectedValues == 2) {
            // grainer
            var startOfQuarter1 = moment().month(8).date(1),  // September 1st
            endOfQuarter1 = moment().month(10).date(30),  // November 30th
            startOfQuarter2 = moment().month(11).date(1),  // December 1st
            endOfQuarter2 = moment().month(1).date(moment().year() % 4 === 0 ? 29 : 28),  // February 29th (or 28th)
            startOfQuarter3 = moment().month(2).date(1),  // March 1st
            endOfQuarter3 = moment().month(4).date(31),  // May 31st
            startOfQuarter4 = moment().month(5).date(1),  // June 1st
            endOfQuarter4 = moment().month(7).date(31),  // August 31st
            // grainer

            ranges = {
                // 'Last Quarter': [moment().subtract(3, 'month').startOf('quarter'), moment().subtract(3, 'month').endOf('quarter')],
                'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                'Last 6 Months': [moment().subtract(6, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                'Quarter 1': [startOfQuarter1, endOfQuarter1],
                'Quarter 2': [startOfQuarter2, endOfQuarter2],
                'Quarter 3': [startOfQuarter3, endOfQuarter3],
                'Quarter 4': [startOfQuarter4, endOfQuarter4],
            };
            $('#startdate').daterangepicker({
                autoApply: true,
                showDropdowns: true,
                singleDatePicker: true,
                showCustomRangeLabel: true,
                minYear: moment().subtract(7, 'years').year(),
                maxYear: moment().add(7, 'years').year(),
                ranges: ranges,
            }, function(start, end, label) {
                // If a custom range is selected, populate both startDate and endDate
                if (
                    label == 'Quarter 1' || 
                    label == 'Quarter 2' || 
                    label == 'Quarter 3' || 
                    label == 'Quarter 4' || 
                    label === 'Last Year' ||
                    label === 'Last Quarter' ||
                    label === 'Last Quarter' ||
                    label === 'Last 6 Months'
                ) {
                    $('#startdate').val(start.format('MM/DD/YYYY')); // Set start date
                    $('#enddate').val(end.format('MM/DD/YYYY')); // Set end date
                } else {
                    // If a normal date is picked, only set the startDate
                    $('#startdate').val(start.format('MM/DD/YYYY'));
                }
            });

        } else if (selectedValues == 3) {
            // Office Depot
            // Volume rebate (Calendar Quarter)
            var startOfQuarter1 = moment().month(0).date(1),  // January 1st
            endOfQuarter1 = moment().month(2).date(31),  // March 31st
            startOfQuarter2 = moment().month(3).date(1),  // April 1st
            endOfQuarter2 = moment().month(5).date(30),  // June 30th
            startOfQuarter3 = moment().month(6).date(1),  // July 1st
            endOfQuarter3 = moment().month(8).date(30),  // September 30th
            startOfQuarter4 = moment().month(9).date(1),  // October 1st
            endOfQuarter4 = moment().month(11).date(31);  // December 31st
            // Volume rebate (Calendar Quarter)

            var ranges = {
                'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                'Last 6 Months': [moment().subtract(6, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                'Quarter 1': [startOfQuarter1, endOfQuarter1],
                'Quarter 2': [startOfQuarter2, endOfQuarter2],
                'Quarter 3': [startOfQuarter3, endOfQuarter3],
                'Quarter 4': [startOfQuarter4, endOfQuarter4],
            };

            $('#startdate').daterangepicker({
                autoApply: true,
                showDropdowns: true,
                singleDatePicker: true,
                showCustomRangeLabel: true,
                minYear: moment().subtract(7, 'years').year(),
                maxYear: moment().add(7, 'years').year(),
                ranges: ranges,
            }, function(start, end, label) {
                // If a custom range is selected, populate both startDate and endDate
                if (
                    label == 'Quarter 1' || 
                    label == 'Quarter 2' || 
                    label == 'Quarter 3' || 
                    label == 'Quarter 4' || 
                    label === 'Last Year' ||
                    label === 'Last Quarter' ||
                    label === 'Last Quarter' ||
                    label === 'Last 6 Months'
                ) {
                    $('#startdate').val(start.format('MM/DD/YYYY')); // Set start date
                    $('#enddate').val(end.format('MM/DD/YYYY')); // Set end date
                } else {
                    // If a normal date is picked, only set the startDate
                    $('#startdate').val(start.format('MM/DD/YYYY'));
                }
            });
        } else if (selectedValues == 4) {
            ranges = {
                // 'Last Quarter': [moment().subtract(3, 'month').startOf('quarter'), moment().subtract(3, 'month').endOf('quarter')],
                'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                'Last 6 Months': [moment().subtract(6, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
            };
            $('#startdate').daterangepicker({
                autoApply: true,
                showDropdowns: true,
                singleDatePicker: true,
                showCustomRangeLabel: true,
                minYear: moment().subtract(7, 'years').year(),
                maxYear: moment().add(7, 'years').year(),
                ranges: ranges,
            }, function(start, end, label) {
                // If a custom range is selected, populate both startDate and endDate
                if (
                    label == 'Quarter 1' || 
                    label == 'Quarter 2' || 
                    label == 'Quarter 3' || 
                    label == 'Quarter 4' || 
                    label === 'Last Year' ||
                    label === 'Last Quarter' ||
                    label === 'Last Quarter' ||
                    label === 'Last 6 Months'
                ) {
                    $('#startdate').val(start.format('MM/DD/YYYY')); // Set start date
                    $('#enddate').val(end.format('MM/DD/YYYY')); // Set end date
                } else {
                    // If a normal date is picked, only set the startDate
                    $('#startdate').val(start.format('MM/DD/YYYY'));
                }
            });

        } else if (selectedValues == 5) {
            // WB Mason (Calendar Quarter)
            var startOfQuarter1 = moment().month(0).date(1),  // January 1st
            endOfQuarter1 = moment().month(2).date(31),  // March 31st
            startOfQuarter2 = moment().month(3).date(1),  // April 1st
            endOfQuarter2 = moment().month(5).date(30),  // June 30th
            startOfQuarter3 = moment().month(6).date(1),  // July 1st
            endOfQuarter3 = moment().month(8).date(30),  // September 30th
            startOfQuarter4 = moment().month(9).date(1),  // October 1st
            endOfQuarter4 = moment().month(11).date(31);  // December 31st
            // WB Mason (Calendar Quarter)
            ranges = {
                // 'Last Quarter': [moment().subtract(3, 'month').startOf('quarter'), moment().subtract(3, 'month').endOf('quarter')],
                'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                'Last 6 Months': [moment().subtract(6, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                'Quarter 1': [startOfQuarter1, endOfQuarter1],
                'Quarter 2': [startOfQuarter2, endOfQuarter2],
                'Quarter 3': [startOfQuarter3, endOfQuarter3],
                'Quarter 4': [startOfQuarter4, endOfQuarter4],
            };
            $('#startdate').daterangepicker({
                autoApply: true,
                showDropdowns: true,
                singleDatePicker: true,
                showCustomRangeLabel: true,
                minYear: moment().subtract(7, 'years').year(),
                maxYear: moment().add(7, 'years').year(),
                ranges: ranges,
            }, function(start, end, label) {
                // If a custom range is selected, populate both startDate and endDate
                if (
                    label == 'Quarter 1' || 
                    label == 'Quarter 2' || 
                    label == 'Quarter 3' || 
                    label == 'Quarter 4' || 
                    label === 'Last Year' ||
                    label === 'Last Quarter' ||
                    label === 'Last Quarter' ||
                    label === 'Last 6 Months'
                ) {
                    $('#startdate').val(start.format('MM/DD/YYYY')); // Set start date
                    $('#enddate').val(end.format('MM/DD/YYYY')); // Set end date
                } else {
                    // If a normal date is picked, only set the startDate
                    $('#startdate').val(start.format('MM/DD/YYYY'));
                }
            });

        } else if (selectedValues == 6) {
            // lyrco
            var startOfQuarter1 = moment().month(0).date(1),  // January 1st
            endOfQuarter1 = moment().month(2).date(31),  // March 31st
            startOfQuarter2 = moment().month(3).date(1),  // April 1st
            endOfQuarter2 = moment().month(5).date(30),  // June 30th
            startOfQuarter3 = moment().month(6).date(1),  // July 1st
            endOfQuarter3 = moment().month(8).date(30),  // September 30th
            startOfQuarter4 = moment().month(9).date(1),  // October 1st
            endOfQuarter4 = moment().month(11).date(31),  // December 31st
            // lyrco
            ranges = {
                // 'Last Quarter': [moment().subtract(3, 'month').startOf('quarter'), moment().subtract(3, 'month').endOf('quarter')],
                'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                'Last 6 Months': [moment().subtract(6, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                'Quarter 1': [startOfQuarter1, endOfQuarter1],
                'Quarter 2': [startOfQuarter2, endOfQuarter2],
                'Quarter 3': [startOfQuarter3, endOfQuarter3],
                'Quarter 4': [startOfQuarter4, endOfQuarter4],
            };
            $('#startdate').daterangepicker({
                autoApply: true,
                showDropdowns: true,
                singleDatePicker: true,
                showCustomRangeLabel: true,
                minYear: moment().subtract(7, 'years').year(),
                maxYear: moment().add(7, 'years').year(),
                ranges: ranges,
            }, function(start, end, label) {
                // If a custom range is selected, populate both startDate and endDate
                if (
                    label == 'Quarter 1' || 
                    label == 'Quarter 2' || 
                    label == 'Quarter 3' || 
                    label == 'Quarter 4' || 
                    label === 'Last Year' ||
                    label === 'Last Quarter' ||
                    label === 'Last Quarter' ||
                    label === 'Last 6 Months'
                ) {
                    $('#startdate').val(start.format('MM/DD/YYYY')); // Set start date
                    $('#enddate').val(end.format('MM/DD/YYYY')); // Set end date
                } else {
                    // If a normal date is picked, only set the startDate
                    $('#startdate').val(start.format('MM/DD/YYYY'));
                }
            });

        } else {
            ranges = {
                'Last Quarter': [moment().subtract(3, 'month').startOf('quarter'), moment().subtract(3, 'month').endOf('quarter')],
                'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                'Last 6 Months': [moment().subtract(6, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
            };
            $('#startdate').daterangepicker({
                autoApply: true,
                showDropdowns: true,
                singleDatePicker: true,
                showCustomRangeLabel: true,
                minYear: moment().subtract(7, 'years').year(),
                maxYear: moment().add(7, 'years').year(),
                ranges: ranges,
            }, function(start, end, label) {
                // If a custom range is selected, populate both startDate and endDate
                if (
                    label == 'Quarter 1' || 
                    label == 'Quarter 2' || 
                    label == 'Quarter 3' || 
                    label == 'Quarter 4' || 
                    label === 'Last Year' ||
                    label === 'Last Quarter' ||
                    label === 'Last Quarter' ||
                    label === 'Last 6 Months'
                ) {
                    $('#startdate').val(start.format('MM/DD/YYYY')); // Set start date
                    $('#enddate').val(end.format('MM/DD/YYYY')); // Set end date
                } else {
                    // If a normal date is picked, only set the startDate
                    $('#startdate').val(start.format('MM/DD/YYYY'));
                }
            });
        }

        $('input[name="rebate_check"]').change(function() {
            if ($('input[name="rebate_check"]:checked').val() == 1) {
                // Volume rebate (Calendar Quarter)
                var startOfQuarter1 = moment().month(0).date(1),  // January 1st
                endOfQuarter1 = moment().month(2).date(31),  // March 31st
                startOfQuarter2 = moment().month(3).date(1),  // April 1st
                endOfQuarter2 = moment().month(5).date(30),  // June 30th
                startOfQuarter3 = moment().month(6).date(1),  // July 1st
                endOfQuarter3 = moment().month(8).date(30),  // September 30th
                startOfQuarter4 = moment().month(9).date(1),  // October 1st
                endOfQuarter4 = moment().month(11).date(31);  // December 31st
                // Volume rebate (Calendar Quarter)
            } else {
                // Incentive Rebate
                var startOfQuarter1 = moment().month(10).date(1),  // November 1st
                endOfQuarter1 = moment().month(0).date(31).add(1, 'year'),  // January 31st
                startOfQuarter2 = moment().month(1).date(1),  // February 1st
                endOfQuarter2 = moment().month(3).date(30),  // April 30th
                startOfQuarter3 = moment().month(4).date(1),  // May 1st
                endOfQuarter3 = moment().month(6).date(31),  // July 31st
                startOfQuarter4 = moment().month(7).date(1),  // August 1st
                endOfQuarter4 = moment().month(9).date(31);  // October 31st
                // Incentive Rebate
            }

            var ranges = {
                'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                'Last 6 Months': [moment().subtract(6, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                'Quarter 1': [startOfQuarter1, endOfQuarter1],
                'Quarter 2': [startOfQuarter2, endOfQuarter2],
                'Quarter 3': [startOfQuarter3, endOfQuarter3],
                'Quarter 4': [startOfQuarter4, endOfQuarter4],
            };

            $('#startdate').daterangepicker({
                autoApply: true,
                showDropdowns: true,
                singleDatePicker: true,
                showCustomRangeLabel: true,
                minYear: moment().subtract(7, 'years').year(),
                maxYear: moment().add(7, 'years').year(),
                ranges: ranges,
            }, function(start, end, label) {
                // If a custom range is selected, populate both startDate and endDate
                if (
                    label == 'Quarter 1' || 
                    label == 'Quarter 2' || 
                    label == 'Quarter 3' || 
                    label == 'Quarter 4' || 
                    label === 'Last Year' ||
                    label === 'Last Quarter' ||
                    label === 'Last Quarter' ||
                    label === 'Last 6 Months'
                ) {
                    $('#startdate').val(start.format('MM/DD/YYYY')); // Set start date
                    $('#enddate').val(end.format('MM/DD/YYYY')); // Set end date
                } else {
                    // If a normal date is picked, only set the startDate
                    $('#startdate').val(start.format('MM/DD/YYYY'));
                }
            });
        });
        
        // Assuming your select element has id 'mySelect'
        $('#supplier').change(function() {
            // Get the selected value
            var selectedValue = $(this).val();
            
            if (selectedValue == 1) {
                 // Grand & Toy
                var startOfQuarter1 = moment().month(5).date(1),  // June 1st
                endOfQuarter1 = moment().month(7).date(31),  // August 31st
                startOfQuarter2 = moment().month(8).date(1),  // September 1st
                endOfQuarter2 = moment().month(10).date(30),  // November 30th
                startOfQuarter3 = moment().month(11).date(1),  // December 1st
                endOfQuarter3 = moment().month(1).date(moment().year() % 4 === 0 ? 29 : 28),  // February 28th (or 29th in a leap year)
                startOfQuarter4 = moment().month(2).date(1),  // March 1st
                endOfQuarter4 = moment().month(4).date(31),  // May 31st
                // Grand & Toy

                ranges = {
                    // 'Last Quarter': [moment().subtract(3, 'month').startOf('quarter'), moment().subtract(3, 'month').endOf('quarter')],
                    'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                    'Last 6 Months': [moment().subtract(6, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                    'Quarter 1': [startOfQuarter1, endOfQuarter1],
                    'Quarter 2': [startOfQuarter2, endOfQuarter2],
                    'Quarter 3': [startOfQuarter3, endOfQuarter3],
                    'Quarter 4': [startOfQuarter4, endOfQuarter4],
                };

                $('#startdate').daterangepicker({
                    autoApply: true,
                    showDropdowns: true,
                    singleDatePicker: true,
                    showCustomRangeLabel: true,
                    minYear: moment().subtract(7, 'years').year(),
                    maxYear: moment().add(7, 'years').year(),
                    ranges: ranges,
                }, function(start, end, label) {
                    // If a custom range is selected, populate both startDate and endDate
                    if (
                        label == 'Quarter 1' || 
                        label == 'Quarter 2' || 
                        label == 'Quarter 3' || 
                        label == 'Quarter 4' || 
                        label === 'Last Year' ||
                        label === 'Last Quarter' ||
                        label === 'Last Quarter' ||
                        label === 'Last 6 Months'
                    ) {
                        $('#startdate').val(start.format('MM/DD/YYYY')); // Set start date
                        $('#enddate').val(end.format('MM/DD/YYYY')); // Set end date
                    } else {
                        // If a normal date is picked, only set the startDate
                        $('#startdate').val(start.format('MM/DD/YYYY'));
                    }
                });
            } else if (selectedValue == 2) {
                // grainer
                var startOfQuarter1 = moment().month(8).date(1),  // September 1st
                endOfQuarter1 = moment().month(10).date(30),  // November 30th
                startOfQuarter2 = moment().month(11).date(1),  // December 1st
                endOfQuarter2 = moment().month(1).date(moment().year() % 4 === 0 ? 29 : 28),  // February 29th (or 28th)
                startOfQuarter3 = moment().month(2).date(1),  // March 1st
                endOfQuarter3 = moment().month(4).date(31),  // May 31st
                startOfQuarter4 = moment().month(5).date(1),  // June 1st
                endOfQuarter4 = moment().month(7).date(31),  // August 31st
                // grainer

                ranges = {
                    // 'Last Quarter': [moment().subtract(3, 'month').startOf('quarter'), moment().subtract(3, 'month').endOf('quarter')],
                    'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                    'Last 6 Months': [moment().subtract(6, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                    'Quarter 1': [startOfQuarter1, endOfQuarter1],
                    'Quarter 2': [startOfQuarter2, endOfQuarter2],
                    'Quarter 3': [startOfQuarter3, endOfQuarter3],
                    'Quarter 4': [startOfQuarter4, endOfQuarter4],
                };
                $('#startdate').daterangepicker({
                    autoApply: true,
                    showDropdowns: true,
                    singleDatePicker: true,
                    showCustomRangeLabel: true,
                    minYear: moment().subtract(7, 'years').year(),
                    maxYear: moment().add(7, 'years').year(),
                    ranges: ranges,
                }, function(start, end, label) {
                    // If a custom range is selected, populate both startDate and endDate
                    if (
                        label == 'Quarter 1' || 
                        label == 'Quarter 2' || 
                        label == 'Quarter 3' || 
                        label == 'Quarter 4' || 
                        label === 'Last Year' ||
                        label === 'Last Quarter' ||
                        label === 'Last Quarter' ||
                        label === 'Last 6 Months'
                    ) {
                        $('#startdate').val(start.format('MM/DD/YYYY')); // Set start date
                        $('#enddate').val(end.format('MM/DD/YYYY')); // Set end date
                    } else {
                        // If a normal date is picked, only set the startDate
                        $('#startdate').val(start.format('MM/DD/YYYY'));
                    }
                });
            } else if (selectedValue == 3) {
                // Office Depot
                // Volume rebate (Calendar Quarter)
                var startOfQuarter1 = moment().month(0).date(1),  // January 1st
                endOfQuarter1 = moment().month(2).date(31),  // March 31st
                startOfQuarter2 = moment().month(3).date(1),  // April 1st
                endOfQuarter2 = moment().month(5).date(30),  // June 30th
                startOfQuarter3 = moment().month(6).date(1),  // July 1st
                endOfQuarter3 = moment().month(8).date(30),  // September 30th
                startOfQuarter4 = moment().month(9).date(1),  // October 1st
                endOfQuarter4 = moment().month(11).date(31);  // December 31st
                // Volume rebate (Calendar Quarter)

                // Incentive Rebate
                // var startOfQuarter1 = moment().month(10).date(1),  // November 1st
                // endOfQuarter1 = moment().month(0).date(31).add(1, 'year'),  // January 31st
                // startOfQuarter2 = moment().month(1).date(1),  // February 1st
                // endOfQuarter2 = moment().month(3).date(30),  // April 30th
                // startOfQuarter3 = moment().month(4).date(1),  // May 1st
                // endOfQuarter3 = moment().month(6).date(31),  // July 31st
                // startOfQuarter4 = moment().month(7).date(1),  // August 1st
                // endOfQuarter4 = moment().month(9).date(31);  // October 31st
                // Incentive Rebate

                var ranges = {
                    // 'Last Quarter': [moment().subtract(3, 'month').startOf('quarter'), moment().subtract(3, 'month').endOf('quarter')],
                    'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                    'Last 6 Months': [moment().subtract(6, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                    'Quarter 1': [startOfQuarter1, endOfQuarter1],
                    'Quarter 2': [startOfQuarter2, endOfQuarter2],
                    'Quarter 3': [startOfQuarter3, endOfQuarter3],
                    'Quarter 4': [startOfQuarter4, endOfQuarter4],
                };
                $('#startdate').daterangepicker({
                    autoApply: true,
                    showDropdowns: true,
                    singleDatePicker: true,
                    showCustomRangeLabel: true,
                    minYear: moment().subtract(7, 'years').year(),
                    maxYear: moment().add(7, 'years').year(),
                    ranges: ranges,
                }, function(start, end, label) {
                    // If a custom range is selected, populate both startDate and endDate
                    if (
                        label == 'Quarter 1' || 
                        label == 'Quarter 2' || 
                        label == 'Quarter 3' || 
                        label == 'Quarter 4' || 
                        label === 'Last Year' ||
                        label === 'Last Quarter' ||
                        label === 'Last Quarter' ||
                        label === 'Last 6 Months'
                    ) {
                        $('#startdate').val(start.format('MM/DD/YYYY')); // Set start date
                        $('#enddate').val(end.format('MM/DD/YYYY')); // Set end date
                    } else {
                        // If a normal date is picked, only set the startDate
                        $('#startdate').val(start.format('MM/DD/YYYY'));
                    }
                });
            } else if (selectedValue == 4) {
                ranges = {
                    'Last Quarter': [moment().subtract(3, 'month').startOf('quarter'), moment().subtract(3, 'month').endOf('quarter')],
                    'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                    'Last 6 Months': [moment().subtract(6, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                };
                $('#enddate').daterangepicker({
                    autoApply: true,
                    showDropdowns: true,
                    singleDatePicker: true,
                    showCustomRangeLabel: true,
                    minYear: moment().subtract(7, 'years').year(),
                    maxYear: moment().add(7, 'years').year(),
                    ranges: ranges,
                });
            } else if (selectedValue == 5) {
                // WB Mason (Calendar Quarter)
                var startOfQuarter1 = moment().month(0).date(1),  // January 1st
                endOfQuarter1 = moment().month(2).date(31),  // March 31st
                startOfQuarter2 = moment().month(3).date(1),  // April 1st
                endOfQuarter2 = moment().month(5).date(30),  // June 30th
                startOfQuarter3 = moment().month(6).date(1),  // July 1st
                endOfQuarter3 = moment().month(8).date(30),  // September 30th
                startOfQuarter4 = moment().month(9).date(1),  // October 1st
                endOfQuarter4 = moment().month(11).date(31),  // December 31st
                // WB Mason (Calendar Quarter)

                ranges = {
                    // 'Last Quarter': [moment().subtract(3, 'month').startOf('quarter'), moment().subtract(3, 'month').endOf('quarter')],
                    'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                    'Last 6 Months': [moment().subtract(6, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                    'Quarter 1': [startOfQuarter1, endOfQuarter1],
                    'Quarter 2': [startOfQuarter2, endOfQuarter2],
                    'Quarter 3': [startOfQuarter3, endOfQuarter3],
                    'Quarter 4': [startOfQuarter4, endOfQuarter4],
                };
                $('#startdate').daterangepicker({
                    autoApply: true,
                    showDropdowns: true,
                    singleDatePicker: true,
                    showCustomRangeLabel: true,
                    minYear: moment().subtract(7, 'years').year(),
                    maxYear: moment().add(7, 'years').year(),
                    ranges: ranges,
                }, function(start, end, label) {
                    // If a custom range is selected, populate both startDate and endDate
                    if (
                        label == 'Quarter 1' || 
                        label == 'Quarter 2' || 
                        label == 'Quarter 3' || 
                        label == 'Quarter 4' || 
                        label === 'Last Year' ||
                        label === 'Last Quarter' ||
                        label === 'Last Quarter' ||
                        label === 'Last 6 Months'
                    ) {
                        $('#startdate').val(start.format('MM/DD/YYYY')); // Set start date
                        $('#enddate').val(end.format('MM/DD/YYYY')); // Set end date
                    } else {
                        // If a normal date is picked, only set the startDate
                        $('#startdate').val(start.format('MM/DD/YYYY'));
                    }
                });
            } else if (selectedValue == 6) {
                // lyrco
                var startOfQuarter1 = moment().month(0).date(1),  // January 1st
                endOfQuarter1 = moment().month(2).date(31),  // March 31st
                startOfQuarter2 = moment().month(3).date(1),  // April 1st
                endOfQuarter2 = moment().month(5).date(30),  // June 30th
                startOfQuarter3 = moment().month(6).date(1),  // July 1st
                endOfQuarter3 = moment().month(8).date(30),  // September 30th
                startOfQuarter4 = moment().month(9).date(1),  // October 1st
                endOfQuarter4 = moment().month(11).date(31),  // December 31st
                // lyrco

                ranges = {
                    // 'Last Quarter': [moment().subtract(3, 'month').startOf('quarter'), moment().subtract(3, 'month').endOf('quarter')],
                    'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                    'Last 6 Months': [moment().subtract(6, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                    'Quarter 1': [startOfQuarter1, endOfQuarter1],
                    'Quarter 2': [startOfQuarter2, endOfQuarter2],
                    'Quarter 3': [startOfQuarter3, endOfQuarter3],
                    'Quarter 4': [startOfQuarter4, endOfQuarter4],
                };
                $('#startdate').daterangepicker({
                    autoApply: true,
                    showDropdowns: true,
                    singleDatePicker: true,
                    showCustomRangeLabel: true,
                    minYear: moment().subtract(7, 'years').year(),
                    maxYear: moment().add(7, 'years').year(),
                    ranges: ranges,
                }, function(start, end, label) {
                    // If a custom range is selected, populate both startDate and endDate
                    if (
                        label == 'Quarter 1' || 
                        label == 'Quarter 2' || 
                        label == 'Quarter 3' || 
                        label == 'Quarter 4' || 
                        label === 'Last Year' ||
                        label === 'Last Quarter' ||
                        label === 'Last Quarter' ||
                        label === 'Last 6 Months'
                    ) {
                        $('#startdate').val(start.format('MM/DD/YYYY')); // Set start date
                        $('#enddate').val(end.format('MM/DD/YYYY')); // Set end date
                    } else {
                        // If a normal date is picked, only set the startDate
                        $('#startdate').val(start.format('MM/DD/YYYY'));
                    }
                });
            } else {
                ranges = {
                    'Last Quarter': [moment().subtract(3, 'month').startOf('quarter'), moment().subtract(3, 'month').endOf('quarter')],
                    'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                    'Last 6 Months': [moment().subtract(6, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                };
                $('#startdate').daterangepicker({
                    autoApply: true,
                    showDropdowns: true,
                    singleDatePicker: true,
                    showCustomRangeLabel: true,
                    minYear: moment().subtract(7, 'years').year(),
                    maxYear: moment().add(7, 'years').year(),
                    ranges: ranges,
                }, function(start, end, label) {
                    // If a custom range is selected, populate both startDate and endDate
                    if (
                        label == 'Quarter 1' || 
                        label == 'Quarter 2' || 
                        label == 'Quarter 3' || 
                        label == 'Quarter 4' || 
                        label === 'Last Year' ||
                        label === 'Last Quarter' ||
                        label === 'Last Quarter' ||
                        label === 'Last 6 Months'
                    ) {
                        $('#startdate').val(start.format('MM/DD/YYYY')); // Set start date
                        $('#enddate').val(end.format('MM/DD/YYYY')); // Set end date
                    } else {
                        // If a normal date is picked, only set the startDate
                        $('#startdate').val(start.format('MM/DD/YYYY'));
                    }
                });
            }

            if (selectedValue == 3) {
                $('#incentive_rebate_check').show();
                $('#incentive_rebate_check_label').show();
                // $('#incentive_rebate_check').prop('checked', true);
            } else {
                $('#incentive_rebate_check').hide();
                $('#incentive_rebate_check_label').hide();
                $('#incentive_rebate_check').prop('checked', false);
            }
        });
        // $('#enddate').daterangepicker({
        //     autoApply: true,
        //     showDropdowns: true,
        //     showCustomRangeLabel: true,
        //     minYear: moment().subtract(7, 'years').year(),
        //     maxYear: moment().add(7, 'years').year(),
        //     // maxDate: moment(),
        //     ranges: ranges,

        // });

        // Button click event
        $('#import_form').on('submit', function () {
            event.preventDefault();
            $('#startDates').text(moment($('#startdate').val(), 'MM/DD/YYYY').format('MM/DD/YYYY'));
            $('#endDates').text(moment($('#enddate').val(), 'MM/DD/YYYY').format('MM/DD/YYYY'));
            $('.header_bar').attr('style', 'display:flex !important;');

            // Initiate DataTable AJAX request
            $('#supplier_report_data').DataTable().ajax.reload();
        });

        function setPercentage() {
            if ($('.total_amount').val() != null) {
                $('#total_spend').text($('.total_amount').val());
            }

            var selectedValues = $('#supplier').val();
        
            if (selectedValues == 3) {
                $('#incentive_rebate_check').show();
                $('#incentive_rebate_check_label').show();
                // $('#incentive_rebate_check').prop('checked', true);
            } else {
                $('#incentive_rebate_check').hide();
                $('#incentive_rebate_check_label').hide();
                $('#incentive_rebate_check').prop('checked', false);
            }

            var $html = $('<div>' + (supplierDataTable.column(2).data()[0] !== undefined ? supplierDataTable.column(2).data()[0] : '<input type="hidden" value="0"class="qualified_spend">') + ' ' + (supplierDataTable.column(3).data()[0] !== undefined ? supplierDataTable.column(3).data()[0] : '<input type="hidden" value="0"class="input_volume_rebate">') + ' ' + (supplierDataTable.column(4).data()[0] !== undefined ? supplierDataTable.column(4).data()[0] : '<input type="hidden" value="0" class="input_incentive_rebate">') + '</div>'),
            hiddenVolumeRebateInputValue = $html.find('.input_volume_rebate').val(),
            hiddenIncentiveRebateInputValue = $html.find('.input_incentive_rebate').val(),
            totalAmount = $html.find('.qualified_spend').val();

            $('#qualified_spend').text('$'+totalAmount);

            if ($('#volume_rebate_check').is(':checked')) {
                supplierDataTable.column('volume_rebate:name').visible(true);
                $('#volume_rebate').text((hiddenVolumeRebateInputValue !== '0' ? '$' + hiddenVolumeRebateInputValue : 'N/A'));
                $('.volume_rebate_header').attr('style', 'display:flex !important;');
            } else {
                supplierDataTable.column('volume_rebate:name').visible(false);
                $('.volume_rebate_header').attr('style', 'display:none !important;');
                $('#volume_rebate').text('');
            }

            if ($('#incentive_rebate_check').is(':checked')) {
                supplierDataTable.column('incentive_rebate:name').visible(true);
                $('#incentive_rebate').text((hiddenIncentiveRebateInputValue !== '0' ? '$' + hiddenIncentiveRebateInputValue : 'N/A'));
                $('.incentive_rebate_header').attr('style', 'display:flex !important;');
            } else {
                supplierDataTable.column('incentive_rebate:name').visible(false);
                $('.incentive_rebate_header').attr('style', 'display:none !important;');
                $('#incentive_rebate').text('');
            }
        }

        // End Date Picker - Simple calendar
        $('#enddate').daterangepicker({
            autoApply: true,
            showDropdowns: true,
            singleDatePicker: true,
            locale: {
                format: 'MM/DD/YYYY'
            }
        }, function(start) {
            $('#enddate').val(start.format('MM/DD/YYYY')); // Manually set the selected date for end date
        });

        // DataTable initialization
        var supplierDataTable = $('#supplier_report_data').DataTable({
            oLanguage: {sProcessing: '<div id="page-loader"><div id="page-loader-wrap"><div class="spinner-grow text-primary" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-success" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-danger" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-warning" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-info" role="status"><span class="sr-only">Loading...</span></div><div class="spinner-grow text-light" role="status"><span class="sr-only">Loading...</span></div></div></div>'},
            processing: true,
            serverSide: true, 
            lengthMenu: [40], // Specify the options you want to show
            lengthChange: false, // Hide the "Show X entries" dropdown
            searching:false, 
            pageLength: 40,
            order: [[3, 'desc']],
            ajax: {
                url: '{{ route("report.supplier_filter") }}',
                type: 'POST',
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                data: function (d) {
                    // Pass date range and supplier ID when making the request
                    d.supplier = $('#supplier').val();
                    d.rebate_check = $('input[name="rebate_check"]:checked').val();
                    d.end_date = moment($('#enddate').val(), 'MM/DD/YYYY').format('YYYY-MM-DD');
                    d.start_date = moment($('#startdate').val(), 'MM/DD/YYYY').format('YYYY-MM-DD');
                },
            },

            beforeSend: function() {
                // Show both the DataTables processing indicator and the manual loader before making the AJAX request
                $('.dataTables_processing').show();
                $('#manualLoader').show();
            },

            complete: function(response) {
                // Hide both the DataTables processing indicator and the manual loader when the DataTable has finished loading
                $('.dataTables_processing').hide();
                $('#manualLoader').hide();
                if (businessdataTable.data().count() > 40) {
                    $('#business_data_paginate').show(); // Enable pagination
                } else {
                    $('#business_data_paginate').hide();
                }
            },

            columns: [
                { data: 'supplier', name: 'supplier', title: 'Supplier'},
                { data: 'account_name', name: 'account_name', title: 'Account Name'},
                { data: 'cost', name: 'cost', title: 'Spend'},
                { data: 'volume_rebate', name: 'volume_rebate', title: 'Volume Rebate'},
                { data: 'incentive_rebate', name: 'incentive_rebate', title: 'Incentive Rebate'},
            ],

            fnDrawCallback: function( oSettings ) {
                setPercentage();
            },
        });

        $('#downloadCsvBtn').on('click', function () {
            // Trigger CSV download
            downloadCsv();
        });

    

        function downloadCsv() {
            // You can customize this URL to match your backend route for CSV download
            var csvUrl = '{{ route("report.export-supplier_report-csv") }}', order = supplierDataTable.order(),
            start = moment($('#startdate').val(), 'MM/DD/YYYY').format('YYYY-MM-DD'),
            end = moment($('#enddate').val(), 'MM/DD/YYYY').format('YYYY-MM-DD'),
            rebate_check = $('input[name="rebate_check"]:checked').val();

            // Add query parameters for date range and supplier ID
            csvUrl += '?start_date=' + start + '&end_date=' + end + '&column=' + order[0][0] + '&order=' + order[0][1] + '&supplier=' + $('#supplier').val() + '&rebate_check=' + rebate_check;

            // Open a new window to download the CSV file
            window.open(csvUrl, '_blank');
        } 
    });        
</script>
@endsection