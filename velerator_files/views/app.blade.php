<!doctype html>
<html class="no-js" lang="en">
	<head>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		 
		<title>@yield('title')</title>
		
		<link href="/css/app.css" rel="stylesheet">
		[CSS]
	</head>
	<body>
		@include('partials.navigation')
		 
		@yield('content')
		<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
		<script src="/js/vendor/modernizr.js"></script>
		[JS]
		<script>
			$(document).foundation();
		</script>
	 
		@yield('view_scripts')
	</body>
</html> 