(function($) {

	'use strict';
	
	var tabellaDatiOrdiniTracciabilita;	
	var tabellaDatiComponentiTracciabilita;
	var tabellaDatiOrdiniTracciabilita_dettaglio;
	var tabellaDatiComponentiTracciabilita_dettaglio;
	
	var g_trcOrdini_idProduzione;
	var g_trcComponenti_idProduzione
	
	
	var linguaItaliana_ordini = {
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
	
	var linguaItaliana_componenti = {
		"processing": "Caricamento...",
		"search": "Ricerca: ",
		"lengthMenu": "_MENU_ righe per pagina",
		"zeroRecords": "Nessun componente disponibile",
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
		
		$.fn.dataTable.moment("DD/MM/YYYY - HH:mm");
	
		tabellaDatiOrdiniTracciabilita = $('#tabellaDati-tracciabilita-ordini').DataTable({
			
			"order": [ 3, "desc" ],
			"aLengthMenu": [
				[5, 10, 15, 20, 100, -1],
				[5, 10, 15, 20, 100, "Tutti"]
			],
			
			"iDisplayLength": 5,
			
			"columns": [
			    { "data": "IdProduzione" },
				{ "data": "Prodotto" }, 
			    { "data": "QtaRichiesta" },				
			    { "data": "DataOraInizio" },
				{ "data": "DataOraFine" },
				{ "data": "Lotto" },
				{ "data": "IdProduzioneAux" }			
			],
			"columnDefs": [		
				{
					"targets": [ 6 ],
					"visible": false,
				}
			],			

			"language": linguaItaliana_ordini,
			"info":     false,			
			rowId: 'IdProduzione',
			select: true
			//"dom": "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4'l><'col-sm-8'p>>"			

		});
		
		
		tabellaDatiOrdiniTracciabilita_dettaglio = $('#tabellaDati-tracciabilita-ordini-dettaglio').DataTable({
			
			"order": [ 1, "asc" ],
			"aLengthMenu": [
				[5, 10, 15, 20, 100, -1],
				[5, 10, 15, 20, 100, "Tutti"]
			],
			
			"iDisplayLength": 10,
															
			"columns": [
			    //{ "data": "IdProduzione" },			
			    { "data": "CodiceComponente" },
				{ "data": "DescrizioneComponente" }, 						
			    { "data": "LottoComponente" },
				{ "data": "QtaUsata" },
				{ "data": "IndiceRiga" }
			],	
			"columnDefs": [		
				{
					"targets": [ 4 ],
					"visible": false
				}
			],

			"language": linguaItaliana_componenti,
			"info":     false,
		    "drawCallback": function( settings ) {
				var api = this.api();
				api.rows().every( function ( rowIdx, tableLoop, rowLoop ) {
				
					var data = this.data();
					var idRiga = this.node();							
					var indiceRiga = this.data()['IndiceRiga'];

					// Colorazione delle righe tabella in base al valore del campo stato
					if (indiceRiga == 0) {
						$(idRiga).css('background-color', 'rgba(51, 153, 255, 0.4)');
					}
					else if (indiceRiga == 1) {
						$(idRiga).css('background-color', 'rgba(51, 153, 255, 0.2)');
					}	
						
				});
			}
			//"dom": "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4'l><'col-sm-8'p>>"			

		});

		
		
		tabellaDatiComponentiTracciabilita = $('#tabellaDati-tracciabilita-componenti').DataTable({
			
			"order": [ 3, "desc" ],
			"aLengthMenu": [
				[5, 10, 15, 20, 100, -1],
				[5, 10, 15, 20, 100, "Tutti"]
			],
			
			"iDisplayLength": 5,	
			
			"columns": [
			    { "data": "IdProdotto" },
				{ "data": "DescrizioneProdotto" }, 
			    { "data": "CategoriaProdotto" },				
			    { "data": "SottocategoriaProdotto" },				
			    { "data": "Lotto" }						
			],		
		

			"language": linguaItaliana_componenti,
			"info":     false,			
			rowId: 'IdProdotto',
			select: true
			//"dom": "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4'l><'col-sm-8'p>>"			

		});	


		
		tabellaDatiComponentiTracciabilita_dettaglio = $('#tabellaDati-tracciabilita-componenti-dettaglio').DataTable({
			
			"order": [ 1, "asc" ],
			"aLengthMenu": [
				[5, 10, 15, 20, 100, -1],
				[5, 10, 15, 20, 100, "Tutti"]
			],
			
			"iDisplayLength": 10,
															
			"columns": [
			    { "data": "IdProduzione" },
				{ "data": "Prodotto" }, 
			    { "data": "QtaRichiesta" },				
			    { "data": "DataOraInizio" },	
				{ "data": "DataOraFine" },	
				{ "data": "Lotto" }	,
				{ "data": "IndiceRiga" }				
			],	
			"columnDefs": [		
				{
					"targets": [ 6 ],
					"visible": false,
				}				
			],

			"language": linguaItaliana_ordini,
			"info":     false,
		    "drawCallback": function( settings ) {
				var api = this.api();
				api.rows().every( function ( rowIdx, tableLoop, rowLoop ) {
				
					var data = this.data();
					var idRiga = this.node();							
					var indiceRiga = this.data()['IndiceRiga'];

					// Colorazione delle righe tabella in base al valore del campo stato
					if (indiceRiga == 0) {
						$(idRiga).css('background-color', 'rgba(255, 153, 0, 0.4)');
					}
					else if (indiceRiga == 1) {
						$(idRiga).css('background-color', 'rgba(255, 153, 0, 0.2)');
					}	
						
				});
			}
		});
		
		

								
								
		
		// PER POST REFRESH: MEMORIZZO TAB ATTUALMENTE MOSTRATO PER RIPRISTINO VISUALIZZAZIONE DELLO STESSO IN CASO DI REFRESH (TAB ELENCHI DETTAGLIO)
		$('#tab-tracciabilita a[data-toggle="tab"]').on('show.bs.tab', function(e) {
			sessionStorage.setItem('activeTab_tracciabilita', $(e.target).attr('href'));

			var tabSelezionato = $(e.target).attr('href');

			if (tabSelezionato == '#tracciabilita-ordini') {

				
				// visualizzo il corpo della distinta risorse	
				$.post("tracciabilita.php", { azione: "mostra-ordini" })
				.done(function(data) {

					$('#tabellaDati-tracciabilita-ordini').dataTable().fnClearTable();

					// aggiorno i dati della tabella e lo stato di attivazione/disattivazione dei pulsanti
					if (data != "NO_ROWS") {
						$('#tabellaDati-tracciabilita-ordini').dataTable().fnAddData(JSON.parse(data));			
					}
				});
				
			}
			else if (tabSelezionato == '#tracciabilita-componenti') {
	
				
				// visualizzo il corpo della distinta risorse	
				$.post("tracciabilita.php", { azione: "mostra-componenti" })
				.done(function(data) {
					
					$('#tabellaDati-tracciabilita-componenti').dataTable().fnClearTable();

					// aggiorno i dati della tabella e lo stato di attivazione/disattivazione dei pulsanti
					if (data != "NO_ROWS") {
						$('#tabellaDati-tracciabilita-componenti').dataTable().fnAddData(JSON.parse(data));			
					}
				});
				
			}
			
		});
		var activeTab_tracciabilita = sessionStorage.getItem('activeTab_tracciabilita');
		if(activeTab_tracciabilita){
			$('#tab-tracciabilita a[href="' + activeTab_tracciabilita + '"]').tab('show');
		}
		else {
			$('#tab-tracciabilita a[href="#tracciabilita-ordini"]').tab('show');
		}

		
	});	
	
	


	
	
	// RECUPERA ELENCO COMPONENTI PER L'COMMESSA SELEZIONATO (al clic su una riga dell'elenco lavori)
	$('#tabellaDati-tracciabilita-ordini tbody').on( 'click', 'tr', function () {
		
		var datiRiga = $('#tabellaDati-tracciabilita-ordini').DataTable().row(this).data();
		var idProduzione = datiRiga['IdProduzioneAux'];
		g_trcOrdini_idProduzione = datiRiga['IdProduzioneAux'];

		// invoco il metodo che recupera le righe della distinta base per il prodotto selezionato
		$.post("tracciabilita.php", { azione: "mostra-dettaglio-ordini", idProduzione: idProduzione })
		.done(function(data) {

			$('#tabellaDati-tracciabilita-ordini-dettaglio').dataTable().fnClearTable();
			
			// se ho risultati, li mostro nella tabella
			if (data != "NO_ROWS") {
				$('#tabellaDati-tracciabilita-ordini-dettaglio').dataTable().fnAddData(JSON.parse(data));

			}
		});

		
	});	


	// RECUPERA ELENCO COMPONENTI PER L'COMMESSA SELEZIONATO (al clic su una riga dell'elenco lavori)
	$('#tabellaDati-tracciabilita-componenti tbody').on( 'click', 'tr', function () {
		
		var datiRiga = $('#tabellaDati-tracciabilita-componenti').DataTable().row(this).data();
		var idComponente = datiRiga['IdProdotto'];
		var lotto = datiRiga['Lotto'];
		
		// invoco il metodo che recupera le righe della distinta base per il prodotto selezionato
		$.post("tracciabilita.php", { azione: "mostra-dettaglio-componenti", idComponente: idComponente, lotto: lotto })
		.done(function(data) {

			$('#tabellaDati-tracciabilita-componenti-dettaglio').dataTable().fnClearTable();
			
			// se ho risultati, li mostro nella tabella
			if (data != "NO_ROWS") {
				$('#tabellaDati-tracciabilita-componenti-dettaglio').dataTable().fnAddData(JSON.parse(data));

			}
		});

	});		


	
	
})(jQuery);