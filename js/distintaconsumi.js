(function ($) {
	'use strict';

	var tabellaDatiDC;

	var g_idRisorsa;
	var g_nomeRisorsa;

	var linguaItaliana = {
		processing: 'Caricamento...',
		search: 'Ricerca: ',
		lengthMenu: '_MENU_ righe per pagina',
		zeroRecords: 'Distinta consumi non compilata per macchina selezionata.',
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

	function mostraDistinta() {
		tabellaDatiDC.ajax.url('distintaconsumi.php?azione=mostraDC&idRisorsa=' + g_idRisorsa);
		tabellaDatiDC.ajax.reload();
	}

	// DEFINIZIONE TABELLA CORPO DISTINTA RISORSE
	$(function () {
		//VALORE DEFAULT SELECTPICKER
		$('.selectpicker').selectpicker({
			noneSelectedText: 'Seleziona...',
		});

		// DISTINTA MACCHINE: DEFINIZIONE TABELLA
		tabellaDatiDC = $('#tabellaDati-DC').DataTable({
			order: [[0, 'asc']],
			aLengthMenu: [
				[10, 25, 50, 100, -1],
				[10, 25, 50, 100, 'Tutti'],
			],

			iDisplayLength: 25,
			columns: [
				{ data: 'Consumo' },
				{ data: 'Udm' },
				{ data: 'TipoCalcolo' },
				{ data: 'ConsumoIpotetico' },
				{ data: 'azioni' },
			],
			columnDefs: [
				{
					targets: [4],
					width: '5%',
					className: 'center-bolded',
				},
			],
			language: linguaItaliana,
		});

		// D. MACCHINE POST REFRESH: RECUPERO E SETTO CORRETTAMENTE I VALORI DELLA SELECT DI FILTRO 'PRODOTTO'
		if (sessionStorage.getItem('dc_idRisorsa') === null) {
			g_nomeRisorsa = $('#dc_IdRisorsa option:selected').text();
			g_idRisorsa = $('#dc_IdRisorsa option:selected').val();
		} else {
			g_idRisorsa = sessionStorage.getItem('dc_idRisorsa');
			g_nomeRisorsa = sessionStorage.getItem('dc_nomeRisorsa');
		}
		$('#dc_IdRisorsa').val(g_idRisorsa);
		$('#dc_IdRisorsa').selectpicker('refresh');

		mostraDistinta();
	});

	// D. MACCHINE: RIMUOVO BORDO DI ERRORE SU SELEZIONE CAMPO
	$('#form-nuovo-consumo input').on('blur', function () {
		$(this).removeClass('errore');
	});

	// D. MACCHINE: SU CAMBIO SELECT DI FILTRO (PRODOTTO E LINEA): RICARICO LA DISTINTA VISUALIZZATA
	$('#dc_IdRisorsa').on('change', function () {
		// Recupero informazioni PRODOTTO e LINEA DI PRODUZIONE selezionate e le memorizzo nelle variabili globali e in sessione
		g_idRisorsa = $('#dc_IdRisorsa').val();
		g_nomeRisorsa = $('#dc_IdRisorsa option:selected').text();

		sessionStorage.setItem('dc_idRisorsa', g_idRisorsa);
		sessionStorage.setItem('dc_nomeRisorsa', g_nomeRisorsa);
		mostraDistinta();
	});

	// D. MACCHINE: VISUALIZZAZONE POPUP 'AGGIUNTA NUOVO ELEMENTO A DISTINTA'
	$('#aggiungi-risorsa').on('click', function () {
		$('#form-nuova-consumo input').removeClass('errore');

		$('#form-nuovo-consumo')[0].reset();

		// Recupero i consumi disponibili e non ancora aggiunti alla distinta, per popolare la relativa SELECT
		$.post('distintaconsumi.php', { azione: 'caricaSelectConsumi', idRisorsa: g_idRisorsa }).done(
			function (data) {
				if (data == 'NO_RIS') {
					$('#form-nuovo-consumo #dc_IdTipoConsumo').html(
						'<option value="">Nessun consumo disponibile</option>'
					);
					$('#form-nuovo-consumo #dc_IdTipoConsumo').prop('disabled', true);
					$('#form-nuovo-consumo #dc_IdTipoConsumo').selectpicker('refresh');
					$('#salva-dc').prop('disabled', true);
				} else {
					$('#form-nuovo-consumo #dc_IdTipoConsumo').html(data);
					$('#form-nuovo-consumo #dc_IdTipoConsumo').val('default');
					$('#form-nuovo-consumo #dc_IdTipoConsumo').prop('disabled', false);
					$('#form-nuovo-consumo #dc_IdTipoConsumo').selectpicker('refresh');
					$('#salva-dc').prop('disabled', false);
				}
			}
		);

		$('#form-nuovo-consumo').find('input#dc_Azione').val('nuovo');
		$('#modal-nuovo-consumo-label').text('AGGIUNTA NUOVO CONSUMO');
		$('#modal-nuovo-consumo').modal('show');
	});

	// D. MACCHINE: VISUALIZZAZIONE POPUP 'MODIFICA ELEMENTO DISTINTA'
	$('body').on('click', 'a.modifica-dc', function (e) {
		e.preventDefault();

		// Valorizzazione campi popup
		var id_Risorsa = $(this).data('id-risorsa');
		var id_Consumo = $(this).data('id-consumo');

		$('#form-nuovo-consumo input').removeClass('errore');

		$('#form-nuovo-consumo')[0].reset();
		// Invoco il metodo per recuperare i valori per la riga selezionata
		$.post('distintaconsumi.php', {
			azione: 'recupera-dc',
			idRisorsa: id_Risorsa,
			idConsumo: id_Consumo,
		}).done(function (data) {
			var dati = JSON.parse(data);

			// Popolo il popup con i dati ricavati
			for (var chiave in dati) {
				if (dati.hasOwnProperty(chiave)) {
					$('#form-nuovo-consumo')
						.find('input#' + chiave)
						.val(dati[chiave]);
					$('#form-nuovo-consumo select#' + chiave).val(dati[chiave]);
					$('#form-nuovo-consumo select#' + chiave).selectpicker('refresh');
				}
			}
			$.post('distintaconsumi.php', {
				azione: 'caricaSelectConsumi',
				idRisorsa: g_idRisorsa,
				idConsumo: id_Consumo,
			}).done(function (data) {
				$('#form-nuovo-consumo #dc_IdTipoConsumo').html(data);
				$('#form-nuovo-consumo #dc_IdTipoConsumo').val(id_Consumo);
				$('#form-nuovo-consumo #dc_IdTipoConsumo').prop('disabled', false);
				$('#form-nuovo-consumo #dc_IdTipoConsumo').selectpicker('refresh');
				$('#salva-dc').prop('disabled', false);
			});
		});

		$('#form-nuovo-consumo').find('input#dc_Azione').val('modifica');
		$('#modal-nuovo-consumo-label').text('MODIFICA CONSUMO');
		$('#modal-nuovo-consumo').modal('show');

		return false;
	});

	// D. MACCHINE: SALVATAGGIO DA POPUP MODIFICA/INSERIMENTO ELEMENTO DISTINTA
	$('body').on('click', '#salva-dc', function (e) {
		e.preventDefault();

		// inizializzo il contatore errori
		var errori = 0;

		// Controllo riempimento campi obbligatori (INPUT)
		$('#form-nuovo-consumo .obbligatorio').each(function () {
			if ($(this).val() == '') {
				errori++;
				$(this).addClass('errore');
			}
		});

		// Controllo riempimento campi obbligatori (SELECT)
		$('#form-nuovo-consumo .selectpicker').each(function () {
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
			// Salvo i dati
			$.post('distintaconsumi.php', {
				azione: 'salva-dc',
				data: $('#form-nuovo-consumo').serialize(),
				idRisorsa: g_idRisorsa,
			}).done(function (data) {
				// se è tutto OK
				if (data == 'OK') {
					$('#modal-nuovo-consumo').modal('hide');

					mostraDistinta();
				} else {
					console.log(data);
					swal({
						title: 'ATTENZIONE',
						text: 'Operazione non risucita.',
						icon: 'error',
						button: 'Ho capito',

						closeModal: true,
					});
				}
			});
		}

		return false;
	});

	// D. MACCHINE: CANCELLAZIONE MACCHINA DA DISTINTA
	$('body').on('click', 'a.cancella-dc', function (e) {
		e.preventDefault();

		var id_Risorsa = $(this).data('id-risorsa');
		var id_Consumo = $(this).data('id-consumo');

		swal({
			title: 'Attenzione',
			text: "Confermi di voler eliminare il consumo in oggetto dalla distinta? Questo comporterà l'eliminazione dei dettagli associati. L'eliminazione è irreversibile.",
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
				$.post('distintaconsumi.php', {
					azione: 'cancella-DC',
					idRisorsa: id_Risorsa,
					idConsumo: id_Consumo,
				}).done(function (data) {
					swal.close();

					mostraDistinta();
				});
			} else {
				console.log(data);
				swal.close();
				swal({
					title: 'ATTENZIONE',
					text: 'Operazione non risucita.',
					icon: 'error',
					button: 'Ho capito',
					closeModal: true,
				});
			}
		});
		return false;
	});
})(jQuery);
