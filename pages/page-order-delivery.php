<?php

class Simple_Order_delivery {

	public function __construct() {
		add_action('admin_menu', [$this, 'menu']);
		add_action('wp_ajax_delivery_ajax', [$this, 'ajax']);
	}

	public function menu() {
		add_submenu_page(
			'simple-order',
			'Pengiriman',
			'Pengiriman',
			'read',
			'simple-order-delivery',
			[$this, 'page']
		);
	}

	public function page() {
		?>
		<h1>Pengiriman</h1>
		<div id="hot-delivery"></div>
		<p>Catatan:</p>
		<ol>
			<li>
			Halaman ini digunakan untuk menampilkan penjualan yang <strong>sudah terjadwal</strong> dan <strong>belum dikirim</strong>.
			</li>
			<li>
			Klik tombol <code>Selesai</code> untuk update <strong>stok sudah dipesan</strong>.
			</li>
			<li>
			Penjadwalan dilakukan di halaman <strong>aktifitas</strong>.
			</li>
		</ol>
		<?php
	}

	public function ajax() {
		global $wpdb;

		$response = [
			'headers' => ['ID', 'Jadwal kirim', 'Kurir', 'Toko', 'Produk', 'Jumlah', 'Nominal', ''],
		];

		if (isset($_POST['method']) && $_POST['method'] == 'complete') {
			unset($response['headers']);

			$id = $_POST['id'];
			$result = $wpdb->update($wpdb->_PURCHASES, [
				'delivery_status' => 'complete'
			], [
				'id' => $id
			]);

			if ($result) {
				$purchase_details = SO::gets($wpdb->_PURCHASE_DETAILS, 'purchase_id', $id);

				foreach ($purchase_details as $d) {
					SO::update_stock($d->product_id, 'pending_out', $d->qty, 'decrease');
				}
			}
		}

		$results = $wpdb->get_results("SELECT * FROM {$wpdb->_PURCHASES} WHERE courier_id IS NOT NULL AND courier_id != 0 AND delivery_status NOT IN ('complete', 'cancel')");

		for ($i = 0; $i < count($results); $i++) {
			$results[$i]->delivery_scheduled_date = wp_date('l, d F Y', strtotime($results[$i]->delivery_scheduled_date));

			$results[$i]->courier_name = SO::get($wpdb->_COURIERS, 'id', $results[$i]->courier_id)->name;
			$results[$i]->store_name = SO::get($wpdb->_STORES, 'id', $results[$i]->store_id)->store_name;

			// get products
			$details = $wpdb->get_results($wpdb->prepare("SELECT D.*, P.product_name FROM {$wpdb->_PURCHASE_DETAILS} D LEFT JOIN {$wpdb->_PRODUCTS} P ON D.product_id = P.id WHERE D.purchase_id = %d", $results[$i]->id));

			foreach ($details as $d) {
				$results[$i]->__children[] = [
					'product_name' => $d->product_name,
					'qty' => $d->qty,
				];
			}
		}

		$response['table'] = $results;
		$response['columns'] = [
			['data' => 'id', 'readOnly' => true],
			['data' => 'delivery_scheduled_date', 'readOnly' => true],
			['data' => 'courier_name', 'readOnly' => true],
			['data' => 'store_name', 'readOnly' => true],
			['data' => 'product_name', 'readOnly' => true],
			['data' => 'qty', 'readOnly' => true],
			['data' => 'pay_amount', 'readOnly' => true],
			['data' => 'action', 'readOnly' => true],
		];

		wp_send_json_success($response);
	}
	
}

new Simple_Order_delivery();