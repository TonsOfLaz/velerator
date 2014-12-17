<!doctype html>
<html class="no-js" lang="en">
	<head>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		 
		<title>@yield('title')</title>
		 
		<link rel="stylesheet" type="text/css" href="assets/css/foundation.css">
		<link rel="stylesheet" type="text/css" href="assets/css/app.css">
		<link rel="stylesheet" type="text/css" href="assets/js/vendor/modernizr.js">

		<link rel="stylesheet" type="text/css" href="assets/css/main.css">
	</head>
	<body>
		<nav class="top-bar" data-topbar>
			<ul class="title-area">
				<li class="name">
				<h1><a href="/">Home</a></h1>
				</li>
				 
				<li class="toggle-topbar menu-icon"><a href="#"><span>Menu</span></a></li>
			</ul>
			 
			<section class="top-bar-section">
				<!-- Right Nav Section -->
				<ul class="right">
					<li data-reveal-id="clock-modal">
						<a href="/">About</a>
					</li>
					<li class="has-dropdown">
					<a href="#">Configure</a>
						<ul class="dropdown">
							<li><a href="/">Home</a></li>
							<li><a href="/">Home</a></li>
						</ul>
					</li>
					@include("sections.navigation")
				</ul>
				 
				<!-- Left Nav Section -->
				<ul class="left">
					<li></li>
				</ul>
			</section>
		</nav>
		 
		@yield('content')
		 
		<script type="text/javascript" src="assets/js/functions.js"></script>
		<script type="text/javascript" src="assets/js/vendor/jquery.js"></script>
		<script type="text/javascript" src="assets/js/foundation.min.js"></script>
		<script type="text/javascript" src="assets/js/app.js"></script>
		<script>
			$(document).foundation();
		</script>
	 
		<script>
			@yield('end_scripts')
		</script>
	</body>
</html> 