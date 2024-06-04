<div id="layoutSidenav_nav" >
                <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                    <div class="sb-sidenav-menu">
                        <div class="nav side_bar_admin">
                                                  
                            <a class="nav-link {{ (isset($pageTitleCheck) && $pageTitleCheck == 'Upload Sheets') ? 'active' : '' }}" href="{{route('upload.sheets')}}">
                                <div class="sb-nav-link-icon"><i class="fa fa-upload" aria-hidden="true"></i></div>
                                Data Management 
                            </a>
                            <div class="manage_account_link">
                            <a class="nav-link {{ (isset($pageTitleCheck) && $pageTitleCheck == 'Accounts Data') ? 'active' : '' }}" href="{{route('account')}}">
                                <div class="sb-nav-link-icon"><i class="fa fa-users" aria-hidden="true"></i></div>
                                Manage Accounts
                            </a>
                            <a href="{{ route('account.customer-edit')}}" class="bell_icon_link position-relative">
                                <i class="fa-solid fa-bell"></i>
                                <span class="notification-count" id="account_count" style="display:none"></span>
                            </a>
                            </div>
                            <!-- <a class="nav-link {{ (isset($pageTitleCheck) && $pageTitleCheck == 'Sales Team') ? 'active' : '' }}" href="{{route('sales.index')}}">
                                <div class="sb-nav-link-icon"><i class="fa fa-address-card" aria-hidden="true"></i></div>
                                Manage Sales Repersantative
                            </a> -->
                             <!-- Submenu for Manage sales -->
                             @if(in_array('Sales Rep', auth()->user()->permissions->pluck('name')->toArray()) || auth()->user()->user_type != \App\Models\User::USER_TYPE_USER)
                             <a class="nav-link {{ (isset($pageTitleCheck) && in_array($pageTitleCheck, ['Sales Team', 'Commission'])) ? 'active' : '' }}" data-toggle="collapse" href="#submenuSale">
                               <div class="sb-nav-link-icon"><i class="fa fa-th-list" aria-hidden="true"></i></div>
                                Sales Rep
                               <i class="fas fa-caret-down"></i>
                            </a>
                            <div class="collapse {{ (isset($pageTitleCheck) && in_array($pageTitleCheck, ['Sales Team', 'Commission'])) ? 'show' : '' }}" id="submenuSale">
                                <a class="nav-link ml-3 {{ (isset($pageTitleCheck) && $pageTitleCheck == 'Sales Team') ? 'active' : '' }}" href="{{route('sales.index')}}">
                                    <div class="sb-nav-link-icon"></div>
                                    Manage Sales Rep
                                </a>
                                <a class="nav-link ml-3 {{ (isset($pageTitleCheck) && $pageTitleCheck == 'Commission') ? 'active' : '' }}" href="{{route('commission.list', ['commissionType' => 'commission_listing'])}}">
                                    <div class="sb-nav-link-icon"></div>
                                    Sales Rep Commission
                                </a>
                             </div>
                            @endif
                            @if(in_array('Manage Supplier', auth()->user()->permissions->pluck('name')->toArray()) || auth()->user()->user_type != \App\Models\User::USER_TYPE_USER)
                            <a class="nav-link {{ (isset($pageTitleCheck) && $pageTitleCheck == 'Supplier Data') ? 'active' : '' }}" href="{{route('supplier')}}">
                                <div class="sb-nav-link-icon"><i class="fas fa-chart-area"></i></div>
                                Manage Supplier
                            </a>
                            @endif
                            
                            <a class="nav-link {{ (isset($pageTitleCheck) && $pageTitleCheck == 'Catalog List') ? 'active' : '' }}" href="{{route('catalog.list', ['catalogType' => 'catalog'])}}">
                                <div class="sb-nav-link-icon"><i class="fa fa-book" aria-hidden="true"></i></div>
                                Catalog List
                            </a>

                            @if(in_array('Rebate', auth()->user()->permissions->pluck('name')->toArray()) || auth()->user()->user_type != \App\Models\User::USER_TYPE_USER)
                            <div class="manage_account_link">
                                <a class="nav-link {{ (isset($pageTitleCheck) && $pageTitleCheck == 'Rebate') ? 'active' : '' }}" href="{{route('rebate.list', ['rebateType' => 'rebate'])}}">
                                    <div class="sb-nav-link-icon"><i class="fa fa-usd" aria-hidden="true"></i></div>
                                    Rebate
                                </a>
                                <a href="{{route('rebate.list', ['rebateType' => 'edit_rebate'])}}" class="bell_icon_link position-relative">
                                    <i class="fa-solid fa-bell"></i>
                                    <span class="notification-count" id="rebate_count" style="display:none"></span>
                                </a>
                            </div>
                            @endif
                            <!-- <a class="nav-link {{ (isset($pageTitleCheck) && $pageTitleCheck == 'Commission') ? 'active' : '' }}" href="{{route('commission.list', ['commissionType' => 'commission_listing'])}}">
                                <div class="sb-nav-link-icon"><i class="fa fa-book" aria-hidden="true"></i></div>
                                Commission
                            </a> -->
                            @if(in_array('Manage Users', auth()->user()->permissions->pluck('name')->toArray()) || auth()->user()->user_type != \App\Models\User::USER_TYPE_USER)
                            <a class="nav-link {{ (isset($pageTitleCheck) && $pageTitleCheck == 'User Data') ? 'active' : '' }}" href="{{route('user.show')}}">
                                <div class="sb-nav-link-icon"><i class="fa-solid fa-user"></i></div>
                                Manage Users
                            </a>
                            @endif

                            <!-- Submenu for Manage Supplier -->
                            <a class="nav-link {{ (isset($pageTitleCheck) && in_array($pageTitleCheck, ['Business Report', 'Optimization Report', 'Consolidated Supplier Report', 'Supplier Rebate Report', 'Validation Rebate Report', 'Commission Report'])) ? 'active' : '' }}" data-toggle="collapse" href="#submenuSupplier">
                               <div class="sb-nav-link-icon"><i class="fa fa-th-list" aria-hidden="true"></i></div>
                                Reports
                               <i class="fas fa-caret-down"></i>
                            </a>
                            <div class="collapse {{ (isset($pageTitleCheck) && in_array($pageTitleCheck, ['Business Report', 'Optimization Report', 'Consolidated Supplier Report', 'Supplier Rebate Report', 'Validation Rebate Report', 'Commission Report'])) ? 'show' : '' }}" id="submenuSupplier">
                                <a class="nav-link ml-3 {{ (isset($pageTitleCheck) && $pageTitleCheck == 'Business Report') ? 'active' : '' }}" href="{{route('report.type', ['reportType' => 'business_report'])}}">Business Report</a>
                                <a class="nav-link ml-3 {{ (isset($pageTitleCheck) && $pageTitleCheck == 'Quarter Report') ? 'active' : '' }}" href="{{route('report.type', ['reportType' => 'optimization_report'])}}">Quarter Report</a>
                                <a class="nav-link ml-3 {{ (isset($pageTitleCheck) && $pageTitleCheck == 'Consolidated Supplier Report') ? 'active' : '' }}" href="{{route('report.type', ['reportType' => 'consolidated_report'])}}">Consolidated Supplier Report</a>
                                <a class="nav-link ml-3 {{ (isset($pageTitleCheck) && $pageTitleCheck == 'Supplier Rebate Report') ? 'active' : '' }}" href="{{route('report.type', ['reportType' => 'supplier_report'])}}">Supplier Rebate Report</a>
                                <a class="nav-link ml-3 {{ (isset($pageTitleCheck) && $pageTitleCheck == 'Validation Rebate Report') ? 'active' : '' }}" href="{{route('report.type', ['reportType' => 'validation_rebate_report'])}}">Validation Rebate Report</a>
                                <a class="nav-link ml-3 {{ (isset($pageTitleCheck) && $pageTitleCheck == 'Commission Report') ? 'active' : '' }}" href="{{route('report.type', ['reportType' => 'commission_report'])}}">Commission Report</a>
                            </div>
                            @if(in_array('SQL Maintenance', auth()->user()->permissions->pluck('name')->toArray()) || auth()->user()->user_type != \App\Models\User::USER_TYPE_USER)
                            <a class="nav-link" target="_blank" href="http://3.95.106.180:7080/phpMyAdmin2025/">
                                <div class="sb-nav-link-icon"><i class="fa fa-database" aria-hidden="true"></i></div>
                                SQL Maintenance
                            </a>
                            @endif
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
                <script>
                    var token = "{{ csrf_token() }}";
                    $.ajax({
                        type: 'GET',
                        url: "{{route('accounts.counts')}}",
                        dataType: 'json',                       
                        headers: {'X-CSRF-TOKEN': token},
                        processData: false,
                        
                        success: function(response) {
                            $('html, body').animate({ scrollTop: 0 }, 'slow');
                            if(response.error){
                            }

                            if(response.success){
                                $('#account_count').css('display','flex');
                                $('#account_count').text(response.success);
                            }
                        },
                        error: function(xhr, status, error) {
                            // Handle error response
                            console.error(xhr.responseText);
                        }
                    });

                    $.ajax({
                        type: 'GET',
                        url: "{{route('rebate.counts')}}",
                        dataType: 'json',                       
                        headers: {'X-CSRF-TOKEN': token},
                        processData: false,
                        
                        success: function(response) {
                            $('html, body').animate({ scrollTop: 0 }, 'slow');
                            if(response.error){
                            }

                            if(response.success){
                                $('#rebate_count').css('display','flex');
                                $('#rebate_count').text(response.success);
                            }
                        },
                        error: function(xhr, status, error) {
                            // Handle error response
                            console.error(xhr.responseText);
                        }
                    });

                    //colaapse sidebar 
                    $('#sidebarToggle').click(function() {
                    $('#layoutSidenav_nav').toggleClass('collapsed');
                    $('#layoutSidenav_content').toggleClass('collapsedfull');                    
                    });

                </script>
<!-- </div> -->