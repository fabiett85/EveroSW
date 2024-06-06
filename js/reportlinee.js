// VARIABILI GLOBALI
var g_rptLin_idLineaProduzione;
var g_rptLin_idProdotto;
var g_rptLin_tipologiaGrafico;


// RIFERIMENTO AL GRAFICO
var riferimentoGraficoOEELinea = document.getElementById('grOEELinea');
var datiGraficoOEELinea;

// DEFINIZIONE OPZIONI DEL GRAFICO
var opzioniGraficoOEELinea = {
	borderWidth: 10,
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
var graficoOEELinea = new Chart(riferimentoGraficoOEELinea, {
	type: 'line',
	data: datiGraficoOEELinea,
	options: opzioniGraficoOEELinea,
});

// FUNZIONE DI RECUPERO DATI PERIODO E RELATIVA VISUALIZZAZIONE IN TABELLA E GRAFICO
function recuperaDatiGraficoOEELinea() {
	// Se ho una linea e un prodotto selezionati
	if (g_rptLin_idLineaProduzione != null && g_rptLin_idProdotto != null) {
		// Recupero i dati relativi all'OEE, secondo i criteri impsotati
		$.post('inc_reportlinee.php', {
			azione: 'rptLin-recupera-oee-periodo',
			idLineaProduzione: g_rptLin_idLineaProduzione,
			idProdotto: g_rptLin_idProdotto,
			dataInizioPeriodo: g_DataInizio,
			dataFinePeriodo: g_DataFine,
		}).done(function (dataAjax) {
			var labelsGrLinea = [];
			var datiGrOEELinea = [];
			var datiGrFattoreDLinea = [];
			var datiGrFattoreELinea = [];
			var datiGrFattoreQLinea = [];

			if (dataAjax != 'NO_ROWS') {
				var dati = JSON.parse(dataAjax);

				dati.forEach(function (entry) {
					labelsGrLinea.push(entry.IdProduzioneGrafico);

					datiGrOEELinea.push(entry.OEELinea);
					datiGrFattoreDLinea.push(entry.D);
					datiGrFattoreELinea.push(entry.E);
					datiGrFattoreQLinea.push(entry.Q);
				});

				$('#rptLin-tabella-ordini').dataTable().fnClearTable();
				if (dati.length > 0) {
					$('#rptLin-tabella-ordini').dataTable().fnAddData(dati);
				}

				datiGraficoOEELinea = {
					labels: labelsGrLinea,
					datasets: [
						{
							label: 'OEE',
							data: datiGrOEELinea,
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
							data: datiGrFattoreDLinea,
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
							data: datiGrFattoreELinea,
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
							data: datiGrFattoreQLinea,
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

				graficoOEELinea.data = datiGraficoOEELinea;
				graficoOEELinea.data.datasets.forEach((dataSet, i) => {
					var meta = graficoOEELinea.getDatasetMeta(i);
					if (i == 0) {
						meta.hidden = null;
					} else {
						meta.hidden = true;
					}
				});
				graficoOEELinea.update();
			} else {
				$('#rptLin-tabella-ordini').dataTable().fnClearTable();

				datiGraficoOEELinea = {
					labels: labelsGrLinea,
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

				graficoOEELinea.data = datiGraficoOEELinea;
				graficoOEELinea.update();
			}
		});

		$.post('inc_reportlinee.php', {
			azione: 'rptLin-calcola-valori-medi',
			idLineaProduzione: g_rptLin_idLineaProduzione,
			idProdotto: g_rptLin_idProdotto,
			dataInizioPeriodo: g_DataInizio,
			dataFinePeriodo: g_DataFine,
		}).done(function (dataAjax) {
			if (dataAjax != 'NO_ROWS') {
				var datiMedi = JSON.parse(dataAjax);

				$('#rptLin_DMedio').val(datiMedi['FattoreDMedio']);
				$('#rptLin_EMedio').val(datiMedi['FattoreEMedio']);
				$('#rptLin_QMedio').val(datiMedi['FattoreQMedio']);

				$('#rptLin_OEEMedio').val(datiMedi['OEEMedio']);
			}
		});
	}
}

// FUNZIONE PER POPOLAMENTO SELECT RISORSE
function rptLineapopolaSelectProdotti() {
	//popolo select prodotti
	$.post('inc_reportlinee.php', {
		azione: 'rptLin-carica-select-prodotti',
		idLineaProduzione: g_rptLin_idLineaProduzione,
		idProdotto: g_rptLin_idProdotto,
	}).done(function (data) {
		$('#rptLin_Prodotti').html(data);
		$('#rptLin_Prodotti').selectpicker('refresh');
	});
}

(function ($) {
	'use strict';

	var rptLin_tabellaDatiOrdiniPeriodo;
	var rptLin_tabellaDartiOrdineSelezionato;
	var rptLin_tabellaDatiDistintaRisorse;
	var rptLin_tabellaDatiDistintaComponenti;
	var rptLin_tabellaDatiCasiCumulativo;
	var rptLin_tabellaDatiCasi;
	var rptLin_tabellaDatiDowntime;

	var rptLin_linguaItaliana = {
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

	var rptLinDettaglio_linguaItaliana = {
		processing: 'Caricamento...',
		search: 'Ricerca: ',
		lengthMenu: '_MENU_ righe per pagina',
		zeroRecords: 'Nessuna dato disponibile',
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

		rptLin_tabellaDatiOrdiniPeriodo = $('#rptLin-tabella-ordini').DataTable({
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
				{ data: 'Lotto' },
				{ data: 'VelocitaLinea' },
				{ data: 'D' },
				{ data: 'E' },
				{ data: 'Q' },
				{ data: 'OEELinea' },
			],
			columnDefs: [
				{ className: 'td-d', targets: [8] },
				{ className: 'td-e', targets: [9] },
				{ className: 'td-q', targets: [10] },
				{ className: 'td-oee', targets: [11] },
			],

			language: rptLin_linguaItaliana,
			info: false,
			//"dom": "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4'l><'col-sm-8'p>>"
		});

		// DATATABLE DATI COMMESSA SELEZIONATO
		rptLin_tabellaDartiOrdineSelezionato = $('#rptLin-tabella-ordine-selezionato').DataTable({
			iDisplayLength: 1,

			columns: [
				{ data: 'IdProduzione' },
				{ data: 'DescrizioneProdotto' },
				{ data: 'DataInizio' },
				{ data: 'DataFine' },
				{ data: 'QtaProdotta' },
				{ data: 'QtaConforme' },
				{ data: 'Lotto' },
				{ data: 'TTotale' },
				{ data: 'Downtime' },
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

			language: rptLin_linguaItaliana,
			searching: false,
			info: false,
			ordering: false,
			paging: false,
			dom: "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4'l><'col-sm-8'p>>",
		});

		// DATATABLE RISORSE COINVOLTE PER L'COMMESSA SELEZIONATO
		rptLin_tabellaDatiDistintaRisorse = $('#rptLin-tabella-risorse-coinvolte').DataTable({
			order: [[1, 'desc']],
			aLengthMenu: [
				[5, 10, 25, 50, 100, -1],
				[5, 10, 25, 50, 100, 'Tutti'],
			],
			iDisplayLength: 5,

			columns: [
				{ data: 'Descrizione' },
				{ data: 'DataInizio' },
				{ data: 'DataFine' },
				{ data: 'TTotale' },
				{ data: 'Downtime' },
				{ data: 'Attrezzaggio' },
				{ data: 'DeltaAttrezzaggio' },
				{ data: 'Velocita' },
				{ data: 'OEERisorsa' },
			],
			columnDefs: [{ className: 'td-oee', targets: [8] }],

			language: rptLinDettaglio_linguaItaliana,
			searching: false,
			dom: "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4'l><'col-sm-8'p>>",
		});

		// DATATABLE DISTINTA COMPONENTI PER L'COMMESSA SELEZIONATO
		rptLin_tabellaDatiDistintaComponenti = $('#rptLin-tabella-componenti').DataTable({
			aLengthMenu: [
				[5, 10, 25, 50, 100, -1],
				[5, 10, 25, 50, 100, 'Tutti'],
			],
			iDisplayLength: 5,

			order: [[0, 'asc']],

			columns: [{ data: 'IdProdotto' }, { data: 'Descrizione' }, { data: 'QuantitaComponente' }],

			language: rptLinDettaglio_linguaItaliana,
			searching: false,
			dom: "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4'l><'col-sm-8'p>>",
		});

		// DATATABLE ELENCO CASI INSERITI (RAGGRUPPATI PER TIPOLOGIA)
		rptLin_tabellaDatiCasiCumulativo = $('#rptLin-tabella-casi-cumulativo').DataTable({
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

			language: rptLinDettaglio_linguaItaliana,
			searching: false,
			info: false,
			dom: "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4'l><'col-sm-8'p>>",
		});

		// DATATABLE ELENCO CASI INSERITI PER L'COMMESSA SELEZIONATO
		rptLin_tabellaDatiCasi = $('#rptLin-tabella-casi').DataTable({
			aLengthMenu: [
				[5, 10, 25, 50, 100, -1],
				[5, 10, 25, 50, 100, 'Tutti'],
			],
			iDisplayLength: 10,

			order: [[3, 'desc']],

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

			language: rptLinDettaglio_linguaItaliana,
			searching: false,
			dom: "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4'l><'col-sm-8'p>>",
		});
	});

	// ****** OPERAZIONI SVOLTE SU COMANDO UTENTE ******

	// AL CLIC SU UNO DEI PUNTI VISUALIZZATI SUL GRAFICO (OEE COMMESSA), RICAVO LABEL E VALORE
	$('body').on('click', '#grOEELinea', function (e) {
		var activePoints = graficoOEELinea.getElementsAtEventForMode(
			e,
			'point',
			graficoOEELinea.options
		);
		var firstPoint = activePoints[0];
		if (firstPoint != null) {
			var label = graficoOEELinea.data.labels[firstPoint._index];
			var idProduzioneLabel = label.slice(0, -13).trim();
			var value =
				graficoOEELinea.data.datasets[firstPoint._datasetIndex].data[firstPoint._index];
			var descrizioneLinea = $('#rptLin_LineeProduzione option:selected').text();

			$('#modal-linea-label-codOrdine')
				.text(idProduzioneLabel)
				.css('font-style', 'italic')
				.css('font-weight', 'bold');
			$('#modal-linea-label-descLinea')
				.text(descrizioneLinea)
				.css('font-style', 'italic')
				.css('font-weight', 'bold');

			//Visualizzazione distinta risorse per l'ordine di produzione selezionato
			$.post('inc_reportlinee.php', {
				azione: 'rptLin-mostra-dettaglio-ordine',
				idProduzione: idProduzioneLabel,
			}).done(function (data) {
				if (data != 'NO_ROWS') {
					$('#rptLin-tabella-ordine-selezionato').dataTable().fnClearTable();
					$('#rptLin-tabella-ordine-selezionato').dataTable().fnAddData(JSON.parse(data));
				} else {
					$('#rptLin-tabella-ordine-selezionato').dataTable().fnClearTable();
				}
			});

			//Visualizzazione distinta risorse per l'ordine di produzione selezionato
			$.post('inc_reportlinee.php', {
				azione: 'rptLin-mostra-distinta-risorse-ordine',
				idProduzione: idProduzioneLabel,
			}).done(function (data) {
				var dati = JSON.parse(data);
				if (dati.length > 0) {
					$('#rptLin-tabella-risorse-coinvolte').dataTable().fnClearTable();
					$('#rptLin-tabella-risorse-coinvolte').dataTable().fnAddData(dati);
				} else {
					$('#rptLin-tabella-risorse-coinvolte').dataTable().fnClearTable();
				}
			});

			//Visualizzazione distinta componenti per l'ordine di produzione selezionato
			$.post('inc_reportlinee.php', {
				azione: 'rptLin-mostra-distinta-componenti-ordine',
				idProduzione: idProduzioneLabel,
			}).done(function (data) {
				var dati = JSON.parse(data);
				if (dati.length > 0) {
					$('#rptLin-tabella-componenti').dataTable().fnClearTable();
					$('#rptLin-tabella-componenti').dataTable().fnAddData(dati);
				} else {
					$('#rptLin-tabella-componenti').dataTable().fnClearTable();
				}
			});

			//Visualizzazione elenco casi verificatisi per l'ordine di produzione selezionato (CUMULATIVO)
			$.post('inc_reportlinee.php', {
				azione: 'rptLin-mostra-casi-produzione-cumulativo',
				idProduzione: idProduzioneLabel,
			}).done(function (data) {
				var dati = JSON.parse(data);
				if (dati.length > 0) {
					$('#rptLin-tabella-casi-cumulativo').dataTable().fnClearTable();
					$('#rptLin-tabella-casi-cumulativo').dataTable().fnAddData(dati);
				} else {
					$('#rptLin-tabella-casi-cumulativo').dataTable().fnClearTable();
				}
			});

			//Visualizzazione elenco casi verificatisi per l'ordine di produzione selezionato
			$.post('inc_reportlinee.php', {
				azione: 'rptLin-mostra-casi-ordine',
				idProduzione: idProduzioneLabel,
				tipoModal: 'lin',
			}).done(function (data) {
				var dati = JSON.parse(data);
				if (dati.length > 0) {
					$('#rptLin-tabella-casi').dataTable().fnClearTable();
					$('#rptLin-tabella-casi').dataTable().fnAddData(dati);
				} else {
					$('#rptLin-tabella-casi').dataTable().fnClearTable();
				}
			});

			$('#modal-report-linea').modal('show');
		}
	});

	//  ALLA VARIAZIONE DELLA LINEA SELEZIONATA...
	$('#rptLin_LineeProduzione').on('change', function () {
		g_rptLin_idLineaProduzione = $('#rptLin_LineeProduzione').val();
		sessionStorage.setItem('rptLin_idLineaProduzione', g_rptLin_idLineaProduzione);

		//popolo select prodotti
		$.post('inc_reportlinee.php', {
			azione: 'rptLin-carica-select-prodotti',
			idLineaProduzione: g_rptLin_idLineaProduzione,
		}).done(function (data) {
			$('#rptLin_Prodotti').html(data);
			$('#rptLin_Prodotti').selectpicker('refresh');

			g_rptLin_idProdotto = $('#rptLin_Prodotti').val();
			sessionStorage.setItem('rptLin_idProdotto', g_rptLin_idProdotto);

			recuperaDatiGraficoOEELinea();
		});
	});

	// ALLA VARIAZIONE DEL PRODOTTO
	$('#rptLin_Prodotti').on('change', function () {
		// Aggiorno le variabili globali con i valori attualmente selezionati
		g_rptLin_idProdotto = $('#rptLin_Prodotti').val();

		// Memorizzo nelle variabili di sessione i valori recuperati
		sessionStorage.setItem('rptLin_idProdotto', g_rptLin_idProdotto);

		// Invoco funzione per recupero dati periodo
		recuperaDatiGraficoOEELinea();
	});

	$('#rptLin_DataInizio, #rptLin_DataFine').on('blur', function () {
		var nome = $(this).attr('id');
		window['g_' + nome + 'Periodo'] = $(this).val();
		sessionStorage.setItem(nome + 'Periodo', window['g_' + nome + 'Periodo']);
		var prefisso = nome.split('_')[0];
		var dataInizio = $('#' + prefisso + '_DataInizio').val();
		var dataFine = $('#' + prefisso + '_DataFine').val();
		if (dataFine >= dataInizio) {
			recuperaDatiGraficoOEELinea();
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
	$('#rptLin-stampa-report').on('click', function () {
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
		var descrizioneLinea = $('#rptLin_LineeProduzione option:selected').text();

		var valoriRiga = [];
		var valoriTabella = [];

		// Credo il documento
		var doc = new jspdf.jsPDF({ orientation: 'landscape' });
		const pageWidth = doc.internal.pageSize.width;
		const pageHeight = doc.internal.pageSize.height;

		// Scrittura intestazione report
		doc.setFontSize(10);
		doc.text(pageWidth - 60, 10, 'Report in data: ' + dataReportIntestazione);
		doc.text(10, 10, 'Linea: ' + descrizioneLinea);
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
				+g_DataFine.substring(8, 10) +
				'/' +
				g_DataFine.substring(5, 7) +
				'/' +
				g_DataFine.substring(0, 4)
		);

		// Recupero i dati degli ordini nel periodo
		$.post('inc_reportlinee.php', {
			azione: 'rptLin-recupera-oee-periodo',
			idLineaProduzione: g_rptLin_idLineaProduzione,
			idProdotto: g_rptLin_idProdotto,
			dataInizioPeriodo: g_DataInizio,
			dataFinePeriodo: g_DataFine,
		}).done(function (dataAjax) {
			// Se ho dati nel periodo selezionato
			if (dataAjax != 'NO_ROWS' && dataAjax != '') {
				var dati;
				dati = JSON.parse(dataAjax);

				// Definisco il nome del report
				nomeFilePDF =
					'RptOEELinea' +
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
				var canvasImg = riferimentoGraficoOEELinea.toDataURL('image/png', 1.0);

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
							'Qta OK',
							'Data inizio',
							'Data fine',
							'Lotto',
							'Vel. linea [pz/h]',
							'Disp. [%]',
							'Eff. [%]',
							'Qual. [%]',
							'OEE Linea [%]',
							'OEE Periodo [%]',
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

	// ESPORTAZIONE DETTAGLIO COMMESSA SELEZIONATO PER LA LINEA IN OGGETTO
	$('#rptLin-stampa-report-dettaglio').on('click', function () {
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
		var descrizioneLinea = $('#rptLin_LineeProduzione option:selected').text();
		var idProduzioneSelezionato = $('#modal-linea-label-codOrdine').text();
		var dati;

		var valoriRiga = [];
		var valoriTabella = [];

		// Creo il documento e ricavo le dimensioni
		var doc = new jspdf.jsPDF({ orientation: 'landscape' });
		const pageWidth = doc.internal.pageSize.width;
		const pageHeight = doc.internal.pageSize.height;

		// Definisco il nome del report
		var nomeFilePDF = 'Rpt' + dataReport + '_DettaglioLinea_Ord' + idProduzioneSelezionato;

		// Scrittura intestazione report
		doc.setFontSize(10);
		doc.text(pageWidth - 60, 10, 'Report in data: ' + dataReportIntestazione);
		doc.text(10, 10, 'Linea: ' + descrizioneLinea);
		doc.text(10, 15, 'Commessa selezionata: ' + idProduzioneSelezionato);

		// DETTAGLIO COMMESSA SELEZIONATO: visualizzazione distinta risorse per l'ordine di produzione selezionato
		$.post('inc_reportlinee.php', {
			azione: 'rptLin-mostra-dettaglio-ordine',
			idProduzione: idProduzioneSelezionato,
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
						'Codice',
						'Prodotto',
						'Data inizio',
						'Data fine',
						'Qta tot.',
						'Qta ok',
						'Lotto',
						'T. tot.',
						'T. down',
						'T. attr.',
						'Vel. linea [pz/h]',
						'Disp. [%]',
						'Eff. [%]',
						'Qual. [%]',
						'OEE linea. [%]',
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

			doc.setLineWidth(1.0);

			// DISTINTA RISORSE: visualizzazione distinta risorse per l'ordine di produzione selezionato
			$.post('inc_reportlinee.php', {
				azione: 'rptLin-mostra-distinta-risorse-ordine',
				idProduzione: idProduzioneSelezionato,
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
								content: 'ELENCO MACCHINE COINVOLTE',
								colSpan: 9,
								styles: { halign: 'left', fillColor: [22, 160, 133] },
							},
						],
						[
							'Descrizione',
							'Orario inizio',
							'Orario fine',
							'T. tot. [min]',
							'T. down [min]',
							'T. attr. [min]',
							'Delta T. attr. [min]',
							'OEE [%]',
							'Vel. reale [pz/h]',
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

				// Azzero array
				valoriRiga = [];
				valoriTabella = [];

				// DISTINTA COMPONENTI: visualizzazione distinta componenti per l'ordine di produzione selezionato
				$.post('inc_reportlinee.php', {
					azione: 'rptLin-mostra-distinta-componenti-ordine',
					idProduzione: idProduzioneSelezionato,
				}).done(function (data) {
					dati = JSON.parse(data);
					if (dati.length > 0) {
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
									content: 'ELENCO COMPONENTI PRODOTTO FINALE',
									colSpan: 3,
									styles: { halign: 'left', fillColor: [22, 160, 133] },
								},
							],
							['Componente', 'Descrizione', 'Qta'],
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

					// DETTAGLIO DOWNTIME: visualizzazione periodi di downtime per l'ordine di produzione selezionato
					$.post('inc_reportlinee.php', {
						azione: 'rptLin-mostra-casi-produzione-cumulativo',
						idProduzione: idProduzioneSelezionato,
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

						// DETTAGLIO EVENTI: visualizzazione elenco casi verificatisi per l'ordine di produzione selezionato
						$.post('inc_reportlinee.php', {
							azione: 'rptLin-mostra-casi-ordine',
							idProduzione: idProduzioneSelezionato,
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
		});
	});
})(jQuery);
