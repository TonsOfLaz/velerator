@extends('app')

@section('content')

    <img src="http://lorempixel.com/1900/500/[IMAGE]" alt="" />
    
    <div id="image_overlay" class="row">
      <div class="columns small-12">
        <p>[TAGLINE]</p>
      </div>
    </div>
    <div class="row row-light">
      <div class="columns small-12 large-4">
      <div class="row">
        <div class="columns small-6 large-12">
          <div class="circleimage" style="background-image:url(http://lorempixel.com/200/200/[IMAGEBOX]/5)">
            <h3>[BOX1]</h3>
            
          </div>
        </div>
        <div class="columns small-6 large-12">
          <p>[BOX1QUOTE]</p>
        </div>
        
      </div>
        
      </div>
      <div class="columns small-12 large-4">
      <div class="row">
        <div class="columns small-6 large-12">
        <div class="circleimage" style="background-image:url(http://lorempixel.com/200/200/[IMAGEBOX]/10)">
          <h3>[BOX2]</h3>
          </div>
        </div>
        <div class="columns small-6 large-12">
          <p>[BOX2QUOTE]</p>
        </div>
        </div>
      </div>
      <div class="columns small-12 large-4">
      <div class="row">
        <div class="columns small-6 large-12">
        <div class="circleimage" style="background-image:url(http://lorempixel.com/200/200/[IMAGEBOX]/6)">
          <h3>[BOX3]</h3>
          </div>
        </div>
        <div class="columns small-6 large-12">
          <p>[BOX3QUOTE]</p>
        </div>
        </div>
      </div>
    </div>
    [SECTIONS]

@stop