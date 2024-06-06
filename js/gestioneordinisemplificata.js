(function ($) {
	'use strict';

	var url = 'gestioneordinisemplificata.php';
	var tabellaOrdini;
	var g_idStatoOrdine = $('#filtro-ordini').val();

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
	//Genero data e ora odierne per nomenclatura report esportazione
	var today = moment();

	// FUNZIONE: RECUPERA ELENCO ORDINI
	function visualizzaElencoOrdini(idStatoOrdine) {
		// visualizzo il corpo della distinta risorse
		$.post('gestioneordinisemplificata.php', {
			azione: 'mostra',
			idStatoOrdine: idStatoOrdine,
		}).done(function (data) {
			try {
				tabellaOrdini.clear();
				tabellaOrdini.rows.add(JSON.parse(data)).draw();
			} catch (error) {
				console.error(error);
				console.log(data);
				swal({
					title: 'ERRORE!',
					text: 'Errore nel recupero degli ordini.',
					icon: 'error',
				});
			}
		});
	}

	// FUNZIONE: CARICAMENTO ORDINE (SU TUTTE LE MACCHINE DELLA LINEA)
	function caricamentoOrdineMacchinaMultipla(elencoRisorse, idProduzione) {
		// Se utente conferma: procedo al caricamento multiplo
		$.post('gestioneordinisemplificata.php', {
			azione: 'carica-ordine-multiplo',
			elencoRisorse: JSON.stringify(elencoRisorse),
			idProduzione: idProduzione,
		}).done(function (data) {

			// Se procedura andata a buon fine, assegno l'id produzione caricato alla variabile globale competente, nascondo il popup di caricamento ordine
			if (data == 'OK') {
				visualizzaElencoOrdini(g_idStatoOrdine);
			} else {
				console.log(data);
				swal({
					title: 'ERRORE!',
					text: 'Errore in caricamento ordine.',
					icon: 'error',
				});
			}
		});
	}

	// FUNZIONE: SCARICAMENTO ORDINE (DA TUTTE LE MACCHINE DELLA LINEA)
	function scaricamentoOrdineMacchinaMultipla(elencoRisorse, idProduzione) {
		// Se utente non conferma: procedro al caricamento singolo dell'ordine solo sulla risorsa in oggetto
		$.post('gestioneordinisemplificata.php', {
			azione: 'scarica-ordine-multiplo',
			elencoRisorse: JSON.stringify(elencoRisorse),
			idProduzione: idProduzione,
		}).done(function (data) {
			// Se procedura andata a buon fine, assegno l'id produzione caricato alla variabile globale competente, nascondo il popup di caricamento ordine
			if (data == 'OK') {
				visualizzaElencoOrdini(g_idStatoOrdine);
			} else if (data == 'ORDINE_AVVIATO') {
				// visualizzo messaggio di errore
				swal({
					title: 'ATTENZIONE!',
					text: 'Avvio commessa in corso, impossibile procedere',
					icon: 'warning',
				});
			} else {
				console.log(data);
				swal({
					title: 'ERRORE!',
					text: 'Errore in scaricamento ordine.',
					icon: 'error',
				});
			}
		});
	}

	function salvaOrdine() {
		$.post(url, {
			azione: 'salva-ordine-produzione',
			data: $('#form-ordine-produzione').serialize(), risorseSelezionate: $('#form-ordine-produzione #op_RisorseSelezionate').val()
		}).done(function (data) {

			if (data == 'OK') {
				visualizzaElencoOrdini(g_idStatoOrdine);
				$('#modal-ordine-produzione').modal('hide');
			} else {
				swal({
					title: 'ERRORE!',
					text: "Errore nel salvataggio dell'ordine.",
					icon: 'error',
				});
			}
		});
	}

	//VISUALIZZA DATATABLE COMMESSE
	$(function () {
		//VALORE DEFAULT SELECTPICKER
		$('.selectpicker').selectpicker({
			noneSelectedText: 'Seleziona...',
		});

		$.fn.dataTable.moment('DD/MM/YYYY - HH:mm');

		tabellaOrdini = $('#tabellaOrdini').DataTable({
			order: [5, 'desc'],
			aLengthMenu: [
				[10, 20, 30, 50, 100, -1],
				[10, 20, 30, 50, 100, 'Tutti'],
			],

			iDisplayLength: 10,

			columns: [
				{ data: 'IdProduzioneERif' },
				{ data: 'lp_Descrizione' },
				{ data: 'prd_Descrizione' },
				{ data: 'op_QtaRichiesta' },
				{ data: 'um_Sigla' },
				{ data: 'DataOraProgrammazione' },
				{ data: 'DataOraFineTeorica' },
				{ data: 'op_Lotto' },
				{ data: 'op_Priorita' },
				{ data: 'so_Descrizione' },
				{ data: 'risorseSelezionate' },
				{ data: 'op_NoteProduzione' },
				{ data: 'azioniCaricaScarica' },
				{ data: 'comandi' },
				{ data: 'azioni' },
				{ data: 'op_IdProduzione' },
				{ data: 'op_LineaProduzione' },
				{ data: 'op_ProgressivoParziale' }

			],
			columnDefs: [
				{
					targets: [12, 13, 14],
					className: 'center-bolded',
				},
				{
					visible: false,
					targets: [1, 4, 6, 15, 16, 17],
				},
			],

			language: linguaItaliana,
			drawCallback: function (settings) {
				var api = this.api();
				api.rows().every(function (rowIdx, tableLoop, rowLoop) {
					var idRiga = this.node();
					var data = this.data();
					var statoLinea = this.data()['so_Descrizione'];

					if (statoLinea == 'OK') {
						$(idRiga).css('background-color', 'rgba(0, 255, 0, 0.8)');
					} else if (statoLinea == 'CHIUSO') {
						$(idRiga).css('background-color', 'rgba(101, 108, 108, 0.2)');
					} else if (statoLinea == 'ATTIVO') {
						$(idRiga).css('background-color', 'rgba(102, 204, 255, 0.7)');
					} else if (statoLinea == 'CARICATO') {
						$(idRiga).css('background-color', 'rgba(102, 204, 255, 0.7)');
					} else if (statoLinea == 'MANUTENZIONE') {
						$(idRiga).css('background-color', 'rgba(102, 0, 204, 0.5)');
					} else if (statoLinea == 'MEMO') {
						$(idRiga).css('background-color', 'rgba(252, 252, 252, 0.7)');
					}
				});
			},
			buttons: [
				{
					extend: 'excel',
					text: 'EXCEL',
					className: 'btn-success',
					filename: 'ElencoCommesse_' + today.format('YYYYMMDD'),
					title: 'ELENCO COMMESSE AL ' + today.format('DD/MM/YYYY'),
					exportOptions: {
						columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
					},
				},
				{
					extend: 'pdf',
					text: 'PDF',
					className: 'btn-danger',
					filename: 'ElencoCommesse_' + today.format('YYYYMMDD'),
					title: 'ELENCO COMMESSE AL ' + today.format('DD/MM/YYYY'),
					orientation: 'landscape',
					download: 'open',
					exportOptions: {
						columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
					},
				},
			],
			dom:
				"<'row'<'col-sm-12 col-md-6 d-flex justify-content-left align-items-center'lB><'col-sm-12 col-md-6'f>>" +
				"<'row'<'col-sm-12'tr>>" +
				"<'row'<'col-sm-12 col-md-6'i><'col-sm-12 col-md-6'p>>",
		});

		// GESTIONE COMMESSE SEMPLIFICATA: VISUALIZZA TABELLA
		visualizzaElencoOrdini(g_idStatoOrdine);
	});

	// GESTIONE COMMESSE SEMPLIFICATA: ESEGUO CON CADENZA REGOLARE (OGNI 10 SECONDI) IL RELOAD DELLA TABELLA COMMESSE PER MOSTRARE DATI AGGIORNATI
	setInterval(function () {
		visualizzaElencoOrdini(g_idStatoOrdine);
	}, 10000);

	// GESTIONE COMMESSE SEMPLIFICATA: VISUALIZZAZIONE ELENCO COMMESSE IN BASE ALLO STATO SELEZIONATO NELLA COMBO
	$('#filtro-ordini').on('change', function () {
		g_idStatoOrdine = $(this).val();
		visualizzaElencoOrdini(g_idStatoOrdine);
	});

	$('body').on('click', '.carica-ordine-produzione', function () {
		var datiRiga = tabellaOrdini.row($(this).parents('tr')).data();
		var idProduzione = datiRiga.op_IdProduzione;
		var idLinea = datiRiga.op_LineaProduzione;
		// Verifico lo stato attuale delle risorse coinvolte dalla produzione selezionata per verificare la possibilità di eseguire un caricamento simultaneo su tutte quelle disponibili
		$.post('gestioneordinisemplificata.php', {
			azione: 'recupera-risorse',
			idProduzione: idProduzione,
		}).done(function (data) {
			try {
				if (data != 'NO_RIS') {
					var elencoRisorse = JSON.parse(data);
					caricamentoOrdineMacchinaMultipla(elencoRisorse, idProduzione);
				} else {
					swal({
						title: 'ATTENZIONE!',
						text: 'Nessuna macchina definita nel sistema.',
						icon: 'warning',
					});
				}
			} catch (error) {
				console.error(error);
				console.log(data);
				swal({
					title: 'ERRORE!',
					text: 'Errore nel reperimento delle macchine.',
					icon: 'error',
				});
			}
		});
	});

	$('body').on('click', '.scarica-ordine-produzione', function () {
		var datiRiga = tabellaOrdini.row($(this).parents('tr')).data();
		var idProduzione = datiRiga.op_IdProduzione;
		var idLinea = datiRiga.op_LineaProduzione;
		// Verifico lo stato attuale delle risorse coinvolte dalla produzione selezionata per verificare la possibilità di eseguire un caricamento simultaneo su tutte quelle disponibili
		$.post('gestioneordinisemplificata.php', {
			azione: 'recupera-risorse',
			idProduzione: idProduzione,
		}).done(function (data) {
			try {
				if (data != 'NO_RIS') {
					var elencoRisorse = JSON.parse(data);
					scaricamentoOrdineMacchinaMultipla(elencoRisorse, idProduzione);
				} else {
					swal({
						title: 'ATTENZIONE!',
						text: 'Nessuna macchina definita nel sistema.',
						icon: 'warning',
					});
				}
			} catch (error) {
				console.error(error);
				console.log(data);
				swal({
					title: 'ERRORE!',
					text: 'Errore nel reperimento delle macchine.',
					icon: 'error',
				});
			}
		});
	});

	$('body').on('click', '.cancella-commessa', function () {
		var datiRiga = tabellaOrdini.row($(this).parents('tr')).data();
		var idProduzione = datiRiga.op_IdProduzione;

		$.post(url, { azione: 'cancella-ordine-produzione', id: idProduzione }).done(function (data) {
			if (data == 'OK') {
				visualizzaElencoOrdini(g_idStatoOrdine);
			} else {
				swal({
					title: 'ERRORE!',
					text: "Errore nell'eliminazione della commessa.",
					icon: 'error',
				});
			}
		});
	});

	$('#nuovo-ordine-produzione').click(function (e) {
		e.preventDefault();
		$('#form-ordine-produzione').removeClass('was-validated');
		$('#form-ordine-produzione')[0].reset();
		$('#form-ordine-produzione #op_DataProduzione').val(today.format('YYYY-MM-DD'));
		$('#form-ordine-produzione #op_OraProduzione').val(today.format('HH:mm'));
		$('#form-ordine-produzione .selectpicker').val('');
		$('#form-ordine-produzione #op_Stato').val(2);
		$('#form-ordine-produzione .selectpicker').selectpicker('refresh');
		$('#op_IdProduzione').prop('readonly', false);
		$('#op_Prodotto').prop('disabled', false);
		$('#form-ordine-produzione #azione').val('nuovo');
		$.post(url, { azione: 'genera-id-produzione' }).done(function (data) {
			try {
				var dati = JSON.parse(data);
				$('#form-ordine-produzione #op_IdProduzione').val(dati.codice);
				$('#form-ordine-produzione #op_Lotto').val(dati.codice);
			} catch (error) {
				swal({
					title: 'ATTENZIONE!',
					text: 'Errore nella generazione del nuovo codice.',
					icon: 'warning',
				});
			}
		});
		
		$.post("gestioneordinisemplificata.php", { azione: "recupera-linee" })
		.done(function(idLinea) {

			// Se ho una linea soltanto, la preparo preselezionata nella select
			if((idLinea != "NO_LIN") && (idLinea != "MULT_LIN")) {
				$('#form-ordine-produzione #op_LineaProduzione').val(idLinea);
				$('#form-ordine-produzione #op_LineaProduzione').selectpicker('refresh');
			}
		});
		
		$('#modal-ordine-produzione').modal('show');
	});

	$('#op_Prodotto, #op_LineaProduzione').on('change', function () {
		var idProdotto = $('#op_Prodotto').val();
		var idLineaProduzione = $('#op_LineaProduzione').val();

		// Recupero l'unità di misura relativa al prodotto selezionato
		$.post('utilities.php', {
			azione: 'recupera-udm',
			codice: idProdotto,
		}).done(function (data) {
			$('#form-ordine-produzione #op_Udm').val(data);
			$('#form-ordine-produzione #op_Udm').selectpicker('refresh');
			var short = $('#form-ordine-produzione #op_Udm option:selected')
				.text()
				.split('(')[1]
				.split(')')[0];
			$('.udm-vel').text('[' + short + '/h]');
		});

		// Recupero la velocità teorica della linea, in relazione al prodotto selezionato
		$.post(url, {
			azione: 'recupera-velocita-teorica',
			idProdotto: idProdotto,
			idLineaProduzione: idLineaProduzione,
		}).done(function (data) {

			$('#form-ordine-produzione #vel_VelocitaTeoricaLinea').val(data);
			$('#form-ordine-produzione #vel_VelocitaTeoricaLinea').selectpicker('refresh');
		});
	});

	$('#op_Udm').on('change', function () {
		var short = $('#form-ordine-produzione #op_Udm option:selected')
			.text()
			.split('(')[1]
			.split(')')[0];
		$('.udm-vel').text('[' + short + '/h]');
	});

	// GESTIONE COMMESSE SEMPLIFICATA: SALVATAGGIO DA POPUP MODIFICA/INSERIMENTO
	$('body').on('click', '#salva-ordine-produzione', function (e) {
		e.preventDefault();
		$('#form-ordine-produzione').addClass('was-validated');
		var azione = $('#form-ordine-produzione #azione').val();
		// se ho anche solo un errore mi fermo qui
		if (!$('#form-ordine-produzione')[0].checkValidity()) {
			swal({
				title: 'ATTENZIONE!',
				text: 'Compilare tutti i campi richiesti.',
				icon: 'warning',
			});
		} // nessun errore, posso continuare
		else {
			var lottoInserito = $('#form-ordine-produzione #op_Lotto').val();
			var idProduzione = $('#form-ordine-produzione #op_IdProduzione').val();

			// salvo i dati
			$.post('gestioneordinisemplificata.php', {
				azione: 'verifica-valori-ripetuti',
				lottoInserito: lottoInserito,
				idProduzione: idProduzione,
			}).done(function (data) {
				if (data == 'OK' || azione == 'modifica') {
					// Riabilito i campi per poter serializzare i dati
					$('#form-ordine-produzione #op_IdProduzione').prop('disabled', false);
					$('#form-ordine-produzione #op_Prodotto').prop('disabled', false);

					salvaOrdine();
				} else if (data == 'RIPETUTI') {
					swal({
						title: 'ATTENZIONE!',
						text: 'Lotto inserito già utilizzato, desideri proseguire comunque?.',
						icon: 'warning',
						buttons: {
							cancel: {
								text: 'ANNULLA',
								value: false,
								visible: true,
								className: 'btn btn-secondary',
							},
							confirm: {
								text: 'SÌ, PROSEGUI',
								value: true,
								className: 'btn btn-danger',
							},
						},
					}).then((procedi) => {
						if (procedi) {
							salvaOrdine();
						}
					});
				} else {
					console.error(error);
					console.log(data);
					swal({
						title: 'ERRORE!',
						text: 'Errore nella verifica del lotto.',
						icon: 'error',
					});
				}
			});
		}

		return false;
	});

	$('body').on('click', '.gestisci-commessa', function () {
		var datiRiga = tabellaOrdini.row($(this).parents('tr')).data();
		var idProduzione = datiRiga.op_IdProduzione;
		$('#modal-ordine-produzione-label').text('MODIFICA COMMESSA');
		$('#form-ordine-produzione')[0].reset();
		$('#form-ordine-produzione').removeClass('was-validated');
		$('#op_IdProduzione').prop('readonly', true);
		$('#op_Prodotto').prop('disabled', true);
		$('#form-ordine-produzione #azione').val('modifica');
		$.post('gestioneordinisemplificata.php', {
			azione: 'recupera-ordine',
			codice: idProduzione,
		}).done(function (data) {
			try {
				var dati = JSON.parse(data);

				// recupero valori per input-text
				for (var chiave in dati) {
					if (dati.hasOwnProperty(chiave)) {
						$('#form-ordine-produzione #' + chiave).val(dati[chiave]);
					}
				}

				var selected = dati['op_RisorseSelezionate'].split(',');
				$("#op_RisorseSelezionate").val(selected);
			
				$('#form-ordine-produzione .selectpicker').selectpicker('refresh');
				$('#form-ordine-produzione #op_IdOrdine_Aux').val(dati['op_IdProduzione']);
				$('#modal-ordine-produzione').modal('show');
			} catch (error) {
				console.error(error);
				console.log(data);
				swal({
					title: 'ERRORE!',
					text: "Errore nel recupero dell'ordine.",
					icon: 'error',
				});
			}
		});

		return false;
	});

	// RIPRESA COMMESSA DI PRODUZIONE: RIPRENDI ESECUZIONE DI UN COMMESSA GIA' ESEGUITO
	$('body').on('click', '#riprendi-ordine-parziale', function (e) {
		e.preventDefault();
		today = moment();
		var datiRiga = tabellaOrdini.row($(this).parents('tr')).data();
		var idProduzione = datiRiga.op_IdProduzione;
		var progressivoParziale = datiRiga.op_ProgressivoParziale;
		$('#form-riprendi-ordine').removeClass('was-validated');
		$('#form-riprendi-ordine')[0].reset();

		swal({
			title: 'ATTENZIONE!',
			text: "Desideri RIPRENDERE l'ordine " + idProduzione + '?',
			icon: 'warning',
			buttons: {
				cancel: {
					text: 'ANNULLA',
					value: false,
					visible: true,
					className: 'btn btn-secondary',
				},
				confirm: {
					text: 'CONFERMA',
					value: true,
					className: 'btn btn-primary',
				},
			},
		}).then((procedi) => {
			if (procedi) {
				// Recupero le informazioni relative all'ordine selezionat e da riprendere
				$.post('gestioneordinisemplificata.php', {
					azione: 'recupera-ordine-ripreso',
					idProduzione: idProduzione,
					progressivoParziale: progressivoParziale,
				}).done(function (data) {

					try {
						var dati = JSON.parse(data);

						// recupero valori per input-text
						for (var chiave in dati) {
							if (dati.hasOwnProperty(chiave)) {
								$('#form-riprendi-ordine #' + chiave).val(dati[chiave]);
							}
							$('.unita_misura').html(' [' + dati['rop_UdmSigla'] + ']');
						}
						
						var selected = dati['op_RisorseSelezionate'].split(',');
						$("#form-riprendi-ordine #op_RisorseSelezionate").val(selected);
						$('#form-riprendi-ordine .selectpicker').selectpicker('refresh');
				
						$('#form-riprendi-ordine #op_DataOrdine').val(today.format('YYYY-MM-DD'));
						$('#form-riprendi-ordine #op_OraOrdine').val(today.format('HH:mm'));
						$('#form-riprendi-ordine #op_DataProduzione').val(today.format('YYYY-MM-DD'));
						$('#form-riprendi-ordine #op_OraProduzione').val(today.format('HH:mm'));
						$('#modal-riprendi-ordine').modal('show');
					} catch (error) {
						console.error(error);
						console.log(data);
						swal({
							title: 'ERRORE!',
							text: "Errore nel recupero dell'ordine.",
							icon: 'error',
						});
					}
				});
			} else {
				swal.close();
			}
		});
		return false;
	});

	// RIPRESA COMMESSA DI PRODUZIONE: CREO UNA COPIA DELL'COMMESSA IN OGGETTO
	$('#salva-ordine-ripreso').click(function (e) {
		e.preventDefault();
		$('#form-riprendi-ordine').addClass('was-validated');
		if (!$('#form-riprendi-ordine')[0].checkValidity()) {
			swal({
				title: 'ATTENZIONE!',
				text: 'Compilare tutti i campi richiesti.',
				icon: 'warning',
			});
			return;
		}

		// Eseguo memorizzazione del nuovo ordine
		$.post('gestioneordinisemplificata.php', {
			azione: 'salva-ordine-ripreso',
			data: $('#form-riprendi-ordine').serialize(), op_risorseSelezionate: $('#form-riprendi-ordine #op_RisorseSelezionate').val()
		}).done(function (data) {

			// Se l'inserimento è andato a buon fine, procedo a inserire la relativa distinta macchine (INSERIMENTO IMPLICITO)
			if (data == 'OK') {
				$('#modal-riprendi-ordine').modal('hide');
				visualizzaElencoOrdini(g_idStatoOrdine);
			} // restituisco un errore senza chiudere la modale
			else {
				console.log(data);
				swal({
					title: 'ERRORE!',
					text: "Errore nel salvataggio dell'ordine.",
					icon: 'error',
				});
			}
		});

		return false;
	});

	$('body').on('click', '.termina-ordine-produzione', function () {
		var riga = tabellaOrdini.row($(this).parents('tr')).data();
		$.post('gestionecommesse.php', {
			azione: 'termina-ordine',
			idProduzione: riga.op_IdProduzione,
		}).done((data) => {

			if (data == 'OK') {
				visualizzaElencoOrdini(g_idStatoOrdine);
				swal({
					title: 'OPERAZIONE EFFETTUATA!',
					icon: 'success',
				});
			} else {
				console.log(data);
				swal({
					title: 'ERRORE!',
					icon: 'error',
				});
			}
		});
	});

	$('body').on('click', '.avvia-ordine-produzione', function () {
		var riga = tabellaOrdini.row($(this).parents('tr')).data();
		$.post('gestionecommesse.php', {
			azione: 'avvia-ordine',
			idProduzione: riga.op_IdProduzione,
		}).done((data) => {
			if (data == 'OK') {
				visualizzaElencoOrdini(g_idStatoOrdine);
				swal({
					title: 'OPERAZIONE EFFETTUATA!',
					icon: 'success',
				});
			} else {
				console.log(data);
				swal({
					title: 'ERRORE!',
					icon: 'error',
				});
			}
		});
	});
})(jQuery);
