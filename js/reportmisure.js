// Variabili globali
var g_rptMis_idRisorsa;
var g_rptMis_idProduzione;
var g_rptMis_idMisura;
var g_rptMis_DataInizioPeriodo;
var g_rptMis_DataFinePeriodo;

var g_rptMis_tipologiaGrafico;

var datiGrafico = [];

// Genero data e ora per pre-inizializzare il campo
var g_rptMis_DataOdierna = moment().format('YYYY-MM-DD');

// RIFERIMENTO AL GRAFICO
var riferimentoGraficoMisure = document.getElementById('grMisure');
var datiGraficoMisure;

// DEFINIZIONE OPZIONI DEL GRAFICO
var opzioniGraficoMisure = {
	tooltips: {
		enabled: true,
	},
	layout: {
		padding: {
			left: 25,
			right: 25,
			top: 35,
			bottom: 0,
		},
	},
	legend: {
		display: true,
		position: 'bottom',
		labels: {
			padding: 20,
			boxWidth: 10,
			fontColor: 'black',
		},
		onClick: function (e, legendItem) {
			var index = legendItem.datasetIndex;
			var ci = this.chart;
			var alreadyHidden =
				ci.getDatasetMeta(index).hidden === null ? false : ci.getDatasetMeta(index).hidden;

			ci.data.datasets.forEach(function (e, i) {
				var meta = ci.getDatasetMeta(i);

				if (i !== index) {
					if (!alreadyHidden) {
						meta.hidden = meta.hidden === null ? !meta.hidden : null;
					} else if (meta.hidden === null) {
						meta.hidden = true;
					}
				} else if (i === index) {
					meta.hidden = null;
				}
			});
			ci.update();
		},
	},
	animation: {
		onComplete: function () {
			/*
			var chartInstance = this.chart,
			ctx = chartInstance.ctx;
			ctx.font = Chart.helpers.fontString(Chart.defaults.global.defaultFontSize, Chart.defaults.global.defaultFontStyle, Chart.defaults.global.defaultFontFamily);
			ctx.textAlign = 'left';



			ctx.font = "0.6rem Arial";



			this.data.datasets.forEach(function (dataset, i) {
				var meta = chartInstance.controller.getDatasetMeta(i);
				meta.data.forEach(function (bar, index) {
					if (meta.hidden!=true)	{
						ctx.textAlign = 'center';
						ctx.fillStyle = 'black';
						var data = dataset.data[index].y + "";
						ctx.fillText(data, bar._model.x, bar._model.y - 15);
					}
				});
			});
			*/
		},
	},
	responsive: true,
	scales: {
		type: 'time',
		xAxes: [
			{
				ticks: {
					autoSkip: false,
					maxRotation: 40,
					minRotation: 40,
					fontSize: 10,
					fontStyle: 'italic',
					fontColor: 'black',
					padding: 7,
					major: {
						enabled: true,
						fontSize: 14,
					},
				},
				scaleLabel: {
					display: true,
					fontColor: 'black',
					labelString: 'DATA E ORA',
					fontSize: 10,
					fontStyle: 'bold',
				},
			},
		],
		yAxes: [
			{
				id: 'y',
				ticks: {
					fontSize: 10,
					fontStyle: 'italic',
					fontColor: 'black',
					padding: 15,
				},
				scaleLabel: {
					display: true,
					fontColor: 'black',
					labelString: 'VALORI',
					fontSize: 10,
					fontStyle: 'bold',
				},
			},
		],
	},
	plugins: {
		zoom: {
			pan: {
				enabled: true,
				mode: 'xy',
			},

			zoom: {
				enabled: true,
				sensitivity: 0.05,
				drag: false,
				enabled: true,
				mode: 'xy',
			},
		},
	},
};

// ISTANZIO IL GRAFICO
var graficoMisure = new Chart(riferimentoGraficoMisure, {
	type: 'line',
	data: datiGraficoMisure,
	options: opzioniGraficoMisure,
});

