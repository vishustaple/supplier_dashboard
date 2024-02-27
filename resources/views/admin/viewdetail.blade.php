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
            <a href="{{ route('report.back') }}" class="btn btn-secondary border-0 bg_yellow"><i class="fas fa-arrow-left me-2"></i>Back</a>
            @endif
            @if (isset($account) && !empty($account))
            <a href="{{ route('account') }}" class="btn btn-secondary border-0 bg_yellow" ><i class="fas fa-arrow-left me-2"></i>Back</a>
            @endif
            @if (isset($salesData) && !empty($salesData))
            <a href="{{ route('sales.index') }}" class="btn btn-secondary border-0 bg_yellow" ><i class="fas fa-arrow-left me-2"></i>Back</a>
            @endif
            <!-- Add redirect conditions here -->
            </div>
        </div>
        <div class="container">
            @if (isset($orders) && !empty($orders))
            <div class="row">
                <div class="col-md-8">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Column</th>
                            <th scope="col">Values</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            @if (isset($orders->customer_number) && !empty($orders->customer_number))    
                            <th scope="row">Customer Number</th>
                            <td> {{ $orders->customer_number }}</td>
                            @endif
                            <tr>
                            </tr>
                            @if (isset($orders->customer_name) && !empty($orders->customer_name))
                            <th scope="row">Customer Name</th>
                            <td> {{ $orders->customer_name??'null' }}</td>
                            @endif
                            <tr>
                            </tr>
                            @if (isset($orders->supplier_name) && !empty($orders->supplier_name))
                            <th scope="row">Supplier Name</th>
                            <td> {{ $orders->supplier_name }}</td>
                            @endif
                            <tr>
                            </tr>
                            @if (isset($orders->amount) && !empty($orders->amount))
                            <th scope="row">Amount</th>
                            <td> {{ '$'.$orders->amount }}</td>
                            @endif
                            <tr>
                            </tr>
                            @if (isset($data->date) && !empty($data->date))
                            <th scope="row">Date</th>
                            <td> {{ date_format(date_create($data->date), 'm/d/Y') }}</td>
                            @endif
                            <tr>
                            </tr>
                            @if (isset($orders->internal_reporting_name) && !empty($orders->internal_reporting_name))
                            <th scope="row">Internal Reporting Name</th>
                            <td> {{ $orders->internal_reporting_name ?? 'null' }}</td>
                            @endif
                            <tr>
                            </tr>
                            @if (isset($orders->qbr) && !empty($orders->qbr))
                            <th scope="row">QBR</th>
                            <td> {{ $orders->qbr ??'null' }}</td>
                            @endif
                            <tr>
                            </tr>
                            @if (isset($orders->supplier_acct_rep) && !empty($orders->supplier_acct_rep))
                            <th scope="row">Supplier Acc Rep</th>
                            <td> {{ $orders->supplier_acct_rep??'null' }}</td>
                            @endif
                            <tr>
                            </tr>
                            @if (isset($orders->management_fee) && !empty($orders->management_fee))
                            <th scope="row">Mangement Fee</th>
                            <td> {{ $orders->management_fee??'null'  }}</td>
                            @endif
                            <tr>
                            </tr>
                            @if (isset($orders->record_type) && !empty($orders->record_type))
                            <th scope="row">Record type </th>
                            <td> {{ $orders->record_type ??'null' }}</td>
                            @endif
                            <tr>
                            </tr>
                            @if (isset($orders->cpg_sales_representative) && !empty($orders->cpg_sales_representative))
                            <th scope="row">CPG Sales Representative </th>
                            <td> {{ $orders->cpg_sales_representative??'null'  }}</td>
                            @endif
                            <tr>
                            </tr>
                            @if (isset($orders->cpg_customer_service_rep) && !empty($orders->cpg_customer_service_rep))
                            <th scope="row">CPG Customer Service Rep </th>
                            <td> {{ $orders->cpg_customer_service_rep??'null'  }}</td>
                            @endif
                            <tr>
                            </tr>
                            @if (isset($orders->sf_cat) && !empty($orders->sf_cat))
                            <th scope="row">SF Cat</th>
                            <td> {{ $orders->sf_cat??'null'  }}</td>
                            @endif
                            <tr>
                            </tr>
                            @if (isset($orders->rebate_freq) && !empty($orders->rebate_freq))
                            <th scope="row">Rebate Freq</th>
                            <td> {{ $orders->rebate_freq	??'null'  }}</td>
                            @endif
                            <tr>
                            </tr>
                            @if (isset($orders->member_rebate) && !empty($orders->member_rebate))
                            <th scope="row">Member Rebate</th>
                            <td> {{ $orders->member_rebate??'null'  }}</td>
                            @endif
                            <tr>
                            </tr>
                            @if (isset($orders->comm_rate) && !empty($orders->comm_rate))
                            <th scope="row">Comm Rate</th>
                            <td> {{ $orders->comm_rate??'null'  }}</td>
                            @endif
                       </tr>
                    </tbody>
                </table>
            </div>
            </div>
            @endif
            @if (isset($account) && !empty($account))
            <div class="row">
                <div class="col-md-8">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Column</th>
                            <th scope="col">Values</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            @if (isset($account->qbr) && !empty($account->qbr))    
                            <th scope="row">Qbr</th>
                            <td>{{ $account->qbr }}</td>
                            @endif
                            <tr>
                            </tr>
                            @if (isset($account->alies) && !empty($account->alies))
                            <th scope="row">Customer Name</th>
                            <td>{{ $account->alies}}</td>
                            @endif
                            <tr>
                            </tr>
                            @if (isset($account->sf_cat) && !empty($account->sf_cat))
                            <th scope="row">Sf Cat</th>
                            <td>{{ $account->sf_cat }}</td>
                            @endif
                            <tr>
                            </tr>
                            @if (isset($account->comm_rate) && !empty($account->comm_rate))
                            <th scope="row">Comm Rate</th>
                            <td>{{ $account->comm_rate }}</td>
                            @endif
                            <tr>
                            </tr>
                            @if (isset($account->spend_name) && !empty($account->spend_name))
                            <th scope="row">Spend Name</th>
                            <td>{{ $account->spend_name}}</td>
                            @endif
                            <tr>
                            </tr>
                            @if (isset($account->rebate_freq) && !empty($account->rebate_freq))
                            <th scope="row">Rebate Freq</th>
                            <td>{{ $account->rebate_freq }}</td>
                            @endif
                            <tr>
                            </tr>
                            @if (isset($account->record_type) && !empty($account->record_type))
                            <th scope="row">Record Type</th>
                            <td>{{ $account->record_type}}</td>
                            @endif
                            <tr>
                            </tr>
                            @if (isset($account->account_name) && !empty($account->account_name))
                            <th scope="row">Account Name</th>
                            <td>{{ $account->account_name }}</td>
                            @endif
                            <tr>
                            </tr>
                            @if (isset($account->member_rebate) && !empty($account->member_rebate))
                            <th scope="row">Member Rebate</th>
                            <td>{{ $account->member_rebate }}</td>
                            @endif
                            <tr>
                            </tr>
                            @if (isset($account->temp_end_date) && !empty($account->temp_end_date))
                            <th scope="row">Temp End Date</th>
                            <td>{{  date_format(date_create($account->temp_end_date), 'm/d/Y') }}</td>
                            @endif
                            <tr>
                            </tr>
                            @if (isset($account->volume_rebate) && !empty($account->volume_rebate))
                            <th scope="row">Volume Rebate</th>
                            <td>{{ $account->volume_rebate}}</td>
                            @endif
                            <tr>
                            </tr>
                            @if (isset($account->management_fee) && !empty($account->management_fee))
                            <th scope="row">Management Fee</th>
                            <td>{{ $account->management_fee }}</td>
                            @endif
                            <tr>
                            </tr>
                            @if (isset($account->customer_number) && !empty($account->customer_number))
                            <th scope="row">Customer Number</th>
                            <td>{{ $account->customer_number}}</td>
                            @endif
                            <tr>
                            </tr>
                            @if (isset($account->temp_active_date) && !empty($account->temp_active_date))
                            <th scope="row">Temp Active Date</th>
                            <td>{{  date_format(date_create($account->temp_active_date), 'm/d/Y')}}</td>
                            @endif
                            <tr>
                            </tr>
                            @if (isset($account->category_supplier) && !empty($account->category_supplier))
                            <th scope="row">Category Supplier</th>
                            <td>{{ $account->category_supplier}}</td>
                            @endif
                            <tr>
                            </tr>
                            @if (isset($account->supplier_acct_rep) && !empty($account->supplier_acct_rep))
                            <th scope="row">Supplier Acct Rep</th>
                            <td>{{ $account->supplier_acct_rep }}</td>
                            @endif
                            <tr>
                            </tr>
                            @if (isset($account->parent_name) && !empty($account->parent_name))
                            <th scope="row">Parent Name</th>
                            <td>{{ $account->parent_name }}</td>
                            @endif
                            <tr>
                            </tr>
                            @if (isset($account->grand_parent_name) && !empty($account->grand_parent_name))
                            <th scope="row">Grand Parent Name</th>
                            <td>{{ $account->grand_parent_name }}</td>
                            @endif
                            <tr>
                            </tr>
                            @if (isset($account->sales_representative) && !empty($account->sales_representative))
                            <th scope="row">Sales Representative</th>
                            <td>{{ $account->sales_representative }}</td>
                            @endif
                            <tr>
                            </tr>
                            @if (isset($account->internal_reporting_name) && !empty($account->internal_reporting_name))
                            <th scope="row">Internal Reporting Name</th>
                            <td>{{ $account->internal_reporting_name }}</td>
                            @endif
                            <tr>
                            </tr>
                            @if (isset($account->cpg_sales_representative) && !empty($account->cpg_sales_representative))
                            <th scope="row">Cpg Sales Representative</th>
                            <td>{{ $account->cpg_sales_representative}}</td>
                            @endif
                            <tr>
                            </tr>
                            @if (isset($account->cpg_customer_service_rep) && !empty($account->cpg_customer_service_rep))
                            <th scope="row">Cpg Customer Service Rep</th>
                            <td>{{ $account->cpg_customer_service_rep}}</td>
                            @endif
                            <tr>
                            </tr>
                            @if (isset($account->customer_service_representative) && !empty($account->customer_service_representative))
                            <th scope="row">Customer Service Representative</th>
                            <td>{{ $account->customer_service_representative}}</td>
                            @endif
                        </tr>
                    </tbody>
                </table>
            </div>
            </div>
            @endif

            @if (isset($catalog) && !empty($catalog))
            <div class="row">
                <div class="col-md-8">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">Column</th>
                        <th scope="col">Values</th>
                    </tr>
                </thead>
                <tbody>                   
                    @foreach ($catalog as $key => $value)
                        @if (!isset($a) && empty($a))
                        <tr>
                            <th scope="row">Sku</th>
                            <td>{{ $value['sku'] }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Price</th>
                            <td>{{ $value['price'] }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Supplier Name</th>
                            <td>{{ $value['supplier_name'] }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Description</th>
                            <td>{{ $value['description'] }}</td>
                        </tr>
                        @endif
                        <?php $a=1; ?>
                        <tr>
                            <th scope="row">{{ $value['key'] }}</th><td>{{ $value['value'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
            </div>
            @endif
            <?php //echo"<pre>"; print_r($salesData);?>
            @if (isset($salesData) && !empty($salesData))
            <div class="row">
                <div class="col-md-8">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Column</th>
                            <th scope="col">Values</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($salesData as $key => $value)
                        @if ((isset($value['first_name']) && !empty($value['first_name'])))
                            <tr>
                                <th scope="row">Name</th>
                                <td>{{ $value['first_name'] . ' ' . $value['last_name'] }}</td>
                            </tr>
                        @endif
                        @if (isset($value['phone']) && !empty($value['phone']))
                            <tr>
                                <th scope="row">Phone</th>
                                <td>{{ $value['phone'] }}</td>
                            </tr>
                        @endif
                        @if (isset($value['email']) && !empty($value['email']))
                            <tr>
                                <th scope="row">Email</th>
                                <td>{{ $value['email'] }}</td>
                            </tr>
                        @endif
                        @if (isset($value['status']) && !empty($value['status']))
                            <tr>
                                <th scope="row">Status</th>
                                <td>{{ $value['status'] == 1 ? 'Active' : 'Inactive' }}</td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
            </div>
            </div>
            @endif
        </div>
    </div>
</div>
<script>
 
</script>

@endsection