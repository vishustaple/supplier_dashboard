@extends('layout.app', ['pageTitleCheck' => $pageTitle])
@section('content')
    <div id="layoutSidenav">
        @include('layout.sidenavbar', ['pageTitleCheck' => $pageTitle])
            <div id="layoutSidenav_content">
                <div class="m-1 d-md-flex border-bottom pb-3 mb-3 flex-md-row align-items-center justify-content-between">
                    <h3 class="mb-0 ps-2">Power BI</h3>
                </div>
                <iframe title="CenterPoint Reporting SQL" width="1140" height="541.25" src=https://app.powerbi.com/reportEmbed?reportId=a43b8268-0440-4e69-b90c-69aaf1a16b63&autoAuth=true&ctid=d9448cd0-bd4f-4914-a17f-522139087ae4 frameborder="0" allowFullScreen="true"></iframe>
        @include('layout.footer')
        </div>
    </div>
@endsection   