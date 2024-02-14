


@extends('layout.app', ['pageTitleCheck' => 'Upload Sheets'])

 @section('content')

 <div id="layoutSidenav">
    @include('layout.sidenavbar', ['pageTitleCheck' => 'Upload Sheets'])
    <div id="layoutSidenav_content">
        <div class="m-1 d-md-flex border-bottom pb-3 mb-3 flex-md-row align-items-center justify-content-between">
                <h3 class="mb-0 ps-2">Data Management</h3>
        </div>
        <div class="alert alert-success m-3" id="user_del_success" style="display:none;"></div>
        <div class="container">
            <div class="alert alert-success" id="successMessage" style="display:none;">
            </div>
            <div class="alert alert-danger" id="errorMessage" style="display:none;">
            </div>
        
            @if(session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <form  id="import_form"  enctype="multipart/form-data">
                @csrf
                <div class="row py-4 align-items-end border-bottom mb-3">
                    <div class="form-group col-md-6 mb-0">
                        <label for="selectBox">Select Supplier:</label>
                        <select id="selectBox" name="supplierselect" class="form-control"> 
                        <option value="" selected>--Select--</option>
                        @if(isset($categorySuppliers))
                        @foreach($categorySuppliers as $categorySupplier)
                        <option value="{{ $categorySupplier->id }}">{{ $categorySupplier->supplier_name }}</option>
                        @endforeach
                        @endif
                        </select>
                    </div>
                    <div id="enddates" class="form-group invisible relative col-md-6 mb-0">
                        <label for="enddate">Select Date:</label>
                        <input class="form-control " id="enddate" name="enddate" placeholder="Enter Your End Date " >   
                        <!-- <div class="input-overlay"></div>              -->
                    </div>
                    <div class="form-group relative col-md-6 pt-4 mb-0">
                        <label for="file">Choose Excel File:</label>
                        <input type="file" name="file" id="file" class="form-control">
                        <!-- <div class="input-overlay-file"></div>   -->
                    </div>
                    <div class="col-md-6 pt-4 mb-0 d-flex justify-content-end">
                        <div class="relative imprt_wrapper text-end me-2">
                            <a id="sampleFileDownloadBtn" class="btn btn-primary invisible" href="#"><i class="fa fa-cloud-download" aria-hidden="true"></i> Sample File</a>
                            <!-- <button type="button" class="btn btn-primary invisible" id="sampleFileDownloadBtn"><i class="fa fa-cloud-download" aria-hidden="true"></i> Sample File</button> -->
                        </div>
                        <div class="relative imprt_wrapper text-end me-2">
                            <button type="button" class="btn btn-primary invisible" id="necessaryFieldBtn" data-bs-toggle="modal" data-bs-target="#staticBackdrop"><i class="fa fa-list" aria-hidden="true"></i> Columns List</button>
                        </div>
                        <div class="relative imprt_wrapper text-end">
                            <button type="submit" class="btn btn-primary" id="importBtn"><i class="me-2 fa-solid fa-file-import"></i>Import</button>
                        </div>
                    </div>
                </div>
            </form>
            
            <!-- Modal -->
            <div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-scrollable">
                    <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="staticBackdropLabel">Fields List</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-2">
                        <div class="row list_filed m-3 p-2 border border-secondary">
                    <div class="col-md-9 px-0">
                        <h5 class="list_heading ">Fields</h5>
                        <ul class="list-group" id="necessaryFieldList">
                        </ul>
                    </div>
                    <div class="col-md-3 px-0 ">
                        <h5 class="list_heading">Required</h5>
                        <ul class="list-group ps-3" id="necessaryFieldList1">
                        </ul>
                    </div>
                        </div>
                        
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <!-- <button type="button" class="btn btn-primary">Understood</button> -->
                    </div>
                    </div>
                </div>
            </div>

            <table id="example" class="data_table_files">
            <!-- Your table content goes here -->
            </table>
        </div>
        @include('layout.footer')
    </div>
</div>
<style>
    .spinner {
        margin: 0 auto;
        width: 70px;
        text-align: center;
    }

    .spinner div {
        width: 10px;
        height: 10px;
        background-color: #333;
        border-radius: 100%;
        display: inline-block;
        animation: sk-bouncedelay 1.2s infinite ease-in-out both;
    }

    .spinner  .bounce1 {
        animation-delay: -0.32s;
    }

    .spinner  .bounce2 {
        animation-delay: -0.16s;
    }

    @-webkit-keyframes sk-bouncedelay {
        0%, 80%, 100% {
            transform: scale(0);
        }

        40% {
            transform: scale(1);
        }
    }

    @keyframes sk-bouncedelay {
        0%, 80%, 100% {
            transform: scale(0);
        }

        40% {
            transform: scale(1);
        }
    }
      
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
    .file_td{
        width: 388px;
        display: block;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .list_filed .list-group-item {
        font-size: 14px;
        padding: 5px 10px 5px 10px;
        text-transform: capitalize !important;
        border: 0px;
        min-height: 31px;
    }
    #necessaryFieldList .list-group-item:nth-child(2n),
        #necessaryFieldList1 .list-group-item:nth-child(2n) {
        background-color: #cccccc5e !important;
    }
    .list_filed .list_heading{
        background-color: #b17828; 
        padding: 5px 10px 5px 10px;
        color: #fff;
        font-size: 17px;
    }
    #necessaryFieldList1 .list-group-item {
    color: #008000;
    }
