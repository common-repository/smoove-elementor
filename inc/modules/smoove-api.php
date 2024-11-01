<?php
namespace SMEF\Modules;

use SMEF\Helpers\DB;

class Smoove_Api{
	public $api_url;
	public $api_key;
	public $default_response;

	public function __construct( $api_key = false  ){
		$this->api_url = 'https://rest.smoove.io/v1/';
		$this->api_key = $api_key ? $api_key : get_option('smef_api_key');
		$this->default_response = ['code' => false];
	}

	public function get_lists(){
		$lists = [];
		$response_code = 0;
		$response_message = esc_html__( 'Error loading lists.', 'smoove-elementor' );

		for( $page = 1; $page < 1000; $page++ ){
			$api_response = $this->get('Lists?page=' . $page);
			
			if( $api_response['code'] == 200 && is_array( $api_response['body'] ) && !empty( $api_response['body'] ) ){
				$response_code = 200;

				foreach( $api_response['body'] as $smoove_list ){
					if( !$smoove_list['id'] || !$smoove_list['name'] ){ continue; }
					
					$lists[ $smoove_list['id'] ] = $smoove_list['name'];
				}
			}else{
				if( $api_response['code'] ){ $response_code = $api_response['code']; }
				if( $api_response['message'] ){ $response_message = $api_response['message']; }

				break;
			}
		}

		if( is_array( $lists ) && !empty( $lists ) ){
            $response = [
                'code' => 200,
                'lists' => $lists,
            ];
		}else{
			$response = [
				'code' => $response_code,
				'message' => $response_message
			];
        }

		return $response;
	}

	public function get_fields(){
		$fields = [];

		$response = $this->get('Account/ContactFields');
		
		if( $response['code'] == 200 && is_array( $response['body'] ) && !empty( $response['body'] ) ){
			foreach( $response['body'] as $smoove_list ){
				if( !$smoove_list['key'] || !$smoove_list['label'] ){ continue; }

				switch( $smoove_list['key'] ){
					case 'id':
					case 'timestampSignup':
						break;

					case 'mobile':
						$fields['cellPhone'] = 'Cell Phone';
						break;

					default:
						$fields[ $smoove_list['key'] ] = $smoove_list['label'];
						break;
				}
			}
		}

		if( !isset( $fields['canReceiveEmails'] ) ){
			$fields['canReceiveEmails'] = 'Can Receive Emails';
		}

		if( !isset( $fields['canReceiveSmsMessages'] ) ){
			$fields['canReceiveSmsMessages'] = 'Can Receive SMS Messages';
		}

		return $fields;
	}

	public function get_contact_status( $args = [] ){
		if( $email = $args['email'] ){
			return $this->get( 'Contacts/status/' . $email . '?by=email' );
		}else if( $cellPhone = $args['cellPhone'] ){
			return $this->get( 'Contacts/status/' . $cellPhone . '?by=CellPhone' );
		}else if( $externalId = $args['externalId'] ){
			return $this->get( 'Contacts/status/' . $externalId . '?by=ExternalId' );
		}
	}

	public function send_contact( $contact_data, $restore_if_deleted = false, $restore_if_unsubscribed = false, $debug_errors = [] ){
		$restore_if_deleted = $restore_if_deleted ? 'true' : 'false';
		$restore_if_unsubscribed = $restore_if_unsubscribed ? 'true' : 'false';

		$endpoint = 'Contacts?updateIfExists=true&restoreIfDeleted=' . $restore_if_deleted . '&restoreIfUnsubscribed=' . $restore_if_unsubscribed;

		return $this->post( $endpoint, $contact_data, $debug_errors );
	}

	public function get( $endpoint, $body = false, $debug_errors = [] ){
		return $this->call( $endpoint, $body, 'GET', $debug_errors );
	}

	public function post( $endpoint, $body = false, $debug_errors = [] ){
		return $this->call( $endpoint, $body, 'POST', $debug_errors );
	}

	public function call( $endpoint, $body = false, $method = 'GET', $debug_errors = [] ){
		if( !$this->api_key ){
			return $this->default_response;
		}

		$args = [
			'headers' => [
				'Content-type' => 'application/json',
				'apiKey' => $this->api_key,
			],
		];

		if( is_array( $body ) && !empty( $body ) ){
			$args['body'] = wp_json_encode( $body );
		}
		
		if( strtolower( $method ) == 'post' ){
			$response = wp_remote_post( $this->api_url . $endpoint, $args );
		}else{
			$response = wp_remote_get( $this->api_url . $endpoint, $args );
		}

		if( !is_wp_error( $response ) ){
			$response_body = json_decode( $response['body'], true );
			$response_code = wp_remote_retrieve_response_code( $response );
			$response_info = is_array( $response['response'] ) ? $response['response'] : [];

			if( is_array( $debug_errors ) && !empty( $debug_errors ) ){
				$response_info = array_merge( $response_info, $debug_errors );
			}

			DB::maybe_add_api_log_entry([
				'method' => $method,
				'endpoint' => $endpoint,
				'payload' => $body ? $body : '',
				'response_body' => $response_body ? $response_body : '',
				'response_info' => $response_info,
				'response_code' => $response_code,
			]);

			return [
				'api_key' => $this->api_key,
				'code' => $response_code,
				'body' => $response_body,
				'message' => $response['response']['message'] 
			];
		}else{
			return $this->default_response;
		}
	}
}