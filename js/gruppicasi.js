(function ($) {
	'use strict';

	var tabellaDatiGc;

	$(function () {
		//VISUALIZZA DATATABLE
		tabellaDatiGc = $('#tabellaDati-gc').DataTable({
			aLengthMenu: [
				[12, 24, 50, 100, -1],
				[12, 24, 50, 100, 'Tutti'],
			],
			iDisplayLength: 12,
			ajax: 'gruppicasi.php?azione=mostra-gc',
			columns: [{ data: 'gcDescrizione' }, { data: 'azioni' }],
			columnDefs: [
				{
					targets: [1],
					className: 'center-bolded',
					width: '10%',
				},
			],
			language: linguaItaliana,
		});
	});

	// UNITA' DI MISURA: CANCELLAZIONE SEGNALAZIONE DI CAMPO VUOTO
	$('#form-gc input').on('blur', function () {
		$(this).removeClass('errore');
	});

	// UNITA' DI MISURA: INSERIMENTO NUOVA CATEGORIA
	$('#nuova-gc').on('click', function () {
		$('#form-gc').removeClass('was-validated');

		$('#form-gc')[0].reset();
		$('#form-gc .selectpicker').val('default');
		$('#form-gc .selectpicker').selectpicker('refresh');

		$('#form-gc').find('input#gc_azione').val('nuovo');
		$('#modal-gc-label').text('INSERIMENTO NUOVO GRUPPO');
		$('#modal-gc').modal('show');
	});

	// UNITA' DI MISURA: CANCELLAZIONE CATEGORIA SELEZIONATA
	$('body').on('click', 'a.cancella-entry-gc', function (e) {
		e.preventDefault();

		var idRiga = $(this).data('id_riga');

		swal({
			title: 'Attenzione',
			text: "Confermi di voler eliminare l'unità di misura in oggetto? L'eliminazione è irreversibile.",
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
				$.post('gruppicasi.php', { azione: 'cancella-gc', idRiga: idRiga }).done(function (
					data
				) {
					// chiudo
					swal.close();

					// ricarico la datatable per vedere le modifiche
					tabellaDatiGc.ajax.reload();
				});
			} else {
				swal.close();
			}
		});
		return false;
	});

	// UNITA' DI MISURA: CARICAMENTO POPUP PER MODIFICA
	$('body').on('click', 'a.modifica-entry-gc', function (e) {
		e.preventDefault();

		$('#form-gc').removeClass('was-validated');

		$('#form-gc')[0].reset();

		var idRiga = $(this).data('id_riga');

		$.post('gruppicasi.php', { azione: 'recupera-gc', idRiga: idRiga }).done(function (data) {
			var dati = JSON.parse(data);

			// Valorizzo popup con i dati recuperati
			for (var chiave in dati) {
				if (dati.hasOwnProperty(chiave)) {
					$('#form-gc input#' + chiave).val(dati[chiave]);
				}
			}

			$('#form-gc').find('input#gc_azione').val('modifica');
			$('#modal-gc-label').text('MODIFICA GRUPPO');
			$('#modal-gc').modal('show');
		});

		return false;
	});

	// UNITA' DI MISURA: SALVATAGGIO DA POPUP MODIFICA/INSERIMENTO
	$('body').on('click', '#salva-entry-gc', function (e) {
		e.preventDefault();
		$('#form-gc').addClass('was-validated');

		// se ho anche solo un errore mi fermo qui
		if (!$('#form-gc')[0].checkValidity()) {
			swal({
				title: 'Attenzione',
				text: 'Compilare tutti i campi richiesti.',
				icon: 'warning',
			});
		} // nessun errore, posso continuare
		else {
			// salvo i dati
			$.post('gruppicasi.php', { azione: 'salva-gc', data: $('#form-gc').serialize() }).done(
				function (data) {
					// se è tutto OK
					if (data == 'OK') {
						$('#modal-gc').modal('hide');

						// ricarico la datatable per vedere le modifiche
						tabellaDatiGc.ajax.reload();
					} // restituisco un errore senza chiudere la modale
					else {
						swal({
							title: 'ATTENZIONE',
							text: data,
							icon: 'warning',
							button: 'Ho capito',
							closeModal: true,
						});
					}
				}
			);
		}

		return false;
	});
})(jQuery);
