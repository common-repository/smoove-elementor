<?php
namespace SMEF;

use SMEF\Helpers\DB;

class Setup{
	public function __construct(){
		// register_activation_hook( SMEF_PLUGIN_FILE, [$this, 'plugin_activated'] );
	}
	
	// public function plugin_activated(){
	// 	$log_file = SMEF_PLUGIN_PATH . '/logs/plugin-activated-' . date('Y-m-d--H-i') . '.txt';

	// 	file_put_contents( $log_file, 'Activated:' . PHP_EOL . 'yes' . PHP_EOL . '--------------' . PHP_EOL, FILE_APPEND );

	// 	DB::create_api_log_table();
	// }
}

new Setup();