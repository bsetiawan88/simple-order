<?php

class Simple_Order_Stores {

	public function __construct() {
		add_action('admin_menu', [$this, 'menu']);
		add_action('wp_ajax_stores_ajax', [$this, 'ajax']);
	}

	public function menu() {
		add_submenu_page(
			'simple-order',
			'Toko',
			'Toko',
			'read',
			'simple-order-stores',
			[$this, 'page']
		);
	}

	public function page() {
		?>
		<h1>Toko</h1>
		<div id="hot-stores"></div>
		<button class="button button-primary button-add" style="margin-top:10px">Tambah</button>
		<p>Catatan:</p>
		<ol>
			<li>
			Halaman ini digunakan untuk memilih toko saat pembelian atau penjualan.
			</li>
			<li>
			Isi <code>buy</code> pada kolom <code>Tipe</code> untuk toko yang digunakan untuk pembelian.
			</li>
			<li>
			Isi <code>sell</code> pada kolom <code>Tipe</code> untuk toko yang digunakan untuk penjualan.
			</li>
			<li>
			Jika kolom <code>Tipe</code> tidak diisi, maka toko tidak akan tampil di halaman pembelian atau penjualan.
			</li>
		</ol>
		<?php
	}

	public function ajax() {
		global $wpdb;

		$response = [
			'headers' => ['ID', 'Tipe', 'Nama toko', 'Alamat', 'Telepon', 'Kontak'],
		];

		if (isset($_POST['method'])) {
			if ($_POST['method'] == 'add') {
				$wpdb->insert($wpdb->_STORES, [
					'store_name' => 'toko baru'
				]);

				unset($response['headers']);
			} else if ($_POST['method'] == 'update') {
				$wpdb->update($wpdb->_STORES, 
				[
					sanitize_text_field($_POST['field']) => sanitize_text_field($_POST['after']),
				], [
					'id' => $_POST['id']
				]);

				Simple_Order::add_log('stores');

				unset($response['headers']);
			}
		}

		$results = $wpdb->get_results("SELECT * FROM {$wpdb->_STORES} ORDER BY type, store_name ASC");
		$response['table'] = $results;
		$response['columns'] = [
			['data' => 'id', 'readOnly' => true],
			['data' => 'type', 'type' => 'dropdown', 'source' => ['sell', 'buy']],
			['data' => 'store_name'],
			['data' => 'address'],
			['data' => 'phone'],
			['data' => 'contact'],
		];

		wp_send_json_success($response);
	}
	
}

new Simple_Order_Stores();