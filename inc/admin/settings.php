<?php
namespace SMEF\Admin;

use SMEF\Modules\Api_Logs_Table;

class Settings{
	public $settings_page_slug = 'smef-connector-settings';
	public $debug_log_page_slug = 'smef-connector-debug-log';

	public $settings_section_id = 'smef-connector-settings-section';

	public function __construct(){
		add_action( 'admin_menu', [$this, 'add_smoove_settings_menu'] );
		add_action( 'admin_init', [$this, 'register_smoove_settings_fields'] );
	}

	public function add_smoove_settings_menu(){
		add_options_page(
			esc_html__('smoove connector for Elementor forms', 'smoove-elementor'),
			esc_html__('smoove connector for Elementor forms', 'smoove-elementor'),
			'manage_options',
			$this->settings_page_slug,
			[$this, 'smoove_settings_page'],
		);
		
		if( get_option( 'smef_debug_mode', 'off' ) == 'on' ){
			add_options_page(
				esc_html__('smoove debug log', 'smoove-elementor'),
				esc_html__('smoove debug log', 'smoove-elementor'),
				'manage_options',
				$this->debug_log_page_slug,
				[$this, 'smoove_debug_log_page'],
			);
		}
	}

	public function register_smoove_settings_fields(){
		register_setting( $this->settings_page_slug, 'smef_api_key' );
		register_setting( $this->settings_page_slug, 'smef_contact_unsubscribed_action' );
		register_setting( $this->settings_page_slug, 'smef_contact_deleted_action' );
		register_setting( $this->settings_page_slug, 'smef_debug_mode' );

		add_settings_section(
			$this->settings_section_id, // section ID
			'', // title (optional)
			'', // callback function to display the section (optional)
			$this->settings_page_slug
		);

		add_settings_field(
			'smef_api_key',
			esc_html__('API Key', 'smoove-elementor'),
			[$this, 'input_field'],
			$this->settings_page_slug,
			$this->settings_section_id,
			[
				'label_for' => 'smef_api_key',
				'field_args' => [
					'name' => 'smef_api_key',
				]
			]
		);

		add_settings_field(
			'smef_contact_unsubscribed_action',
			esc_html__('Unsubscribed contact action', 'smoove-elementor'),
			[$this, 'select_field'],
			$this->settings_page_slug,
			$this->settings_section_id,
			[
				'label_for' => 'smef_contact_unsubscribed_action',
				'field_args' => [
					'name' => 'smef_contact_unsubscribed_action',
					'value' => 'do_not_process',
					'options' => [
						'do_not_process' => esc_html__('Don\'t process', 'smoove-elementor'),
						'restore' => esc_html__('Restore', 'smoove-elementor')
					]
				]
			]
		);

		add_settings_field(
			'smef_contact_deleted_action',
			esc_html__('Deleted contact action', 'smoove-elementor'),
			[$this, 'select_field'],
			$this->settings_page_slug,
			$this->settings_section_id,
			[
				'label_for' => 'smef_contact_deleted_action',
				'field_args' => [
					'name' => 'smef_contact_deleted_action',
					'value' => 'do_not_process',
					'options' => [
						'do_not_process' => esc_html__('Don\'t process', 'smoove-elementor'),
						'restore' => esc_html__('Restore', 'smoove-elementor')
					]
				]
			]
		);

		add_settings_field(
			'smef_debug_mode',
			esc_html__('Debug Mode', 'smoove-elementor'),
			[$this, 'select_field'],
			$this->settings_page_slug,
			$this->settings_section_id,
			[
				'label_for' => 'smef_debug_mode',
				'field_args' => [
					'name' => 'smef_debug_mode',
					'value' => 'off',
					'options' => [
						'off' => esc_html__('Off', 'smoove-elementor'),
						'on' => esc_html__('On', 'smoove-elementor')
					]
				]
			]
		);
	}

	public function message_field( $args ){
		$field_args = wp_parse_args( $args['field_args'], [
			'class' => 'smef-form-message info',
			'id' => isset( $args['id'] ) ? $args['id'] : false,
		]);

		echo '<div ';
		foreach( $field_args as $key => $value ){
			echo esc_html( $key ) . '="' . esc_attr( $value ) . '" ';
		}
		echo '>';
		
		echo '<p>' . esc_html( $field_args['value'] ) . '</p></div>';
	}

	public function input_field( $args ){
		$field_args = wp_parse_args( $args['field_args'], [
			'type' => 'text',
			'class' => 'smef-form-control',
			'id' => isset( $args['label_for'] ) ? $args['label_for'] : false,
		]);

		$default_value = isset( $field_args['value'] ) ? $field_args['value'] : false;

		$field_args['value'] = isset( $field_args['name'] ) ? get_option( $field_args['name'], $default_value ) : '';

		if( empty( $field_args['value'] ) && isset( $args['field_args']['value'] ) ){
			$field_args['value'] = $args['field_args']['value'];
		}

		echo '<input ';
		foreach( $field_args as $key => $value ){
			echo esc_html( $key ) . '="' . esc_attr( $value ) . '" ';
		}
		echo '/>';
	}

	public function select_field( $args ){
		$field_args = wp_parse_args( $args['field_args'], [
			'class' => 'smef-form-control',
			'id' => isset( $args['label_for'] ) ? $args['label_for'] : false,
			'options' => []
		]);
		
		$field_options = $field_args['options'];
		$field_value = isset( $field_args['name'] ) ? get_option( $field_args['name'], $field_args['value'] ) : '';
		
		unset( $field_args['options'] );
		unset( $field_args['value'] );

		$field_attrs = [];

		echo '<select ';
		foreach( $field_args as $key => $value ){
			echo esc_html( $key ) . '="' . esc_attr( $value ) . '" ';
		}
		echo '>';

			foreach( $field_options as $value => $label ){
				echo '<option value="' . esc_attr( $value ) . '"' . selected( $field_value, $value, false ) . '>' . esc_html( $label ) . '</option>';
			}
		echo '</select>';
	}

	public function smoove_get_settings_page_header(){
		$html = '';

		$html .= '<div class="smef-settings-header">';
			$html .= '<img src="' . esc_html( SMEF_PLUGIN_DIR ) . '/assets/images/smoove-settings-logo.jpg" class="smef-settings-logo">';
			$html .= '<h1 class="smef-settings-title">' . esc_html( get_admin_page_title() ) . '</h1>';
		$html .= '</div>';

		return $html;
	}

	public function smoove_settings_page(){
		if( !current_user_can( 'manage_options' ) ){
			return;
		}

		echo '<div class="wrap smef-settings-wrap">';
			echo $this->smoove_get_settings_page_header();

			echo '<div class="smef-settings-body">';
				echo '<form id="smef-settings-form" class="smef-form" action="options.php" method="post">';
					settings_fields( $this->settings_page_slug );
					do_settings_sections( $this->settings_page_slug );
					submit_button();
				echo '</form>';
			echo '</div>';
		echo '</div>';
	}

	public function smoove_debug_log_page(){
		if( !current_user_can( 'manage_options' ) ){
			return;
		}

		echo '<div class="wrap smef-settings-wrap">';
			echo $this->smoove_get_settings_page_header();

			echo '<div class="smef-settings-body">';
				echo '<div class="smef-debug-log">';
					echo '<form method="get">';
						echo '<input type="hidden" name="page" value="' . $_GET['page'] . '" />';
						
						$Api_Logs_Table = new Api_Logs_Table();
						$Api_Logs_Table->prepare_items();
						$Api_Logs_Table->display();
					echo '</form>';
				echo '</div>';
			echo '</div>';
		echo '</div>';
	}
}

new Settings();