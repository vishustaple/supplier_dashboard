
<!doctype html>
<html>
<head>
   @include('layout.head')
</head>
<body >
     
   <div id="main" class=""> 
      @include('layout.sidenav') 
      @yield('content')
   </div>

   @include('layout.footscript')
</body>
</html>