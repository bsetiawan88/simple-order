<?php

class Simple_Order_Couriers {

	public function __construct() {
		add_action('admin_menu', [$this, 'menu']);
		add_action('wp_ajax_couriers_ajax', [$this, 'ajax']);
	}

	public function menu() {
		add_submenu_page(
			'simple-order',
			'Kurir',
			'Kurir',
			'read',
			'simple-order-couriers',
			[$this, 'page']
		);
	}

	public function page() {
		?>
		<h1>Kurir</h1>
		<div id="hot-couriers"></div>
		<button class="button button-primary button-add" style="margin-top:10px">Tambah</button>
		<?php
	}

	public function ajax() {
		global $wpdb;

		$response = [
			'headers' => ['ID', 'Nama kurir', 'Telepon'],
		];

		if (isset($_POST['method'])) {
			if ($_POST['method'] == 'add') {
				$wpdb->insert($wpdb->_COURIERS, [
					'name' => 'kurir baru'
				]);

				unset($response['headers']);
			} else if ($_POST['method'] == 'update') {
				$wpdb->update($wpdb->_COURIERS, 
				[
					sanitize_text_field($_POST['field']) => sanitize_text_field($_POST['after']),
				], [
					'id' => $_POST['id']
				]);

				Simple_Order::add_log('couriers');

				unset($response['headers']);
			}
		}

		$results = $wpdb->get_results("SELECT * FROM {$wpdb->_COURIERS}");
		$response['table'] = $results;
		$response['columns'] = [
			['data' => 'id', 'readOnly' => true],
			['data' => 'name'],
			['data' => 'phone'],
		];

		wp_send_json_success($response);
	}
	
}

new Simple_Order_Couriers();