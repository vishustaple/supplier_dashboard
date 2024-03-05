@extends('layout.app', ['pageTitleCheck' => 'Accounts Data'])

 @section('content')
 <div id="layoutSidenav">
    @include('layout.sidenavbar', ['pageTitleCheck' => 'Accounts Data'])
    <div id="layoutSidenav_content" >
        <h3 class="mb-0 ps-2">Manage Commission</h3>
        <div class="row align-items-end border-bottom pb-3 pe-3 mb-4">
            <div class="col-md-12 mb-0 text-end">
                <!-- Button trigger modal -->
                <!-- <a href="{{ route('account.create')}}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Account</a> -->
                
                </div>
        </div> 
        <div class="container">
            <button type="button" name="" id="add_commission" class="btn btn-primary" >+ Add</button>
        <table class="table" id="commission_table">
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
            <td>
                <!-- <button type="button" name="" id="" class="btn btn-danger"> X </button> -->
            </td>
        </tr>
        </tbody>
        </table>
            
    </div>
</div>
</div>
<script>
$(document).ready(function() {    
    $('#add_commission').on('click', function(){
        var count = parseInt($('#commission_table tbody tr:last-child th:first-child').text()) + 1;
        console.log(count);
        $('#commission_table').append('<tr><th scope="row">'+ count +'</th><td>Mark</td><td>Otto</td><td class="center"><button type="button" name="" class=" removeRowBtn btn btn-danger"> X </button></td></tr>');
    });

    $('#commission_table').on('click', '.removeRowBtn', function() {
        $(this).closest('tr').remove();
    });
});
</script>
@endsection