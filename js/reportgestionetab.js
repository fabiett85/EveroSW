var g_DataInizio = moment().subtract(1, 'week').format('YYYY-MM-DD');
var g_DataFine = moment().format('YYYY-MM-DD');


(function($) {

	//VALORE DEFAULT SELECTPICKER
	$(".selectpicker").selectpicker({
		noneSelectedText : 'Seleziona'
	});


	// PER POST REFRESH: MEMORIZZO TAB ATTUALMENTE MOSTRATO PER RIPRISTINO VISUALIZZAZIONE DELLO STESSO IN CASO DI REFRESH
	$('#tab-statistiche a[data-toggle="tab"]').on('show.bs.tab', function(e) {

		sessionStorage.setItem('activeTab_statistiche', $(e.target).attr('href'));
		var tabSelezionato = $(e.target).attr('href');



		if (tabSelezionato == '#report-organizzazione') {

			$('.card-title').html('ANALISI RENDIMENTO - ORGANIZZAZIONE');


			// impostazione variabile ID LINEA PRODUZIONE
			if (sessionStorage.getItem('rptOrg_idLineaProduzione') === null) {
				g_rptOrg_idLineaProduzione = null;
			}
			else {
				g_rptOrg_idLineaProduzione = sessionStorage.getItem('rptOrg_idLineaProduzione');
			}
			$('#rptOrg_LineeProduzione').val(g_rptOrg_idLineaProduzione);
			$("#rptOrg_LineeProduzione").selectpicker('refresh');


			// impostazione variabile DATA INIZIO TRACCIATE
			if (sessionStorage.getItem('g_DataInizio') === null) {

			}
			else {
				g_DataInizio = sessionStorage.getItem('g_DataInizio');
			}
			$('#rptOrg_DataInizio').val(g_DataInizio);


			// impostazione variabile DATA FINE TRACCIATE
			if (sessionStorage.getItem('g_DataFine') === null) {

			}
			else {
				g_DataFine = sessionStorage.getItem('g_DataFine');
			}
			$('#rptOrg_DataFine').val(g_DataFine);


			if (g_rptOrg_idLineaProduzione != "") {
				recuperaDatiGraficoOEEOrganizzazione();
			}


		}
		else if (tabSelezionato == '#report-produzioni') {

			$('.card-title').html('ANALISI RENDIMENTO - LINEE');


			// impostazione variabile ID LINEA PRODUZIONE
			if (sessionStorage.getItem('rptLin_idLineaProduzione') === null) {
				g_rptLin_idLineaProduzione = null;
			}
			else {
				g_rptLin_idLineaProduzione = sessionStorage.getItem('rptLin_idLineaProduzione');
			}
			$('#rptLin_LineeProduzione').val(g_rptLin_idLineaProduzione);
			$("#rptLin_LineeProduzione").selectpicker('refresh');


			// impostazione variabile ID PRODOTTO
			if (sessionStorage.getItem('rptLin_idProdotto') === null) {
				g_rptLin_idProdotto = null;
			}
			else {
				g_rptLin_idProdotto = sessionStorage.getItem('rptLin_idProdotto');
			}


			// impostazione variabile DATA INIZIO TRACCIATE
			if (sessionStorage.getItem('g_DataInizio') === null) {
			}
			else {
				g_DataInizio = sessionStorage.getItem('g_DataInizio');
			}
			$('#rptLin_DataInizio').val(g_DataInizio);


			// impostazione variabile DATA FINE TRACCIATE
			if (sessionStorage.getItem('g_DataFine') === null) {
			}
			else {
				g_DataFine = sessionStorage.getItem('g_DataFine');
			}
			$('#rptLin_DataFine').val(g_DataFine);


			if (g_rptLin_idLineaProduzione != null ) {

				//popolo select prodotti
				$.post("reportrendimento.php", { azione: "rptLin-carica-select-prodotti", idLineaProduzione: g_rptLin_idLineaProduzione, idProdotto: g_rptLin_idProdotto })
				.done(function(data) {

					$('#rptLin_Prodotti').html(data);
					$("#rptLin_Prodotti").selectpicker('refresh');
					g_rptLin_idProdotto = $('#rptLin_Prodotti').val();

					// Invoco funzione per recupero dati periodo
					recuperaDatiGraficoOEELinea();
				});
			}


		}
		else if (tabSelezionato == '#report-risorse') {

			$('.card-title').html('ANALISI RENDIMENTO - MACCHINE');


			// impostazione variabile ID LINEA PRODUZIONE
			if (sessionStorage.getItem('rptRis_idLineaProduzione') === null) {
				g_rptRis_idLineaProduzione = null;
			}
			else {
				g_rptRis_idLineaProduzione = sessionStorage.getItem('rptRis_idLineaProduzione');
			}
			$('#rptRis_LineeProduzione').val(g_rptRis_idLineaProduzione);
			$("#rptRis_LineeProduzione").selectpicker('refresh');


			// impostazione variabile ID RISORSA
			if (sessionStorage.getItem('rptRis_idRisorsa') === null) {
				g_rptRis_idRisorsa = null;
			}
			else {
				g_rptRis_idRisorsa = sessionStorage.getItem('rptRis_idRisorsa');
			}


			// impostazione variabile ID PRODOTTO
			if (sessionStorage.getItem('rptRis_idProdotto') === null) {
				g_rptRis_idProdotto = "%";
			}
			else {
				g_rptRis_idProdotto = sessionStorage.getItem('rptRis_idProdotto');
			}


			// impostazione variabile DATA INIZIO TRACCIATE
			if (sessionStorage.getItem('g_DataInizio') === null) {
			}
			else {
				g_DataInizio = sessionStorage.getItem('g_DataInizio');
			}
			$('#rptRis_DataInizio').val(g_DataInizio);


			// impostazione variabile DATA FINE TRACCIATE
			if (sessionStorage.getItem('g_DataFine') === null) {
			}
			else {
				g_DataFine = sessionStorage.getItem('g_DataFine');
			}
			$('#rptRis_DataFine').val(g_DataFine);

			// verifico se ho una linea selezionata
			if (g_rptRis_idLineaProduzione != null) {

				//popolo select risorse
				$.post("reportrendimento.php", { azione: "rptRis-carica-select-risorse", idLineaProduzione: g_rptRis_idLineaProduzione, idRisorsa: g_rptRis_idRisorsa })
				.done(function(data) {

					$('#rptRis_RisorseLinea').html(data);
					$("#rptRis_RisorseLinea").selectpicker('refresh');
					g_rptRis_idRisorsa = $('#rptRis_RisorseLinea').val();

					// verifico se ho una risorsa selezionata
					if (g_rptRis_idRisorsa != null) {

						//popolo select prodotti
						$.post("reportrendimento.php", { azione: "rptRis-carica-select-prodotti", idRisorsa: g_rptRis_idRisorsa, idProdotto: g_rptRis_idProdotto })
						.done(function(data) {

							$('#rptRis_Prodotti').html(data);
							$("#rptRis_Prodotti").selectpicker('refresh');
							g_rptRis_idProdotto = $('#rptRis_Prodotti').val();

							recuperaDatiGraficoOEERisorse();
						});
					}
				});
			}


		}
		else if (tabSelezionato == '#report-diagnostica') {

			$('.card-title').html('ANALISI RENDIMENTO - DIAGNOSTICA');


			// impostazione variabile ID LINEA PRODUZIONE
			if (sessionStorage.getItem('rptDia_idLineaProduzione') === null) {
				g_rptDia_idLineaProduzione = null;
			}
			else {
				g_rptDia_idLineaProduzione = sessionStorage.getItem('rptDia_idLineaProduzione');
			}
			$('#rptDia_LineeProduzione').val(g_rptDia_idLineaProduzione);
			$("#rptDia_LineeProduzione").selectpicker('refresh');


			// impostazione variabile ID RISORSA
			if (sessionStorage.getItem('rptDia_idRisorsa') === null) {
				g_rptDia_idRisorsa = null;
			}
			else {
				g_rptDia_idRisorsa = sessionStorage.getItem('rptDia_idRisorsa');
			}

			// impostazione variabile DATA INIZIO TRACCIATE
			if (sessionStorage.getItem('g_DataInizio') === null) {
			}
			else {
				g_DataInizio = sessionStorage.getItem('g_DataInizio');
			}
			$('#rptDia_DataInizio').val(g_DataInizio);


			// impostazione variabile DATA FINE TRACCIATE
			if (sessionStorage.getItem('g_DataFine') === null) {
			}
			else {
				g_DataFine = sessionStorage.getItem('g_DataFine');
			}
			$('#rptDia_DataFine').val(g_DataFine);


			if (g_rptDia_idLineaProduzione != null && g_rptDia_idRisorsa != null) {
				//popolo select risorse
				$.post("reportrendimento.php", { azione: "rptDia-carica-select-risorse", idLineaProduzione: g_rptDia_idLineaProduzione, idRisorsa: g_rptDia_idRisorsa })
				.done(function(data) {

					$('#rptDia_RisorseLinea').html(data);
					$("#rptDia_RisorseLinea").selectpicker('refresh');
					g_rptDia_idRisorsa = $('#rptDia_RisorseLinea').val();

					recuperaDatiGraficoOrePerse();
				});
			}
			else {

			}
		}


	});

	var activeTab = sessionStorage.getItem('activeTab_statistiche');
	if(activeTab){
		$('#tab-statistiche a[href="' + activeTab + '"]').tab('show');
	}
	else {
		$('#tab-statistiche a[href="#report-produzioni"]').tab('show');
	}

	var dataInizio = sessionStorage.getItem('g_DataInizio');
	if(dataInizio){
		$('.dataInizio').val(dataInizio)
	}

	$('.dataInizio').on('change', function (e) {
		var dataInizio = $(this).val()
		sessionStorage.setItem('g_DataInizio', dataInizio)
		$('.dataInizio').val(dataInizio)
	})

	var dataFine = sessionStorage.getItem('g_DataFine');
	if(dataFine){
		$('.dataFine').val(dataFine)
	}

	$('.dataFine').on('change', function (e) {
		var dataFine = $(this).val()
		sessionStorage.setItem('g_DataFine', dataFine)
		$('.dataFine').val(dataFine)
	})

})(jQuery);