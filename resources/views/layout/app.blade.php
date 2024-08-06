
<!doctype html>
<html>
<head>
   @include('layout.head')

</head>
<body >
     
   <div id="main" class=""> 
  
      @if(Route::currentRouteName() !== 'login' || Route::currentRouteName() !== 'user.forget')
         @include('layout.sidenav')
      @endif
    
      @yield('content')
   </div>

   @include('layout.footscript')
</body>
</html>