(function ($) {
	// VARIABILI GLOBALI
	var g_idProduzione;
	var g_progressivoParziale;

	var tabellaDatiOrdiniAvviati;
	var tabellaDatiOrdiniAttivi;
	var tabellaDatiOrdiniChiusi;

	var tabellaDatiDistintaRisorse;
	var tabellaDatiDistintaComponenti;
	var tabellaDatiCasi;
	var tabellaDatiCasiCumulativo;
	var tabellaDatiClienti;

	var tabSelezionato;

	var today = moment();

	// Grafico a Torta per visualizzazione OEE
	const originalDoughnutDraw = Chart.controllers.doughnut.prototype.draw;
	Chart.helpers.extend(Chart.controllers.doughnut.prototype, {
		draw: function () {
			const chart = this.chart;
			const { width, height, ctx, config } = chart.chart;

			const { datasets } = config.data;

			const dataset = datasets[0];
			const datasetData = dataset.data;
			const completed = datasetData[0];
			const text = `${completed}%`;
			let x, y, mid;

			originalDoughnutDraw.apply(this, arguments);

			const fontSize = (height / 130).toFixed(2);
			ctx.font = 'italic bold ' + fontSize + 'em Arial';
			ctx.textBaseline = 'center';

			x = Math.round((width - ctx.measureText(text).width) / 2);
			y = height / 1.8 - fontSize;
			ctx.fillStyle = '#000000';
			ctx.fillText(text, x, y);
			mid = x + ctx.measureText(text).width / 2;
		},
	});

	// FUNZIONE: RECUPERO DETTAGLI COMMESSA SELEZIONATO
	function recuperaDettaglioOrdine() {
		//Visualizzazione dati ordine produzione selezionato
		$.post('statoordini.php', {
			azione: 'recupera-ordine',
			idProduzione: g_idProduzione,
		}).done(function (data) {
			try {
				var dati = JSON.parse(data);

				for (const key in dati) {
					if (Object.hasOwnProperty.call(dati, key)) {
						const element = dati[key];
						$('#form-dati-ordine #' + key).val(element);
					}
				}

				if (dati['op_Stato'] == 'OK') {
					$('#form-dati-ordine input').css('background-color', '#ccffdd');
					$('#op_DataOraFine').val('IN CORSO...');
					// prova di lampeggio testo
					$('#op_DataOraFine, #op_Stato').css('color', 'transparent');
					var timeout = setTimeout(function () {
						$('#op_DataOraFine, #op_Stato').css('color', '#404040');
					}, 500);
				} else {
					$('#form-dati-ordine input').css('background-color', '#ecf1f8');
					$('#op_DataOraFine').css('color', '#404040');
					$('#op_Stato').css('color', '#404040');
				}

				tabellaDatiDistintaRisorse.ajax.url(
					'statoordini.php?azione=mostra-distinta-risorse&idProduzione=' + g_idProduzione
				);
				tabellaDatiDistintaRisorse.ajax.reload();

				tabellaDatiDistintaComponenti.ajax.url(
					'statoordini.php?azione=mostra-distinta-componenti&idProduzione=' +
						g_idProduzione +
						'&qtaRichiesta=' +
						dati['op_QtaDaProdurre']
				);
				tabellaDatiDistintaComponenti.ajax.reload();

				tabellaDatiCasiCumulativo.ajax.url(
					'statoordini.php?azione=mostra-casi-produzione-cumulativo&idProduzione=' +
						g_idProduzione
				);
				tabellaDatiCasiCumulativo.ajax.reload();

				tabellaDatiCasi.ajax.url(
					'statoordini.php?azione=mostra-casi-produzione&idProduzione=' + g_idProduzione
				);
				tabellaDatiCasi.ajax.reload();

				tabellaDatiClienti.ajax.url(
					'statoordini.php?azione=mostra-clienti&idProduzione=' + g_idProduzione
				);
				tabellaDatiClienti.ajax.reload();
			} catch (error) {
				console.error(error);
			}
		});
	}

	$(function () {
		$.fn.dataTable.moment('DD/MM/YYYY - HH:mm');

		// DATATABLE COMMESSE AVVIATI (STATO = 'OK')
		tabellaDatiOrdiniAvviati = $('#tabellaDati-ordini-avviati').DataTable({
			order: [
				[0, 'asc'],
				[8, 'desc'],
			],

			columns: [
				{ data: 'lp_Descrizione' },
				{ data: 'op_IdProduzione' },
				{ data: 'prd_Descrizione' },
				{ data: 'op_Lotto' },
				{ data: 'op_QtaDaProdurre' },
				{ data: 'rp_QtaProdotta' },
				{ data: 'rp_qtaConformi' },
				{ data: 'rp_qtaScarti' },
				{ data: 'DataOraInizio' },
				{ data: 'DataOraFine' },
				{ data: 'Velocita' },
				{ data: 'ValoreOee' },
				{ data: 'Oee' },
				{ data: 'azioni' },
				{ data: 'StatoLinea' },
			],
			columnDefs: [
				{
					targets: [5, 11, 14],
					visible: false,
				},
				{
					targets: [1, 2, 3, 4, 5, 6, 7, 8, 9, 11, 12, 13, 14],
					className: 'center-bolded',
				},
				{
					targets: [10],
					className: 'left',
				},
				{
					targets: [0],
					className: 'left-bolded',
				},
				{
					width: '3%',
					targets: [13],
				},
				{
					width: '13%',
					targets: [10],
				},
			],

			language: linguaItaliana,
			ordering: false,
			info: false,
			paging: false,
			autoWidth: false,

			ajax: {
				url: 'statoordini.php?azione=avviati',
				dataSrc: '',
			},

			// Colorazione delle righe tabella in base al valore del campo 'stato linea'
			drawCallback: function (settings) {
				var api = this.api();
				api.rows().every(function (rowIdx, tableLoop, rowLoop) {
					var data = this.data();
					var idRiga = this.node();
					var statoLinea = data['StatoLinea'];

					// Colorazione delle righe tabella in base al valore del campo stato
					if (statoLinea == 'at') {
						$('td', idRiga).eq(0).css('background-color', 'rgba(255, 204, 0, 0.8)');
					} else if (statoLinea == 'ko') {
						$('td', idRiga).eq(0).css('background-color', 'rgba(255, 0, 0, 0.9)');
					} else if (statoLinea == 'ok') {
						$('td', idRiga).eq(0).css('background-color', 'rgba(0, 255, 0, 0.9)');
					}

					var codiceOrdine = data['op_IdProduzione'].split()[0];
					var valoreOEEGrafico = parseFloat(data['ValoreOee']);

					var idGrafico = 'grOEE_' + codiceOrdine;
					new Chart($('#' + idGrafico), {
						type: 'doughnut',
						data: {
							labels: [],
							datasets: [
								{
									label: '',
									data: [valoreOEEGrafico, 100 - valoreOEEGrafico],
									backgroundColor: ['#007acc', '#b3e0ff'],
								},
							],
						},
						options: {
							responsive: false,
							tooltips: { enabled: false },
							hover: { mode: null },
						},
					});
				});
			},
			buttons: [
				{
					extend: 'excel',
					text: 'EXCEL',
					className: 'btn-success',
					filename: buttonFilename('CommesseAvviate', today),
					title: buttonTitle('CommesseAvviate', today),
					exportOptions: {
						columns: [0, 1, 2, 3, 4, 6, 7, 8, 9],
					},
				},
				{
					extend: 'pdf',
					text: 'PDF',
					className: 'btn-danger',
					filename: buttonFilename('CommesseAvviate', today),
					title: buttonTitle('CommesseAvviate', today),
					orientation: 'landscape',
					download: 'open',
					exportOptions: {
						columns: [0, 1, 2, 3, 4, 6, 7, 8, 9],
					},
				},
			],
			dom:
				"<'row'<'col-sm-12 col-md-6'B><'col-sm-12 col-md-6'f>>" +
				"<'row'<'col-sm-12'tr>>" +
				"<'row'<'col-sm-12 col-md-6'><'col-sm-12 col-md-6'p>>",
		});

		// DATATABLE COMMESSE ATTIVI (STATO = 'ATTIVO')
		tabellaDatiOrdiniAttivi = $('#tabellaDati-ordini-attivi').DataTable({
			aLengthMenu: [
				[8, 16, 24, 32, 100, -1],
				[8, 16, 24, 32, 100, 'Tutti'],
			],
			iDisplayLength: 8,

			order: [[5, 'desc']],
			ajax: {
				url: 'statoordini.php?azione=attivi',
				dataSrc: '',
			},
			columns: [
				{ data: 'IdProduzione' },
				{ data: 'Prodotto' },
				{ data: 'Lotto' },
				{ data: 'DescrizioneLinea' },
				{ data: 'QtaRichiesta' },
				{ data: 'DataOraProgrammazione' },
				{ data: 'DataOraFinePrevista' },
				{ data: 'azioni' },
			],
			columnDefs: [
				{
					targets: [7],
					className: 'center-bolded',
				},
				{
					width: '1%',
					targets: [7],
				},
				{
					targets: [4, 5, 6],
					width: '15%',
				},
			],

			language: linguaItaliana,
			autoWidth: false,

			buttons: [
				{
					extend: 'excel',
					text: 'EXCEL',
					className: 'btn-success',
					filename: buttonFilename('CommesseAttive', today),
					title: buttonTitle('CommesseAttive', today),
					exportOptions: {
						columns: [0, 1, 2, 3, 4, 5, 6],
					},
				},
				{
					extend: 'pdf',
					text: 'PDF',
					className: 'btn-danger',
					filename: buttonFilename('CommesseAttive', today),
					title: buttonTitle('CommesseAttive', today),
					orientation: 'landscape',
					download: 'open',
					exportOptions: {
						columns: [0, 1, 2, 3, 4, 5, 6],
					},
				},
			],
			dom:
				"<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
				"<'row'<'col-sm-12'tr>>" +
				"<'row'<'col-sm-12 col-md-6 d-flex justify-content-left align-items-center'iB><'col-sm-12 col-md-6'p>>",
		});

		// DATATABLE COMMESSE COMPLETATE (STATO = 'CHIUSO')
		tabellaDatiOrdiniChiusi = $('#tabellaDati-ordini-chiusi').DataTable({
			aLengthMenu: [
				[8, 16, 24, 32, 100, -1],
				[8, 16, 24, 32, 100, 'Tutti'],
			],
			iDisplayLength: 8,

			order: [[8, 'desc']],
			ajax: {
				url: 'statoordini.php?azione=chiusi',
				dataSrc: '',
			},
			columns: [
				{ data: 'IdProduzione' },
				{ data: 'Prodotto' },
				{ data: 'Lotto' },
				{ data: 'DescrizioneLinea' },
				{ data: 'QtaRichiesta' },
				{ data: 'QtaProdotta' },
				{ data: 'QtaConforme' },
				{ data: 'QtaScarti' },
				{ data: 'DataOraInizio' },
				{ data: 'DataOraFine' },
				{ data: 'Oee' },
				{ data: 'azioni' },
			],
			columnDefs: [
				{
					targets: [5],
					visible: false,
				},
				{
					targets: [11],
					className: 'center-bolded',
				},
				{
					width: '3%',
					targets: [11],
				},
				{
					width: '8%',
					targets: [2, 4, 5, 6, 7, 10, 11],
				},
				{
					width: '10%',
					targets: [0, 1, 3, 8, 9],
				},
				{
					width: '15%',
					targets: [2],
				},
			],

			language: linguaItaliana,
			autoWidth: false,
			buttons: [
				{
					extend: 'excel',
					text: 'EXCEL',
					className: 'btn-success',
					filename: buttonFilename('CommesseChiuse', today),
					title: buttonTitle('CommesseChiuse', today),
					exportOptions: {
						columns: [0, 1, 2, 3, 4, 6, 7, 8, 9, 10],
					},
				},
				{
					extend: 'pdf',
					text: 'PDF',
					className: 'btn-danger',
					filename: buttonFilename('CommesseChiuse', today),
					title: buttonTitle('CommesseChiuse', today),
					orientation: 'landscape',
					download: 'open',
					exportOptions: {
						columns: [0, 1, 2, 3, 4, 6, 7, 8, 9, 10],
					},
				},
			],
			dom:
				"<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
				"<'row'<'col-sm-12'tr>>" +
				"<'row'<'col-sm-12 col-md-6 d-flex justify-content-left align-items-center'iB><'col-sm-12 col-md-6'p>>",
		});

		// DATATABLE DISTINTA ELENCO RISORSE COINVOLTE NELLA PRODUZIONE
		tabellaDatiDistintaRisorse = $('#tabellaDati-distinta-risorse').DataTable({
			aLengthMenu: [
				[8, 16, 24, 32, 100, -1],
				[8, 16, 24, 32, 100, 'Tutti'],
			],
			iDisplayLength: 8,

			order: [[19, 'asc']],
			ajax: {
				url: 'statoordini.php?azione=mostra-distinta-risorse&idProduzione=' + g_idProduzione,
				dataSrc: '',
			},

			columns: [
				{ data: 'Descrizione' },
				{ data: 'DataInizio' },
				{ data: 'DataFine' },
				{ data: 'QtaConforme' },
				{ data: 'QtaScarti' },
				{ data: 'TTotale' },
				{ data: 'Attrezzaggio' },
				{ data: 'Downtime' },
				{ data: 'DRisorsa' },
				{ data: 'ERisorsa' },
				{ data: 'QRisorsa' },
				{ data: 'OEERisorsa' },
				{ data: 'Velocita' },
				{ data: 'AuxStatoDowntime_Man' },
				{ data: 'AuxStatoDowntime_Auto' },
				{ data: 'AuxStatoAttrezzaggio_Man' },
				{ data: 'AuxStatoAttrezzaggio_Auto' },
				{ data: 'AuxStatoPPrevista_Man' },
				{ data: 'AuxStatoPPrevista_Auto' },
				{ data: 'Ordinamento' },
			],
			columnDefs: [
				{
					targets: [13, 14, 15, 16, 17, 18, 19],
					visible: false,
				},
				{
					className: 'td-d',
					targets: [8],
				},
				{
					className: 'td-e',
					targets: [9],
				},
				{
					className: 'td-q',
					targets: [10],
				},
				{ className: 'td-oee', targets: [11] },
			],

			language: linguaItaliana,
			searching: false,
			info: false,
			drawCallback: function (settings) {
				var api = this.api();
				api.rows().every(function (rowIdx, tableLoop, rowLoop) {
					var data = this.data();
					var idRiga = this.node();
					var statoAvariaMan = this.data()['AuxStatoDowntime_Man'];
					var statoAvariaScada = this.data()['AuxStatoDowntime_Man'];
					var statoAttMan = this.data()['AuxStatoAttrezzaggio_Man'];
					var statoAttScada = this.data()['AuxStatoAttrezzaggio_Auto'];
					var statoPPrevistaMan = this.data()['AuxStatoPPrevista_Man'];
					var statoPPrevistaScada = this.data()['AuxStatoPPrevista_Auto'];
					var dataFine = this.data()['DataFine'];

					// Colorazione delle righe tabella in base al valore del campo 'stato'
					if (dataFine == 'IN CORSO...') {
						if (statoPPrevistaMan == 1 || statoPPrevistaScada == 1) {
							$(idRiga).css('background-color', 'rgba(179, 209, 255, 0.8)');
						} else if (statoAttMan == 1 || statoAttScada == 1) {
							$(idRiga).css('background-color', 'rgba(255, 204, 0, 0.8)');
							$(idRiga).fadeOut(250).fadeIn(250);
						} else if (statoAvariaMan == 1 || statoAvariaScada == 1) {
							$(idRiga).css('background-color', 'rgba(255, 0, 0, 0.8)');
							$(idRiga).fadeOut(250).fadeIn(250);
						}
					}
				});
			},
			dom: "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4'l><'col-sm-8'p>>",
		});

		// DATATABLE DISTINTA COMPONENTI COINVOLTI NELLA PRODUZIONE
		tabellaDatiDistintaComponenti = $('#tabellaDati-distinta-componenti').DataTable({
			aLengthMenu: [
				[8, 16, 24, 32, 100, -1],
				[8, 16, 24, 32, 100, 'Tutti'],
			],
			iDisplayLength: 8,

			ajax: {
				url:
					'statoordini.php?azione=mostra-distinta-componenti&idProduzione=' +
					g_idProduzione +
					'&qtaRichiesta=0',
				dataSrc: '',
			},
			order: [[0, 'asc']],

			columns: [{ data: 'IdProdotto' }, { data: 'Descrizione' }, { data: 'QuantitaComponente' }],

			language: linguaItaliana,
			searching: false,
			info: false,
			dom: "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4'l><'col-sm-8'p>>",
		});

		// DATATABLE ELENCO CASI INSERITI
		tabellaDatiCasi = $('#tabellaDati-casi-produzione').DataTable({
			aLengthMenu: [
				[8, 16, 24, 32, 100, -1],
				[8, 16, 24, 32, 100, 'Tutti'],
			],
			iDisplayLength: 8,

			order: [
				[0, 'asc'],
				[2, 'desc'],
			],
			ajax: {
				url: 'statoordini.php?azione=mostra-casi-produzione&idProduzione=' + g_idProduzione,
				dataSrc: '',
			},

			columns: [
				{ data: 'DescrizioneRisorsa' }, //0
				{ data: 'DescrizioneCaso' }, //1
				{ data: 'TipoEvento' }, //2
				{ data: 'Gruppo' }, //3
				{ data: 'DataInizio' }, //4
				{ data: 'DataFine' }, //5
				{ data: 'Durata' }, //6
				{ data: 'Note' }, //7
			],
			columnDefs: [
				{
					width: '5%',
					targets: [2, 6],
				},
				{
					width: '8%',
					targets: [3, 4, 5],
				},
				{
					targets: [0],
					width: '10%',
				},
				{
					targets: [1, 7],
					width: '15%',
				},
			],

			language: linguaItaliana,
			searching: false,
			info: false,
			dom: "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4'l><'col-sm-8'p>>",
		});

		// DATATABLE ELENCO CASI INSERITI (RAGGRUPPATI PER TIPOLOGIA)
		tabellaDatiCasiCumulativo = $('#tabellaDati-casi-produzione-cumulativo').DataTable({
			aLengthMenu: [
				[8, 16, 24, 32, 100, -1],
				[8, 16, 24, 32, 100, 'Tutti'],
			],
			iDisplayLength: 8,
			ajax: {
				url:
					'statoordini.php?azione=mostra-casi-produzione-cumulativo&idProduzione=' +
					g_idProduzione,
				dataSrc: '',
			},
			order: [
				[0, 'asc'],
				[2, 'desc'],
			],

			columns: [
				{ data: 'DescrizioneRisorsa' },
				{ data: 'DescrizioneCaso' },
				{ data: 'TipoEvento' },
				{ data: 'Gruppo' },
				{ data: 'NumeroEventi' },
				{ data: 'Durata' },
			],

			language: linguaItaliana,
			searching: false,
			info: false,
			dom: "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4'l><'col-sm-8'p>>",
		});

		// DATATABLE ELENCO CASI INSERITI
		tabellaDatiClienti = $('#tabellaDati-clienti').DataTable({
			aLengthMenu: [
				[8, 16, 24, 32, 100, -1],
				[8, 16, 24, 32, 100, 'Tutti'],
			],
			iDisplayLength: 8,

			ajax: {
				url: 'statoordini.php?azione=mostra-clienti&idProduzione=' + g_idProduzione,
				dataSrc: '',
			},

			columns: [{ data: 'cl_Descrizione' }, { data: 'co_Qta' }, { data: 'co_Note' }],

			language: linguaItaliana,
			searching: false,
			info: false,
			dom: "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4'l><'col-sm-8'p>>",
		});

		// POST REFRESH: RECUPERO IMPOSTAZIONI DI VISUALIZZAZIONE PANNELLI, IN BASE ALL'ULTIMA NAVIGAZIONE EFFETTUATA
		// Se è la prima volta che accedo, carico configurazione di default (pannello elenco mostrato, pannello dettaglio nascosto)
		if (sessionStorage.getItem('show_pannelloElencoOrdini') === null) {
			sessionStorage.setItem('show_pannelloElencoOrdini', true);
			$('#pannelloElencoOrdini').collapse('show');
			$('#pannelloDettaglioOrdine').collapse('hide');
		}
		// Se stavo visualizzando 'pannello dettaglio', riparto con la medesima visualizzazione (pannello elenco nascosto, pannello dettaglio mostrato) e recupero anche l'ultimo ID produzione caricato
		else if (sessionStorage.getItem('show_pannelloElencoOrdini') == 'false') {
			if (sessionStorage.getItem('idOrdineProduzione') != null) {
				g_idProduzione = sessionStorage.getItem('idOrdineProduzione');
				g_progressivoParziale = sessionStorage.getItem('progressivoParzialeOrdine');
				$('#pannelloElencoOrdini').collapse('hide');
				$('#pannelloDettaglioOrdine').collapse('show');
				$('#ritorna-elenco-ordini').prop('hidden', false);

				if (sessionStorage.getItem('activeTab_statoOrdini') == '#stato-ordini-avviati') {
					$('#riprendi-ordine-parziale').prop('hidden', true);
				} else {
					$('#riprendi-ordine-parziale').prop('hidden', false);
				}

				recuperaDettaglioOrdine();
			}
		}
		// Se stavo visualizzando 'pannello elenco ordini', riparto con la medesima visualizzazione (pannello elenco mostrato, pannello dettaglio nascosto)
		else if (sessionStorage.getItem('show_pannelloElencoOrdini') == 'true') {
			$('#pannelloElencoOrdini').collapse('show');
			$('#pannelloDettaglioOrdine').collapse('hide');
			$('#ritorna-elenco-ordini').prop('hidden', true);
		}

		// POST REFRESH: MEMORIZZO PANNELLO ATTUALMENTE MOSTRATO PER RIPRISTINO IN CASO DI REFRESH
		$('#pannelloElencoOrdini').on('shown.bs.collapse', function () {
			sessionStorage.setItem('show_pannelloElencoOrdini', true);
			sessionStorage.setItem('show_pannelloDettaglioOrdine', false);
			$('#ritorna-elenco-ordini').prop('hidden', true);
		});

		$('#pannelloElencoOrdini').on('hidden.bs.collapse', function () {
			sessionStorage.setItem('show_pannelloElencoOrdini', false);
			sessionStorage.setItem('show_pannelloDettaglioOrdine', true);
			$('#ritorna-elenco-ordini').prop('hidden', false);
		});

		// POST REFRESH: MEMORIZZO TAB ATTUALMENTE MOSTRATO PER RIPRISTINO IN CASO DI REFRESH (TAB STATO COMMESSE)
		$('#tab-stato-ordini a[data-toggle="tab"]').on('show.bs.tab', function (e) {
			tabSelezionato = $(e.target).attr('href');

			if (tabSelezionato == '#stato-ordini-avviati') {
				tabellaDatiOrdiniAvviati.ajax.reload();
			} else if (tabSelezionato == '#stato-ordini-attivi') {
				tabellaDatiOrdiniAttivi.ajax.reload();
			} else if (tabSelezionato == '#stato-ordini-chiusi') {
				tabellaDatiOrdiniChiusi.ajax.reload();
			}

			sessionStorage.setItem('activeTab_statoOrdini', $(e.target).attr('href'));
		});

		var activeTab_statoOrdini = sessionStorage.getItem('activeTab_statoOrdini');
		if (activeTab_statoOrdini) {
			$('#tab-stato-ordini a[href="' + activeTab_statoOrdini + '"]').tab('show');
		} else {
			$('#tab-stato-ordini a[href="#stato-ordini-avviati"]').tab('show');
		}

		// POST REFRESH: MEMORIZZO TAB ATTUALMENTE MOSTRATO PER RIPRISTINO IN CASO DI REFRESH (TAB ELENCHI DETTAGLIO)
		$('#tab-elenchi a[data-toggle="tab"]').on('show.bs.tab', function (e) {
			sessionStorage.setItem('activeTab_elenchi', $(e.target).attr('href'));
		});

		var activeTab_elenchi = sessionStorage.getItem('activeTab_elenchi');
		if (activeTab_elenchi) {
			$('#tab-elenchi a[href="' + activeTab_elenchi + '"]').tab('show');
		}

		// OP. SCHEDULATE (OGNI 1 SECONDO): REFRESH DETTAGLIO COMMESSA DI PRODUZIONE
		setInterval(function () {
			// se sto visualizzando 'pannelo dettaglio', invoco la funzione di recupero dettaglio ordine
			if ($('#pannelloDettaglioOrdine').hasClass('show')) {
				recuperaDettaglioOrdine();
			}
		}, 1000);

		// OP. SCHEDULATE (OGNI 10 SECONDI): REFRESH DELLE TABELLE IN BASE AL TAB IN CUI MI TROVO
		setInterval(function () {
			// Se sono nel tab 'stato eventi', eseguo il refresh dei dati
			if (tabSelezionato == '#manutenzione-stato-eventi') {
				mostraTabStatoEventi();
			} else if (tabSelezionato == '#manutenzione-ordinaria') {
				mostraTabManOrdinarie();
			} else if (tabSelezionato == '#stato-ordini-avviati') {
				tabellaDatiOrdiniAvviati.ajax.reload();
			} else if (tabSelezionato == '#stato-ordini-attivi') {
				tabellaDatiOrdiniAttivi.ajax.reload();
			} else if (tabSelezionato == '#stato-ordini-chiusi') {
				tabellaDatiOrdiniChiusi.ajax.reload();
			}
		}, 10000);
	});

	// DETTAGLIO COMMESSA DI PRODUZIONE: CHIUDI PANNELLO ELENCO COMMESSE, APRI PANNELLO DETTAGLIO
	$('body').on('click', '.espandi-dettaglio-ordine', function (e) {
		e.preventDefault();

		// Ricavo l'ID produzione selezionato e lo imposto nella variabile globale apposita e nella relativa variabile di sessione
		g_idProduzione = $(this).data('idProduzione');
		sessionStorage.setItem('idOrdineProduzione', g_idProduzione);

		g_progressivoParziale = $(this).data('progressivo-parziale');
		sessionStorage.setItem('progressivoParzialeOrdine', g_progressivoParziale);

		// Funzione di recupero dettagli ordine
		recuperaDettaglioOrdine();

		// Imposto adeguatamente la visualizzazione dei pannelli (toggle collapse) e del pulsante di chiusura dettaglio
		$('#pannelloElencoOrdini').collapse('hide');
		$('#pannelloDettaglioOrdine').collapse('show');
		sessionStorage.setItem('show_pannelloElencoOrdini', false);
		sessionStorage.setItem('show_pannelloDettaglioOrdine', true);
		$('#ritorna-elenco-ordini').prop('hidden', false);

		// Gestisco visualizzazione pulsante 'ripresa ordine parziale'
		if (sessionStorage.getItem('activeTab_statoOrdini') == '#stato-ordini-avviati') {
			$('#riprendi-ordine-parziale').prop('hidden', true);
		} else {
			$('#riprendi-ordine-parziale').prop('hidden', false);
		}

		return false;
	});

	// DETTAGLIO COMMESSA DI PRODUZIONE: CHIUDI PANNELLO DETTAGLIO E TORNA A PANNELLO ELENCO COMMESSE
	$('body').on('click', '#ritorna-elenco-ordini', function (e) {
		e.preventDefault();

		// svuoto tabelle di visualizzazione distinte, componenti e casi
		$('#tabellaDati-distinta-risorse').dataTable().fnClearTable();
		$('#tabellaDati-distinta-componenti').dataTable().fnClearTable();
		$('#tabellaDati-casi-produzione').dataTable().fnClearTable();

		// svuoto variabili del form
		$('#form-dati-ordine')[0].reset();

		// imposto adeguatamente la visualizzazione dei pannelli (toggle collapse) e del pulsante di chiusura dettaglio
		$('#pannelloElencoOrdini').collapse('show');
		$('#pannelloDettaglioOrdine').collapse('hide');
		sessionStorage.setItem('show_pannelloElencoOrdini', true);
		sessionStorage.setItem('show_pannelloDettaglioOrdine', false);
		$('#ritorna-elenco-ordini').prop('hidden', true);

		return false;
	});
})(jQuery);
