@extends('app')

@section('title')
Edit [MODEL] {{ $[SINGULAR_VARIABLE]->link_text }}
@endsection

@section('content')
	<div class='row'>
		<div class='small-12 columns'>
			<h1>Edit [MODEL] {{ $[SINGULAR_VARIABLE]->link_text }}</h1>
		</div>
	</div>
	{!! Form::model($[SINGULAR_VARIABLE], ['url' => '[TABLE]']) !!}
	@include('[TABLE].form', ['submit_text' => 'Update [MODEL]'])
	{!! Form::close() !!}
		</div>
	</div>
@endsection