<?php

class SO {

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
	
	public static function get_total_remaining($month = null) {
		global $wpdb;

		$where = '';
		if ($month === 0) {
			$query_month = date('m', time());
		} else if (!empty($month)) {
			$query_month = date('m', strtotime("+$month months"));
		}

		if (isset($query_month)) {
			$where = $wpdb->prepare(" AND MONTH(payment_scheduled_date) = %d", $query_month);
		}

		$pay_amount = $wpdb->get_var("SELECT sum(pay_amount) FROM {$wpdb->_PURCHASES} WHERE payment_status != 'complete' {$where}");
		$paid = $wpdb->get_var("SELECT sum(amount) FROM {$wpdb->_FINANCE} WHERE purchase_id IN (
			SELECT id FROM {$wpdb->_PURCHASES} WHERE payment_status != 'complete' {$where}
		)");

		$remaining = $pay_amount - $paid;
		return $remaining;
	}

	public static function get_stock_pending_in_value() {
		global $wpdb;
		return $wpdb->get_var("SELECT SUM(stock_pending_in * price_buy) FROM {$wpdb->_PRODUCTS}");
	}

	public static function get_stock_value() {
		global $wpdb;
		return $wpdb->get_var("SELECT SUM(stock_available * price_buy) FROM {$wpdb->_PRODUCTS}");
	}

	public static function get_stock_profit_value() {
		global $wpdb;
		$value = $wpdb->get_var("SELECT SUM(stock_available * price_sell) FROM {$wpdb->_PRODUCTS}");

		return $value - self::get_stock_value();
	}

	public static function get_stock_pending_profit_value() {
		global $wpdb;
		$value = $wpdb->get_var("SELECT SUM(stock_pending_in * price_sell) FROM {$wpdb->_PRODUCTS}");

		return $value - self::get_stock_pending_in_value();
	}

	public static function get_profit($month = null) {
		global $wpdb;

		$where = '';
		if ($month === 0) {
			$query_month = date('m', time());
		} else if (!empty($month)) {
			$query_month = date('m', strtotime("+$month months"));
		}

		$query = "SELECT sum(profit) FROM {$wpdb->_PURCHASES} WHERE type = 'sell' AND payment_status = 'complete' ";
		if (isset($query_month)) {
			$query .= $wpdb->prepare(" AND (MONTH(payment_scheduled_date) = %d OR (payment_scheduled_date IS NULL AND MONTH (purchase_date) = %d))", $query_month, $query_month);
		}

		$profit = $wpdb->get_var($query);

		return $profit;
	}

	public static function calculate_profit() {
		global $wpdb;

		$query = "SELECT * FROM {$wpdb->_PURCHASES} WHERE type = 'sell' AND profit IS NULL";
		$results = $wpdb->get_results($query);

		if ($results) {
			foreach ($results as $row) {
				$details = $wpdb->get_results($wpdb->prepare("SELECT D.qty, P.price_buy FROM {$wpdb->_PURCHASE_DETAILS} D LEFT JOIN {$wpdb->_PRODUCTS} P ON D.product_id = P.id WHERE D.purchase_id = %d", $row->id));
	
				$price_buy = 0;
				foreach ($details as $d) {
					$price_buy += ($d->qty * $d->price_buy);
				}
	
				$profit = $row->pay_amount - $price_buy;
				$wpdb->update($wpdb->_PURCHASES, ['profit' => $profit], ['id' => $row->id]);
			}
		}
	}

	public static function get_undelivered_count($type) {
		global $wpdb;
		return intval($wpdb->get_var("SELECT count(*) FROM {$wpdb->_PURCHASES} WHERE type = '{$type}' AND delivery_status != 'complete'"));
	}

	public static function get_sales_month($store_id, $month = 0) {
		global $wpdb;

		if (empty($month)) {
			$month = date('m', time());
		} else {
			$month = date('m', strtotime("-$month months"));
		}

		return $wpdb->get_var($wpdb->prepare("SELECT SUM(pay_amount) FROM {$wpdb->_PURCHASES} WHERE store_id = %d AND type = %s AND MONTH(purchase_date) = %d", $store_id, 'sell', $month));
	}

	public static function get_sales_count($store_id, $month = 0) {
		global $wpdb;

		if (empty($month)) {
			$month = date('m', time());
		} else {
			$month = date('m', strtotime("-$month months"));
		}

		return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->_PURCHASES} WHERE store_id = %d AND type = %s AND MONTH(purchase_date) = %d", $store_id, 'sell', $month));
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

	public static function insert_finance($data) {
		global $wpdb;

		if (isset($data['method']) && $data['method'] == 'cash') {
			$balance = self::get_balance_cash();
		} else {
			$balance = self::get_balance_transfer();
			$data['method'] = 'transfer';
		}

		if ($data['type'] == 'in') {
			$data['balance'] = $balance + intval($data['amount']);
		} else {
			$data['balance'] = $balance - intval($data['amount']);
		}

		return $wpdb->insert($wpdb->_FINANCE, $data);
	}
}