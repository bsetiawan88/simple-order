<?php

class Simple_Order_Buy {

	public function __construct() {
		add_action('admin_menu', [$this, 'menu']);
		add_action('wp_ajax_buy_ajax', [$this, 'ajax']);

		if (isset($_GET['page']) && ($_GET['page'] == 'simple-order-buy' && isset($_GET['id']))) {
			add_action('admin_enqueue_scripts', function() {
				wp_enqueue_style('simple-order-bootstrap', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css');
			});
		}
	}

	public function menu() {
		add_submenu_page(
			'simple-order',
			'Pembelian',
			'Pembelian',
			'read',
			'simple-order-buy',
			[$this, 'page']
		);
	}

	public function page() {
		global $wpdb;

		if (isset($_GET['id'])) {
			if (isset($_POST['invoice'])) {
				$purchase_id = sanitize_text_field($_GET['id']);
				$purchase = SO::get($wpdb->_PURCHASES, 'id', $purchase_id);

				if (!$purchase) {
					return;
				}
				
				$data = $_POST['invoice'];

				// Remove the data prefix
				$data = str_replace('data:image/jpeg;base64,', '', $data);
				$data = str_replace(' ', '+', $data);

				// Decode the base64 data
				$decoded = base64_decode($data);
				
				// Save the decoded data to a file (e.g., image.jpg)
				$relative_path = '/order/invoices/' . date('Y') . '/' . date('m') . '/';
				$path = WP_CONTENT_DIR . $relative_path;
				@mkdir($path, 0755, true);
				$filename = $purchase_id . '.jpg';
				file_put_contents($path . $filename, $decoded);

				$wpdb->update($wpdb->_FINANCE, ['invoice' => $relative_path . $filename], ['purchase_id' => $purchase_id])
				?>
				<div class="container mt-5">
					<h1>Data sudah disimpan</h1>
					<script>
						setTimeout(function() {
							window.location.href = '<?php echo admin_url('admin.php?page=simple-order-buy'); ?>';
						}, 1000);
					</script>
				</div>
				<?php
			} else {
				?>
				<div class="container mt-5">
				<h1>Upload nota</h1>
				<div id="hot-buy"></div>
				<form method="POST" style="margin-top:50px">
					<input type="hidden" id="invoice" name="invoice" />
					<img id="preview" />
					<div class="form-group">
						<label for="file">Nota</label>
						<input type="file" id="file" accept="image/jpeg" />
					</div>
					<input class="btn btn-primary" type="submit" value="Kirim"/>
				</form>
				</div>
				<?php
			}
		} else {
		?>
		<h1>Pembelian</h1>
		<div id="hot-buy"></div>
		<p>Catatan:</p>
		<ol>
			<li>
			Halaman ini digunakan untuk menampilkan pembelian yang <strong>belum diterima</strong> atau <strong>belum upload nota</strong>.
			</li>
			<li>
			Pembelian wajib upload nota.
			</li>
			<li>
			Klik tombol <code>Selesai</code> untuk update <strong>stok tersedia</strong>.
			</li>
		</ol>
		<?php
		}
	}

	public function ajax() {
		global $wpdb;

		$response = [
			'headers' => ['ID', 'Tanggal', 'Toko', 'Produk', 'Harga beli', 'Jumlah', 'Nominal', ''],
		];

		if (isset($_POST['method']) && $_POST['method'] == 'complete') {
			unset($response['headers']);

			$id = $_POST['id'];

			$purchase =$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->_PURCHASES} WHERE id = %d AND delivery_status != %s", $id, 'complete'));

			if ($purchase) {
				$result = $wpdb->update($wpdb->_PURCHASES, [
					'delivery_status' => 'complete'
				], [
					'id' => $id
				]);
	
				if ($result) {
					$purchase_details = SO::gets($wpdb->_PURCHASE_DETAILS, 'purchase_id', $id);
	
					foreach ($purchase_details as $d) {
						SO::update_stock($d->product_id, 'available', $d->qty, 'increase');
						SO::update_stock($d->product_id, 'pending_in', $d->qty, 'decrease');
						SO::update_stock_value($d->product_id);
					}
				}
			}
		}

		$query = "SELECT P.*, S.store_name, F.date FROM {$wpdb->_PURCHASES} P LEFT JOIN {$wpdb->_STORES} S ON P.store_id = S.id LEFT JOIN {$wpdb->_FINANCE} F ON F.purchase_id = P.id WHERE P.type = 'buy' AND ";

		if (isset($_POST['id'])) {
			$query .= $wpdb->prepare("P.id = %d", $_POST['id']);
		} else {
			$query .= $wpdb->prepare("(P.delivery_status = %s OR F.invoice IS NULL)", 'pending');
		}

		$results = $wpdb->get_results($query);

		for ($i = 0; $i < count($results); $i++) {
			// get products
			$details = $wpdb->get_results($wpdb->prepare("SELECT D.*, P.product_name FROM {$wpdb->_PURCHASE_DETAILS} D LEFT JOIN {$wpdb->_PRODUCTS} P ON D.product_id = P.id WHERE D.purchase_id = %d", $results[$i]->id));

			foreach ($details as $d) {
				$results[$i]->__children[] = [
					'product_name' => $d->product_name,
					'price' => $d->price,
					'qty' => $d->qty,
					'total' => $d->amount
				];
			}

			// $response['expanded'] = true;
		}

		$response['table'] = $results;

		$response['columns'] = [
			['data' => 'id', 'readOnly' => true],
			['data' => 'date', 'readOnly' => true],
			['data' => 'store_name', 'readOnly' => true],
			['data' => 'product_name', 'readOnly' => true],
			['data' => 'price', 'readOnly' => true],
			['data' => 'qty', 'readOnly' => true],
			['data' => 'pay_amount', 'readOnly' => true],
			['data' => 'action', 'readOnly' => true],
		];

		if (isset($_POST['id'])) {
			$i = count($response['columns']) - 1;
			unset($response['columns'][$i]);
		}

		wp_send_json_success($response);
	}
	
}

new Simple_Order_Buy();