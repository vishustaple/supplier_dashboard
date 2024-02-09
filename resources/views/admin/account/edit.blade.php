@extends('layout.app', ['pageTitleCheck'=> (($currentTitle == 'Edit Account Data')?'Edit Account Data':'')])

 @section('content')
 <div id="layoutSidenav">
    @include('layout.sidenavbar', ['pageTitleCheck'=> (($fromTitle == 'account')?'Accounts Data':'')] )
    <div id="layoutSidenav_content" >
        <h3 class="mb-0 ps-2">Edit Data</h3>
        <div class="row align-items-end border-bottom pb-3 mb-4">
            <div class="col-md-12 mb-0 text-end">
                <a href="{{ route('account') }}"  class="btn btn-primary border-0 bg_yellow" title="Back"><i class="fas fa-arrow-left me-2"></i>Back</a>
            </div>
        </div>
      
        <div class="alert alert-success" id="successMessage" style="display:none;">
            </div>
            <div class="alert alert-danger" id="errorMessage" style="display:none;">
            </div>
            <form class="px-4" id="edit_account" method="post">
                @csrf
                <div class="row">
                <div class="form-group col-md-6">
                    <label>Customer ID </label>
                    <input type="text" placeholder="Enter Customer Id" class="form-control" name="customer_id" id="customer_id">
                </div> 
                <div class="form-group col-md-6">
                    <label>Customer Name</label>
                    <input type="text" placeholder="Enter Customer name" class="form-control" name="customer_name" id="customer_name">
                </div>
                <!-- <div class="form-group col-md-6">
                   
                </div> -->
                <div class="form-group col-md-6">
                <div class="form-check form-check-inline">
                    <input type="checkbox" id="parent" class="form-check-input radio-checkbox" name="parent" value="1">
                    <label class="form-check-label" for="parent">Parent</label>
                    </div>
                    <label for="selectBox" class="d-block">Grand Parent:</label>
                    <select id="grandparentSelect" name="grandparentSelect" class="form-control" disabled> 
                        <option value="" selected>--Select--</option>
                        @if(!empty($grandparent))
                            @foreach($grandparent as $gp)
                            <option value="{{ $gp->id }}">{{ $gp->alies }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="form-group col-md-6 mt-4">
                    <label>Account Name</label>
                    <input type="text" placeholder="Enter Account Name" class="form-control" name="account_name">
                </div>
                <div class="form-group col-md-6">
                    <label>Volume Rebate</label>
                    <input type="text" placeholder="Enter Volume Rebate" class="form-control" name="volume_rebate">
                </div>
                <div class="form-group col-md-6">
                    <label>Sales Representative</label>
                    <input type="text" placeholder="Enter Sales Representative" class="form-control" name="sales_representative">
                </div>
                <div class="form-group col-md-6">
                    <label>Customer Service Representative</label>
                    <input type="text" placeholder="Enter Customer Service Representative" class="form-control" name="customer_service_representative">
                </div>
                <div class="form-group col-md-6">
                    <label>Member Rebate</label>
                    <input type="text" placeholder="Enter Member Rebate" class="form-control" name="member_rebate">
                </div>
                <div class="form-group col-md-6">
                    <label>Temporary Active Date</label>
                    <input type="date" class="form-control" name="temp_active_date">
                </div>
                <div class="form-group col-md-6">
                    <label>Temporary End Date</label>
                    <input type="date" class="form-control" name="temp_end_date">
                </div>
                <div class="form-group col-md-6">
                    <label>Internal Reporting Name</label>
                    <input type="text" placeholder="Enter Internal Reporting Name" class="form-control" name="internal_reporting_name">
                </div>
                <div class="form-group col-md-6">
                    <label>QBR</label>
                    <input type="text" placeholder="Enter QBR" class="form-control" name="qbr">
                </div>
                <div class="form-group col-md-6">
                    <label>Spend Name</label>
                    <input type="text" placeholder="Enter Spend Name" class="form-control"          name="spend_name">
                </div>
                <div class="form-group col-md-6">
                    <label>Supplier Account Representative</label>
                    <input type="text" placeholder="Enter Supplier Account Representative" class="form-control" name="supplier_acct_rep">
                </div>
                <div class="form-group col-md-6">
                    <label>Management Fee</label>
                    <input type="text" placeholder="Enter Management Fee" class="form-control" name="management_fee">
                </div>
                <div class="form-group col-md-6">
                    <label>Record Type</label>
                    <input type="text" placeholder="Enter Record Type" class="form-control" name="record_type">
                </div>
                <div class="form-group col-md-6">
                    <label>Category Supplier</label>
                    <input type="text" placeholder="Enter Category Supplier" class="form-control" name="category_supplier">
                </div>
                <div class="form-group col-md-6">
                    <label>CPG Sales Representative</label>
                    <input type="text" placeholder="Enter CPG Sales Representative" class="form-control" name="cpg_sales_representative">
                </div>
                <div class="form-group col-md-6">
                    <label>CPG Customer Service Representative</label>
                    <input type="text" placeholder="Enter CPG Customer Service Representative" class="form-control" name="cpg_customer_service_rep">
                </div>
                <div class="form-group col-md-6">
                    <label>SF Cat</label>
                    <input type="text" placeholder="Enter SF Cat" class="form-control" name="sf_cat">
                </div>
                <div class="form-group col-md-6">
                    <label>Rebate Frequency</label>
                    <input type="text" placeholder="Enter Rebate Frequency" class="form-control" name="rebate_freq">
                </div>
                <div class="form-group col-md-6">
                    <label>Commission Rate</label>
                    <input type="text" placeholder="Enter Commission Rate" class="form-control" name="comm_rate">
                </div>
                </div>
                <div class="text-center py-3">
                <button type="submit" class="btn btn-primary mx-auto" id="supplier_add">Submit</button>
                </div>

            </form>
        
    </div>
</div>
<script>

    
</script>

@endsection