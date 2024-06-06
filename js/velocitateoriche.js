(function ($) {
	'use strict';

	var tabellaDatiVt;

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

		//VISUALIZZA DATATABLE VELOCITA' TEORICHE LINEA
		tabellaDatiVt = $('#tabellaDati-vt').DataTable({
			order: [
				[0, 'asc'],
				[1, 'asc'],
			],
			aLengthMenu: [
				[12, 24, 50, 100, -1],
				[12, 24, 50, 100, 'Tutti'],
			],
			iDisplayLength: 50,
			ajax: {
				url: $('#tabellaDati-vt').attr('data-source'),
				dataSrc: '',
			},
			columns: [
				{ data: 'IdProdotto' },
				{ data: 'IdLineaProduzione' },
				{ data: 'VelocitaTeorica' },
				{ data: 'azioni' },
			],
			columnDefs: [
				{
					targets: [3],
					className: 'center-bolded',
				},
				{
					width: '10%',
					targets: [3],
				},
			],
			language: linguaItaliana,
			//"dom":  "<'row'<'col-sm-6'><'col-sm-6'f>r>" + "t" + "<'row'<'col-sm-4'l><'col-sm-8'p>>"
		});
	});

	// VELOCITA' TEORICHE LINEA: CANCELLAZIONE SEGNALAZIONE DI CAMPO VUOTO
	$('#form-vt input').on('blur', function () {
		$(this).removeClass('errore');
	});

	// VELOCITA' TEORICHE LINEA: INSERIMENTO NUOVA VELOCITA' TEORICA DI LINEA
	$('#nuova-vt').on('click', function () {
		$('#form-vt input').removeClass('errore');

		$('#form-vt')[0].reset();
		$('#form-vt .selectpicker').val('default');
		$('#form-vt .selectpicker').selectpicker('refresh');

		$('#form-vt').find('input#vel_azione').val('nuovo');
		$('#modal-vt-label').text('INSERIMENTO NUOVA RIGA');
		$('#modal-vt').modal('show');
	});

	// VELOCITA' TEORICHE LINEA: CANCELLAZIONE VELOCITA' TEORICA SELEZIONATA
	$('body').on('click', 'a.cancella-entry-vt', function (e) {
		e.preventDefault();

		var idRiga = $(this).data('id_riga');

		swal({
			title: 'Attenzione',
			text: "Confermi di voler eliminare la velocità teorica di linea in oggetto? L'eliminazione è irreversibile.",
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
				$.post('velocitateoriche.php', { azione: 'cancella-vt', idRiga: idRiga }).done(
					function (data) {
						// chiudo
						swal.close();

						// ricarico la datatable per vedere le modifiche
						tabellaDatiVt.ajax.reload(null, false);
					}
				);
			} else {
				swal.close();
			}
		});
		return false;
	});

	// VELOCITA' TEORICHE LINEA: CARICAMENTO POPUP PER MODIFICA
	$('body').on('click', 'a.modifica-entry-vt', function (e) {
		e.preventDefault();

		$('#form-vt input').removeClass('errore');

		$('#form-vt')[0].reset();

		var idRiga = $(this).data('id_riga');

		$.post('velocitateoriche.php', { azione: 'recupera-vt', idRiga: idRiga }).done(function (
			data
		) {
			var dati = JSON.parse(data);

			// visualizza nel popup i dati recuperati
			for (var chiave in dati) {
				if (dati.hasOwnProperty(chiave)) {
					$('#form-vt')
						.find('input#' + chiave)
						.val(dati[chiave]);
					$('#form-vt select#' + chiave).val(dati[chiave]);
					$('#form-vt select#' + chiave).selectpicker('refresh');
				}
			}

			$('#form-vt').find('input#vel_azione').val('modifica');
			$('#modal-vt-label').text('MODIFICA RIGA');
			$('#modal-vt').modal('show');
		});
		return false;
	});

	// VELOCITA' TEORICHE LINEA: SALVATAGGIO DA POPUP MODIFICA/INSERIMENTO
	$('body').on('click', '#salva-entry-vt', function (e) {
		e.preventDefault();

		// inizializzo il contatore errori
		var errori = 0;

		// Controllo riempimento campi obbligatori (INPUT)
		$('#form-vt .obbligatorio').each(function () {
			if ($(this).val() == '') {
				errori++;
				$(this).addClass('errore');
			}
		});

		// Controllo riempimento campi obbligatori (SELECT)
		$('#form-vt .selectpicker').each(function () {
			if ($(this).val() == null) {
				errori++;
				$(this).addClass('errore');
			}
		});

		// Se ho anche solo un errore mi fermo qui
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
			$.post('velocitateoriche.php', {
				azione: 'salva-vt',
				data: $('#form-vt').serialize(),
			}).done(function (data) {
				// se è tutto OK
				if (data == 'OK') {
					$('#modal-vt').modal('hide');

					// ricarico la datatable per vedere le modifiche
					tabellaDatiVt.ajax.reload(null, false);
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
