<?php

class Simple_Order_Finance {

	public function __construct() {
		add_action('admin_menu', [$this, 'menu']);
		add_action('wp_ajax_finance_ajax', [$this, 'ajax']);
	}

	public function menu() {
		add_submenu_page(
			'simple-order',
			'Laporan',
			'Laporan',
			'read',
			'simple-order-finance',
			[$this, 'page']
		);
	}

	public function page() {
		?>
		<h1>Laporan Transfer</h1>
		<h2>Saldo: <?php echo Simple_Order::currency(Simple_Order::get_balance_transfer()); ?></h2>
		<div id="hot-transfers"></div>
		<hr>
		<h1>Laporan Tunai</h1>
		<h2>Saldo: <?php echo Simple_Order::currency(Simple_Order::get_balance_cash()); ?></h2>
		<div id="hot-cash"></div>
		<p>Catatan:</p>
		<ol>
			<li>
				Saldo tunai wajib dipindah ke saldo transfer pada awal bulan untuk perhitungan laba dan audit.
			</li>
		</ol>
		<?php
	}

	public function ajax() {
		global $wpdb;

		$response = [
			'headers' => ['ID', 'Tanggal', 'Tipe', 'Deskripsi', 'Nominal', 'Saldo', 'Nota', 'Catatan'],
		];

		if (isset($_POST['method'])) {
			$method = sanitize_text_field($_POST['method']);
		} else {
			$method = 'transfer';
		}

		$results = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->_FINANCE} WHERE method = %s ORDER BY id ASC LIMIT 100", $method));

		$response['table'] = $results;
		$response['columns'] = [
			['data' => 'id', 'readOnly' => true],
			['data' => 'date', 'readOnly' => true, 'width' => '80px'],
			['data' => 'type', 'readOnly' => true],
			['data' => 'description', 'readOnly' => true, 'width' => '150px'],
			['data' => 'amount', 'readOnly' => true],
			['data' => 'balance', 'readOnly' => true],
			['data' => 'invoice', 'readOnly' => true],
			['data' => 'note', 'readOnly' => true],
		];

		wp_send_json_success($response);
	}
	
}

new Simple_Order_Finance();