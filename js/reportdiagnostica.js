// VARIABILI GLOBALI
var g_rptDia_idLineaProduzione;
var g_rptDia_idRisorsa;
var g_DataFine;

// GENERO DATA E ORA ODIERNA
var g_rptDia_DataOdierna = moment('YYYY-MM-DD');

// RIFERIMENTO AL GRAFICO GENERALE ORE IMPRODUTTIVE
var riferimentoGraficoGeneraleDiagnostica = document.getElementById('grDiagnosticaLinee');
var datiGraficoOEEDiagnostica;

// RIFERIMENTO AL GRAFICO DETTAGLIO DOWNTIME RISORSA
var riferimentoGraficoDettaglioDowntimeRisorsa = document.getElementById(
	'grDettaglioDowntimeRisorsa'
);
var datiGraficoGraficoDettaglioDowntime;

// DEFINIZIONE OPZIONI GRAFICO GENERALE ORE IMPRODUTTIVE
var opzioniGraficoGeneraleDiagnostica = {
	tooltips: {
		enabled: false,
	},
	hover: { mode: null },
	layout: {
		padding: {
			left: 25,
			right: 50,
			top: 35,
			bottom: 0,
		},
	},
	legend: {
		display: false,
		position: 'bottom',
		labels: {
			padding: 15,
			boxWidth: 10,
			fontColor: 'black',
		},
	},
	animation: {
		onProgress: function () {
			var chartInstance = this.chart,
				ctx = chartInstance.ctx;

			ctx.font = '0.7rem Arial';

			this.data.datasets.forEach(function (dataset, i) {
				var meta = chartInstance.controller.getDatasetMeta(i);
				meta.data.forEach(function (bar, index) {
					ctx.textAlign = 'center';
					ctx.fillStyle = 'red';
					var data = dataset.data[index] + ' min';
					ctx.fillText(data, bar._model.x + 30, bar._model.y);
				});
			});
		},
	},
	responsive: true,
	scales: {
		xAxes: [
			{
				ticks: {
					autoSkip: false,
					maxRotation: 40,
					minRotation: 40,
					min: 0,
					fontSize: 10,
					fontStyle: 'italic',
					fontColor: 'black',
					padding: 5,
				},
				scaleLabel: {
					display: true,
					fontColor: 'black',
					labelString: 'MINUTI IMPRODUTTIVI NEL PERIODO CONSIDERATO',
					fontSize: 11,
					fontStyle: 'bold',
				},
			},
		],
		yAxes: [
			{
				barPercentage: 0.6,
				categoryPercentage: 0.6,
				ticks: {
					fontSize: 10,
					fontStyle: 'italic',
					fontColor: 'black',
					padding: 15,
				},
				scaleLabel: {
					display: false,
					fontColor: 'black',
					labelString: 'LINEE',
					fontSize: 11,
					fontStyle: 'bold',
				},

				barPercentage: 0.5,
			},
		],
	},
};

