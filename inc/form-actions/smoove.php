<?php
namespace SMEF\Form_Actions;

use \Elementor\Controls_Manager;
use \ElementorPro\Modules\Forms\Classes\Action_Base;
use \SMEF\Modules\Smoove_Api;
use \SMEF\Helpers\Smoove as Smoove_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Elementor form smoove action.
 *
 * Custom Elementor form action which adds new subscriber to smoove after form submission.
 *
 * @since 1.0.0
 */
class Smoove_Action_After_Submit extends Action_Base{
	public $map_input_prefix = 'smoove_fields_map_input_';

	/**
	 * Get action name.
	 *
	 * Retrieve Sendy action name.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string
	 */
	public function get_name() {
		return 'smoove';
	}

	/**
	 * Get action label.
	 *
	 * Retrieve smoove action label.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string
	 */
	public function get_label() {
		return esc_html__( 'smoove', 'smoove-elementor' );
	}

	/**
	 * Register action controls.
	 *
	 * Add input fields to allow the user to customize the action settings.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param \Elementor\Widget_Base $widget
	 */
	public function register_settings_section( $widget ) {
		$widget->start_controls_section(
			'section_smoove',
			[
				'label' => esc_html__( 'smoove', 'smoove-elementor' ),
				'condition' => [
					'submit_actions' => $this->get_name(),
				],
			]
		);

		$smef_api_key = get_option('smef_api_key');

		if( $smef_api_key ){
			$Smoove_Api = new Smoove_Api();
			$smoove_response = $Smoove_Api->get_lists();
			$smoove_fields = $Smoove_Api->get_fields();

            if( $smoove_response['code'] == 200 && is_array( $smoove_response['lists'] ) && !empty( $smoove_response['lists'] ) ){
                $smoove_lists = [ 0 => esc_html__('- Select -', 'smoove-elementor') ] + $smoove_response['lists'];
                
                $widget->add_control(
                    'smoove_list',
                    [
                        'label' => esc_html__( 'smoove List', 'smoove-elementor' ),
                        'type' => Controls_Manager::SELECT2,
                        'options' => $smoove_lists,
                    ]
                );

                if( is_array( $smoove_fields ) && !empty( $smoove_fields ) ){
                    $widget->add_control(
                        'smoove_fields_map_heading',
                        [
                            'label' => esc_html__( 'Fields Mapping', 'smoove-elementor' ),
                            'type' => Controls_Manager::HEADING,
                            'separator' => 'before',
                        ]
                    );

                    $smoove_field_options = [
                        0 => esc_html__('- Select -', 'smoove-elementor')
                    ];

                    foreach( $smoove_fields as $key => $label ){
                        $smoove_field_options[$key] = $label;
                    }

                    $widget->add_control(
                        $this->map_input_prefix . 'template',
                        [
                            'label' => 'Fields Map Template',
                            'type' => Controls_Manager::SELECT,
                            'options' => $smoove_field_options,
                        ]
                    );
                }
            }else if( $smoove_response['code'] == 402 ){
                $upgrade_link = '<a href="https://admin.smoove.io/panel.aspx" target="_blank">' . esc_html__('upgrade', 'smoove-elementor') . '</a>';

                $widget->add_control(
                    'smoove_payment_required_note',
                    [
                        'type' => Controls_Manager::RAW_HTML,
                        /* translators: smoove upgrade account link */
                        'raw' => sprintf( esc_html__( 'You reached the limit. For more calls, please %s your account.', 'smoove-elementor' ), $upgrade_link ),
                        'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
                    ]
                );
            }else if( isset( $smoove_response['message'] ) && !empty( $smoove_response['message'] ) ){
                $widget->add_control(
                    'smoove_general_error_note',
                    [
                        'type' => Controls_Manager::RAW_HTML,
                        'raw' => $smoove_response['message'],
                        'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
                    ]
                );
            }
		}else{
			$settings_link = '<a href="' . admin_url('options-general.php?page=smef-connector-settings') . '" target="_blank">' . esc_html__('plugin settings page', 'smoove-elementor') . '</a>';

			$widget->add_control(
				'smoove_no_api_key_note',
				[
					'type' => Controls_Manager::RAW_HTML,
					/* translators: plugin settings page link */
					'raw' => sprintf( esc_html__( 'In order to use smoove integration, please set you smoove API Key in the %s.', 'smoove-elementor' ), $settings_link ),
					'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
				]
			);
		}

		$widget->end_controls_section();
	}

