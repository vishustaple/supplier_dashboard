<!-- resources/views/excel-import.blade.php -->


@extends('layout.app')

 @section('content')
 <div id="layoutSidenav">
    @include('layout.sidenavbar', ['pageTitleCheck' => 'view Data'])
    <div id="layoutSidenav_content">
        <h3 class="mb-0 ps-2">View Detail</h3>
        <div class="row align-items-end border-bottom pb-3 mb-4">
            <div class="col-md-12 mb-0 text-end">
            @if (isset($orders) && !empty($orders))
            <a href="{{ route('report.back') }}" class="btn btn-secondary">Back</a>
            @endif
            @if (isset($account) && !empty($account))
            <a href="{{ route('account') }}" class="btn btn-secondary">Back</a>
            @endif
            <!-- Add redirect conditions here -->
            </div>
        </div>
       
        <div class="container">
            @if (isset($orders) && !empty($orders))
                <div class="row_details">
                    @if (isset($orders->customer_number) && !empty($orders->customer_number))    
                    <p><b>Customer Number</b>: {{ $orders->customer_number }}</p>
                    @endif

                    @if (isset($orders->customer_name) && !empty($orders->customer_name))
                    <p><b>Customer Name</b>: {{ $orders->customer_name??'null' }}</p>
                    @endif

                    @if (isset($orders->supplier_name) && !empty($orders->supplier_name))
                    <p><b>Supplier Name</b>: {{ $orders->supplier_name }}</p>
                    @endif

                    @if (isset($orders->amount) && !empty($orders->amount))
                    <p><b>Amount</b>: {{ '$'.$orders->amount }}</p>
                    @endif

                    @if (isset($data->date) && !empty($data->date))
                    <p><b>Date</b>: {{ date_format(date_create($data->date), 'm/d/Y') }}</p>
                    @endif

                    @if (isset($orders->internal_reporting_name) && !empty($orders->internal_reporting_name))
                    <p><b>Internal Reporting Name</b>: {{ $orders->internal_reporting_name ?? 'null' }}</p>
                    @endif

                    @if (isset($orders->qbr) && !empty($orders->qbr))
                    <p><b>QBR</b>: {{ $orders->qbr ??'null' }}</p>
                    @endif

                    @if (isset($orders->supplier_acct_rep) && !empty($orders->supplier_acct_rep))
                    <p><b>Supplier Acc Rep</b>: {{ $orders->supplier_acct_rep??'null' }}</p>
                    @endif

                    @if (isset($orders->management_fee) && !empty($orders->management_fee))
                    <p><b>Mangement Fee</b>: {{ $orders->management_fee??'null'  }}</p>
                    @endif

                    @if (isset($orders->record_type) && !empty($orders->record_type))
                    <p><b>Record type </b>: {{ $orders->record_type ??'null' }}</p>
                    @endif

                    @if (isset($orders->cpg_sales_representative) && !empty($orders->cpg_sales_representative))
                    <p><b>CPG Sales Representative </b>: {{ $orders->cpg_sales_representative??'null'  }}</p>
                    @endif

                    @if (isset($orders->cpg_customer_service_rep) && !empty($orders->cpg_customer_service_rep))
                    <p><b>CPG Customer Service Rep </b>: {{ $orders->cpg_customer_service_rep??'null'  }}</p>
                    @endif

                    @if (isset($orders->sf_cat) && !empty($orders->sf_cat))
                    <p><b>SF Cat</b>: {{ $orders->sf_cat??'null'  }}</p>
                    @endif

                    @if (isset($orders->rebate_freq) && !empty($orders->rebate_freq))
                    <p><b>Rebate Freq</b>: {{ $orders->rebate_freq	??'null'  }}</p>
                    @endif

                    @if (isset($orders->member_rebate) && !empty($orders->member_rebate))
                    <p><b>Member Rebate</b>: {{ $orders->member_rebate??'null'  }}</p>
                    @endif

                    @if (isset($orders->comm_rate) && !empty($orders->comm_rate))
                    <p><b>Comm Rate</b>: {{ $orders->comm_rate??'null'  }}</p>
                    @endif

                </div>
            @endif

            @if (isset($account) && !empty($account))
                <div class="row_details">
                    @if (isset($account->qbr) && !empty($account->qbr))    
                    <p><b>Qbr</b>: {{ $account->qbr }}</p>
                    @endif
                    @if (isset($account->alies) && !empty($account->alies))
                    <p><b>Customer Name</b>: {{ $account->alies}}</p>
                    @endif
                    @if (isset($account->sf_cat) && !empty($account->sf_cat))
                    <p><b>Sf Cat</b>: {{ $account->sf_cat }}</p>
                    @endif
                    @if (isset($account->comm_rate) && !empty($account->comm_rate))
                    <p><b>Comm Rate</b>: {{ $account->comm_rate }}</p>
                    @endif
                    @if (isset($account->spend_name) && !empty($account->spend_name))
                    <p><b>Spend Name</b>: {{ $account->spend_name}}</p>
                    @endif
                    @if (isset($account->rebate_freq) && !empty($account->rebate_freq))
                    <p><b>Rebate Freq</b>: {{ $account->rebate_freq }}</p>
                    @endif
                    @if (isset($account->record_type) && !empty($account->record_type))
                    <p><b>Record Type</b>: {{ $account->record_type}}</p>
                    @endif
                    @if (isset($account->account_name) && !empty($account->account_name))
                    <p><b>Account Name</b>: {{ $account->account_name }}</p>
                    @endif
                    @if (isset($account->member_rebate) && !empty($account->member_rebate))
                    <p><b>Member Rebate</b>: {{ $account->member_rebate }}</p>
                    @endif
                    @if (isset($account->temp_end_date) && !empty($account->temp_end_date))
                    <p><b>Temp End Date</b>: {{  date_format(date_create($account->temp_end_date), 'm/d/Y') }}</p>
                    @endif
                    @if (isset($account->volume_rebate) && !empty($account->volume_rebate))
                    <p><b>Volume Rebate</b>: {{ $account->volume_rebate}}</p>
                    @endif
                    @if (isset($account->management_fee) && !empty($account->management_fee))
                    <p><b>Management Fee</b>: {{ $account->management_fee }}</p>
                    @endif
                    @if (isset($account->customer_number) && !empty($account->customer_number))
                    <p><b>Customer Number</b>: {{ $account->customer_number}}</p>
                    @endif
                    @if (isset($account->temp_active_date) && !empty($account->temp_active_date))
                    <p><b>Temp Active Date</b>: {{  date_format(date_create($account->temp_active_date), 'm/d/Y')}}</p>
                    @endif
                    @if (isset($account->category_supplier) && !empty($account->category_supplier))
                    <p><b>Category Supplier</b>: {{ $account->category_supplier}}</p>
                    @endif
                    @if (isset($account->supplier_acct_rep) && !empty($account->supplier_acct_rep))
                    <p><b>Supplier Acct Rep</b>: {{ $account->supplier_acct_rep }}</p>
                    @endif
                    @if (isset($account->parent_name) && !empty($account->parent_name))
                    <p><b>Parent Name</b>: {{ $account->parent_name }}</p>
                    @endif
                    @if (isset($account->grand_parent_name) && !empty($account->grand_parent_name))
                    <p><b>Grand Parent Name</b>: {{ $account->grand_parent_name }}</p>
                    @endif
                    @if (isset($account->sales_representative) && !empty($account->sales_representative))
                    <p><b>Sales Representative</b>: {{ $account->sales_representative }}</p>
                    @endif
                    @if (isset($account->internal_reporting_name) && !empty($account->internal_reporting_name))
                    <p><b>Internal Reporting Name</b>: {{ $account->internal_reporting_name }}</p>
                    @endif
                    @if (isset($account->cpg_sales_representative) && !empty($account->cpg_sales_representative))
                    <p><b>Cpg Sales Representative</b>: {{ $account->cpg_sales_representative}}</p>
                    @endif
                    @if (isset($account->cpg_customer_service_rep) && !empty($account->cpg_customer_service_rep))
                    <p><b>Cpg Customer Service Rep</b>: {{ $account->cpg_customer_service_rep}}</p>
                    @endif
                    @if (isset($account->customer_service_representative) && !empty($account->customer_service_representative))
                    <p><b>Customer Service Representative</b>: {{ $account->customer_service_representative}}</p>
                    @endif
                </div>
            @endif

            @if (isset($catalog) && !empty($catalog))
                @foreach ($catalog as $key => $value)
                    <div class="row_details">
                        @if (!isset($a) && empty($a))
                            <p><b>Sku</b>: {{ $value['sku'] }}</p>
                            <p><b>Price</b>: {{ $value['price'] }}</p>
                            <p><b>Supplier Name</b>: {{ $value['supplier_name'] }}</p>
                            <p><b>Description</b>: {{ $value['description'] }}</p>
                        @endif
                        <?php $a=1; ?>
                        <p><b>{{ $value['key'] }}</b>: {{ $value['value'] }}</p>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</div>
<script>
 
</script>

@endsection