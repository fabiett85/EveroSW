(function ($) {
	'use strict';

	var tabellaDatiClienti;

	$(function () {
		//VALORE DEFAULT SELECTPICKER
		$('.selectpicker').selectpicker({
			noneSelectedText: 'Seleziona...',
		});

		//VISUALIZZA DATATABLE UNITA' DI MISURA
		tabellaDatiClienti = $('#tabellaDati').DataTable({
			aLengthMenu: [
				[12, 24, 50, 100, -1],
				[12, 24, 50, 100, 'Tutti'],
			],
			iDisplayLength: 12,
			ajax: {
				url: $('#tabellaDati').attr('data-source'),
				dataSrc: '',
			},
			columns: [
				{ data: 'cl_IdRiga' },
				{ data: 'cl_Descrizione' },
				{ data: 'cl_Telefono' },
				{ data: 'cl_Mail' },
				{ data: 'cl_Indirizzo' },
				{ data: 'azioni' },
			],
			columnDefs: [
				{
					width: '10%',
					targets: [5],
					className: 'center-bolded',
				},
			],
			language: linguaItaliana,
			//"dom":  "<'row'<'col-sm-6'><'col-sm-6'f>r>" + "t" + "<'row'<'col-sm-4'l><'col-sm-8'p>>"
		});
	});

	// UNITA' DI MISURA: CANCELLAZIONE SEGNALAZIONE DI CAMPO VUOTO
	$('#form-inserimento input').on('blur', function () {
		$(this).removeClass('errore');
	});

	// UNITA' DI MISURA: INSERIMENTO NUOVA CATEGORIA
	$('#nuovo-cliente').on('click', function () {
		$('#form-inserimento input').removeClass('errore');

		$('#form-inserimento')[0].reset();

		$('#form-inserimento #cl_azione').val('nuovo');
		$('#modal-inserimento-label').text('INSERIMENTO NUOVA RIGA');
		$('#modal-inserimento').modal('show');
	});

	// UNITA' DI MISURA: CANCELLAZIONE CATEGORIA SELEZIONATA
	$('body').on('click', 'a.cancella-entry', function (e) {
		e.preventDefault();

		var idRiga = $(this).data('idRiga');

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
				$.post('clienti.php', { azione: 'cancella', idRiga: idRiga }).done(function (
					data
				) {
					// chiudo
					swal.close();

					// ricarico la datatable per vedere le modifiche
					tabellaDatiClienti.ajax.reload(null, false);
				});
			} else {
				swal.close();
			}
		});
		return false;
	});

	// UNITA' DI MISURA: CARICAMENTO POPUP PER MODIFICA
	$('body').on('click', 'a.modifica-entry', function (e) {
		e.preventDefault();

		$('#form-inserimento input').removeClass('errore');

		$('#form-inserimento')[0].reset();

		var idRiga = $(this).data('idRiga');

		$.post('clienti.php', { azione: 'recupera', idRiga: idRiga }).done(function (data) {
			var dati = JSON.parse(data);

			// Valorizzo popup con i dati recuperati
			for (var chiave in dati) {
				if (dati.hasOwnProperty(chiave)) {
					$('#form-inserimento')
						.find('input#' + chiave)
						.val(dati[chiave]);
					$('#form-inserimento select#' + chiave).val(dati[chiave]);
					$('#form-inserimento select#' + chiave).selectpicker('refresh');
				}
			}

			$('#form-inserimento').find('input#cl_azione').val('modifica');
			$('#modal-inserimento-label').text('MODIFICA RIGA');
			$('#modal-inserimento').modal('show');
		});

		return false;
	});

	// UNITA' DI MISURA: SALVATAGGIO DA POPUP MODIFICA/INSERIMENTO
	$('body').on('click', '#salva-entry', function (e) {
		e.preventDefault();

		var errori = 0;

		// Controllo riempimento campi obbligatori (INPUT)
		$('#form-inserimento .obbligatorio').each(function () {
			if ($(this).val() == '') {
				errori++;
				$(this).addClass('errore');
			}
		});

		// Controllo riempimento campi obbligatori (SELECT)
		$('#form-inserimento .selectpicker').each(function () {
			if ($(this).val() == null) {
				errori++;
				$(this).addClass('errore');
			}
		});

		// se ho anche solo un errore mi fermo qui
		if (errori > 0) {
			swal({
				title: 'Attenzione',
				text: 'Compilare tutti i campi obbligatori contrassegnati con *',
				icon: 'warning',
				button: 'Ho capito',

				closeModal: true,
			});
		} // nessun errore, posso continuare
		else {
			// salvo i dati
			$.post('clienti.php', {
				azione: 'salva',
				data: $('#form-inserimento').serialize(),
			}).done(function (data) {
				// se è tutto OK
				if (data == 'OK') {
					$('#modal-inserimento').modal('hide');

					// ricarico la datatable per vedere le modifiche
					tabellaDatiClienti.ajax.reload();
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
			});
		}

		return false;
	});
})(jQuery);
