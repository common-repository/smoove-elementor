<?php
namespace SMEF\Helpers;

class DB{
	public static function create_api_log_table(){
		global $wpdb;

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' ); 
		
		$table_name = self::get_api_log_table();
		
		$wpdb->query("DROP TABLE " . $table_name );

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			`id` INT NOT NULL AUTO_INCREMENT,
			`method` text NOT NULL, 
			`endpoint` text NOT NULL, 
			`payload` longtext NOT NULL, 
			`response_body` longtext NOT NULL, 
			`response_info` longtext NOT NULL, 
			`response_code` INT NOT NULL, 
			`time` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL, 
			PRIMARY KEY `id` (`id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

		dbDelta( $sql );

		flush_rewrite_rules();
	}

	public static function truncate_api_log_table(){
        global $wpdb;

		$table_name = self::get_api_log_table();
		
        $wpdb->query("TRUNCATE TABLE $table_name");
	}

	public static function maybe_add_api_log_entry( $data = [] ){
		if( get_option( 'smef_debug_mode', 'off' ) == 'on' ){
			self::add_api_log_entry( $data );
		}
	}

	public static function add_api_log_entry( $data = [] ){
		global $wpdb;
		
		$table_name = self::get_api_log_table();

		$defauls = [
			'method' => '',
			'endpoint' => '',
			'payload' => '',
			'response_body' => '',
			'response_info' => '',
			'response_code' => 0,
			'time' => current_time('mysql', 1),
		];

		$data = array_merge( $defauls, $data );

		if( is_array( $data['payload'] ) ){
			$data['payload'] = maybe_serialize( $data['payload'] );
		}

		if( is_array( $data['response_body'] ) ){
			$data['response_body'] = maybe_serialize( $data['response_body'] );
		}

		if( is_array( $data['response_info'] ) ){
			$data['response_info'] = maybe_serialize( $data['response_info'] );
		}

		$inserted = $wpdb->insert(
			$table_name,
			$data
		);
	}

	public static function get_api_log_table(){
		global $wpdb;

		return $wpdb->prefix . 'smoove_elementor_api_log';
	}
}