// DEFINIZIONE OPZIONI GRAFICO DETTAGLIO DOWNTIME RISORSA
var opzioniGraficoDettaglioDowntimeRisorsa = {
	tooltips: {
		enabled: false,
	},
	layout: {
		padding: {
			left: 25,
			right: 50,
			top: 35,
			bottom: 0,
		},
	},
	legend: {
		display: false,
		position: 'bottom',
		labels: {
			padding: 15,
			boxWidth: 10,
			fontColor: 'black',
		},
	},
	animation: {
		onProgress: function () {
			var chartInstance = this.chart,
				ctx = chartInstance.ctx;

			ctx.font = '0.8rem Arial';

			this.data.datasets.forEach(function (dataset, i) {
				var meta = chartInstance.controller.getDatasetMeta(i);
				meta.data.forEach(function (bar, index) {
					ctx.textAlign = 'center';
					ctx.fillStyle = 'red';
					var data = dataset.data[index] + ' min';
					ctx.fillText(data, bar._model.x + 30, bar._model.y);
				});
			});
		},
	},
	responsive: true,
	scales: {
		xAxes: [
			{
				barPercentage: 1.0,
				categoryPercentage: 1.0,
				ticks: {
					autoSkip: false,
					maxRotation: 40,
					minRotation: 40,
					min: 0,
					fontSize: 11,
					fontStyle: 'italic',
					fontColor: 'black',
					padding: 5,
				},
				scaleLabel: {
					display: true,
					fontColor: 'black',
					labelString: 'MINUTI IMPRODUTTIVI PER TIPOLOGIA DI EVENTO',
					fontSize: 14,
					fontStyle: 'bold',
				},
			},
		],
		yAxes: [
			{
				barPercentage: 1.0,
				categoryPercentage: 1.0,
				ticks: {
					fontSize: 11,
					fontStyle: 'italic',
					fontColor: 'black',
					padding: 15,
				},
				scaleLabel: {
					display: false,
					fontColor: 'black',
					labelString: 'LINEE',
					fontSize: 12,
					fontStyle: 'bold',
				},

				barPercentage: 0.5,
			},
		],
	},
};

// ISTANZIO GRAFICO GENERALE ORE IMPRODUTTIVE
var graficoGeneraleDiagnostica = new Chart(riferimentoGraficoGeneraleDiagnostica, {
	type: 'horizontalBar',
	data: datiGraficoOEEDiagnostica,
	options: opzioniGraficoGeneraleDiagnostica,
});

// ISTANZIO GRAFICO DETTAGLIO DOWNTIME RISORSA
var graficoDettaglioDowntimeRisorsa = new Chart(riferimentoGraficoDettaglioDowntimeRisorsa, {
	type: 'horizontalBar',
	data: datiGraficoGraficoDettaglioDowntime,
	options: opzioniGraficoDettaglioDowntimeRisorsa,
});

