@extends('layout.app', ['pageTitleCheck' => 'Accounts Data'])

 @section('content')
 <div id="layoutSidenav">
    @include('layout.sidenavbar', ['pageTitleCheck' => 'Accounts Data'])
    <div id="layoutSidenav_content" >
    <h3 class="mb-0 ps-3">Edit Customer Name</h3>

<div class="py-5">
    <div class="edit_table container">
   <table class="dataTable no-footer">
    <thead>
        <tr>
        <th>Customer Number</th>
        <th>Customer Name</th>
        <th>Account Name</th>
        <th>Supplier</th>
        <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>1234567890</td>
            <td><input class="form-control" type="text" placeholder="Customer Name"></td>
            <td>Tradewind Energy, Inc.</td>
            <td>Office Depot</td>
            <td><button type="button" class="btn btn-primary" >Save</button></td>
        </tr>
        <tr>
            <td>1234567890</td>
            <td><input class="form-control" type="text" placeholder="Customer Name"></td>
            <td>Tradewind Energy, Inc.</td>
            <td>Office Depot</td>
            <td><button type="button" class="btn btn-primary" >Save</button></td>
        </tr>
        <tr>
            <td>1234567890</td>
            <td><input class="form-control" type="text" placeholder="Customer Name"></td>
            <td>Tradewind Energy, Inc.</td>
            <td>Office Depot</td>
            <td><button type="button" class="btn btn-primary" >Save</button></td>
        </tr>
        <tr>
            <td>1234567890</td>
            <td><input class="form-control" type="text" placeholder="Customer Name"></td>
            <td>Tradewind Energy, Inc.</td>
            <td>Office Depot</td>
            <td><button type="button" class="btn btn-primary" >Save</button></td>
        </tr>
        <tr>
            <td>1234567890</td>
            <td><input class="form-control" type="text" placeholder="Customer Name"></td>
            <td>Tradewind Energy, Inc.</td>
            <td>Office Depot</td>
            <td><button type="button" class="btn btn-primary" >Save</button></td>
        </tr>
    </tbody>
   </table>
    </div>
    </div>

    </div>
 </div>
 @endsection