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
		<nav class="top-bar small-12" data-topbar>
			<ul class="title-area">
				<li class="name">
				<h1><a href="/">[APPNAME]</a></h1>
				</li>
				 
				<li class="toggle-topbar menu-icon"><a href="#"><span>Menu</span></a></li>
			</ul>
			 
			<section class="top-bar-section">
				<!-- Right Nav Section -->
				<ul class="right">
					@include("partials.navigation")
				</ul>
				 
				<!-- Left Nav Section -->
				<ul class="left">
					<li></li>
				</ul>
			</section>
		</nav>
		 
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