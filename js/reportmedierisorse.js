var g_rptRis_DataInizio;
var g_rptRis_DataFine;

var g_rptRisMedie_DataInizio;
var g_rptRisMedie_DataFine;

(function ($) {
	'use strict';

	var tabellaDatiOEEMediGeneraliRisorse;
	var tabellaDatiOEEMediRisorse;

	var g_rptRisMedieGenerali_idRisorsa;
	var g_rptRisMedie_idRisorsa;
	var g_rptRisMedie_idProdotto;

	var g_rptMis_dataOdierna = moment().format('YYYY-MM-DD');

	// OEE MEDI RISORSE: VISUALIZZA TABELLA OEE MEDI
	function rptRisMedieGeneraliMostraTabellaOEE() {
		tabellaDatiOEEMediGeneraliRisorse.ajax.url(
			'reportmedierisorse.php?azione=rptRis-mostra-OEE-medi-generali&idRisorsa=' +
				g_rptRisMedieGenerali_idRisorsa +
				'&dataInizio=' +
				g_rptRis_DataInizio +
				'&dataFine=' +
				g_rptRis_DataFine
		);
		tabellaDatiOEEMediGeneraliRisorse.ajax.reload();
	}

	// OEE MEDI RISORSE: FUNZIONE PER POPOLAMENTO SELECT PRODOTTI
	function rptRisMedieMostraTabellaOEE() {
		tabellaDatiOEEMediRisorse.ajax.url(
			'reportmedierisorse.php?azione=rptRis-mostra-OEE-medi&idRisorsa=' +
				g_rptRisMedie_idRisorsa +
				'&idProdotto=' +
				g_rptRisMedie_idProdotto +
				'&dataInizio=' +
				g_rptRisMedie_DataInizio +
				'&dataFine=' +
				g_rptRisMedie_DataFine
		);
		tabellaDatiOEEMediRisorse.ajax.reload();
	}

	$(function () {
		//VISUALIZZA DATATABLE SOTTOCATEGORIA
		tabellaDatiOEEMediGeneraliRisorse = $('#rptRis-OEE-medi-generali').DataTable({
			order: [[2, 'desc']],
			aLengthMenu: [
				[6, 12, -1],
				[6, 12, 'Tutti'],
			],
			iDisplayLength: 6,

			columns: [
				{ data: 'DescrizioneRisorsa' },
				{ data: 'DMedioRisorsa' },
				{ data: 'EMedioRisorsa' },
				{ data: 'QMedioRisorsa' },
				{ data: 'OEEMedioRisorsa' },
				{ data: 'OEEMiglioreRisorsa' },
			],
			columnDefs: [
				{
					className: 'td-d',
					targets: [1],
				},
				{
					className: 'td-e',
					targets: [2],
				},
				{
					className: 'td-q',
					targets: [3],
				},
				{ className: 'td-oee', targets: [4, 5] },
			],
			language: linguaItaliana,
			ajax: {
				url:
					'reportmedierisorse.php?azione=rptRis-mostra-OEE-medi-generali&idRisorsa=' +
					g_rptRisMedieGenerali_idRisorsa +
					'&dataInizio=' +
					g_rptRis_DataInizio +
					'&dataFine=' +
					g_rptRis_DataFine,
				dataSrc: '',
			},
			//"dom": "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4'l><'col-sm-8'p>>"
		});

		//VISUALIZZA DATATABLE SOTTOCATEGORIA
		tabellaDatiOEEMediRisorse = $('#rptRis-OEE-medi').DataTable({
			order: [[2, 'desc']],
			aLengthMenu: [
				[6, 12, -1],
				[6, 12, 'Tutti'],
			],
			iDisplayLength: 6,

			columns: [
				{ data: 'DescrizioneRisorsa' },
				{ data: 'DescrizioneProdotto' },
				{ data: 'DMedioRisorsa' },
				{ data: 'EMedioRisorsa' },
				{ data: 'QMedioRisorsa' },
				{ data: 'OEEMedioRisorsa' },
				{ data: 'OEEMiglioreRisorsa' },
			],
			columnDefs: [
				{
					className: 'td-d',
					targets: [2],
				},
				{
					className: 'td-e',
					targets: [3],
				},
				{
					className: 'td-q',
					targets: [4],
				},
				{ className: 'td-oee', targets: [5, 6] },
			],
			language: linguaItaliana,

			ajax: {
				url:
					'reportmedierisorse.php?azione=rptRis-mostra-OEE-medi&idRisorsa=' +
					g_rptRisMedie_idRisorsa +
					'&idProdotto=' +
					g_rptRisMedie_idProdotto +
					'&dataInizio=' +
					g_rptRisMedie_DataInizio +
					'&dataFine=' +
					g_rptRisMedie_DataFine,
				dataSrc: '',
			},
			//"dom": "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4'l><'col-sm-8'p>>"
		});

		// PER POST REFRESH: MEMORIZZO TAB ATTUALMENTE MOSTRATO PER RIPRISTINO VISUALIZZAZIONE DELLO STESSO IN CASO DI REFRESH (TAB ELENCHI DETTAGLIO)
		$('#tab-medie-risorse a[data-toggle="tab"]').on('show.bs.tab', function (e) {
			sessionStorage.setItem('activeTab_medieRisorse', $(e.target).attr('href'));

			var tabSelezionato = $(e.target).attr('href');

			if (tabSelezionato == '#medie-risorse-generali') {
				$('.card-title').html('RENDIMENTO MEDIO MACCHINE - GENERALE');

				// impostazione variabile
				if (sessionStorage.getItem('rptRisMedieGenerali_idRisorsa') === null) {
					g_rptRisMedieGenerali_idRisorsa = '%';
				} else {
					g_rptRisMedieGenerali_idRisorsa = sessionStorage.getItem(
						'rptRisMedieGenerali_idRisorsa'
					);
				}
				$('#rptRisMedieGenerali_Risorse').val(g_rptRisMedieGenerali_idRisorsa);
				$('#rptRisMedieGenerali_Risorse').selectpicker('refresh');

				// impostazione variabile
				if (sessionStorage.getItem('rptRis_DataInizio') === null) {
					g_rptRis_DataInizio = g_rptMis_dataOdierna;
				} else {
					g_rptRis_DataInizio = sessionStorage.getItem('rptRis_DataInizio');
				}
				$('#rptRis_DataInizio').val(g_rptRis_DataInizio);

				// impostazione variabile
				if (sessionStorage.getItem('rptRis_DataFine') === null) {
					g_rptRis_DataFine = g_rptMis_dataOdierna;
				} else {
					g_rptRis_DataFine = sessionStorage.getItem('rptRis_DataFine');
				}
				$('#rptRis_DataFine').val(g_rptRis_DataFine);

				rptRisMedieGeneraliMostraTabellaOEE();
			} else if (tabSelezionato == '#medie-risorse-dettaglio') {
				$('.card-title').html('RENDIMENTO MEDIO MACCHINE - PER PRODOTTO');

				// impostazione variabile
				if (sessionStorage.getItem('rptRisMedie_idRisorsa') === null) {
					g_rptRisMedie_idRisorsa = '%';
				} else {
					g_rptRisMedie_idRisorsa = sessionStorage.getItem('rptRisMedie_idRisorsa');
				}
				$('#rptRisMedie_Risorse').val(g_rptRisMedie_idRisorsa);
				$('#rptRisMedie_Risorse').selectpicker('refresh');

				// impostazione variabile id prodotto
				if (sessionStorage.getItem('rptRisMedie_idProdotto') === null) {
					g_rptRisMedie_idProdotto = '%';
				} else {
					g_rptRisMedie_idProdotto = sessionStorage.getItem('rptRisMedie_idProdotto');
				}

				// impostazione variabile
				if (sessionStorage.getItem('rptRisMedie_DataInizio') === null) {
					g_rptRisMedie_DataInizio = g_rptMis_dataOdierna;
				} else {
					g_rptRisMedie_DataInizio = sessionStorage.getItem('rptRisMedie_DataInizio');
				}
				$('#rptRisMedie_DataInizio').val(g_rptRisMedie_DataInizio);

				// impostazione variabile
				if (sessionStorage.getItem('rptRisMedie_DataFine') === null) {
					g_rptRisMedie_DataFine = g_rptMis_dataOdierna;
				} else {
					g_rptRisMedie_DataFine = sessionStorage.getItem('rptRisMedie_DataFine');
				}
				$('#rptRisMedie_DataFine').val(g_rptRisMedie_DataFine);

				//popolo select prodotti
				$.post('reportmedierisorse.php', {
					azione: 'rptRisMedie-carica-select-prodotti',
					idRisorsa: g_rptRisMedie_idRisorsa,
					idProdotto: g_rptRisMedie_idProdotto,
				}).done(function (data) {
					$('#rptRisMedie_Prodotti').html(data);
					$('#rptRisMedie_Prodotti').selectpicker('refresh');
					g_rptRisMedie_idProdotto = $('#rptRisMedie_Prodotti').val();

					rptRisMedieMostraTabellaOEE();
				});
			}
		});
		var activeTab_medieRisorse = sessionStorage.getItem('activeTab_medieRisorse');
		if (activeTab_medieRisorse) {
			$('#tab-medie-risorse a[href="' + activeTab_medieRisorse + '"]').tab('show');
		} else {
			$('#tab-medie-risorse a[href="#medie-risorse-generali"]').tab('show');
		}
	});

	//  ALLA VARIAZIONE DEL
	$('#rptRisMedieGenerali_Risorse').on('change', function () {
		//
		g_rptRisMedieGenerali_idRisorsa = $('#rptRisMedieGenerali_Risorse').val();

		// memorizzo nelle variabili di sessione i valori recuperati
		sessionStorage.setItem('rptRisMedieGenerali_idRisorsa', g_rptRisMedieGenerali_idRisorsa);

		//
		rptRisMedieGeneraliMostraTabellaOEE();
	});

	//  ALLA VARIAZIONE DEL
	$('#rptRisMedie_Risorse, #rptRisMedie_Prodotti').on('change', function () {
		//
		g_rptRisMedie_idRisorsa = $('#rptRisMedie_Risorse').val();
		g_rptRisMedie_idProdotto = $('#rptRisMedie_Prodotti').val();

		// memorizzo nelle variabili di sessione i valori recuperati
		sessionStorage.setItem('rptRisMedie_idRisorsa', g_rptRisMedie_idRisorsa);
		sessionStorage.setItem('rptRisMedie_idProdotto', g_rptRisMedie_idProdotto);

		//
		rptRisMedieMostraTabellaOEE();
	});

	$('#rptRis_DataInizio, #rptRis_DataFine, #rptRisMedie_DataInizio, #rptRisMedie_DataFine').on(
		'blur',
		function () {
			var nome = $(this).attr('id');
			window['g_' + nome] = $(this).val();
			sessionStorage.setItem(nome, window['g_' + nome]);
			var prefisso = nome.split('_')[0];
			var dataInizio = $('#' + prefisso + '_DataInizio').val();
			var dataFine = $('#' + prefisso + '_DataFine').val();
			if (dataFine >= dataInizio) {
				if (prefisso == 'rptRis') {
					rptRisMedieGeneraliMostraTabellaOEE();
				} else {
					rptRisMedieMostraTabellaOEE();
				}
			} else {
				swal({
					title: 'ATTENZIONE!',
					text: 'La data di inizio periodo è oltre la data di fine.',
					icon: 'warning',
					button: 'Ho capito',

					closeModal: true,
				});
			}
		}
	);

	// ESPORTAZIONE RENDIMENTO MEDIO RISORSE (GENERALE)
	$('#rptRisMedieGenerali-stampa-report').on('click', function () {
		// Ricavo la data attuale per generazione nome report
		var today = new Date();
		var dd = today.getDate();
		var mm = today.getMonth() + 1; //January is 0!
		var yyyy = today.getFullYear();
		if (dd < 10) {
			dd = '0' + dd;
		}
		if (mm < 10) {
			mm = '0' + mm;
		}

		var dataReport = yyyy + '' + mm + '' + dd;
		var dataReportIntestazione = dd + '/' + mm + '/' + yyyy;
		var nomeFilePDF = '';
		var descrizioneLinea = $('#rptRisMedieGenerali_Risorse option:selected').text();

		var valoriRiga = [];
		var valoriTabella = [];

		// Credo il documento
		var doc = new jspdf.jsPDF({ orientation: 'landscape' });
		const pageWidth = doc.internal.pageSize.width;
		const pageHeight = doc.internal.pageSize.height;

		// Scrittura intestazione report
		doc.setFontSize(10);
		doc.text(pageWidth - 60, 10, 'Report in data: ' + dataReportIntestazione);
		doc.text(10, 10, 'Risorsa selezionata: ' + descrizioneLinea);

		// Recupero i dati degli ordini nel periodo
		$.post('reportmedierisorse.php', {
			azione: 'rptRis-mostra-OEE-medi-generali',
			idRisorsa: g_rptRisMedieGenerali_idRisorsa,
			dataInizio: g_rptRis_DataInizio,
			dataFine: g_rptRis_DataFine,
		}).done(function (dataAjax) {
			// Se ho dati nel periodo selezionato
			if (dataAjax != 'NO_ROWS') {
				var dati;
				dati = JSON.parse(dataAjax);

				// Definisco il nome del report
				nomeFilePDF = 'Rpt' + dataReport + '_OEEMediRisorse_Generale';

				// Conversione: array di array associativi --> array di array
				// Scorro il file JSON dei dati ottenuti (Array di array associativi) iterando sui vari array associativi
				for (var i = 0; i < dati.length; i++) {
					// Aggiungo ogni dato dell'array associativo all'array dei risultati
					$.each(dati[i], function (key, value) {
						valoriRiga.push(value);
					});

					// Terminato il controllo del primo array associativo, aggiungo l'array ottenuto al nuovo array di array dei risultati
					valoriTabella.push(valoriRiga);
					valoriRiga = [];
				}

				// Aggiungo al documento la tabella con la lista ordini nel periodo
				doc.autoTable({
					head: [
						[
							{
								content: 'RENDIMENTO MEDIO RISORSE - GENERALE',
								colSpan: 6,
								styles: { halign: 'left', fillColor: [22, 160, 133] },
							},
						],
						[
							'Linea',
							'Disp. media [%]',
							'Eff. media [%]',
							'Qual. media [%]',
							'OEE medio [%]',
							'OEE migliore [%]',
						],
					],
					body: valoriTabella,
					theme: 'grid',
					margin: { horizontal: 10 },
					styles: { fontSize: 7 },
					rowPageBreak: 'avoid',
					startY: 20,
				});

				// Stampa numerazione pagine
				const pages = doc.internal.getNumberOfPages();

				for (let j = 1; j < pages + 1; j++) {
					let horizontalPos = pageWidth / 2;
					let verticalPos = pageHeight - 5;
					doc.setPage(j);
					doc.text(`Pag. ${j} di ${pages}`, horizontalPos, verticalPos, { align: 'center' });
				}

				// Esporto e salvo il documento creato
				doc.save(nomeFilePDF + '.pdf');
			} else {
				swal({
					title: 'ATTENZIONE!',
					text: 'Nessun dato disponibile nel periodo selezionato.',
					icon: 'warning',
					button: 'Ho capito',

					closeModal: true,
				});
			}
		});
	});

	// ESPORTAZIONE RENDIMENTO MEDIO RISORSE (DETTAGLIO PER PRODOTTO)
	$('#rptRisMedie-stampa-report').on('click', function () {
		// Ricavo la data attuale per generazione nome report
		var today = new Date();
		var dd = today.getDate();
		var mm = today.getMonth() + 1; //January is 0!
		var yyyy = today.getFullYear();
		if (dd < 10) {
			dd = '0' + dd;
		}
		if (mm < 10) {
			mm = '0' + mm;
		}

		var dataReport = yyyy + '' + mm + '' + dd;
		var dataReportIntestazione = dd + '/' + mm + '/' + yyyy;
		var nomeFilePDF = '';
		var descrizioneRisorsa = $('#rptRisMedie_Risorse option:selected').text();
		var descrizioneProdotto = $('#rptRisMedie_Prodotti option:selected').text();

		var valoriRiga = [];
		var valoriTabella = [];

		// Credo il documento
		var doc = new jspdf.jsPDF({ orientation: 'landscape' });
		const pageWidth = doc.internal.pageSize.width;
		const pageHeight = doc.internal.pageSize.height;

		// Scrittura intestazione report
		doc.setFontSize(10);
		doc.text(pageWidth - 60, 10, 'Report in data: ' + dataReportIntestazione);
		doc.text(10, 10, 'Risorsa selezionata: ' + descrizioneRisorsa);
		doc.text(10, 15, 'Prodotto selezionato: ' + descrizioneProdotto);

		// Recupero i dati degli ordini nel periodo
		$.post('reportmedierisorse.php', {
			azione: 'rptRis-mostra-OEE-medi',
			idRisorsa: g_rptRisMedie_idRisorsa,
			idProdotto: g_rptRisMedie_idProdotto,
			dataInizio: g_rptRis_DataInizio,
			dataFine: g_rptRis_DataFine,
		}).done(function (dataAjax) {
			// Se ho dati nel periodo selezionato
			if (dataAjax != 'NO_ROWS') {
				var dati;
				dati = JSON.parse(dataAjax);

				// Definisco il nome del report
				nomeFilePDF = 'Rpt' + dataReport + '_OEEMediRisorse_DettaglioProdotto';

				// Conversione: array di array associativi --> array di array
				// Scorro il file JSON dei dati ottenuti (Array di array associativi) iterando sui vari array associativi
				for (var i = 0; i < dati.length; i++) {
					// Aggiungo ogni dato dell'array associativo all'array dei risultati
					$.each(dati[i], function (key, value) {
						valoriRiga.push(value);
					});

					// Terminato il controllo del primo array associativo, aggiungo l'array ottenuto al nuovo array di array dei risultati
					valoriTabella.push(valoriRiga);
					valoriRiga = [];
				}

				// Aggiungo al documento la tabella con la lista ordini nel periodo
				doc.autoTable({
					head: [
						[
							{
								content: 'RENDIMENTO MEDIO RISORSE - DETTAGLIO PER PRODOTTO',
								colSpan: 7,
								styles: { halign: 'left', fillColor: [22, 160, 133] },
							},
						],
						[
							'Risorsa',
							'Prodotto',
							'Disp. media [%]',
							'Eff. media [%]',
							'Qual. media [%]',
							'OEE medio [%]',
							'OEE migliore [%]',
						],
					],
					body: valoriTabella,
					theme: 'grid',
					margin: { horizontal: 10 },
					styles: { fontSize: 7 },
					rowPageBreak: 'avoid',
					startY: 20,
				});

				// Stampa numerazione pagine
				const pages = doc.internal.getNumberOfPages();

				for (let j = 1; j < pages + 1; j++) {
					let horizontalPos = pageWidth / 2;
					let verticalPos = pageHeight - 5;

					doc.text(`Pag. ${j} di ${pages}`, horizontalPos, verticalPos, { align: 'center' });
				}

				// Esporto e salvo il documento creato
				doc.save(nomeFilePDF + '.pdf');
			} else {
				swal({
					title: 'ATTENZIONE!',
					text: 'Nessun dato disponibile nel periodo selezionato.',
					icon: 'warning',
					button: 'Ho capito',

					closeModal: true,
				});
			}
		});
	});
})(jQuery);
