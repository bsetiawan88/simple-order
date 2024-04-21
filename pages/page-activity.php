<?php

class Simple_Order_Activity {

	public function __construct() {
		add_action('admin_menu', [$this, 'menu']);

		if (isset($_GET['page']) && ($_GET['page'] == 'simple-order-activity' || ($_GET['page'] == 'simple-order-sell' && isset($_GET['id'])))) {
			add_action('admin_enqueue_scripts', function() {
				wp_enqueue_style('simple-order-bootstrap', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css');
				wp_enqueue_script('jquery-ui-datepicker');
				wp_enqueue_style('jquery-style', '//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css');
				wp_enqueue_script('simple-order-finance', plugin_dir_url(SIMPLE_ORDER_PLUGIN_FILE) . 'assets/activity.js');
			});
		}
	}

	public function menu() {
		add_submenu_page(
			'simple-order',
			'Aktifitas',
			'Aktifitas',
			'read',
			'simple-order-activity',
			[$this, 'page']
		);
		
	}

	public function save_data() {
		global $wpdb;

		$balance = SO::get_balance_transfer();
		$action = sanitize_text_field($_POST['action']);

		if (method_exists($this, $action)) {
			$this->$action();
		}
	}

	public function page() {
		if (!empty($_POST)) {
			$this->save_data();
		}

		global $wpdb;

		$store_options = $wpdb->get_results("SELECT * FROM {$wpdb->_STORES} ORDER BY store_name ASC");
		$store_options_buy = $store_options_sell = '';
		if ($store_options) {
			foreach ($store_options as $o) {
				if ($o->type == 'buy') {
					$store_options_buy .= '<option value="' . $o->id . '">' . $o->store_name . '</option>';
				} else if ($o->type == 'sell') {
					$store_options_sell .= '<option value="' . $o->id . '">' . $o->store_name . '</option>';
				}
			}
		}

		$products_options = $wpdb->get_results("SELECT * FROM {$wpdb->_PRODUCTS} ORDER BY product_name ASC");

		$courier_options = $wpdb->get_results("SELECT * FROM {$wpdb->_COURIERS} ORDER BY name ASC");
		$courier_options_html = '';
		if ($courier_options) {
			foreach ($courier_options as $o) {
				$courier_options_html .= '<option value="' . $o->id . '">' . $o->name . '</option>';
			}
		}

		$form_action = admin_url('admin.php?page=simple-order-activity');
		?>
		<style>
			body, input, select {
				font-size: 12px!important;
			}
			.tr-produk {
				border-bottom: none;
			}
			.tr-option td {
				border-top: none;
			}
			#form_pembelian td, #form_penjualan td {
				padding: 5px!important;
			}
			p {
				margin-bottom: 0!important;
			}
			#ui-datepicker-div {
				z-index: 9999!important;
			}
		</style>
		<div class="container mt-5">
			<h1>Aktifitas baru</h1>

			<div class="form-group">
				<label for="aktifitas">Aktifitas:</label>
				<select id="aktifitas" class="form-control">
					<option selected> -- Pilih -- </option>
					<option value="pembelian">Pembelian</option>
					<option value="penjualan">Penjualan</option>
					<option value="pengeluaran">Pengeluaran</option>
					<option value="pengiriman">Pengiriman</option>
					<option value="penambahan_modal">Penambahan modal</option>
					<option value="pengambilan_laba">Pengambilan laba</option>
					<option value="pindah_saldo_tunai">Pindah saldo tunai</option>
				</select>
			</div>

			<div class="div-penambahan_modal" style="display:none">
				<form id="form_penambahan_modal" method="POST" action="<?php echo $form_action; ?>">
					<input type="hidden" name="action" value="penambahan_modal"/>
					<div class="form-group">
						<label for="penambahan_modal">Masuk:</label>
						<input type="number" class="form-control" name="penambahan_modal" required>
					</div>
					<button type="submit" class="btn btn-primary float-right">Kirim</button>
				</form>
			</div>

			<div class="div-pengambilan_laba" style="display:none">
				<form id="form_pengambilan_laba" method="POST" action="<?php echo $form_action; ?>">
					<input type="hidden" name="action" value="pengambilan_laba"/>
					<div class="form-group">
						<label for="pengambilan_laba">Keluar:</label>
						<input type="number" class="form-control" name="pengambilan_laba" required>
					</div>
					<button type="submit" class="btn btn-primary float-right">Kirim</button>
				</form>
			</div>

