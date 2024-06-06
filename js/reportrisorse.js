// Variabili globali
var g_rptRis_idLineaProduzione;
var g_rptRis_idRisorsa;
var g_rptRis_idProdotto;

var g_rptRis_tipologiaGrafico;

// Genero data e ora per pre-inizializzare il campo
var g_rptRis_DataOdierna = moment().format('YYYY-MM-DD');

// RIFERIMENTO AL GRAFICO
var riferimentoGraficoOEERisorse = document.getElementById('grOEERisorse');
var datiGraficoOEERisorse;

// DEFINIZIONE OPZIONI DEL GRAFICO
var opzioniGraficoOEERisorse = {
	tooltips: {
		enabled: true,
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
		display: true,
		position: 'right',
		labels: {
			padding: 15,
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
		onProgress: function () {
			var chartInstance = this.chart,
				ctx = chartInstance.ctx;
			ctx.font = Chart.helpers.fontString(
				Chart.defaults.global.defaultFontSize,
				Chart.defaults.global.defaultFontStyle,
				Chart.defaults.global.defaultFontFamily
			);
			ctx.textAlign = 'left';

			ctx.font = '0.7rem Arial';

			this.data.datasets.forEach(function (dataset, i) {
				var meta = chartInstance.controller.getDatasetMeta(i);
				meta.data.forEach(function (bar, index) {
					if (dataset.data[index] != 0) {
						if (i == 0 && meta.hidden == null) {
							ctx.textAlign = 'center';
							ctx.fillStyle = 'green';
							var data = dataset.data[index] + '%';
							ctx.fillText(data, bar._model.x, bar._model.y - 15);
						} else if (i == 1 && meta.hidden == null) {
							ctx.textAlign = 'center';
							ctx.fillStyle = 'blue';
							var data = dataset.data[index] + '%';
							ctx.fillText(data, bar._model.x, bar._model.y - 15);
						} else if (i == 2 && meta.hidden == null) {
							ctx.textAlign = 'center';
							ctx.fillStyle = 'red';
							var data = dataset.data[index] + '%';
							ctx.fillText(data, bar._model.x, bar._model.y - 15);
						} else if (i == 3 && meta.hidden == null) {
							ctx.textAlign = 'center';
							ctx.fillStyle = 'orange';
							var data = dataset.data[index] + '%';
							ctx.fillText(data, bar._model.x, bar._model.y - 15);
						}
					}
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
					fontSize: 10,
					fontStyle: 'italic',
					fontColor: 'black',
					padding: 7,
				},
				scaleLabel: {
					display: true,
					fontColor: 'black',
					labelString: 'CODICI COMMESSA (DATA INIZIO)',
					fontSize: 10,
					fontStyle: 'bold',
				},
			},
		],
		yAxes: [
			{
				ticks: {
					fontSize: 10,
					fontStyle: 'italic',
					fontColor: 'black',
					padding: 15,
				},
				scaleLabel: {
					display: true,
					fontColor: 'black',
					labelString: 'OEE [%]',
					fontSize: 10,
					fontStyle: 'bold',
				},
			},
		],
	},
};

// ISTANZIO IL GRAFICO
var graficoOEERisorse = new Chart(riferimentoGraficoOEERisorse, {
	type: 'line',
	data: datiGraficoOEERisorse,
	options: opzioniGraficoOEERisorse,
});

// FUNZIONE PER RECUPERO DATI GRAFICO E RELATIVA VISUALIZZAZIONE
function recuperaDatiGraficoOEERisorse() {
	// Se ho una risorsa e un prodotto selezionati
	if (g_rptRis_idRisorsa != null && g_rptRis_idProdotto != null) {
		// Recupero i dati relativi all'OEE, secondo i criteri impsotati
		$.post('inc_reportrisorse.php', {
			azione: 'rptRis-recupera-oee-periodo',
			idRisorsa: g_rptRis_idRisorsa,
			idProdotto: g_rptRis_idProdotto,
			dataInizioPeriodo: g_DataInizio,
			dataFinePeriodo: g_DataFine,
		}).done(function (dataAjax) {
			var labelsGrRisorse = [];
			var datiGrOEERisorse = [];
			var datiGrFattoreDRisorse = [];
			var datiGrFattoreERisorse = [];
			var datiGrFattoreQRisorse = [];

			if (dataAjax != 'NO_ROWS') {
				var dati = JSON.parse(dataAjax);

				dati.forEach(function (entry) {
					labelsGrRisorse.push(entry.IdProduzioneGrafico);

					datiGrOEERisorse.push(entry.OEERisorsa);
					datiGrFattoreDRisorse.push(entry.DRisorsa);
					datiGrFattoreERisorse.push(entry.ERisorsa);
					datiGrFattoreQRisorse.push(entry.QRisorsa);
				});

				$('#rptRis-tabella-ordini').dataTable().fnClearTable();
				if (dati.length > 0) {
					$('#rptRis-tabella-ordini').dataTable().fnAddData(JSON.parse(dataAjax));
				}

				datiGraficoOEERisorse = {
					labels: labelsGrRisorse,
					datasets: [
						{
							label: 'OEE',
							data: datiGrOEERisorse,
							lineTension: 0,
							fill: true,
							borderColor: 'green',
							backgroundColor: 'transparent',
							pointBorderColor: 'green',
							pointBackgroundColor: 'green',
							pointRadius: 2,
							pointHoverRadius: 3,
							pointHitRadius: 30,
							pointBorderWidth: 2,
							borderWidth: 1,
						},
						{
							label: '(D)ISP.',
							data: datiGrFattoreDRisorse,
							lineTension: 0,
							fill: true,
							borderColor: 'blue',
							backgroundColor: 'transparent',
							pointBorderColor: 'blue',
							pointBackgroundColor: 'blue',
							pointRadius: 2,
							pointHoverRadius: 3,
							pointHitRadius: 30,
							pointBorderWidth: 2,
							borderWidth: 1,
						},
						{
							label: '(E)FFIC.',
							data: datiGrFattoreERisorse,
							lineTension: 0,
							fill: true,
							borderColor: 'red',
							backgroundColor: 'transparent',
							pointBorderColor: 'red',
							pointBackgroundColor: 'red',
							pointRadius: 2,
							pointHoverRadius: 3,
							pointHitRadius: 30,
							pointBorderWidth: 2,
							borderWidth: 1,
						},
						{
							label: '(Q)UAL.',
							data: datiGrFattoreQRisorse,
							lineTension: 0,
							fill: true,
							borderColor: 'orange',
							backgroundColor: 'transparent',
							pointBorderColor: 'orange',
							pointBackgroundColor: 'orange',
							pointRadius: 2,
							pointHoverRadius: 3,
							pointHitRadius: 30,
							pointBorderWidth: 2,
							borderWidth: 1,
						},
					],
				};

				graficoOEERisorse.data = datiGraficoOEERisorse;
				graficoOEERisorse.data.datasets.forEach((dataSet, i) => {
					var meta = graficoOEERisorse.getDatasetMeta(i);
					if (i == 0) {
						meta.hidden = null;
					} else {
						meta.hidden = true;
					}
				});
				graficoOEERisorse.update();
			} else {
				$('#rptRis-tabella-ordini').dataTable().fnClearTable();

				datiGraficoOEERisorse = {
					labels: labelsGrRisorse,
					datasets: [
						{
							label: 'OEE',
							data: [],
							lineTension: 0,
							fill: true,
							borderColor: 'green',
							backgroundColor: 'transparent',
							pointBorderColor: 'green',
							pointBackgroundColor: 'green',
							pointRadius: 2,
							pointHoverRadius: 3,
							pointHitRadius: 30,
							pointBorderWidth: 2,
							borderWidth: 1,
						},
						{
							label: '(D)isponibilità',
							data: [],
							lineTension: 0,
							fill: true,
							borderColor: 'blue',
							backgroundColor: 'transparent',
							pointBorderColor: 'blue',
							pointBackgroundColor: 'blue',
							pointRadius: 2,
							pointHoverRadius: 3,
							pointHitRadius: 30,
							pointBorderWidth: 2,
							borderWidth: 1,
						},
						{
							label: '(E)fficienza',
							data: [],
							lineTension: 0,
							fill: true,
							borderColor: 'red',
							backgroundColor: 'transparent',
							pointBorderColor: 'red',
							pointBackgroundColor: 'red',
							pointRadius: 2,
							pointHoverRadius: 3,
							pointHitRadius: 30,
							pointBorderWidth: 2,
							borderWidth: 1,
						},
						{
							label: '(Q)ualità',
							data: [],
							lineTension: 0,
							fill: true,
							borderColor: 'orange',
							backgroundColor: 'transparent',
							pointBorderColor: 'orange',
							pointBackgroundColor: 'orange',
							pointRadius: 2,
							pointHoverRadius: 3,
							pointHitRadius: 30,
							pointBorderWidth: 2,
							borderWidth: 1,
						},
					],
				};

				graficoOEERisorse.data = datiGraficoOEERisorse;
				graficoOEERisorse.update();
			}
		});

		$.post('inc_reportrisorse.php', {
			azione: 'rptRis-calcola-valori-medi',
			idRisorsa: g_rptRis_idRisorsa,
			idProdotto: g_rptRis_idProdotto,
			dataInizioPeriodo: g_DataInizio,
			dataFinePeriodo: g_DataFine,
		}).done(function (dataAjax) {
			var datiMedi = JSON.parse(dataAjax);

			if (dataAjax != 'NO_ROWS') {
				$('#rptRis_DMedio').val(datiMedi['FattoreDMedio']);
				$('#rptRis_EMedio').val(datiMedi['FattoreEMedio']);
				$('#rptRis_QMedio').val(datiMedi['FattoreQMedio']);

				$('#rptRis_OEEMedio').val(datiMedi['OEEMedio']);
			}
		});
	}
}

// FUNZIONE PER POPOLAMENTO SELECT RISORSE
function rptRisPopolaSelectRisorse(idLineaProduzione, idRisorsa) {
	//popolo select prodotti
	$.post('inc_reportrisorse.php', {
		azione: 'rptRis-carica-select-risorse',
		idLineaProduzione: idLineaProduzione,
		idRisorsa: idRisorsa,
	}).done(function (data) {
		$('#rptRis_RisorseLinea').html(data);
		$('#rptRis_RisorseLinea').selectpicker('refresh');
	});
}

// FUNZIONE PER POPOLAMENTO SELECT PRODOTTI
function rptRisPopolaSelectProdotti(idRisorsa, idProdotto) {
	//popolo select prodotti
	$.post('inc_reportrisorse.php', {
		azione: 'rptRis-carica-select-prodotti',
		idRisorsa: idRisorsa,
		idProdotto: g_rptRis_idProdotto,
	}).done(function (data) {
		$('#rptRis_Prodotti').html(data);
		$('#rptRis_Prodotti').selectpicker('refresh');
	});
}

(function ($) {
	var rptRis_tabellaDatiReportProduzioni;
	var rptRis_tabellaDatiDistintaRisorse;
	var rptRis_tabellaDatiDistintaComponenti;
	var rptRis_tabellaDatiCasiCumulativo;
	var rptRis_tabellaDatiCasi;
	var rptRis_tabellaDatiDowntime;
	var rptRis_tabellaDatiOrdiniPeriodo;
	var rptRis_tabellaDatiOrdineSelezionato;

	var rptRis_linguaItaliana = {
		processing: 'Caricamento...',
		search: 'Ricerca: ',
		lengthMenu: '_MENU_ righe per pagina',
		zeroRecords: 'Nessuna produzione selezionata',
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

	var rptRisDettaglio_linguaItaliana = {
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

	// DATATABLE POPOLAMENTO GRAFICO E TABELLA COMMESSE RELATIVI AL PERIODO E RISORSA SELEZIONATI
	$(function () {
		$.fn.dataTable.moment('DD/MM/YYYY HH:mm:ss');

		rptRis_tabellaDatiOrdiniPeriodo = $('#rptRis-tabella-ordini').DataTable({
			aLengthMenu: [
				[5, 10, -1],
				[5, 10, 'Tutti'],
			],
			iDisplayLength: 5,

			order: [[3, 'desc']],

			columns: [
				{ data: 'IdProduzione' },
				{ data: 'DescrizioneProdotto' },
				{ data: 'QtaProdotta' },
				{ data: 'QtaConforme' },
				{ data: 'DataInizio' },
				{ data: 'DataFine' },
				{ data: 'VelocitaRisorsa' },
				{ data: 'DRisorsa' },
				{ data: 'ERisorsa' },
				{ data: 'QRisorsa' },
				{ data: 'OEERisorsa' },
				{ data: 'OEEMedioProdotto' },
			],
			columnDefs: [
				{ className: 'td-d', targets: [7] },
				{ className: 'td-e', targets: [8] },
				{ className: 'td-q', targets: [9] },
				{ className: 'td-oee', targets: [10] },
				{ className: 'td-oee', targets: [11] },
			],

			language: rptRis_linguaItaliana,
			info: false,
			//"dom": "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4'l><'col-sm-8'p>>"
		});

		// DATATABLE DATI COMMESSA SELZIONATO
		rptRis_tabellaDatiOrdineSelezionato = $('#rptRis-tabella-ordine-selezionato').DataTable({
			iDisplayLength: 1,

			columns: [
				{ data: 'IdProduzione' },
				{ data: 'DescrizioneProdotto' },
				{ data: 'DataInizio' },
				{ data: 'DataFine' },
				{ data: 'QtaProdotta' },
				{ data: 'QtaConforme' },
				{ data: 'TTotale' },
				{ data: 'Downtime' },
				{ data: 'DeltaAttrezzaggio' },
				{ data: 'TAttrezzaggio' },
				{ data: 'VelocitaLinea' },
				{ data: 'D' },
				{ data: 'E' },
				{ data: 'Q' },
				{ data: 'OEELinea' },
			],
			columnDefs: [
				{ className: 'td-d', targets: [11] },
				{ className: 'td-e', targets: [12] },
				{ className: 'td-q', targets: [13] },
				{ className: 'td-oee', targets: [14] },
			],

			language: rptRis_linguaItaliana,
			searching: false,
			info: false,
			ordering: false,
			paging: false,
			dom: "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4'l><'col-sm-8'p>>",
		});

		// DATATABLE ELENCO CASI INSERITI (RAGGRUPPATI PER TIPOLOGIA)
		rptRis_tabellaDatiCasiCumulativo = $('#rptRis-tabella-casi-cumulativo').DataTable({
			aLengthMenu: [
				[8, 16, 24, 32, 100, -1],
				[8, 16, 24, 32, 100, 'Tutti'],
			],
			iDisplayLength: 8,

			order: [
				[0, 'asc'],
				[1, 'asc'],
			],

			columns: [
				{ data: 'DescrizioneRisorsa' },
				{ data: 'DescrizioneCaso' },
				{ data: 'TipoEvento' },
				{ data: 'NumeroEventi' },
				{ data: 'Durata' },
			],

			language: rptRisDettaglio_linguaItaliana,
			searching: false,
			info: false,
			dom: "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4'l><'col-sm-8'p>>",
		});

		// DATATABLE ELENCO CASI INSERITI PER LA RISORSA E L'COMMESSA SELEZIONATI
		rptRis_tabellaDatiCasi = $('#rptRis-tabella-casi').DataTable({
			aLengthMenu: [
				[5, 10, 25, 50, 100, -1],
				[5, 10, 25, 50, 100, 'Tutti'],
			],
			iDisplayLength: 10,

			order: [[1, 'asc']],

			columns: [
				{ data: 'DescrizioneRisorsa' },
				{ data: 'DescrizioneCaso' },
				{ data: 'TipoEvento' },
				{ data: 'DataInizio' },
				{ data: 'DataFine' },
				{ data: 'Durata' },
				{ data: 'Note' },
			],
			columnDefs: [
				{ targets: 3, type: 'date-eu' },
				{ targets: 4, type: 'date-eu' },
			],

			language: rptRisDettaglio_linguaItaliana,
			searching: false,
			info: false,
			dom: "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4'l><'col-sm-8'p>>",
		});
	});

	// AL CLIC SU UNO DEI PUNTI VISUALIZZATI SUL GRAFICO (OEE PRODUZIONE DELLA RISORSA), RICAVO LABEL E VALORE
	$('body').on('click', '#grOEERisorse', function (e) {
		var activePoints = graficoOEERisorse.getElementsAtEventForMode(
			e,
			'point',
			graficoOEERisorse.options
		);
		var firstPoint = activePoints[0];
		if (firstPoint != null) {
			var label = graficoOEERisorse.data.labels[firstPoint._index];
			var idProduzioneLabel = label.slice(0, -13).trim();
			var value =
				graficoOEERisorse.data.datasets[firstPoint._datasetIndex].data[firstPoint._index];
			var descrizioneRisorsa = $('#rptRis_RisorseLinea option:selected').text();

			//Imposto le label del popup in modo da mostrare ID PRODUZIONE e DESCRIZIONE RISORSA selezionati
			$('#modal-risorsa-label-codOrdine')
				.text(idProduzioneLabel)
				.css('font-style', 'italic')
				.css('font-weight', 'bold');
			$('#modal-risorsa-label-descRisorsa')
				.text(descrizioneRisorsa)
				.css('font-style', 'italic')
				.css('font-weight', 'bold');

			//Visualizzazione distinta risorse per l'ordine di produzione selezionato
			$.post('inc_reportrisorse.php', {
				azione: 'rptRis-mostra-dettaglio-ordine',
				idProduzione: idProduzioneLabel,
				idRisorsa: g_rptRis_idRisorsa,
			}).done(function (data) {
				var dati = JSON.parse(data);
				if (dati.length > 0) {
					$('#rptRis-tabella-ordine-selezionato').dataTable().fnClearTable();
					$('#rptRis-tabella-ordine-selezionato').dataTable().fnAddData(dati);
				} else {
					$('#rptRis-tabella-ordine-selezionato').dataTable().fnClearTable();
				}
			});

			//Visualizzazione elenco casi verificatisi per l'ordine di produzione selezionato (CUMULATIVO)
			$.post('inc_reportrisorse.php', {
				azione: 'rptRis-mostra-casi-produzione-cumulativo',
				idProduzione: idProduzioneLabel,
				idRisorsa: g_rptRis_idRisorsa,
			}).done(function (data) {
				var dati = JSON.parse(data);
				if (dati.length > 0) {
					$('#rptRis-tabella-casi-cumulativo').dataTable().fnClearTable();
					$('#rptRis-tabella-casi-cumulativo').dataTable().fnAddData(dati);
				} else {
					$('#rptRis-tabella-casi-cumulativo').dataTable().fnClearTable();
				}
			});

			//Visualizzazione elenco casi verificatisi per risorsa e ordine di produzione selezionati
			$.post('inc_reportrisorse.php', {
				azione: 'rptRis-mostra-casi-ordine',
				idProduzione: idProduzioneLabel,
				idRisorsa: g_rptRis_idRisorsa,
			}).done(function (data) {
				var dati = JSON.parse(data);
				if (dati.length > 0) {
					$('#rptRis-tabella-casi').dataTable().fnClearTable();
					$('#rptRis-tabella-casi').dataTable().fnAddData(dati);
				} else {
					$('#rptRis-tabella-casi').dataTable().fnClearTable();
				}
			});

			// mostro il popup di dettaglio risorsa
			$('#modal-report-risorsa').modal('show');
		}
	});

	// ALLA VARIAZIONE DELLA LINEA SELEZIONATA, RICARICO LE SELECT 'RISORSE' E 'PRODOTTI' E AGGIORNO I DATI VISUALIZZATI DAL GRAFICO
	$('#rptRis_LineeProduzione').on('change', function () {
		g_rptRis_idLineaProduzione = $('#rptRis_LineeProduzione').val();
		sessionStorage.setItem('rptRis_idLineaProduzione', g_rptRis_idLineaProduzione);

		//popolo select prodotti
		$.post('inc_reportrisorse.php', {
			azione: 'rptRis-carica-select-risorse',
			idLineaProduzione: g_rptRis_idLineaProduzione,
		}).done(function (data) {
			$('#rptRis_RisorseLinea').html(data);
			$('#rptRis_RisorseLinea').selectpicker('refresh');

			g_rptRis_idRisorsa = $('#rptRis_RisorseLinea').val();
			sessionStorage.setItem('rptRis_idRisorsa', g_rptRis_idRisorsa);

			//popolo select prodotti
			$.post('inc_reportrisorse.php', {
				azione: 'rptRis-carica-select-prodotti',
				idRisorsa: g_rptRis_idRisorsa,
			}).done(function (data) {
				$('#rptRis_Prodotti').html(data);
				$('#rptRis_Prodotti').selectpicker('refresh');

				g_rptRis_idProdotto = $('#rptRis_Prodotti').val();
				sessionStorage.setItem('rptRis_idProdotto', g_rptRis_idProdotto);

				recuperaDatiGraficoOEERisorse();
			});
		});
	});

	//  ALLA VARIAZIONE DELLA RISORSA SELEZIONATA, RICARICO LA SELECT 'PRODOTTI' E AGGIORNO I DATI MOSTRATI DAL GRAFICO
	$('#rptRis_RisorseLinea').on('change', function () {
		g_rptRis_idRisorsa = $('#rptRis_RisorseLinea').val();
		sessionStorage.setItem('rptRis_idRisorsa', g_rptRis_idRisorsa);

		//popolo select prodotti
		$.post('inc_reportrisorse.php', {
			azione: 'rptRis-carica-select-prodotti',
			idRisorsa: g_rptRis_idRisorsa,
		}).done(function (data) {
			$('#rptRis_Prodotti').html(data);
			$('#rptRis_Prodotti').selectpicker('refresh');

			g_rptRis_idProdotto = $('#rptRis_Prodotti').val();
			sessionStorage.setItem('rptRis_idProdotto', g_rptRis_idProdotto);

			recuperaDatiGraficoOEERisorse();
		});
	});

	//  ALLA VARIAZIONE DEL PRODOTTO SELEZIONATO, AGGIORNO I DATI VISUALIZZATI
	$('#rptRis_Prodotti').on('change', function () {
		g_rptRis_idProdotto = $('#rptRis_Prodotti').val();

		// memorizzo nelle variabili di sessione i valori recuperati
		sessionStorage.setItem('rptRis_idProdotto', g_rptRis_idProdotto);

		recuperaDatiGraficoOEERisorse();
	});

	//  ALLA VARIAZIONE DELLA DATA DI INIZIO DEL PERIODO SELEZIONATO, AGGIORNO I DATI VISUALIZZATI
	$('#rptRis_DataInizio, #rptRis_DataFine').on('blur', function () {
		var nome = $(this).attr('id');
		window['g_' + nome + 'Periodo'] = $(this).val();
		sessionStorage.setItem(nome + 'Periodo', window['g_' + nome + 'Periodo']);
		var prefisso = nome.split('_')[0];
		var dataInizio = $('#' + prefisso + '_DataInizio').val();
		var dataFine = $('#' + prefisso + '_DataFine').val();
		if (dataFine >= dataInizio) {
			recuperaDatiGraficoOEERisorse();
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

	// ESPORTAZIONE GRAFICO E TABELLA COMMESSE NEL PERIODO CONSIDERATO
	$('#rptRis-stampa-report').on('click', function () {
		// Ricavo la data attuale per generazione nome report
		var today = moment();
		var dataReport = today.format('YYYYMMDD');
		var dataReportIntestazione = today.format('DD/MM/YYYY');
		var nomeFilePDF = '';
		var descrizioneRisorsa = $('#rptRis_RisorseLinea option:selected').text();

		var valoriRiga = [];
		var valoriTabella = [];

		// Credo il documento
		var doc = new jspdf.jsPDF({ orientation: 'landscape' });
		const pageWidth = doc.internal.pageSize.width;
		const pageHeight = doc.internal.pageSize.height;

		// Scrittura intestazione report
		doc.setFontSize(10);
		doc.text(pageWidth - 60, 10, 'Report in data: ' + dataReportIntestazione);
		doc.text(10, 10, 'Macchina: ' + descrizioneRisorsa);
		doc.text(
			10,
			15,
			'Commesse nel periodo: ' +
				g_DataInizio.substring(8, 10) +
				'/' +
				g_DataInizio.substring(5, 7) +
				'/' +
				g_DataInizio.substring(0, 4) +
				' - ' +
				g_DataFine.substring(8, 10) +
				'/' +
				g_DataFine.substring(5, 7) +
				'/' +
				g_DataFine.substring(0, 4)
		);

		// Recupero i dati degli ordini nel periodo
		$.post('inc_reportrisorse.php', {
			azione: 'rptRis-recupera-oee-periodo',
			idRisorsa: g_rptRis_idRisorsa,
			idProdotto: g_rptRis_idProdotto,
			dataInizioPeriodo: g_DataInizio,
			dataFinePeriodo: g_DataFine,
		}).done(function (dataAjax) {
			// Se ho dati nel periodo selezionato
			if (dataAjax != 'NO_ROWS' && dataAjax != '') {
				var dati;
				dati = JSON.parse(dataAjax);

				// Definisco il nome del report
				nomeFilePDF =
					'RptOEERisorsa' +
					dataReport +
					'_' +
					g_DataInizio +
					'_' +
					g_DataFine;

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

				// Converto il grafico in immagine PNG
				var canvasImg = riferimentoGraficoOEERisorse.toDataURL('image/png', 1.0);

				// Costanti per formattazione corretta del grafico
				const imgProps = doc.getImageProperties(canvasImg);
				const pdfWidth = doc.internal.pageSize.getWidth();
				const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;

				// Aggiungo al documento il grafico degli ordini nel periodo
				doc.addImage(canvasImg, 'JPEG', 10, 20, pdfWidth - 20, pdfHeight);

				// Aggiungo al documento la tabella con la lista ordini nel periodo
				doc.autoTable({
					head: [
						[
							'Codice',
							'Prodotto',
							'Qta tot.',
							'Qta ok',
							'Data inizio',
							'Data fine',
							'Vel. macchina [pz/h]',
							'Disp. [%]',
							'Eff. [%]',
							'Qual. [%]',
							'OEE ris. [%] (comm.)',
							'OEE macc. [%] (media prd)',
						],
					],
					body: valoriTabella,
					theme: 'grid',
					margin: { horizontal: 10 },
					styles: { fontSize: 7 },
					headStyles: {
						lineWidth: 0.1,
						lineColor: [199, 199, 199],
					},
					startY: pdfHeight + 30,
				});

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
			} else {
				swal({
					title: 'ATTENZIONE!',
					text: 'Nessun dato disponibile.',
					icon: 'warning',
					button: 'Ho capito',

					closeModal: true,
				});
			}
		});
	});

	// ESPORTAZIONE DETTAGLIO COMMESSA SELEZIONATO PER LA RISORSA IN OGGETTO
	$('#rptRis-stampa-report-dettaglio').on('click', function () {
		// Ricavo la data attuale per generazione nome report
		var today = moment();
		var dataReport = today.format('YYYYMMDD');
		var dataReportIntestazione = today.format('DD/MM/YYYY');
		var descrizioneRisorsa = $('#rptRis_RisorseLinea option:selected').text();
		var idProduzioneSelezionato = $('#modal-risorsa-label-codOrdine').text();
		var dati;

		var valoriRiga = [];
		var valoriTabella = [];

		// Creo il documento e ricavo le dimensioni
		var doc = new jspdf.jsPDF({ orientation: 'landscape' });
		const pageWidth = doc.internal.pageSize.width;
		const pageHeight = doc.internal.pageSize.height;

		// Definisco il nome del report
		var nomeFilePDF = 'Rpt' + dataReport + '_DettaglioRisorsa_Ord' + idProduzioneSelezionato;

		// Scrittura intestazione report
		doc.setFontSize(10);
		doc.text(pageWidth - 60, 10, 'Report in data: ' + dataReportIntestazione);
		doc.text(10, 10, 'Macchina: ' + descrizioneRisorsa);
		doc.text(10, 15, 'Commessa selezionata: ' + idProduzioneSelezionato);

		// Recupero i dati degli ordini nel periodo
		// DETTAGLIO COMMESSA SELEZIONATO: visualizzazione distinta risorse per l'ordine di produzione selezionato
		$.post('inc_reportrisorse.php', {
			azione: 'rptRis-mostra-dettaglio-ordine',
			idProduzione: idProduzioneSelezionato,
			idRisorsa: g_rptRis_idRisorsa,
		}).done(function (dataAjax) {
			dati = JSON.parse(dataAjax);

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
						'Codice',
						'Prodotto',
						'Data inizio',
						'Data fine',
						'Qta tot.',
						'Qta ok',
						'T. tot.',
						'T. down',
						'T. attr.',
						'Delta T. attr.',
						'Vel. macchina [pz/h]',
						'Disp. [%]',
						'Eff. [%]',
						'Qual. [%]',
						'OEE ris. [%]',
					],
				],
				body: valoriTabella,
				theme: 'grid',
				margin: { horizontal: 10 },
				rowPageBreak: 'avoid',
				styles: { fontSize: 7 },
				headStyles: {
					lineWidth: 0.1,
					lineColor: [199, 199, 199],
				},
				startY: 20,
			});

			// Azzero array
			valoriRiga = [];
			valoriTabella = [];

			// CUMULATIVO EVENTI: visualizzazione periodi di downtime per risorsa e ordine di produzione selezionati
			$.post('inc_reportrisorse.php', {
				azione: 'rptRis-mostra-casi-produzione-cumulativo',
				idProduzione: idProduzioneSelezionato,
				idRisorsa: g_rptRis_idRisorsa,
			}).done(function (data) {
				if (data != 'NO_ROWS') {
					dati = JSON.parse(data);

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
				}

				// Aggiungo al documento la tabella con la lista ordini nel periodo
				doc.autoTable({
					head: [
						[
							{
								content: 'CUMULATIVO EVENTI',
								colSpan: 5,
								styles: { halign: 'left', fillColor: [22, 160, 133] },
							},
						],
						['Macchina', 'Evento', 'Tipo.', 'N° eventi', 'Durata [min]'],
					],
					body: valoriTabella,
					theme: 'grid',
					margin: { horizontal: 10 },
					styles: { fontSize: 7 },
					headStyles: {
						lineWidth: 0.1,
						lineColor: [199, 199, 199],
					},
					rowPageBreak: 'avoid',
				});

				// Azzero array
				valoriRiga = [];
				valoriTabella = [];

				// DETTAGLIO EVENTI: visualizzazione elenco casi verificatisi per risorsa e ordine di produzione selezionati
				$.post('inc_reportrisorse.php', {
					azione: 'rptRis-mostra-casi-ordine',
					idProduzione: idProduzioneSelezionato,
					tipoModal: 'ris',
					idRisorsa: g_rptRis_idRisorsa,
				}).done(function (data) {
					if (data != 'NO_ROWS') {
						dati = JSON.parse(data);

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
					}

					// Aggiungo al documento la tabella con la lista ordini nel periodo
					doc.autoTable({
						head: [
							[
								{
									content: 'DETTAGLIO EVENTI',
									colSpan: 7,
									styles: { halign: 'left', fillColor: [22, 160, 133] },
								},
							],
							[
								'Macchina',
								'Evento',
								'Tipo.',
								'Orario inizio',
								'Orario fine',
								'Durata [min]',
								'Note/segnalazioni',
							],
						],
						body: valoriTabella,
						theme: 'grid',
						margin: { horizontal: 10 },
						styles: { fontSize: 7 },
						headStyles: {
							lineWidth: 0.1,
							lineColor: [199, 199, 199],
						},
						rowPageBreak: 'avoid',
					});

					// Stampa numerazione pagine
					const pages = doc.internal.getNumberOfPages();
					const pageWidth = doc.internal.pageSize.width;
					const pageHeight = doc.internal.pageSize.height;
					for (let j = 1; j < pages + 1; j++) {
						let horizontalPos = pageWidth / 2;
						let verticalPos = pageHeight - 5;
						doc.setPage(j);
						doc.text(`Pag. ${j} di ${pages}`, horizontalPos, verticalPos, {
							align: 'center',
						});
					}

					// Esporto e salvo il documento creato
					doc.save(nomeFilePDF + '.pdf');
				});
			});
		});
	});
})(jQuery);
