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
            <th scope="col">#</th>
            <th scope="col">First</th>
            <th scope="col">Last</th>
            <th scope="col">Handle</th>
            </tr>
        </thead>
        <tbody>
            <tr>
            <th scope="row">1</th>
            <td>Mark</td>
            <td>Otto</td>
            <td class="center">
                <button type="button" name="" id="" class="btn btn-danger"> X </button>
            
            </td>
            </tr>
            <tr>
            <th scope="row">2</th>
            <td>Jacob</td>
            <td>Thornton</td>
            <td>@fat</td>
            </tr>
            <tr>
            <th scope="row">3</th>
            <td colspan="2">Larry the Bird</td>
            <td>@twitter</td>
            </tr>
        </tbody>
        </table>
    </div>
</div>
</div>
@endsection