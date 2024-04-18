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

		$transfer = SO::get_balance_transfer();
		$cash = SO::get_balance_cash();
		$stock_value = SO::get_stock_value();
		$stock_pending_value = SO::get_pending_in_stock_value();
		?>
		<h1>Rangkuman</h1>
		
		<hr>

		<h2>Saldo transfer: <?php echo SO::currency($transfer); ?></h2>
		<h2>Saldo tunai: <?php echo SO::currency($cash); ?></h2>
		<h2>Nilai stok: <?php echo SO::currency($stock_value); ?></h2>
		<h2>Total aset: <?php echo SO::currency($transfer + $cash + $stock_value); ?></h2>
		<h2>Nilai stok belum diterima: <?php echo SO::currency($stock_pending_value); ?></h2>

		<hr>

		<h2>Total penagihan tempo <?php echo wp_date('F Y'); ?>: <?php echo SO::currency(SO::get_total_remaining(0)); ?></h2>
		<h2>Total penagihan tempo <?php echo wp_date('F Y', strtotime('+1 months')); ?>: <?php echo SO::currency(SO::get_total_remaining(1)); ?></h2>
		<h2>Total penagihan: <?php echo SO::currency(SO::get_total_remaining()); ?></h2>

		<hr>

		<h2>Laba: <?php echo SO::currency(SO::get_profit()); ?></h2>

		<hr>

		<h2>Pembelian belum diterima: <?php echo SO::get_undelivered_count('buy'); ?></h2>
		<h2>Penjualan belum dikirim: <?php echo SO::get_undelivered_count('sell'); ?></h2>
		<?php
	}
}

new Simple_Order_Dashboard();