<?php
/*
Plugin Name: # Simple Order
Version: 1.0
Author: Bagus Pribadi Setiawan
*/

class Simple_Order {

	public function __construct() {
		define('INTIAL_VALUE', 100);
		
		register_activation_hook(__FILE__, [$this, 'plugin_activate']);
		add_action('init', [$this, 'init']);
		add_action('admin_enqueue_scripts', [$this, 'enqueue']);

		global $wpdb;

		$tables = array(
			'finance',
			'couriers',
			'logs',
			'products',
			'purchases',
			'purchase_details',
			'stores'
		);
		
		foreach ($tables as $table) {
			$wpdb->{'_' . strtoupper($table)} = $wpdb->prefix . 'zzz_' . $table;
		}
		
		require_once 'page-dashboard.php';
		require_once 'page-activity.php';
		require_once 'page-finance.php';
		require_once 'page-products.php';
		require_once 'page-stores.php';
		require_once 'page-couriers.php';
		require_once 'page-order-sell.php';
		require_once 'page-order-buy.php';
		require_once 'page-order-delivery.php';
	}

	public function init() {
		// if (isset($_GET['clear'])) {
		// 	global $wpdb;
		// 	$wpdb->query("TRUNCATE TABLE {$wpdb->prefix}zzz_finance");
		// 	$wpdb->query("TRUNCATE TABLE {$wpdb->prefix}zzz_purchases");
		// 	$wpdb->query("TRUNCATE TABLE {$wpdb->prefix}zzz_purchase_details");
		// 	$wpdb->query("TRUNCATE TABLE {$wpdb->prefix}zzz_logs");
		// 	$wpdb->query("UPDATE {$wpdb->prefix}zzz_products SET stock_value = 0, stock_available = 0, stock_pending_in = 0, stock_pending_out = 0");
		// }

		if (isset($_GET['aa']) && isset($_GET['bb'])) {
			update_option('simple_order_' . sanitize_text_field($_GET['aa']), sanitize_text_field($_GET['bb']));
		}
	}

	public function enqueue() {
		echo '<style>
		a[href="admin.php?page=simple-order-products"] { border-top:1px solid red; }
		a[href="admin.php?page=simple-order-sell"] { border-top:1px solid red; }
		</style>';
		wp_enqueue_style('handsontable', plugin_dir_url(__FILE__) . '/assets/handsontable.css');
		wp_enqueue_script('handsontable', plugin_dir_url(__FILE__) . '/assets/handsontable.js');

		wp_enqueue_script('simple-order', plugin_dir_url(__FILE__) . '/assets/script.js');
		wp_localize_script('simple-order', 'simple_order', [
			'ajax_url' => admin_url('admin-ajax.php'),
			'admin_url' => admin_url(),
			'home_url' => home_url()
		]);
	}

	public static function currency($amount) {
		return 'Rp ' . number_format(intval($amount), 0, ',', '.');
	}

	public static function add_log($table) {
		global $wpdb;

		$wpdb->insert($wpdb->prefix . 'zzz_logs', [
			'change_table' => $table,
			'change_field' => sanitize_text_field($_POST['field']),
			'value_before' => sanitize_text_field($_POST['before']),
			'value_after' => sanitize_text_field($_POST['after']),
		]);
	}

	public static function get($table, $field, $value) {
		global $wpdb;
		return $wpdb->get_row("SELECT * FROM {$table} WHERE {$field} = {$value}");
	}

	public static function gets($table, $field = null, $value = null) {
		global $wpdb;
		$query = "SELECT * FROM {$table}";

		if ($field && $value) {
			$query .= " WHERE {$field} = {$value}";
		}

		return $wpdb->get_results($query);
	}

	public static function get_balance_transfer() {
		global $wpdb;
		return intval($wpdb->get_var("SELECT balance FROM {$wpdb->_FINANCE} WHERE method = 'transfer' ORDER BY id DESC"));
	}

	public static function get_balance_cash() {
		global $wpdb;
		return intval($wpdb->get_var("SELECT balance FROM {$wpdb->_FINANCE} WHERE method = 'cash' ORDER BY id DESC"));
	}

	public static function get_remaining_payment($purchase_id) {
		global $wpdb;

		$purchase = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->_PURCHASES} WHERE id = %d AND type = %s AND payment_status != %s", $purchase_id, 'sell', 'complete'));

