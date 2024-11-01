<?php
namespace SMEF\Admin;

use SMEF\Helpers\DB;

class Setup{
	public function __construct(){
		add_action( 'admin_init', [$this, 'handle_clear_api_logs'] );
		add_action( 'admin_enqueue_scripts', [$this, 'admin_enqueues'] );
		add_action( 'elementor/editor/after_enqueue_scripts',  [$this, 'editor_enqueues'] );
	}

	public function handle_clear_api_logs(){
		global $wpdb;

		if( isset( $_GET['action']) && $_GET['action'] === 'smef_clear_api_logs' ){
			if( !isset( $_GET['_wpnonce']) || !wp_verify_nonce( $_GET['_wpnonce'], 'smef_clear_api_logs' ) ){
				wp_die('Security check failed');
			}

			DB::truncate_api_log_table();

			wp_redirect( admin_url('options-general.php?page=smef-connector-debug-log&deleted=1') );
			exit;
		}
	}

	public function admin_enqueues(){
		wp_enqueue_script( 'jquery' );

		wp_enqueue_style( 'smef-admin', SMEF_PLUGIN_DIR . '/assets/css/admin.css', [], SMEF_FILE_VER );
		wp_enqueue_script( 'smef-admin', SMEF_PLUGIN_DIR . '/assets/js/admin.js', ['jquery'], SMEF_FILE_VER, true );

		wp_localize_script( 'smef-admin', 'smef_localize', [
			'api_url' => site_url('/wp-json/smef'),
			'api_nonce' => wp_create_nonce('wp_rest'),
			'are_you_sure' => esc_html__('Are you sure?', 'smoove-elementor'),
		] );
	}

	public function editor_enqueues(){
		wp_enqueue_script( 'jquery' );
		
		wp_enqueue_style( 'smef-editor', SMEF_PLUGIN_DIR . '/assets/css/editor.css', [], SMEF_FILE_VER );
		wp_enqueue_script( 'smef-editor', SMEF_PLUGIN_DIR . '/assets/js/editor.js', ['jquery'], SMEF_FILE_VER, true );

		wp_localize_script( 'smef-editor', 'smef_localize', [
			'select_placeholder' => esc_html__('- Select -', 'smoove-elementor')
		] );
	}
}

new Setup();