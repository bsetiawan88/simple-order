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

		$transfer = Simple_Order::get_balance_transfer();
		$cash = Simple_Order::get_balance_cash();
		$stock_value = Simple_Order::get_stock_value();
		
		?>
		<h1>Rangkuman</h1>
		
		<hr>

		<h2>Saldo transfer: <?php echo Simple_Order::currency($transfer); ?></h2>
		<h2>Saldo tunai: <?php echo Simple_Order::currency($cash); ?></h2>
		<h2>Nilai stok: <?php echo Simple_Order::currency($stock_value); ?></h2>
		<h2>Total aset: <?php echo Simple_Order::currency($transfer + $cash + $stock_value); ?></h2>

		<hr>

		<h2>Total penagihan: <?php echo Simple_Order::currency(Simple_Order::get_total_remaining()); ?></h2>
		<h2>Laba: <?php echo Simple_Order::currency(Simple_Order::get_profit()); ?></h2>

		<hr>

		<h2>Pembelian belum diterima: <?php echo Simple_Order::get_undelivered_count('buy'); ?></h2>
		<h2>Penjualan belum dikirim: <?php echo Simple_Order::get_undelivered_count('sell'); ?></h2>
		<?php
	}
}

new Simple_Order_Dashboard();