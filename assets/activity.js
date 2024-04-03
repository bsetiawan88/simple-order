jQuery(function($) {

	if ($('.datepicker').length > 0) {
		$('.datepicker').datepicker({
			dateFormat: 'DD, d MM yy'
		});
	}

	$('#aktifitas').change(function() {
		var aktifitas = $(this).val();

		$('[class*="div-"]').hide();
		$('.div-' + aktifitas).show();
		
	});

	$('.search').on('keyup', function() {
		var search_text = $(this).val().toLowerCase();
		var parent_element = $(this).data('parent');
		
		$('.' + parent_element + ' .table tr').each(function() {
			if ($(this).hasClass('tr-produk') == false && $(this).hasClass('tr-option') == false) {
			
			} else {
				var item_name = $(this).data('name').toLowerCase();
				if (item_name.includes(search_text)) {
					$(this).show();
				} else {
					$(this).hide();
				}
			}
		});
	});

	$('.select-all').on('click', function() {
		$('.purchase-id-checkbox').prop('checked', true);
	});

	$(document).on('change, keyup', '#form_pembelian .harga-beli, #form_pembelian .jumlah, #form_pembelian .diskon, #form_pembelian .biaya', function() {
		updateSubtotalPembelian();
	});

	function updateSubtotalPembelian() {
		var total = 0;
		for (i = 0; i < $('#form_pembelian .harga-beli').length; i++) {
			var harga_beli = parseInt($('#form_pembelian .harga-beli').eq(i).val());
			var jumlah = parseInt($('#form_pembelian .jumlah').eq(i).val());

			var subtotal = harga_beli * jumlah;
			total = total + subtotal;
			
			$('#form_pembelian .subtotal').eq(i).html(subtotal);
		}

		if ($('#form_pembelian .diskon').val() !== '') {
			total -= parseInt($('#form_pembelian .diskon').val());
		}

		if ($('#form_pembelian .biaya').val() !== '') {
			total += parseInt($('#form_pembelian .biaya').val());
		}

		$('#form_pembelian .total').html(total);
	}

	$(document).on('change, keyup', '#form_penjualan .harga-beli, #form_penjualan .jumlah, #form_penjualan .diskon, #form_penjualan .biaya', function() {
		updateSubtotalPenjualan();
	});
	
	function updateSubtotalPenjualan() {
		var total = 0;
		for (i = 0; i < $('#form_penjualan .harga-jual').length; i++) {
			var harga_jual = parseInt($('#form_penjualan .harga-jual').eq(i).val());
			var jumlah = parseInt($('#form_penjualan .jumlah').eq(i).val());

			var subtotal = harga_jual * jumlah;
			total = total + subtotal;
			$('#form_penjualan .subtotal').eq(i).html(subtotal);
		}

		if ($('#form_penjualan .diskon').val() !== '') {
			total -= parseInt($('#form_penjualan .diskon').val());
		}

		if ($('#form_penjualan .biaya').val() !== '') {
			total += parseInt($('#form_penjualan .biaya').val());
		}

		$('#form_penjualan .total').html(total);
	}

});
