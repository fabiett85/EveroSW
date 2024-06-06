(function ($) {
	var dataInizio;
	var dataFine;
	var tabellaCosti;

	// Genero data e ora per pre-inizializzare il campo
	var today = moment();
	var momentInizio = moment().subtract(6, 'months');

	// FUNZIONE PER RECUPERO DATI GRAFICO E RELATIVA VISUALIZZAZIONE
	function recuperaDatiCostiLavoro() {
		$.post('reportcosti.php', {
			azione: 'recupera-costi',
			dataInizio: dataInizio,
			dataFine: dataFine,
		}).done(function (dataAjax) {
			var dati = JSON.parse(dataAjax);

			tabellaCosti.clear();
			tabellaCosti.rows.add(dati).draw();
		});
	}

	var rptCst_linguaItaliana = {
		processing: 'Caricamento...',
		search: 'Ricerca: ',
		lengthMenu: '_MENU_ righe per pagina',
		zeroRecords: 'Nessun dato disponibile',
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

	// DATATABLE POPOLAMENTO GRAFICO E TABELLA COMMESSE RELATIVI AL TRACCIATE E RISORSA SELEZIONATI
	$(function () {
		// impostazione variabile DATA INIZIO TRACCIATE
		if (sessionStorage.getItem('dataInizio') === null) {
			dataInizio = momentInizio.format('YYYY-MM-DD');
		} else {
			dataInizio = sessionStorage.getItem('dataInizio');
		}
		$('#DataInizio').val(dataInizio);

		// impostazione variabile DATA FINE TRACCIATE
		if (sessionStorage.getItem('dataFine') === null) {
			dataFine = today.format('YYYY-MM-DD');
		} else {
			dataFine = sessionStorage.getItem('dataFine');
		}
		$('#DataFine').val(dataFine);

		$.fn.dataTable.moment('DD/MM/YYYY HH:mm:ss');

		tabellaCosti = $('#tabellaCosti').DataTable({
			aLengthMenu: [
				[5, 10, 20, 50, 100, -1],
				[5, 10, 20, 50, 100, 'Tutti'],
			],
			iDisplayLength: 10,

			order: [0, 'asc'],

			columns: [
				{ data: 'Prodotto' },
				{ data: 'Commessa' },
				{ data: 'OreLavorate' },
				{ data: 'OreManutenzione' },
				{ data: 'QtaConformi' },
				{ data: 'TempoPerProdotto' },
				{ data: 'CostoPerProdotto' },
			],
			columnDefs: [],

			language: rptCst_linguaItaliana,
			buttons: [
				{
					extend: 'excel',
					text: 'EXCEL',
					className: 'btn-success',
					filename: buttonFilename('ReportCosti', dataInizio, dataFine),
					title: buttonTitle('REPORT COSTI', dataInizio, dataFine),
					exportOptions: {
						columns: [':visible'],
					},
				},
				{
					extend: 'pdf',
					text: 'PDF',
					className: 'btn-danger',
					filename: buttonFilename('ReportCosti', dataInizio, dataFine),
					title: buttonTitle('REPORT COSTI', dataInizio, dataFine),
					orientation: 'landscape',
					download: 'open',
					exportOptions: {
						columns: [':visible'],
					},
				},
			],
			dom:
				"<'row'<'col-sm-12 col-md-6 d-flex justify-content-left align-items-center'lB><'col-sm-12 col-md-6'f>>" +
				"<'row'<'col-sm-12'tr>>" +
				"<'row'<'col-sm-12 col-md-6'i><'col-sm-12 col-md-6'p>>",
		});

		recuperaDatiCostiLavoro();
	});

	//  SULLA PERDITA DEL FOCUS DEI CAMPI DATA, AGGIORNO I DATI VISUALIZZATI DAL GRAFICO
	$('#DataInizio, #DataFine').on('blur', function () {
		dataInizio = $('#DataInizio').val();
		dataFine = $('#DataFine').val();

		sessionStorage.setItem('DataInizio', dataInizio);
		sessionStorage.setItem('DataFine', dataFine);

		recuperaDatiCostiLavoro();

		if (dataFine < dataInizio) {
			swal({
				title: 'ATTENZIONE!',
				text: 'La data di inizio periodo è oltre la data di fine.',
				icon: 'warning',
				button: 'Ho capito',

				closeModal: true,
			});
		}
	});

	// ESPORTAZIONE RENDIMENTO MEDIO RISORSE (DETTAGLIO PER PRODOTTO)
	$('#report').on('click', function () {
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

		// add the font to jsPDF
		doc.addFileToVFS('Roboto-Regular.ttf', robotoFont);
		doc.addFont('Roboto-Regular.ttf', 'Roboto-Regular', 'normal');
		doc.setFont('Roboto-Regular');
		doc.setFontSize(10);
		doc.text(pageWidth - 60, 10, 'Report in data: ' + dataReportIntestazione);
		doc.text(10, 10, 'RIEPILOGO COSTI €€€');
		doc.text(10, 15, 'Periodo considerato: ' + stringaInizio + ' - ' + stringaFine);

		var ricerca = $('.dataTables_filter input').val();
		if (ricerca) {
			doc.text(10, 20, 'Ricerca per : ' + ricerca);
		}

		var dati = tabellaCosti.rows({ search: 'applied' }).data().toArray();
		console.log(dati);
		// Se ho dati nel periodo selezionato
		if (dati.length > 0) {
			// Definisco il nome del report
			nomeFilePDF = 'Rpt' + dataReport + '_RiepilogoCosti';

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
							colSpan: 7,
							styles: { halign: 'left', fillColor: [22, 160, 133] },
						},
					],
					[
						'Prodotto',
						'Commessa',
						'Ore Lavorate',
						'Ore Manutenzione',
						'Qta Prodotta',
						'Tempo Per Prodotto',
						'Costo Per Prodotto',
					],
				],
				body: valoriTabella,
				theme: 'grid',
				margin: { horizontal: 10 },
				styles: { fontSize: 7, font: 'Roboto-Regular' },
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
