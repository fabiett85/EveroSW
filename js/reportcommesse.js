(function ($) {
	'use strict';

	// VARIABILI GLOBALI
	var dataInizio;
	var dataFine;

	var tabellaCommesse;

	// Genero data e ora per pre-inizializzare il campo
	var today = moment();
	var momentInizio = moment().subtract(6, 'months');

	// Funzione per visualizzazione tabella 'COMMESSE CHIUSE'
	function mostraCommesse() {
		tabellaCommesse.ajax.url(
			'reportcommesse.php?azione=mostra&dataInizio=' + dataInizio + '&dataFine=' + dataFine
		);
		tabellaCommesse.ajax.reload();
	}

	$(function () {
		// impostazione variabile DATA INIZIO TRACCIATE
		if (sessionStorage.getItem('rptCom_DataInizio') === null) {
			dataInizio = momentInizio.format('YYYY-MM-DD');
		} else {
			dataInizio = sessionStorage.getItem('rptCom_DataInizio');
		}
		$('#rptCom_DataInizio').val(dataInizio);

		// impostazione variabile DATA FINE TRACCIATE
		if (sessionStorage.getItem('rptCom_DataFine') === null) {
			dataFine = today.format('YYYY-MM-DD');
		} else {
			dataFine = sessionStorage.getItem('rptCom_DataFine');
		}
		$('#rptCom_DataFine').val(dataFine);

		$.fn.dataTable.moment('DD/MM/YYYY - HH:mm');

		// DATATABLE COMMESSE COMPLETATE (STATO = 'CHIUSO')
		tabellaCommesse = $('#tabellaCommesse').DataTable({
			aLengthMenu: [
				[8, 16, 24, 32, 100, -1],
				[8, 16, 24, 32, 100, 'Tutti'],
			],
			iDisplayLength: 8,

			order: [[8, 'desc']],
			ajax: {
				url:
					'reportcommesse.php?azione=mostra&dataInizio=' +
					dataInizio +
					'&dataFine=' +
					dataFine,
				dataSrc: '',
			},
			columns: [
				{ data: 'IdProduzione' },
				{ data: 'Prodotto' },
				{ data: 'Lotto' },
				{ data: 'DescrizioneLinea' },
				{ data: 'QtaRichiesta' },
				{ data: 'QtaConforme' },
				{ data: 'QtaScarti' },
				{ data: 'DataOraInizio' },
				{ data: 'DataOraFine' },
				{ data: 'TTotale' },
				{ data: 'Downtime' },
				{ data: 'Oee' },
			],
			columnDefs: [
				{
					width: '6%',
					targets: [2, 4, 5, 6, 9, 10, 11],
				},
				{
					width: '10%',
					targets: [0, 1, 3, 7, 8],
				},
			],

			language: linguaItaliana,
			autoWidth: false,

			buttons: [
				{
					extend: 'excel',
					text: 'EXCEL',
					className: 'btn-success',
					filename: buttonFilename('ReportCommesse', dataInizio, dataFine),
					title: buttonTitle('REPORT COMMESSE', dataInizio, dataFine),
					exportOptions: {
						columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
					},
				},
				{
					extend: 'pdf',
					text: 'PDF',
					className: 'btn-danger',
					filename: buttonFilename('ReportCommesse', dataInizio, dataFine),
					title: buttonTitle('REPORT COMMESSE', dataInizio, dataFine),
					orientation: 'landscape',
					download: 'open',
					exportOptions: {
						columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
					},
				},
			],
			dom:
				"<'row'<'col-sm-12 col-md-6 d-flex justify-content-left align-items-center'lB><'col-sm-12 col-md-6'f>>" +
				"<'row'<'col-sm-12'tr>>" +
				"<'row'<'col-sm-12 col-md-6'i><'col-sm-12 col-md-6'p>>",
		});


		// OP. SCHEDULATE (OGNI 10 SECONDI): REFRESH DELLE TABELLE IN BASE AL TAB IN CUI MI TROVO
		setInterval(function () {
			mostraCommesse();
		}, 10000);
	});

	//  SULLA PERDITA DEL FOCUS DEI CAMPI DATA, AGGIORNO I DATI VISUALIZZATI DAL GRAFICO
	$('#rptCom_DataInizio, #rptCom_DataFine').on('blur', function () {
		dataInizio = $('#rptCom_DataInizio').val();
		dataFine = $('#rptCom_DataFine').val();

		sessionStorage.setItem('rptCom_DataInizio', dataInizio);
		sessionStorage.setItem('rptCom_DataFine', dataFine);

		mostraCommesse();

		if (dataFine < dataInizio) {
			swal({
				title: 'ATTENZIONE!',
				text: 'La data di inizio periodo è oltre la data di fine.',
				icon: 'warning',
			});
		}
	});

	// ESPORTAZIONE RENDIMENTO MEDIO RISORSE (DETTAGLIO PER PRODOTTO)
	$('#rptCom-stampa-report').on('click', function () {
		var dataReport = today.format('YYYYMMDD');
		var dataReportIntestazione = today.format('DD/MM/YYYY');
		var nomeFilePDF = '';

		var valoriRiga = [];
		var valoriTabella = [];

		// Credo il documento
		var doc = new jspdf.jsPDF({ orientation: 'landscape' });
		const pageWidth = doc.internal.pageSize.width;
		const pageHeight = doc.internal.pageSize.height;

		var stringaInizio = moment(dataInizio).format('DD/MM/YYYY');
		var stringaFine = moment(dataFine).format('DD/MM/YYYY');

		// Scrittura intestazione report
		doc.setFontSize(10);
		doc.text(pageWidth - 60, 10, 'Report in data: ' + dataReportIntestazione);
		doc.text(10, 10, 'RIEPILOGO COMMESSE');
		doc.text(10, 15, 'Periodo considerato: ' + stringaInizio + ' - ' + stringaFine);

		var ricerca = $('.dataTables_filter input').val();
		if (ricerca) {
			doc.text(10, 20, 'Ricerca per : ' + ricerca);
		}

		var dati = tabellaCommesse.rows({ search: 'applied' }).data().toArray();

		// Se ho dati nel periodo selezionato
		if (dati.length > 0) {
			// Definisco il nome del report
			nomeFilePDF = 'Rpt' + dataReport + '_RiepilogoCommesse';

			// Conversione: array di array associativi --> array di array
			// Scorro il file JSON dei dati ottenuti (Array di array associativi) iterando sui vari array associativi
			for (var i = 0; i < dati.length; i++) {
				// Aggiungo ogni dato dell'array associativo all'array dei risultati
				$.each(dati[i], function (key, value) {
					valoriRiga.push(String(value));
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
							content: 'RIEPILOGO COMMESSE',
							colSpan: 12,
							styles: { halign: 'left', fillColor: [22, 160, 133] },
						},
					],
					[
						'Commessa',
						'Prodotto',
						'Lotto',
						'Linea',
						'Da prod.',
						'Conformi',
						'Scarti',
						'Data inizio',
						'Data fine',
						'Tempo tot',
						'Downtime',
						'OEE [%]',
					],
				],
				body: valoriTabella,
				theme: 'grid',
				margin: { horizontal: 10 },
				styles: { fontSize: 7 },
				rowPageBreak: 'avoid',
				startY: 25,
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
			});
		}
	});
})(jQuery);
