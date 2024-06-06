(function($) {

	'use strict';
	
	var tabellaDatiRicM;
	
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
	
	

	$(function() {
	
	
		//VALORE DEFAULT SELECTPICKER
		$(".selectpicker").selectpicker({
			noneSelectedText : 'Seleziona...'
		});
	
	
		//VISUALIZZA DATATABLE RICETTE MACCHINA
		tabellaDatiRicM = $('#tabellaDati-ricm').DataTable({
			"order": [[ 0, "asc" ], [ 1, "asc" ] ],
			"aLengthMenu": [
				[12, 24, 50, 100, -1],
				[12, 24, 50, 100, "Tutti"]
			],
			"iDisplayLength": 50,
			"ajax": {
				"url": $('#tabellaDati-ricm').attr('data-source'),
				"dataSrc": ""
			},
			"columns": [
				{ "data": "DescrizioneRisorsa" },
			    { "data": "DescrizioneProdotto" },
			    { "data": "IdRicetta" },
				{ "data": "DescrizioneRicetta" },				
			    { "data": "azioni" }
			],
			"columnDefs": [		
				{
					"targets": [ 4 ],
					"className": 'center-bolded',
				},	
				{ 	
					"width": "5%", 
					"targets": [ 4 ], 
				}				
			],				
			"language": linguaItaliana,
			//"dom":  "<'row'<'col-sm-6'><'col-sm-6'f>r>" + "t" + "<'row'<'col-sm-4'l><'col-sm-8'p>>"			
		});
		
		
	});
	
	
	// RICETTE MACCHINA: CANCELLAZIONE SEGNALAZIONE DI CAMPO VUOTO
	$('#form-ricm input').on('blur',function(){
		$(this).removeClass('errore');
	})	
	
	
	
	//RICETTE MACCHINA: INSERIMENTO NUOVA RICETTA
	$('#nuova-ricm').on('click',function(){

		$('#form-ricm input').removeClass('errore');
		
		$('#form-ricm')[0].reset();
		$('#form-ricm .selectpicker').val('default');
		$('#form-ricm .selectpicker').selectpicker('refresh');
		

		$('#form-ricm').find('input#ricm_azione').val('nuovo');
		$('#modal-ricm-label').text('INSERIMENTO NUOVA RICETTA');
		$("#modal-ricm").modal("show");
	});
	
	
	
	// RICETTE MACCHINA: CANCELLAZIONE RICETTA SELEZIONATA
	$('body').on('click','a.cancella-entry-ricm',function(e){
		
		e.preventDefault();
		
		var idRiga = $(this).data('id_riga');
		
		swal({
			title: 'Attenzione',
			text: "Confermi di voler eliminare la ricetta in oggetto? L'eliminazione è irreversibile.",
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
				$.post("ricettemacchina.php", { azione: "cancella-ricm", idRiga: idRiga })
				.done(function(data) {
					
					// chiudo
					swal.close();
					
					// ricarico la datatable per vedere le modifiche
					tabellaDatiRicM.ajax.reload(null, false);
				});	
			}
			else
			{
				swal.close();
			}
		});
		return false;
	});
	
	

	
	
	
	// RICETTE MACCHINA: CARICAMENTO POPUP PER MODIFICA
	$('body').on('click','a.modifica-entry-ricm',function(e){
		
		e.preventDefault();
		
		var idRiga = $(this).data('id_riga');
		
		$('#form-ricm input').removeClass('errore');
		
		$('#form-ricm')[0].reset();
			
		$.post("ricettemacchina.php", { azione: "recupera-ricm", idRiga: idRiga })
		.done(function(data) {

			var dati = JSON.parse(data);
			
			// visualizza nel popup i dati recuperati
			for(var chiave in dati)
			{
				if(dati.hasOwnProperty(chiave))
				{
					$('#form-ricm').find('input#' + chiave).val(dati[chiave]);
					$('#form-ricm select#' + chiave).val(dati[chiave]);
					$('#form-ricm select#' + chiave).selectpicker('refresh');
				}
			}
			

			// aggiungo il campo CodiceCliente come id della modifica
			$('#form-ricm input').removeClass('errore');
			$('#form-ricm').find('input#ricm_azione').val('modifica');
			$('#modal-ricm-label').text('MODIFICA RIGA');		
			
			$("#modal-ricm").modal("show");
		});
		
		return false;
		
	});
	

	
	
	// RICETTE MACCHINA: SALVATAGGIO DA POPUP MODIFICA/INSERIMENTO 
	$('body').on('click','#salva-entry-ricm',function(e){
		
		e.preventDefault();
		
		// inizializzo il contatore errori
		var errori = 0;
		
		// Controllo riempimento campi obbligatori (INPUT)
		$('#form-ricm .obbligatorio').each(function(){
			if($(this).val() == "")
			{
				errori++;
				$(this).addClass('errore');
			}
		});

		// Controllo riempimento campi obbligatori (SELECT)
		$('#form-ricm .selectpicker').each(function(){
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
			// salvo i dati
			$.post("ricettemacchina.php", { azione: "salva-ricm", data: $('#form-ricm').serialize() })
			.done(function(data) {
	
				// se è tutto OK
				if(data == "OK")
				{
					$("#modal-ricm").modal("hide");
					
					// ricarico la datatable per vedere le modifiche
					tabellaDatiRicM.ajax.reload(null, false);
					
				}
				else // restituisco un errore senza chiudere la modale
				{
					swal({
						title: "ATTENZIONE",
						text:  data,
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