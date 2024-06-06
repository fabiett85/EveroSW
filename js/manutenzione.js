var g_mnt_idLineaProduzione;
var g_mnt_tipoEvento;
var g_mnt_statoEvento;
var g_mntOrd_idRisorsa;
var g_mntOrd_statoEvento;

var tabellaDatiOrdiniAvviati;
var tabellaDatiOrdiniAttivi;
var tabellaDatiOrdiniChiusi;
var tabellaDatiStatoEventi;
var tabellaDatiManutenzioniOrdinarie;

var tabSelezionato;


// Grafico a Torta per visualizzazione OEE
const originalDoughnutDraw = Chart.controllers.doughnut.prototype.draw;
Chart.helpers.extend(Chart.controllers.doughnut.prototype, {
	draw: function() {
			const chart = this.chart;
			const {
			  width,
			  height,
			  ctx,
			  config
			} = chart.chart;

		const {
			datasets
		} = config.data;

		const dataset = datasets[0];
		const datasetData = dataset.data;
		const completed = datasetData[0];
		const text = `${completed}%`;
		let x, y, mid;

		originalDoughnutDraw.apply(this, arguments);

		const fontSize = (height / 130).toFixed(2);
		ctx.font = "italic bold "+fontSize+"em Arial";
		ctx.textBaseline = "center";


		x = Math.round((width - ctx.measureText(text).width) / 2);
		y = (height / 1.8) - fontSize;
		ctx.fillStyle = "#000000"
		ctx.fillText(text, x, y);
		mid = x + ctx.measureText(text).width / 2;
	}
});


// Funzione per visualizzazione tabella 'MANUTENZIONI ORDINARIE'
function mostraTabManOrdinarie() {
	$.post("manutenzione.php", { azione: "manutenzione-ordinaria-mostra", idRisorsa: g_mntOrd_idRisorsa, statoEvento: g_mntOrd_statoEvento })
	.done(function(data) {

		var currentPage = tabellaDatiManutenzioniOrdinarie.page();

		if (data != "NO_ROWS") {
			$('#tabellaDati-manutenzione-ordinaria').dataTable().fnClearTable();
			$('#tabellaDati-manutenzione-ordinaria').dataTable().fnAddData(JSON.parse(data));
			tabellaDatiManutenzioniOrdinarie.page(currentPage).draw(false);
		}
		else {
			$('#tabellaDati-manutenzione-ordinaria').dataTable().fnClearTable();
		}
	});
}

// Funzione per visualizzazione tabella 'MANUTENZIONI STRAORDINARIE (STATO EVENTI)'
function mostraTabStatoEventi() {

	$.post("manutenzione.php", { azione: "manutenzione-mostra-stato-eventi", idLineaProduzione: g_mnt_idLineaProduzione, tipoEvento: g_mnt_tipoEvento, statoEvento: g_mnt_statoEvento })
	.done(function(data) {

		var currentPage = tabellaDatiStatoEventi.page();

		if (data != "NO_ROWS") {
			$('#tabellaDati-manutenzione-stato-eventi').dataTable().fnClearTable();
			$('#tabellaDati-manutenzione-stato-eventi').dataTable().fnAddData(JSON.parse(data));
			tabellaDatiStatoEventi.page(currentPage).draw(false);
		}
		else {
			$('#tabellaDati-manutenzione-stato-eventi').dataTable().fnClearTable();
		}
	});
}


// Funzione per visualizzazione tabella 'COMMESSE AVVIATI'
function mostraTabOrdiniAvviati() {

	$.post("manutenzione.php", { azione: "mostra-ordini-avviati" })
	.done(function(data) {

		if (data != "NO_ROWS") {

			$('#tabellaDati-ordini-avviati').dataTable().fnClearTable();
			$('#tabellaDati-ordini-avviati').dataTable().fnAddData(JSON.parse(data));

			// Scorro le righe della tabella e vado a popolare gli oggetti 'pie' per la visualizzazione dell'OEE
			tabellaDatiOrdiniAvviati.rows().every( function ( rowIdx, tableLoop, rowLoop ) {

				var data = this.data();
				var codiceOrdine = this.data()['IdProduzioneOEE'].trim();
				var valoreOEEGrafico = parseFloat(this.data()['ValoreOee']);
				var idGrafico = "grOEE_"+codiceOrdine;

				var context = document.getElementById(idGrafico).getContext('2d');
				var chart = new Chart(context, {
					type: 'doughnut',
					data: {
						labels: [],
						datasets: [{
							label: '',
							data: [valoreOEEGrafico, 100 - valoreOEEGrafico],
							backgroundColor: ['#007acc', '#b3e0ff']
						}]
					},
					options: {
						responsive: false,
						tooltips: {enabled: false},
						hover: {mode: null}
					}
				});

			});
		}
		else {
			$('#tabellaDati-ordini-avviati').dataTable().fnClearTable();
		}
	});
}


// Funzione per visualizzazione tabella 'COMMESSE ATTIVI'
function mostraTabOrdiniAttivi() {

	$.post("manutenzione.php", { azione: "mostra-ordini-attivi" })
	.done(function(data) {

		if (data != "NO_ROWS") {
			$('#tabellaDati-ordini-attivi').dataTable().fnClearTable();
			$('#tabellaDati-ordini-attivi').dataTable().fnAddData(JSON.parse(data));
		}
		else {
			$('#tabellaDati-ordini-attivi').dataTable().fnClearTable();
		}
	});
}


