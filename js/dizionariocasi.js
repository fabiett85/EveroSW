(function ($) {
	'use strict';
	var g_idRisorsa;
	var g_tipoCaso;

	var tabellaDatiCasi;
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

	// FUNZIONE: RECUPERA ELENCO EVENTI
	function visualizzaElencoEventi(idRisorsa, tipoCaso) {
		// Visualizzo elenco dei casi previsti
		tabellaDatiCasi.ajax.url(
			'dizionariocasi.php?azione=mostra&idRisorsa=' +
				idRisorsa +
				'&tipoCaso=' +
				tipoCaso
		);
		tabellaDatiCasi.ajax.reload();
	}

	$(function () {
		//VALORE DEFAULT SELECTPICKER
		$('.selectpicker').selectpicker({
			noneSelectedText: 'Seleziona...',
		});

		//VISUALIZZA DATATABLE ELENCO CASI
		tabellaDatiCasi = $('#tabellaDati-casi').DataTable({
			order: [
				[0, 'asc'],
				[1, 'asc'],
			],
			aLengthMenu: [
				[10, 20, 30, 50, 100, -1],
				[10, 20, 30, 50, 100, 'Tutti'],
			],
			iDisplayLength: 10,

			columns: [
				{ data: 'DescrizioneRisorsa' },
				{ data: 'IdEvento' },
				{ data: 'DescrizioneEvento' },
				{ data: 'CategoriaMES' },
				{ data: 'Abilitazione' },
				{ data: 'LogicaInvertita' },
				{ data: 'FlagManuale' },
				{ data: 'azioni' },
			],
			columnDefs: [
				{
					width: '5%',
					targets: [7],
					className: 'center-bolded',
				},
				{
					width: '10%',
					targets: [4, 5, 6],
				},
			],
			language: linguaItaliana,
		});

		// DIZIONARIO EVENTI: VISUALIZZA TABELLA
		visualizzaElencoEventi(g_idRisorsa, g_tipoCaso);
	});

	// DIZIONARIO EVENTI: CANCELLAZIONE SEGNALAZIONE DI CAMPO VUOTO
	$('#form-caso input').on('blur', function () {
		$(this).removeClass('errore');
	});

	// DIZIONARIO EVENTI: SU CAMBIO SELECT DI FILTRO (ID RISORSA e TIPO CASO) VISUALIZZAZIONE ELENCO CASI
	$('#cas_FiltroRisorse, #cas_FiltroTipi').on('change', function () {
		g_idRisorsa = $('#cas_FiltroRisorse').val();
		g_tipoCaso = $('#cas_FiltroTipi').val();

		visualizzaElencoEventi(g_idRisorsa, g_tipoCaso);
	});

	// DIZIONARIO EVENTI: INSERIMENTO NUOVO EVENTO
	$('#nuovo-caso').on('click', function () {
		$('#form-caso input').removeClass('errore');

		$('#form-caso')[0].reset();
		$('#form-caso #cas_IdRisorsa').attr('disabled', false);
		$('#form-caso #cas_IdEvento').attr('disabled', false);
		$('#form-caso #cas_DescrizioneEvento').attr('disabled', false);
		$('#form-caso .selectpicker').val('default');
		$('#form-caso .selectpicker').selectpicker('refresh');

		$('#form-caso').find('input#azione').val('nuovo');
		$('#modal-caso-label').text('INSERIMENTO NUOVO EVENTO');
		$('#modal-caso').modal('show');
	});

	// DIZIONARIO EVENTI: CANCELLAZIONE EVENTO SELEZIONATO
	$('body').on('click', 'a.cancella-caso', function (e) {
		e.preventDefault();

		var idRiga = $(this).data('id_riga');

		swal({
			title: 'Attenzione',
			text: "Confermi di voler eliminare l'evento in oggetto? L'eliminazione è irreversibile.",
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
				$.post('dizionariocasi.php', {
					azione: 'cancella-caso',
					id: idRiga,
				}).done(function (data) {
					swal.close();

					// ricarico la datatable per vedere le modifiche
					visualizzaElencoEventi(g_idRisorsa, g_tipoCaso);
				});
			} else {
				swal.close();
			}
		});
		return false;
	});

	// DIZIONARIO EVENTI: CARICAMENTO POPUP PER MODIFICA EVENTO
	$('body').on('click', 'a.modifica-caso', function (e) {
		e.preventDefault();

		$('#form-caso input').removeClass('errore');

		$('#form-caso')[0].reset();

		var idRiga = $(this).data('id_riga');

		$.post('dizionariocasi.php', { azione: 'recupera', codice: idRiga }).done(
			function (data) {
				var dati = JSON.parse(data);

				// Valorizzo popup con i dati recuperati
				for (var chiave in dati) {
					if (dati.hasOwnProperty(chiave)) {
						$('#form-caso')
							.find('input#' + chiave)
							.val(dati[chiave]);
						$('#form-caso select#' + chiave).val(dati[chiave]);
						$('#form-caso select#' + chiave).selectpicker('refresh');
					}
				}

				// Tratto separatamente la valorizzazione delle checkbox:
				// 'caso disabilitato'
				if (dati['cas_Disabilitato'] == 0) {
					$('#form-caso')
						.find('input#cas_Disabilitato')
						.prop('checked', true);
				} else {
					$('#form-caso')
						.find('input#cas_Disabilitato')
						.prop('checked', false);
				}
				// 'caso invertito'
				if (dati['cas_Invertito'] == 1) {
					$('#form-caso')
						.find('input#cas_Invertito')
						.prop('checked', true);
				} else {
					$('#form-caso')
						.find('input#cas_Invertito')
						.prop('checked', false);
				}

				// Imposto eventuali disabilitazioni
				$('#form-caso #cas_IdEvento').attr('disabled', true);
				$('#form-caso #cas_IdRisorsa').attr('disabled', true);
				$('#form-caso .selectpicker').selectpicker('refresh');

				$('#form-caso').find('input#azione').val('modifica');
				$('#modal-caso-label').text('MODIFICA CASO');
				$('#modal-caso').modal('show');
			}
		);

		return false;
	});

	// DIZIONARIO EVENTI: SALVATAGGIO DA POPUP MODIFICA/INSERIMENTO EVENTO
	$('body').on('click', '#salva-caso', function (e) {
		e.preventDefault();

		// inizializzo il contatore errori
		var errori = 0;

		// Controllo riempimento campi obbligatori (INPUT)
		$('#form-caso .obbligatorio').each(function () {
			if ($(this).val() == '') {
				errori++;
				$(this).addClass('errore');
			}
		});

		// Controllo riempimento campi obbligatori (SELECT)
		$('#form-caso .selectpicker').each(function () {
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
			var flagManuale;
			var falgDisabilitato;
			var flagLogicaInvertita;

			// Tratto separatamente la valorizzazione delle checkbox:
			// 'caso disabilitato'
			if ($('#cas_Disabilitato').is(':checked')) {
				falgDisabilitato = 0;
			} else {
				falgDisabilitato = 1;
			}
			// 'caso invertito'
			if ($('#cas_Invertito').is(':checked')) {
				flagLogicaInvertita = 1;
			} else {
				flagLogicaInvertita = 0;
			}

			var descrizioneCaso = $(
				'#form-caso #cas_IdCaso option:selected'
			).text();
			var idCaso = $('#form-caso #cas_IdCaso').val();

			// salvo i dati
			$.post('dizionariocasi.php', {
				azione: 'salva-caso',
				data: $('#form-caso').serialize(),
				idCaso: idCaso,
				descrizioneCaso: descrizioneCaso,
				falgDisabilitato: falgDisabilitato,
				flagLogicaInvertita: flagLogicaInvertita,
			}).done(function (data) {
				// se è tutto OK
				if (data == 'OK') {
					$('#modal-caso').modal('hide');

					// ricarico la datatable per vedere le modifiche
					visualizzaElencoEventi(g_idRisorsa, g_tipoCaso);
				} // restituisco un errore senza chiudere la modale
				else {
					swal({
						title: 'Operazione non eseguita.',
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
