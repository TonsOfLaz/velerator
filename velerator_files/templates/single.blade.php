@extends('app')

@section('title')
{{ [OBJECT]->name() }}
@stop

@section('content')

<div class="row">
	<div class="columns small-12">{{ [OBJECT]->name() }}</div>
</div>
<div class="row">
	<div class="columns small-8">
		{{ [OBJECT]->id }}
	</div>
	<div class="columns small-4 panel"></div>
</div>

@stop