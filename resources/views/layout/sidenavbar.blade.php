<div id="layoutSidenav_nav">
                <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                    <div class="sb-sidenav-menu">
                        <div class="nav">
                                                  
                            <a class="nav-link {{ (isset($pageTitleCheck) && $pageTitleCheck == 'Upload Sheets') ? 'active' : '' }}" href="{{route('upload.sheets')}}">
                                <div class="sb-nav-link-icon"><i class="fa fa-upload" aria-hidden="true"></i></div>
                                Data Management 
                            </a>
                            <!-- @if(Auth::user()->user_type == App\Models\User::USER_TYPE_USER ) -->
                            <a class="nav-link {{ (isset($pageTitleCheck) && $pageTitleCheck == 'Accounts Data') ? 'active' : '' }}" href="{{route('account')}}">
                                <div class="sb-nav-link-icon"><i class="fa fa-users" aria-hidden="true"></i></div>
                                Manage Accounts
                            </a>
                            <!-- @endif -->
                            <a class="nav-link {{ (isset($pageTitleCheck) && $pageTitleCheck == 'Supplier Data') ? 'active' : '' }}" href="{{route('supplier')}}">
                                <div class="sb-nav-link-icon"><i class="fas fa-chart-area"></i></div>
                                Manage Supplier
                            </a>
                            <a class="nav-link " href="{{route('user.show')}}">
                                <div class="sb-nav-link-icon"><i class="fa-solid fa-user"></i></div>
                                Manage Users
                            </a>
                            <!-- Submenu for Manage Supplier -->
                            <a class="nav-link {{ (isset($pageTitleCheck) && $pageTitleCheck == 'Supplier Report') ? 'active' : '' }}" data-toggle="collapse" href="#submenuSupplier">
                               <div class="sb-nav-link-icon"><i class="fa fa-th-list" aria-hidden="true"></i></div>
                                Reports
                               <i class="fas fa-caret-down"></i>
                            </a>
                            <div class="collapse" id="submenuSupplier">
                                <a class="nav-link ml-3" href="#">Business Report</a>
                                <a class="nav-link ml-3" href="#">Optimization Report</a>
                                <a class="nav-link ml-3" href="#">Consolidated Supplier Report</a>
                                <a class="nav-link ml-3" href="#">Supplier Rebate Report</a>
                                <a class="nav-link ml-3" href="#">Validation Rebate Report</a>
                                <a class="nav-link ml-3" href="#">Commission Report</a>
                            </div>
                            <a class="nav-link" target="_blank" href="http://3.95.106.180:7080/phpMyAdmin2025/">
                                <div class="sb-nav-link-icon"><i class="fa fa-database" aria-hidden="true"></i></div>
                                SQL Maintenance
                            </a>
                          
                            <a class="nav-link" href="{{route('user.logout')}}">
                                <div class="sb-nav-link-icon"><i class="fa fa-sign-out" aria-hidden="true"></i></div>
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