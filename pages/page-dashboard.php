<?php

class Simple_Order_Dashboard {

	public function __construct() {
		add_action('admin_menu', [$this, 'menu']);
	}

	public function menu() {
		add_menu_page(
			'Manajemen Order',
			'Manajemen Order',
			'read',
			'simple-order',
			[$this, 'page'],
			'dashicons-admin-plugins',
			50
		);
	}

	public function page() {
		global $wpdb;

		SO::calculate_profit();

		$transfer = SO::get_balance_transfer();
		$cash = SO::get_balance_cash();
		$stock_value = SO::get_stock_value();
		$stock_pending_in_value = SO::get_stock_pending_in_value();
		$stock_pending_out_value = SO::get_stock_pending_out_value();
		$stock_profit_value = SO::get_stock_profit_value();
		$stock_pending_profit_value = SO::get_stock_pending_profit_value();
		?>
		<h1>Rangkuman</h1>
		
		<hr>

		<div class="dashboard-item">
			<table class="wp-list-table widefat striped table-view-list">
				<thead>
					<tr>
						<th colspan="2">Aset</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><strong>Saldo transfer</strong></td>
						<td class="text-right"><strong><?php echo SO::currency($transfer); ?></strong></td>
					</tr>
					<tr>
						<td><strong>Saldo tunai</strong></td>
						<td class="text-right"><strong><?php echo SO::currency($cash); ?></strong></td>
					</tr>
					<tr>
						<td><strong>Nilai stok</strong></td>
						<td class="text-right"><strong><?php echo SO::currency($stock_value); ?></strong></td>
					</tr>
					<tr>
						<td><strong>Total</strong></td>
						<td class="text-right" style="color:red"><strong><?php echo SO::currency($transfer + $cash + $stock_value); ?></strong></td>
					</tr>
				</tbody>
			</table>
		</div>

		<div class="dashboard-item">
			<table class="wp-list-table widefat striped table-view-list">
				<thead>
					<tr>
						<th colspan="2">Estimasi laba berdasarkan stok</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><strong>Stok tersedia</strong></td>
						<td class="text-right"><strong><?php echo SO::currency($stock_profit_value); ?></strong></td>
					</tr>
					<tr>
						<td><strong>Stok belum diterima</strong></td>
						<td class="text-right"><strong><?php echo SO::currency($stock_pending_profit_value); ?></strong></td>
					</tr>
					<tr>
						<td><strong>Total</strong></td>
						<td class="text-right" style="color:red"><strong><?php echo SO::currency($stock_profit_value + $stock_pending_profit_value); ?></strong></td>
					</tr>
				</tbody>
			</table>
		</div>

		<hr>

		<table class="wp-list-table widefat striped table-view-list" style="width:45%!important;">
			<thead>
				<tr>
					<th></th>
					<th class="text-right">Jumlah</th>
					<th class="text-right">Nilai Aset</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><strong>Pembelian belum diterima</strong></td>
					<td class="text-right"><strong><?php echo SO::get_undelivered_count('buy'); ?></strong></td>
					<td class="text-right"><strong><?php echo SO::currency($stock_pending_in_value); ?></strong></td>
				</tr>
				<tr>
					<td><strong>Penjualan belum dikirim</strong></td>
					<td class="text-right"><strong><?php echo SO::get_undelivered_count('sell'); ?></strong></td>
					<td class="text-right"><strong><?php echo SO::currency($stock_pending_out_value); ?></strong></td>
				</tr>
			</tbody>
		</table>

		<hr>

		<table class="wp-list-table widefat striped table-view-list" style="width:90%!important;">
			<thead>
				<tr>
					<th></th>
					<th class="text-right">Jumlah penjualan belum lunas</th>
					<th class="text-right">Jumlah penjualan lunas</th>
					<th class="text-right">Nominal penagihan</th>
					<th class="text-right">Laba penagihan</th>
					<th class="text-right">Laba akhir (penjualan lunas)</th>
					<th class="text-right">Omzet</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><strong><?php echo wp_date('F Y'); ?></strong></td>
					<td class="text-right"><strong><?php echo SO::get_sales_count(0); ?></strong></td>
					<td class="text-right"><strong><?php echo SO::get_sales_count(0, true); ?></strong></td>
					<td class="text-right"><strong><?php echo SO::currency(SO::get_total_remaining(0)); ?></strong></td>
					<td class="text-right"><strong><?php echo SO::currency(SO::get_profit(0)); ?></strong></td>
					<td class="text-right" style="color:red"><strong><?php echo SO::currency(SO::get_profit(0, true)); ?></strong></td>
					<td class="text-right"><strong><?php echo SO::currency(SO::get_sales(0)); ?></strong></td>
				</tr>
				<tr>
					<td><strong><?php echo wp_date('F Y', strtotime('+1 months')); ?></strong></td>
					<td class="text-right"><strong><?php echo SO::get_sales_count(1); ?></strong></td>
					<td class="text-right"><strong><?php echo SO::get_sales_count(1, true); ?></strong></td>
					<td class="text-right"><strong><?php echo SO::currency(SO::get_total_remaining(1)); ?></strong></td>
					<td class="text-right"><strong><?php echo SO::currency(SO::get_profit(1)); ?></strong></td>
					<td class="text-right" style="color:red"><strong><?php echo SO::currency(SO::get_profit(1, true)); ?></strong></td>
					<td class="text-right"><strong><?php echo SO::currency(SO::get_sales(1)); ?></strong></td>
				</tr>
				<tr>
					<td><strong>Total</strong></td>
					<td class="text-right"><strong><?php echo SO::get_sales_count(); ?></strong></td>
					<td class="text-right"><strong><?php echo SO::get_sales_count(null, true); ?></strong></td>
					<td class="text-right"><strong><?php echo SO::currency(SO::get_total_remaining()); ?></strong></td>
					<td class="text-right"><strong><?php echo SO::currency(SO::get_profit(null)); ?></strong></td>
				</tr>
			</tbody>
		</table>

		<?php
	}
}

new Simple_Order_Dashboard();