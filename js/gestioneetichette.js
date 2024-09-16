(function($) {

	'use strict';
	
	var tabellaEsl;
	
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
		
		//VISUALIZZA DATATABLE LINEA
		tabellaEsl = $('#tabella-esl').DataTable({
			"order": [[ 0, "asc" ] ],
			"aLengthMenu": [
				[10, 25, 50, 100, -1],
				[10, 25, 50, 100, "Tutti"]
			],
			"iDisplayLength": 50,
			"ajax": {
				"url": $('#tabella-esl').attr('data-source'),
				"dataSrc": ""
			},
			"columns": [
				{ "data": "IdRiga" },	
				{ "data": "CodiceEsl" },
			    { "data": "TipoESl" },
				{ "data": "AbiEsl" },
			    { "data": "Serbatoio" },
			    { "data": "Campo1" },
				{ "data": "Campo2" },
			    { "data": "Campo3" },
			    { "data": "Campo4" },
				{ "data": "Campo5" },
			    { "data": "Campo6" },
			    { "data": "Campo7" },
				{ "data": "Campo8" },
			    { "data": "Campo9" },
			    { "data": "Campo10" },
			    { "data": "azioni" }			
			],
			"columnDefs": [		
				{ 	
					"width": "7%", 
					"targets": [ 1 ], 
				},
								{ 	
					"visible": false, 
					"targets": [ 0 ], 
				}
			],
			buttons: [
				{
					extend: 'excel',
					text: 'EXCEL',
					className: 'btn-success',
					exportOptions: {
						columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13],
					},
				},
				{
					extend: 'pdf',
					text: 'PDF',
					className: 'btn-danger',
					orientation: 'landscape',
					download: 'open',
					exportOptions: {
						columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13],
					},
				},
			],			
			language: linguaItaliana,
			autoWidth: true,			
			"language": linguaItaliana,
			dom:
				"<'row'<'col-sm-12 col-md-6 d-flex justify-content-left align-items-center'lB><'col-sm-12 col-md-6'f>>" +
				"<'row'<'col-sm-12'tr>>" +
				"<'row'<'col-sm-12 col-md-6'i><'col-sm-12 col-md-6'p>>",
		});
		
		
	});	

	//VALORE DEFAULT SELECTPICKER
	$(".selectpicker").selectpicker({
		noneSelectedText : 'Seleziona...'
	});

	//Etichette: aggiunta nuova lavagna elettronica
	$('#nuova-esl').on('click',function(){
		
		alert('nuova esl');
		$('#form-esl input').removeClass('errore');
		
		$('#form-esl')[0].reset();
		$('#form-esl .selectpicker').val('default');
		$('#form-esl .selectpicker').selectpicker('refresh');
		
		$('#form-esl').find('input#azione').val('nuovo');
		$('#modal-esl-label').text('INSERIMENTO NUOVA LAVAGNA ELETTRONICA');
		$("#modal-esl").modal("show");
		
	});
	
	//Etichette: cancella lavagna elettronica
	$('body').on('click','a.cancella-esl',function(e){
		
		e.preventDefault();

		alert('cancella esl');
		
	});	

	//Etichette: modifica lavagna elettronica
	$('body').on('click','a.modifica-esl',function(e){
		
		e.preventDefault();
		
		var idRiga = $(this).data('id_riga');

		$.post("gestioneetichette.php", { azione: "recupera", codice: idRiga })
		.done(function(data) {
			
			$('#form-esl')[0].reset();
			$('#form-esl input').removeClass('errore');
			
			var dati = JSON.parse(data);
			
			
			//  Visulizzo nel popup i valori recuperati
			for(var chiave in dati)
			{
				if(dati.hasOwnProperty(chiave))
				{
					$('#form-esl').find('input#' + chiave).val(dati[chiave]);
					$('#form-esl select#' + chiave).val(dati[chiave]);
					$('#form-esl select#' + chiave).selectpicker('refresh');
				}
			}
			
			$('#ris_LineaProduzione').val(dati['ris_LineaProduzione']);
			
			// Gestisco a parte i valori relativi alle checkbox:
			// 'abilitazione misure'
			if (dati['Abilitazione'] == 1) {
				$('#form-esl').find('input#Abilitazione').prop('checked', true);
			}
			else {
				$('#form-esl').find('input#Abilitazione').prop('checked', false);
			}

			// Aggiungo il campo 'IdRisorsa' come id della modifica
			$('#form-esl').find('input#Id').val(dati['Id']);
			$('#form-esl').find('input#azione').val('modifica');
			$('#modal-esl-label').text('MODIFICA DATI LAVAGNA');
		
			$("#modal-esl").modal("show");
		});
		
		return false;		
		
	});		



	//Funzione per gestione aggiornamento lavagne elettroniche 
	$('#btn-update-lavagne').click(function(){
		
		$.get("funzioniprofimax.php", { azione: "update-import" })
		.done(function(data) {
			setTimeout(function(){
				$.get("funzioniprofimax.php", { azione: "update-execute" })
				.done(function(data) {
					if(data == 'OK') {
						swal({
							icon: 'success',
							title: 'Operazione effettuata!',
							text: 'Tracciato dati generato correttamente',
							customClass: {
								confirmButton: 'btn btn-primary mx-1',
								cancelButton: 'btn btn-secondary mx-1',
							},
							buttonsStyling: false,
						});
					}
					else {
						swal({
							icon: 'error',
							title: 'Errore!',
							text: data,
							customClass: {
								confirmButton: 'btn btn-primary mx-1',
								cancelButton: 'btn btn-secondary mx-1',
							},
							buttonsStyling: false,
						});
					}
				}); 
			}, 5000)			

		});

	});	
	
	
	
	
	//Script per eseguire la sincronizzazione tra il DB del gestionale e il DB interno dell'applicazione
	$('#btn-update-tabella').click(function(){

		$.get("aggiornamentotabellaesl.php", { azione: "sincronizza-tabella" })
		.done(function(data) {
			if(data == 'OK') {
				tabellaEsl.ajax.reload(null, false);
				swal({
					icon: 'success',
					title: 'Operazione effettuata!',
					text: 'Dati aggiornati correttamente!',
					customClass: {
						confirmButton: 'btn btn-primary mx-1',
						cancelButton: 'btn btn-secondary mx-1',
					},
					buttonsStyling: false,
				});
			}
			else {
				tabellaEsl.ajax.reload(null, false);
				swal({
					icon: 'error',
					title: 'Errore!',
					text: data,
					customClass: {
						confirmButton: 'btn btn-primary mx-1',
						cancelButton: 'btn btn-secondary mx-1',
					},
					buttonsStyling: false,
				});
			}
		}); 		

	});
				
	
})(jQuery);	