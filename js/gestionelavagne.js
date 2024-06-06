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
			"order": [[ 1, "asc" ] ],
			"aLengthMenu": [
				[10, 25, 50, 100, -1],
				[10, 25, 50, 100, "Tutti"]
			],
			"iDisplayLength": 10,
			"ajax": {
				"url": $('#tabella-esl').attr('data-source'),
				"dataSrc": ""
			},
			"columns": [
				
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
			    { "data": "azione" }				
			],
			"columnDefs": [		
			
			],				
			"language": linguaItaliana,
			//"dom":  "<'row'<'col-sm-6'><'col-sm-6'f>r>" + "t" + "<'row'<'col-sm-4'l><'col-sm-8'p>>"			
		});
		
		
	});		
	
})(jQuery);	