(function ($) {
	var url = 'reportrendimento.php';
	var today = moment();
	var ago = moment().subtract(7, 'days');

	var inizioReportLinee = ago.format('YYYY-MM-DD');
	var fineReportLinee = today.format('YYYY-MM-DD');

	var inizioReportRisorse = ago.format('YYYY-MM-DD');
	var fineReportRisorse = today.format('YYYY-MM-DD');

	var inizioReportDiag = ago.format('YYYY-MM-DD');
	var fineReportDiag = today.format('YYYY-MM-DD');

	var rptLin_tabellaDatiOrdiniPeriodo;
	// RIFERIMENTO AL GRAFICO
	var datiGraficoOEELinea;

	// ISTANZIO IL GRAFICO
	var graficoOEELinea = new Chart($('#grOEELinea'), {
		type: 'line',
		data: datiGraficoOEELinea,
		options: {
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
						ci.getDatasetMeta(index).hidden === null
							? false
							: ci.getDatasetMeta(index).hidden;

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
		},
	});

	function graficoLinee() {
		var linea = $('#rptLin_LineeProduzione').val();
		var prodotto = $('#rptLin_Prodotti').val();
		var inizio = $('#rptLin_DataInizio').val();
		var fine = $('#rptLin_DataFine').val();

		$.post('inc_reportlinee.php', {
			azione: 'rptLin-calcola-valori-medi',
			idLineaProduzione: linea,
			idProdotto: prodotto,
			dataInizioPeriodo: inizio,
			dataFinePeriodo: fine,
		}).done(function (dataAjax) {
			if (dataAjax != 'NO_ROWS') {
				var datiMedi = JSON.parse(dataAjax);
				$('#rptLin_DMedio').val(datiMedi['FattoreDMedio']);
				$('#rptLin_EMedio').val(datiMedi['FattoreEMedio']);
				$('#rptLin_QMedio').val(datiMedi['FattoreQMedio']);
				$('#rptLin_OEEMedio').val(datiMedi['OEEMedio']);
			}
		});

		$.post(url, {
			azione: 'rptLin-recupera-oee-periodo',
			idLineaProduzione: linea,
			idProdotto: prodotto,
			dataInizioPeriodo: inizio,
			dataFinePeriodo: fine,
		}).done(function (data) {
			try {
				var dati = JSON.parse(data);
				console.log(dati);

				var labelsGrLinea = [];
				var datiGrOEELinea = [];
				var datiGrFattoreDLinea = [];
				var datiGrFattoreELinea = [];
				var datiGrFattoreQLinea = [];

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
			} catch (error) {
				console.error(error);
				console.log(data);
			}
		});
	}

	$(function () {
		var tmp;
		tmp = sessionStorage.getItem('inizioReportLinee');
		if (tmp) {
			inizioReportLinee = tmp;
		}
		$('#rptLin_DataInizio').val(inizioReportLinee);

		tmp = sessionStorage.getItem('fineReportLinee');
		if (tmp) {
			fineReportLinee = tmp;
		}
		$('#rptLin_DataFine').val(fineReportLinee);

		tmp = sessionStorage.getItem('inizioReportRisorse');
		if (tmp) {
			inizioReportRisorse = tmp;
		}
		$('#rptRis_DataInizio').val(inizioReportRisorse);

		tmp = sessionStorage.getItem('fineReportRisorse');
		if (tmp) {
			fineReportRisorse = tmp;
		}
		$('#rptRis_DataFine').val(fineReportRisorse);

		tmp = sessionStorage.getItem('inizioReportDiag');
		if (tmp) {
			inizioReportDiag = tmp;
		}
		$('#rptDia_DataInizio').val(inizioReportDiag);

		tmp = sessionStorage.getItem('fineReportDiag');
		if (tmp) {
			fineReportDiag = tmp;
		}
		$('#rptDia_DataFine').val(fineReportDiag);

		var activeTab = sessionStorage.getItem('activeTab_statistiche');
		if (activeTab) {
			$('#tab-statistiche a[href="' + activeTab + '"]').tab('show');
		} else {
			$('#tab-statistiche a[href="#report-produzioni"]').tab('show');
		}

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

			language: linguaItaliana,
			info: false,
			//"dom": "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4'l><'col-sm-8'p>>"
		});

		$.post(url, {
			azione: 'rptLin-carica-select-prodotti',
			idLineaProduzione: $('#rptLin_LineeProduzione').val(),
		}).done(function (data) {
			$('#rptLin_Prodotti').html(data);
			$('#rptLin_Prodotti').selectpicker('refresh');
			graficoLinee();
		});
	});

	$('#rptLin_DataInizio, #rptLin_DataFine').on('blur', function () {
		var inizio = $('#rptLin_DataInizio').val();
		var fine = $('#rptLin_DataFine').val();

		sessionStorage.setItem('inizioReportLinee', inizio);
		sessionStorage.setItem('fineReportLinee', fine);
		graficoLinee();
	});

	$('#rptRis_DataInizio, #rptRis_DataFine').on('blur', function () {
		var inizio = $('#rptRis_DataInizio').val();
		var fine = $('#rptRis_DataFine').val();

		sessionStorage.setItem('inizioReportRisorse', inizio);
		sessionStorage.setItem('fineReportRisorse', fine);
	});

	$('#rptDia_DataInizio, #rptDia_DataFine').on('blur', function () {
		var inizio = $('#rptDia_DataInizio').val();
		var fine = $('#rptDia_DataFine').val();

		sessionStorage.setItem('inizioReportDiag', inizio);
		sessionStorage.setItem('fineReportDiag', fine);
	});

	// PER POST REFRESH: MEMORIZZO TAB ATTUALMENTE MOSTRATO PER RIPRISTINO VISUALIZZAZIONE DELLO STESSO IN CASO DI REFRESH
	$('#tab-statistiche a[data-toggle="tab"]').on('show.bs.tab', function (e) {
		sessionStorage.setItem('activeTab_statistiche', $(e.target).attr('href'));
		var tabSelezionato = $(e.target).attr('href');

		if (tabSelezionato == '#report-organizzazione') {
			$('.card-title').html('ANALISI RENDIMENTO - ORGANIZZAZIONE');
		} else if (tabSelezionato == '#report-produzioni') {
			$('.card-title').html('ANALISI RENDIMENTO - LINEE');
		} else if (tabSelezionato == '#report-risorse') {
			$('.card-title').html('ANALISI RENDIMENTO - MACCHINE');
		} else if (tabSelezionato == '#report-diagnostica') {
			$('.card-title').html('ANALISI RENDIMENTO - DIAGNOSTICA');
		}
	});

	$('#rptLin_LineeProduzione').change(function () {
		sessionStorage.setItem('rptLin_LineeProduzione', $(this).val());

		$.post(url, {
			azione: 'rptLin-carica-select-prodotti',
			idLineaProduzione: $(this).val(),
		}).done(function (data) {
			$('#rptLin_Prodotti').html(data);
			$('#rptLin_Prodotti').selectpicker('refresh');
			graficoLinee();
		});
	});

	$('#rptLin_Prodotti').change(function () {
		graficoLinee();
	});
})(jQuery);