// FUNZIONE PER RECUPERO DATI GRAFICO E RELATIVA VISUALIZZAZIONE
function recuperaDatiGraficoMisure(selected) {
	var tempLabels = [];
	var tempDati = [];

	$('#rptMis-tabella-misure').dataTable().fnClearTable();

	if (selected != '' && g_rptMis_idProduzione != '') {
		for (var i = 0; i < selected.length; i++) {
			$.post('inc_reportmisure.php', {
				azione: 'rptMis-recupera-misure',
				idRisorsa: g_rptMis_idRisorsa,
				idMisura: selected[i],
				idProduzione: g_rptMis_idProduzione,
				dataInizioPeriodo: g_rptMis_DataInizioPeriodo,
				dataFinePeriodo: g_rptMis_DataFinePeriodo,
			}).done(function (dataAjax) {
				if (dataAjax != 'NO_ROWS') {
					var dati = JSON.parse(dataAjax);
					var descrizioneMisura = '';
					tempLabels = [];
					dati.forEach(function (entry) {
						tempLabels.push(entry.DataOraMisura);
						tempDati.push(entry.ValoreMisura);
						descrizioneMisura = entry.DescrizioneMisura;
						coloreLinea = entry.ColoreLinea;
						unitaMisura = entry.UnitaMisura;
					});

					var riga = {
						label: descrizioneMisura + ' [' + unitaMisura + ']',
						data: tempDati,
						lineTension: 0,
						borderColor: coloreLinea,
						backgroundColor: 'transparent',
						pointBorderColor: coloreLinea,
						pointBackgroundColor: coloreLinea,
						pointRadius: 1,
						pointHoverRadius: 2,
						pointHitRadius: 2,
						pointBorderWidth: 1,
						fill: true,
						borderWidth: 1,
					};

					tempDati = [];

					datiGrafico.push(riga);

					$.post('inc_reportmisure.php', {
						azione: 'rptMis-recupera-misure-tabella',
						idRisorsa: g_rptMis_idRisorsa,
						idMisura: selected[i],
						idProduzione: g_rptMis_idProduzione,
						dataInizioPeriodo: g_rptMis_DataInizioPeriodo,
						dataFinePeriodo: g_rptMis_DataFinePeriodo,
					}).done(function (data) {
						var dati = JSON.parse(data);
						if (dati.length > 0) {
							$('#rptMis-tabella-misure').dataTable().fnAddData(dati);
						}
					});
				}
			});
		}

		datiGraficoMisure = {
			labels: tempLabels,
			datasets: datiGrafico,
		};

		graficoMisure.options.tooltips = {
			enabled: true,
			mode: 'index',
			intersect: 'false',
			position: 'nearest',
			callbacks: {
				title: function (tooltipItem, data) {
					return 'Campione n°: ' + (tooltipItem[0].index + 1);
				},
				afterLabel: function (tooltipItem, data) {
					return 'Data e ora campione: ' + tooltipItem.xLabel + '\n';
				},
			},
		};

		datiGrafico = [];
		graficoMisure.data = datiGraficoMisure;
		graficoMisure.update();
		graficoMisure.resetZoom();
	} else {
		tempLabels = [];
		datiGrafico = [];

		datiGraficoMisure = {
			labels: tempLabels,
			datasets: datiGrafico,
		};

		graficoMisure.data = datiGraficoMisure;
		graficoMisure.update();
		graficoMisure.resetZoom();
	}
}

// FUNZIONE PER POPOLAMENTO SELECT PRODOTTI
function rptMisPopolaSelectProdotti(idRisorsa, idProdotto) {
	//popolo select prodotti
	$.post('inc_reportmisure.php', {
		azione: 'rptMis-carica-select-prodotti',
		idRisorsa: idRisorsa,
		idProdotto: g_rptMis_idProdotto,
	}).done(function (data) {
		$('#rptMis_Prodotti').html(data);
		$('#rptMis_Prodotti').selectpicker('refresh');
	});
}

