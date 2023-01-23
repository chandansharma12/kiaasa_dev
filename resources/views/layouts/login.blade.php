<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<!-- Required meta tags -->
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
<link rel="stylesheet" type="text/css" href="{{ asset('css/font-4-6-awesome.min.css') }}"> 
<link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/styles.css') }}">
<link rel="stylesheet" href="{{ asset('css/developer.css') }}">
<script>var ROOT_PATH = "{{url('/')}}"; </script>
<title>{{ config('app.name', 'Laravel') }}</title>
</head>
<body>


@yield('content')

<footer></footer>
<script src="{{ asset('js/jquery-3.4.1.slim.min.js') }}" ></script> 
<script	src="{{ asset('js/jquery-2.2.4.min.js') }}" ></script>
<script src="{{ asset('js/popper.min.js') }}" ></script>
<script src="{{ asset('js/bootstrap.min.js') }}" ></script> 
@yield('scripts')
</body>
</html>