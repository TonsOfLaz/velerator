@extends('app')

@section('title')
Edit [MODEL] {{ $[SINGULAR_VARIABLE]->link_text }}
@endsection

@section('content')
	<div class='row'>
		<div class='small-10 columns'>
			<h1>Edit [MODEL] {{ $[SINGULAR_VARIABLE]->link_text }}</h1>
		</div>
		<div class='small-2 columns'>
			<a class='button' href='/[TABLE]/{{ $[SINGULAR_VARIABLE]->id }}'>View</a>
		</div>
	</div>
	{!! Form::model($[SINGULAR_VARIABLE], [ 'method' 	=> 'PATCH', 
											'url' 		=> "[TABLE]/$[SINGULAR_VARIABLE]->id", 
											'files' 	=> [FILES_FLAG]]) !!}
	@include('[TABLE].form', ['submit_text' => 'Update [MODEL]'])
	{!! Form::close() !!}
		</div>
	</div>
@endsection