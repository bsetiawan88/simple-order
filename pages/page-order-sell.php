<?php

class Simple_Order_Sell {

	public function __construct() {
		add_action('admin_menu', [$this, 'menu']);
		add_action('wp_ajax_sell_ajax', [$this, 'ajax']);
		add_action('wp_ajax_sell_for_delivery_ajax', [$this, 'ajax_delivery']);
	}

	public function menu() {
		add_submenu_page(
			'simple-order',
			'Penjualan',
			'Penjualan',
			'read',
			'simple-order-sell',
			[$this, 'page']
		);
	}

	public function page() {
		global $wpdb;

		if (isset($_GET['id'])) {
			$purchase_id = sanitize_text_field($_GET['id']);
			$purchase = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->_PURCHASES} WHERE id = %d AND type = %s AND payment_status != %s", $purchase_id, 'sell', 'complete'));

			if (!$purchase) {
				return;
			}

			$remaining = SO::get_remaining_payment($purchase_id);

			if (!$remaining) {
				$remaining = $purchase->total;
			}

			if (!empty($_POST['payment_method'])) {
				$amount = $_POST['amount'];

				if (!empty($_POST['payment_date'])) {
					$date = strtotime($_POST['payment_date']);
					$formatted_date = date("Y-m-d", $date);
					$payment_date = date('Y-m-d', $date);
				} else {
					$payment_date = date('Y-m-d');
				}

				$note = sanitize_text_field($_POST['note']);
				$payment_method = sanitize_text_field($_POST['payment_method']);

				if ($payment_method == 'transfer') {
					$balance = SO::get_balance_transfer();
				} else {
					$balance = SO::get_balance_cash();
				}

				$store = SO::get($wpdb->_STORES, 'id', $purchase->store_id);
				$description = 'Pembayaran order: #' . $purchase_id . ' (toko: ' . $store->store_name .')';

				$payment_status = 'partial';
				$count = SO::get_payment_count($purchase_id);

				if ($amount >= $remaining) {
					$amount = $remaining;
					$payment_status = 'complete';
					if (empty($count)) {
						$description .= ' - lunas';
					} else {
						$count++;
						$description .= ' - pembayaran ke ' . $count . ' - lunas';
					}
				} else {
					$count++;
					$description .= ' - pembayaran ke-' . $count;
				}

				$balance += $amount;

				$data = [
					'date' => $payment_date,
					'type' => 'in',
					'method' => $payment_method,
					'purchase_id' => $purchase_id,
					'description' => $description,
					'amount' => $amount,
					'note' => $note
				];

				$result = SO::insert_finance($data);

				if ($result) {
					// update purchase
					$wpdb->update($wpdb->_PURCHASES, [
						'payment_status' => $payment_status
					], [
						'id' => $purchase_id
					]);
				}
				?>
				<div class="container mt-5">
					<h1>Data sudah disimpan</h1>
					<script>
						setTimeout(function() {
							window.location.href = '<?php echo admin_url('admin.php?page=simple-order-sell'); ?>';
						}, 1000);
					</script>
				</div>
				<?php
			} else if (isset($_POST['cancel_confirm'])) {
				$result = $wpdb->update($wpdb->_PURCHASES, [
					'payment_status' => 'cancel',
					'delivery_status' => 'cancel'
				], [
					'id' => $purchase_id
				]);
	
				if ($result) {
					$purchase_details = SO::gets($wpdb->_PURCHASE_DETAILS, 'purchase_id', $purchase_id);

					foreach ($purchase_details as $d) {
						SO::update_stock($d->product_id, 'available', $d->qty, 'increase');
						SO::update_stock($d->product_id, 'pending_out', $d->qty, 'decrease');
					}
				}
				?>
				<div class="container mt-5">
					<h1>Data sudah disimpan</h1>
					<script>
						setTimeout(function() {
							window.location.href = '<?php echo admin_url('admin.php?page=simple-order-sell'); ?>';
						}, 1000);
					</script>
				</div>
				<?php
			} else {
				?>
				<div class="container mt-5">

					<h1>Form pembayaran</h1>

					<div id="hot-sell"></div>

					<form method="POST" style="margin-top:50px">

						<div class="form-group">
							<label for="payment_method">Metode pembayaran</label>
							<select id="payment_method" name="payment_method" class="form-control">
								<option selected value="transfer">Transfer</option>
								<option value="cash">Tunai</option>
							</select>
						</div>

						<div class="form-group">
							<label for="amount">Jumlah</label>
							<input type="number" id="amount" name="amount" class="form-control" value="<?php echo $remaining; ?>"/>
						</div>

						<div class="form-group">
							<label for="payment_date">Tanggal pembayaran</label>
							<input type="text" id="payment_date" name="payment_date" class="form-control datepicker" />
						</div>

						<div class="form-group">
							<label for="note">Catatan</label>
							<textarea id="note" name="note" class="form-control"></textarea>
						</div>

						<input class="btn btn-primary" type="submit" value="Kirim"/>

					</form>

					<hr>

					<h1>Form pembatalan</h1>

					<form method="POST" style="margin-top:50px">

						<div class="form-group">
							<input type="checkbox" id="cancel_confirm" name="cancel_confirm" class="form-control" value="1" />
							<label for="cancel_confirm">Konfirmasi pembatalan</label>
						</div>

						<input class="btn btn-primary" type="submit" value="Batalkan"/>

					</form>
				</div>
			<?php
			}
		} else {
		?>
		<h1>Penjualan</h1>
		<h2>Total penagihan: <?php echo SO::currency(SO::get_total_remaining()); ?></h2>

		<div>
			<label for="filter1" class="filter-label"><input id="filter1" class="filter-button" data-action="unpaid" type="radio" name="opt" checked> Belum lunas</label>
			<label for="filter2" class="filter-label"><input id="filter2" class="filter-button" data-action="pending" type="radio" name="opt"> Belum dikirim</label>
			<label for="filter3" class="filter-label"><input id="filter3" class="filter-button" data-action="complete" type="radio" name="opt"> Selesai</label>
			 |
			<label class="filter-label"><a class="expand-all" href="#">Detail</a></label>
			<label class="filter-label"><a class="collapse-all" href="#">Ringkasan</a></label>
		</div>

		<br/>

		<div id="hot-sell"></div>
		<p>Catatan:</p>
		<ol>
			<li>
			Klik tombol <code>Pembayaran</code> untuk pelunasan.
			</li>
			<li>
			Tanggal yang berwarna merah adalah tanggal tempo.
			</li>
		</ol>
		<?php
		}
	}

	public function ajax() {
		global $wpdb;

		$response = [
			'headers' => ['ID', 'Tanggal', 'Toko', 'Produk', 'Harga jual', 'Jumlah', 'Nominal', 'Sisa bayar', 'Laba', 'Laba %', 'Jadwal kirim', ''],
		];

		$query = "SELECT P.*, S.store_name FROM {$wpdb->_PURCHASES} P LEFT JOIN {$wpdb->_STORES} S ON P.store_id = S.id WHERE P.type = 'sell' ";

		if (isset($_POST['id'])) {
			if ($_POST['page'] == 'stores') {
				$query .= ' AND S.id = ' . intval($_POST['id']);
			} else {
				$query .= ' AND P.id = ' . intval($_POST['id']);
			}
		} else if (isset($_POST['data'])) {
			if ($_POST['data'] == 'pending') {
				$query .= " AND P.delivery_status NOT IN ('complete', 'cancel')";
			} else if ($_POST['data'] == 'unpaid') {
				$query .= " AND P.payment_status NOT IN ('complete', 'cancel')";
			} else if ($_POST['data'] == 'complete') {
				$query .= " AND P.delivery_status = 'complete' AND P.payment_status = 'complete'";
			}
			unset($response['headers']);
		} else {
			$query .= " AND P.payment_status NOT IN ('complete', 'cancel')";
		}

		$query .= " ORDER BY P.id DESC LIMIT 100";

		$results = $wpdb->get_results($query);

		for ($i = 0; $i < count($results); $i++) {
			$results[$i]->purchase_date = wp_date('l, d F Y', strtotime($results[$i]->purchase_date));

			if ($results[$i]->delivery_scheduled_date) {
				$results[$i]->delivery_scheduled_date = wp_date('l, d F Y', strtotime($results[$i]->delivery_scheduled_date));
			}
			
			if ($results[$i]->payment_status == 'complete') {
				$results[$i]->remaining = null;
			} else {
				$results[$i]->remaining = SO::get_remaining_payment($results[$i]->id);

				if (!$results[$i]->remaining) {
					$results[$i]->remaining = $results[$i]->total;
				}
			}

			if ($results[$i]->payment_scheduled_date) {
				$results[$i]->purchase_date .= '.' . wp_date('l, d F Y', strtotime($results[$i]->payment_scheduled_date));
			}

			// get products
			$details = $wpdb->get_results($wpdb->prepare("SELECT D.*, P.product_name FROM {$wpdb->_PURCHASE_DETAILS} D LEFT JOIN {$wpdb->_PRODUCTS} P ON D.product_id = P.id WHERE D.purchase_id = %d", $results[$i]->id));

			foreach ($details as $d) {
				$results[$i]->__children[] = [
					'product_name' => '[' . $d->product_id . '] ' . $d->product_name,
					'price' => $d->price,
					'qty' => $d->qty,
					'total' => $d->amount
				];
			}

			$profit_percentage = round($results[$i]->profit / $results[$i]->pay_amount * 100, 2);
			$results[$i]->profit_percentage = $profit_percentage;
		}

		$response['table'] = $results;

		$response['columns'] = [
			['data' => 'id', 'readOnly' => true],
			['data' => 'purchase_date', 'readOnly' => true, 'width' => '100px'],
			['data' => 'store_name', 'readOnly' => true],
			['data' => 'product_name', 'readOnly' => true],
			['data' => 'price', 'readOnly' => true],
			['data' => 'qty', 'readOnly' => true],
			['data' => 'pay_amount', 'readOnly' => true],
			['data' => 'remaining', 'readOnly' => true],
			['data' => 'profit', 'readOnly' => true],
			['data' => 'profit_percentage', 'readOnly' => true],
			['data' => 'delivery_scheduled_date', 'readOnly' => true],
			['data' => 'action', 'readOnly' => true],
		];

		if (isset($_POST['id']) || (isset($_POST['data']) && $_POST['data'] == 'complete')) {
			$i = count($response['columns']) - 1;
			unset($response['columns'][$i]);
		}

		if (isset($_POST['page']) && $_POST['page'] == 'stores') {
			unset($response['columns'][2]);
			unset($response['headers'][2]);

			$response['headers'] = array_values($response['headers']);
			$response['columns'] = array_values($response['columns']);
		}

		wp_send_json_success($response);
	}

	public function ajax_delivery() {
		global $wpdb;

		$response = [
			'headers' => ['', 'ID', 'Tanggal', 'Toko', 'Produk', 'Jumlah', 'Nominal'],
		];

		$query = "SELECT P.*, S.store_name FROM {$wpdb->_PURCHASES} P LEFT JOIN {$wpdb->_STORES} S ON P.store_id = S.id WHERE P.type = 'sell' AND P.delivery_status NOT IN ('complete', 'cancel') AND delivery_scheduled_date IS NULL";

		$results = $wpdb->get_results($query);

		for ($i = 0; $i < count($results); $i++) {
			if ($results[$i]->payment_scheduled_date) {
				$results[$i]->purchase_date .= '.' . $results[$i]->payment_scheduled_date;
			}

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
			['data' => 'action', 'readOnly' => true],
			['data' => 'id', 'readOnly' => true],
			['data' => 'purchase_date', 'readOnly' => true, 'width' => '100px'],
			['data' => 'store_name', 'readOnly' => true],
			['data' => 'product_name', 'readOnly' => true],
			['data' => 'qty', 'readOnly' => true],
			['data' => 'pay_amount', 'readOnly' => true],
		];

		wp_send_json_success($response);
	}
	
}

new Simple_Order_Sell();