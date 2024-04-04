jQuery(function($) {

	if ($('.datepicker').length > 0) {
		$('.datepicker').datepicker({
			dateFormat: 'DD, d MM yy'
		});
	}

	if ($('#file').length > 0) {
		// Function to resize image before displaying
		function resizeAndDisplayImage(fileInput) {
			var file = fileInput.files[0];
			var reader = new FileReader();

			reader.onload = function(e) {
				var img = new Image();
				img.onload = function() {
					var canvas = document.createElement('canvas');
					var ctx = canvas.getContext('2d');
					var maxWidth = 800; // Maximum width for resized image
					var scaleFactor = maxWidth / img.width;
					canvas.width = img.width * scaleFactor;
					canvas.height = img.height * scaleFactor;
					ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
					
					// Convert canvas to data URL and set it as the source of the image
					var resizedDataUrl = canvas.toDataURL('image/jpeg');
					$('#preview').attr('src', resizedDataUrl);
					$('#invoice').val(resizedDataUrl);
				};
				img.src = e.target.result;
			};
			reader.readAsDataURL(file);
		}

		// Handle file input change event
		$('#file').change(function() {
			resizeAndDisplayImage(this);
		});
	}

	var hot;
	var renderer;

	window.updater = null;

	function currency(number) {
		const formattedAmount = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(number);
		return formattedAmount.split(',')[0];
	}

	function load_data(elem, data, extra) {
		if (typeof extra != 'undefined') {
			data = {...data, ...extra};
		}

		console.log(data);

		$.post(simple_order.ajax_url, data, function(response) {
			console.log(response.data.table);

			if (typeof response.data.headers != 'undefined') {
				create_table(elem, response.data.headers);
			}

			if (typeof response.data.table != 'undefined') {
				hot.loadData(response.data.table);

				if (typeof data.id == 'undefined' || typeof response.data.collapsed != 'undefined') {
					setTimeout(function() {
						nestedRowsPlugin = hot.getPlugin('nestedRows');
						nestedRowsPlugin.collapsingUI.collapseAll();
					}, 500);
				}
			}

			setTimeout(function() {
				hot.updateSettings({
					columns: response.data.columns,
					colWidths: [20]
				});
			}, 300);
		});
	}

	function load_products(extra) {
		var data = {
			action: 'products_ajax'
		};

		load_data('#hot-products', data, extra);
	}

	function renderer_products(instance, td, row, col, prop, value, cellProperties) {
		Handsontable.renderers.TextRenderer.apply(this, arguments);

		if (prop == 'stock_value' || prop == 'est') {
			td.style.textAlign = 'right';
			td.textContent = currency(td.textContent);
		}

		if (prop == 'price_buy' || prop == 'price_sell' || prop == 'stock_available' || prop == 'stock_pending_in' || prop == 'stock_pending_out') {
			td.style.textAlign = 'right';
		}
	}

	function load_stores(extra) {
		var data = {
			action: 'stores_ajax'
		};

		load_data('#hot-stores', data, extra);
	}
	
	function load_couriers(extra) {
		var data = {
			action: 'couriers_ajax'
		};

		load_data('#hot-couriers', data, extra);
	}
		
	function load_transfers(extra) {
		var data = {
			action: 'finance_ajax'
		};

		load_data('#hot-transfers', data, extra);
	}
		
	function load_cash(extra) {
		var data = {
			action: 'finance_ajax'
		};

		load_data('#hot-cash', data, extra);
	}

	function renderer_finance(instance, td, row, col, prop, value, cellProperties) {
		Handsontable.renderers.TextRenderer.apply(this, arguments);

		if (prop == 'type') {
			if (value === "in") {
				td.textContent = 'Kredit';
				td.style.color = 'blue';
			} else {
				td.textContent = 'Debet';
				td.style.color = 'red';
			}
		}

		if (prop == 'invoice') {
			td.style.textAlign = 'center';
			var invoice_url = instance.getDataAtCell(row, instance.propToCol('invoice'));

			if (invoice_url) {
				var url = simple_order.home_url + '/wp-content' + invoice_url;
				td.innerHTML = '<a href="' + url + '" target="_blank"><img width="200px" src="' + url + '"></a>';
			}
		}

		if (prop == 'amount' || prop == 'balance') {
			td.style.textAlign = 'right';
			td.textContent = currency(td.textContent);
		}
	}

	function load_buy(extra) {
		var data = {
			action: 'buy_ajax'
		};

		load_data('#hot-buy', data, extra);
	}

	function renderer_buy(instance, td, row, col, prop, value, cellProperties) {
		Handsontable.renderers.TextRenderer.apply(this, arguments);

		if ((prop == 'price' || prop == 'pay_amount') && td.textContent != '') {
			td.style.textAlign = 'right';
			td.textContent = currency(td.textContent);
		}

		if (prop == 'qty') {
			td.style.textAlign = 'right';
		}

		if (prop == 'action') {
			var id = instance.getDataAtCell(row, 0);
			td.style.textAlign = 'center';

			if (id) {
				var url = simple_order.admin_url + 'admin.php?page=simple-order-buy&id=' + id;

				td.innerHTML += '<button onclick="updater({method: \'complete\', id: ' + id + '})">Selesai</button> ';
				td.innerHTML += '<button onclick="window.location.href=\'' + url + '\'">Upload Nota</button>';
			}
		}
	}

	function load_sell(extra) {
		var data = {
			action: 'sell_ajax'
		};

		load_data('#hot-sell', data, extra);
	}

	function renderer_sell(instance, td, row, col, prop, value, cellProperties) {
		Handsontable.renderers.TextRenderer.apply(this, arguments);

		if (prop == 'purchase_date') {
			if (value != null && value.indexOf('.') > 0 ) {
				dates = value.split('.');
				td.innerHTML = dates[0] + '<br/>' + '<span style="color:red;">' + dates[1] + '</span>';
			}
		}

		if ((prop == 'pay_amount' || prop == 'remaining' || prop == 'price') && td.textContent != '') {
			td.style.textAlign = 'right';
			if (value != '-') {
				td.textContent = currency(td.textContent);
			}
		}

		if (prop == 'qty') {
			td.style.textAlign = 'right';
		}

		if (prop == 'action') {
			td.style.textAlign = 'center';
			var id = instance.getDataAtCell(row, 0);
			var remaining = instance.getDataAtCell(row, instance.propToCol('remaining'));

			if (remaining != null) {
				var url = simple_order.admin_url + 'admin.php?page=simple-order-sell&id=' + id;
				td.innerHTML += '<button onclick="window.location.href=\'' + url + '\'">Pembayaran</button>'
			}

		}
	}

	function load_sell_delivery(extra) {
		var data = {
			action: 'sell_for_delivery_ajax'
		};

		load_data('#hot-sell-delivery', data, extra);
	}

	function renderer_sell_delivery(instance, td, row, col, prop, value, cellProperties) {
		Handsontable.renderers.TextRenderer.apply(this, arguments);

		if (prop == 'purchase_date') {
			if (value != null && value.indexOf('.') > 0 ) {
				dates = value.split('.');
				td.innerHTML = dates[0] + '<br/>' + '<span style="color:red;">' + dates[1] + '</span>';
			}
		}

		if (prop == 'pay_amount' && td.textContent != '') {
			td.style.textAlign = 'right';
			if (value != '-') {
				td.textContent = currency(td.textContent);
			}
		}

		if (prop == 'qty') {
			td.style.textAlign = 'right';
		}

		if (prop == 'action') {
			td.style.textAlign = 'center';
			var id = instance.getDataAtCell(row, 1);
			if (id) {
				td.innerHTML = '<input type="checkbox" name="purchase_id[]" class="purchase-id-checkbox" value="' + id + '" />';
			}
		}
	}
	
	function load_delivery(extra) {
		var data = {
			action: 'delivery_ajax'
		};

		load_data('#hot-delivery', data, extra);
	}

	function renderer_delivery(instance, td, row, col, prop, value, cellProperties) {
		Handsontable.renderers.TextRenderer.apply(this, arguments);

		if (prop == 'pay_amount' && td.textContent != '') {
			td.style.textAlign = 'right';
			td.textContent = currency(td.textContent);
		}

		if (prop == 'qty') {
			td.style.textAlign = 'right';
		}

		if (prop == 'action') {
			var id = instance.getDataAtCell(row, 0);
			if (id) {
				td.style.textAlign = 'center';
				td.innerHTML = '<button onclick="updater({method: \'complete\', id: '+id+'})">Selesai</button>'
			}
		}
	}
	
	function create_table(selector, headers) {
		var sourceDataObject = [
				{
					__children: []
				}
			];

		var container = document.querySelector(selector, headers);
		hot = new Handsontable(container, {
			data: sourceDataObject,
			colHeaders: headers,
			columnSorting: true,
			preventOverflow: 'horizontal',
			rowHeaders: true,
			nestedRows: true,
			height: 'auto',
			width: 'auto',
			stretchH: 'all',
			autoWrapRow: true,
			autoWrapCol: true,
			licenseKey: 'non-commercial-and-evaluation',
			afterChange: function(change, source) {
				if (source === 'loadData' || source == 'populateFromArray') {
					return;
				}
				
				change.forEach(function(row) {
					var id = hot.getDataAtCell(row[0], 0);
					var field = row[1];
					var before = row[2];
					var after = row[3];

					updater({
						method: 'update',
						id: id,
						field: field,
						before: before,
						after: after
					});
				});
			}
		});

		if (typeof renderer != 'undefined') {
			hot.updateSettings({
				renderer: renderer
			});
		}
	}

	if ($('#hot-products').length > 0) {
		updater = load_products;
		renderer = renderer_products;
		updater();
	}

	if ($('#hot-stores').length > 0) {
		updater = load_stores;
		updater();
	}
	
	if ($('#hot-couriers').length > 0) {
		updater = load_couriers;
		updater();
	}
	
	if ($('#hot-transfers').length > 0) {
		updater = load_transfers;
		renderer = renderer_finance;
		updater({method: 'transfer'});
	}
	
	if ($('#hot-cash').length > 0) {
		updater = load_cash;
		renderer = renderer_finance;
		updater({method: 'cash'});
	}
	
	if ($('#hot-buy').length > 0) {
		updater = load_buy;
		renderer = renderer_buy;

		var query_string = window.location.search;
		var url_params = new URLSearchParams(query_string);
		var id = url_params.get('id');

		if (typeof id != 'object') {
			updater({id: id});
		} else {
			updater();
		}
	}

	if ($('#hot-delivery').length > 0) {
		updater = load_delivery;
		renderer = renderer_delivery;
		updater();
	}

	if ($('#hot-sell').length > 0) {
		updater = load_sell;
		renderer = renderer_sell;

		var query_string = window.location.search;
		var url_params = new URLSearchParams(query_string);
		var id = url_params.get('id');

		if (typeof id != 'object') {
			updater({id: id});
		} else {
			updater();
		}
	}

	if ($('#hot-sell-delivery').length > 0) {
		updater = load_sell_delivery;
		renderer = renderer_sell_delivery;
		updater();
	}

	$('.button-add').click(function() {
		updater({method: 'add'});
	});

});