			<div class="div-pembelian" style="display:none">
				<form id="form_pembelian" method="POST" action="<?php echo $form_action; ?>">
					<input type="hidden" name="action" value="pembelian"/>
					<div class="form-group">
						<label for="pembelian-toko">Nama toko:</label>
						<select name="pembelian_toko" class="form-control nama-toko" required>
						<?php echo $store_options_buy; ?>
						</select>
					</div>
					<div class="form-group">
						<label for="search">Cari produk:</label>
						<input type="text" class="form-control search" data-parent="produk-table-beli" />
					</div>
					<div class="produk-table-beli">
						<?php echo $this->create_table($products_options, 'beli'); ?>
					</div>
					<button type="submit" class="btn btn-primary float-right">Kirim</button>
				</form>

			</div>

			<div class="div-penjualan" style="display:none">

				<form id="form_penjualan" method="POST" action="<?php echo $form_action; ?>">
					<input type="hidden" name="action" value="penjualan"/>
					<div class="form-group">
						<label for="penjualan-toko">Nama toko:</label>
						<select name="penjualan_toko" class="form-control nama-toko" required>
						<?php echo $store_options_sell; ?>
						</select>
					</div>
					<div class="form-group">
						<label for="search">Cari produk:</label>
						<input type="text" class="form-control search" data-parent="produk-table-jual" />
					</div>
					<div class="produk-table-jual">
						<?php echo $this->create_table($products_options, 'jual'); ?>
					</div>
					<button type="submit" class="btn btn-primary float-right">Kirim</button>
				</form>
			</div>

			<div class="div-pengeluaran" style="display:none">
				<form id="form_pengeluaran" method="POST" action="<?php echo $form_action; ?>">
					<input type="hidden" name="action" value="pengeluaran" />
					<div class="form-group">
						<label for="nominal">Nominal:</label>
						<input type="number" class="form-control" name="nominal" required>
					</div>
					<div class="form-group">
						<label for="keterangan">Keterangan:</label>
						<textarea name="keterangan" id="keterangan" class="form-control"></textarea>
					</div>
					<div class="form-group">
						<label for="sumber">Diambil dari:</label>
						<select id="sumber" name="sumber" class="form-control" required>
							<option value="transfer" selected>Saldo transfer</option>
							<option value="cash">Saldo tunai</option>
						</select>
					</div>
					<button type="submit" class="btn btn-primary float-right">Kirim</button>
				</form>
			</div>

			<div class="div-pindah_saldo_tunai" style="display:none">
				<form id="pindah_saldo_tunai" method="POST" action="<?php echo $form_action; ?>">
					<input type="hidden" name="action" value="pindah_saldo_tunai" />
					<div class="form-group">
						<label for="nominal">Jumlah yang akan dipindah:</label>
						<input type="number" class="form-control" name="nominal" value="<?php echo SO::get_balance_cash();?>" required>
					</div>
					<button type="submit" class="btn btn-primary float-right">Kirim</button>
				</form>
			</div>

			<div class="div-pengiriman" style="display:none">
				<form id="pengiriman" method="POST" action="<?php echo $form_action; ?>">
					<input type="hidden" name="action" value="pengiriman" />
					<div class="form-group">
						<label for="tanggal">Tanggal Kirim:</label>
						<input type="text" class="form-control datepicker" name="tanggal"  required/>
					</div>
					<div class="form-group">
						<label for="kurir">Kurir:</label>
						<select id="kurir" name="kurir" class="form-control" required>
							<?php echo $courier_options_html; ?>
						</select>
					</div>
					<br/>

					<a href="#" class="btn btn-primary select-all" style="margin-bottom:10px;">Pilih semua</a>
					<br/>
					<div id="hot-sell-delivery"></div>
					<br/>
					<button type="submit" class="btn btn-primary float-right">Kirim</button>
				</form>
			</div>

