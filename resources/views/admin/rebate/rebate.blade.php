@extends('layout.app', ['pageTitleCheck' => 'Accounts Data'])

 @section('content')
 <div id="layoutSidenav">
    @include('layout.sidenavbar', ['pageTitleCheck' => 'Accounts Data'])
    <div id="layoutSidenav_content" >
        <h3 class="mb-0 ps-2">Manage Rebate</h3>
        <div class="row align-items-end border-bottom pb-3 pe-3 mb-4">
            <div class="col-md-12 mb-0 text-end">
                <!-- Button trigger modal -->
                <!-- <a href="{{ route('account.create')}}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Account</a> -->
                
                </div>
        </div> 
        <div class="container">
        <table class="table">
        <thead>
            <tr>
                <th scope="col">Account Number</th>
                <th scope="col">Customer Name</th>
                <th scope="col">Supplier</th>
                <th scope="col">Volume Rebate</th>
                <th scope="col">Incentive Rebate</th>
                <th scope="col">Action</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <th scope="row">432423423</th>
                <td>Center point</td>
                <td>Office Depot</td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="" id="" aria-describedby="helpId" placeholder="" />
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="" id="" aria-describedby="helpId" placeholder="" />
                </td>
                <td class="center">
                    <button type="button" class="btn btn-success"> Save </button>
                </td>
            </tr>
            <tr>
                <th scope="row">465756657</th>
                <td>Center point2</td>
                <td>Grainger</td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="" id="" aria-describedby="helpId" placeholder="" />
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="" id="" aria-describedby="helpId" placeholder="" />
                </td>
                <td class="center">
                    <button type="button" class="btn btn-success"> Save </button>
                </td>
            </tr>
            <tr>
                <th scope="row">545435454</th>
                <td>Center point3</td>
                <td>Lryco</td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="" id="" aria-describedby="helpId" placeholder="" />
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="" id="" aria-describedby="helpId" placeholder="" />
                </td>
                <td class="center">
                    <button type="button" class="btn btn-success"> Save </button>
                </td>
            </tr>
        </tbody>
        </table>
    </div>
</div>
</div>
@endsection