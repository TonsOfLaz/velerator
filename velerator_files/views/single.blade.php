@extends('app')

@section('title')
{{ [OBJECT]->name() }}
@stop

@section('content')

<div class="row">
	<div class="columns small-12">{{ [OBJECT]->name() }}</div>
</div>
<div class="row">
	<div class="columns small-6">
		<img src="{{ [OBJECT]->image }}" />
	</div>
	<div class="columns small-6">

	</div>
</div>

@stop