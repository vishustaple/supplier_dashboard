<div id="layoutSidenav_nav">
                <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                    <div class="sb-sidenav-menu">
                        <div class="nav">
                            <!-- <div class="sb-sidenav-menu-heading">Core</div>
                            <a class="nav-link" href="{{route('home')}}">
                                <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                                Dashboard
                            </a> -->
                            <!-- <div class="sb-sidenav-menu-heading">Addons</div> -->
                            <a class="nav-link" href="{{route('upload.sheets')}}">
                                <div class="sb-nav-link-icon"><i class="fas fa-chart-area"></i></div>
                                Upload Sheets
                            </a>
                            <a class="nav-link" href="{{route('account')}}">
                                <div class="sb-nav-link-icon"><i class="fas fa-table"></i></div>
                                Manage Accounts
                            </a>
                            <a class="nav-link" href="{{route('supplier')}}">
                                <div class="sb-nav-link-icon"><i class="fas fa-table"></i></div>
                                Manage Supplier
                            </a>
                            <a class="nav-link" target="_blank" href="http://3.95.106.180:7080/phpMyAdmin2025/">
                                <div class="sb-nav-link-icon"><i class="fas fa-table"></i></div>
                                Access Database
                            </a>
                            <a class="nav-link" href="{{route('user.logout')}}">
                                <div class="sb-nav-link-icon"><i class="fas fa-table"></i></div>
                                Logout
                            </a>
                        </div>
                    </div>
                    <div class="sb-sidenav-footer text-center">
                       
                            @if(auth()->check())
                            <div class="small">Logged in as: {{ auth()->user()->first_name  . ' ' . auth()->user()->last_name}}!</div>
                            @else
                            <div class="small">Logged in as: </div>

                            @endif
                    <span class="text-white">CenterPoint Group</span>
                    </div>
                </nav>
</div>