</style>
 <!-- Include Date Range Picker JavaScript -->
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.js"></script>
    </body>
    <script>
    $(document).ready(function() {
        $('#page-loader').hide();
        $('#importBtn').on( "click", function(event) {
            event.preventDefault();
            $('#page-loader').show();
            var formData = new FormData($('#import_form')[0]);
        
            $.ajax({
                type: 'POST',
                url: "{{route('import.excel')}}", // Replace with your actual route name
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    $('html, body').animate({ scrollTop: 0 }, 'slow');
                    if(response.error){
                        $('#page-loader').hide();
                        $('#errorMessage').text(response.error);
                        $('#errorMessage').css('display','block');
                        setTimeout(function () {
                        $('#errorMessage').fadeOut();
                        }, 5000);
                      
                    }
                    let errorMessages = [];

                    if (response && response.error) {
                    $('#page-loader').hide();
                    // Iterate over each field in the error object
                    Object.keys(response.error).forEach(field => {
                    // Get the error messages for the current field
                    let fieldErrorMessages = response.error[field];

                    // Concatenate the field name and its error messages
                    let errorMessageText = `${fieldErrorMessages.join('</br>')}`;
                    console.log(errorMessageText);

                    // Accumulate the error messages
                    errorMessages.push(errorMessageText);
                    });
                    $('#errorMessage').html(errorMessages.join('<br>'));
                    $('#errorMessage').css('display','block');
                    }

                    if(response.success){
                        $('#page-loader').hide();
                        $('#successMessage').text(response.success);
                        $('#successMessage').css('display','block');
                        $("form")[0].reset();
                        //disable all field 
                        $('#enddate,#file,#importBtn').prop('disabled', true);
                        setTimeout(function () {
                        $('#successMessage').fadeOut();
                        location.reload();
                        }, 2000); 
                       
                    }
                    // Handle success response
                    console.log(response);
                },
                error: function(xhr, status, error) {
                    // Handle error response
                    console.error(xhr.responseText);
                }
            });
        });

        $('#selectBox').val('');
       // $('#startdate,#enddate,#file').prop('disabled', true);     
        $('#selectBox').on('change', function() {
            var dataIdValue = $(this).val(); // Replace with your dynamic value
            
            if (dataIdValue == 1) {
                //add rangepicker on field 
                $('#enddate').daterangepicker({  
                    showDropdowns: false,
                    linkedCalendars: false,
                    isInvalidDate: function(date) {
                        // Disable dates more than one month from the selected start date
                        var startDate = $('#enddate').data('daterangepicker').startDate;
                        var endDateLimit = moment(startDate).add(1, 'month');
                        return date.isAfter(endDateLimit);
                    }
                });
            }

            // Creating a multidimensional array
            var multiArray = [
                [],
                ['SOLD TOACCOUNT','SOLD TO NAME','SHIP TOACCOUNT','SHIP TO NAME','SHIP TO ADDRESS','CATEGORIES','SUB GROUP 1','PRODUCT','DESCRIPTION','GREEN (Y/N)','QUANTITYSHIPPED','ON-CORESPEND','OFF-CORESPEND'],            
                ['Track Code', 'Track Code Name', 'Sub track Code', 'Sub Track Code Name','Account Number', 'Account Name', 'Material', 'Material Description','Material Segment', 'Brand Name', 'Bill Date', 'Billing Document','Purchase Order Number', 'Sales Document', 'Name of Orderer', ' Sales Office','Sales Office Name', 'Bill Line No. ', 'Active Price Point', 'Billing Qty','Purchase Amount', 'Freight Billed', 'Tax Billed', 'Total Invoice Price','Actual Price Paid', 'Reference Price', 'Ext Reference Price', 'Diff $','Discount %', 'Invoice Number'],
                ['CUSTOMER GRANDPARENT ID','CUSTOMER GRANDPARENT NM','CUSTOMER PARENT ID','CUSTOMER PARENT NM','CUSTOMER ID','CUSTOMER NM','DEPT','CLASS','SUBCLASS','SKU','Manufacture Item#','Manufacture Name','Product Description','Core Flag','Maxi Catalog/WholesaleFlag','UOM','PRIVATE BRAND','GREEN SHADE','QTY Shipped','Unit Net Price','(Unit) Web Price','Total Spend','Shipto Location','Contact Name','Shipped Date','Invoice #','Payment Method'],
                ['MASTER_CUSTOMER', 'MASTER_NAME', 'BILLTONUMBER', 'BILLTONAME', 'SHIPTONUMBER', 'SHIPTONAME', 'SHIPTOADDRESSLINE1', 'SHIPTOADDRESSLINE2', 'SHIPTOADDRESSLINE3', 'SHIPTOCITY', 'SHIPTOSTATE', 'SHIPTOZIPCODE', 'LASTSHIPDATE', 'SHIPTOCREATEDATE', 'SHIPTOSTATUS', 'LINEITEMBUDGETCENTER', 'CUSTPOREL', 'CUSTPO', 'ORDERCONTACT', 'ORDERCONTACTPHONE', 'SHIPTOCONTACT', 'ORDERNUMBER', 'ORDERDATE', 'SHIPPEDDATE', 'TRANSSHIPTOLINE3', 'SHIPMENTNUMBER', 'TRANSTYPECODE', 'ORDERMETHODDESC', 'PYMTTYPE', 'PYMTMETHODDESC', 'INVOICENUMBER', 'SUMMARYINVOICENUMBER', 'INVOICEDATE', 'CVNCECARDFLAG', 'SKUNUMBER', 'ITEMDESCRIPTION', 'STAPLESADVANTAGEITEMDESCRIPTION', 'SELLUOM', 'QTYINSELLUOM', 'STAPLESOWNBRAND', 'DIVERSITYCD', 'DIVERSITY', 'DIVERSITYSUBTYPECD', 'DIVERSITYSUBTYPE', 'CONTRACTFLAG', 'SKUTYPE', 'TRANSSOURCESYSCD', 'TRANSACTIONSOURCESYSTEM', 'ITEMFREQUENCY', 'NUMBERORDERSSHIPPED', 'QTY', 'ADJGROSSSALES', 'AVGSELLPRICE'],
                ['Customer Num','Customer Name','Item Num','Item Name','Category','Category Umbrella','Price Method','Uo M','Current List','Qty','Ext Price',],
                ['Payer', 'Name Payer', 'Sold-to pt', 'Name Sold-to party', 'Ship-to', 'Name Ship-to', 'Name 3 + Name 4 - Ship-to', 'Street - Ship-to', 'District - Ship-to', 'PostalCode - Ship-to', 'City - Ship-to', 'Country - Ship-to', 'Leader customer 1', 'Leader customer 2', 'Leader customer 3', 'Leader customer 4', 'Leader customer 5', 'Leader customer 6', 'Product hierarchy', 'Section', 'Family', 'Category', 'Sub Category', 'Material', 'Material Description', 'Ownbrand', 'Green product', 'NBS', 'Customer Material', 'Customer description', 'Sales unit', 'Qty. in SKU', 'Sales deal', 'Purchase order type', 'Qty in Sales Unit - P', 'Quantity in SKU - P', 'Number of orders - P', 'Sales Amount - P', 'Tax amount - P', 'Net sales - P', 'Avg Selling Price - P', 'Document Date', 'Sales Document', 'PO number', 'BPO number', 'Invoice list', 'Billing Document', 'Billing Date', 'CAC number', 'CAC description', 'Billing month - P'],
                ['GP ID','GP Name','202301','202302','202303','202304','202305','202306','202307','202308','202309','202310','202311','202312','202313','202314','202315','202316','202317','202318','202319','202320','202321','202322','202323','202324','202325','202326','2023027','202328','202329','202330','202331','202332','202333','202334','202335','202336','202337','202338','202339','202340','202341','202342','202343','202344','202345','202346','202347','202348','202349','202350','202351','202352'],
                ['CUSTOMER GRANDPARENT ID','CUSTOMER GRANDPARENT NM','CUSTOMER PARENT ID','CUSTOMER PARENT NM','CUSTOMER ID','CUSTOMER NM','DEPT','CLASS','SUBCLASS','SKU','Manufacture Item#','Manufacture Name','Product Description','Core Flag','Maxi Catalog/WholesaleFlag','UOM','PRIVATE BRAND','GREEN SHADE','QTY Shipped','Unit Net Price','(Unit) Web Price','Total Spend','Shipto Location','Contact Name','Shipped Date','Invoice #','Payment Method'],
            ],

            multiArray1 = [
                [],
                ['SOLD TO NAME', 'SOLD TOACCOUNT', 'ON-CORESPEND', 'OFF-CORESPEND'],
                ['Track Code', 'Track Code Name', 'Sub track Code', 'Sub Track Code Name', 'Account Name', 'Account Number', 'Actual Price Paid', 'Invoice Number', 'Bill Date'],
                ['CUSTOMER GRANDPARENT ID', 'CUSTOMER GRANDPARENT NM', 'CUSTOMER PARENT ID', 'CUSTOMER PARENT NM', 'CUSTOMER ID', 'Total Spend', 'Invoice #', 'Shipped Date'],
                ['MASTER_CUSTOMER', 'MASTER_CUSTOMER', 'ADJGROSSSALES', 'INVOICENUMBER', 'INVOICEDATE'],
                ['Customer Name', 'Customer Num', 'Current List', 'Invoice Num', 'Invoice Date'],
                ['Leader customer 2', 'Leader customer 3', 'Leader customer 4', 'Leader customer 5', 'Leader customer 6', 'Leader customer 1', 'Sales Amount - P', 'Billing Document', 'Billing Date'],
                ['Account ID'],
                ['CUSTOMER GRANDPARENT ID', 'CUSTOMER GRANDPARENT NM', 'CUSTOMER PARENT ID', 'CUSTOMER PARENT NM', 'CUSTOMER ID', 'Total Spend', 'Invoice #', 'Shipped Date'],
            ],

            // Define the list items content (you can fetch this dynamically if needed)
            listItemsContent = multiArray[dataIdValue],
            multiArray2 = multiArray1[dataIdValue];

            // Clear existing list items (if any)
            $("#necessaryFieldList, #necessaryFieldList1").empty();

            // Add new list items
            $.each(listItemsContent, function(index, content) {
                $("#necessaryFieldList").append("<li class='list-group-item'>" + content + "</li>");
                let containsKey = multiArray2.includes(content);
                if (containsKey) {
                    $("#necessaryFieldList1").append("<li class='list-group-item'><i class='fa-solid fa-check'></i></li>");
                } else {
                    $("#necessaryFieldList1").append("<li class='list-group-item'></li>");
                }
            });

            if (dataIdValue != '') {
                $('#necessaryFieldBtn').removeClass('invisible');
                $('#sampleFileDownloadBtn').removeClass('invisible');
                var xlsxUrl = "{{ route('file.download') }}/"+dataIdValue;

                // Set the href attribute of the anchor tag
                $('#sampleFileDownloadBtn').attr('href', xlsxUrl);
            } else {
                $('#sampleFileDownloadBtn').addClass('invisible');
                $('#necessaryFieldBtn').addClass('invisible');
            }

            var startDateInput = $('#enddate');
            if ($(this).val() == '1') {
                $(".input-overlay").css("display","none");
                // startDateInput.prop('disabled', false);
                $('#enddates').removeClass('invisible');
            } else {
                $(".input-overlay").css("position","absolute");
                $('#enddates').addClass('invisible');
            }
            var selectedSupplier = $(this).val();
        });

        $('#enddate').val('');
        $('#enddate').on('change', function() {
            var EndDateInput = $('#file');  // Assuming you want to check the value of #file
            
            if ($(this).val().trim() !== '') {
                $(".input-overlay-file").css("display","none");
                EndDateInput.prop('disabled', false);
            } else {
                $(".input-overlay-file").css("position","absolute");
                EndDateInput.prop('disabled', true);
            }
        });

         var exportTable =  $('#example').DataTable({
            "paging": true,   // Enable pagination
            "ordering": true, // Enable sorting
            "searching": true, // Enable search
            "lengthChange":false,
            "pageLength": 40,
            "data": <?php if(isset($data)){ echo $data; }  ?>,
            "columns": [
                { title: 'Supplier Name' },
                { title: 'File Name' },
                { title: 'Processing' },
                { title: 'Uploaded By' },
                { title: 'Date' },
                { title: 'Action' },
                // Add more columns as needed
            ],
            "rowCallback": function(row, data, index) {
                // Loop through each cell in the row
                $('td', row).each(function() {
                    // Check if the cell contains a button with a specific class
                    if ($(this).find('button.invisible').length) {
                        $(row).css('background-color','#f09b9b');
                    }
                });
            }
            
        });
        if (exportTable.data().count() > 40) {
            // console.log("here");
            $('#example_paginate').show(); // Enable pagination
        } else {
            console.log("here");
            $('#example_paginate').hide();
        }
        
        $(document).on('click','.remove',function(){               
            var id = $(this).attr('data-id');
            
            swal.fire({
                title: "Oops....",
                text: "Are you sure you want to delete this file?",
                icon: "error",
                showCancelButton: true,
                confirmButtonText: 'YES',
                cancelButtonText: 'NO',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    $(this).attr('disabled', true); // Disable the element
                    window.location.href = "{{ route('upload.delete') }}/"+id
                } 
            });
        });
    });
</script>
</html>

@endsection