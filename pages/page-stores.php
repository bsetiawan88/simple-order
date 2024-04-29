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
		global $wpdb;

		if (isset($_GET['id'])) {
			$store_id = sanitize_text_field($_GET['id']);

			$store = SO::get($wpdb->_STORES, 'id', $store_id);
			?>
			<h1>Riwayat penjualan toko <?php echo $store->store_name; ?></h1>
			<br/>

			<div>
				<label class="filter-label"><a class="expand-all" href="#">Detail</a></label>
				<label class="filter-label"><a class="collapse-all" href="#">Ringkasan</a></label>
			</div>
			<br/>

			<div id="hot-sell"></div>
			<?php
		} else {
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
	}

	public function ajax() {
		global $wpdb;

		$response = [
			'headers' => ['ID', 'Tipe', 'Nama toko', 'Alamat', 'Telepon', 'Kontak'],
		];

		if (isset($_POST['method'])) {
			if ($_POST['method'] == 'add') {
				$wpdb->insert($wpdb->_STORES, [
					'store_name' => 'zzz toko baru'
				]);
			} else if ($_POST['method'] == 'update') {
				$wpdb->update($wpdb->_STORES, 
				[
					sanitize_text_field($_POST['field']) => sanitize_text_field($_POST['after']),
				], [
					'id' => $_POST['id']
				]);

				SO::add_log('stores');
			}

			unset($response['headers']);
		}

		$response['columns'] = [
			['data' => 'id', 'readOnly' => true],
			['data' => 'type', 'type' => 'dropdown', 'source' => ['sell', 'buy']],
			['data' => 'store_name'],
			['data' => 'address'],
			['data' => 'phone'],
			['data' => 'contact'],
		];

		$results = $wpdb->get_results("SELECT * FROM {$wpdb->_STORES} ORDER BY type, store_name ASC");
		if ($results) {
			if (isset($response['headers'])) {
				$response['headers'][] = [
					'Omzet ' . date('F Y', time())
				];
			}
			
			$response['columns'][] = [
				'data' => 'sales_0',
				'readOnly' => true
			];

			for ($i = 0; $i < count($results); $i++) {
				if ($results[$i]->type != 'sell') continue;

				$count = SO::get_store_sales_count($results[$i]->id);
				if ($count) {
					$results[$i]->sales_0 = SO::currency(SO::get_store_sales_month($results[$i]->id)) . ' / ' . $count;
				}
			}
		}

		if (isset($response['headers'])) {
			$response['headers'][] = [
				''
			];
		}

		$response['columns'][] = [
			'data' => 'action',
			'readOnly' => true
		];

		$response['table'] = $results;

		wp_send_json_success($response);
	}
	
}

new Simple_Order_Stores();