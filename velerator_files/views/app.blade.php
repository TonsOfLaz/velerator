<!doctype html>
<html class="no-js" lang="en">
	<head>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		 
		<title>@yield('title')</title>
		
		<link href="/css/app.css" rel="stylesheet">
		<link href="//cdnjs.cloudflare.com/ajax/libs/select2/4.0.0-rc.2/css/select2.min.css" rel="stylesheet" />
		[CSS]
	</head>
	<body>
		@include('partials.navigation')
		 
		@yield('content')
		<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
		<script src="/js/vendor/modernizr.js"></script>
		<script src="//cdnjs.cloudflare.com/ajax/libs/select2/4.0.0-rc.2/js/select2.min.js"></script>
		[JS]
		<script>
			$(document).foundation();
		</script>
	 
		@yield('footer')
	</body>
</html> 