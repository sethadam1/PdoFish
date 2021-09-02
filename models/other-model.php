<?php

class ModelName2 extends PdoFish {

	public static $table 		= 'my_table_name';
	public static $primary_key	= 'example_id';

	static function example_function($value) {
		return self::first(['conditions'=>['some_other_field=?',$value]]);
	}
}