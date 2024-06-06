var tabella;
var linguaItaliana = {
	processing: 'Caricamento...',
	search: 'Cerca testo',
	lengthMenu: '_MENU_ righe per pagina',
	zeroRecords: 'Nessun record presente',
	info: 'Pagina _PAGE_ di _PAGES_',
	infoEmpty: 'Nessun dato disponibile',
	infoFiltered: '(filtrate da _MAX_ righe totali)',
	paginate: {
		first: 'Prima',
		last: 'Ultima',
		next: 'Prossima',
		previous: 'Precedente',
	},
};

$(function () {
	// DATATABLE
	tabella = $('#tabella').DataTable({
		dom:
			"<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
			"<'row'<'col-sm-12'tr>>" +
			"<'row post-tabella'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 d-flex justify-content-end align-items-center'p>>",
		ajax: $('#tabella').attr('data-source'),
		language: linguaItaliana,
		select: true,
		responsive: true,
	});

	// CANCELLAZIONE RIGA
	$('body').on('click', 'a.cancella-riga', function (e) {
		e.preventDefault();

		var myHref = $(this).attr('href');

		swal({
			title: 'Attenzione',
			text: "Confermi di voler eliminare la riga in oggetto? L'eliminazione è irreversibile.",
			icon: 'warning',
			showCancelButton: true,
			buttons: {
				cancel: {
					text: 'Annulla',
					value: null,
					visible: true,
					className: 'btn btn-danger',
					closeModal: true,
				},
				confirm: {
					text: 'Sì, elimina',
					value: true,
					visible: true,
					className: 'btn btn-primary',
					closeModal: true,
				},
			},
		}).then((procedi) => {
			if (procedi) {
				$.get(myHref).done(function (data) {
					// chiudo
					swal.close();

					// ricarico la datatable per vedere le modifiche
					tabella.ajax.reload();
				});
			} else {
				swal.close();
			}
		});

		return false;
	});

	// MODIFICA RIGA
	$('body').on('click', 'a.modifica-riga', function (e) {
		e.preventDefault();
		$('#update').val('true');

		var myHref = $(this).attr('href');
		$.get(myHref).done(function (data) {
			var dati = JSON.parse(data);
			for (var chiave in dati) {
				if (dati.hasOwnProperty(chiave)) {
					// console.log(chiave + " -> " + dati[chiave]);
					var escaped = $.escapeSelector(dati[chiave]);
					$('#form [name="' + chiave + '"], #form [name="' + chiave + '_old"]')
						.not('[type=radio]')
						.val(dati[chiave])
						.trigger('change');
					$('#cb_' + chiave + '_' + escaped).prop('checked', 'checked');
				}
			}

			$('#modalDettaglio').modal('show');
		});

		return false;
	});

	$('body').on('click', '#apriModaleNuovo', function (e) {
		e.preventDefault();
		$('#update').val('false');
		$('#form')[0].reset();

		$('#modalDettaglio').modal('show');
	});

	// SALVATAGGIO
	$('#salva').on('click', function (e) {
		e.preventDefault();

		var errori = 0;

		$('#form')
			.find('.obbligatorio')
			.each(function () {
				var campo = $(this).attr('name');

				if (!$(this).val()) {
					$('[name="' + campo + '"]').addClass('errore');
					$('small[data-per="' + campo + '"]').show();
					errori++;
				}
			});

		if (!errori) {
			var serialized = $('#form').serialize();
			$.post('manutenzionearchivi.php?pagina=' + g_Pagina, serialized).done(function (data) {
				tabella.ajax.reload();
				$('#modalDettaglio').modal('hide');
			});
		}

		return false;
	});
});