// Funzione per visualizzazione tabella 'COMMESSE CHIUSI'
function mostraTabOrdiniChiusi() {

	$.post("manutenzione.php", { azione: "mostra-ordini-chiusi" })
	.done(function(data) {

		if (data != "NO_ROWS") {
			$('#tabellaDati-ordini-chiusi').dataTable().fnClearTable();
			$('#tabellaDati-ordini-chiusi').dataTable().fnAddData(JSON.parse(data));
		}
		else {
			$('#tabellaDati-ordini-chiusi').dataTable().fnClearTable();
		}
	});
}







(function($) {

	'use strict';

	var linguaItaliana = {
		"processing": "Caricamento...",
		"search": "Ricerca: ",
		"lengthMenu": "_MENU_ righe per pagina",
		"zeroRecords": "Nessun ordine presente per la categoria selezionata.",
		"info": "Pagina _PAGE_ di _PAGES_",
		"infoEmpty": "Nessun ordine disponibile",
		"infoFiltered": "(filtrate da _MAX_ righe totali)",
		"paginate": {
		    "first":      "Prima",
		    "last":       "Ultima",
		    "next":       "Prossima",
		    "previous":   "Precedente"
		}
	}

	var linguaItalianaEventi = {
		"processing": "Caricamento...",
		"search": "Ricerca: ",
		"lengthMenu": "_MENU_ righe per pagina",
		"zeroRecords": "Nessun evento registrato per l'ordine selezionato",
		"info": "Pagina _PAGE_ di _PAGES_",
		"infoEmpty": "Nessun ordine disponibile",
		"infoFiltered": "(filtrate da _MAX_ righe totali)",
		"paginate": {
		    "first":      "Prima",
		    "last":       "Ultima",
		    "next":       "Prossima",
		    "previous":   "Precedente"
		}
	}

	var linguaItalianaManutenzione = {
		"processing": "Caricamento...",
		"search": "Ricerca: ",
		"lengthMenu": "_MENU_ righe per pagina",
		"zeroRecords": "Nessuna manutenzione programmata",
		"info": "Pagina _PAGE_ di _PAGES_",
		"infoEmpty": "Nessun ordine disponibile",
		"infoFiltered": "(filtrate da _MAX_ righe totali)",
		"paginate": {
		    "first":      "Prima",
		    "last":       "Ultima",
		    "next":       "Prossima",
		    "previous":   "Precedente"
		}
	}


	$(function() {

		$.fn.dataTable.moment("DD/MM/YYYY - HH:mm:ss");


		// DATATABLE COMMESSE AVVIATI (STATO = 'OK')
		tabellaDatiOrdiniAvviati = $('#tabellaDati-ordini-avviati').DataTable({
			"aLengthMenu": [
				[8, 16, 24, 32, 100, -1],
				[8, 16, 24, 32, 100, "Tutti"]
			],
			"iDisplayLength": 8,

			"order": [[ 0, "asc" ], [ 8, "desc" ]],

			"columns": [
				{ "data": "DescrizioneLinea"},
				{ "data": "IdProduzione" },
				{ "data": "Prodotto" },
				{ "data": "Lotto" },
			    { "data": "QtaRichiesta" },
			    { "data": "QtaProdotta" },
				{ "data": "QtaConforme" },
				{ "data": "QtaScarti" },
			    { "data": "DataOraInizio" },
			    { "data": "DataOraFine" },
				{ "data": "VelocitaLinea" },
			    { "data": "ValoreOee" },
				{ "data": "Oee" },
			    { "data": "azioni" },
				{ "data": "StatoLinea" }
			],
			"columnDefs": [
				{
					"targets": [ 5, 11, 12, 13, 14 ],
					"visible": false,
				},
				{
					"targets": [ 1, 2, 3, 4, 5, 6, 7, 8, 9, 11, 12, 13, 14  ],
					"className": 'center-bolded',
				},
				{
					"targets": [ 10 ],
					"className": 'left',
				},
				{
					"targets": [  0 ],
					"className": 'left-bolded',
				},
				{
					"width": "3%",
					"targets": [ 13 ],
				},
				{
					"width": "15%",
					"targets": [ 10 ],
				},
				{
					"width": "7%",
					"targets": [ 1, 3, 4, 5, 6, 7 ],
				},
				{
					"targets": [ 0, 2, 9, 8, 12 ],
					"width": "10%",
				}
			],

			"language": linguaItaliana,
			"ordering": false,
			"info":     false,
			"paging": 	false,
			"autoWidth": false,

			// Colorazione delle righe tabella in base al valore del campo 'stato linea'
		    "drawCallback": function( settings ) {
				var api = this.api();
				api.rows().every( function ( rowIdx, tableLoop, rowLoop ) {

					var data = this.data();
					var idRiga = this.node();

					var statoLinea = this.data()['StatoLinea'];

					// Colorazione delle righe tabella in base al valore del campo stato
					if (statoLinea == "at") {
						$('td', idRiga).eq(0).css('background-color', 'rgba(255, 204, 0, 0.8)');
					}
					else if (statoLinea == "av") {
						$('td', idRiga).eq(0).css('background-color', 'rgba(255, 0, 0, 0.9)');
					}
					if (statoLinea == "ok") {
						$('td', idRiga).eq(0).css('background-color', 'rgba(0, 255, 0, 0.9)');
					}

				});
			}
		});


		// DATATABLE COMMESSE ATTIVI (STATO = 'ATTIVO')
		tabellaDatiOrdiniAttivi = $('#tabellaDati-ordini-attivi').DataTable({
			"aLengthMenu": [
				[8, 16, 24, 32, 100, -1],
				[8, 16, 24, 32, 100, "Tutti"]
			],
			"iDisplayLength": 8,

			"order": [[ 5, "desc" ]],

			"columns": [
			    { "data": "IdProduzione" },
				{ "data": "Prodotto" },
				{ "data": "Lotto" },
				{ "data": "DescrizioneLinea"},
			    { "data": "QtaRichiesta" },
			    { "data": "DataOraProgrammazione" },
				{ "data": "DataOraFinePrevista" },
			    { "data": "azioni" }
			],
			"columnDefs": [
				{
					"targets": [ 7 ],
					"visible": false,
				},
				{
					"targets": [ 7 ],
					"className": 'center-bolded',
				},
				{
					"width": "1%",
					"targets": [ 7 ],
				},
				{
					"targets": [ 4, 5, 6 ],
					"width": "15%",
				}
			],

			"language": linguaItaliana,
			"autoWidth": false
		});


		// DATATABLE COMMESSE COMPLETATE (STATO = 'CHIUSO')
		tabellaDatiOrdiniChiusi = $('#tabellaDati-ordini-chiusi').DataTable({
			"aLengthMenu": [
				[8, 16, 24, 32, 100, -1],
				[8, 16, 24, 32, 100, "Tutti"]
			],
			"iDisplayLength": 8,

			"order": [[ 5, "desc" ]],

			"columns": [
			    { "data": "IdProduzione" },
				{ "data": "Prodotto" },
				{ "data": "DescrizioneLinea"},
			    { "data": "QtaRichiesta" },
			    { "data": "QtaProdotta" },
			    { "data": "DataOraInizio" },
			    { "data": "DataOraFine" },
				{ "data": "Oee" }
			],
			"columnDefs": [
				{
					"targets": [ 5, 6 ],
					"width": "15%",
				}
			],
			 "autoWidth": false,
			"language": linguaItaliana,
		});


		// DATATABLE MANUTENZIONI STRAORDINARIE (DASHBOARD EVENTI)
		tabellaDatiStatoEventi = $('#tabellaDati-manutenzione-stato-eventi').DataTable({
			"order": [[ 2, "desc" ]],
			"aLengthMenu": [
				[8, 16, 24, 32, 100, -1],
				[8, 16, 24, 32, 100, "Tutti"]
			],
			"iDisplayLength": 8,

			"columns": [
				{ "data": "Linea"},
				{ "data": "Risorsa"},
				{ "data": "DataInizio"},
				{ "data": "DataFine"},
				{ "data": "Evento"},
				{ "data": "Tipo"},
				{ "data": "Gruppo"},
				{ "data": "Riconosciuto"},
				{ "data": "Risolto"},
				{ "data": "PulsanteRiconosciuto"},
				{ "data": "PulsanteRisolto"}
			],
			"columnDefs": [
				{
					"targets": [ 7, 8 ],
					"visible": false,
				},
				{
					"targets": [ 9, 10 ],
					"className": 'center-bolded',
					"orderable": false,
					"width": "5%",
				},
				{
					"targets": [ 2, 3 ],
					"width": "15%",
				}
			],
			"language": linguaItalianaEventi,
			"info":     	false,

			// Colorazione righe tabella in base a 'stato evento'
		    "drawCallback": function( settings ) {
				var api = this.api();
				api.rows().every( function ( rowIdx, tableLoop, rowLoop ) {

					var data = this.data();
					var idRiga = this.node();
					var statoRiconosciuto = this.data()['Riconosciuto'];
					var statoRisolto = this.data()['Risolto'];


					if ((statoRiconosciuto == 0 || statoRiconosciuto === null ) && (statoRisolto == 0  || statoRisolto === null)) {
						$(idRiga).css('background-color', 'rgba(255, 0, 0, 1)');
					}
					else if (statoRisolto == 1) {
						$(idRiga).css('background-color', 'rgba(0, 255, 0, 0.8)');
					}
					else if ((statoRiconosciuto == 1) && (statoRisolto == 0 || statoRisolto === null)) {
						$(idRiga).css('background-color', 'rgba(255, 255, 0, 1)');
					}

				});
			},
			stateSave:		true,
			"autoWidth": 	false

		});


		// DATATABLE MANUTENZIONI ORDINARIE
		tabellaDatiManutenzioniOrdinarie = $('#tabellaDati-manutenzione-ordinaria').DataTable({
			"order": [[ 1, "desc" ]],
			"aLengthMenu": [
				[8, 16, 24, 32, 100, -1],
				[8, 16, 24, 32, 100, "Tutti"]
			],
			"iDisplayLength": 8,

			"columns": [
			    { "data": "Risorsa"},
				{ "data": "DataOraInizio"},
				{ "data": "DataOraFine"},
				{ "data": "Descrizione"},
				{ "data": "BloccoLinea"},
				{ "data": "Riconosciuto"},
				{ "data": "Risolto"},
				{ "data": "PulsanteRisolto"},
				{ "data": "Azioni"}
			],
			"columnDefs": [
				{
					"targets": [ 5, 6 ],
					"visible": false,
				},
				{
					"targets": [ 4, 7, 8],
					"className": 'center-bolded',
					"orderable": false,
					"width": "8%",
				},
				{
					"targets": [ 4 ],
					"className": 'center-bolded',
					"orderable": false,
					"width": "10%",
				}
			],

			// Colorazione righe tabella in base a 'stato evento'
		    "drawCallback": function( settings ) {
				var api = this.api();
				api.rows().every( function ( rowIdx, tableLoop, rowLoop ) {

					var data = this.data();
					var idRiga = this.node();
					var statoRiconosciuto = this.data()['Riconosciuto'];
					var statoRisolto = this.data()['Risolto'];


					if ((statoRiconosciuto == 0 || statoRiconosciuto === null ) && (statoRisolto == 0  || statoRisolto === null)) {
						$(idRiga).css('background-color',  'rgba(255, 0, 0, 1)');
					}
					else if (statoRisolto == 1) {
						$(idRiga).css('background-color',  'rgba(0, 196, 0, 1)');
					}
					else if ((statoRiconosciuto == 1) && (statoRisolto == 0 || statoRisolto === null)) {
						$(idRiga).css('background-color',  'rgba(255, 255, 0, 1)');
					}

				});
			},
			"language": 	linguaItalianaManutenzione,
			"info":     	false,
			"autoWidth": 	false

		});



		// Imposto testo di default per selectpicker 'elenco risorse' per il tab 'manutenzione ordinaria'
		$(".selectpicker").selectpicker({
			noneSelectedText : 'Seleziona una macchina'
		});



		// CON CADENZA REGOLARE (OGNI 10 SECONDI) REFRESH DELLE TABELLE
		setInterval( function () {

			// Se sono nel tab 'stato eventi', eseguo il refresh dei dati
			if (tabSelezionato == '#manutenzione-stato-eventi') {
				mostraTabStatoEventi();
			}
			else if (tabSelezionato == '#manutenzione-ordinaria') {
				mostraTabManOrdinarie();
			}
			else if (tabSelezionato == '#manutenzione-stato-ordini-avviati') {
				mostraTabOrdiniAvviati();
			}
			else if (tabSelezionato == '#manutenzione-stato-ordini-attivi') {
				mostraTabOrdiniAttivi();
			}
			else if (tabSelezionato == '#manutenzione-stato-ordini-chiusi') {
				mostraTabOrdiniChiusi();
			}

		}, 10000 );



		// PER POST REFRESH: MEMORIZZO TAB ATTUALMENTE MOSTRATO PER RIPRISTINO VISUALIZZAZIONE DELLO STESSO IN CASO DI REFRESH (TAB STATO COMMESSE)
		$('#tab-manutenzione-stato-ordini a[data-toggle="tab"]').on('show.bs.tab', function(e) {

			tabSelezionato = $(e.target).attr('href');

			if (tabSelezionato == '#manutenzione-ordinaria') {

				// variabile ID LINEA PRODUZIONE
				if (sessionStorage.getItem('mntOrd_idRisorsa') === null) {
					g_mntOrd_idRisorsa = "%";
				}
				else {
					g_mntOrd_idRisorsa = sessionStorage.getItem('mntOrd_idRisorsa');
				}
				$('#mntOrd_FiltroRisorse').val(g_mntOrd_idRisorsa);
				$("#mntOrd_FiltroRisorse").selectpicker('refresh');


				// variabile STATO EVENTO
				if (sessionStorage.getItem('mntOrd_statoEvento') === null) {
					g_mntOrd_statoEvento = "%";
				}
				else {
					g_mntOrd_statoEvento = sessionStorage.getItem('mntOrd_statoEvento');
				}
				$('#mntOrd_StatoEvento').val(g_mntOrd_statoEvento);
				$("#mntOrd_StatoEvento").selectpicker('refresh');

				mostraTabManOrdinarie();

			}
			else if (tabSelezionato == '#manutenzione-stato-eventi') {

				// variabile ID LINEA PRODUZIONE
				if (sessionStorage.getItem('mnt_idLineaProduzione') === null) {
					g_mnt_idLineaProduzione = "%";
				}
				else {
					g_mnt_idLineaProduzione = sessionStorage.getItem('mnt_idLineaProduzione');
				}
				$('#mnt_LineeProduzione').val(g_mnt_idLineaProduzione);
				$("#mnt_LineeProduzione").selectpicker('refresh');


				// variabile TIPO EVENTO
				if (sessionStorage.getItem('mnt_tipoEvento') === null) {
					g_mnt_tipoEvento = "%";
				}
				else {
					g_mnt_tipoEvento = sessionStorage.getItem('mnt_tipoEvento');
				}
				$('#mnt_TipoEvento').val(g_mnt_tipoEvento);
				$("#mnt_TipoEvento").selectpicker('refresh');


				// variabile STATO EVENTO
				if (sessionStorage.getItem('mnt_statoEvento') === null) {
					g_mnt_statoEvento = "%";
				}
				else {
					g_mnt_statoEvento = sessionStorage.getItem('mnt_statoEvento');
				}
				$('#mnt_StatoEvento').val(g_mnt_statoEvento);
				$("#mnt_StatoEvento").selectpicker('refresh');

				mostraTabStatoEventi();
			}
			else if (tabSelezionato == '#manutenzione-stato-ordini-avviati') {

				mostraTabOrdiniAvviati();
			}
			else if (tabSelezionato == '#manutenzione-stato-ordini-attivi') {
				mostraTabOrdiniAttivi();
			}
			else if (tabSelezionato == '#manutenzione-stato-ordini-chiusi') {
				mostraTabOrdiniChiusi();
			}


			sessionStorage.setItem('activeTab_manutenzioneStatoOrdini', $(e.target).attr('href'));
		});

		var activeTab_manutenzioneStatoOrdini = sessionStorage.getItem('activeTab_manutenzioneStatoOrdini');
		if(activeTab_manutenzioneStatoOrdini){
			$('#tab-manutenzione-stato-ordini a[href="' + activeTab_manutenzioneStatoOrdini + '"]').tab('show');
		}
		else {
			$('#tab-manutenzione-stato-ordini a[href="#manutenzione-stato-eventi"]').tab('show');
		}

	});





	// *** MANUTENZIONI STRAORDINARIE (EVENTI) ***

	//  MANUTENZIONI STRAORDINARIE: ALLA VARIAZIONE DEI CAMPI 'LINEE PRODUZIONE', 'TIPO EVENTO', 'STATO EVENTO', ESEGUO IL REFRESH DEI DATI DELLA TABELLA 'STATO EVENTI'
	$('#mnt_LineeProduzione, #mnt_TipoEvento, #mnt_StatoEvento').on('change',function(){

		//
		g_mnt_idLineaProduzione = $('#mnt_LineeProduzione').val();
		g_mnt_tipoEvento = $('#mnt_TipoEvento').val();
		g_mnt_statoEvento = $('#mnt_StatoEvento').val();

		// memorizzo nelle variabili di sessione i valori recuperati
		sessionStorage.setItem('mnt_idLineaProduzione', g_mnt_idLineaProduzione);
		sessionStorage.setItem('mnt_tipoEvento', g_mnt_tipoEvento);
		sessionStorage.setItem('mnt_statoEvento', g_mnt_statoEvento);

		mostraTabStatoEventi()
	});



	// MANUTENZIONI STRAORDINARIE: CLIC SU PULSANTE 'RISOLVI'
	$('body').on('click','.riconosci-man-str',function(e){

		e.preventDefault();

		var idRiga = $(this).data('id_riga');

		//Visualizzazione dati ordini attualmente ATTIVI
		$.post("manutenzione.php", { azione: "riconosci-man-str", idRiga })
		.done(function(dataRiconoscimento) {

			if (dataRiconoscimento == "OK") {

				mostraTabStatoEventi()
			}
		});

		return false;

	});



	// MANUTENZIONI STRAORDINARIE: VISUALIZZAZIONE POPUP 'RISOLVI'
	$('body').on('click','.risolvi-man-str',function(e){

		e.preventDefault();

		var idRiga = $(this).data('id_riga');

		$.post("manutenzione.php", { azione: "recupera-man-str", idRiga: idRiga })
		.done(function(data) {

			$('#form-risolvi-man-str')[0].reset();

			var dati = JSON.parse(data);

			$('#form-risolvi-man-str #manStrRis_IdRisorsa').val(dati['ac_IdRisorsa']);
			$('#form-risolvi-man-str #manStrRis_DescrizioneRisorsa').val(dati['ris_Descrizione']);
			$('#form-risolvi-man-str #manStrRis_IdEvento').val(dati['ac_IdEvento']);
			$('#form-risolvi-man-str #manStrRis_DescrizioneEvento').val(dati['ac_DescrizioneEvento']);
			$('#form-risolvi-man-str #manStrRis_IdRiga').val(idRiga);
			$('#form-risolvi-man-str #manStrRis_NumeroRapporto').val(dati['im_NumeroRapporto']);
			$('#form-risolvi-man-str #manStrRis_Riconosciuto').val(dati['im_Riconosciuto']);

			var dataOraEvento = dati['ac_DataInizio']+"T"+dati['ac_OraInizio'];
			$("#form-risolvi-man-str #manStrRis_DataEvento").val(dataOraEvento);


			$('#form-risolvi-man-str input, textarea').removeClass('errore');
			$("#modal-risolvi-evento").modal("show");
		});

		return false;

	});


	// MANUTENZIONI STRAORDINARIE: CANCELLAZIONE SEGNALAZIONE DI CAMPO VUOTO
	$('#form-risolvi-man-str input, textarea').on('blur',function(){
		$(this).removeClass('errore');
	})


	// MANUTENZIONI STRAORDINARIE: SALVATAGGIO RAPPORTO DI LAVORO DA POPUP 'RISOLVI'
	$('body').on('click','#salva-rapporto-man-str',function(e){

		e.preventDefault();

		// inizializzo il contatore errori
		var errori = 0;

		// controllo che i campi obbligatori siano tutti riempiti
		$('#form-risolvi-man-str .obbligatorio').each(function(){


			if($(this).val() == "")
			{
				errori++;
				$(this).addClass('errore');
			}
		});

		// se ho anche solo un errore mi fermo qui
		if(errori > 0)
		{
			swal({
				title: "Attenzione",
				text: "Compilare tutti i campi obbligatori contrassegnati con *",
				icon: "warning",
				button: "Ho capito",

				closeModal: true,

			});
		}
		else // nessun errore, posso continuare
		{

			// salvo i dati
			$.post("manutenzione.php", { azione: "salva-rapporto-man-str", data: $('#form-risolvi-man-str').serialize() })
			.done(function(data) {


				// se è tutto OK
				if(data == "OK")
				{
					$("#modal-risolvi-evento").modal("hide");

					mostraTabStatoEventi()

				}
				else // restituisco un errore senza chiudere la modale
				{
					swal({
						title: "Operazione non eseguita.",
						text: data,
						icon: "warning",
						button: "Ho capito",

						closeModal: true,

					});
				}

			});
		}
		return false;

	});



	//  MANUTENZIONI STRAORDINARIE: VISUALIZZAZIONE RAPPORTO DI LAVORO
	$('body').on('click','.vedi-rapporto-man-str',function(e){

		e.preventDefault();

		var idRiga = $(this).data('id_riga');

		$.post("manutenzione.php", { azione: "recupera-man-str", idRiga: idRiga })
		.done(function(data) {

			$('#form-vedi-rapporto-man-str')[0].reset();

			var dati = JSON.parse(data);

			$('#form-vedi-rapporto-man-str #manStrVis_DescrizioneRisorsa').val(dati['ris_Descrizione']);
			$('#form-vedi-rapporto-man-str #manStrVis_IdEvento').val(dati['ac_IdEvento']);
			$('#form-vedi-rapporto-man-str #manStrVis_DescrizioneEvento').val(dati['ac_DescrizioneEvento']);
			$('#form-vedi-rapporto-man-str #manStrVis_PersonaleRic').val(dati['im_PersonaleRic']);
			$('#form-vedi-rapporto-man-str #manStrVis_PersonaleRis').val(dati['im_PersonaleRis']);

			var dataOraEvento = dati['ac_DataInizio']+"T"+dati['ac_OraInizio'];
			$("#form-vedi-rapporto-man-str #manStrVis_DataEvento").val(dataOraEvento);

			var dataOraInizioPrevisto = dati['im_DataInizioPrevista']+"T"+dati['im_OraInizioPrevista'];
			$("#form-vedi-rapporto-man-str #manStrVis_DataInizioPrevista").val(dataOraInizioPrevisto);

			var dataOraInizioIntervento = dati['im_DataInterventoInizio']+"T"+dati['im_OraInterventoInizio'];
			$("#form-vedi-rapporto-man-str #manStrVis_DataInizioIntervento").val(dataOraInizioIntervento);

			var dataOraFineIntervento = dati['im_DataInterventoFine']+"T"+dati['im_OraInterventoFine'];
			$("#form-vedi-rapporto-man-str #manStrVis_DataFineIntervento").val(dataOraFineIntervento);


			$('textarea#manStrVis_NoteIntervento').val(dati['im_DescrizioneIntervento']);

			$("#modal-vedi-rapporto-man-str").modal("show");
		});


		return false;

	});








	// *** MANUTENZIONI ORDINARIE ***

	// MANUTENZIONI ORDINARIE: VISUALIZZAZIONE POPUP 'NUOVA MANUTENZIONE'
	$('#crea-manutenzione-ordinaria').on('click',function(){

		$('#form-ins-man-ord').find('input#azione').val('default');
		$("#manOrdIns_LineeProduzione").selectpicker('refresh');

		var idLineaProduzione = $('#manOrdIns_LineeProduzione').val();

		//popolo select risorse
		$.post("manutenzione.php", { azione: "mntOrd-carica-select-risorse", idLineaProduzione: idLineaProduzione })
		.done(function(data) {

			$('#form-ins-man-ord #manOrdIns_Risorse').html(data);
			$("#form-ins-man-ord #manOrdIns_Risorse").selectpicker('refresh');

		});

		$('#form-ins-man-ord')[0].reset();
		$('#form-ins-man-ord input, textarea').removeClass('errore');
		$('#modal-ins-man-ordinaria-label').text('PROGRAMMA MANUTENZIONE');

		$("#modal-ins-man-ordinaria").modal("show");
	});


	// MANUTENZIONI ORDINARIE: CANCELLAZIONE SEGNALAZIONE DI CAMPO VUOTO
	$('#form-ins-man-ord input, textarea').on('blur',function(){
		$(this).removeClass('errore');
	})


	//  MANUTENZIONI ORDINARIE: ALLA VARIAZIONE DEI CAMPI 'LINEE PRODUZIONE', 'STATO EVENTO', ESEGUO REFRESH TABELLA 'MANUTENZIONI ORDINARIE'
	$('#mntOrd_FiltroRisorse, #mntOrd_StatoEvento').on('change',function(){

		if ($('#mntOrd_FiltroRisorse').val() === null) {
			g_mntOrd_idRisorsa = "%";
		}
		else {
			g_mntOrd_idRisorsa = $('#mntOrd_FiltroRisorse').val()
		}

		g_mntOrd_statoEvento = $('#mntOrd_StatoEvento').val();

		// memorizzo nelle variabili di sessione i valori recuperati
		sessionStorage.setItem('mntOrd_idRisorsa', g_mntOrd_idRisorsa);
		sessionStorage.setItem('mntOrd_statoEvento', g_mntOrd_statoEvento);

		mostraTabManOrdinarie();
	});



	// MANUTENZIONI ORDINARIE: ALLA SELEZIONE DELLA LINEA, POPOLO LA SELECT RISORSE
	$('#manOrdIns_LineeProduzione').on('change',function(){

		var idLineaProduzione = $('#manOrdIns_LineeProduzione').val();
		$("#manOrdIns_LineeProduzione").selectpicker('refresh');

		//popolo select risorse
		$.post("manutenzione.php", { azione: "mntOrd-carica-select-risorse", idLineaProduzione: idLineaProduzione })
		.done(function(data) {

			$('#form-ins-man-ord #manOrdIns_Risorse').html(data);
			$("#form-ins-man-ord #manOrdIns_Risorse").selectpicker('refresh');

		});
	});



	// MANUTENZIONI ORDINARIE: CANCELLAZIONE MANUTENZIONE SELEZIONATA
	$('body').on('click','a.cancella-man-ord',function(e){

		e.preventDefault();

		var idRiga = $(this).data('id_riga');

		swal({
			title: 'Attenzione',
			text: "Confermi di voler eliminare la manutenzione programmata? L'eliminazione è irreversibile.",
			icon: 'warning',
			showCancelButton: true,
			buttons: {
				cancel: {
					text: "Annulla",
					value: null,
					visible: true,
					className: "btn btn-danger",
					closeModal: true,
				},
				confirm: {
					text: "Sì, elimina",
					value: true,
					visible: true,
					className: "btn btn-primary",
					closeModal: true
				}
			}
		})
		.then((procedi) => {
			if(procedi)
			{
				$.post("manutenzione.php", { azione: "cancella-man-ord", id: idRiga })
				.done(function(data) {

					swal.close();

					mostraTabManOrdinarie();
				});
			}
			else
			{
				swal.close();
			}
		});
		return false;
	});



	// MANUTENZIONI ORDINARIE: VISUALIZZAZIONE POPUP 'MODIFICA MANUTENZIONE'
	$('body').on('click','a.modifica-man-ord',function(e){

		e.preventDefault();

		var idRiga = $(this).data('id_riga');

		$.post("manutenzione.php", { azione: "recupera-man-ord", codice: idRiga })
		.done(function(data) {

			$('#form-mod-man-ord')[0].reset();

			var dati = JSON.parse(data);

			$('#form-mod-man-ord #manOrdMod_Linea').val(dati['lp_Descrizione']);
			$('#form-mod-man-ord #manOrdMod_Risorse').val(dati['ElencoRisorse']);
			$('textarea#manOrdMod_DescrizioneIntervento').val(dati['ac_ManOrd_Descrizione']);
			$('#form-mod-man-ord #manOrdMod_IdRiga').val(idRiga);

			var dataOraInizioIntervento = dati['ac_ManOrd_DataInizioPrevista']+"T"+dati['ac_ManOrd_OraInizioPrevista'];
			$("#form-mod-man-ord #manOrdMod_DataInizioPrevista").val(dataOraInizioIntervento);


			var dataOraFineIntervento = dati['ac_ManOrd_DataFinePrevista']+"T"+dati['ac_ManOrd_OraFinePrevista'];
			$("#form-mod-man-ord #manOrdMod_DataFinePrevista").val(dataOraFineIntervento);


			//recupero valore per checkbox abilitazione misure
			if (dati['ac_ManOrd_BloccoLinea'] == 1) {
				$('#form-mod-man-ord').find('input#manOrdMod_LineaBloccata').prop('checked', true);
			}
			else {
				$('#form-mod-man-ord').find('input#manOrdMod_LineaBloccata').prop('checked', false);
			}

			// aggiungo il campo CodiceCliente come id della modifica
			$('#form-mod-man-ord').find('input#azione').val('modifica');
			$('#modal-mod-man-ord-label').text('MODIFICA MANUTENZIONE PROGRAMMATA');

			$("#modal-mod-man-ord").modal("show");
		});

		return false;

	});



	//  MANUTENZIONI ORDINARIE: SALVATAGGIO DA POPUP 'MODIFICA MANUTENZIONE'
	$('body').on('click','#salva-mod-man-ord',function(e){

		e.preventDefault();

		// inizializzo il contatore errori
		var errori = 0;

		// controllo che i campi obbligatori siano tutti riempiti
		$('#form-mod-man-ord .obbligatorio').each(function(){


			if($(this).val() == "")
			{
				errori++;
				$(this).addClass('errore');
			}
		});

		// se ho anche solo un errore mi fermo qui
		if(errori > 0)
		{
			swal({
				title: "Attenzione",
				text: "Compilare tutti i campi obbligatori contrassegnati con *",
				icon: "warning",
				button: "Ho capito",

				closeModal: true,

			});
		}
		else // nessun errore, posso continuare
		{
			var bloccoLinea;

			//recupero valore della checkbox e lo formato opportunamente
			if ($('#mntOrdMod_LineaBloccata').is(":checked")) {
				bloccoLinea = 1;
			}
			else {
				bloccoLinea = 0;

			}

			// salvo i dati
			$.post("manutenzione.php", { azione: "salva-mod-man-ord", data: $('#form-mod-man-ord').serialize(), bloccoLinea: bloccoLinea})
			.done(function(data) {

				// se è tutto OK
				if(data == "OK")
				{
					$("#modal-mod-man-ord").modal("hide");

					mostraTabManOrdinarie();

				}
				else // restituisco un errore senza chiudere la modale
				{
					swal({
						title: "Operazione non eseguita.",
						text: data,
						icon: "warning",
						button: "Ho capito",

						closeModal: true,

					});
				}

			});
		}

		return false;

	});



	//  MANUTENZIONI ORDINARIE: SALVATAGGIO DA POPUP 'PROGRAMMAZIONE MANUTENZIONE'
	$('body').on('click','#salva-ins-man-ord',function(e){

		e.preventDefault();

		// inizializzo il contatore errori
		var errori = 0;

		// controllo che i campi obbligatori siano tutti riempiti
		$('#form-ins-man-ord .obbligatorio').each(function(){

			if($(this).val() == "")
			{
				errori++;
				$(this).addClass('errore');
			}
		});

		// se ho anche solo un errore mi fermo qui
		if(errori > 0)
		{
			swal({
				title: "Attenzione",
				text: "Compilare tutti i campi obbligatori contrassegnati con *",
				icon: "warning",
				button: "Ho capito",

				closeModal: true,

			});
		}
		else if($('#mntOrd_Risorse').val() == '') {
			swal({
				title: "Attenzione",
				text: "Selezionare le macchine coinvolte.",
				icon: "warning",
				button: "Ho capito",

				closeModal: true,

			});
		}
		else // nessun errore, posso continuare
		{
			var bloccoLinea;

			//recupero valore della checkbox e lo formato opportunamente
			if ($('#manOrdIns_LineaBloccata').is(":checked")) {
				bloccoLinea = 1;
			}
			else {
				bloccoLinea = 0;

			}

			var elencoMacchine = $('#manOrdIns_Risorse').val();

			// salvo i dati
			$.post("manutenzione.php", { azione: "salva-ins-man-ord", data: $('#form-ins-man-ord').serialize(), bloccoLinea: bloccoLinea, elencoMacchine: elencoMacchine})
			.done(function(data) {

				// se è tutto OK
				if(data == "OK")
				{
					$("#modal-ins-man-ordinaria").modal("hide");

					mostraTabManOrdinarie();

				}
				else // restituisco un errore senza chiudere la modale
				{
					swal({
						title: "Operazione non eseguita.",
						text: data,
						icon: "warning",
						button: "Ho capito",

						closeModal: true,

					});
				}

			});
		}

		return false;

	});



	// MANUTENZIONI ORDINARIE: VISUALIZZA POPUP 'RISOLVI'
	$('body').on('click','.risolvi-man-ord',function(e){

		e.preventDefault();

		var idRiga = $(this).data('id_riga');

		$.post("manutenzione.php", { azione: "recupera-man-ord", codice: idRiga })
		.done(function(data) {

			$('#form-risolvi-man-ord')[0].reset();

			var dati = JSON.parse(data);

			$('#form-risolvi-man-ord #manOrdRis_Risorse').val(dati['ElencoRisorse']);
			$('#form-risolvi-man-ord #manOrdRis_IdRisorsa').val(dati['ac_IdRisorsa']);
			$('#form-risolvi-man-ord #manOrdRis_DescrizioneIntervento').val(dati['ac_ManOrd_Descrizione']);
			$('#form-risolvi-man-ord #manOrdRis_IdRiga').val(idRiga);

			var dataOraInizioPrevisto = dati['ac_ManOrd_DataInizioPrevista']+"T"+dati['ac_ManOrd_OraInizioPrevista'];
			$("#form-risolvi-man-ord #manOrdRis_DataInizioPrevista").val(dataOraInizioPrevisto);


			var dataOraFinePrevista = dati['ac_ManOrd_DataFinePrevista']+"T"+dati['ac_ManOrd_OraFinePrevista'];
			$("#form-risolvi-man-ord #manOrdRis_DataFinePrevista").val(dataOraFinePrevista);

			$("#modal-risolvi-man-ordinaria Input, textarea").removeClass('errore');
			$("#modal-risolvi-man-ordinaria").modal("show");
		});

		return false;

	});


	// MANUTENZIONI ORDINARIE: CANCELLAZIONE SEGNALAZIONE DI CAMPO VUOTO
	$('#form-risolvi-man-ord input, textarea').on('blur',function(){
		$(this).removeClass('errore');
	})



	//  MANUTENZIONI ORDINARIE: SALVATAGGIO RAPPORTO DI LAVORO DA POPUP 'RISOLVI'
	$('body').on('click','#salva-rapporto-man-ord',function(e){

		e.preventDefault();

		// inizializzo il contatore errori
		var errori = 0;

		// controllo che i campi obbligatori siano tutti riempiti
		$('#form-risolvi-man-ord .obbligatorio').each(function(){

			if($(this).val() == "")
			{
				errori++;
				$(this).addClass('errore');
			}
		});

		// se ho anche solo un errore mi fermo qui
		if(errori > 0)
		{
			swal({
				title: "Attenzione",
				text: "Compilare tutti i campi obbligatori contrassegnati con *",
				icon: "warning",
				button: "Ho capito",

				closeModal: true,

			});
		}
		else // nessun errore, posso continuare
		{

			// salvo i dati
			$.post("manutenzione.php", { azione: "salva-rapporto-man-ord", data: $('#form-risolvi-man-ord').serialize() })
			.done(function(data) {


				// se è tutto OK
				if(data == "OK")
				{
					$("#modal-risolvi-man-ordinaria").modal("hide");

					mostraTabManOrdinarie();

				}
				else // restituisco un errore senza chiudere la modale
				{
					swal({
						title: "Operazione non eseguita.",
						text: data,
						icon: "warning",
						button: "Ho capito",

						closeModal: true,

					});
				}

			});
		}
		return false;

	});






	// MANUTENZIONI ORDINARIE: VISUALIZZAZIONE RAPPORTO DI LAVORO
	$('body').on('click','.vedi-rapporto-man-ord',function(e){

		e.preventDefault();

		var idRiga = $(this).data('id_riga');

		$.post("manutenzione.php", { azione: "recupera-man-ord", codice: idRiga })
		.done(function(data) {


			$('#form-vedi-rapporto-man-ord')[0].reset();

			var dati = JSON.parse(data);

			$('#form-vedi-rapporto-man-ord #manOrdVis_Risorse').val(dati['ElencoRisorse']);
			$('#form-vedi-rapporto-man-ord #manOrdVis_DescrizioneIntervento').val(dati['ac_ManOrd_Descrizione']);
			$('#form-vedi-rapporto-man-ord #manOrdVis_IdRiga').val(idRiga);
			$('#form-vedi-rapporto-man-ord #manOrdVis_NoteIntervento').val(dati['im_DescrizioneIntervento']);
			$('#form-vedi-rapporto-man-ord #manOrdVis_PersonaleRic').val(dati['im_PersonaleRic']);
			$('#form-vedi-rapporto-man-ord #manOrdVis_PersonaleRis').val(dati['im_PersonaleRis']);

			var dataOraInizioPrevisto = dati['im_DataInizioPrevista']+"T"+dati['im_OraInizioPrevista'];
			$("#form-vedi-rapporto-man-ord #manOrdVis_DataInizioPrevista").val(dataOraInizioPrevisto);

			var dataOraFinePrevista = dati['im_DataFinePrevista']+"T"+dati['im_OraFinePrevista'];
			$("#form-vedi-rapporto-man-ord #manOrdVis_DataFinePrevista").val(dataOraFinePrevista);

			var dataOraInizioIntervento = dati['im_DataInterventoInizio']+"T"+dati['im_OraInterventoInizio'];
			$("#form-vedi-rapporto-man-ord #manOrdVis_DataInizioIntervento").val(dataOraInizioIntervento);

			var dataOraFineIntervento = dati['im_DataInterventoFine']+"T"+dati['im_OraInterventoFine'];
			$("#form-vedi-rapporto-man-ord #manOrdVis_DataFineIntervento").val(dataOraFineIntervento);

			$('textarea#manOrdVis_DescrizioneIntervento').val(dati['im_DescrizioneIntervento']);

			$("#modal-vedi-rapporto-man-ord").modal("show");
		});

		return false;

	});


})(jQuery);