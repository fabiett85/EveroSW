var pianoOrario = [];
				  
var coloriLinee = [];
coloriLinee['pt_Linea1'] = 'rgba(0, 102, 255, 0.4)';
coloriLinee['pt_Linea2'] = 'rgba(0, 204, 0, 0.4)';
coloriLinee['pt_Linea3'] = 'rgba(255, 255, 0, 0.5)';
coloriLinee['pt_Linea4'] = 'rgba(255, 102, 0, 0.4)';
coloriLinee['pt_Linea5'] = 'rgba(255, 204, 0, 0.4)';

var nomiLinee = [];


var calendarEl = document.getElementById('calendar');
var calendar;

var disabilita;


// FUNZIONE: RICERCA LA PRIMA DATA DISPONIBILE NEL CALENDARIO (LA PRIMA PER CUI NON SONO STATI PROGRAMMATI ORARI)
function cercaPrimaDataDisponibile() {
	$.post("gestionepianoorario.php", { azione: "ricerca-data-disponibile"})
	.done(function(data) {

		$('#gt_DataInizio').val(data);
		$('#gt_DataFine').val(data);
	});

}


// FUNZIONE: ISTANZIA IL CALENDARIO	
function create_calendar() {
					
	calendar = new FullCalendar.Calendar(calendarEl, {
		fixedWeekCount: false,
		firstDay: 1,
		height: "auto",
		initialView: 'dayGridMonth',
		events: pianoOrario,
		dateClick: function(info) {
		},
		eventClick: function(info) {
			
			var dataCliccata = info.event.start;
			var mydate = new Date(dataCliccata);
			let formatted_date = mydate.getFullYear() + "-" + (mydate.getMonth() + 1) + "-" + mydate.getDate();
			var dataGiornoFormattata = mydate.getDate().toString().padStart(2, "0") + '/' + (mydate.getMonth() + 1).toString().padStart(2, "0") + '/' + mydate.getFullYear().toString();
			
			$.post("gestionepianoorario.php", { azione: "recupera-giorno", giorno: formatted_date })
			.done(function(data) {

				var dati = JSON.parse(data);
				
				$('#form-modifica-calendario').find('input#pt_GiornoSelezionato').val(dataGiornoFormattata);
				
				// recupero valori per input-text
				for(var chiave in dati)
				{
					if(dati.hasOwnProperty(chiave))
					{
						$('#form-modifica-calendario').find('input#' + chiave).val(parseInt(dati[chiave]));

					}
				}
			});
				
			// visualizzo popup
			$("#modal-modifica-calendario").modal("show");
			
			return false;

		
		}	
	});
	
	calendar.setOption('locale', 'it');
	calendar.render();
}


// FUNZIONE: CANCELLA IL CALENDARIO
function destroy_calendar() {
	calendar.destroy();
	delete calendar;
}


// FUNZIONE: PREDISPONE I DATI DA VISUALIZZARE SUL CALENDARIO E LO ISTANZIA
function caricaDatiCalendario() {
	
	var numeroLinee;
	
	// Recupero le linee definite nel sistema
	$.ajaxSetup({async: false});  
	$.post("gestionepianoorario.php", { azione: "recupera-linee" })
	.done(function(dataAjaxLinee) {
		
		// Se sono definite linee, procedo a inizializzare l'array e le variabili ausiliarie relative
		if (dataAjaxLinee != "NO_ROWS") {
			
			var datiLinee = JSON.parse(dataAjaxLinee);
			var indiceLineaDB;
			numeroLinee = datiLinee.length;
			
			for(var i = 0; i < numeroLinee; i++) {
				
				indiceLineaDB = i + 1;
				nomiLinee['pt_Linea' + indiceLineaDB] = datiLinee[i].DescrizioneLinea;
				
			}
		}
		else {
			numeroLinee = 0;
		}
	});
	
	// Recupero le date e gli orari definiti, recuperandoli linea per linea
	$.ajaxSetup({async: false});  
	$.post("gestionepianoorario.php", { azione: "recupera-piano-orario"})
	.done(function(dataAjaxOrari) {

		if (dataAjaxOrari != "NO_ROWS") {
			
			pianoOrario = [];
			
			var datiOrari = JSON.parse(dataAjaxOrari);
			var infoOrario;
			var indiceLineaVisualizzazione;
			
			for(var i = 0; i < datiOrari.length; i++) {
				var  rigaData = datiOrari[i];

				for (var j = 0; j <= numeroLinee; j++) {
					
					indiceLineaVisualizzazione = j + 1;
					
					if (rigaData['OreLinea'+j] != null) {
						infoOrario = {title: nomiLinee['pt_Linea' + j] + ': ' + rigaData['OreLinea'+j] + ' ORE', start: rigaData['DataOrdinamento'], color: coloriLinee['pt_Linea' + j], textColor: "#404040"};
						pianoOrario.push(infoOrario);	
					}
				}
			}
			
		}
		
	});	
	
	create_calendar();

}




