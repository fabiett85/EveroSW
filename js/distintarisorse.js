(function ($) {
	'use strict';

	var tabellaDatiDRC;

	var g_idProdotto;
	var g_nomeProdotto;
	var g_idLineaProduzione;
	var g_nomeLineaProduzione;

	var linguaItaliana = {
		processing: 'Caricamento...',
		search: 'Ricerca: ',
		lengthMenu: '_MENU_ righe per pagina',
		zeroRecords: 'Distinta risorse non compilata per prodotto e linea selezionati.',
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
		$.post('distintarisorse.php', { azione: 'recuperaP', idProdotto: g_idProdotto }).done(
			function (data) {
				if (data != 'NO_ROWS') {
					var dati = JSON.parse(data);

					// visualizzo i dati del prodotto finito selezionato e il titolo del corpo distinta
					$('#dr_DescrizioneProdottoSelezionato').val(dati['prd_Descrizione']);
					$('#dr_TempoTeoricoProdottoSelezionato').val(dati['prd_TempoTeorico']);

					$.post('distintarisorse.php', {
						azione: 'mostraDRC',
						idProdotto: g_idProdotto,
						idLineaProduzione: g_idLineaProduzione,
					}).done(function (data) {
						$('#tabellaDati-DRC').dataTable().fnClearTable();

						// aggiorno i dati della tabella e lo stato di attivazione/disattivazione dei pulsanti
						if (data != 'NO_ROWS') {
							$('#tabellaDati-DRC').dataTable().fnAddData(JSON.parse(data));

							$('#inizializza-distinta-risorse').prop('hidden', true);
							$('#resetta-distinta-risorse').prop('hidden', false);
							$('#aggiungi-risorsa').prop('disabled', false);
							$('#inizializza-distinta-risorse').prop('disabled', true);
						} else {
							$('#inizializza-distinta-risorse').prop('hidden', false);
							$('#resetta-distinta-risorse').prop('hidden', true);
							$('#aggiungi-risorsa').prop('disabled', true);
							$('#inizializza-distinta-risorse').prop('disabled', false);
						}
					});
				} else {
					$('#inizializza-distinta-risorse').prop('hidden', false);
					$('#resetta-distinta-risorse').prop('hidden', true);
					$('#aggiungi-risorsa').prop('disabled', true);
					$('#inizializza-distinta-risorse').prop('disabled', true);
				}
			}
		);
	}

	// DEFINIZIONE TABELLA CORPO DISTINTA RISORSE
	$(function () {
		//VALORE DEFAULT SELECTPICKER
		$('.selectpicker').selectpicker({
			noneSelectedText: 'Seleziona...',
		});

		// DISTINTA MACCHINE: DEFINIZIONE TABELLA
		tabellaDatiDRC = $('#tabellaDati-DRC').DataTable({
			order: [[6, 'asc']],
			aLengthMenu: [
				[10, 25, 50, 100, -1],
				[10, 25, 50, 100, 'Tutti'],
			],

			iDisplayLength: 25,
			columns: [
				{ data: 'IdRisorsa' },
				{ data: 'Descrizione' },
				{ data: 'Ricetta' },
				{ data: 'NoteSetup' },
				{ data: 'FlagUltimaMacchina' },
				//{ "data": "AbiMisure" },
				//{ "data": "TempoTeoricoPezzo" },
				{ data: 'azioni' },
				{ data: 'Ordinamento' },
			],
			columnDefs: [
				{
					targets: [6],
					visible: false,
				},
				{
					targets: [4, 5],
					className: 'center-bolded',
					width: '5%',
				},
				{
					width: '10%',
					targets: [0, 1],
				},
			],
			language: linguaItaliana,
		});

		// D. MACCHINE POST REFRESH: RECUPERO E SETTO CORRETTAMENTE I VALORI DELLA SELECT DI FILTRO 'PRODOTTO'
		if (sessionStorage.getItem('dr_idProdotto') === null) {
			g_nomeProdotto = $('#dr_ProdottoSelezionato option:selected').text();
			g_idProdotto = $('#dr_ProdottoSelezionato option:selected').val();
		} else {
			g_idProdotto = sessionStorage.getItem('dr_idProdotto');
			g_nomeProdotto = sessionStorage.getItem('dr_nomeProdotto');
		}
		$('#dr_ProdottoSelezionato').val(g_idProdotto);
		$('#dr_ProdottoSelezionato').selectpicker('refresh');

		// D. MACCHINE POST REFRESH: RECUPERO E SETTO CORRETTAMENTE I VALORI DELLA SELECT DI FILTRO 'LINEA'
		if (sessionStorage.getItem('dr_idLineaProduzione') === null) {
			g_nomeLineaProduzione = $('#dr_LineeProduzione option:selected').text();
			g_idLineaProduzione = $('#dr_LineeProduzione option:selected').val();
		} else {
			g_idLineaProduzione = sessionStorage.getItem('dr_idLineaProduzione');
			g_nomeLineaProduzione = sessionStorage.getItem('dr_nomeLineaProduzione');
		}
		$('#dr_LineeProduzione').val(g_idLineaProduzione);
		$('#dr_LineeProduzione').selectpicker('refresh');

		// D. MACCHINE POST REFRESH: VISUALIZZAZIONE DETTAGLIO DISTINTA RISORSE
		mostraDistinta();
	});

	// D. MACCHINE: RIMUOVO BORDO DI ERRORE SU SELEZIONE CAMPO
	$('#form-nuova-risorsa input').on('blur', function () {
		$(this).removeClass('errore');
	});

	// D. MACCHINE: SU CAMBIO SELECT DI FILTRO (PRODOTTO E LINEA): RICARICO LA DISTINTA VISUALIZZATA
	$('#dr_ProdottoSelezionato, #dr_LineeProduzione').on('change', function () {
		// Recupero informazioni PRODOTTO e LINEA DI PRODUZIONE selezionate e le memorizzo nelle variabili globali e in sessione
		g_idProdotto = $('#dr_ProdottoSelezionato').val();
		g_idLineaProduzione = $('#dr_LineeProduzione').val();
		g_nomeProdotto = $('#dr_ProdottoSelezionato option:selected').text();
		g_nomeLineaProduzione = $('#dr_LineeProduzione option:selected').text();

		sessionStorage.setItem('dr_idLineaProduzione', g_idLineaProduzione);
		sessionStorage.setItem('dr_nomeLineaProduzione', g_nomeLineaProduzione);
		sessionStorage.setItem('dr_idProdotto', g_idProdotto);
		sessionStorage.setItem('dr_nomeProdotto', g_nomeProdotto);

		// Recupero dati del prodotto selezionato
		mostraDistinta();
	});

	// D. MACCHINE: SU CAMBIO MACCHINA IN SELECT POPUP DI INSERIMENTO, AGGIORNO LA SELECT CON LE RICETTE DISPONIBILI
	$('#form-nuova-risorsa #drc_IdRisorsa').on('change', function () {
		var idRisorsa = $('#form-nuova-risorsa #drc_IdRisorsa').val();

		// Se non ho macchine disponibili
		if (idRisorsa == '') {
			$('#salva-drc').prop('disabled', true);
			$('#form-nuova-risorsa #drc_FattoreConteggi').val(1);
			$('#form-nuova-risorsa #drc_IdRicetta').html(
				'<option value="">Nessuna macchina disponibile</option>'
			);
			$('#form-nuova-risorsa #drc_IdRicetta').prop('disabled', true);
			$('#form-nuova-risorsa .selectpicker').selectpicker('refresh');
		} else {
			$('#salva-drc').prop('disabled', false);
			$('#form-nuova-risorsa #drc_FattoreConteggi').val(1);

			//Recupero le risorse disponibili e non ancora aggiunte alla distinta, per popolare la relativa SELECT
			$.post('distintarisorse.php', {
				azione: 'caricaSelectRicette',
				idRisorsa: idRisorsa,
				idProdotto: g_idProdotto,
			}).done(function (data) {
				// Se non ho ricetta disponibili per la macchina selezioanta
				if (data == 'NO_RIC') {
					$('#form-nuova-risorsa #drc_IdRicetta').html(
						'<option value="">Nessuna ricetta disponibile</option>'
					);
					$('#form-nuova-risorsa #drc_IdRicetta').prop('disabled', true);
				} else {
					$('#form-nuova-risorsa #drc_IdRicetta').html(data);
					$('#form-nuova-risorsa #drc_IdRicetta').prop('disabled', false);
				}
				$('#form-nuova-risorsa .selectpicker').selectpicker('refresh');

				$.post('distintarisorse.php', {
					azione: 'fattoreConteggi',
					idRisorsa: idRisorsa,
				}).done(function (data) {
					// Se non ho ricetta disponibili per la macchina selezioanta
					$('#form-nuova-risorsa #drc_FattoreConteggi').val(data);
				});
			});
		}
	});

	// D. MACCHINE: INIZIALIZZA DISTINTA PER PRODOTTO FINITO E LINEA SELEZIONATI (POPOLATA CON LE MACCHINE RELATIVE ALLA LINEA SELEZIONATA)
	$('#inizializza-distinta-risorse').on('click', function () {
		// Inizializzo la distinta macchine per il prodotto selezionato, con le macchine appartenenti alla linea selezionata
		$.post('distintarisorse.php', {
			azione: 'inizializza-distinta-risorse',
			idProdotto: g_idProdotto,
			idLineaProduzione: g_idLineaProduzione,
		}).done(function (data) {
			if (data == 'OK') {
				// Ricarico la datatable per vedere le modifiche
				mostraDistinta();

				$('#inizializza-distinta-risorse').prop('hidden', true);
				$('#inizializza-distinta-risorse').prop('disabled', false);
				$('#resetta-distinta-risorse').prop('hidden', false);
				$('#aggiungi-risorsa').prop('disabled', false);
			}
		});
	});

	// D. MACCHINE: RESETTA DISTINTA ATTUALE PER PRODOTTO FINITO E LINEA SELEZIONATI
	$('#resetta-distinta-risorse').on('click', function () {
		swal({
			title: 'Attenzione',
			text: "Confermi di voler cancellare la distinta macchine compilata? L'eliminazione è irreversibile.",
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
				// Elimino la distinta macchine definita
				$.post('distintarisorse.php', {
					azione: 'resetta-distinta-risorse',
					idProdotto: g_idProdotto,
					idLineaProduzione: g_idLineaProduzione,
				}).done(function (data) {
					if (data == 'OK') {
						$('#tabellaDati-DRC').dataTable().fnClearTable();
						$('#inizializza-distinta-risorse').prop('hidden', false);
						$('#inizializza-distinta-risorse').prop('disabled', false);
						$('#resetta-distinta-risorse').prop('hidden', true);
						$('#aggiungi-risorsa').prop('disabled', true);
					}
				});
			} else {
				swal.close();
			}
		});
		return false;
	});

	// D. MACCHINE: VISUALIZZAZONE POPUP 'AGGIUNTA NUOVO ELEMENTO A DISTINTA'
	$('#aggiungi-risorsa').on('click', function () {
		$('#form-nuova-risorsa input').removeClass('errore');

		$('#form-nuova-risorsa')[0].reset();
		$('#form-nuova-risorsa #drc_NomeLineaProduzione').val(g_nomeLineaProduzione);
		$('#form-nuova-risorsa #drc_IdLineaProduzione').val(g_idLineaProduzione);
		$('#form-nuova-risorsa #drc_Prodotto').val(g_idProdotto);
		$('#form-nuova-risorsa #drc_NomeProdotto').val(g_nomeProdotto);

		// Recupero le risorse disponibili e non ancora aggiunte alla distinta, per popolare la relativa SELECT
		$.post('distintarisorse.php', {
			azione: 'caricaSelectRisorse',
			idProdotto: g_idProdotto,
			idLineaProduzione: g_idLineaProduzione,
		}).done(function (data) {
			if (data == 'NO_RIS') {
				$('#form-nuova-risorsa #drc_IdRisorsa').html(
					'<option value="">Nessuna macchina disponibile</option>'
				);
				$('#form-nuova-risorsa #drc_IdRisorsa').prop('disabled', true);
				$('#form-nuova-risorsa #drc_IdRisorsa').selectpicker('refresh');
				$('#salva-drc').prop('disabled', true);
			} else {
				$('#form-nuova-risorsa #drc_IdRisorsa').html(data);
				$('#form-nuova-risorsa #drc_IdRisorsa').val('default');
				$('#form-nuova-risorsa #drc_IdRisorsa').prop('disabled', false);
				$('#form-nuova-risorsa #drc_IdRisorsa').selectpicker('refresh');
				$('#salva-drc').prop('disabled', false);
			}

			// Valorizzo opportunamente la select di selezione ricetta
			$('#form-nuova-risorsa #drc_IdRicetta').html(
				'<option value="">Nessuna macchina selezionata</option>'
			);
			$('#form-nuova-risorsa #drc_IdRicetta').prop('disabled', true);
			$('#form-nuova-risorsa #drc_IdRicetta').selectpicker('refresh');
		});

		$('#form-nuova-risorsa').find('input#drc_Azione').val('nuovo');
		$('#modal-nuova-risorsa-label').text('AGGIUNTA NUOVA MACCHINA');
		$('#modal-nuova-risorsa').modal('show');
	});

	// D. MACCHINE: VISUALIZZAZIONE POPUP 'MODIFICA ELEMENTO DISTINTA'
	$('body').on('click', 'a.modifica-drc', function (e) {
		e.preventDefault();

		// Valorizzazione campi popup
		g_idProdotto = $('#dr_ProdottoSelezionato').val();
		var idRisorsa = $(this).data('id-risorsa');

		$('#form-nuova-risorsa input').removeClass('errore');

		$('#form-nuova-risorsa')[0].reset();
		$('#form-nuova-risorsa #drc_NomeLineaProduzione').val(g_nomeLineaProduzione);
		$('#form-nuova-risorsa #drc_IdLineaProduzione').val(g_idLineaProduzione);
		$('#form-nuova-risorsa #drc_Prodotto').val(g_idProdotto);
		$('#form-nuova-risorsa #drc_NomeProdotto').val(g_nomeProdotto);

		// Invoco il metodo per recuperare i valori per la riga selezionata
		$.post('distintarisorse.php', {
			azione: 'recupera-drc',
			idProdotto: g_idProdotto,
			idRisorsa: idRisorsa,
		}).done(function (data) {
			var dati = JSON.parse(data);

			// Popolo il popup con i dati ricavati
			for (var chiave in dati) {
				if (dati.hasOwnProperty(chiave)) {
					$('#form-nuova-risorsa')
						.find('input#' + chiave)
						.val(dati[chiave]);
					$('#form-nuova-risorsa select#' + chiave).val(dati[chiave]);
					$('#form-nuova-risorsa select#' + chiave).selectpicker('refresh');
				}
			}

			// Recupero valore per checkbox selezione macchina come ultima di linea
			if (dati['drc_FlagUltima'] == 1) {
				$('#form-nuova-risorsa').find('input#drc_FlagUltima').prop('checked', true);
			} else {
				$('#form-nuova-risorsa').find('input#drc_FlagUltima').prop('checked', false);
			}

			// Tratto separatamente il popolamento della select 'risorse'
			$.post('distintarisorse.php', {
				azione: 'caricaSelectRisorse',
				idProdotto: g_idProdotto,
				idLineaProduzione: g_idLineaProduzione,
				risorsa: idRisorsa,
			}).done(function (data) {
				$('#form-nuova-risorsa #drc_IdRisorsa').html(data);
				$('#form-nuova-risorsa #drc_IdRisorsa').prop('disabled', true);
				$('#form-nuova-risorsa #drc_IdRisorsa').selectpicker('refresh');

				// Tratto separatamente il popolamento della select 'ricette'
				$.post('distintarisorse.php', {
					azione: 'caricaSelectRicette',
					idRisorsa: $('#form-nuova-risorsa #drc_IdRisorsa').val(),
					idRicetta: dati['drc_IdRicetta'],
				}).done(function (data) {
					if (data == 'NO_RIC') {
						$('#form-nuova-risorsa #drc_IdRicetta').html(
							'<option value="">Nessuna ricetta disponibile</option>'
						);
						$('#form-nuova-risorsa #drc_IdRicetta').prop('disabled', true);
						$('#form-nuova-risorsa #drc_IdRicetta').selectpicker('refresh');
					} else {
						$('#form-nuova-risorsa #drc_IdRicetta').html(data);
						$('#form-nuova-risorsa #drc_IdRicetta').prop('disabled', false);
						$('#form-nuova-risorsa #drc_IdRicetta').selectpicker('refresh');
					}

					$('#salva-drc').prop('disabled', false);

					$('#form-nuova-risorsa').find('input#drc_Azione').val('modifica');
					$('#modal-nuova-risorsa-label').text('MODIFICA MACCHINA');
					$('#modal-nuova-risorsa').modal('show');
				});
			});
		});

		return false;
	});

	// D. MACCHINE: SALVATAGGIO DA POPUP MODIFICA/INSERIMENTO ELEMENTO DISTINTA
	$('body').on('click', '#salva-drc', function (e) {
		e.preventDefault();

		// inizializzo il contatore errori
		var errori = 0;

		// Controllo riempimento campi obbligatori (INPUT)
		$('#form-nuova-risorsa .obbligatorio').each(function () {
			if ($(this).val() == '') {
				errori++;
				$(this).addClass('errore');
			}
		});

		// Controllo riempimento campi obbligatori (SELECT)
		$('#form-nuova-risorsa .selectpicker').each(function () {
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
			var idRisorsa = $('#drc_IdRisorsa').val();
			$('#drc_IdRisorsa').prop('disabled', false);

			var flagUltimaMacchina;

			// Recupero valore della checkbox e lo formato opportunamente
			if ($('#drc_FlagUltima').is(':checked')) {
				flagUltimaMacchina = 1;
			} else {
				flagUltimaMacchina = 0;
			}

			// Salvo i dati
			$.post('distintarisorse.php', {
				azione: 'salva-drc',
				data: $('#form-nuova-risorsa').serialize(),
				flagUltimaMacchina: flagUltimaMacchina,
			}).done(function (data) {
				// se è tutto OK
				if (data == 'OK') {
					$('#modal-nuova-risorsa').modal('hide');

					// ricarico la datatable per vedere le modifiche
					mostraDistinta();
				} else {
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
	$('body').on('click', 'a.cancella-drc', function (e) {
		e.preventDefault();

		var idRisorsa = $(this).data('id-risorsa');
		g_idProdotto = $(this).data('id-prodotto');

		swal({
			title: 'Attenzione',
			text: "Confermi di voler eliminare la macchina in oggetto dalla distinta? Questo comporterà l'eliminazione dei dettagli associati. L'eliminazione è irreversibile.",
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
				$.post('distintarisorse.php', {
					azione: 'cancella-DRC',
					idRisorsa: idRisorsa,
					idProdotto: g_idProdotto,
				}).done(function (data) {
					swal.close();

					$.post('distintarisorse.php', {
						azione: 'mostraDRC',
						idProdotto: g_idProdotto,
						idLineaProduzione: g_idLineaProduzione,
					}).done(function (datiTabella) {
						if (datiTabella != 'NO_ROWS') {
							$('#tabellaDati-DRC').dataTable().fnClearTable();
							$('#tabellaDati-DRC').dataTable().fnAddData(JSON.parse(datiTabella));
						} else {
							$('#tabellaDati-DRC').dataTable().fnClearTable();
						}
					});
				});
			} else {
				swal.close();
			}
		});
		return false;
	});
})(jQuery);
