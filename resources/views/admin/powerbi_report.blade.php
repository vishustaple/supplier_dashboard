@extends('layout.app', ['pageTitleCheck' => $pageTitle])
@section('content')
    <div id="layoutSidenav">
        @include('layout.sidenavbar', ['pageTitleCheck' => $pageTitle])
        <div id="layoutSidenav_content">
            <div class="m-1 d-md-flex border-bottom pb-3 mb-3 flex-md-row align-items-center justify-content-between">
                <h3 class="mb-0 ps-2">{{ $pageTitle }}</h3>
            </div>
            <div class="container">
                @if($accessToken)
                    <div id="embedContainer" style="height: 600px;"></div>
                @else
                    {!! $ifram !!}
                @endif
            </div>
            @include('layout.footer')
        </div>
    </div>
    @if($accessToken)
        <script src="https://cdn.jsdelivr.net/npm/powerbi-client@2.21.0/dist/powerbi.min.js"></script>
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                let models = window['powerbi-client'].models;
                let config = {
                    type: 'report',
                    tokenType: models.TokenType.Aad,
                    accessToken: "{{ $accessToken }}",
                    embedUrl: "https://app.powerbi.com/reportEmbed",
                    id: "{{ $reportId }}",
                    settings: {
                        panes: {
                            filters: {
                                visible: true
                            },
                            pageNavigation: {
                                visible: true
                            }
                        },
                        bars: {
                            statusBar: {
                                visible: true
                            }
                        }
                    }
                };

                let embedContainer = document.getElementById('embedContainer');
                let report = powerbi.embed(embedContainer, config);

                report.on("loaded", function () {
                    console.log("Report loaded");
                });

                report.on("error", function (event) {
                    console.error(event.detail);
                });

                report.on("rendered", function () {
                    console.log("Report rendered");
                });
            });
        </script>
    @endif
@endsection  