// FUNZIONE DI RECUPERO DATI TRACCIATE E RELATIVA VISUALIZZAZIONE IN TABELLA E GRAFICO
function recuperaDatiGraficoOrePerse() {
	var labelsGrDiagnostica = [];
	var datiGrOrePerseDiagnostica = [];

	var descrizioneLineaSelezionata = $('#rptDia_LineeProduzione option:selected').text();
	var descrizioneRisorsaSelezionata = $('#rptDia_RisorseLinea option:selected').text();

	// Se ho una linea e una risorsa selezionate
	if (g_rptDia_idLineaProduzione != null && g_rptDia_idRisorsa != null) {
		// Recupero i dati di diagnostica, secondo i criteri impsotati
		$.post('inc_reportdiagnostica.php', {
			azione: 'rptDia-popola-istogramma-linee',
			idLineaProduzione: g_rptDia_idLineaProduzione,
			idRisorsa: g_rptDia_idRisorsa,
			dataInizioPeriodo: g_DataInizio,
			dataFinePeriodo: g_DataFine,
		}).done(function (dataAjax) {
			var dati = JSON.parse(dataAjax);
			if (dati.length > 0) {
				if (g_rptDia_idRisorsa != '_') {
					graficoGeneraleDiagnostica.options.scales.xAxes[0].scaleLabel.labelString =
						'MINUTI IMPRODUTTIVI - ' + descrizioneRisorsaSelezionata;
					$('#intestazione-th1').text('Tipologia evento');
				} else {
					if (g_rptDia_idLineaProduzione == '_') {
						graficoGeneraleDiagnostica.options.scales.xAxes[0].scaleLabel.labelString =
							'MINUTI IMPRODUTTIVI LINEE ';
						$('#intestazione-th1').text('Linea di produzione');
					} else {
						graficoGeneraleDiagnostica.options.scales.xAxes[0].scaleLabel.labelString =
							'MINUTI IMPRODUTTIVI - ' + descrizioneLineaSelezionata;
						$('#intestazione-th1').text('Macchina');
					}
				}

				dati.forEach(function (entry) {
					labelsGrDiagnostica.push(entry.Label);
					datiGrOrePerseDiagnostica.push(entry.Dati);
				});

				datiGraficoOEEDiagnostica = {
					labels: labelsGrDiagnostica,
					datasets: [
						{
							label: 'Ore Perse',
							data: datiGrOrePerseDiagnostica,
							backgroundColor: [
								'#ff3434',
								'#ff4747',
								'#ff5b5b',
								'#ff6f6f',
								'#ff8282',
								'#ff9696',
								'#ffa9a9',
								'#ffbdbd',
								'#ffd1d1',
							],
							hoverBackgroundColor: [],
						},
					],
				};

				graficoGeneraleDiagnostica.data = datiGraficoOEEDiagnostica;
				graficoGeneraleDiagnostica.update();

				$('#rptDia-tabella-diagnostica').dataTable().fnClearTable();
				$('#rptDia-tabella-diagnostica').dataTable().fnAddData(dati);
			} else {
				if (g_rptDia_idRisorsa != '_') {
					graficoGeneraleDiagnostica.options.scales.xAxes[0].scaleLabel.labelString =
						'MINUTI IMPRODUTTIVI - ' + descrizioneRisorsaSelezionata;
				} else {
					if (g_rptDia_idLineaProduzione == '_') {
						graficoGeneraleDiagnostica.options.scales.xAxes[0].scaleLabel.labelString =
							'MINUTI IMPRODUTTIVI LINEE ';
					} else {
						graficoGeneraleDiagnostica.options.scales.xAxes[0].scaleLabel.labelString =
							'MINUTI IMPRODUTTIVI - ' + descrizioneLineaSelezionata;
					}
				}

				datiGraficoOEEDiagnostica = {
					labels: [],
					datasets: [
						{
							label: 'Ore Perse',
							data: [],
							backgroundColor: [
								'#ff2020',
								'#ff3434',
								'#ff4747',
								'#ff5b5b',
								'#ff6f6f',
								'#ff8282',
								'#ff9696',
								'#ffa9a9',
								'#ffbdbd',
								'#ffd1d1',
							],
							hoverBackgroundColor: [],
						},
					],
				};

				graficoGeneraleDiagnostica.data = datiGraficoOEEDiagnostica;
				graficoGeneraleDiagnostica.update();
				$('#rptDia-tabella-diagnostica').dataTable().fnClearTable();
			}
		});
	}
}

