<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class [NAME] extends Model {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = '[TABLE]';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [FILLABLE_ARRAY];

	/**
	 * The attributes excluded from the model's JSON form.
	 *
	 * @var array
	 */
	protected $hidden = [HIDDEN_ARRAY];

	[RELATIONSHIPS]

}
