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