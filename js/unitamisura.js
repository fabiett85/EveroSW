(function($) {

	'use strict';
	
	var tabellaDatiUm;
	
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
	
	
		//VISUALIZZA DATATABLE UNITA' DI MISURA
		tabellaDatiUm = $('#tabellaDati-um').DataTable({
			"order": [[ 0, "asc" ], [ 1, "asc" ] ],
			"aLengthMenu": [
				[12, 24, 50, 100, -1],
				[12, 24, 50, 100, "Tutti"]
			],
			"iDisplayLength": 12,
			"ajax": {
				"url": $('#tabellaDati-um').attr('data-source'),
				"dataSrc": ""
			},
			"columns": [
			    { "data": "UmSigla" },
			    { "data": "UmDescrizione" },			
			    { "data": "azioni" }
			],
			"columnDefs": [		
				{
					"targets": [ 2 ],
					"className": 'center-bolded',
				},	
				{ 	
					"width": "10%", 
					"targets": [ 2 ], 
				}				
			],				
			"language": linguaItaliana,
			//"dom":  "<'row'<'col-sm-6'><'col-sm-6'f>r>" + "t" + "<'row'<'col-sm-4'l><'col-sm-8'p>>"			
		});
		
		
	});
	
	
	
	// UNITA' DI MISURA: CANCELLAZIONE SEGNALAZIONE DI CAMPO VUOTO
	$('#form-um input').on('blur',function(){
		$(this).removeClass('errore');
	})		
	
	
	
	// UNITA' DI MISURA: INSERIMENTO NUOVA CATEGORIA
	$('#nuova-um').on('click',function(){
		
		$('#form-um input').removeClass('errore');
		
		$('#form-um')[0].reset();
		$('#form-um .selectpicker').val('default');
		$('#form-um .selectpicker').selectpicker('refresh');
		
		$('#form-um').find('input#um_azione').val('nuovo');
		$('#modal-um-label').text('INSERIMENTO NUOVA RIGA');
		$("#modal-um").modal("show");
	});
	
	
	
	
	
	// UNITA' DI MISURA: CANCELLAZIONE CATEGORIA SELEZIONATA
	$('body').on('click','a.cancella-entry-um',function(e){
		
		e.preventDefault();
		
		var idRiga = $(this).data('id_riga');
		
		swal({
			title: 'Attenzione',
			text: "Confermi di voler eliminare l'unità di misura in oggetto? L'eliminazione è irreversibile.",
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
				$.post("unitamisura.php", { azione: "cancella-um", idRiga: idRiga })
				.done(function(data) {
					
					// chiudo
					swal.close();
					
					// ricarico la datatable per vedere le modifiche
					tabellaDatiUm.ajax.reload(null, false);
				});	
			}
			else
			{
				swal.close();
			}
		});
		return false;
	});
	
	

	
	
	
	// UNITA' DI MISURA: CARICAMENTO POPUP PER MODIFICA
	$('body').on('click','a.modifica-entry-um',function(e){
		
		e.preventDefault();
		
		$('#form-um input').removeClass('errore');
		
		$('#form-um')[0].reset();
		
		var idRiga = $(this).data('id_riga');
		
		$.post("unitamisura.php", { azione: "recupera-um", idRiga: idRiga })
		.done(function(data) {
			
			var dati = JSON.parse(data);
			
			// Valorizzo popup con i dati recuperati
			for(var chiave in dati)
			{
				if(dati.hasOwnProperty(chiave))
				{
					$('#form-um').find('input#' + chiave).val(dati[chiave]);
					$('#form-um select#' + chiave).val(dati[chiave]);
					$('#form-um select#' + chiave).selectpicker('refresh');
				}
			}
			
			$('#form-um').find('input#um_azione').val('modifica');
			$('#modal-um-label').text('MODIFICA RIGA');		
			$("#modal-um").modal("show");
		});
		
		return false;
		
	});
	

	
	
	// UNITA' DI MISURA: SALVATAGGIO DA POPUP MODIFICA/INSERIMENTO 
	$('body').on('click','#salva-entry-um',function(e){
		
		e.preventDefault();

		var errori = 0;
		
		// Controllo riempimento campi obbligatori (INPUT)
		$('#form-um .obbligatorio').each(function(){
			if($(this).val() == "")
			{
				errori++;
				$(this).addClass('errore');
			}
		});

		// Controllo riempimento campi obbligatori (SELECT)
		$('#form-um .selectpicker').each(function(){
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
			$.post("unitamisura.php", { azione: "salva-um", data: $('#form-um').serialize() })
			.done(function(data) {
	
				// se è tutto OK
				if(data == "OK")
				{
					$("#modal-um").modal("hide");
					
					// ricarico la datatable per vedere le modifiche
					tabellaDatiUm.ajax.reload(null, false);
					
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