	/**
	 * Run action.
	 *
	 * Runs the smoove action after form submission.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param \ElementorPro\Modules\Forms\Classes\Form_Record  $record
	 * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajax_handler
	 */
	public function run( $record, $ajax_handler ) {
		$settings = $record->get( 'form_settings' );

		$fields_map = [];

		foreach( $settings as $key => $smoove_field ) {
			if( strpos( $key, $this->map_input_prefix ) !== 0 || empty( $smoove_field ) ){
				continue;
			}

			$form_field = str_replace( $this->map_input_prefix, '', $key );
			$fields_map[$smoove_field] = $form_field;
		}

		// if ( empty( $fields_map['email'] ) && empty( $fields_map['phone'] ) ) {
		// 	return;
		// }

		// Get submitted form data.
		$raw_fields = $record->get( 'fields' );

		// Generate smoove contact data.
		$contact_data = [];
		$custom_fields = [];
		$debug_errors = [];

		foreach ( $raw_fields as $id => $field ) {
			if( empty( $field['value'] ) ){
				continue;
			}

			$smoove_fields = array_keys( array_filter( $fields_map, function( $field_id ) use ($id){
				return $field_id == $id;
			}) );

			if( is_array( $smoove_fields ) && !empty( $smoove_fields ) ){
				foreach( $smoove_fields as $smoove_field ){
					if( preg_match( '/i\d{1,3}/', $smoove_field ) ){
						$custom_fields[$smoove_field] = $field['value'];
					}else{
						$contact_data[$smoove_field] = $field['value'];
					}
				}
			}
		}

		if( isset( $contact_data['mobile'] ) && !isset( $contact_data['cellPhone'] ) ){
			$contact_data['cellPhone'] = $contact_data['mobile'];
			unset( $contact_data['mobile'] );
		}

		if( is_array( $custom_fields ) && !empty( $custom_fields ) ){
			$contact_data['customFields'] = $custom_fields;
		}

		if( !empty( $settings['smoove_list'] ) ){
			$contact_data['lists_ToSubscribe'] = [$settings['smoove_list']];
		}

		if( isset( $contact_data['canReceiveEmails'] ) ){
			$canReceiveEmails = $contact_data['canReceiveEmails'];

			if( in_array( strtolower( $canReceiveEmails ), ['yes', 'on'] ) ){
				$canReceiveEmails = true;
			}else if( strtolower( $canReceiveEmails ) === 'no' ){
				$canReceiveEmails = false;
			}else if( is_numeric( $canReceiveEmails ) && intval( $canReceiveEmails ) === 1 ){
				$canReceiveEmails = true;
			}else if( is_numeric( $canReceiveEmails ) && intval( $canReceiveEmails ) === 0 ){
				$canReceiveEmails = false;
			}else{
				$debug_errors['canReceiveEmails'] = 'Wrong value. <a href="https://google.com" target="_blank">Read more</a>.';
				$canReceiveEmails = false;
			}
		}

		if( isset( $contact_data['canReceiveSmsMessages'] ) ){
			$canReceiveSmsMessages = $contact_data['canReceiveSmsMessages'];

			if( in_array( strtolower( $canReceiveSmsMessages ), ['yes', 'on'] ) ){
				$canReceiveSmsMessages = true;
			}else if( strtolower( $canReceiveSmsMessages ) === 'no' ){
				$canReceiveSmsMessages = false;
			}else if( is_numeric( $canReceiveSmsMessages ) && intval( $canReceiveSmsMessages ) === 1 ){
				$canReceiveSmsMessages = true;
			}else if( is_numeric( $canReceiveSmsMessages ) && intval( $canReceiveSmsMessages ) === 0 ){
				$canReceiveSmsMessages = false;
			}else{
				$debug_errors['canReceiveSmsMessages'] = 'Wrong value. <a href="https://google.com" target="_blank">Read more</a>.';
				$canReceiveSmsMessages = false;
			}
		}

		$contact_data['canReceiveEmails'] = $canReceiveEmails ? $canReceiveEmails : false;
		$contact_data['canReceiveSmsMessages'] = $canReceiveSmsMessages ? $canReceiveSmsMessages : false;

		// Debugging
		// $ajax_handler->add_error_message( print_r( $contact_data, true ) );
		// $ajax_handler->add_error_message( print_r( $debug_errors, true ) );

		if( empty( $contact_data['email'] ) 
			&& empty( $contact_data['cellPhone'] ) 
			&& empty( $contact_data['externalId'] )
		){
			return;
		}

		$response = Smoove_Helper::maybe_send_contact_to_smoove( $contact_data, $debug_errors );

		// Debugging
		// $ajax_handler->add_error_message( print_r( $response, true ) );
	}

	/**
	 * On export.
	 *
	 * Clears smoove form settings/fields when exporting.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param array $element
	 */
	public function on_export( $element ) {
		foreach( $element as $key => $value ){
			if( strpos( $key, 'smoove' ) === 0 ){
				unset( $element[$key] );
			}
		}

		return $element;
	}
}