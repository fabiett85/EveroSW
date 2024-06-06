var MedieGenerali_DataInizio;
var MedieGenerali_DataFine;
var MedieProdotto_DataInizio;
var MedieProdotto_DataFine;

(function ($) {
	'use strict';

	var tabellaDatiOEEMediGeneraliLinee;
	var tabellaDatiOEEMediLinee;

	var MedieGenerali_idLinea;
	var MedieProdotto_idLinea;
	var MedieProdotto_idProdotto;

	var dataOdierna = moment().format('YYYY-MM-DD');

	// Definisco SELECTPICKER
	$('.selectpicker').selectpicker();

	// OEE MEDI LINEE: VISUALIZZA TABELLA OEE MEDI
	function rptLinMedieGeneraliMostraTabellaOEE() {
		tabellaDatiOEEMediGeneraliLinee.ajax.url(
			'reportmedielinee.php?azione=medie-linee&idLinea=' +
				MedieGenerali_idLinea +
				'&dataInizio=' +
				MedieGenerali_DataInizio +
				'&dataFine=' +
				MedieGenerali_DataFine
		);
		tabellaDatiOEEMediGeneraliLinee.ajax.reload();
	}

	// OEE MEDI LINEE DETTAGLIO: FUNZIONE PER POPOLAMENTO SELECT PRODOTTI
	function rptLinMediePopolaSelectProdotti() {
		//popolo select prodotti
		$.post('reportmedielinee.php', {
			azione: 'rptLinMedie-carica-select-prodotti',
			idLineaProduzione: MedieProdotto_idLinea,
			idProdotto: MedieProdotto_idProdotto,
		}).done(function (data) {
			$('#rptLinMedie_Prodotti').html(data);
			$('#rptLinMedie_Prodotti').selectpicker('refresh');
		});
	}

	// OEE MEDI LINEE DETTAGLIO: VISUALIZZA TABELLA OEE MEDI
	function rptLinMedieMostraTabellaOEE() {
		tabellaDatiOEEMediLinee.ajax.url(
			'reportmedielinee.php?azione=medie-prodotto&idLinea=' +
				MedieProdotto_idLinea +
				'&idProdotto=' +
				MedieProdotto_idProdotto +
				'&dataInizio=' +
				MedieProdotto_DataInizio +
				'&dataFine=' +
				MedieProdotto_DataFine
		);
		tabellaDatiOEEMediLinee.ajax.reload();
	}

	$(function () {
		//VISUALIZZA DATATABLE MEDIE GENERALI
		tabellaDatiOEEMediGeneraliLinee = $('#rptLin-OEE-medi-generali').DataTable({
			order: [[2, 'desc']],
			aLengthMenu: [
				[6, 12, -1],
				[6, 12, 'Tutti'],
			],
			iDisplayLength: 6,

			columns: [
				{ data: 'DescrizioneLinea' },
				{ data: 'DMedioLinea' },
				{ data: 'EMedioLinea' },
				{ data: 'QMedioLinea' },
				{ data: 'OEEMedioLinea' },
				{ data: 'OEEMiglioreLinea' },
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
					'reportmedielinee.php?azione=medie-linee&idLinea=' +
					MedieGenerali_idLinea +
					'&dataInizio=' +
					MedieGenerali_DataInizio +
					'&dataFine=' +
					MedieGenerali_DataFine,
				dataSrc: '',
			},
			//"dom": "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4'l><'col-sm-8'p>>"
		});

		//VISUALIZZA DATATABLE CATEGORIA
		tabellaDatiOEEMediLinee = $('#rptLin-OEE-medi').DataTable({
			order: [[2, 'desc']],
			aLengthMenu: [
				[6, 12, -1],
				[6, 12, 'Tutti'],
			],
			iDisplayLength: 6,

			columns: [
				{ data: 'DescrizioneLinea' },
				{ data: 'DescrizioneProdotto' },
				{ data: 'DMedioLinea' },
				{ data: 'EMedioLinea' },
				{ data: 'QMedioLinea' },
				{ data: 'OEEMedioLinea' },
				{ data: 'OEEMiglioreLinea' },
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
					'reportmedielinee.php?azione=medie-prodotto&idLinea=' +
					MedieProdotto_idLinea +
					'&idProdotto=' +
					MedieProdotto_idProdotto +
					'&dataInizio=' +
					MedieProdotto_DataInizio +
					'&dataFine=' +
					MedieProdotto_DataFine,
				dataSrc: '',
			},
			//"dom": "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4'l><'col-sm-8'p>>"
		});

		// PER POST REFRESH: MEMORIZZO TAB ATTUALMENTE MOSTRATO PER RIPRISTINO VISUALIZZAZIONE DELLO STESSO IN CASO DI REFRESH (TAB ELENCHI DETTAGLIO)
		$('#tab-medie-linee a[data-toggle="tab"]').on('show.bs.tab', function (e) {
			sessionStorage.setItem('activeTab_medieLinee', $(e.target).attr('href'));

			var tabSelezionato = $(e.target).attr('href');

			if (tabSelezionato == '#medie-linee-generali') {
				$('.card-title').html('RENDIMENTO MEDIO LINEE - GENERALE');

				// impostazione variabile
				if (sessionStorage.getItem('rptLinMedieGenerali_idLineaProduzione') === null) {
					MedieGenerali_idLinea = '%';
				} else {
					MedieGenerali_idLinea = sessionStorage.getItem(
						'rptLinMedieGenerali_idLineaProduzione'
					);
				}
				$('#rptLinMedieGenerali_LineeProduzione').val(MedieGenerali_idLinea);
				$('#rptLinMedieGenerali_LineeProduzione').selectpicker('refresh');

				// impostazione variabile
				if (sessionStorage.getItem('MedieGenerali_DataInizio') === null) {
					MedieGenerali_DataInizio = dataOdierna;
				} else {
					MedieGenerali_DataInizio = sessionStorage.getItem('MedieGenerali_DataInizio');
				}
				$('#MedieGenerali_DataInizio').val(MedieGenerali_DataInizio);

				// impostazione variabile
				if (sessionStorage.getItem('MedieGenerali_DataFine') === null) {
					MedieGenerali_DataFine = dataOdierna;
				} else {
					MedieGenerali_DataFine = sessionStorage.getItem('MedieGenerali_DataFine');
				}
				$('#MedieGenerali_DataFine').val(MedieGenerali_DataFine);

				rptLinMedieGeneraliMostraTabellaOEE();
			} else if (tabSelezionato == '#medie-linee-dettaglio') {
				$('.card-title').html('RENDIMENTO MEDIO LINEE - PER PRODOTTO');

				// impostazione variabile
				if (sessionStorage.getItem('rptLinMedie_idLineaProduzione') === null) {
					MedieProdotto_idLinea = '%';
				} else {
					MedieProdotto_idLinea = sessionStorage.getItem('rptLinMedie_idLineaProduzione');
				}
				$('#rptLinMedie_LineeProduzione').val(MedieProdotto_idLinea);
				$('#rptLinMedie_LineeProduzione').selectpicker('refresh');

				// impostazione variabile id prodotto
				if (sessionStorage.getItem('rptLinMedie_Prodotti') === null) {
					MedieProdotto_idProdotto = '%';
				} else {
					MedieProdotto_idProdotto = sessionStorage.getItem('rptLinMedie_Prodotti');
				}
				// impostazione variabile
				if (sessionStorage.getItem('MedieProdotto_DataInizio') === null) {
					MedieProdotto_DataInizio = dataOdierna;
				} else {
					MedieProdotto_DataInizio = sessionStorage.getItem('MedieProdotto_DataInizio');
				}
				$('#MedieProdotto_DataInizio').val(MedieProdotto_DataInizio);

				// impostazione variabile
				if (sessionStorage.getItem('MedieProdotto_DataFine') === null) {
					MedieProdotto_DataFine = dataOdierna;
				} else {
					MedieProdotto_DataFine = sessionStorage.getItem('MedieProdotto_DataFine');
				}
				$('#MedieProdotto_DataFine').val(MedieProdotto_DataFine);

				//popolo select prodotti
				$.post('reportmedielinee.php', {
					azione: 'rptLinMedie-carica-select-prodotti',
					idLineaProduzione: MedieProdotto_idLinea,
					idProdotto: MedieProdotto_idProdotto,
				}).done(function (data) {
					$('#rptLinMedie_Prodotti').html(data);
					$('#rptLinMedie_Prodotti').selectpicker('refresh');
					MedieProdotto_idProdotto = $('#rptLinMedie_Prodotti').val();

					rptLinMedieMostraTabellaOEE();
				});
			}
		});
		var activeTab_medieLinee = sessionStorage.getItem('activeTab_medieLinee');
		if (activeTab_medieLinee) {
			$('#tab-medie-linee a[href="' + activeTab_medieLinee + '"]').tab('show');
		} else {
			$('#tab-medie-linee a[href="#medie-linee-generali"]').tab('show');
		}
	});

	//  ALLA VARIAZIONE DEL
	$('#rptLinMedieGenerali_LineeProduzione').on('change', function () {
		//
		MedieGenerali_idLinea = $('#rptLinMedieGenerali_LineeProduzione').val();

		// memorizzo nelle variabili di sessione i valori recuperati
		sessionStorage.setItem('rptLinMedieGenerali_idLineaProduzione', MedieGenerali_idLinea);

		rptLinMedieGeneraliMostraTabellaOEE();
	});

	//  ALLA VARIAZIONE DEL
	$('#rptLinMedie_LineeProduzione, #rptLinMedie_Prodotti').on('change', function () {
		//
		MedieProdotto_idLinea = $('#rptLinMedie_LineeProduzione').val();
		MedieProdotto_idProdotto = $('#rptLinMedie_Prodotti').val();

		// memorizzo nelle variabili di sessione i valori recuperati
		sessionStorage.setItem('rptLinMedie_idLineaProduzione', MedieProdotto_idLinea);
		sessionStorage.setItem('rptLinMedie_idProdotto', MedieProdotto_idProdotto);

		rptLinMediePopolaSelectProdotti(MedieProdotto_idLinea, MedieProdotto_idProdotto);
		rptLinMedieMostraTabellaOEE();
	});

	$(
		'#MedieGenerali_DataInizio, #MedieGenerali_DataFine, #MedieProdotto_DataInizio, #MedieProdotto_DataFine'
	).on('blur', function () {
		var nome = $(this).attr('id');
		window[nome] = $(this).val();
		sessionStorage.setItem(nome, window[nome]);
		var prefisso = nome.split('_')[0];
		var dataInizio = $('#' + prefisso + '_DataInizio').val();
		var dataFine = $('#' + prefisso + '_DataFine').val();
		if (dataFine >= dataInizio) {
			if (prefisso == 'MedieGenerali') {
				rptLinMedieGeneraliMostraTabellaOEE();
			} else {
				rptLinMediePopolaSelectProdotti();
				rptLinMedieMostraTabellaOEE();
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
	});

	// ESPORTAZIONE RENDIMENTO MEDIO LINEE (GENERALE)
	$('#rptLinMedieGenerali-stampa-report').on('click', function () {
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
		var descrizioneLinea = $('#rptLinMedieGenerali_LineeProduzione option:selected').text();

		var valoriRiga = [];
		var valoriTabella = [];

		// Credo il documento
		var doc = new jspdf.jsPDF({ orientation: 'landscape' });
		const pageWidth = doc.internal.pageSize.width;
		const pageHeight = doc.internal.pageSize.height;

		// Scrittura intestazione report
		doc.setFontSize(10);
		doc.text(pageWidth - 60, 10, 'Report in data: ' + dataReportIntestazione);
		doc.text(10, 10, 'Linea selezionata: ' + descrizioneLinea);

		// Recupero i dati degli ordini nel periodo
		$.post('reportmedielinee.php', {
			azione: 'rptLin-mostra-OEE-medi-generali',
			idLineaProduzione: MedieGenerali_idLinea,
			dataInizio: MedieGenerali_DataInizio,
			dataFine: MedieGenerali_DataFine,
		}).done(function (dataAjax) {
			// Se ho dati nel periodo selezionato
			if (dataAjax != 'NO_ROWS') {
				var dati;
				dati = JSON.parse(dataAjax);

				// Definisco il nome del report
				nomeFilePDF = 'Rpt' + dataReport + '_OEEMediLinee_Generale';

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
								content: 'RENDIMENTO MEDIO LINEE - GENERALE',
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

	// ESPORTAZIONE RENDIMENTO MEDIO LINEE (DETTAGLIO PER PRODOTTO)
	$('#rptLinMedie-stampa-report').on('click', function () {
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
		var descrizioneLinea = $('#rptLinMedie_LineeProduzione option:selected').text();
		var descrizioneProdotto = $('#rptLinMedie_Prodotti option:selected').text();

		var valoriRiga = [];
		var valoriTabella = [];

		// Credo il documento
		var doc = new jspdf.jsPDF({ orientation: 'landscape' });
		const pageWidth = doc.internal.pageSize.width;
		const pageHeight = doc.internal.pageSize.height;

		// Scrittura intestazione report
		doc.setFontSize(10);
		doc.text(pageWidth - 60, 10, 'Report in data: ' + dataReportIntestazione);
		doc.text(10, 10, 'Linea selezionata: ' + descrizioneLinea);
		doc.text(10, 15, 'Prodotto selezionato: ' + descrizioneProdotto);

		// Recupero i dati degli ordini nel periodo
		$.post('reportmedielinee.php', {
			azione: 'rptLin-mostra-OEE-medi',
			idLineaProduzione: MedieProdotto_idLinea,
			idProdotto: MedieProdotto_idProdotto,
			dataInizio: MedieProdotto_DataInizio,
			dataFine: MedieProdotto_DataFine,
		}).done(function (dataAjax) {
			// Se ho dati nel periodo selezionato
			if (dataAjax != 'NO_ROWS') {
				var dati;
				dati = JSON.parse(dataAjax);

				// Definisco il nome del report
				nomeFilePDF = 'Rpt' + dataReport + '_OEEMediLinee_DettaglioProdotto';

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
								content: 'RENDIMENTO MEDIO LINEE - DETTAGLIO PER PRODOTTO',
								colSpan: 7,
								styles: { halign: 'left', fillColor: [22, 160, 133] },
							},
						],
						[
							'Linea',
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
})(jQuery);
