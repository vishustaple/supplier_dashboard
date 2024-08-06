
<!doctype html>
<html>
<head>
   @include('layout.head')

</head>
<body >
     
   <div id="main" class=""> 
  
      @if(Route::currentRouteName() != 'user.forget')
      @if(Route::currentRouteName() != 'login')
         @include('layout.sidenav')
         @endif
      @endif
    
      @yield('content')
   </div>

   @include('layout.footscript')
</body>
</html>