		</div>
		<?php
	}
	
	function create_table($data_array, $mode) {
		$table = '<table class="table">';
		
		// Create table header
		$table .= '<tr>';
		$table .= '<th class="text-right">Harga Beli</th>';
		$table .= '<th class="text-right">Harga Jual</th>';
		$table .= '<th class="text-right" style="width:100px;">Jumlah</th>';
		$table .= '<th class="text-right">Subtotal</th>';
		$table .= '</tr>';
	
		foreach ($data_array as $row_data) {
			$row = '';

			$row .= '<tr class="tr-produk" data-name="' . $row_data->product_name . '">';
			$row .= '<td colspan="4"><input type="hidden" class="form-control" name="data[product_id][]" value="' . $row_data->id . '">' . $row_data->product_name . ' (stok: '. $row_data->stock_available . ')</td>';
			$row .= '</tr>';

			$row .= '<tr class="tr-option" data-name="' . $row_data->product_name . '">';
			if ($mode == 'beli') {
				$row .= '<td><input type="number" name="data[price_buy][]" class="form-control text-right harga-beli" value="' . $row_data->price_buy . '"></td>';
			} else {
				$row .= '<td><p class="text-right">' . $row_data->price_buy . '</p></td>';
			}

			if ($mode == 'beli') {
				$row .= '<td><p class="text-right">' . $row_data->price_sell . '</p></td>';
			} else {
				$row .= '<td><input type="number" name="data[price_sell][]" class="form-control text-right harga-jual" value="' . $row_data->price_sell . '"></td>';
			}

			$row .= '<td><input type="number" name="data[qty][]" class="form-control jumlah text-right" value="0"></td>';
			$row .= '<td><p class="text-right subtotal">0</p></td>';
			$row .= '</tr>';

			$table .= $row;
		}

		$table .= '<tr>';
		$table .= '<td colspan="3"><p class="text-right">Diskon/potongan harga</p></td>';
		$table .= '<td><input type="number" name="diskon" class="form-control text-right diskon"></td>';
		$table .= '</tr>';

		$table .= '<tr>';
		$table .= '<td colspan="3"><p class="text-right">Biaya tambahan (ongkir dll)</p></td>';
		$table .= '<td><input type="number" name="biaya_tambahan" class="form-control text-right biaya"></td>';
		$table .= '</tr>';

		if ($mode !== 'beli') {
			$table .= '<tr>';
			$table .= '<td colspan="3"><p class="text-right">Tempo</p></td>';
			$table .= '<td><input type="text" name="tempo" class="form-control datepicker"></td>';
			$table .= '</tr>';
		}

		$table .= '<tr>';
		$table .= '<td colspan="3"><p class="text-right">Total</p></td>';
		$table .= '<td><p class="text-right total">0</p></td>';
		$table .= '</tr>';

		$table .= '</table>';
	
		return $table;
	}
	
	public function penambahan_modal() {
		$data = [
			'type' => 'in',
			'description' => 'Penambahan modal',
			'amount' => $_POST['penambahan_modal'],
		];

		SO::insert_finance($data);
	}

	public function pengambilan_laba() {
		$data = [
			'type' => 'out',
			'description' => 'Pengambilan laba',
			'amount' => $_POST['pengambilan_laba']
		];

		SO::insert_finance($data);
	}

	public function pembelian() {
		global $wpdb;
		
		$store = SO::get($wpdb->_STORES, 'id', $_POST['pembelian_toko']);

		if ($store) {
			$purchase_data = [
				'store_id' => intval($_POST['pembelian_toko']),
				'type' => 'buy',
				'delivery_status' => 'pending',
				'discount' => intval($_POST['diskon']),
				'additional_fee' => intval($_POST['biaya_tambahan']),
				'payment_status' => 'complete'
			];

			$wpdb->insert($wpdb->_PURCHASES, $purchase_data);
			$purchase_id = $wpdb->insert_id;

			$total = 0;
			$subtotal = 0;
			$i = 0;
			foreach ($_POST['data']['qty'] as $qty) {
				if ($qty) {
					$subtotal = $_POST['data']['price_buy'][$i] * $qty;
					$total += $subtotal;

					$purchase_details_data = [
						'purchase_id' => $purchase_id,
						'product_id' => $_POST['data']['product_id'][$i],
						'qty' => $qty,
						'price' => $_POST['data']['price_buy'][$i],
						'amount' => $subtotal
					];

					$wpdb->insert($wpdb->_PURCHASE_DETAILS, $purchase_details_data);

					// update stock pending in
					SO::update_stock($_POST['data']['product_id'][$i], 'pending_in', $qty, 'increase');
				}

				$i++;
			}

			// update purchase
			$pay_amount = $total - intval($_POST['diskon']) + intval($_POST['biaya_tambahan']);

			$wpdb->update($wpdb->_PURCHASES, [
				'total' => $total,
				'pay_amount' => $pay_amount
			], [
				'id' => $purchase_id
			]);

			$data = [
				'type' => 'out',
				'purchase_id' => $purchase_id,
				'description' => 'Pembelian toko: ' . $store->store_name,
				'amount' => $pay_amount,
			];

			SO::insert_finance($data);
		}
	}

	public function penjualan() {
		global $wpdb;
		$balance = SO::get_balance_transfer();

		$store = SO::get($wpdb->_STORES, 'id', $_POST['penjualan_toko']);

		if ($store) {
			$purchase_data = [
				'store_id' => intval($_POST['penjualan_toko']),
				'type' => 'sell',
				'delivery_status' => 'pending',
				'discount' => intval($_POST['diskon']),
				'additional_fee' => intval($_POST['biaya_tambahan']),
				'payment_status' => 'pending',
			];

			if (!empty($_POST['tempo'])) {
				$date = strtotime($_POST['tempo']);
				$purchase_data['payment_scheduled_date'] = date("Y-m-d", $date);
			}

			$wpdb->insert($wpdb->_PURCHASES, $purchase_data);
			$purchase_id = $wpdb->insert_id;

			$total = 0;
			$subtotal = 0;
			$i = 0;
			foreach ($_POST['data']['qty'] as $qty) {
				if ($qty) {
					$subtotal = $_POST['data']['price_sell'][$i] * $qty;
					$total += $subtotal;

					$purchase_details_data = [
						'purchase_id' => $purchase_id,
						'product_id' => $_POST['data']['product_id'][$i],
						'qty' => $qty,
						'price' => $_POST['data']['price_sell'][$i],
						'amount' => $subtotal
					];

					$wpdb->insert($wpdb->_PURCHASE_DETAILS, $purchase_details_data);

					// update stock pending out and available
					SO::update_stock($_POST['data']['product_id'][$i], 'pending_out', $qty, 'increase');
					SO::update_stock($_POST['data']['product_id'][$i], 'available', $qty, 'decrease');
				}

				$i++;
			}

			// update purchase
			$pay_amount = $total - intval($_POST['diskon']) + intval($_POST['biaya_tambahan']);

			$wpdb->update($wpdb->_PURCHASES, [
				'total' => $total,
				'pay_amount' => $pay_amount
			], [
				'id' => $purchase_id
			]);

			SO::calculate_profit();
		}
	}

	public function pengeluaran() {
		if ($_POST['sumber'] == 'cash') {
			$method = 'cash';
		} else {
			$method = 'transfer';
		}

		$data = [
			'type' => 'out',
			'description' => 'Pengeluaran: ' . sanitize_text_field($_POST['keterangan']),
			'amount' => $_POST['nominal'],
			'method' => $method
		];

		SO::insert_finance($data);
	}

	public function pindah_saldo_tunai() {
		$balance = SO::get_balance_cash();
		$amount = intval($_POST['nominal']);

		if ($amount > $balance) {
			$amount = $balance;
		}

		$data = [
			'type' => 'out',
			'description' => 'Pindah saldo tunai',
			'amount' => $amount,
			'method' => 'cash'
		];

		SO::insert_finance($data);

		$data = [
			'type' => 'in',
			'description' => 'Pindah saldo tunai',
			'amount' => $amount,
			'method' => 'transfer'
		];

		SO::insert_finance($data);
	}

	public function pengiriman() {
		global $wpdb;

		$courier_id = intval($_POST['kurir']);

		$courier = SO::get($wpdb->_COURIERS, 'id', $courier_id);
		if (!$courier) {
			return;
		}

		$date = strtotime($_POST['tanggal']);
		$date = date("Y-m-d", $date);

		foreach ($_POST['purchase_id'] as $id) {
			$purchase = SO::get($wpdb->_PURCHASES, 'id', $id);

			if ($purchase) {
				// update
				$wpdb->update($wpdb->_PURCHASES, ['courier_id' => $courier_id, 'delivery_scheduled_date' => $date], ['id' => $id]);
			}
		}
	}
}

new Simple_Order_Activity();