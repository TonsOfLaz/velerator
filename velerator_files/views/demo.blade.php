@extends('app')

@section('title')
Demo Page
@stop

@section('content')
<div id="intro" class="row">
	<div class="columns small-12"><h1>[APP] Velerated</h1></div>
	<div class="columns small-12"><h2>Your app has been generated from the file: [FILE]</h2></div>
	<div id="intro_message" class="columns panel small-12">
		You are seeing this page as a demonstration of what Velerator has set up based on your config file. If you would like to get the default Laravel Welcome Page back, open the file <span class="filepath">/app/Http/routes.php</span> and replace:
		<pre class="codeblock">
Route::get('/', function() {
	return view('velerator_demo');
});</pre>
		With:
		<pre class="codeblock">
Route::get('/', 'WelcomeController@index');</pre>
	</div>
	
</div>
<div class="row">
	<div class="columns small-12">
		[ROUTES]
	</div>
</div>

[MODELSECTIONS]
<style>
	pre {
		background: #333;
		color: #EEE;
		padding: 15px;
		margin: 5px 0px;
	}
	.filepath {
		background: #779;
		color: #EEE;
		padding: 5px;
	}
	table {
		width: 100%;
	}
	tr.divider td {
		background: rgb(21,77,131);
		color: white;
		font-size: 1.2em;
	}
</style>
@stop