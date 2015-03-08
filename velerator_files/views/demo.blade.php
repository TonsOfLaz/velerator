@extends('app')

@section('title')
Demo Page
@stop

@section('content')
<div id="intro" class="row">
	<div class="columns small-12"><h1>[APP]: Velerator Demo</h1></div>
	<div class="columns small-12"><h2>Your app has been generated from the file: [FILE]</h2></div>
	<div id="intro_message" class="columns panel small-12">
		You are seeing this page as a demonstration of what Velerator has set up based on your config file. If you would like to get the default Laravel Welcome page back, open the file <span class="filepath">/app/Http/routes.php</span> and replace:
		<pre class="codeblock">
Route::get('/', function() {
	return view('velerator_demo');
});</pre>
		With:
		<pre class="codeblock">
Route::get('/', 'WelcomeController@index');</pre>
	</div>
</div>
<style>
	pre {
		background: #333;
		color: #EEE;
		padding: 15px;
	}
</style>
@stop