(($) => {
	var today = moment();
	var url = 'disponibilitacomponenti.php';

	function popolaSelect() {

		$('#sel_componente').val();
		$.post(url, {
			azione: 'popola-select',
			data: $('#form-disponibilita').serialize(),
			commesse: $('#sel_commesse').val(),
			componenti: $('#sel_componente').val(),
		}).done((data) => {
			try {
				var dati = JSON.parse(data);
			} catch (error) {
				console.error(error);
				console.log(data);
			}
		});
	}

	$(() => {
		$('.selectpicker').val('%');
		$('.selectpicker').selectpicker('refresh');
		$('#fine').val(moment().add(7, 'd').format('YYYY-MM-DD'));
		$('#inizio').val(moment().subtract(7, 'd').format('YYYY-MM-DD'));

		retrieveFromStorage(url + 'inizio', '#inizio');
		retrieveFromStorage(url + 'fine', '#fine');
	});

	$('#inizio').blur(function () {
		sessionStorage.setItem(url + 'inizio', $(this).val());
	});

	$('#fine').blur(function () {
		sessionStorage.setItem(url + 'fine', $(this).val());
		popolaSelect();
	});
})(jQuery);
