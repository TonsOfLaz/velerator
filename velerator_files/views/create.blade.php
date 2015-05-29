@extends('app')

@section('title')
Add a New [MODEL]
@endsection

@section('content')
	<div class='row'>
		<div class='small-12 columns'>
			<h1>Add a new [MODEL]</h1>
		</div>
	</div>
	{!! Form::open(['url' => '[TABLE]', 'files' => [FILES_FLAG]]) !!}
	@include('[TABLE].form', ['submit_text' => 'Add New [MODEL]'])
	{!! Form::close() !!}
		</div>
	</div>
@endsection