(function($) {

	'use strict';
	
	
	$(function() {
		
		//VALORE DEFAULT SELECTPICKER
		$(".selectpicker").selectpicker({
			noneSelectedText : 'Seleziona...'
		});
		
		// PREDISPONGO I DATI DA VISUALIZZARE E ISTANZIO IL CALENDARIO
		caricaDatiCalendario();
		
	});	
	
	
	// PIANO TURNI: CANCELLAZIONE SEGNALAZIONE DI CAMPO VUOTO
	$('#form-piano-turni input').on('blur',function(){
		$(this).removeClass('errore');
	})		


	//  SULLA PERDITA DEL FOCUS DEI CAMPI DATA, AGGIORNO I DATI VISUALIZZATI DAL GRAFICO
	$('#gt_DataInizio, #gt_DataFine').on('blur',function(){

		var dataInizio = $('#gt_DataInizio').val()
		var dataFine = $('#gt_DataFine').val()
		if (dataFine<dataInizio){

			swal({
				title: "ATTENZIONE!",
				text: "La data di inizio periodo è oltre la data di fine: range resettato dal sistema",
				icon: "warning",
				button: "Ho capito",

				closeModal: true,

			});
			
			 $('#gt_DataInizio').val(dataFine)
		}
	});
	
	


	// PIANO TURNI - INIZIALIZZAZIONE: VISUALIZZAZIONE POPUP DI INIZIALIZZAZIONE CALENDARIO
	$('#inizializza-orari-lavoro').on('click',function(){

		$('#form-piano-turni input').removeClass('errore');
		
		$('#form-piano-turni')[0].reset();
		$('#form-piano-turni .selectpicker').val('default');
		$('#form-piano-turni .selectpicker').selectpicker('refresh');

		
		// RICERCA LA PRIMA DATA DISPONIBILE
		cercaPrimaDataDisponibile();
		
		$("#modal-inizializza-orari").modal("show");
	});	
		



	
	// PIANO TURNI - INIZIALIZZAZIONE: CONFERMA INIZIALIZZAZIONE PIANO (CLIC SU PULSANTE SALVATAGGIO INIZIALIZZAZIONE)
	$('#conferma-piano-turni').on('click',function(e){
		
		e.preventDefault();
		
		// inizializzo il contatore errori
		var errori = 0;
		
		// Controllo riempimento campi obbligatori (INPUT)
		$('#form-piano-turni .obbligatorio').each(function(){
			if($(this).val() == "")
			{
				errori++;
				$(this).addClass('errore');
			}
		});

		// Controllo riempimento campi obbligatori (SELECT)
		$('#form-piano-turni .selectpicker').each(function(){
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

			var elencoLineeSelezionate = $('#gt_LineeProduzione').val();

			$.post("gestionepianoorario.php", { azione: "verifica-piano-esistente", data: $('#form-piano-turni').serialize(), elencoLineeSelezionate: elencoLineeSelezionate})
			.done(function(data) {

				if (data == "VERIFICA_OK") {
							
					$.post("gestionepianoorario.php", { azione: "inizializza-piano-turni", data: $('#form-piano-turni').serialize(), elencoLineeSelezionate: elencoLineeSelezionate})
					.done(function(data) {

						if (data == "OK") {
							caricaDatiCalendario();
							$("#modal-inizializza-orari").modal("hide");
							
							swal("Calendario inizializzato correttamente!");							
						}
						else {
							swal({
								title: "ATTENZIONE!",
								text: data,
								icon: "warning",
								button: "Ho capito",
								
								closeModal: true,
								
							});							
						}	
						
					});
					
					
				}
				else if(data == "VERIFICA_KO") {
					
					swal({
						title: 'Attenzione',
						text: "Nel periodo considerato esistono già orari definiti per una o più linee: PROCEDI COMUNQUE SOVRASCRIVENDO I VALORI PRECEDENTI?",
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
								text: "Sì, sovrascrivi",
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
														
							$.post("gestionepianoorario.php", { azione: "inizializza-piano-turni", data: $('#form-piano-turni').serialize(), elencoLineeSelezionate: elencoLineeSelezionate})
							.done(function(data) {

								if (data == "OK") {
									caricaDatiCalendario();
									$("#modal-inizializza-orari").modal("hide");										
								}
								else {
									swal({
										title: "Errore INIZIALIZZAZIONE.",
										text: data,
										icon: "warning",
										button: "Ho capito",
										
										closeModal: true,
										
									});		
								}	
							});								

						}
						else
						{
							swal.close();
						}
					});
											
					
				}	
				
			});
		}
		return false;
		
    });	



	// PIANO TURNI - MODIFICA: SALVATAGGIO DA POPUP DI MODIFICA GIORNO DEL CALENDARIO
	$('body').on('click','#salva-calendario',function(e){
		
		e.preventDefault();
		
		// inizializzo il contatore errori
		var errori = 0;
		
		// Controllo riempimento campi obbligatori (INPUT)
		$('#form-modifica-calendario .obbligatorio').each(function(){
			if($(this).val() == "")
			{
				errori++;
				$(this).addClass('errore');
			}
		});

		// Controllo riempimento campi obbligatori (SELECT)
		$('#form-modifica-calendario .selectpicker').each(function(){
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
			$.post("gestionepianoorario.php", { azione: "salva-calendario", data: $('#form-modifica-calendario').serialize() })

			.done(function(data) {
									
				// se è tutto OK
				if(data == "OK")
				{
					$("#modal-modifica-calendario").modal("hide");
					
					destroy_calendar();
					caricaDatiCalendario();
					
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
	
	
	
	
	
	
	
})(jQuery);