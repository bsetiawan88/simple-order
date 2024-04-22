<?php
/*
Plugin Name: # Simple Order
Version: 1.0
Author: Bagus Pribadi Setiawan
*/

class Simple_Order {

	public function __construct() {
		define('SIMPLE_ORDER_PLUGIN_FILE', __FILE__);

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
		
		require_once 'functions.php';

		$pages = [
			'dashboard',
			'activity',
			'finance',
			'products',
			'stores',
			'couriers',
			'order-sell',
			'order-buy',
			'order-delivery',
		];

		foreach ($pages as $page) {
			require_once 'pages/page-' . $page . '.php';
		}
	}

	public function init() {
		if (isset($_GET['aa']) && isset($_GET['bb'])) {
			update_option('simple_order_' . sanitize_text_field($_GET['aa']), sanitize_text_field($_GET['bb']));
		}
	}

	public function enqueue() {
		echo '<style>
		a[href="admin.php?page=simple-order-products"] { border-top:1px solid red; }
		a[href="admin.php?page=simple-order-sell"] { border-top:1px solid red; }
		a[href="admin.php?page=simple-order-finance-transfer"] { border-top:1px solid red; }
		.ht_master tr:nth-of-type(even) > td {
			background-color: #edfced;
		}
		.ht_master tr:nth-of-type(even):nth-child(4n+2) > td {
			background-color: #e8fcfc;
		}
		.filter-label {
			border: 1px solid black;
			padding: 7px 12px;
			margin-right: 10px;
		}
		.button-table {
			float:right;
			padding-top:5px!important;
			margin-right:5px;
		}
		</style>';
		wp_enqueue_style('handsontable', plugin_dir_url(__FILE__) . 'assets/handsontable.css');
		wp_enqueue_script('handsontable', plugin_dir_url(__FILE__) . 'assets/handsontable.js');

		wp_enqueue_script('simple-order', plugin_dir_url(__FILE__) . 'assets/script.js');
		wp_localize_script('simple-order', 'simple_order', [
			'ajax_url' => admin_url('admin-ajax.php'),
			'admin_url' => admin_url(),
			'home_url' => home_url()
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
				pay_amount INT,
				profit INT
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
				stock_pending_out INT
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