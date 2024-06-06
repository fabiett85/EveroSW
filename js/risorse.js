(function($) {

	'use strict';
	
	var tabellaDatiRisorse;	
	var linguaItaliana = {
		"processing": "Caricamento...",
		"search": "Ricerca: ",
		"lengthMenu": "_MENU_ righe per pagina",
		"zeroRecords": "Nessun record presente",
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
	
	//VISUALIZZA DATATABLE RISORSE
	$(function() {
		
		//VALORE DEFAULT SELECTPICKER
		$(".selectpicker").selectpicker({
			noneSelectedText : 'Seleziona...'
		});
		
		// MACCHINE: DEFINIZIONE TABELLA
		tabellaDatiRisorse = $('#tabellaDati-risorse').DataTable({
			"order": [[0, "asc" ]],
			"aLengthMenu": [
				[12, 24, 36, 50, 100, -1],
				[12, 24, 36, 50, 100, "Tutti"]
			],
			"iDisplayLength": 12,
			"ajax": {
				"url": $('#tabellaDati-risorse').attr('data-source'),
				"dataSrc": ""
			},
			"columns": [
			    { "data": "Descrizione" },
				{ "data": "LineaProduzione" },
				{ "data": "Ordinamento" } ,
				{ "data": "TTeoricoAttrezzaggio" }, 
			    { "data": "AbiMisure" },
				{ "data": "FlagUltimaMacchina" },
				{ "data": "FlagDisabilitaCalcoloOEE" },
			    { "data": "ProduzioneAttivata" },
			    { "data": "StatoRisorsa" },
				{ "data": "StatoAllarme" },
				{ "data": "TotOreFunz" },
				{ "data": "FreqOreMan" },	
				{ "data": "Udm" },				
			    { "data": "azioni" },
			],
			"columnDefs": [		
				{
					"targets": [ 13 ],
					"className": 'center-bolded',
				},	
				{ 	
					"width": "5%", 
					"targets": [ 13 ], 
				},
				{ 	
					"visible": false, 
					"targets": [ 8, 9 ], 
				}	
			],				
			"language": linguaItaliana,
			//"dom":  "<'row'<'col-sm-8'><'col-sm-4'f>r>"+ "t" + "<'row'<'col-sm-12'r>>" + "<'row'<'col-sm-4'l><'col-sm-8'p>>"			
		});
		
	});
	
	
	// MACCHINE: CANCELLAZIONE SEGNALAZIONE DI CAMPO VUOTO
	$('#form-risorsa input').on('blur',function(){
		$(this).removeClass('errore');
	})		
	
	
	
	// MACCHINE: INSERIMENTO NUOVA RISORSA
	$('#nuova-risorsa').on('click',function(){
		
		$('#form-risorsa input').removeClass('errore');
		
		$('#form-risorsa')[0].reset();
		$('#ris_TTeoricoAttrezzaggio, #ris_OreFunzTotali, #ris_OreFunz_FreqMan').val(0);
		$('#form-risorsa .selectpicker').val('default');
		$('#form-risorsa .selectpicker').selectpicker('refresh');
		
		$('#form-risorsa').find('input#azione').val('nuovo');
		$('#modal-risorsa-label').text('INSERIMENTO NUOVA MACCHINA');
		$("#modal-risorsa").modal("show");
		
	});
	
	
	// MACCHINE: CANCELLAZIONE RISORSA SELEZIONATA
	$('body').on('click','a.cancella-risorsa',function(e){
		
		e.preventDefault();
		
		var idRiga = $(this).data('id_riga');
		
		swal({
			title: 'Attenzione',
			text: "Confermi di voler eliminare la macchina in oggetto? L'eliminazione è irreversibile.",
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
				$.post("risorse.php", { azione: "cancella-risorsa", id: idRiga })
				.done(function(data) {

					if (data == "RISORSA_OCCUPATA") {
						swal({
							title: "Attenzione",
							text: "La macchina ha ordini pendenti, impossibile procedere.",
							icon: "warning",
							button: "Ho capito",
							closeModal: true,
						});						
						
					}
					else if(data == "ERRORE") {
						swal({
							title: "Attenzione",
							text: "Errore durante l'eliminazione, riprovare.",
							icon: "warning",
							button: "Ho capito",
							closeModal: true,
						});						
					}
					else if(data == "OK") {
					
						// Ricarico la datatable per vedere le modifiche
						tabellaDatiRisorse.ajax.reload(null, false);
					}
					swal.close();
				});	
			}
			else
			{
				swal.close();
			}
		});
		return false;
	});
	
	
	
	
	
	// MACCHINE: CARICAMENTO POPUP PER MODIFICA
	$('body').on('click','a.modifica-risorsa',function(e){
		
		e.preventDefault();
		
		var idRiga = $(this).data('id_riga');
		
		$.post("risorse.php", { azione: "recupera", codice: idRiga })
		.done(function(data) {
			
			$('#form-risorsa')[0].reset();
			$('#form-risorsa input').removeClass('errore');
			
			var dati = JSON.parse(data);
			
			
			//  Visulizzo nel popup i valori recuperati
			for(var chiave in dati)
			{
				if(dati.hasOwnProperty(chiave))
				{
					$('#form-risorsa').find('input#' + chiave).val(dati[chiave]);
					$('#form-risorsa select#' + chiave).val(dati[chiave]);
					$('#form-risorsa select#' + chiave).selectpicker('refresh');
				}
			}
			
			$('#ris_LineaProduzione').val(dati['ris_LineaProduzione']);
			
			// Gestisco a parte i valori relativi alle checkbox:
			// 'abilitazione misure'
			if (dati['ris_AbiMisure'] == 1) {
				$('#form-risorsa').find('input#ris_AbiMisure').prop('checked', true);
			}
			else {
				$('#form-risorsa').find('input#ris_AbiMisure').prop('checked', false);
			}
			
			// 'ultima risorsa'
			if (dati['ris_FlagUltima'] == 1) {
				$('#form-risorsa').find('input#ris_FlagUltima').prop('checked', true);
			}
			else {
				$('#form-risorsa').find('input#ris_FlagUltima').prop('checked', false);
			}

			// 'abilita calcolo OEE'
			if (dati['ris_FlagDisabilitaOEE'] == 1) {
				$('#form-risorsa').find('input#ris_FlagDisabilitaOEE').prop('checked', true);
			}
			else {
				$('#form-risorsa').find('input#ris_FlagDisabilitaOEE').prop('checked', false);
			}

			// Aggiungo il campo 'IdRisorsa' come id della modifica
			$('#form-risorsa').find('input#ris_IdRisorsa_Aux').val(dati['ris_IdRisorsa']);
			$('#form-risorsa').find('input#azione').val('modifica');
			$('#modal-risorsa-label').text('MODIFICA MACCHINA');
		
			$("#modal-risorsa").modal("show");
		});
		
		return false;
		
	});
	
	
	

	
	
	
	// MACCHINE: SALVATAGGIO DA POPUP MODIFICA/INSERIMENTO 
	$('body').on('click','#salva-risorsa',function(e){
		
		e.preventDefault();
		
		// inizializzo il contatore errori
		var errori = 0;
		
		// Controllo riempimento campi obbligatori (INPUT)
		$('#form-risorsa .obbligatorio').each(function(){
			if($(this).val() == "")
			{
				errori++;
				$(this).addClass('errore');
			}
		});

		// Controllo riempimento campi obbligatori (SELECT)
		$('#form-risorsa .selectpicker').each(function(){
			if($(this).val() == null)
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
			var abiMisure;
			var flagUltimaMacchina;
			var flagDisabilitaOEE;
			
			//recupero valore della checkbox e lo formato opportunamente
			if ($('#form-risorsa #ris_AbiMisure').is(":checked")) {
				abiMisure = 1;
			}
			else {
				abiMisure = 0;
			
			}
			
			//recupero valore della checkbox e lo formato opportunamente
			if ($('#form-risorsa #ris_FlagUltima').is(":checked")) {
				flagUltimaMacchina = 1;
			}
			else {
				flagUltimaMacchina = 0;
			
			}

			//recupero valore della checkbox e lo formato opportunamente
			if ($('#form-risorsa #ris_FlagDisabilitaOEE').is(":checked")) {
				flagDisabilitaOEE = 1;
			}
			else {
				flagDisabilitaOEE = 0;
			
			}
			
			// salvo i dati
			$.post("risorse.php", { azione: "salva-risorsa", data: $('#form-risorsa').serialize(), abiMisure: abiMisure, flagUltimaMacchina: flagUltimaMacchina, flagDisabilitaOEE: flagDisabilitaOEE })
			.done(function(data) {
									
				// se è tutto OK
				if(data == "OK")
				{
					$("#modal-risorsa").modal("hide");
					
					// ricarico la datatable per vedere le modifiche
					tabellaDatiRisorse.ajax.reload(null, false);
					
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