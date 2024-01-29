<!-- resources/views/excel-import.blade.php -->


@extends('layout.app')
 @extends('layout.sidenav')
 @section('content')

 <div id="layoutSidenav">
    @include('layout.sidenavbar')
    <div id="layoutSidenav_content">
        <div class="mx-auto py-4 d-flex justify-content-between align-items-center">
         <h2 class="mb-0">Accounts Data</h2>
                    <!-- Button trigger modal -->
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal">
                    Add Account
                    </button>

                    <!-- Modal -->
                    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Add Account</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form class="" id="add_supplier">
                                <div class="form-group">
                                    <label>Enter Customer ID </label>
                                    <input type="text" placeholder="Enter Customer Id" class="form-control">
                                </div> 
                                <div class="form-group">
                                    <label>Enter Customer Name</label>
                                    <input type="text" placeholder="Enter Customer name" class="form-control">
                                </div>
                                <div class="form-group">
                                <input type="checkbox" id="parent"  class="radio-checkbox" name="parent" value="1">
                                <label for="parent"> Parent</label><br>
                                <input type="checkbox" id="grandparent"  class="radio-checkbox" name="grandparent" value="0">
                                <label for="grandparent">GrandParent</label><br>
                                </div>
                                <div class="form-group">
                                <label for="selectBox">Grand Parent:</label>
                                <select id="selectBox" name="supplierselect" class="form-control"> 
                                <option value="" selected>--Select--</option>
                                 @if(!empty($grandparent))
                                 @foreach($grandparent as $gp)
                                <option value="{{$gp->id}}">{{$gp->customer_name}}</option>
                              @endforeach
                              @endif
                                </select>
                                </div>
                                <div class="form-group">
                                    <label for="selectBox"> Parent:</label>
                                <select id="selectBox" name="supplierselect" class="form-control"> 
                                <option value="" selected>--Select--</option>
                             
                                <option value=""></option>
                              
                                </select>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary">Save changes</button>
                        </div>
                        </div>
                    </div>
                    </div>
        </div>
        <div class="container">
         
            <table id="account_data" class="data_table_files">
            <!-- Your table content goes here -->
            </table>
        </div>
        
    </div>
</div>
<script>
     $(document).ready(function() {
     $('#account_data').DataTable({
            "paging": true,   // Enable pagination
            "ordering": true, // Enable sorting
            "searching": true, // Enable search
            "data": <?php if(isset($accountsdata)){echo $accountsdata;}  ?>,
            "columns": [
                { title: 'SR. No' },
                { title: 'Account Name' },
                { title: 'Parent Name' },
                { title: 'GrandParent Name' },
                // { title: 'Created At' },
              
            ]
        });
    });
            // JavaScript to make checkboxes act like radio buttons
            const radioCheckboxes = document.querySelectorAll('.radio-checkbox');

            radioCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
            // Uncheck all other checkboxes in the group
            radioCheckboxes.forEach(otherCheckbox => {
            if (otherCheckbox !== checkbox) {
            otherCheckbox.checked = false;
            }
            });
            });
            });
        </script>

@endsection