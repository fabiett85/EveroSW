var g_logStatoOrdini_idProduzione;
var g_logScartoOrdini_idProduzioone;
var g_logStatoOrdini_qtaRichiesta;

var g_scr_statoOrdini;
var g_scr_dataInizioPeriodo;
var g_scr_dataFinePeriodo;


// DATA/ORA ODIERNE PER INIZIALIZZAZIONI
var today = new Date();
var dd = today.getDate();
var mm = today.getMonth()+1; //January is 0!
var yyyy = today.getFullYear();
if(dd<10) {
	dd = '0'+dd;
} 
if(mm<10) {
	mm = '0'+mm;
}
var g_scr_dataOdierna = yyyy + "-" + mm + "-" + dd;



// FUNZIONE: VISUALIZZAZIONE ORDINI PER GESTIONE SCARTI
function visualizzaOrdiniGestioneScarti() {

	// invoco il metodo che recupera le righe della distinta base per il prodotto selezionato
	$.post("logistica.php", { azione: "mostra-elenco-ordini-scarti", statoOrdini: g_scr_statoOrdini, dataInizioPeriodo: g_scr_dataInizioPeriodo, dataFinePeriodo: g_scr_dataFinePeriodo })
	.done(function(data) {
		
		// se ho risultati, li mostro nella tabella
		if (data != "NO_ROWS") {
			$('#tabellaOrdini_regScartiLogistica').dataTable().fnClearTable();
			$('#tabellaOrdini_regScartiLogistica').dataTable().fnAddData(JSON.parse(data));
		}
		else {
			$('#tabellaOrdini_regScartiLogistica').dataTable().fnClearTable();
		}
	});
}



// FUNZIONE GESTIONE SCARTI: VISUALIZZAZIONE COMMESSE 
function visualizzaOrdini() {

	// invoco il metodo che recupera le righe della distinta base per il prodotto selezionato
	$.post("logistica.php", { azione: "mostra-elenco-ordini-scarti", statoOrdini: g_scr_statoOrdini, dataInizioPeriodo: g_scr_dataInizioPeriodo, dataFinePeriodo: g_scr_dataFinePeriodo })
	.done(function(data) {
		
		// se ho risultati, li mostro nella tabella
		if (data != "NO_ROWS") {
			$('#tabellaOrdini_regScartiLogistica').dataTable().fnClearTable();
			$('#tabellaOrdini_regScartiLogistica').dataTable().fnAddData(JSON.parse(data));
		}
		else {
			$('#tabellaOrdini_regScartiLogistica').dataTable().fnClearTable();
		}
	});
}



