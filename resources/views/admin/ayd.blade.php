@extends('layout.app', ['pageTitleCheck' => $pageTitle])
@section('content')
    <div id="layoutSidenav">
        @include('layout.sidenavbar', ['pageTitleCheck' => $pageTitle])
        <div id="layoutSidenav_content" >
            <div class="container">
                <div class="m-1 mb-2 d-md-flex border-bottom pb-3 mb-3 align-items-center justify-content-between">
                    <h3 class="mb-0 ">{{ $pageTitle }}</h3>
                </div>
                <div class="row align-items-end border-bottom pb-3 mb-4">
                    <iframe id="ayd-chat" width="80%" height="600" frameborder="0"  src="https://www.askyourdatabase.com/chatbot/{{ env('AYD_CHATBOT_ID') }}"></iframe>
                </div>
            </div>
        </div>
    </div>
    <script>
    $(document).ready(function() {
        // Add CSRF token to all AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        let loginCompleted = false;

        window.addEventListener('message', function(event) {
            if (event.data.type === 'LOGIN_REQUIRED') {
                $.post('/admin/ayd-session', function(response) {
                    if (response.url) {
                        $('#ayd-chat').attr('src', response.url);
                    }
                });
            } else if (event.data.type === 'LOGIN_SUCCESS' && !loginCompleted) {
                console.log('LOGIN_SUCCESS');
                loginCompleted = true; // prevent further reloads
                $('#ayd-chat').attr('src', event.data.url);
            }
        });
    });
    </script>
@endsection