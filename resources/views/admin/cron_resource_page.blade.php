@extends('layout.app', ['pageTitleCheck' => $pageTitle])
@section('content')
    <div id="layoutSidenav">
        @include('layout.sidenavbar', ['pageTitleCheck' => $pageTitle])
        <div id="layoutSidenav_content" >
            <h3 class="mb-3 ps-2 ms-1">Cron Resource Page</h3>
            <div class="container">
                <div class="alert alert-success m-3" id="account_del_success" style="display:none;"></div>
                <ul class="list-unstyled">
                    <li><strong>Cron = 1</strong> The file has been successfully uploaded to the server and is ready for validation.</li>
                    <li class="mt-3"><strong>Cron = 11</strong> The uploaded file has been validated successfully and is now ready for processing.</li>
                    <li class="mt-3"><strong>Cron = 10</strong> The uploaded file failed validation during the validation process.</li>
                    <li class="mt-3"><strong>Cron = 2</strong> It means file <code>30%</code> uploade <b>completed</b>.</li>
                    <li class="mt-3"><strong>Cron = 4</strong> It means file <code>50%</code> uploade <b>completed</b>.</li>
                    <li class="mt-3"><strong>Cron = 5</strong> It means file <code>70%</code> uploade <b>completed</b>.</li>
                    <li class="mt-3"><strong>Cron = 6</strong> It means file <code>100%</code> uploade <b>completed</b>.</li>
                </ul>
            </div>
        </div>
    </div>
@endsection