(function ($) {
	'use strict';

	var rptDia_tabellaDatiDiagnostica;

	var rptDia_linguaItaliana = {
		processing: 'Caricamento...',
		search: 'Ricerca: ',
		lengthMenu: '_MENU_ righe per pagina',
		zeroRecords: 'Nessun dato presente',
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

	// DATATABLE COMMESSE TROVATI GRAFICO
	$(function () {
		$.fn.dataTable.moment('DD/MM/YYYY HH:mm:ss');

		// DATATABLE DATI COMMESSA SELEZIONATO
		rptDia_tabellaDatiDiagnostica = $('#rptDia-tabella-diagnostica').DataTable({
			aLengthMenu: [
				[5, 10, -1],
				[5, 10, 'Tutti'],
			],
			iDisplayLength: 5,

			order: [[1, 'desc']],

			columns: [{ data: 'Label' }, { data: 'Dati' }],

			language: rptDia_linguaItaliana,
			info: false,
			//"dom": "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4'l><'col-sm-8'p>>"
		});
	});

	// ****** OPERAZIONI SVOLTE SU COMANDO UTENTE ******

	// AL CLIC SU UNO DEI PUNTI VISUALIZZATI SUL GRAFICO (OEE COMMESSA), RICAVO LABEL E VALORE
	$('body').on('click', '#grDiagnosticaLinee', function (e) {
		var descrizioneLineaSelezionata = $('#rptDia_LineeProduzione option:selected').text();
		var descrizioneRisorsaSelezionata = $('#rptDia_RisorseLinea option:selected').text();

		if (g_rptDia_idRisorsa != '_') {
			graficoDettaglioDowntimeRisorsa.options.scales.xAxes[0].scaleLabel.labelString =
				'CLASSIFICAZIONE ORE DOWNTIME - ' + descrizioneRisorsaSelezionata;

			var labelsGrDettaglioDowntimeRisorsa = [];
			var datiGrDettaglioDowntimeRisorsa = [];

			var activePoints = graficoGeneraleDiagnostica.getElementsAtEventForMode(
				e,
				'point',
				graficoGeneraleDiagnostica.options
			);
			var firstPoint = activePoints[0];
			if (firstPoint != null) {
				var descrizioneRisorsa = $('#rptDia_RisorseLinea option:selected').text();
				var label = graficoGeneraleDiagnostica.data.labels[firstPoint._index];

				$('#modal-diagnostica-risorsa-label-descRisorsa')
					.text(descrizioneRisorsa)
					.css('font-style', 'italic')
					.css('font-weight', 'bold');

				$.post('inc_reportdiagnostica.php', {
					azione: 'rptDia-recupera-dettaglio-downtime-risorsa',
					idRisorsa: g_rptDia_idRisorsa,
					tipoCasoSelezionato: label,
					dataInizioPeriodo: g_DataInizio,
					dataFinePeriodo: g_DataFine,
				}).done(function (dataAjax) {
					if (dataAjax != 'NO_ROWS') {
						var dati = JSON.parse(dataAjax);

						dati.forEach(function (entry) {
							labelsGrDettaglioDowntimeRisorsa.push(entry.Label);
							datiGrDettaglioDowntimeRisorsa.push(entry.Dati);
						});

						datiGraficoGraficoDettaglioDowntime = {
							labels: labelsGrDettaglioDowntimeRisorsa,
							datasets: [
								{
									label: 'Ore Perse',
									data: datiGrDettaglioDowntimeRisorsa,
									backgroundColor: [
										'#ff3434',
										'#ff4747',
										'#ff5b5b',
										'#ff6f6f',
										'#ff8282',
										'#ff9696',
										'#ffa9a9',
										'#ffbdbd',
										'#ffd1d1',
									],
									hoverBackgroundColor: [],
								},
							],
						};

						graficoDettaglioDowntimeRisorsa.data = datiGraficoGraficoDettaglioDowntime;
						graficoDettaglioDowntimeRisorsa.update();
					} else {
						datiGraficoGraficoDettaglioDowntime = {
							labels: [],
							datasets: [
								{
									label: 'Ore Perse',
									data: [],
									backgroundColor: [
										'#ff3434',
										'#ff4747',
										'#ff5b5b',
										'#ff6f6f',
										'#ff8282',
										'#ff9696',
										'#ffa9a9',
										'#ffbdbd',
										'#ffd1d1',
									],
									hoverBackgroundColor: [],
								},
							],
						};

						graficoDettaglioDowntimeRisorsa.data = datiGraficoGraficoDettaglioDowntime;
						graficoDettaglioDowntimeRisorsa.update();
					}
				});

				$('#modal-report-diagnostica-risorsa').modal('show');
			}
		} else if (g_rptDia_idLineaProduzione == '_' && g_rptDia_idRisorsa == '_') {
			var labelsGrDettaglioDowntimeLinea = [];
			var datiGrDettaglioDowntimeLinea = [];

			var activePoints = graficoGeneraleDiagnostica.getElementsAtEventForMode(
				e,
				'point',
				graficoGeneraleDiagnostica.options
			);
			var firstPoint = activePoints[0];
			if (firstPoint != null) {
				var label = graficoGeneraleDiagnostica.data.labels[firstPoint._index];

				graficoDettaglioDowntimeRisorsa.options.scales.xAxes[0].scaleLabel.labelString =
					'CLASSIFICAZIONE ORE DOWNTIME - ' + label;
				$('#modal-diagnostica-risorsa-label-descRisorsa')
					.text(label)
					.css('font-style', 'italic')
					.css('font-weight', 'bold');

				$.post('inc_reportdiagnostica.php', {
					azione: 'rptDia-recupera-dettaglio-downtime-linea',
					descrizioneLineaSelezionata: label,
					dataInizioPeriodo: g_DataInizio,
					dataFinePeriodo: g_DataFine,
				}).done(function (dataAjax) {
					if (dataAjax != 'NO_ROWS') {
						var dati = JSON.parse(dataAjax);

						dati.forEach(function (entry) {
							labelsGrDettaglioDowntimeLinea.push(entry.Label);
							datiGrDettaglioDowntimeLinea.push(entry.Dati);
						});

						datiGraficoGraficoDettaglioDowntime = {
							labels: labelsGrDettaglioDowntimeLinea,
							datasets: [
								{
									label: 'Ore Perse',
									data: datiGrDettaglioDowntimeLinea,
									backgroundColor: [
										'#ff3434',
										'#ff4747',
										'#ff5b5b',
										'#ff6f6f',
										'#ff8282',
										'#ff9696',
										'#ffa9a9',
										'#ffbdbd',
										'#ffd1d1',
									],
									hoverBackgroundColor: [],
								},
							],
						};

						graficoDettaglioDowntimeRisorsa.data = datiGraficoGraficoDettaglioDowntime;
						graficoDettaglioDowntimeRisorsa.update();
					} else {
						datiGraficoGraficoDettaglioDowntime = {
							labels: [],
							datasets: [
								{
									label: 'Ore Perse',
									data: [],
									backgroundColor: [
										'#ff3434',
										'#ff4747',
										'#ff5b5b',
										'#ff6f6f',
										'#ff8282',
										'#ff9696',
										'#ffa9a9',
										'#ffbdbd',
										'#ffd1d1',
									],
									hoverBackgroundColor: [],
								},
							],
						};

						graficoDettaglioDowntimeRisorsa.data = datiGraficoGraficoDettaglioDowntime;
						graficoDettaglioDowntimeRisorsa.update();
					}
				});
				$('#modal-report-diagnostica-risorsa').modal('show');
			}
		}
	});

	//  ALLA VARIAZIONE DELLA LINEA SELEZIONATA...
	$('#rptDia_LineeProduzione').on('change', function () {
		g_rptDia_idLineaProduzione = $('#rptDia_LineeProduzione').val();
		sessionStorage.setItem('rptDia_idLineaProduzione', g_rptDia_idLineaProduzione);

		$.post('inc_reportdiagnostica.php', {
			azione: 'rptDia-carica-select-risorse',
			idLineaProduzione: g_rptDia_idLineaProduzione,
		}).done(function (data) {
			$('#rptDia_RisorseLinea').html(data);
			$('#rptDia_RisorseLinea').selectpicker('refresh');

			g_rptDia_idRisorsa = $('#rptDia_RisorseLinea').val();
			sessionStorage.setItem('rptDia_idRisorsa', g_rptDia_idRisorsa);

			recuperaDatiGraficoOrePerse();
		});
	});

	//  ALLA VARIAZIONE DELLA RISORSA, DELLA DATA INIZIO, O DELLA DATA FINE...
	$('#rptDia_RisorseLinea').on('change', function () {
		// Aggiorno le variabili globali con i valori attualmente selezionati
		g_rptDia_idRisorsa = $('#rptDia_RisorseLinea').val();

		// Memorizzo nelle variabili di sessione i valori recuperati
		sessionStorage.setItem('rptDia_idRisorsa', g_rptDia_idRisorsa);

		// Invoco funzione per recupero dati periodo
		recuperaDatiGraficoOrePerse();
	});

	$('#rptDia_DataInizio, #rptDia_DataFine').on('blur', function () {
		var nome = $(this).attr('id');
		window['g_' + nome + 'Periodo'] = $(this).val();
		sessionStorage.setItem(nome + 'Periodo', window['g_' + nome + 'Periodo']);
		var prefisso = nome.split('_')[0];
		g_DataInizio = $('#' + prefisso + '_DataInizio').val();
		g_DataFine = $('#' + prefisso + '_DataFine').val();
		if (g_DataFine >= g_DataInizio) {
			recuperaDatiGraficoOrePerse();
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

	// ESPORTAZIONE GRAFICO E TABELLA COMMESSE NEL TRACCIATE CONSIDERATO
	$('#rptDia-stampa-report').on('click', function () {
		// Ricavo la data attuale per generazione nome report
		var today = moment();
		var dataReport = today.format('YYYYMMDD');
		var dataReportIntestazione = today.format('DD/MM/YYYY');
		var nomeFilePDF = '';

		var idLinea = $('#rptDia_LineeProduzione').val();
		var idRisorsa = $('#rptDia_RisorseLinea').val();
		var descrizioneLinea = $('#rptDia_LineeProduzione option:selected').text();
		var descrizioneRisorsa = $('#rptDia_RisorseLinea option:selected').text();

		var descrizioneReport = '';

		if (idRisorsa != '_') {
			descrizioneReport = descrizioneRisorsa;
			nomeFilePDF =
				'RptDiagnosiRisorsa' +
				dataReport +
				'_' +
				g_DataInizio +
				'_' +
				g_DataFine;
		} else {
			if (idLinea == '_') {
				descrizioneReport = 'TUTTE LE LINEE';
				nomeFilePDF =
					'RptDiagnosiLinee' +
					dataReport +
					'_' +
					g_DataInizio +
					'_' +
					g_DataFine;
			} else {
				descrizioneReport = descrizioneLinea;
				nomeFilePDF =
					'RptDiagnosiLinea' +
					dataReport +
					'_' +
					g_DataInizio +
					'_' +
					g_DataFine;
			}
		}

		var valoriRiga = [];
		var valoriTabella = [];

		// Credo il documento
		var doc = new jspdf.jsPDF({ orientation: 'landscape' });
		const pageWidth = doc.internal.pageSize.width;
		const pageHeight = doc.internal.pageSize.height;

		// Scrittura intestazione report
		doc.setFontSize(11);
		doc.text(10, 10, 'DIAGNOSTICA ORE IMPRODUTTIVE - CUMULATIVO');
		doc.text(pageWidth - 40, 10, 'Data: ' + dataReportIntestazione);
		doc.setFontSize(9);
		doc.text(10, 15, 'Riferimento: ' + descrizioneReport);
		doc.text(
			10,
			20,
			'Periodo considerato: ' +
				g_DataInizio.substring(8, 10) +
				'/' +
				g_DataInizio.substring(5, 7) +
				'/' +
				g_DataInizio.substring(0, 4) +
				' - ' +
				+g_DataFine.substring(8, 10) +
				'/' +
				g_DataFine.substring(5, 7) +
				'/' +
				g_DataFine.substring(0, 4)
		);

		// Converto il grafico in immagine PNG
		var canvasImg = riferimentoGraficoGeneraleDiagnostica.toDataURL('image/png', 0.8);

		// Costanti per formattazione corretta del grafico
		const imgProps = doc.getImageProperties(canvasImg);
		const pdfWidth = doc.internal.pageSize.getWidth();
		const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;

		// Aggiungo al documento il grafico degli ordini nel periodo
		doc.addImage(canvasImg, 'JPEG', 10, 20, pdfWidth - 20, pdfHeight);

		// Stampa numerazione pagine
		const pages = doc.internal.getNumberOfPages();

		for (let j = 1; j < pages + 1; j++) {
			let horizontalPos = pageWidth / 2; //Can be fixed number
			let verticalPos = pageHeight - 5; //Can be fixed number
			doc.setPage(j);
			doc.text(`Pag. ${j} di ${pages}`, horizontalPos, verticalPos, { align: 'center' });
		}

		// Esporto e salvo il documento creato
		doc.save(nomeFilePDF + '.pdf');
	});

	// ESPORTAZIONE DETTAGLIO COMMESSA SELEZIONATO PER LA LINEA IN OGGETTO
	$('#rptDia-stampa-report-dettaglio').on('click', function () {
		// Ricavo la data attuale per generazione nome report
		var today = moment();
		var dataReport = today.format('YYYYMMDD');
		var dataReportIntestazione = today.format('DD/MM/YYYY');
		var nomeFilePDF = '';

		var idLinea = $('#rptDia_LineeProduzione').val();
		var idRisorsa = $('#rptDia_RisorseLinea').val();
		var descrizioneLinea = $('#rptDia_LineeProduzione option:selected').text();
		var descrizioneRisorsa = $('#rptDia_RisorseLinea option:selected').text();

		var descrizioneReport = '';

		if (idRisorsa != '_') {
			descrizioneReport = descrizioneRisorsa;
			nomeFilePDF =
				'RptDettaglioDTRisorsa' +
				dataReport +
				'_' +
				g_DataInizio +
				'_' +
				g_DataFine;
		} else {
			if (idLinea == '_') {
				descrizioneReport = $('#modal-diagnostica-risorsa-label-descRisorsa').text();
				nomeFilePDF =
					'RptDettaglioDTLinee' +
					dataReport +
					'_' +
					g_DataInizio +
					'_' +
					g_DataFine;
			}
		}

		// Credo il documento
		var doc = new jspdf.jsPDF({ orientation: 'landscape' });
		const pageWidth = doc.internal.pageSize.width;
		const pageHeight = doc.internal.pageSize.height;

		// Scrittura intestazione report
		doc.setFontSize(11);
		doc.text(10, 10, 'DIAGNOSTICA ORE IMPRODUTTIVE - DETTAGLIO DOWNTIME');
		doc.text(pageWidth - 40, 10, 'Data: ' + dataReportIntestazione);
		doc.setFontSize(9);
		doc.text(10, 15, 'Riferimento: ' + descrizioneReport);
		doc.text(
			10,
			20,
			'Periodo considerato: ' +
				g_DataInizio.substring(8, 10) +
				'/' +
				g_DataInizio.substring(5, 7) +
				'/' +
				g_DataInizio.substring(0, 4) +
				' - ' +
				+g_DataFine.substring(8, 10) +
				'/' +
				g_DataFine.substring(5, 7) +
				'/' +
				g_DataFine.substring(0, 4)
		);

		// Converto il grafico in immagine PNG
		var canvasImg = riferimentoGraficoDettaglioDowntimeRisorsa.toDataURL('image/png', 0.8);

		// Costanti per formattazione corretta del grafico
		const imgProps = doc.getImageProperties(canvasImg);
		const pdfWidth = doc.internal.pageSize.getWidth();
		const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;

		// Aggiungo al documento il grafico degli ordini nel periodo
		doc.addImage(canvasImg, 'JPEG', 10, 20, pdfWidth - 20, pdfHeight);

		// Stampa numerazione pagine
		const pages = doc.internal.getNumberOfPages();

		for (let j = 1; j < pages + 1; j++) {
			let horizontalPos = pageWidth / 2; //Can be fixed number
			let verticalPos = pageHeight - 5; //Can be fixed number
			doc.setPage(j);
			doc.text(`Pag. ${j} di ${pages}`, horizontalPos, verticalPos, { align: 'center' });
		}

		// Esporto e salvo il documento creato
		doc.save(nomeFilePDF + '.pdf');
	});
})(jQuery);
