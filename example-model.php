<?php

// still in development
class PdoFishExample extends PdoFish {

	public static $table 		= 'my_table';
	public static $primary_key	= 'example_id';

	static function get($value) {
		return self::first(['conditions'=>['some_other_field=?',$value]]);
	}
}
