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
			[BOTH]
			@if (Auth::guest())
				[GUEST]
				<li><a href="/auth/login">Login</a></li>
				<li><a href="/auth/register">Register</a></li>
			@else
				[AUTH]
				<li class="has-dropdown">
					<a href="#">{{ Auth::user()->name }}</a>
					<ul class="dropdown">
						<li><a href="/auth/logout">Logout</a></li>
					</ul>
				</li>
			@endif
			
		</ul>
		 
		<!-- Left Nav Section -->
		<ul class="left">
			<li></li>
		</ul>
	</section>
</nav>