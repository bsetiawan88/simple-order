<?php

class Simple_Order_Products {

	public function __construct() {
		add_action('admin_menu', [$this, 'menu']);
		add_action('wp_ajax_products_ajax', [$this, 'ajax']);
	}

	public function menu() {
		add_submenu_page(
			'simple-order',
			'Produk',
			'Produk',
			'read',
			'simple-order-products',
			[$this, 'page']
		);
	}

	public function page() {
		?>
		<h1>Produk</h1>
		<h2>Total nilai stok: <?php echo SO::currency(SO::get_stock_value()); ?></h2>
		<h2>Total estimasi laba stok: <?php echo SO::currency(SO::get_stock_profit_value()); ?></h2>
		<div id="hot-products" style="width:100%"></div>
		<button class="button button-primary button-add" style="margin-top:10px">Tambah</button>
		<?php
	}

	public function ajax() {
		global $wpdb;

		$response = [
			'headers' => ['ID', 'Nama produk', 'Harga beli', 'Harga jual', 'Estimasi laba', 'Stok tersedia', 'Stok belum masuk', 'Stok sudah dipesan', 'Nilai stok'],
		];

		if (isset($_POST['method'])) {
			if ($_POST['method'] == 'add') {
				$wpdb->insert($wpdb->_PRODUCTS, [
					'product_name' => 'zzz produk baru'
				]);

				unset($response['headers']);
			} else if ($_POST['method'] == 'update') {
				$wpdb->update($wpdb->_PRODUCTS, 
				[
					sanitize_text_field($_POST['field']) => sanitize_text_field($_POST['after'])
				], [
					'id' => $_POST['id']
				]);

				SO::add_log('products');

				unset($response['headers']);
			}
		}

		$edit_stock = get_option('simple_order_edit_stock', false);
		$edit_stock = $edit_stock ? false : true;

		$results = $wpdb->get_results("SELECT * FROM {$wpdb->_PRODUCTS} ORDER BY product_name ASC");

		for ($i = 0; $i < count($results); $i++) {
			$results[$i]->est = $results[$i]->price_sell - $results[$i]->price_buy;
			$results[$i]->stock_value = $results[$i]->stock_available * $results[$i]->price_buy;
		}

		$response['table'] = $results;
		$response['columns'] = [
			['data' => 'id', 'readOnly' => true],
			['data' => 'product_name'],
			['data' => 'price_buy'],
			['data' => 'price_sell'],
			['data' => 'est', 'readOnly' => true],
			['data' => 'stock_available', 'readOnly' => $edit_stock],
			['data' => 'stock_pending_in', 'readOnly' => true],
			['data' => 'stock_pending_out', 'readOnly' => true],
			['data' => 'stock_value', 'readOnly' => true],
		];

		wp_send_json_success($response);
	}
	
}

new Simple_Order_Products();