<!doctype html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<title>@yield('title')</title>
		<link rel="stylesheet" href="/css/main.css">
	</head>
	<body>
		@include('sections.header')
		@yield('content')
		@include('sections.footer')
	</body>
</html>