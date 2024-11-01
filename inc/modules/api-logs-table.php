<?php
namespace SMEF\Modules;

use WP_List_Table;
use SMEF\Helpers\DB;

if( !class_exists('WP_List_Table' ) ){
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Api_Logs_Table extends WP_List_Table {
	public function __construct() {
		parent::__construct([
			'singular' => esc_html__('Log', 'smoove-elementor'), 
			'plural' => esc_html__('Logs', 'smoove-elementor'), 
			'ajax' => false 
		]);
	}

	public function get_columns() {
		return [
			// 'cb' => '<input type="checkbox" />',
			// 'id' => esc_html__('ID', 'smoove-elementor'),
			'method' => esc_html__('Method', 'smoove-elementor'),
			'endpoint' => esc_html__('Endpoint', 'smoove-elementor'),
			'payload' => esc_html__('Payload', 'smoove-elementor'),
			'response_body' => esc_html__('Response Body', 'smoove-elementor'),
			'response_info' => esc_html__('Response Info', 'smoove-elementor'),
			'response_code' => esc_html__('Response Code', 'smoove-elementor'),
			'time' => esc_html__('Time', 'smoove-elementor')
		];
	}

	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'payload':
			case 'response_body':
			case 'response_info':
				$value = maybe_unserialize( $item[$column_name] );
				return '<div class="smef-limit-height">' . $this->array_to_list( $value ) . '</div>';
			default:
				return $item[$column_name];
		}
	}

	// public function column_cb( $item ) {
	// 	return sprintf('<input type="checkbox" name="id[]" value="%s" />', $item['id']);
	// }

	public function get_sortable_columns() {
		return [
			'method' => ['method', false],
			'endpoint' => ['endpoint', false],
			'response_code' => ['response_code', false],
			'time' => ['time', false]
		];
	}

	public function array_to_list( $array = [] ) {
		$html = '';

		if( !is_array( $array ) || empty( $array ) ){
			return $html;
		}

		$html .= '<ul class="ul-square">';
			foreach( $array as $key => $value ){
				if( is_array( $value ) ) {
					$html .= '<li>' . htmlspecialchars( $key ) . $this->array_to_list( $value ) . '</li>';
				} else {
					$html .= '<li>' . htmlspecialchars( $key ) . ': ' . $value . '</li>';
				}
			}
		$html .= '</ul>';

		return $html;
	}

	protected function extra_tablenav($which) {
		if( $which == 'top' ){
			$method = isset( $_GET['method'] ) ? $_GET['method'] : '';
			$endpoint = isset( $_GET['endpoint'] ) ? $_GET['endpoint'] : '';
			$response_code = isset( $_GET['response_code'] ) ? $_GET['response_code'] : '';

			echo '<div class="alignleft actions">';
				echo '<select name="method">';
					echo '<option value="">' . esc_html__('Method', 'smoove-elementor') . '</option>';
					echo '<option value="GET"' . selected( $method, 'GET', false ) . '>GET</option>';
					echo '<option value="POST"' . selected( $method, 'POST', false ) . '>POST</option>';
				echo '</select>';

				echo '<select name="endpoint">';
					echo '<option value="">' . esc_html__('Endpoint', 'smoove-elementor') . '</option>';
					echo '<option value="Lists"' . selected( $endpoint, 'Lists', false ) . '>Lists</option>';
					echo '<option value="Account/ContactFields"' . selected( $endpoint, 'Account/ContactFields', false ) . '>Account/ContactFields</option>';
					echo '<option value="Contacts"' . selected( $endpoint, 'Contacts', false ) . '>Contacts</option>';
					echo '<option value="Contacts/status"' . selected( $endpoint, 'Contacts/status', false ) . '>Contacts/status</option>';
				echo '</select>';

				echo '<select name="response_code">';
					echo '<option value="">' . esc_html__('Response Code', 'smoove-elementor') . '</option>';
					echo '<option value="100"' . selected( $response_code, '100', false ) . '>100 Continue</option>';
					echo '<option value="101"' . selected( $response_code, '101', false ) . '>101 Switching Protocols</option>';
					echo '<option value="200"' . selected( $response_code, '200', false ) . '>200 OK</option>';
					echo '<option value="201"' . selected( $response_code, '201', false ) . '>201 Created</option>';
					echo '<option value="202"' . selected( $response_code, '202', false ) . '>202 Accepted</option>';
					echo '<option value="204"' . selected( $response_code, '204', false ) . '>204 No Content</option>';
					echo '<option value="301"' . selected( $response_code, '301', false ) . '>301 Moved Permanently</option>';
					echo '<option value="302"' . selected( $response_code, '302', false ) . '>302 Found</option>';
					echo '<option value="303"' . selected( $response_code, '303', false ) . '>303 See Other</option>';
					echo '<option value="304"' . selected( $response_code, '304', false ) . '>304 Not Modified</option>';
					echo '<option value="400"' . selected( $response_code, '400', false ) . '>400 Bad Request</option>';
					echo '<option value="401"' . selected( $response_code, '401', false ) . '>401 Unauthorized</option>';
					echo '<option value="403"' . selected( $response_code, '403', false ) . '>403 Forbidden</option>';
					echo '<option value="404"' . selected( $response_code, '404', false ) . '>404 Not Found</option>';
					echo '<option value="405"' . selected( $response_code, '405', false ) . '>405 Method Not Allowed</option>';
					echo '<option value="408"' . selected( $response_code, '408', false ) . '>408 Request Timeout</option>';
					echo '<option value="429"' . selected( $response_code, '429', false ) . '>429 Too Many Requests</option>';
					echo '<option value="500"' . selected( $response_code, '500', false ) . '>500 Internal Server Error</option>';
					echo '<option value="501"' . selected( $response_code, '501', false ) . '>501 Not Implemented</option>';
					echo '<option value="502"' . selected( $response_code, '502', false ) . '>502 Bad Gateway</option>';
					echo '<option value="503"' . selected( $response_code, '503', false ) . '>503 Service Unavailable</option>';
					echo '<option value="504"' . selected( $response_code, '504', false ) . '>504 Gateway Timeout</option>';
					echo '<option value="505"' . selected( $response_code, '505', false ) . '>505 HTTP Version Not Supported</option>';
				echo '</select>';

				$filter_button = esc_html__('Filter', 'smoove-elementor');

				submit_button( $filter_button, '', 'filter_action', false );
			echo '</div>';
		}

		if( $which == 'bottom' ){
			$clear_logs_link = add_query_arg([
				'action' => 'smef_clear_api_logs',
				'_wpnonce' => wp_create_nonce('smef_clear_api_logs')
			]);

			echo '<div class="alignleft actions">';
				echo '<a href="' . $clear_logs_link . '" class="smef-clear-logs">' . esc_html__('Clear all logs', 'smoove-elementor') . '</a>';
			echo '</div>';
		}
	}

	public function prepare_items() {
		global $wpdb;
		$table_name = DB::get_api_log_table();
		
		$orderby = isset( $_GET['orderby'] ) ? $_GET['orderby'] : 'time';
		$order = isset( $_GET['order'] ) ? strtoupper( $_GET['order'] ) : 'DESC';

		$where = '';

		$method = isset( $_GET['method'] ) ? $_GET['method'] : '';
		$endpoint = isset( $_GET['endpoint'] ) ? $_GET['endpoint'] : '';
		$response_code = isset( $_GET['response_code'] ) ? intval( $_GET['response_code'] ) : '';

		if( !empty( $method ) ){
			$where .= !empty( $where ) ? ' AND ' : 'WHERE ';
			$where .= $wpdb->prepare("method LIKE %s", '%' . $wpdb->esc_like( $method ) . '%');
		}

		if( !empty( $endpoint ) ){
			$where .= !empty( $where ) ? ' AND ' : 'WHERE ';
			$where .= $wpdb->prepare("endpoint LIKE %s", '%' . $wpdb->esc_like( $endpoint ) . '%');
		}

		if( !empty( $response_code ) ){
			$where .= !empty( $where ) ? ' AND ' : 'WHERE ';
			$where .= $wpdb->prepare("response_code = %d", $response_code);
		}

		$per_page = 10;
		$current_page = $this->get_pagenum();
		$total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name $where");

		$this->set_pagination_args([
			'total_items' => $total_items,
			'per_page' => $per_page
		]);

		$this->items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name $where ORDER BY $orderby $order LIMIT %d OFFSET %d",
				$per_page,
				($current_page - 1) * $per_page
			),
			ARRAY_A
		);

		// var_dump( $wpdb->last_query );
		// var_dump( $wpdb->last_error );

		$columns = $this->get_columns();
		$hidden = [];
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = [$columns, $hidden, $sortable];
	}
}