(function($) {
	
	$(".selectpicker").selectpicker();
	
	
	var tabellaOrdini;	
	var tabellaComponenti;
	
	var tabellaOrdiniRegScarti;	
	var tabellaComponentiRegScarti;

	
	var linguaItaliana = {
		"processing": "Caricamento...",
		"search": "Ricerca: ",
		"lengthMenu": "_MENU_ righe per pagina",
		"zeroRecords": "Nessun ordine disponibile.",
		"info": "Pagina _PAGE_ di _PAGES_",
		"infoEmpty": "Nessun dato disponibile",
		"infoFiltered": "(filtrate da _MAX_ righe totali)",
		"paginate": {
		    "first":      "Prima",
		    "last":       "Ultima",
		    "next":       "Prossima",
		    "previous":   "Precedente"
		}
	}
	
	var linguaItaliana2 = {
		"processing": "Caricamento...",
		"search": "Ricerca: ",
		"lengthMenu": "_MENU_ righe per pagina",
		"zeroRecords": "Nessun componente per l'ordine selezionato.",
		"info": "Pagina _PAGE_ di _PAGES_",
		"infoEmpty": "Nessun ordine selezionato",
		"infoFiltered": "(filtrate da _MAX_ righe totali)",
		"paginate": {
		    "first":      "Prima",
		    "last":       "Ultima",
		    "next":       "Prossima",
		    "previous":   "Precedente"
		}
	}	
	
	// Datatable elenco ordini di lavoro
	$(function() {
		
		$.fn.dataTable.moment("DD/MM/YYYY - HH:mm");
		
		// STATO COMMESSE: DATATABLE COMMESSE
		tabellaOrdiniStatoOrdini = $('#tabellaOrdini_statoOrdiniLogistica').DataTable({		
			"order": [[4, "desc" ]],
			"aLengthMenu": [
				[5, 10, 15, 25, 50, -1],
				[5, 10, 15, 25, 50, "Tutti"]
			],
			"iDisplayLength": 10,
			
			"order": [ 4, "desc" ],		
			
			"ajax": {
				"url": $('#tabellaOrdini_statoOrdiniLogistica').attr('data-source'),
				"dataSrc": ""
			},			
			"columns": [
			    { "data": "DescrizioneLinea" },			
			    { "data": "IdProduzione" },				
			    { "data": "DescrizioneProdotto" },
			    { "data": "QtaRichiesta" },
				{ "data": "DataInizio" },			
				{ "data": "Stato" },
				{ "data": "IdProduzioneAux" }
			],
			"columnDefs": [		
				{
					"targets": [ 6 ],
					"visible": false,
				}
			],
			"language": linguaItaliana,
			"info":     false,
			rowId: 'IdProduzione',
			select: true,
		    "drawCallback": function( settings ) {
				
			
				var api = this.api();
				api.rows().every( function ( rowIdx, tableLoop, rowLoop ) {
				
					var idRiga = this.node();					
					var data = this.data();
					var statoLinea = this.data()['Stato'];

					if (statoLinea == "OK") {
						$(idRiga).css('background-color',  'rgba(0, 196, 0, 0.8)');
					}
					else if (statoLinea == "CHIUSO") {
						$(idRiga).css('background-color',  'rgba(101, 108, 108, 0.2)');
					}
					else if (statoLinea == "ATTIVO") {
						$(idRiga).css('background-color',  'rgba(102, 204, 255, 0.8)');
					}
					else if (statoLinea == "CARICATO") {
						$(idRiga).css('background-color',  'rgba(102, 204, 255, 0.8)');
					}					
					else if (statoLinea == "MANUTENZIONE") {
						$(idRiga).css('background-color',  'rgba(102, 0, 204, 0.7)'); 
					}					
							

				});				
			}	
			
		});

	
		// STATO COMMESSE: DATATABLE COMPONENTI
		tabellaComponentiStatoOrdini = $('#tabellaComponenti_statoOrdiniLogistica').DataTable({
			"aLengthMenu": [
				[5, 10, 15, 25, 50, -1],
				[5, 10, 15, 25, 50, "Tutti"]
			],
			
			"iDisplayLength": 15,

			"order": [ 1, "asc" ],	
			
			"columns": [
			    { "data": "IdComponente" },				
			    { "data": "DescrizioneComponente" },
			    { "data": "QtaScarti" }
			
			],
			"language": linguaItaliana2,	
			"info":     	false			
		});				
		
		
		
		// GESTIONE SCARTI: DATATABLE COMMESSE
		tabellaOrdiniRegScarti = $('#tabellaOrdini_regScartiLogistica').DataTable({		
			"aLengthMenu": [
				[5, 10, 15, 25, 50, -1],
				[5, 10, 15, 25, 50, "Tutti"]
			],
			"iDisplayLength": 5,
			
			"order": [[ 5 , "desc" ]],		
					
			"columns": [
			    { "data": "DescrizioneLinea" },				
			    { "data": "IdProduzione" },				
			    { "data": "DescrizioneProdotto" },
			    { "data": "QtaProdotta" },
				{ "data": "QtaScarti" },
				{ "data": "DataInizio" },			
				{ "data": "Stato" },
				{ "data": "Azioni" },
				{ "data": "IdProduzioneAux" }				
			],
			"columnDefs": [		
				{
					"targets": [ 8 ],
					"visible": false,
				},
				{
					"targets": [ 7 ],
					"className": 'center-bolded',
				},	
				{ 	
					"width": "5%", 
					"targets": [ 7 ], 
				}					
			],				
			"language": linguaItaliana,
			"info":     false,
			select: true,
		    "drawCallback": function( settings ) {
				var api = this.api();
				api.rows().every( function ( rowIdx, tableLoop, rowLoop ) {
				
					var idRiga = this.node();
					var data = this.data();
					var statoLinea = this.data()['Stato'];

					if (statoLinea == "OK") {
						$(idRiga).css('background-color',  'rgba(0, 196, 0, 0.8)');
					}
					else if (statoLinea == "CHIUSO") {;
						$(idRiga).css('background-color',  'rgba(101, 108, 108, 0.2)');
					}

				});				
			}	
		});

	
		// GESTIONE SCARTI: DATATABLE COMPONENTI
		tabellaComponentiRegScarti = $('#tabellaComponenti_regScartiLogistica').DataTable({
			"order": [[ 1, "asc" ]],
			"aLengthMenu": [
				[5, 10, 15, 25, 50, -1],
				[5, 10, 15, 25, 50, "Tutti"]
			],
			"columnDefs": [		
				{
					"targets": [ 4 ],
					"visible": false,
				}				
			],
			
			"iDisplayLength": 15,		
			
			"columns": [
			    { "data": "IdComponente" },				
			    { "data": "DescrizioneComponente" },
			    { "data": "QtaScarti" },
				{ "data": "UnitaDiMisura" }, 				
			    { "data": "TipoComponente" },				
			    { "data": "Azioni" }				
			],
			"columnDefs": [		
				{
					"targets": [ 5 ],
					"className": 'center-bolded',
				},	
				{ 	
					"width": "5%", 
					"targets": [ 5 ], 
				}					
			],				
			"language": linguaItaliana2,	
			"info":     	false			
		});
		
		
		
	
	
	
	
		// PER POST REFRESH: MEMORIZZO TAB ATTUALMENTE MOSTRATO PER RIPRISTINO VISUALIZZAZIONE DELLO STESSO IN CASO DI REFRESH
		$('#tab-logistica a[data-toggle="tab"]').on('show.bs.tab', function(e) {
			sessionStorage.setItem('activeTab_logistica', $(e.target).attr('href'));

			var tabSelezionato = $(e.target).attr('href');

			if (tabSelezionato == '#logistica-stato-ordini') {
				tabellaOrdiniStatoOrdini.ajax.reload(null, false);
				
				$('.card-title').html('LOGISTICA - MONITORAGGIO COMMESSE');
			}
			else if (tabSelezionato == '#logistica-gestione-scarti') {
				
				$('.card-title').html('LOGISTICA - GESTIONE SCARTI');
				
				// impostazione variabile STATO COMMESSE
				if (sessionStorage.getItem('scr_statoOrdini') === null) {
					g_scr_statoOrdini = "%";
				}	
				else {
					g_scr_statoOrdini = sessionStorage.getItem('scr_statoOrdini');
				}  
				$('#scr_StatoOrdini').val(g_scr_statoOrdini);
				$("#scr_StatoOrdini").selectpicker('refresh');
				
				
				// impostazione variabile DATA INIZIO TRACCIATE
				if (sessionStorage.getItem('scr_dataInizioPeriodo') === null) {
					g_scr_dataInizioPeriodo = g_scr_dataOdierna;
				}
				else {
					g_scr_dataInizioPeriodo = sessionStorage.getItem('scr_dataInizioPeriodo');
				}
				$('#scr_DataInizio').val(g_scr_dataInizioPeriodo);
				
				
				
				// impostazione variabile DATA FINE TRACCIATE
				if (sessionStorage.getItem('scr_dataFinePeriodo') === null) {
					g_scr_dataFinePeriodo = g_scr_dataOdierna;
				}	
				else {
					g_scr_dataFinePeriodo = sessionStorage.getItem('scr_dataFinePeriodo');
				}
				$('#scr_DataFine').val(g_scr_dataFinePeriodo);			


				visualizzaOrdini();					
				
			}
			
		});
		var activeTab_logistica = sessionStorage.getItem('activeTab_logistica');
		if(activeTab_logistica){
			$('#tab-logistica a[href="' + activeTab_logistica + '"]').tab('show');
		}
		else {
			$('#tab-logistica a[href="#logistica-stato-ordini"]').tab('show');
		}			
	
	});
	
	
	
			
	// STATO COMMESSE: RECUPERA ELENCO COMPONENTI PER L'COMMESSA SELEZIONATO (al clic su una riga dell'elenco lavori)
	$('#tabellaOrdini_statoOrdiniLogistica tbody').on( 'click', 'tr', function () {
		

		var datiRiga = $('#tabellaOrdini_statoOrdiniLogistica').DataTable().row(this).data();
		var idProduzione = datiRiga['IdProduzioneAux'];
		g_logStatoOrdini_qtaRichiesta = datiRiga['QtaRichiesta'];
		g_logStatoOrdini_idProduzione = datiRiga['IdProduzioneAux'];
		
		// invoco il metodo che recupera le righe della distinta base per il prodotto selezionato
		$.post("logistica.php", { azione: "mostra-componenti-richiesti", idProduzione: idProduzione, qtaRichiesta: g_logStatoOrdini_qtaRichiesta })
		.done(function(data) {

			// se ho risultati, li mostro nella tabella
			if (data != "NO_ROWS") {
				$('#tabellaComponenti_statoOrdiniLogistica').dataTable().fnClearTable();
				$('#tabellaComponenti_statoOrdiniLogistica').dataTable().fnAddData(JSON.parse(data));
				$('#logistica-stampa-bolla-prelievo').attr('disabled', false);
			}
			else {
				$('#tabellaComponenti_statoOrdiniLogistica').dataTable().fnClearTable();			
			}
		});
	});
	
	
	
	// STATO COMMESSE: VISUALIZZA POPUP 'MODIFICA QTA ORDINE' PER ORDINE SELEZIONATO
	$('body').on('click','.modifica-quantita-ordine',function(e){
		
		e.preventDefault();
		
		var idProduzione = $(this).data('id_riga');
		
		$.post("logistica.php", { azione: "recupera-quantita-ordine", codice: idProduzione })
		.done(function(data) {

			$('#form-rettifica-qta-ordine')[0].reset();
			
			var dati = JSON.parse(data);
			
			
			// Visualizza nel popup i dati recuperati
			for(var chiave in dati)
			{
				if(dati.hasOwnProperty(chiave))
				{
					$('#form-rettifica-qta-ordine').find('input#' + chiave).val(dati[chiave]);
				}
			}
		
			$('#form-rettifica-qta-ordine .udm-popup-qta').val(" [" + dati['um_Sigla'] + "]");
			$("#modal-rettifica-qta-ordine").modal("show");
		});
		
		return false;
		
	});	
	
	
	// STATO COMMESSE: SALVATAGGIO DA POPUP 'MODIFICA/INSERIMENTO SCARTI'
	$('body').on('click','#salva-qta-ordine',function(e){
		
		e.preventDefault();
				
		swal({
			title: 'ATTENZIONE!',
			text: "La modifica in essere determinerà il ricacolo dell'indice OEE e dei relativi fattori: confermi le modifiche alla commessa in oggetto? ",
			icon: 'warning',
			showCancelButton: true,
			buttons: {
				cancel: {
					text: "ANNULLA",
					value: null,
					visible: true,
					className: "btn btn-primary",
					closeModal: true,
				},
				confirm: {
					text: "CONFERMA",
					value: true,
					visible: true,
					className: "btn btn-success",
					closeModal: true
				}
			}
		})
		
		// Se confermo di procedere
		.then((procedi) => {
			if(procedi) 
			{
				var errori = 0;
				
				// Controllo che i campi obbligatori siano tutti riempiti
				$('#form-rettifica-qta-ordine .obbligatorio').each(function(){

					if($(this).val() == "")
					{
						errori++;
						$(this).addClass('errore');
					}
				});	
				
				// Se ho anche solo un errore mi fermo qui
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
					// Salvo i dati
					$.post("logistica.php", { azione: "salva-qta-ordine", data: $('#form-rettifica-qta-ordine').serialize() })
					.done(function(data) {
						

						// se è tutto OK
						if(data == "OK")
						{
							$("#modal-rettifica-qta-ordine").modal("hide");
							
							visualizzaOrdiniGestioneScarti()
							
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
			}
			else {
				swal.close();
			}
		});
		return false;
	});			
	
	
	
	
	
	
	
	
	
	// GESTIONE SCARTI: ALLA VARIAZIONE DELLE SELECT 'STATO COMMESSE' E 'PERIODO DI RIFERIMENTO', AGGIORNO I DATI IN TABELLA COMMESSE
	$('#scr_StatoOrdini, #scr_DataInizio, #scr_DataFine').on('change',function(){
		  		  
		g_scr_statoOrdini = $('#scr_StatoOrdini').val(); 
		g_scr_dataInizioPeriodo = $('#scr_DataInizio').val(); 
		g_scr_dataFinePeriodo = $('#scr_DataFine').val(); 
		
		// memorizzo nelle variabili di sessione i valori recuperati
		sessionStorage.setItem('scr_statoOrdini', g_scr_statoOrdini);					
		sessionStorage.setItem('scr_dataInizioPeriodo', g_scr_dataInizioPeriodo);
		sessionStorage.setItem('scr_dataFinePeriodo', g_scr_dataFinePeriodo);

		visualizzaOrdini();
	});
	

	
	// GESTIONE SCARTI: RECUPERA ELENCO COMPONENTI PER L'COMMESSA SELEZIONATO (al clic su una riga dell'elenco ordini)
	$('#tabellaOrdini_regScartiLogistica tbody').on( 'click', 'tr', function () {
		

		var datiRiga = $('#tabellaOrdini_regScartiLogistica').DataTable().row(this).data();
		var idProduzione = datiRiga['IdProduzioneAux'];
		g_logScartoOrdini_idProduzioone = datiRiga['IdProduzioneAux'];
		
		// invoco il metodo che recupera le righe della distinta base per il prodotto selezionato
		$.post("logistica.php", { azione: "mostra-componenti-scarti", idProduzione: idProduzione })
		.done(function(data) {

			// se ho risultati, li mostro nella tabella
			if (data != "NO_ROWS") {
				$('#tabellaComponenti_regScartiLogistica').dataTable().fnClearTable();
				$('#tabellaComponenti_regScartiLogistica').dataTable().fnAddData(JSON.parse(data));
				$('#logistica-stampa-report-scarti').attr('disabled', false);
			}
			else {
				$('#tabellaComponenti_regScartiLogistica').dataTable().fnClearTable();
			}
			
			
		});
	});
	


	// GESTIONE SCARTI: VISUALIZZA POPUP 'MODIFICA SCARTI'
	$('body').on('click','.modifica-scarti-componente',function(e){
		
		e.preventDefault();
		
		var idRiga = $(this).data('id_riga');
		
		$.post("logistica.php", { azione: "recupera-scarti-componenti", codice: idRiga })
		.done(function(data) {

			$('#form-scarti-componenti')[0].reset();
			
			var dati = JSON.parse(data);
			
			
			// visualizza nel popup i dati recuperati
			for(var chiave in dati)
			{
				if(dati.hasOwnProperty(chiave))
				{
					$('#form-scarti-componenti').find('input#' + chiave).val(dati[chiave]);
					$('#form-scarti-componenti select#' + chiave).val(dati[chiave]);
					$('#form-scarti-componenti select#' + chiave).selectpicker('refresh');					
				}
			}
		
			$("#modal-modifica-scarti").modal("show");
		});
		
		return false;
		
	});
	
	
	
	// GESTIONE SCARTI: SALVATAGGIO DA POPUP 'MODIFICA SCARTI' 
	$('body').on('click','#salva-scarti-componente',function(e){
		
		e.preventDefault();
		
		// inizializzo il contatore errori
		var errori = 0;
		
		var idProduzioneModificaScarti = $('#scr_IdProduzione').val();

		// controllo che i campi obbligatori siano tutti riempiti
		$('#form-scarti-componenti .obbligatorio').each(function(){

			
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
			$.post("logistica.php", { azione: "salva-scarti-componente", data: $('#form-scarti-componenti').serialize() })
			.done(function(data) {

				// se è tutto OK
				if(data == "OK")
				{
					$("#modal-modifica-scarti").modal("hide");
					
					// invoco il metodo che recupera le righe della distinta base per il prodotto selezionato
					$.post("logistica.php", { azione: "mostra-componenti-scarti", idProduzione: idProduzioneModificaScarti })
					.done(function(data) {

						$('#tabellaComponenti_regScartiLogistica').dataTable().fnClearTable();
						
						// se ho risultati, li mostro nella tabella
						if (data != "NO_ROWS") {
							$('#tabellaComponenti_regScartiLogistica').dataTable().fnAddData(JSON.parse(data));
						}
					});
					
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
	
	
	
	
	
	
	
	
	
	
	
	// STATO COMMESSE: STAMPA BOLLA DI PRELIEVO
	$('#logistica-stampa-bolla-prelievo').on('click',function(){

		// Ricavo la data attuale per generazione nome report
		var today = new Date();
		var dd = today.getDate();
		var mm = today.getMonth()+1; //January is 0!
		var yyyy = today.getFullYear();
		if(dd<10) {
			dd = '0'+dd;
		} 
		if(mm<10) {
			mm = '0'+mm;
		}
		
		
		var dataReport = yyyy +""+ mm + "" +dd;
		var dataReportIntestazione = dd + "/" + mm + "/" + yyyy;
		var idProduzioneSelezionato = g_logStatoOrdini_idProduzione;
		var dati;
			
		var valoriRiga = [];
		var valoriTabella = [];
		
		
		// Creo il documento e ricavo le dimensioni
		var doc = new jspdf.jsPDF({orientation: "landscape"})
		const pageWidth = doc.internal.pageSize.width;
		const pageHeight = doc.internal.pageSize.height;		
		
		// Definisco il nome del report
		var nomeFilePDF = "BollaPrelievo"+ dataReport +"_Ord" + idProduzioneSelezionato;		
			
		// Scrittura intestazione report
		doc.setFontSize(10);
		doc.text(pageWidth - 60, 10, "Data documento: " + dataReportIntestazione);	
		doc.text(10, 10, "BOLLA DI PRELIEVO");


		// Recupero i dati degli ordini nel periodo
		// DETTAGLIO COMMESSA SELEZIONATO: visualizzazione distinta risorse per l'ordine di produzione selezionato
		$.post("logistica.php", { azione: "recupera-dettaglio-ordine", idProduzione: idProduzioneSelezionato })
		.done(function(dataAjax) {
			
			dati = JSON.parse(dataAjax);

			// Aggiungo ogni dato dell'array associativo all'array dei risultati
			$.each(dati, function (key, value) {
				valoriRiga.push(value);					
			});
			
			// Terminato il controllo del primo array associativo, aggiungo l'array ottenuto al nuovo array di array dei risultati
			valoriTabella.push(valoriRiga);
			
			// Aggiungo al documento la tabella con la lista ordini nel periodo
			doc.autoTable({
				head: [[ {content: 'TESTATA COMMESSA', colSpan: 7, styles: {halign: 'left', fillColor: [22, 160, 133]}}], ['Linea', 'Codice commessa', 'Prodotto', 'Qta richiesta', 'Data program.', 'Ora program.', 'Lotto', 'Stato']],
				body: valoriTabella,
				theme: 'grid',
				margin: {horizontal: 10},
				styles: {fontSize: 7},
				rowPageBreak: 'avoid',			
				startY: 15
			})	
			
			// Azzero array
			valoriRiga = [];
			valoriTabella = [];		
		
			
			// DISTINTA COMPONENTI: visualizzazione distinta componenti per l'ordine di produzione selezionato
			$.post("logistica.php", { azione: "mostra-componenti-richiesti", idProduzione: idProduzioneSelezionato, qtaRichiesta: g_logStatoOrdini_qtaRichiesta })
			.done(function(data) {

				if (data != "NO_ROWS") {
					dati = JSON.parse(data);
		
					// Conversione: array di array associativi --> array di array
					// Scorro il file JSON dei dati ottenuti (Array di array associativi) iterando sui vari array associativi
					for (var i=0; i < dati.length; i++) {
						
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
					head: [[ {content: 'COMPONENTI RICHIESTI', colSpan: 3, styles: {halign: 'left', fillColor: [22, 160, 133]}}], ['Codice', 'Descrizione', 'Fabbisogno']],
					body: valoriTabella,
					theme: 'grid',
					margin: {horizontal: 10},
					styles: {fontSize: 7},
					rowPageBreak: 'avoid'
				})
				
				
				// Stampa numerazione pagine
				const pages = doc.internal.getNumberOfPages();
				const pageWidth = doc.internal.pageSize.width;
				const pageHeight = doc.internal.pageSize.height;
				for (let j = 1; j < pages + 1 ; j++) {
					let horizontalPos = pageWidth / 2;
					let verticalPos = pageHeight - 5;
					doc.setPage(j);
					doc.text(`Pag. ${j} di ${pages}`, horizontalPos, verticalPos, {align: 'center' });
				}

				// Esporto e salvo il documento creato
				doc.save(nomeFilePDF+'.pdf')			
				
			});
	
		});	
		
	});
	
	
	// GESTIONE SCARTI: STAMPA REPORT SCARTI
	$('#logistica-stampa-report-scarti').on('click',function(){
		
		// Ricavo la data attuale per generazione nome report
		var today = new Date();
		var dd = today.getDate();
		var mm = today.getMonth()+1; //January is 0!
		var yyyy = today.getFullYear();
		if(dd<10) {
			dd = '0'+dd;
		} 
		if(mm<10) {
			mm = '0'+mm;
		}
		
		
		var dataReport = yyyy +""+ mm + "" +dd;
		var dataReportIntestazione = dd + "/" + mm + "/" + yyyy;
		var idProduzioneSelezionato = g_logScartoOrdini_idProduzioone;
		var dati;
			
		var valoriRiga = [];
		var valoriTabella = [];
		
		
		// Creo il documento e ricavo le dimensioni
		var doc = new jspdf.jsPDF({orientation: "landscape"})
		const pageWidth = doc.internal.pageSize.width;
		const pageHeight = doc.internal.pageSize.height;		
		
		// Definisco il nome del report
		var nomeFilePDF = "RptScarti"+ dataReport +"_Ord" + idProduzioneSelezionato;		
			
		// Scrittura intestazione report
		doc.setFontSize(10);
		doc.text(pageWidth - 60, 10, "Data documento: " + dataReportIntestazione);	
		doc.text(10, 10, "REPORT SCARTI COMMESSA");


		// Recupero i dati degli ordini nel periodo
		// DETTAGLIO COMMESSA SELEZIONATO: visualizzazione distinta risorse per l'ordine di produzione selezionato
		$.post("logistica.php", { azione: "recupera-dettaglio-ordine", idProduzione: idProduzioneSelezionato })
		.done(function(dataAjax) {
			
			dati = JSON.parse(dataAjax);

			// Aggiungo ogni dato dell'array associativo all'array dei risultati
			$.each(dati, function (key, value) {
				valoriRiga.push(value);					
			});
			
			// Terminato il controllo del primo array associativo, aggiungo l'array ottenuto al nuovo array di array dei risultati
			valoriTabella.push(valoriRiga);
			
			// Aggiungo al documento la tabella con la lista ordini nel periodo
			doc.autoTable({
				head: [[ {content: 'TESTATA COMMESSA', colSpan: 7, styles: {halign: 'left', fillColor: [22, 160, 133]}}], ['Linea', 'Codice commessa', 'Prodotto', 'Qta richiesta', 'Data program.', 'Ora program.', 'Lotto', 'Stato']],
				body: valoriTabella,
				theme: 'grid',
				margin: {horizontal: 10},
				styles: {fontSize: 7},
				rowPageBreak: 'avoid',			
				startY: 15
			})	
			
			// Azzero array
			valoriRiga = [];
			valoriTabella = [];		
		
			
			// DISTINTA COMPONENTI: visualizzazione distinta componenti per l'ordine di produzione selezionato
			$.post("logistica.php", { azione: "mostra-componenti-scarti", idProduzione: idProduzioneSelezionato })
			.done(function(data) {

				if (data != "NO_ROWS") {
					dati = JSON.parse(data);
		
					// Conversione: array di array associativi --> array di array
					// Scorro il file JSON dei dati ottenuti (Array di array associativi) iterando sui vari array associativi
					for (var i=0; i < dati.length; i++) {
						
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
					head: [[ {content: 'COMPONENTI UTILIZZATI', colSpan: 3, styles: {halign: 'left', fillColor: [22, 160, 133]}}], ['Codice', 'Descrizione', 'Qta scartata']],
					body: valoriTabella,
					theme: 'grid',
					margin: {horizontal: 10},
					styles: {fontSize: 7},					
					rowPageBreak: 'avoid'
				})
				
				
				// Stampa numerazione pagine
				const pages = doc.internal.getNumberOfPages();
				const pageWidth = doc.internal.pageSize.width;
				const pageHeight = doc.internal.pageSize.height;
				for (let j = 1; j < pages + 1 ; j++) {
					let horizontalPos = pageWidth / 2;
					let verticalPos = pageHeight - 5;
					doc.setPage(j);
					doc.text(`Pag. ${j} di ${pages}`, horizontalPos, verticalPos, {align: 'center' });
				}

				// Esporto e salvo il documento creato
				doc.save(nomeFilePDF+'.pdf')			
				
			});
	
		});	
		
	});	
	

		
	
})(jQuery);