(function ($) {
	'use strict';

	var tabellaDatiOrdiniProduzione;
	var tabellaDatiDistintaRisorse;
	var tabellaDatiDistintaComponenti;
	var tabellaDatiDistintaConsumi;

	var g_idStatoOrdine = $('#filtro-ordini').val();

	// FUNZIONE: RECUPERA ELENCO COMMESSE
	function visualizzaElencoOrdini(idStatoOrdine) {
		tabellaDatiOrdiniProduzione.ajax.url(
			'gestionecommesse.php?azione=mostra&idStatoOrdine=' + idStatoOrdine
		);
		tabellaDatiOrdiniProduzione.ajax.reload();
	}

	function mostraDistintaConsumi(idProduzione) {
		// ricarico la datatable per vedere le modifiche
		tabellaDatiDistintaConsumi.ajax.url(
			'gestionecommesse.php?azione=mostra-distinta-consumi&idProduzione=' +
				idProduzione
		);
		tabellaDatiDistintaConsumi.ajax.reload();
	}

	var modOn;

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

	$(function () {
		//VALORE DEFAULT SELECTPICKER
		$('.selectpicker').selectpicker({
			noneSelectedText: 'Seleziona...',
		});

		$.fn.dataTable.moment('DD/MM/YYYY - HH:mm');

		// GESTIONE COMMESSE: VISUALIZZA DATATABLE COMMESSE
		tabellaDatiOrdiniProduzione = $('#tabellaDati-ordini').DataTable({
			aLengthMenu: [
				[5, 10, 20, 50, 100, -1],
				[5, 10, 20, 50, 100, 'Tutti'],
			],
			iDisplayLength: 5,
			order: [
				[5, 'desc'],
				[7, 'asc'],
			],

			columns: [
				{ data: 'IdProduzione' },
				{ data: 'LineaProduzione' },
				{ data: 'Prodotto' },
				{ data: 'QtaRichiesta' },
				{ data: 'QtaDaProdurre' },
				{ data: 'DataOraProgrammazione' },
				{ data: 'DataOraFinePrevista' },
				{ data: 'Lotto' },
				{ data: 'Priorita' },
				{ data: 'StatoOrdine' },
				{ data: 'azioni' },
			],
			columnDefs: [
				{
					targets: [10],
					className: 'center-bolded',
				},
				{
					width: '15%',
					targets: [1, 2],
				},
				{
					width: '10%',
					targets: [0, 5, 6],
				},
				{
					width: '5%',
					targets: [7, 8, 9, 10],
				},
				{
					width: '7%',
					targets: [4, 3],
				},
			],

			language: linguaItaliana,

			drawCallback: function (settings) {
				var api = this.api();
				api.rows().every(function (rowIdx, tableLoop, rowLoop) {
					var idRiga = this.node();
					var data = this.data();
					var statoLinea = this.data()['StatoOrdine'];

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
						$(idRiga).css('background-color', '#ffffff');
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
						columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
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
						columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
					},
				},
			],
			dom:
				"<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
				"<'row'<'col-sm-12'tr>>" +
				"<'row'<'col-sm-12 col-md-6 d-flex justify-content-left align-items-center'iB><'col-sm-12 col-md-6'p>>",
		});

		// GESTIONE COMMESSE: VISUALIZZA DATATABLE DISTINTA RISORSE COMMESSA
		tabellaDatiDistintaRisorse = $('#tabellaDati-distinta-risorse').DataTable(
			{
				aLengthMenu: [
					[8, 16, 24, 100, -1],
					[8, 16, 24, 100, 'Tutti'],
				],
				iDisplayLength: 8,
				order: [7, 'asc'],

				columns: [
					{ data: 'IdRisorsa' },
					{ data: 'Descrizione' },
					{ data: 'Ricetta' },
					{ data: 'NoteIniziali' },
					{ data: 'RegistraMisure' },
					{ data: 'FlagUltima' },
					{ data: 'azioni' },
					{ data: 'Ordinamento' },
				],
				columnDefs: [
					{
						visible: false,
						targets: [7],
					},
					{
						width: '10%',
						targets: [0, 1, 4, 5],
					},
					{
						className: 'center-bolded',
						width: '5%',
						targets: [6],
					},
				],
				language: linguaItaliana,
				searching: false,
				autoWidth: false,
				dom: "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4'l><'col-sm-8'p>>",
			}
		);

		// GESTIONE COMMESSE: VISUALIZZA DATATABLE DISTINTA COMPONENTI COMMESSA
		tabellaDatiDistintaComponenti = $(
			'#tabellaDati-distinta-componenti'
		).DataTable({
			aLengthMenu: [
				[8, 16, 24, 100, -1],
				[8, 16, 24, 100, 'Tutti'],
			],
			iDisplayLength: 8,
			order: [1, 'asc'],

			columns: [
				{ data: 'IdProdotto' },
				{ data: 'Descrizione' },
				{ data: 'UdmComponente' },
				{ data: 'FattoreMoltiplicativo' },
				{ data: 'PezziConfezione' },
				{ data: 'Fabbisogno' },
				{ data: 'azioni' },
			],
			columnDefs: [
				{
					targets: [6],
					width: '5%',
					className: 'center-bolded',
				},
			],
			autoWidth: false,
			language: linguaItaliana,
			searching: false,
			dom: "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4'l><'col-sm-8'p>>",
		});

		tabellaDatiDistintaConsumi = $('#tabellaDati-distinta-consumi').DataTable(
			{
				aLengthMenu: [
					[8, 16, 24, 100, -1],
					[8, 16, 24, 100, 'Tutti'],
				],

				iDisplayLength: 8,
				columns: [
					{ data: 'Macchina' },
					{ data: 'Consumo' },
					{ data: 'Udm' },
					{ data: 'TipoCalcolo' },
					{ data: 'ConsumoIpotetico' },
					{ data: 'azioni' },
				],
				columnDefs: [
					{
						targets: [5],
						className: 'center-bolded',
						width: '5%',
					},
				],
				autoWidth: false,
				language: linguaItaliana,
				dom: "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4'l><'col-sm-8'p>>",
			}
		);

		// GESTIONE COMMESSE - POST REFRESH: ESEGUO CON CADENZA REGOLARE (OGNI 5 SECONDI) IL RELOAD DELLA TABELLA E DEL GANTT PER MOSTRARE DATI AGGIORNATI
		setInterval(function () {
			// Se mi trovo nel pannello di elenco ordini
			if ($('#collapseOne').hasClass('show')) {
				// Ricarico il grafico Gantt e la tabella ordini
				reloadDataGantt();
				visualizzaElencoOrdini($('#filtro-ordini').val());
			}
		}, 10000);

		// GESTIONE COMMESSE - POST REFRESH: PRIMA DI 'UNLOAD PAGINA', SE MODIFICA E' IN CORSO, MOSTRO MESSAGGIO
		$(window).bind('beforeunload', function (e) {
			if (modOn) {
				return '';
			}
		});

		// GESTIONE COMMESSE - POST REFRESH: SU 'UNLOAD PAGINA' SBLOCCO L'COMMESSA CHE ERA BLOCCATA E IN FASE DI MODIFICA
		$(window).on('unload', function () {
			var idOrdineProduzione = $('#op_IdProduzione').val();

			// You can send an ArrayBufferView, Blob, DOMString or FormData
			// Since Content-Type of FormData is multipart/form-data, the Content-Type of the HTTP request will also be multipart/form-data
			var fd = new FormData();
			fd.append('azione', 'sblocca-ordine');
			fd.append('idProduzione', idOrdineProduzione);

			navigator.sendBeacon('gestionecommesse.php', fd);

			modOn = false;
		});

		// GESTIONE COMMESSE - POST REFRESH: IMPOSTAZIONE VISUALIZZAZIONE DI DEFAULT DEI PULSANTI
		$('#nuova-commessa').prop('hidden', false);
		$('#annulla-modifica-ordine').prop('hidden', true);
		$('#conferma-modifica-ordine').prop('hidden', true);
		$('#aggiungi-risorsa-ordine').prop('hidden', true);
		$('#aggiungi-componente-ordine').prop('hidden', true);
		$('#aggiungi-consumo-ordine').prop('hidden', true);
		//$('#conferma-modifica-ordine').prop('disabled', false);

		// GESTIONE COMMESSE POST - REFRESH: OPERAZIONI SU SELEZIONE DEL TAB
		$('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
			var target = $(e.target).attr('id'); // activated tab
			if (target == 'tab-risorse') {
				$('#aggiungi-risorsa-ordine').prop('hidden', false);
				$('#aggiungi-componente-ordine').prop('hidden', true);
				$('#aggiungi-consumo-ordine').prop('hidden', true);
			} else if (target == 'tab-componenti') {
				$('#aggiungi-risorsa-ordine').prop('hidden', true);
				$('#aggiungi-componente-ordine').prop('hidden', false);
				$('#aggiungi-consumo-ordine').prop('hidden', true);
			} else if (target == 'tab-consumi') {
				$('#aggiungi-risorsa-ordine').prop('hidden', true);
				$('#aggiungi-componente-ordine').prop('hidden', true);
				$('#aggiungi-consumo-ordine').prop('hidden', false);
			}
		});

		// GESTIONE COMMESSE: VISUALIZZAZIONE TABELLA
		visualizzaElencoOrdini(g_idStatoOrdine);
	});

	// --------------------------------------------- OPERAZIONI SU DATI COMMESSA  ---------------------------------------------

	//  GESTIONE COMMESSE: AL CAMBIO VALORE SU SELECT DI FILTRO (STATO ORDINI) AGGIORNO ORDINI VISUALIZZATI
	$('#filtro-ordini').on('change', function () {
		g_idStatoOrdine = $(this).val();
		visualizzaElencoOrdini(g_idStatoOrdine);
	});

	// GESTIONE COMMESSE: AL CAMBIO VALORE SU SELECT DI FILTRO (VEL. TEORICA, QTA DA PRODURRE, DATA PRODUZIONE) AGGIORNO ORDINI VISUALIZZATI
	$('#vel_VelocitaTeoricaLinea, #op_QtaDaProdurre, #op_DataProduzione').on(
		'keyup change',
		function () {
			var tempVelLinea = $('#vel_VelocitaTeoricaLinea').val();
			var qtaDaProdurre = $('#op_QtaDaProdurre').val();
			var dataOraProduzione = $('#op_DataProduzione').val();

			if (
				tempVelLinea != '' &&
				tempVelLinea != 0 &&
				qtaDaProdurre != '' &&
				dataOraProduzione != ''
			) {
				var qtaDaProdurreFloat = parseFloat(qtaDaProdurre);
				//Visualizzazione dati ordine produzione selezionato
				$.post('gestionecommesse.php', {
					azione: 'aggiorna-data-fine',
					qtaDaProdurre: qtaDaProdurreFloat,
					velocitaTeoricaLinea: tempVelLinea,
					dataProduzione: dataOraProduzione,
				}).done(function (data) {
					$('#op_DataFine').val(data);
				});
			}
		}
	);

	// GESTIONE COMMESSE: GESTISCI COMMESSA E VISUALIZZANE I DATI
	$('body').on('click', '.gestisci-commessa', function (e) {
		e.preventDefault();

		var idOrdineProduzione = $(this).data('id-ordine-produzione');
		var idProdotto;
		var idLineaProduzione;
		var velTeorica;
		var qtaDaProdurre;

		// Rimuovo segnalazione errore su campi obbligatori
		$('#form-dati-ordine input').removeClass('errore');
		$('#form-nuovo-componente input').removeClass('errore');
		$('#form-nuova-risorsa input').removeClass('errore');

		//Visualizzazione dati ordine produzione selezionato
		$.ajaxSetup({ async: false });
		$.post('gestionecommesse.php', {
			azione: 'gestisci-ordine-produzione',
			idProduzione: idOrdineProduzione,
		}).done(function (dataGestioneOrdine) {
			// Verifico se la commessa è già aperta in gestione da altri utenti
			if (dataGestioneOrdine != 'BLOCCATO') {
				try {
					var dati = JSON.parse(dataGestioneOrdine);

					modOn = true;

					// Valorizzo campi standard
					for (var chiave in dati) {
						if (dati.hasOwnProperty(chiave)) {
							$('#blocco-modifica')
								.find('input#' + chiave)
								.val(dati[chiave]);
							$('#blocco-modifica select#' + chiave).val(dati[chiave]);
							$('#blocco-modifica select#' + chiave).selectpicker(
								'refresh'
							);
						}
					}

					idProdotto = dati['op_Prodotto'];

					// Valorizzo opportunamente il campo 'stato ordine'
					$('#op_Stato').val(dati['op_Stato']);
					$('#op_Stato').selectpicker('refresh');

					// Valorizzo opportunamente il campo 'quantità da produrre'
					if (dati['op_QtaDaProdurre'] === null) {
						$('#op_QtaDaProdurre').val(dati['op_QtaRichiesta']);
					} else {
						$('#op_QtaDaProdurre').val(dati['op_QtaDaProdurre']);
					}
					qtaDaProdurre = $('#op_QtaDaProdurre').val();

					// Valorizzo opportunamente il campo 'priorità'
					$('#op_Priorita').val(1);

					// Valorizzo opportunamente il campo 'unità di misura'
					$('.udm').text('[' + dati['um_Sigla'] + ']');
					$('.udm-vel').text('[' + dati['um_Sigla'] + '/h]');

					// Valorizzo opportunamente il campo data/ora di programmazione
					var dataOraCompilazione =
						dati['op_DataOrdine'] + 'T' + dati['op_OraOrdine'];
					$('#op_DataOrdine').val(dataOraCompilazione);

					// Valorizzo opportunamente il campo data/ora di programmazione
					var dataOraProgrammazione =
						dati['op_DataProduzione'] + 'T' + dati['op_OraProduzione'];
					$('#op_DataProduzione').val(dataOraProgrammazione);

					// Valorizzo opportunamente il campo data/ora di fine teorica
					var dataOraFineTeorica =
						dati['op_DataFineTeorica'] + 'T' + dati['op_OraFineTeorica'];
					$('#op_DataFine').val(dataOraFineTeorica);

					// Valorizzo opportunamento il campo 'linea di produzione'
					if (
						dati['op_LineaProduzione'] != '' &&
						dati['op_LineaProduzione'] != null
					) {
						$('#op_LineeProduzione').val(dati['op_LineaProduzione']);
						$('#op_LineeProduzione').selectpicker('refresh');
						idLineaProduzione = dati['op_LineaProduzione'];
					} else {
						//Visualizzazione dati ordine produzione selezionato
						$.ajaxSetup({ async: false });
						$.post('gestionecommesse.php', {
							azione: 'verifica-unica-linea',
						}).done(function (data) {
							if (data != 'MULTIPLA') {
								$('#op_LineeProduzione').val(data);
								idLineaProduzione = data;
							} else {
								$('#op_LineeProduzione').val('default');
								idLineaProduzione = $('#op_LineeProduzione').val();
							}
							$('#op_LineeProduzione').selectpicker('refresh');
						});
					}

					// Valorizzo il campo 'velocità teorica' recuperandolo dall'apposita tabella
					$.ajaxSetup({ async: false });
					$.post('gestionecommesse.php', {
						azione: 'recupera-velocita-teorica',
						idProdotto: idProdotto,
						idLineaProduzione: idLineaProduzione,
					}).done(function (dataVelTeoriche) {
						if (dataVelTeoriche == 'NO_ROWS') {
							$('#vel_VelocitaTeoricaLinea').val(0);
							velTeorica = 0;
						} else {
							$('#vel_VelocitaTeoricaLinea').val(
								parseFloat(dataVelTeoriche)
							);
							velTeorica = dataVelTeoriche;
						}
					});

					// Aggiorno ora teorica di fine, in base agli altri dati impostati
					if (
						velTeorica != '' &&
						velTeorica != 0 &&
						$('#op_QtaDaProdurre').val() != '' &&
						dataOraProgrammazione != ''
					) {
						//Visualizzazione dati ordine produzione selezionato
						$.ajaxSetup({ async: false });
						$.post('gestionecommesse.php', {
							azione: 'aggiorna-data-fine',
							qtaDaProdurre: qtaDaProdurre,
							velocitaTeoricaLinea: velTeorica,
							dataProduzione: dataOraProgrammazione,
						}).done(function (data) {
							$('#op_DataFine').val(data);
						});
					}

					// Inizializzazione delle tabelle di lavoro con le distinte risorse e prodotti per l'ordine di produzione selezionato
					$.ajaxSetup({ async: false });
					$.post('gestionecommesse.php', {
						azione: 'inizializza-distinte',
						idProduzione: idOrdineProduzione,
						idProdotto: idProdotto,
						idLineaProduzione: idLineaProduzione,
					}).done(function (dataInizializzazioneDistinte) {
						if (dataInizializzazioneDistinte == 'OK') {
							//Visualizzazione distinta risorse per l'ordine di produzione selezionato
							$.post('gestionecommesse.php', {
								azione: 'mostra-distinta-risorse',
								idProduzione: idOrdineProduzione,
							}).done(function (dataDistintaRisorse) {
								$('#tabellaDati-distinta-risorse')
									.dataTable()
									.fnClearTable();

								if (dataDistintaRisorse != 'NO_ROWS') {
									$('#tabellaDati-distinta-risorse')
										.dataTable()
										.fnAddData(JSON.parse(dataDistintaRisorse));
								}
							});

							//Visualizzazione distinta componenti per l'ordine di produzione selezionato
							$.post('gestionecommesse.php', {
								azione: 'mostra-distinta-componenti',
								idProduzione: idOrdineProduzione,
								qtaDaProdurre: qtaDaProdurre,
							}).done(function (dataDistintaComponenti) {
								$('#tabellaDati-distinta-componenti')
									.dataTable()
									.fnClearTable();

								if (dataDistintaComponenti != 'NO_ROWS') {
									$('#tabellaDati-distinta-componenti')
										.dataTable()
										.fnAddData(JSON.parse(dataDistintaComponenti));
								}
							});

							mostraDistintaConsumi(idOrdineProduzione);

							$('.multi-collapse').collapse('toggle');

							$('#nuova-commessa').prop('hidden', true);
							$('#annulla-modifica-ordine').prop('hidden', false);
							$('#conferma-modifica-ordine').prop('hidden', false);

							// impostazione visualizzazione pulsanti di aggiunta, in base al tab selezionato
							if (
								$('ul#tab-distinte a.active').attr('id') == 'tab-risorse'
							) {
								$('#aggiungi-risorsa-ordine').prop('hidden', false);
								$('#aggiungi-componente-ordine').prop('hidden', true);
								$('#aggiungi-consumo-ordine').prop('hidden', true);
							} else if (
								$('ul#tab-distinte a.active').attr('id') ==
								'tab-componenti'
							) {
								$('#aggiungi-risorsa-ordine').prop('hidden', true);
								$('#aggiungi-componente-ordine').prop('hidden', false);
								$('#aggiungi-consumo-ordine').prop('hidden', true);
							} else if (
								$('ul#tab-distinte a.active').attr('id') == 'tab-consumi'
							) {
								$('#aggiungi-risorsa-ordine').prop('hidden', true);
								$('#aggiungi-componente-ordine').prop('hidden', true);
								$('#aggiungi-consumo-ordine').prop('hidden', false);
							}
						} else {
							console.log(dataInizializzazioneDistinte);
							swal({
								title: 'ATTENZIONE!',
								text: 'Errore in inizializzazione distinte.',
								icon: 'warning',
								button: 'Ho capito',
								closeModal: true,
							});
						}
					});

					// Verifico se ordine è già stato caricato (STATO = 3), nel qual caso inibisco le modifiche
					if (dati['op_Stato'] == 3) {
						$('#form-dati-ordine input').prop('disabled', true);
						$('.btn-gestione-ordine').prop('disabled', true);

						swal({
							title: 'ATTENZIONE!',
							text: 'Ordine già caricato su almeno una macchina: impossibile eseguire modifiche.',
							icon: 'warning',
							button: 'Ho capito',
							closeModal: true,
						});
					} else {
						// Se ordine non è ancora stato caricato, posso procedere a gestirlo e di conseguenza a bloccarne modifiche da parte di altri
						$('#form-dati-ordine input').prop('disabled', false);
						$('.btn-gestione-ordine').prop('disabled', false);

						// Se la commessa non è già in gestione, la blocco prima di procedere a gestirla
						$.post('gestionecommesse.php', {
							azione: 'blocca-ordine',
							idProduzione: idOrdineProduzione,
						}).done(function (dataBloccoOrdine) {
							// Se il blocco della commessa è andato a buon fine, procede a recuperare i relativi dati
							if (dataBloccoOrdine != 'OK') {
								swal({
									title: 'ATTENZIONE!',
									text: 'Errore in gestione ordini - Blocco ordine non possibile.',
									icon: 'warning',
									button: 'Ho capito',
									closeModal: true,
								});
							}
						});
					}
				} catch (error) {
					console.error(error);
					swal({
						title: 'ERRORE!',
						text: 'C\'è stato un problema',
						icon: 'error',
						button: 'Ho capito',
						closeModal: true,
					});
				}
			} else {
				swal({
					title: 'ATTENZIONE!',
					text: 'Ordine già in fase di modifica. Impossibile procedere.',
					icon: 'warning',
					button: 'Ho capito',
					closeModal: true,
				});
			}
		});
	});

	// GESTIONE COMMESSE: SU CAMBIO VALORE DELLA QUANTITA' DA PRODURRE, RICARICO LA DISTINTA COMPONENTI CON IL FABBISOGNO AGGIORNATO
	$('#op_QtaDaProdurre').on('keyup change', function () {
		var idOrdineProduzione = $('#op_IdProduzione').val();
		var qtaDaProdurre = $('#op_QtaDaProdurre').val();

		//Visualizzazione distinta componenti per l'ordine di produzione selezionato
		$.post('gestionecommesse.php', {
			azione: 'mostra-distinta-componenti',
			idProduzione: idOrdineProduzione,
			qtaDaProdurre: qtaDaProdurre,
		}).done(function (dataDistintaComponenti) {
			$('#tabellaDati-distinta-componenti').dataTable().fnClearTable();

			if (dataDistintaComponenti != 'NO_ROWS') {
				$('#tabellaDati-distinta-componenti')
					.dataTable()
					.fnAddData(JSON.parse(dataDistintaComponenti));
			}
		});
	});

	// GESTIONE COMMESSE: SU CAMBIO VALORI IN SELECT LINEA, AGGIORNO I DATI DA ESSA DIPENDENTI (VEL. TEORICA, DISTINTA MACCHINE, DATA TEORICA DI FINE)
	$('#op_LineeProduzione').on('change', function () {
		var idLineaProduzione = $(this).val();
		var nomeLineaProduzione = $('#op_IdProduzione option:selected').text();

		var idProdotto = $('#op_Prodotto').val();
		var idOrdineProduzione = $('#op_IdProduzione').val();

		var tempVelLinea = $('#vel_VelocitaTeoricaLinea').val();
		var qtaDaProdurre = $('#op_QtaDaProdurre').val();
		var qtaDaProdurreFloat = parseFloat(qtaDaProdurre);
		var dataOraProduzione = $('#op_DataProduzione').val();

		//Visualizzazione dati distinta risorse
		$.post('gestionecommesse.php', {
			azione: 'inizializza-distinte',
			idProduzione: idOrdineProduzione,
			idProdotto: idProdotto,
			idLineaProduzione: idLineaProduzione,
			soloRisorse: 1,
		}).done(function (data) {
			if (data == 'OK') {
				//Visualizzazione dati distinta risorse
				$.post('gestionecommesse.php', {
					azione: 'mostra-distinta-risorse',
					idProduzione: idOrdineProduzione,
					idProdotto: idProdotto,
					idLineaProduzione: idLineaProduzione,
				}).done(function (data) {
					$('#tabellaDati-distinta-risorse').dataTable().fnClearTable();

					if (data != 'NO_ROWS') {
						$('#tabellaDati-distinta-risorse')
							.dataTable()
							.fnAddData(JSON.parse(data));
					}
				});

				//Visualizzazione dati distinta componenti
				$.post('gestionecommesse.php', {
					azione: 'recupera-velocita-teorica',
					idProdotto: idProdotto,
					idLineaProduzione: idLineaProduzione,
				}).done(function (data) {
					if (data == 'NO_ROWS') {
						$('#vel_VelocitaTeoricaLinea').val(0);
					} else {
						$('#vel_VelocitaTeoricaLinea').val(parseFloat(data));

						if (
							data != '' &&
							data != 0 &&
							qtaDaProdurreFloat != '' &&
							dataOraProduzione != ''
						) {
							//Visualizzazione dati ordine produzione selezionato
							$.post('gestionecommesse.php', {
								azione: 'aggiorna-data-fine',
								qtaDaProdurre: qtaDaProdurreFloat,
								velocitaTeoricaLinea: data,
								dataProduzione: dataOraProduzione,
							}).done(function (data) {
								$('#op_DataFine').val(data);
							});
						}
					}
				});
			} else if (data == 'ERRORE') {
				swal({
					title: 'ATTENZIONE!',
					text: 'Errore in inizializzazione distinte.',
					icon: 'warning',
					button: 'Ho capito',
					closeModal: true,
				});
			}
		});
	});

	// GESTIONE COMMESSE: ANNULLA MODIFICHE EFFETTUATE
	$('#annulla-modifica-ordine').on('click', function () {
		var idOrdineProduzione = $('#op_IdProduzione').val();

		$.post('gestionecommesse.php', {
			azione: 'pulizia-tabelle-lavoro',
			idProduzione: idOrdineProduzione,
		}).done(function (data) {
			if (data == 'OK') {
				$('#tabellaDati-distinta-risorse').dataTable().fnClearTable();
				$('#tabellaDati-distinta-componenti').dataTable().fnClearTable();

				$('#form-dati-ordine')[0].reset();
				$('#blocco-modifica').find('input.dati-ordine').val('');
				$('.multi-collapse').collapse('toggle');
				$('#annulla-modifica-ordine').prop('hidden', true);
				$('#conferma-modifica-ordine').prop('hidden', true);
				$('#aggiungi-risorsa-ordine').prop('hidden', true);
				$('#aggiungi-componente-ordine').prop('hidden', true);
				$('#aggiungi-consumo-ordine').prop('hidden', true);
				$('#nuova-commessa').prop('hidden', false);

				modOn = false;

				// funzione per sblocco ordine
				var fd = new FormData();
				fd.append('azione', 'sblocca-ordine');
				fd.append('idProduzione', idOrdineProduzione);
				navigator.sendBeacon('gestionecommesse.php', fd);
			} else {
				console.log(data);
				swal({
					title: 'ATTENZIONE!',
					text: 'Errore in svuotamento tabelle di lavoro.',
					icon: 'warning',
					button: 'Ho capito',
					closeModal: true,
				});
			}
		});
	});

	// GESTIONE COMMESSE: CANCELLAZIONE SEGNALAZIONE DI CAMPO VUOTO
	$('#form-dati-ordine input').on('blur', function () {
		$(this).removeClass('errore');
	});

	// COMMESSA DI PRODUZIONE: CONFERMA MODIFICHE EFFETTUATE
	$('#conferma-modifica-ordine').on('click', function () {
		var idOrdineProduzione = $('#op_IdProduzione').val();
		var idLineaProduzione = $('#op_LineeProduzione').val();
		var idProdotto = $('#op_Prodotto').val();

		// Verifico se ho campi obbligatori non compilati
		if (
			$('#vel_VelocitaTeoricaLinea').val() == '' ||
			$('#vel_VelocitaTeoricaLinea').val() == 0 ||
			$('#op_QtaDaProdurre').val() == '' ||
			$('#op_QtaDaProdurre').val() == 0 ||
			$('#op_LineeProduzione').val() == 'lin_00' ||
			$('#op_Priorita').val() == '' ||
			$('#op_Lotto').val() == ''
		) {
			// Segnalo visivamente all'utente eventuali campi non compilati ma obbligatori
			if (
				$('#vel_VelocitaTeoricaLinea').val() == '' ||
				$('#vel_VelocitaTeoricaLinea').val() == 0
			) {
				$('#vel_VelocitaTeoricaLinea').addClass('errore');
			}

			// Segnalo visivamente all'utente eventuali campi non compilati ma obbligatori
			if (
				$('#op_QtaDaProdurre').val() == '' ||
				$('#op_QtaDaProdurre').val() == 0
			) {
				$('#op_QtaDaProdurre').addClass('errore');
			}

			// Segnalo visivamente all'utente eventuali campi non compilati ma obbligatori
			$('#form-dati-ordine .obbligatorio').each(function () {
				if ($(this).val() == '') {
					$(this).addClass('errore');
				}
			});

			// Messaggio di segnalazione campi obbligatori non compilati
			swal({
				title: 'OPERAZIONE NON ESEGUITA!',
				text: 'Compilare tutti i campi obbligatori contrassegnati con *',
				icon: 'warning',
				button: 'Ho capito',
				closeModal: true,
			});
		} else if (!tabellaDatiDistintaRisorse.data().count()) {
			swal({
				title: 'OPERAZIONE NON ESEGUITA!',
				text: 'Verificare di aver compilato le distinte componenti e risorse.',
				icon: 'warning',
				button: 'Ho capito',
				closeModal: true,
			});
		} else {
			//Inserimento dati distinta risorse nella tabella di competenza
			$.post('gestionecommesse.php', {
				azione: 'inserisci-distinta-risorse',
				idProduzione: idOrdineProduzione,
				idLineaProduzione: idLineaProduzione,
			}).done(function (data) {
				if (data == 'OK') {
					$('#tabellaDati-distinta-risorse').dataTable().fnClearTable();

					//Inserimento dati distinta componenti nella tabella di competenza
					$.post('gestionecommesse.php', {
						azione: 'inserisci-distinta-componenti',
						idProduzione: idOrdineProduzione,
						idLineaProduzione: idLineaProduzione,
						idProdotto: idProdotto,
					}).done(function (data) {
						if (data == 'OK') {
							$('#tabellaDati-distinta-componenti')
								.dataTable()
								.fnClearTable();
							$.post('gestionecommesse.php', {
								azione: 'inserisci-distinta-consumi',
								idProduzione: idOrdineProduzione,
								idLineaProduzione: idLineaProduzione,
								idProdotto: idProdotto,
							}).done(function (data) {
								if (data == 'OK') {
									$('#tabellaDati-distinta-consumi')
										.dataTable()
										.fnClearTable();
									//Inserimento dati distinta componenti nella tabella di competenza
									$.post('gestionecommesse.php', {
										azione: 'aggiorna-ordine-produzione',
										data: $('#form-dati-ordine').serialize(),
									}).done(function (data) {
										if (data == 'OK') {
											$.post('gestionecommesse.php', {
												azione: 'pulizia-tabelle-lavoro',
												idProduzione: idOrdineProduzione,
											}).done(function (data) {
												if (data == 'OK') {
													// Aggiorno reload dei dati del grafico e della tabella
													reloadDataGantt();
													visualizzaElencoOrdini(g_idStatoOrdine);

													$('#form-dati-ordine')[0].reset();
													$('#blocco-modifica')
														.find('input.dati-ordine')
														.val('');
													$('.multi-collapse').collapse('toggle');

													$('#nuova-commessa').prop(
														'hidden',
														false
													);
													$('#annulla-modifica-ordine').prop(
														'hidden',
														true
													);
													$('#conferma-modifica-ordine').prop(
														'hidden',
														true
													);
													$('#aggiungi-risorsa-ordine').prop(
														'hidden',
														true
													);
													$('#aggiungi-componente-ordine').prop(
														'hidden',
														true
													);
													$('#aggiungi-consumo-ordine').prop(
														'hidden',
														true
													);

													modOn = false;

													// funzione per sblocco ordine
													var fd = new FormData();
													fd.append('azione', 'sblocca-ordine');
													fd.append(
														'idProduzione',
														idOrdineProduzione
													);
													navigator.sendBeacon(
														'gestionecommesse.php',
														fd
													);
												} else if (data == 'ERRORE') {
													swal({
														title: 'ATTENZIONE!',
														text: 'Errore in svuotamento tabelle di lavoro.',
														icon: 'warning',
														button: 'Ho capito',
														closeModal: true,
													});
												}
											});
										} else if (data == 'ERRORE') {
											swal({
												title: 'ATTENZIONE!',
												text: 'Errore in esecuzione aggiornamento ordine.',
												icon: 'warning',
												button: 'Ho capito',
												closeModal: true,
											});
										}
									});
								} else {
									console.log(data);
									swal({
										title: 'ATTENZIONE!',
										text: 'Errore in esecuzione caricamento distinta consumi.',
										icon: 'Error',
										button: 'Ho capito',
										closeModal: true,
									});
								}
							});
						} else {
							console.log(data);
							swal({
								title: 'ATTENZIONE!',
								text: 'Errore in esecuzione caricamento distinta componenti.',
								icon: 'Error',
								button: 'Ho capito',
								closeModal: true,
							});
						}
					});
				} else {
					console.log(data);
					swal({
						title: 'ATTENZIONE!',
						text: 'Errore in esecuzione caricamento distinta risorse.',
						icon: 'Error',
						button: 'Ho capito',
						closeModal: true,
					});
				}
			});

			return false;
		}
	});

	// --------------------------------------------- INSERIMENTO NUOVA COMMESSA  ---------------------------------------------

	// GESTIONE COMMESSE - D. COMPONENTI: RIMUOVO BORDO DI ERRORE SU SELEZIONE CAMPO
	$('#form-ordine-produzione input').on('blur', function () {
		$(this).removeClass('errore');
	});

	// SU CAMBIO PRODOTTO SELEZIONATO, RECUPERO LA RELATIVA UNITA' DI MISURA E IMPOSTO LA MEDESIMA ANCHE PER LA COMMESSA IN OGGETTO
	$('#op_ProdottoComm').on('change', function () {
		var idProdotto = $(this).val();

		$.post('utilities.php', {
			azione: 'recupera-udm',
			codice: idProdotto,
		}).done(function (data) {
			$('#form-ordine-produzione #op_UdmComm').val(data);
			$('#form-ordine-produzione #op_UdmComm').selectpicker('refresh');
		});
	});

	// COMMESSE DI PRODUZIONE: INSERIMENTO NUOVA COMMESSA DI PRODUZIONE
	$('#nuova-commessa').on('click', function () {
		//Genero data e ora per pre-inizializzare il campo
		var today = moment();
		var dataOdierna = today.format('YYYY-MM-DD');
		var oraOdierna = today.format('HH:mm');

		$('#form-ordine-produzione input').removeClass('errore');

		$('#form-ordine-produzione')[0].reset();
		$('#form-ordine-produzione #op_IdProduzioneComm').prop('disabled', false);
		$('#form-ordine-produzione #op_ProdottoComm').prop('disabled', false);
		$('#form-ordine-produzione .selectpicker').val('default');
		$('#form-ordine-produzione .selectpicker').selectpicker('refresh');

		$('#form-ordine-produzione').find('input#azioneComm').val('nuovo');
		$('#modal-ordine-produzione-label').text('INSERIMENTO NUOVA COMMESSA');

		$('#form-ordine-produzione')
			.find('input#op_DataOrdineComm')
			.val(dataOdierna);
		$('#form-ordine-produzione')
			.find('input#op_OraOrdineComm')
			.val(oraOdierna);
		$('#form-ordine-produzione')
			.find('input#op_DataProduzioneComm')
			.val(dataOdierna);
		$('#form-ordine-produzione')
			.find('input#op_OraProduzioneComm')
			.val(oraOdierna);

		$('#modal-ordine-produzione').modal('show');
	});

	// COMMESSE DI PRODUZIONE: SALVATAGGIO DA POPUP MODIFICA/INSERIMENTO
	$('body').on('click', '#salva-ordine-produzione', function (e) {
		e.preventDefault();

		// inizializzo il contatore errori
		var errori = 0;
		var azione = $('#modal-ordine-produzione #azioneComm').val();

		// Controllo riempimento campi obbligatori (INPUT)
		$('#form-ordine-produzione .obbligatorio').each(function () {
			if ($(this).val() == '') {
				errori++;
				$(this).addClass('errore');
			}
		});

		// Controllo riempimento campi obbligatori (SELECT)
		$('#form-ordine-produzione .selectpicker').each(function () {
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
			var lottoInserito = $('#form-ordine-produzione #op_LottoComm').val();
			var idProduzione = $(
				'#form-ordine-produzione #op_IdProduzioneComm'
			).val();

			// salvo i dati
			$.post('gestionecommesse.php', {
				azione: 'verifica-valori-ripetuti',
				lottoInserito: lottoInserito,
				idProduzione: idProduzione,
			}).done(function (dataVerificaLotto) {
				if (
					(dataVerificaLotto == 'OK' && azione == 'nuovo') ||
					azione == 'modifica'
				) {
					// salvo i dati
					$.post('gestionecommesse.php', {
						azione: 'salva-ordine-produzione',
						data: $('#form-ordine-produzione').serialize(),
					}).done(function (data) {
						// se è tutto OK
						if (data == 'OK') {
							$('#modal-ordine-produzione').modal('hide');

							// ricarico la datatable per vedere le modifiche
							visualizzaElencoOrdini(g_idStatoOrdine);
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
				} else {
					swal({
						title: 'Attenzione',
						text: 'Lotto inserito già utilizzato, desideri proseguire comunque?.',
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
								text: 'Sì, prosegui',
								value: true,
								visible: true,
								className: 'btn btn-primary',
								closeModal: true,
							},
						},
					}).then((procedi) => {
						if (procedi) {
							// salvo i dati
							$.post('gestionecommesse.php', {
								azione: 'salva-ordine-produzione',
								data: $('#form-ordine-produzione').serialize(),
							}).done(function (data) {
								// se è tutto OK
								if (data == 'OK') {
									$('#modal-ordine-produzione').modal('hide');

									// ricarico la datatable per vedere le modifiche
									visualizzaElencoOrdini(g_idStatoOrdine);
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
					});
				}
			});
		}

		return false;
	});

	// COMMESSE DI PRODUZIONE: CANCELLAZIONE COMMESSA SELEZIONATO
	$('body').on('click', '.cancella-commessa', function (e) {
		e.preventDefault();

		var idOrdineProduzione = $(this).data('id-ordine-produzione');
		var statoOrdine = $(this).data('stato-ordine');

		if (statoOrdine > 2 && statoOrdine < 6) {
			swal({
				title: 'ATTENZIONE',
				text: 'Ordine in esecuzione o già terminato, impossibile procedere.',
				icon: 'warning',
				button: 'Ho capito',
				closeModal: true,
			});
		} else {
			swal({
				title: 'Attenzione',
				text: "Confermi di voler eliminare la commessa in oggetto? L'eliminazione è irreversibile.",
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
					$.post('gestionecommesse.php', {
						azione: 'cancella-ordine-produzione',
						id: idOrdineProduzione,
					}).done(function (data) {
						if (data != 'OK') {
							console.log(data);
							swal({
								title: 'Errore.',
								text: 'Operazione non eseguita.',
								icon: 'error',
								button: 'Ho capito',
							});
						}

						visualizzaElencoOrdini(g_idStatoOrdine);
					});
				} else {
					swal.close();
				}
			});
		}
		return false;
	});

	// --------------------------------------------- OPERAZIONI SU DISTINTA RISORSE COINVOLTE  ---------------------------------------------

	// GESTIONE COMMESSE - D. MACCHINE: RIMUOVO BORDO DI ERRORE SU SELEZIONE CAMPO
	$('#form-nuova-risorsa input').on('blur', function () {
		$(this).removeClass('errore');
	});

	// GESTIONE COMMESSE - DISTINTA RISORSE COINVOLTE (TAB. DI LAVORO): CAMBIO MACCHINA IN SELECT POPUP DI INSERIMENTO
	$('#form-nuova-risorsa #rc_IdRisorsa').on('change', function () {
		var idRisorsa = $('#form-nuova-risorsa #rc_IdRisorsa').val();
		var idProdotto = $('#op_Prodotto').val();

		// Se non ho macchine disponibili
		if (idRisorsa == '') {
			$('#salva-risorsa-ordine').prop('disabled', true);

			$('#form-nuova-risorsa #rc_IdRicetta').html(
				'<option value="">Nessuna macchina disponibile</option>'
			);
			$('#form-nuova-risorsa #rc_IdRicetta').prop('disabled', true);
			$('#form-nuova-risorsa .selectpicker').selectpicker('refresh');
		} else {
			$('#salva-risorsa-ordine').prop('disabled', false);

			//Recupero le risorse disponibili e non ancora aggiunte alla distinta, per popolare la relativa SELECT
			$.post('distintarisorse.php', {
				azione: 'caricaSelectRicette',
				idRisorsa: idRisorsa,
				idProdotto: idProdotto,
			}).done(function (data) {
				// Se non ho ricetta disponibili per la macchina selezioanta
				if (data == 'NO_RIC') {
					$('#form-nuova-risorsa #rc_IdRicetta').html(
						'<option value="">Nessuna ricetta disponibile</option>'
					);
					$('#form-nuova-risorsa #rc_IdRicetta').prop('disabled', true);
				} else {
					$('#form-nuova-risorsa #rc_IdRicetta').html(data);
					$('#form-nuova-risorsa #rc_IdRicetta').prop('disabled', false);
				}
				$('#form-nuova-risorsa .selectpicker').selectpicker('refresh');
			});
		}
	});

	// GESTIONE COMMESSE - DISTINTA RISORSE COINVOLTE (TAB. DI LAVORO): VISUALIZZO POPUP 'AGGIUNTA NUOVA MACCHINA'
	$('#aggiungi-risorsa-ordine').on('click', function () {
		var idLineaProduzione = $('#op_LineeProduzione').val();
		var nomeLineaProduzione = $('#op_LineeProduzione option:selected').text();
		var idOrdineProduzione = $('#op_IdProduzione').val();

		$('#form-nuova-risorsa input').removeClass('errore');

		$('#form-nuova-risorsa')[0].reset();
		$('#form-nuova-risorsa #rc_IdLineaProduzione').val(idLineaProduzione);
		$('#form-nuova-risorsa #rc_NomeLineaProduzione').val(nomeLineaProduzione);
		$('#form-nuova-risorsa #rc_IdProduzione').val(idOrdineProduzione);

		//Recupero le risorse disponibili e non ancora aggiunte alla distinta, per popolare la relativa SELECT
		$.post('gestionecommesse.php', {
			azione: 'caricaSelectRisorse',
			idProduzione: idOrdineProduzione,
			idLineaProduzione: idLineaProduzione,
		}).done(function (data) {
			if (data == 'NO_RIS') {
				$('#form-nuova-risorsa #rc_IdRisorsa').html(
					'<option value="">Nessuna macchina disponibile</option>'
				);
				$('#form-nuova-risorsa #rc_IdRisorsa').prop('disabled', true);
				$('#form-nuova-risorsa #rc_IdRisorsa').selectpicker('refresh');
				$('#salva-risorsa-ordine').prop('disabled', true);
			} else {
				$('#form-nuova-risorsa #rc_IdRisorsa').html(data);
				$('#form-nuova-risorsa #rc_IdRisorsa').val('default');
				$('#form-nuova-risorsa #rc_IdRisorsa').prop('disabled', false);
				$('#form-nuova-risorsa #rc_IdRisorsa').selectpicker('refresh');
				$('#salva-risorsa-ordine').prop('disabled', false);
			}

			// Valorizzo opportunamente la select di selezione ricetta
			$('#form-nuova-risorsa #rc_IdRicetta').html(
				'<option value="">Nessuna macchina selezionata</option>'
			);
			$('#form-nuova-risorsa #rc_IdRicetta').prop('disabled', true);
			$('#form-nuova-risorsa .selectpicker').selectpicker('refresh');
		});

		$('#form-nuova-risorsa').find('input#rc_Azione').val('nuovo');
		$('#modal-nuova-risorsa-label').text('AGGIUNTA NUOVA MACCHINA');
		$('#modal-nuova-risorsa').modal('show');
	});

	// GESTIONE COMMESSE - DISTINTA RISORSE COINVOLTE (TAB. DI LAVORO): MODIFICA RISORSA IN TAB. DI LAVORO
	$('body').on('click', 'a.modifica-risorsa-ordine', function (e) {
		e.preventDefault();

		var idRisorsa = $(this).data('id-risorsa-ordine');
		var idOrdineProduzione = $(this).data('id-ordine-produzione');
		var idLineaProduzione = $('#op_LineeProduzione').val();
		var nomeLineaProduzione = $('#op_LineeProduzione option:selected').text();

		$('#form-nuova-risorsa input').removeClass('errore');

		$('#form-nuova-risorsa')[0].reset();
		$('#form-nuova-risorsa #rc_IdLineaProduzione').val(idLineaProduzione);
		$('#form-nuova-risorsa #rc_NomeLineaProduzione').val(nomeLineaProduzione);
		$('#form-nuova-risorsa #rc_IdProduzione').val(idOrdineProduzione);

		// invoco il metodo per recuperare i valori per la riga selezionata
		$.post('gestionecommesse.php', {
			azione: 'recupera-risorsa-ordine',
			idProduzione: idOrdineProduzione,
			idRisorsa: idRisorsa,
		}).done(function (data) {
			var dati = JSON.parse(data);

			// per ciascuno dei dati letti dal form, procedo ad inizializzare la relativa casella
			for (var chiave in dati) {
				if (dati.hasOwnProperty(chiave)) {
					$('#form-nuova-risorsa')
						.find('input#' + chiave)
						.val(dati[chiave]);
					$('#form-nuova-risorsa select#' + chiave).val(dati[chiave]);
					$('#form-nuova-risorsa select#' + chiave).selectpicker(
						'refresh'
					);
				}
			}

			// Tratto separatamente i valori relativi alle checkbox

			// 'abilitazione misure'
			if (dati['rc_RegistraMisure'] == 1) {
				$('#form-nuova-risorsa')
					.find('input#rc_RegistraMisure')
					.prop('checked', true);
			} else {
				$('#form-nuova-risorsa')
					.find('input#rc_RegistraMisure')
					.prop('checked', false);
			}

			// 'ultima macchina'
			if (dati['rc_FlagUltima'] == 1) {
				$('#form-nuova-risorsa')
					.find('input#rc_FlagUltima')
					.prop('checked', true);
			} else {
				$('#form-nuova-risorsa')
					.find('input#rc_FlagUltima')
					.prop('checked', false);
			}

			// Tratto separatamente il popolamento della select 'risorse'
			$.post('gestionecommesse.php', {
				azione: 'caricaSelectRisorse',
				idProduzione: idOrdineProduzione,
				idLineaProduzione: idLineaProduzione,
				risorsa: idRisorsa,
			}).done(function (data) {
				$('#rc_IdRisorsa').html(data);
				$('#form-nuova-risorsa #rc_IdRisorsa').prop('disabled', true);
				$('#form-nuova-risorsa #rc_IdRisorsa').selectpicker('refresh');

				// Tratto separatamente il popolamento della select 'ricette'
				$.post('gestionecommesse.php', {
					azione: 'caricaSelectRicette',
					idRisorsa: $('#form-nuova-risorsa #rc_IdRisorsa').val(),
					idRicetta: dati['rc_IdRicetta'],
				}).done(function (data) {
					if (data == 'NO_RIC') {
						$('#form-nuova-risorsa #rc_IdRicetta').html(
							'<option value="">Nessuna ricetta disponibile</option>'
						);
						$('#form-nuova-risorsa #rc_IdRicetta').prop('disabled', true);
						$('#form-nuova-risorsa #rc_IdRicetta').selectpicker(
							'refresh'
						);
					} else {
						$('#form-nuova-risorsa #rc_IdRicetta').html(data);
						$('#form-nuova-risorsa #rc_IdRicetta').prop(
							'disabled',
							false
						);
						$('#form-nuova-risorsa #rc_IdRicetta').selectpicker(
							'refresh'
						);
					}

					$('#salva-risorsa-ordine').prop('disabled', false);

					// visualizzo popup
					$('#form-nuova-risorsa').find('input#rc_Azione').val('modifica');
					$('#modal-nuova-risorsa-label').text('MODIFICA MACCHINA');
					$('#modal-nuova-risorsa').modal('show');
				});
			});
		});

		return false;
	});

	// GESTIONE COMMESSE - DISTINTA RISORSE COINVOLTE (TAB. DI LAVORO): SALVATAGGIO RISORSA AGGIUNTA IN TAB. DI LAVORO
	$('body').on('click', '#salva-risorsa-ordine', function (e) {
		e.preventDefault();

		var idOrdineProduzione = $('#op_IdProduzione').val();

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
				title: 'ATTENZIONE!',
				text: 'Compilare tutti i campi obbligatori contrassegnati con *',
				icon: 'warning',
				button: 'Ho capito',
				closeModal: true,
			});
		} // nessun errore, posso continuare
		else {
			var idRisorsa = $('#rc_IdRisorsa').val();
			$('#rc_IdRisorsa').prop('disabled', false);

			var abiMisure;
			var flagUltima;

			// Recupero valore della checkbox e lo formato opportunamente
			if ($('#rc_RegistraMisure').is(':checked')) {
				abiMisure = 1;
			} else {
				abiMisure = 0;
			}

			if ($('#rc_FlagUltima').is(':checked')) {
				flagUltima = 1;
			} else {
				flagUltima = 0;
			}

			// Salvo i dati
			$.post('gestionecommesse.php', {
				azione: 'salva-risorsa-ordine',
				data: $('#form-nuova-risorsa').serialize(),
				abiMisure: abiMisure,
				flagUltima: flagUltima,
			}).done(function (data) {
				// se è tutto OK
				if (data == 'OK') {
					$('#modal-nuova-risorsa').modal('hide');

					$.post('gestionecommesse.php', {
						azione: 'mostra-distinta-risorse',
						idProduzione: idOrdineProduzione,
					}).done(function (data) {
						$('#tabellaDati-distinta-risorse').dataTable().fnClearTable();

						if (data != 'NO_ROWS') {
							$('#tabellaDati-distinta-risorse')
								.dataTable()
								.fnAddData(JSON.parse(data));
						}
					});
				} else {
					swal({
						title: 'ATTENZIONE!',
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

	// GESTIONE COMMESSE - DISTINTA RISORSE COINVOLTE (TAB. DI LAVORO): CANCELLAZIONE RISORSA DA TAB. DI LAVORO
	$('body').on('click', 'a.cancella-risorsa-ordine', function (e) {
		e.preventDefault();

		var idRisorsa = $(this).data('id-risorsa-ordine');
		var idOrdineProduzione = $(this).data('id-ordine-produzione');

		swal({
			title: 'ATTENZIONE!',
			text: 'Confermi di voler eliminare la macchina in oggetto dalla distinta?',
			icon: 'warning',
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
				$.post('gestionecommesse.php', {
					azione: 'cancella-risorsa-ordine',
					idRisorsa: idRisorsa,
					idProduzione: idOrdineProduzione,
				}).done(function (data) {
					swal.close();

					$.post('gestionecommesse.php', {
						azione: 'mostra-distinta-risorse',
						idProduzione: idOrdineProduzione,
					}).done(function (data) {
						$('#tabellaDati-distinta-risorse').dataTable().fnClearTable();

						if (data != 'NO_ROWS') {
							$('#tabellaDati-distinta-risorse')
								.dataTable()
								.fnAddData(JSON.parse(data));
						}
					});
				});
			} else {
				swal.close();
			}
		});
		return false;
	});

	// --------------------------------------------- OPERAZIONI SU DISTINTA COMPONENTI PRODOTTO  ---------------------------------------------

	// GESTIONE COMMESSE - D. COMPONENTI: RIMUOVO BORDO DI ERRORE SU SELEZIONE CAMPO
	$('#form-nuovo-componente input').on('blur', function () {
		$(this).removeClass('errore');
	});

	// SU CAMBIO COMPONENTE SELEZIONATO, RECUPERO LA RELATIVA UNITA' DI MISURA
	$('#form-nuovo-componente #cmp_Componente').on('change', function () {
		var idProdotto = $(this).val();

		$.post('utilities.php', {
			azione: 'recupera-udm',
			codice: idProdotto,
		}).done(function (data) {
			$('#form-nuovo-componente #cmp_Udm').val(data);
			$('#form-nuovo-componente #cmp_Udm').selectpicker('refresh');
		});
	});

	// GESTIONE COMMESSE - DISTINTA COMPONENTI (TAB. DI LAVORO): VISUALIZZO POPUP 'AGGIUNTA NUOVO COMPONENTE'
	$('#aggiungi-componente-ordine').on('click', function () {
		var idProdotto = $('#op_Prodotto').val();
		var idLineaProduzione = $('#op_LineeProduzione').val();
		var nomeLineaProduzione = $('#op_LineeProduzione option:selected').text();
		var nomeProdotto = $('#prd_Descrizione').val();
		var idOrdineProduzione = $('#op_IdProduzione').val();

		$('#form-nuovo-componente input').removeClass('errore');

		$('#form-nuovo-componente')[0].reset();
		$('#form-nuovo-componente #cmp_IdLineaProduzione').val(idLineaProduzione);
		$('#form-nuovo-componente #cmp_NomeLineaProduzione').val(
			nomeLineaProduzione
		);
		$('#form-nuovo-componente #cmp_IdProduzione').val(idOrdineProduzione);
		$('#form-nuovo-componente #cmp_Udm').val('default');
		$('#form-nuovo-componente #cmp_Udm').selectpicker('refresh');
		$('#form-nuovo-componente #cmp_FattoreMoltiplicativo').val(1);
		$('#form-nuovo-componente #cmp_PezziConfezione').val(1);

		//Recupero le risorse disponibili e non ancora aggiunte alla distinta, per popolare la relativa SELECT
		$.post('gestionecommesse.php', {
			azione: 'caricaSelectComponenti',
			idProduzione: idOrdineProduzione,
		}).done(function (data) {
			if (data == 'NO_CMP') {
				$('#form-nuovo-componente #cmp_Componente').html(
					'<option value="">Nessun componente disponibile</option>'
				);
				$('#form-nuovo-componente #cmp_Componente').prop('disabled', true);
				$('#form-nuovo-componente #cmp_Componente').selectpicker('refresh');
				$('#salva-componente-ordine').prop('disabled', true);
			} else {
				$('#form-nuovo-componente #cmp_Componente').html(data);
				$('#form-nuovo-componente #cmp_Componente').val('default');
				$('#form-nuovo-componente #cmp_Componente').prop('disabled', false);
				$('#form-nuovo-componente #cmp_Componente').selectpicker('refresh');
				$('#salva-componente-ordine').prop('disabled', false);
			}
		});

		$('#form-nuovo-componente').find('input#cmp_Azione').val('nuovo');
		$('#modal-nuovo-componente-label').text('AGGIUNTA COMPONENTE');
		$('#modal-nuovo-componente').modal('show');
	});

	// GESTIONE COMMESSE - CORPO DISTINTA RISORSE (DRC): MODIFICA COMPONENTE
	$('body').on('click', 'a.modifica-componente-ordine', function (e) {
		e.preventDefault();

		var idComponente = $(this).data('id-componente-ordine');
		var idOrdineProduzione = $(this).data('id-ordine-produzione');

		$('#form-nuovo-componente input').removeClass('errore');

		$('#form-nuovo-componente')[0].reset();

		// Invoco il metodo per recuperare i valori per la riga selezionata
		$.post('gestionecommesse.php', {
			azione: 'recupera-componente-ordine',
			idProduzione: idOrdineProduzione,
			idComponente: idComponente,
		}).done(function (data) {
			var dati = JSON.parse(data);

			// Popolo il popup con i dati recuperati
			for (var chiave in dati) {
				if (dati.hasOwnProperty(chiave)) {
					$('#form-nuovo-componente')
						.find('input#' + chiave)
						.val(dati[chiave]);
					$('#form-nuovo-componente select#' + chiave).val(dati[chiave]);
					$('#form-nuovo-componente select#' + chiave).selectpicker(
						'refresh'
					);
				}
			}

			//  Tratto separatamente il popolamento della select 'componenti'
			$.post('gestionecommesse.php', {
				azione: 'caricaSelectComponenti',
				idProduzione: idOrdineProduzione,
				componente: idComponente,
			}).done(function (data) {
				$('#form-nuovo-componente #cmp_Componente').html(data);
				$('#form-nuovo-componente #cmp_Componente').prop('disabled', true);
				$('#form-nuovo-componente #cmp_Componente').selectpicker('refresh');
			});

			$('#salva-componente-ordine').prop('disabled', false);

			$('#form-nuovo-componente').find('input#cmp_Azione').val('modifica');
			$('#modal-nuovo-componente-label').text('MODIFICA COMPONENTE');
			$('#modal-nuovo-componente').modal('show');
		});

		return false;
	});

	// GESTIONE COMMESSE - DISTINTA COMPONENTI (TAB. DI LAVORO): SALVATAGGIO COMPONENTE AGGIUNTO IN TAB. DI LAVORO
	$('body').on('click', '#salva-componente-ordine', function (e) {
		e.preventDefault();

		var idOrdineProduzione = $('#cmp_IdProduzione').val();
		var qtaDaProdurre = $('#op_QtaDaProdurre').val();

		var errori = 0;

		// Controllo riempimento campi obbligatori (INPUT)
		$('#form-nuovo-componente .obbligatorio').each(function () {
			if ($(this).val() == '') {
				errori++;
				$(this).addClass('errore');
			}
		});

		// Controllo riempimento campi obbligatori (SELECT)
		$('#form-nuovo-componente .selectpicker').each(function () {
			if ($(this).val() == null) {
				errori++;
				$(this).addClass('errore');
			}
		});

		// Se ho anche solo un errore mi fermo qui
		if (errori > 0) {
			swal({
				title: 'ATTENZIONE!',
				text: 'Compilare tutti i campi obbligatori contrassegnati con *',
				icon: 'warning',
				button: 'Ho capito',
				closeModal: true,
			});
		} // nessun errore, posso continuare
		else {
			// PER PERMETTERE SERIALIZE DEL FORM: riabilito le caselle contenenti 'id prodotto' e 'numero riga'
			$('#cmp_Componente').prop('disabled', false);

			// salvo i dati
			$.post('gestionecommesse.php', {
				azione: 'salva-componente-ordine',
				data: $('#form-nuovo-componente').serialize(),
			}).done(function (data) {
				// se è tutto OK
				if (data == 'OK') {
					$('#modal-nuovo-componente').modal('hide');

					//Visualizzazione dati distinta componenti
					$.post('gestionecommesse.php', {
						azione: 'mostra-distinta-componenti',
						idProduzione: idOrdineProduzione,
						qtaDaProdurre: qtaDaProdurre,
					}).done(function (data) {
						$('#tabellaDati-distinta-componenti')
							.dataTable()
							.fnClearTable();

						if (data != 'NO_ROWS') {
							$('#tabellaDati-distinta-componenti')
								.dataTable()
								.fnAddData(JSON.parse(data));
						}
					});
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

			//DOPO SERIALIZE DEL FORM: disabilito nuovamente le caselle contenenti 'id prodotto' e 'numero riga'
			$('#form-nuovo-componente #cmp_Componente').prop('disabled', true);
		}

		return false;
	});

	// GESTIONE COMMESSE - DISTINTA COMPONENTI (TAB. DI LAVORO): CANCELLAZIONE COMPONENTE DA TAB. DI LAVORO
	$('body').on('click', 'a.cancella-componente-ordine', function (e) {
		e.preventDefault();

		var idComponente = $(this).data('id-componente-ordine');
		var idOrdineProduzione = $(this).data('id-ordine-produzione');
		var qtaDaProdurre = $('#op_QtaDaProdurre').val();

		swal({
			title: 'ATTENZIONE!',
			text: 'Confermi RIMOZIONE del componente in oggetto?',
			icon: 'warning',
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
				$.post('gestionecommesse.php', {
					azione: 'cancella-componente-ordine',
					idComponente: idComponente,
					idProduzione: idOrdineProduzione,
				}).done(function (data) {
					swal.close();

					//Visualizzazione dati distinta componenti
					$.post('gestionecommesse.php', {
						azione: 'mostra-distinta-componenti',
						idProduzione: idOrdineProduzione,
						qtaDaProdurre: qtaDaProdurre,
					}).done(function (data) {
						$('#tabellaDati-distinta-componenti')
							.dataTable()
							.fnClearTable();

						if (data != 'NO_ROWS') {
							$('#tabellaDati-distinta-componenti')
								.dataTable()
								.fnAddData(JSON.parse(data));
						}
					});
				});
			} else {
				swal.close();
			}
		});
		return false;
	});

	// --------------------------------------------- OPERAZIONI SU DISTINTA CONSUMI  ---------------------------------------------

	// GESTIONE COMMESSE - D. COMPONENTI: RIMUOVO BORDO DI ERRORE SU SELEZIONE CAMPO
	$('#form-nuovo-consumo input').on('blur', function () {
		$(this).removeClass('errore');
	});

	$('#form-nuovo-consumo #con_IdRisorsa').on('change', function () {
		var idRisorsa = $(this).val();
		$.post('gestionecommesse.php', {
			azione: 'caricaSelectConsumi',
			idRisorsa: idRisorsa,
		}).done(function (data) {
			$('#form-nuovo-consumo #con_IdTipoConsumo').html(data);
			$('#form-nuovo-consumo #con_IdTipoConsumo').val(null);
			$('#form-nuovo-consumo #con_IdTipoConsumo').prop('disabled', false);
			$('#form-nuovo-consumo #con_IdTipoConsumo').selectpicker('refresh');
			$('#salva-dc').prop('disabled', false);
		});
	});

	// GESTIONE COMMESSE - DISTINTA COMPONENTI (TAB. DI LAVORO): VISUALIZZO POPUP 'AGGIUNTA NUOVO COMPONENTE'
	$('#aggiungi-consumo-ordine').on('click', function () {
		var idLineaProduzione = $('#op_LineeProduzione').val();
		var nomeLineaProduzione = $('#op_LineeProduzione option:selected').text();
		var idOrdineProduzione = $('#op_IdProduzione').val();

		$('#form-nuova-consumo input').removeClass('errore');

		$('#form-nuovo-consumo')[0].reset();
		$('#form-nuovo-consumo #con_IdProduzione').val(idOrdineProduzione);
		$.post('gestionecommesse.php', {
			azione: 'caricaSelectRisorseConsumi',
			idProduzione: idOrdineProduzione,
		}).done(function (data) {
			if (data == 'NO_CMP') {
				$('#form-nuovo-consumo #con_IdRisorsa').html(
					'<option value="">Nessuna risorsa coinvolta disponibile</option>'
				);
				$('#form-nuovo-consumo #con_IdRisorsa').prop('disabled', true);
				$('#form-nuovo-consumo #con_IdRisorsa').selectpicker('refresh');
				$('#salva-consumo-ordine').prop('disabled', true);
			} else {
				$('#form-nuovo-consumo #con_IdRisorsa').html(data);
				$('#form-nuovo-consumo #con_IdRisorsa').val(null);
				$('#form-nuovo-consumo #con_IdRisorsa').prop('disabled', false);
				$('#form-nuovo-consumo #con_IdRisorsa').selectpicker('refresh');
				$('#salva-consumo-ordine').prop('disabled', false);
			}
		});

		$('#form-nuovo-consumo').find('input#con_Azione').val('nuovo');
		$('#modal-nuovo-consumo-label').text('AGGIUNTA NUOVO CONSUMO');
		$('#modal-nuovo-consumo').modal('show');
	});

	// GESTIONE COMMESSE - CORPO DISTINTA RISORSE (DRC): MODIFICA COMPONENTE
	$('body').on('click', 'a.modifica-consumo-ordine', function (e) {
		e.preventDefault();

		var idConsumo = $(this).data('id-consumo');
		var idRisorsa = $(this).data('id-risorsa');
		var idOrdineProduzione = $(this).data('id-ordine-produzione');

		$('#form-nuovo-consumo input').removeClass('errore');

		$('#form-nuovo-consumo')[0].reset();

		// Invoco il metodo per recuperare i valori per la riga selezionata
		$.post('gestionecommesse.php', {
			azione: 'recupera-consumo-ordine',
			idRisorsa: idRisorsa,
			idProduzione: idOrdineProduzione,
			idConsumo: idConsumo,
		}).done(function (data) {
			var dati = JSON.parse(data);

			// Popolo il popup con i dati recuperati
			for (var chiave in dati) {
				if (dati.hasOwnProperty(chiave)) {
					$('#form-nuovo-consumo')
						.find('input#' + chiave)
						.val(dati[chiave]);
					$('#form-nuovo-consumo select#' + chiave).val(dati[chiave]);
					$('#form-nuovo-consumo select#' + chiave).selectpicker(
						'refresh'
					);
				}
			}

			$.post('gestionecommesse.php', {
				azione: 'caricaSelectRisorseConsumi',
				idProduzione: idOrdineProduzione,
				idRisorsa: idRisorsa,
			}).done(function (data) {
				$('#form-nuovo-consumo #con_IdRisorsa').html(data);
				$('#form-nuovo-consumo #con_IdRisorsa').val(idRisorsa);
				$('#form-nuovo-consumo #con_IdRisorsa').prop('disabled', false);
				$('#form-nuovo-consumo #con_IdRisorsa').selectpicker('refresh');
				$('#salva-consumo-ordine').prop('disabled', false);
			});

			$.post('gestionecommesse.php', {
				azione: 'caricaSelectConsumi',
				idRisorsa: idRisorsa,
				idConsumo: idConsumo,
			}).done(function (data) {
				$('#form-nuovo-consumo #con_IdTipoConsumo').html(data);
				$('#form-nuovo-consumo #con_IdTipoConsumo').val(idConsumo);
				$('#form-nuovo-consumo #con_IdTipoConsumo').prop('disabled', false);
				$('#form-nuovo-consumo #con_IdTipoConsumo').selectpicker('refresh');
				$('#salva-dc').prop('disabled', false);
			});

			$('#salva-consumo-ordine').prop('disabled', false);

			$('#form-nuovo-consumo').find('input#con_Azione').val('modifica');
			$('#modal-nuovo-consumo-label').text('MODIFICA COMPONENTE');
			$('#modal-nuovo-consumo').modal('show');
		});

		return false;
	});

	// GESTIONE COMMESSE - DISTINTA COMPONENTI (TAB. DI LAVORO): SALVATAGGIO COMPONENTE AGGIUNTO IN TAB. DI LAVORO
	$('body').on('click', '#salva-consumo-ordine', function (e) {
		e.preventDefault();

		var idOrdineProduzione = $('#con_IdProduzione').val();

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

		// Se ho anche solo un errore mi fermo qui
		if (errori > 0) {
			swal({
				title: 'ATTENZIONE!',
				text: 'Compilare tutti i campi obbligatori contrassegnati con *',
				icon: 'warning',
				button: 'Ho capito',
				closeModal: true,
			});
		} // nessun errore, posso continuare
		else {
			// salvo i dati
			$.post('gestionecommesse.php', {
				azione: 'salva-consumo-ordine',
				data: $('#form-nuovo-consumo').serialize(),
			}).done(function (data) {
				// se è tutto OK
				if (data == 'OK') {
					$('#modal-nuovo-consumo').modal('hide');
					mostraDistintaConsumi(idOrdineProduzione);
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

			//DOPO SERIALIZE DEL FORM: disabilito nuovamente le caselle contenenti 'id prodotto' e 'numero riga'
			$('#form-nuovo-consumo #cmp_Componente').prop('disabled', true);
		}

		return false;
	});

	// GESTIONE COMMESSE - DISTINTA COMPONENTI (TAB. DI LAVORO): CANCELLAZIONE COMPONENTE DA TAB. DI LAVORO
	$('body').on('click', 'a.cancella-consumo-ordine', function (e) {
		e.preventDefault();

		var idConsumo = $(this).data('id-consumo');
		var idRisorsa = $(this).data('id-risorsa');
		var idOrdineProduzione = $(this).data('id-ordine-produzione');

		swal({
			title: 'ATTENZIONE!',
			text: 'Confermi RIMOZIONE del consumo in oggetto?',
			icon: 'warning',
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
				$.post('gestionecommesse.php', {
					azione: 'cancella-consumo-ordine',
					idRisorsa: idRisorsa,
					idProduzione: idOrdineProduzione,
					idConsumo: idConsumo,
				}).done(function (data) {
					swal.close();
					mostraDistintaConsumi(idOrdineProduzione);
				});
			} else {
				swal.close();
			}
		});
		return false;
	});

	// --------------------------------------------- OPERAZIONI SU EVENTI VISUALIZZATI SUL GANTT  ---------------------------------------------

	// GESTIONE COMMESSE - DETTAGLI EVENTO GANTT: ABILITO/DISABILITO IL PULSANTE DI SALVATAGGIO, IN BASE ALL'EVENTUALE MODIFICA DEI DATI
	$(
		'#form-dettagli-ordine #DataOraProduzioneGantt, #form-dettagli-ordine #DataOraFineTeoricaGantt, #form-dettagli-ordine #op_StatoGantt'
	).on('change', function () {
		$('#salva-lavoro-gantt').prop('disabled', false);
	});

	// GESTIONE COMMESSE - DETTAGLI EVENTO GANTT: SU EVENTO DI MODIFICA DELLA DATA DI INIZIO, AGGIORNO QUELLA TEORICA DI FINE
	$('#form-dettagli-ordine #DataOraProduzioneGantt').on(
		'keyup change',
		function () {
			var tempVelLinea = $(
				'#form-dettagli-ordine #vel_VelocitaTeoricaLineaGantt'
			).val();
			var qtaDaProdurre = $(
				'#form-dettagli-ordine #op_QtaDaProdurreValGantt'
			).val();
			var dataOraProduzione = $(
				'#form-dettagli-ordine #DataOraProduzioneGantt'
			).val();

			if (
				tempVelLinea != '' &&
				tempVelLinea != 0 &&
				qtaDaProdurre != '' &&
				dataOraProduzione != ''
			) {
				var qtaDaProdurreFloat = parseFloat(qtaDaProdurre);
				//Visualizzazione dati ordine produzione selezionato
				$.post('gestionecommesse.php', {
					azione: 'aggiorna-data-fine',
					qtaDaProdurre: qtaDaProdurreFloat,
					velocitaTeoricaLinea: tempVelLinea,
					dataProduzione: dataOraProduzione,
				}).done(function (data) {
					$('#form-dettagli-ordine #DataOraFineTeoricaGantt').val(data);
				});
			} else {
			}
		}
	);

	// GESTIONE COMMESSE - DETTAGLI EVENTO GANTT: SALVA DATI MODIFICATI NEL POPUP
	$('body').on('click', '#salva-lavoro-gantt', function (e) {
		e.preventDefault();

		var dataOraProduzione = $(
			'#form-dettagli-ordine #DataOraProduzioneGantt'
		).val();
		var dataOraFineTeorica = $(
			'#form-dettagli-ordine #DataOraFineTeoricaGantt'
		).val();
		var idProduzione = $('#form-dettagli-ordine #op_IdProduzioneGantt').val();
		var statoOrdine = $('#form-dettagli-ordine #op_StatoGantt').val();

		$.post('timelineordini.php', {
			azione: 'aggiorna-tempi-lavoro-gantt',
			dataOraProduzione: dataOraProduzione,
			dataOraFineTeorica: dataOraFineTeorica,
			idProduzione: idProduzione,
			statoOrdine: statoOrdine,
		}).done(function (data) {
			if (data == 'OK') {
				// Aggiorno reload dei dati del grafico
				reloadDataGantt();
				visualizzaElencoOrdini();
			} else {
				swal({
					title: 'ATTENZIONE!',
					text: 'Errore in modifica dati commessa da Gantt.',
					icon: 'warning',
					button: 'Ho capito',

					closeModal: true,
				});
			}
		});

		return false;
	});

	// --------------------------------------------- RIPRESA DI UN ORDINE GIA' ESEGUITO  ---------------------------------------------

	// RIPRESA COMMESSA DI PRODUZIONE: RIPRENDI ESECUZIONE DI UN COMMESSA GIA' ESEGUITO
	$('body').on('click', '#riprendi-ordine-parziale', function (e) {
		e.preventDefault();

		var idOrdineProduzione = $(this).data('id-ordine-produzione');
		var progressivoParziale = $(this).data('id-progressivo-parziale');

		swal({
			title: 'ATTENZIONE!',
			text: "Desideri RIPRENDERE l'ordine " + idOrdineProduzione + '?',
			icon: 'warning',
			showCancelButton: true,
			buttons: {
				cancel: {
					text: 'ANNULLA',
					value: null,
					visible: true,
					className: 'btn btn-secondary',
					closeModal: true,
				},
				confirm: {
					text: 'CONFERMA',
					value: true,
					visible: true,
					className: 'btn btn-success',
					closeModal: true,
				},
			},
		}).then((procedi) => {
			if (procedi) {
				// Recupero le informazioni relative all'ordine selezionat e da riprendere
				$.post('statoordini.php', {
					azione: 'recupera-ordine-ripreso',
					idProduzione: idOrdineProduzione,
					progressivoParziale: progressivoParziale,
				}).done(function (data) {
					var dati = JSON.parse(data);

					// recupero valori per input-text
					for (var chiave in dati) {
						if (dati.hasOwnProperty(chiave)) {
							$('#form-riprendi-ordine')
								.find('input#' + chiave)
								.val(dati[chiave]);
						}
						$('.unita_misura').html(' [' + dati['rop_UdmSigla'] + ']');
					}

					$('#modal-riprendi-ordine').modal('show');
				});
			} else {
				swal.close();
			}
		});
		return false;
	});

	// RIPRESA COMMESSA DI PRODUZIONE: CREO UNA COPIA DELL'COMMESSA IN OGGETTO
	$('body').on('click', '#salva-ordine-ripreso', function (e) {
		e.preventDefault();

		var idProduzione = $('#rop_IdProduzione').val();
		var idProdotto = $('#rop_IdProdotto').val();
		var velocitaTeoricaLinea = $('#rop_VelocitaTeorica').val();
		var idLineaProduzione = $('#rop_IdLinea').val();

		// Eseguo memorizzazione del nuovo ordine
		$.post('gestioneordinisemplificata.php', {
			azione: 'salva-ordine-ripreso',
			data: $('#form-riprendi-ordine').serialize(),
		}).done(function (data) {
			// Se l'inserimento è andato a buon fine, procedo a inserire la relativa distinta macchine (INSERIMENTO IMPLICITO)
			if (data == 'OK') {
				// Inizializzazione implicita della distinta macchine
				$.post('gestioneordinisemplificata.php', {
					azione: 'inizializza-distinte',
					idLineaProduzione: idLineaProduzione,
					idProduzione: idProduzione,
					idProdotto: idProdotto,
				}).done(function (data) {
					if (data != 'OK') {
						swal({
							title: 'Operazione non eseguita.',
							text: data,
							icon: 'warning',
							button: 'Ho capito',
							closeModal: true,
						});
					} else {
						$('#modal-ordine-produzione').modal('hide');

						// ricarico la datatable per vedere le modifiche
						visualizzaElencoOrdini(g_idStatoOrdine);
					}
				});

				// Svuoto tabelle di visualizzazione distinte, componenti e casi
				$('#tabellaDati-distinta-risorse').dataTable().fnClearTable();
				$('#tabellaDati-distinta-componenti').dataTable().fnClearTable();
				$('#tabellaDati-casi-produzione').dataTable().fnClearTable();

				// Svuoto variabili del form
				$('#form-riprendi-ordine')[0].reset();
				$('#modal-riprendi-ordine').modal('hide');

				// Imposto la visualizzazione degli ordini MEMO (stato = 1) sulla paginae ricarico i dati  della tabella
				visualizzaElencoOrdini(g_idStatoOrdine);
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

		return false;
	});
})(jQuery);
