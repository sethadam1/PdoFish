<?php

require __DIR__."/credentials.php";
require __DIR__."/PdoFish.class.php";
foreach(glob(__DIR__.'/models/*.php') as $p) {
	include_once($p);
}
PdoFish::startup($PdoFish_options);
