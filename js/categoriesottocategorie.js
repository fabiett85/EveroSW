(function ($) {
	'use strict';

	var tabellaDatiCategorie;
	var tabellaDatiSottocategorie;

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

		//VISUALIZZA DATATABLE CATEGORIA
		tabellaDatiCategorie = $('#tabellaDati-cat').DataTable({
			order: [[0, 'asc']],
			aLengthMenu: [
				[12, 24, 50, 100, -1],
				[12, 24, 50, 100, 'Tutti'],
			],
			iDisplayLength: 12,
			ajax: 'categoriesottocategorie.php?azione=mostra-cat',
			columns: [{ data: 'NomeCategoria' }, { data: 'DescrizioneCategoria' }, { data: 'azioni' }],
			columnDefs: [
				{
					targets: [2],
					className: 'center-bolded',
				},
				{
					width: '10%',
					targets: [2],
				},
			],
			language: linguaItaliana,
		});

		//VISUALIZZA DATATABLE SOTTOCATEGORIA
		tabellaDatiSottocategorie = $('#tabellaDati-sot').DataTable({
			order: [
				[0, 'asc'],
				[1, 'asc'],
			],
			aLengthMenu: [
				[12, 24, 50, 100, -1],
				[12, 24, 50, 100, 'Tutti'],
			],
			iDisplayLength: 12,
			ajax: 'categoriesottocategorie.php?azione=mostra-sot',
			columns: [
				{ data: 'Categoria' },
				{ data: 'NomeSottocategoria' },
				{ data: 'DescrizioneSottocategoria' },
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
		});
	});

	// CATEGORIE: CANCELLAZIONE SEGNALAZIONE DI CAMPO VUOTO
	$('#form-cat input, #form-sot input').on('blur', function () {
		$(this).removeClass('errore');
	});

	//CATEGORIA: INSERIMENTO NUOVA CATEGORIA
	$('#nuova-cat').on('click', function () {
		$('#form-cat input').removeClass('errore');

		$('#form-cat')[0].reset();
		$('#form-cat .selectpicker').val('default');
		$('#form-cat .selectpicker').selectpicker('refresh');

		$('#form-cat').find('input#cat_azione').val('nuovo');
		$('#modal-cat-label').text('INSERIMENTO NUOVA CATEGORIA');
		$('#modal-cat').modal('show');
	});

	//SOTTOCATEGORIE: INSERIMENTO NUOVA SOTTOCATEGORIA
	$('#nuova-sot').on('click', function () {
		$('#form-sot input').removeClass('errore');

		$('#form-sot')[0].reset();
		$('#form-sot .selectpicker').val('default');
		$('#form-sot .selectpicker').selectpicker('refresh');

		$.post('categoriesottocategorie.php', { azione: 'caricaSelectCategoriaAppartenenza' }).done(
			function (data) {
				if (data != 'NO_CAT') {
					$('#form-sot #sot_Categoria').prop('disabled', false);
					$('#form-sot #sot_Categoria').html(data);
					$('#form-sot #sot_Categoria').val('default');
					$('#form-sot #sot_Categoria').selectpicker('refresh');
				}
			}
		);

		$('#form-sot').find('input#sot_azione').val('nuovo');
		$('#modal-sot-label').text('INSERIMENTO NUOVA SOTTOCATEGORIA');
		$('#modal-sot').modal('show');
	});

	// CATEGORIE: CANCELLAZIONE CATEGORIA SELEZIONATA
	$('body').on('click', 'a.cancella-categoria', function (e) {
		e.preventDefault();

		var idRiga = $(this).data('id_riga');

		swal({
			title: 'Attenzione',
			text: "Confermi di voler eliminare la categoria in oggetto? L'eliminazione è irreversibile.",
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
				$.post('categoriesottocategorie.php', { azione: 'cancella-cat', idRiga: idRiga }).done(
					function (data) {
						// chiudo
						swal.close();

						// ricarico la datatable per vedere le modifiche
						tabellaDatiCategorie.ajax.reload(null, false);
					}
				);
			} else {
				swal.close();
			}
		});
		return false;
	});

	// SOTTOCATEGORIE: CANCELLAZIONE SOTTOCATEGORIA SELEZIONATA
	$('body').on('click', 'a.cancella-sottocategoria', function (e) {
		e.preventDefault();

		var idRiga = $(this).data('id_riga');

		swal({
			title: 'Attenzione',
			text: "Confermi di voler eliminare la sottocategoria in oggetto? L'eliminazione è irreversibile.",
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
				$.post('categoriesottocategorie.php', { azione: 'cancella-sot', idRiga: idRiga }).done(
					function (data) {
						// chiudo
						swal.close();

						// ricarico la datatable per vedere le modifiche
						tabellaDatiSottocategorie.ajax.reload(null, false);
					}
				);
			} else {
				swal.close();
			}
		});
		return false;
	});

	// CATEGORIA: CARICAMENTO POPUP PER MODIFICA
	$('body').on('click', 'a.modifica-categoria', function (e) {
		e.preventDefault();

		$('#form-cat input').removeClass('errore');

		$('#form-cat')[0].reset();

		var idRiga = $(this).data('id_riga');

		$.post('categoriesottocategorie.php', { azione: 'recupera-cat', idRiga: idRiga }).done(
			function (data) {
				var dati = JSON.parse(data);

				//Scorro i dati ricevuti dalla funzione "recupera-cat"
				for (var chiave in dati) {
					if (dati.hasOwnProperty(chiave)) {
						$('#form-cat')
							.find('input#' + chiave)
							.val(dati[chiave]);
					}
				}

				// aggiungo il campo CodiceCliente come id della modifica
				$('#form-cat').find('input#cat_IdCategoria_Aux').val(dati['cat_IdCategoria']);
				$('#form-cat').find('input#cat_azione').val('modifica');
				$('#modal-cat-label').text('MODIFICA CATEGORIA');

				$('#modal-cat').modal('show');
			}
		);

		return false;
	});

	// SOTTOCATEGORIA: CARICAMENTO POPUP PER MODIFICA
	$('body').on('click', 'a.modifica-sottocategoria', function (e) {
		e.preventDefault();

		$('#form-sot input').removeClass('errore');

		$('#form-sot')[0].reset();

		var idRiga = $(this).data('id_riga');

		$.post('categoriesottocategorie.php', { azione: 'caricaSelectCategoriaAppartenenza' }).done(
			function (dataSelectCategorie) {
				if (dataSelectCategorie == 'NO_CAT') {
					$('#sot_Categoria').prop('disabled', true);
					$('#sot_Categoria').html(dataSelectCategorie);
				} else {
					$('#sot_Categoria').prop('disabled', false);
					$('#sot_Categoria').html(dataSelectCategorie);
				}

				$.post('categoriesottocategorie.php', { azione: 'recupera-sot', idRiga: idRiga }).done(
					function (data) {
						var dati = JSON.parse(data);

						//Scorro i dati ricevuti dalla funzione "recupera-sot"
						for (var chiave in dati) {
							if (dati.hasOwnProperty(chiave)) {
								$('#form-sot')
									.find('input#' + chiave)
									.val(dati[chiave]);
								$('#form-sot select#' + chiave).val(dati[chiave]);
								$('#form-sot select#' + chiave).selectpicker('refresh');
							}
						}
						$('#form-sot')
							.find('input#sot_IdSottocategoria_Aux')
							.val(dati['sot_IdSottocategoria']);

						$('#form-sot').find('input#sot_azione').val('modifica');
						$('#modal-sot-label').text('MODIFICA SOTTOCATEGORIA');

						$('#modal-sot').modal('show');
					}
				);
			}
		);

		return false;
	});

	// CATEGORIA: SALVATAGGIO DA POPUP MODIFICA/INSERIMENTO
	$('body').on('click', '#salva-categoria', function (e) {
		e.preventDefault();

		var errori = 0;

		// Controllo riempimento campi obbligatori (INPUT)
		$('#form-cat .obbligatorio').each(function () {
			if ($(this).val() == '') {
				errori++;
				$(this).addClass('errore');
			}
		});

		// Controllo riempimento campi obbligatori (SELECT)
		$('#form-cat .selectpicker').each(function () {
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
			$.post('categoriesottocategorie.php', {
				azione: 'salva-cat',
				data: $('#form-cat').serialize(),
			}).done(function (data) {
				// se è tutto OK
				if (data == 'OK') {
					$('#modal-cat').modal('hide');

					// ricarico la datatable per vedere le modifiche
					tabellaDatiCategorie.ajax.reload(null, false);
				} // restituisco un errore senza chiudere la modale
				else {
					swal({
						title: 'ATTENZIONE',
						text: 'Operazione non risucita.',
						icon: 'warning',
						button: 'Ho capito',

						closeModal: true,
					});
				}
			});
		}

		return false;
	});

	// SOTTOCATEGORIA: SALVATAGGIO DA POPUP MODIFICA/INSERIMENTO
	$('body').on('click', '#salva-sottocategoria', function (e) {
		e.preventDefault();

		// inizializzo il contatore errori
		var errori = 0;

		// Controllo riempimento campi obbligatori (INPUT)
		$('#form-sot .obbligatorio').each(function () {
			if ($(this).val() == '') {
				errori++;
				$(this).addClass('errore');
			}
		});

		// Controllo riempimento campi obbligatori (SELECT)
		$('#form-sot .selectpicker').each(function () {
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
			$.post('categoriesottocategorie.php', {
				azione: 'salva-sot',
				data: $('#form-sot').serialize(),
			}).done(function (data) {
				// se è tutto OK
				if (data == 'OK') {
					$('#modal-sot').modal('hide');

					// ricarico la datatable per vedere le modifiche
					tabellaDatiSottocategorie.ajax.reload(null, false);
				} // restituisco un errore senza chiudere la modale
				else {
					swal({
						title: 'ATTENZIONE',
						text: 'Operazione non risucita.',
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
