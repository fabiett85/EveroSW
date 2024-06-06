(function ($) {
	'use strict';

	var tabellaDatiUm;

	var linguaItaliana = {
		processing: 'Caricamento...',
		search: 'Ricerca: ',
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
		//VALORE DEFAULT SELECTPICKER
		$('.selectpicker').selectpicker({
			noneSelectedText: 'Seleziona...',
		});

		//VISUALIZZA DATATABLE LINEA
		tabellaDatiUm = $('#tabellaDati-linee').DataTable({
			order: [[1, 'asc']],
			aLengthMenu: [
				[10, 25, 50, 100, -1],
				[10, 25, 50, 100, 'Tutti'],
			],
			iDisplayLength: 10,
			ajax: {
				url: $('#tabellaDati-linee').attr('data-source'),
				dataSrc: '',
			},
			columns: [
				{ data: 'IdLinea' },
				{ data: 'DescrizioneLinea' },
				{ data: 'Costo' },
				{ data: 'NoteLinea' },
				{ data: 'azioni' },
			],
			columnDefs: [
				{
					targets: [0],
					visible: false,
				},
				{
					targets: [4],
					className: 'center-bolded',
				},
				{
					width: '5%',
					targets: [4],
				},
			],
			language: linguaItaliana,
			//"dom":  "<'row'<'col-sm-6'><'col-sm-6'f>r>" + "t" + "<'row'<'col-sm-4'l><'col-sm-8'p>>"
		});
	});

	// LINEE PRODUZIONE: CANCELLAZIONE SEGNALAZIONE DI CAMPO VUOTO
	$('#form-linee input').on('blur', function () {
		$(this).removeClass('errore');
	});

	// LINEE PRODUZIONE: INSERIMENTO NUOVA LINEA
	$('#nuova-linea').on('click', function () {
		$('#form-linee').removeClass('was-validated');
		$('#form-linee')[0].reset();

		$('#form-linee').find('input#lp_azione').val('nuovo');
		$('#modal-linee-label').text('INSERIMENTO NUOVA LINEA');
		$('#modal-linee').modal('show');
	});

	// LINEE PRODUZIONE: CANCELLAZIONE LINEA SELEZIONATA
	$('body').on('click', 'a.cancella-entry-linee', function (e) {
		e.preventDefault();

		var idRiga = $(this).data('id_riga');

		swal({
			title: 'ATTENZIONE!',
			text: "Confermi di voler eliminare la linea in oggetto? L'eliminazione è irreversibile.",
			icon: 'warning',
			buttons: {
				cancel: {
					text: 'ANNULLA',
					value: null,
					visible: true,
					className: 'btn btn-secondary',
				},
				confirm: {
					text: 'CONFERMA',
					value: true,
					visible: true,
					className: 'btn btn-danger',
				},
			},
		}).then((procedi) => {
			if (procedi) {
				$.post('lineeproduzione.php', { azione: 'cancella-linee', idRiga: idRiga }).done(
					function (data) {
						if (data == 'OK') {
							// chiudo
							swal.close();

							// ricarico la datatable per vedere le modifiche
							tabellaDatiUm.ajax.reload(null, false);
						} else if (data == 'RISORSE_ASSOCIATE') {
							swal.close();

							swal({
								title: 'ATTENZIONE!',
								text: 'Esistono risorse associate alla linea, impossibile procedere.',
								icon: 'warning',
							});
						} else {
							console.log(data);
							swal({
								title: 'ERRORE!',
								text: "Errore nell'eliminazione della linea",
								icon: 'error',
							});
						}
					}
				);
			} else {
				swal.close();
			}
		});
		return false;
	});

	// LINEE PRODUZIONE: CARICAMENTO POPUP PER MODIFICA
	$('body').on('click', 'a.modifica-entry-linee', function (e) {
		e.preventDefault();
		$('#form-linee').removeClass('was-validated');

		var idRiga = $(this).data('id_riga');

		$.post('lineeproduzione.php', {
			azione: 'recupera-linee',
			idRiga: idRiga,
		}).done(function (data) {
			$('#form-linee')[0].reset();
			try {
				var dati = JSON.parse(data);

				// visualizza nel popup i dati recuperati
				for (var chiave in dati) {
					if (dati.hasOwnProperty(chiave)) {
						$('#form-linee input#' + chiave).val(dati[chiave]);
					}
				}

				$('#form-linee').find('input#lp_azione').val('modifica');
				$('#modal-linee-label').text('MODIFICA RIGA');

				$('#modal-linee').modal('show');
			} catch (error) {
				console.error(error);
				console.log(data);
				swal({
					title: 'ERRORE!',
					text: 'Errore nel recupero della linea',
					icon: 'error',
				});
			}
		});

		return false;
	});

	// LINEE PRODUZIONE: SALVATAGGIO DA POPUP MODIFICA/INSERIMENTO
	$('body').on('click', '#salva-entry-linee', function (e) {
		e.preventDefault();
		$('#form-linee').addClass('was-validated');
		if (!$('#form-linee')[0].checkValidity()) {
			swal({
				title: 'ATTENZIONE!',
				text: 'Compilare tutti i campi richiesti.',
				icon: 'warning',
			});
		} // nessun errore, posso continuare
		else {
			// salvo i dati
			$.post('lineeproduzione.php', {
				azione: 'salva-linee',
				data: $('#form-linee').serialize(),
			}).done(function (data) {
				// se è tutto OK
				if (data == 'OK') {
					$('#modal-linee').modal('hide');

					// ricarico la datatable per vedere le modifiche
					tabellaDatiUm.ajax.reload(null, false);
				} // restituisco un errore senza chiudere la modale
				else {
					console.log(data);
					swal({
						title: 'ERRORE!',
						text: 'Errore nel salvataggio della linea',
						icon: 'error',
					});
				}
			});
		}

		return false;
	});
})(jQuery);
