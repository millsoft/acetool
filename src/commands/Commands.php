<?php
error_reporting(E_ALL ^ E_DEPRECATED);

namespace Millsoft\AceTool\Commands;

class Commands{

	public static $console = null;

	public static function load($console){
		self::$console = $console;

		$class = get_called_class();
		$methods = get_class_methods($class);

		//initialize all methods:
		foreach($methods as $method){
			//all methods that start with "command" will be loaded
			if(substr($method, 0, 7) == "command"){
				@$class::$method();
			}
		}

	}


}