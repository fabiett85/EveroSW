(function($) {

	'use strict';

	var tabellaDatiOrdiniProduzione;	
	var g_idStatoOrdine = $('#filtro-ordini').val();
	
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
	
	
	// FUNZIONE: RECUPERA ELENCO ORDINI
	function visualizzaElencoOrdini(idStatoOrdine) {
		// visualizzo il corpo della distinta risorse	
		$.post("ordiniproduzione.php", { azione: "mostra", idStatoOrdine: idStatoOrdine })
		.done(function(data) {
			
			var currentPage = tabellaDatiOrdiniProduzione.page();	
			
			// aggiorno i dati della tabella e lo stato di attivazione/disattivazione dei pulsanti
			if (data != "NO_ROWS") {
				$('#tabellaDati-ordini').dataTable().fnClearTable();
				$('#tabellaDati-ordini').dataTable().fnAddData(JSON.parse(data));
				tabellaDatiOrdiniProduzione.page(currentPage).draw(false); 				
			}
			else {
				$('#tabellaDati-ordini').dataTable().fnClearTable();
			}
		});		
	}
	
	
	
	//VISUALIZZA DATATABLE COMMESSE
	$(function() {
		
		//VALORE DEFAULT SELECTPICKER
		$(".selectpicker").selectpicker({
			noneSelectedText : 'Seleziona...'
		});
		
		$.fn.dataTable.moment("DD/MM/YYYY - HH:mm");
	
		tabellaDatiOrdiniProduzione = $('#tabellaDati-ordini').DataTable({
			
			"order": [ 4, "desc" ],
			"aLengthMenu": [
				[10, 20, 30, 50, 100, -1],
				[10, 20, 30, 50, 100, "Tutti"]
			],
			
			"iDisplayLength": 10,
			
			"columns": [
			    { "data": "IdProduzione" },
				{ "data": "Prodotto" }, 
			    { "data": "QtaRichiesta" },	
				{ "data": "UnitaDiMisura" },	
			    { "data": "DataOraCompilazione" },				
			    { "data": "DataOraProgrammazione" },				
				{ "data": "Lotto" },
				{ "data": "NoteProduzione" },	
				{ "data": "StatoOrdine" },				
			    { "data": "azioni" }									
			],		
			"columnDefs": [		
				{
					"targets": [ 9 ],
					"className": 'center-bolded',
				},	
				{ 	
					"width": "15%", 
					"targets": [ 0, 1 ], 
				},				
				{ 	
					"width": "10%", 
					"targets": [ 4, 5, 6 ], 
				},					
				{ 	
					"width": "5%", 
					"targets": [ 2, 3, 8, 9 ], 
				}						
			],			

			"language": linguaItaliana,
		    "drawCallback": function( settings ) {
				
				var api = this.api();
				api.rows().every( function ( rowIdx, tableLoop, rowLoop ) {
				
					var idRiga = this.node();					
					var data = this.data();
					var statoLinea = this.data()['StatoOrdine'];

					if (statoLinea == "OK") {
						$(idRiga).css('background-color',  'rgba(0, 255, 0, 0.6)');
					}
					else if (statoLinea == "CHIUSO") {
						$(idRiga).css('background-color',  'rgba(101, 108, 108, 0.2)');
					}
					else if (statoLinea == "ATTIVO") {
						$(idRiga).css('background-color',  'rgba(102, 204, 255, 0.6)');
					}	
					else if (statoLinea == "MANUTENZIONE") {
						$(idRiga).css('background-color',  'rgba(102, 0, 204, 0.3)'); 
					}
							

				});				
			}				
			//"dom": "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4'l><'col-sm-8'p>>"			

		});
		
		
		// COMMESSE DI PRODUZIONE: RIMUOVO BORDO DI ERRORE SU SELEZIONE CAMPO
		$('#form-ordine-produzione input').on('blur',function(){
			$(this).removeClass('errore');
		})

	
		// COMMESSE: VISUALIZZA TABELLA
		visualizzaElencoOrdini(g_idStatoOrdine);
		
		// COMMESSE DI PRODUZIONE: INSERIMENTO NUOVA COMMESSA DI PRODUZIONE
		$('#nuovo-ordine-produzione').on('click',function(){
			
						
			//Genero data e ora per pre-inizializzare il campo
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
			
			
			var dt = new Date();
			var ore = dt.getHours();
			var minuti = dt.getMinutes();
			var secondi = dt.getSeconds();
			if(ore<10) {
				ore = '0'+ore;
			} 
			if(minuti<10) {
				minuti = '0'+minuti;
			}
			if(secondi<10) {
				secondi = '0'+secondi;
			}	


			var dataOdierna = yyyy + "-" + mm + "-" + dd;
			var oraOdierna = ore + ":" + minuti;
				
				
			$('#form-ordine-produzione').removeClass('errore');
			
			$('#form-ordine-produzione')[0].reset();
			$('#form-ordine-produzione #op_IdProduzione').prop('disabled', false);	
			$('#form-ordine-produzione #op_Prodotto').prop('disabled', false);	
			$('#form-ordine-produzione .selectpicker').val('default');
			$('#form-ordine-produzione .selectpicker').selectpicker('refresh');
			
			$('#form-ordine-produzione').find('input#azione').val('nuovo');
			$('#modal-ordine-produzione-label').text('INSERIMENTO NUOVA COMMESSA');
			
			$('#form-ordine-produzione').find('input#op_DataOrdine').val(dataOdierna);	
			$('#form-ordine-produzione').find('input#op_OraOrdine').val(oraOdierna);
			$('#form-ordine-produzione').find('input#op_DataProduzione').val(dataOdierna);	
			$('#form-ordine-produzione').find('input#op_OraProduzione').val(oraOdierna);
			
			$("#modal-ordine-produzione").modal("show");
		});		

	});	
	
	
	
	// ESEGUO CON CADENZA REGOLARE (OGNI 10 SECONDI) IL RELOAD DELLA TABELLA COMMESSE PER MOSTRARE DATI AGGIORNATI
	setInterval( function () {
		visualizzaElencoOrdini(g_idStatoOrdine);
	}, 20000 );	


		
	// VISUALIZZAZIONE ELENCO COMMESSE IN BASE ALLO STATO SELEZIONATO NELLA COMBO
	$('#filtro-ordini').on('change',function(){
       g_idStatoOrdine = $(this).val();
		visualizzaElencoOrdini(g_idStatoOrdine);
    });	
	
	
	
	// SU CAMBIO PRODOTTO SELEZIONATO, RECUPERO LA RELATIVA UNITA' DI MISURA E IMPOSTO LA MEDESIMA ANCHE PER LA COMMESSA IN OGGETTO
	$('#op_Prodotto').on('change',function(){
       var idProdotto = $(this).val();
	   
		$.post("utilities.php", { azione: "recupera-udm", codice: idProdotto })
		.done(function(data) {
			$('#form-ordine-produzione #op_Udm').val(data);
			$('#form-ordine-produzione #op_Udm').selectpicker('refresh');
		});
    });		


	
	// COMMESSE DI PRODUZIONE: CANCELLAZIONE COMMESSA SELEZIONATO
	$('body').on('click','a.cancella-ordine-produzione',function(e){
		
		e.preventDefault();
		
		var idOrdineProduzione = $(this).data('id-ordine-produzione');
		
		swal({
			title: 'Attenzione',
			text: "Confermi di voler eliminare la commessa in oggetto? L'eliminazione è irreversibile.",
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
				$.post("ordiniproduzione.php", { azione: "cancella-ordine-produzione", id: idOrdineProduzione })
				.done(function(data) {
					
					swal.close();
					
					visualizzaElencoOrdini(g_idStatoOrdine);
				});	
			}
			else
			{
				swal.close();
			}
		});
		return false;
	});
	
	
	
	
	// COMMESSE DI PRODUZIONE: CARICAMENTO POPUP PER MODIFICA COMMESSA
	$('body').on('click','a.modifica-ordine-produzione',function(e){
		
		e.preventDefault();
		
		var idOrdineProduzione = $(this).data('id-ordine-produzione');
		
		$.post("ordiniproduzione.php", { azione: "recupera-ordine", codice: idOrdineProduzione })
		.done(function(data) {
			
			$('#form-ordine-produzione')[0].reset();
			$('#form-ordine-produzione input').removeClass('errore');
			
			var dati = JSON.parse(data);
			
			
			// recupero valori per input-text
			for(var chiave in dati)
			{
				if(dati.hasOwnProperty(chiave))
				{
					$('#form-ordine-produzione').find('input#' + chiave).val(dati[chiave]);
					$('#form-ordine-produzione select#' + chiave).val(dati[chiave]);
				}
			}

			$('#op_IdProduzione').prop('disabled', true);	
			$('#op_Prodotto').prop('disabled', true);	
			$("#form-ordine-produzione .selectpicker").selectpicker('refresh');
					

			// aggiungo il campo CodiceCliente come id della modifica
			$('#form-ordine-produzione').find('input#op_IdOrdine_Aux').val(dati['op_IdProduzione']);
			$('#form-ordine-produzione').find('input#azione').val('modifica');
			$('#modal-ordine-produzione-label').text('MODIFICA COMMESSA');
		
			
			$("#modal-ordine-produzione").modal("show");
		});
		
		return false;
		
	});
	
	
	
	
	// COMMESSE DI PRODUZIONE: SALVATAGGIO DA POPUP MODIFICA/INSERIMENTO 
	$('body').on('click','#salva-ordine-produzione',function(e){
		
		e.preventDefault();
		
		// inizializzo il contatore errori
		var errori = 0;
		var azione =  $('#azione').val();
		
		// controllo che i campi obbligatori siano tutti riempiti
		$('#form-ordine-produzione .obbligatorio').each(function(){
			if($(this).val() == "")
			{
				errori++;
				$(this).addClass('errore');
			}
		});


		$('#form-ordine-produzione .selectpicker').each(function(){
			if($(this).val() == null)
			{
				errori++;
				$(this).addClass('errore');
			}
		});	
		
		
		// se ho anche solo un errore mi fermo qui
		if((errori > 0))
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
			
			var lottoInserito = $('#op_Lotto').val();
			var idProduzione = $('#op_IdProduzione').val();
			
			// salvo i dati
			$.post("ordiniproduzione.php", { azione: "verifica-valori-ripetuti", lottoInserito: lottoInserito, idProduzione: idProduzione })
			.done(function(dataVerificaLotto) {

				if (dataVerificaLotto == "OK" || azione == "modifica") {
				
					// Riabilito i campi per poter serializzare i dati
					$('#op_IdProduzione').prop('disabled', false);	
					$('#op_Prodotto').prop('disabled', false);	
				
					// salvo i dati
					$.post("ordiniproduzione.php", { azione: "salva-ordine-produzione", data: $('#form-ordine-produzione').serialize() })
					.done(function(data) {
						
						// se è tutto OK
						if(data == "OK")
						{
							$("#modal-ordine-produzione").modal("hide");
							
							// ricarico la datatable per vedere le modifiche
							visualizzaElencoOrdini(g_idStatoOrdine);
							
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
				else {
					swal({
						title: 'Attenzione',
						text: "Lotto inserito già utilizzato, desideri proseguire comunque?.",
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
								text: "Sì, prosegui",
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
							
							// salvo i dati
							$.post("ordiniproduzione.php", { azione: "salva-ordine-produzione", data: $('#form-ordine-produzione').serialize() })
							.done(function(data) {
													
								// se è tutto OK
								if(data == "OK")
								{
									$("#modal-ordine-produzione").modal("hide");
									
									// ricarico la datatable per vedere le modifiche
									visualizzaElencoOrdini(g_idStatoOrdine);
									
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
					});			

				}
			});
		}

		return false;
		
	});
	
})(jQuery);