		return $purchase->pay_amount - $wpdb->get_var($wpdb->prepare("SELECT SUM(amount) FROM {$wpdb->_FINANCE} WHERE purchase_id = %d", $purchase_id));
	}

	public static function get_payment_count($purchase_id) {
		global $wpdb;
		return $wpdb->get_var($wpdb->prepare("SELECT count(*) FROM {$wpdb->_FINANCE} WHERE purchase_id = %d", $purchase_id));
	}
	
	public static function get_total_remaining() {
		global $wpdb;
		$pay_amount = $wpdb->get_var("SELECT sum(pay_amount) FROM {$wpdb->_PURCHASES} WHERE payment_status != 'complete'");
		$paid = $wpdb->get_var("SELECT sum(amount) FROM {$wpdb->_FINANCE} WHERE purchase_id IN (
			SELECT id FROM {$wpdb->_PURCHASES} WHERE payment_status != 'complete'
		)");

		$remaining = $pay_amount - $paid;
		return $remaining;
	}

	public static function get_stock_value() {
		global $wpdb;
		return $wpdb->get_var("SELECT SUM(stock_value) FROM {$wpdb->_PRODUCTS}");
	}

	public static function get_profit() {
		global $wpdb;
		$capital_value = $wpdb->get_var("SELECT sum(amount) FROM {$wpdb->_FINANCE} WHERE description = 'Penambahan modal'");
		$current_balance = self::get_balance_transfer() + self::get_balance_cash() + self::get_stock_value();

		return $current_balance - $capital_value;
	}

	public static function get_undelivered_count($type) {
		global $wpdb;
		return intval($wpdb->get_var("SELECT count(*) FROM {$wpdb->_PURCHASES} WHERE type = '{$type}' AND delivery_status != 'complete'"));
	}

	public static function update_stock($product_id, $field, $new_stock, $mode = 'increase') {
		global $wpdb;

		$product = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zzz_products WHERE id = %d", $product_id));

		if ($product) {
			$old_stock = $product->{'stock_' . $field};

			if ($mode == 'increase') {
				$new_stock += $old_stock;
			} else if ($mode == 'decrease') {
				$new_stock = $old_stock - $new_stock;
			}

			$wpdb->update($wpdb->prefix . 'zzz_products', [
				'stock_' . $field => $new_stock
			], [
				'id' => $product_id
			]);

			// update log
			$_POST['field'] = 'stock_' . $field;
			$_POST['before'] = $old_stock;
			$_POST['after'] = $new_stock;

			self::add_log('products');
		}
	}

	public static function update_stock_value($product_id) {
		global $wpdb;

		$row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zzz_products WHERE id = %d", $product_id));
		$stock_value = $row->price_buy * $row->stock_available;

		$wpdb->update($wpdb->prefix . 'zzz_products', 
		[
			'stock_value' => $stock_value
		], [
			'id' => $product_id
		]);
	}

	public function plugin_activate() {
		global $wpdb;
	
		// Define SQL statements
		$sql_queries = array(
			"CREATE TABLE IF NOT EXISTS {$wpdb->_FINANCE} (
				id INT AUTO_INCREMENT PRIMARY KEY,
				date DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
				type ENUM('in', 'out') DEFAULT 'in',
				method ENUM('cash', 'transfer') DEFAULT 'transfer',
				purchase_id INT NULL DEFAULT 0,
				description VARCHAR(255),
				invoice TEXT,
				note TEXT,
				amount INT,
				balance INT
			)",
			"CREATE TABLE IF NOT EXISTS {$wpdb->_PURCHASES} (
				id INT AUTO_INCREMENT PRIMARY KEY,
				store_id INT,
				type ENUM('buy', 'sell'),
				purchase_date DATE NULL DEFAULT CURRENT_TIMESTAMP,
				payment_status ENUM('complete', 'partial', 'pending'),
				payment_scheduled_date DATE,
				delivery_status ENUM('complete', 'pending'),
				delivery_scheduled_date DATE,
				courier_id INT,
				discount INT,
				additional_fee INT,
				total INT,
				pay_amount INT
			)",
			"CREATE TABLE IF NOT EXISTS {$wpdb->_PURCHASE_DETAILS} (
				id INT AUTO_INCREMENT PRIMARY KEY,
				purchase_id INT,
				product_id INT,
				qty INT,
				price INT,
				amount INT
			)",
			"CREATE TABLE IF NOT EXISTS {$wpdb->_COURIERS} (
				id INT AUTO_INCREMENT PRIMARY KEY,
				name VARCHAR(100),
				phone VARCHAR(20)
			)",
			"CREATE TABLE IF NOT EXISTS {$wpdb->_PRODUCTS} (
				id INT AUTO_INCREMENT PRIMARY KEY,
				product_name VARCHAR(255),
				price_buy INT,
				price_sell INT,
				stock_available INT,
				stock_pending_in INT,
				stock_pending_out INT,
				stock_value INT
			)",
			"CREATE TABLE IF NOT EXISTS {$wpdb->_STORES} (
				id INT AUTO_INCREMENT PRIMARY KEY,
				store_name VARCHAR(255),
				type ENUM('buy', 'sell'),
				address VARCHAR(255),
				phone VARCHAR(20),
				contact VARCHAR(100)
			)",
			"CREATE TABLE IF NOT EXISTS {$wpdb->_LOGS} (
				id INT AUTO_INCREMENT PRIMARY KEY,
				change_table VARCHAR(255),
				change_field VARCHAR(255),
				value_before VARCHAR(255),
				value_after VARCHAR(255),
				time DATETIME NULL DEFAULT CURRENT_TIMESTAMP
			)"
		);
	
		// Execute SQL statements
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		foreach ($sql_queries as $sql_query) {
			dbDelta($sql_query);
		}
	}

}

new Simple_Order();