(function ($) {
	var rptMis_tabellaMisurePeriodo;

	var rptMis_linguaItaliana = {
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
		$.fn.dataTable.moment('DD/MM/YYYY HH:mm:ss');

		rptMis_tabellaMisurePeriodo = $('#rptMis-tabella-misure').DataTable({
			aLengthMenu: [
				[5, 10, 20, 50, 100, -1],
				[5, 10, 20, 50, 100, 'Tutti'],
			],
			iDisplayLength: 10,

			order: [
				[1, 'asc'],
				[0, 'asc'],
				[4, 'desc'],
			],

			columns: [
				{ data: 'DescrizioneMisura' },
				{ data: 'DescrizioneRisorsa' },
				{ data: 'IdProduzione' },
				{ data: 'ValoreMisura' },
				{ data: 'DataOraMisura' },
				{ data: 'IndiceRiga' },
			],
			columnDefs: [
				{
					targets: [5],
					visible: false,
				},
			],

			language: rptMis_linguaItaliana,
			info: false,
			//"dom": "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4'l><'col-sm-8'p>>"
		});

		$('.selectpicker').selectpicker({
			noneSelectedText: 'Seleziona', // by this default 'Nothing selected' -->will change to Please Select
		});

		// impostazione variabile ID RISORSA
		if (sessionStorage.getItem('rptMis_idRisorsa') === null) {
			g_rptMis_idRisorsa = 'RIS_01';
		} else {
			g_rptMis_idRisorsa = sessionStorage.getItem('rptMis_idRisorsa');
		}
		$('#rptMis_RisorseLinea').val(g_rptMis_idRisorsa);
		$('#rptMis_RisorseLinea').selectpicker('refresh');

		// impostazione variabile ID COMMESSA
		if (sessionStorage.getItem('rptMis_idProduzione') === null) {
			g_rptMis_idProduzione = '%';
		} else {
			g_rptMis_idProduzione = sessionStorage.getItem('rptMis_idProduzione');
		}

		// impostazione variabile ID MISURA
		if (sessionStorage.getItem('rptMis_idMisura') === null) {
			g_rptMis_idMisura = '';
		} else {
			g_rptMis_idMisura = sessionStorage.getItem('rptMis_idMisura').split(',');
		}

		// impostazione variabile DATA INIZIO TRACCIATE
		if (sessionStorage.getItem('rptMis_DataInizioPeriodo') === null) {
			g_rptMis_DataInizioPeriodo = g_rptMis_DataOdierna;
		} else {
			g_rptMis_DataInizioPeriodo = sessionStorage.getItem('rptMis_DataInizioPeriodo');
		}
		$('#rptMis_DataInizio').val(g_rptMis_DataInizioPeriodo);

		// impostazione variabile DATA FINE TRACCIATE
		if (sessionStorage.getItem('rptMis_DataFinePeriodo') === null) {
			g_rptMis_DataFinePeriodo = g_rptMis_DataOdierna;
		} else {
			g_rptMis_DataFinePeriodo = sessionStorage.getItem('rptMis_DataFinePeriodo');
		}
		$('#rptMis_DataFine').val(g_rptMis_DataFinePeriodo);

		// Popolo select misure
		$.ajaxSetup({ async: false });
		$.post('inc_reportmisure.php', {
			azione: 'rptMis-carica-select-misure',
			idRisorsa: g_rptMis_idRisorsa,
		}).done(function (dataMisure) {
			$('#rptMis_Misure').html(dataMisure);
			var selected = g_rptMis_idMisura;
			if (dataMisure == "<option value=''>Nessuna misura disponibile</option>") {
				$('#rptMis_Misure').selectpicker('val', '');
			} else {
				$('#rptMis_Misure').selectpicker('val', selected);
			}

			$('#rptMis_Misure').selectpicker('refresh');

			// Popolo select commesse
			$.ajaxSetup({ async: false });
			$.post('inc_reportmisure.php', {
				azione: 'rptMis-carica-select-commesse',
				idRisorsa: g_rptMis_idRisorsa,
				dataInizio: g_rptMis_DataInizioPeriodo,
				dataFine: g_rptMis_DataFinePeriodo,
			}).done(function (data) {
				$('#rptMis_Commesse').html(data);
				$('#rptMis_Commesse').val(g_rptMis_idProduzione);
				$('#rptMis_Commesse').selectpicker('refresh');

				sessionStorage.setItem('rptMis_idProduzione', g_rptMis_idProduzione);
				recuperaDatiGraficoMisure(selected);
			});
		});
	});

	//  ALLA VARIAZIONE DELLA RISORSA SELEZIONATA, AGGIORNO I DATI VISUALIZZATI DAL GRAFICO
	$('#rptMis_RisorseLinea').on('change', function () {
		g_rptMis_idRisorsa = $('#rptMis_RisorseLinea').val();
		sessionStorage.setItem('rptMis_idRisorsa', g_rptMis_idRisorsa);

		// Popolo select misure
		$.ajaxSetup({ async: false });
		$.post('inc_reportmisure.php', {
			azione: 'rptMis-carica-select-misure',
			idRisorsa: g_rptMis_idRisorsa,
		}).done(function (dataMisure) {
			$('#rptMis_Misure').html(dataMisure);
			g_rptMis_idMisura = $('#rptMis_Misure').val();
			var selected = g_rptMis_idMisura;

			if (dataMisure == "<option value=''>Nessuna misura disponibile</option>") {
				$('#rptMis_Misure').selectpicker('val', '');
			} else {
				$('#rptMis_Misure').selectpicker('val', selected);
			}

			$('#rptMis_Misure').selectpicker('refresh');

			// Popolo select commesse
			$.ajaxSetup({ async: false });
			$.post('inc_reportmisure.php', {
				azione: 'rptMis-carica-select-commesse',
				idRisorsa: g_rptMis_idRisorsa,
				dataInizio: g_rptMis_DataInizioPeriodo,
				dataFine: g_rptMis_DataFinePeriodo,
			}).done(function (data) {
				$('#rptMis_Commesse').html(data);
				$('#rptMis_Commesse').selectpicker('refresh');

				g_rptMis_idProduzione = $('#rptMis_Commesse').val();
				sessionStorage.setItem('rptMis_idProduzione', g_rptMis_idProduzione);

				recuperaDatiGraficoMisure(selected);
			});
		});
	});

	//  ALLA VARIAZIONE DELLA MISURA SELEZIONATA, AGGIORNO I DATI VISUALIZZATI DAL GRAFICO
	$('#rptMis_Misure').on('change', function () {
		g_rptMis_idMisura = $('#rptMis_Misure').val();
		sessionStorage.setItem('rptMis_idMisura', g_rptMis_idMisura);

		// Popolo select commesse
		$.post('inc_reportmisure.php', {
			azione: 'rptMis-carica-select-commesse',
			idRisorsa: g_rptMis_idRisorsa,
			dataInizio: g_rptMis_DataInizioPeriodo,
			dataFine: g_rptMis_DataFinePeriodo,
		}).done(function (data) {
			$('#rptMis_Commesse').html(data);
			$('#rptMis_Commesse').selectpicker('refresh');

			g_rptMis_idProduzione = $('#rptMis_Commesse').val();
			sessionStorage.setItem('rptMis_idProduzione', g_rptMis_idProduzione);

			recuperaDatiGraficoMisure(g_rptMis_idMisura);
		});
	});

	//  ALLA VARIAZIONE DEL TRACCIATE DI RIFERIMENTO O DELLA COMMESSA SELEZIONATA, AGGIORNO I DATI VISUALIZZATI DAL GRAFICO
	$('#rptMis_Commesse').on('change', function () {
		g_rptMis_idProduzione = $('#rptMis_Commesse').val();
		sessionStorage.setItem('rptMis_idProduzione', g_rptMis_idProduzione);

		var selected = g_rptMis_idMisura;
		$('#rptMis_Misure').selectpicker('val', selected);
		$('#rptMis_Misure').selectpicker('refresh');

		recuperaDatiGraficoMisure(g_rptMis_idMisura);
	});

	//  SULLA PERDITA DEL FOCUS DEI CAMPI DATA, AGGIORNO I DATI VISUALIZZATI DAL GRAFICO
	$('#rptMis_DataInizio, #rptMis_DataFine').on('blur', function () {
		var nome = $(this).attr('id');
		window['g_' + nome + 'Periodo'] = $(this).val();
		sessionStorage.setItem(nome + 'Periodo', window['g_' + nome + 'Periodo']);
		var prefisso = nome.split('_')[0];
		var dataInizio = $('#' + prefisso + '_DataInizio').val();
		var dataFine = $('#' + prefisso + '_DataFine').val();
		if (dataFine >= dataInizio) {
			recuperaDatiGraficoMisure(g_rptMis_idMisura);
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
	$('#rptMis-stampa-report').on('click', function () {
		// Ricavo la data attuale per generazione nome report
		var today = moment();
		var dataReport = today.format('YYYYMMDD');
		var dataReportIntestazione = today.format('DD/MM/YYYY');
		var nomeFilePDF = '';
		var descrizioneRisorsa = $('#rptMis_RisorseLinea option:selected').text();

		var valoriRiga = [];
		var valoriTabella = [];

		// Credo il documento
		var doc = new jspdf.jsPDF({ orientation: 'landscape' });
		const pageWidth = doc.internal.pageSize.width;
		const pageHeight = doc.internal.pageSize.height;

		// Scrittura intestazione report
		doc.setFontSize(10);
		doc.text(pageWidth - 60, 10, 'Report in data: ' + dataReportIntestazione);
		doc.text(10, 10, 'Risorsa: ' + descrizioneRisorsa);
		doc.text(
			10,
			15,
			'Periodo considerato: ' +
				g_rptMis_DataInizioPeriodo.substring(8, 10) +
				'/' +
				g_rptMis_DataInizioPeriodo.substring(5, 7) +
				'/' +
				g_rptMis_DataInizioPeriodo.substring(0, 4) +
				' - ' +
				+g_rptMis_DataFinePeriodo.substring(8, 10) +
				'/' +
				g_rptMis_DataFinePeriodo.substring(5, 7) +
				'/' +
				g_rptMis_DataFinePeriodo.substring(0, 4)
		);

		// Definisco il nome del report
		nomeFilePDF =
			descrizioneRisorsa +
			' - RptMisure' +
			dataReport +
			'_' +
			g_rptMis_DataInizioPeriodo +
			'_' +
			g_rptMis_DataFinePeriodo;

		// Converto il grafico in immagine PNG
		var canvasImg = riferimentoGraficoMisure.toDataURL('image/png', 1.0);

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
