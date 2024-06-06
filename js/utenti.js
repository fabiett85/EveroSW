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


		//VISUALIZZA DATATABLE UTENTI
		tabellaDatiRicM = $('#tabellaDati-usr').DataTable({
			"order": [[ 0, "asc" ], [ 1, "asc" ] ],
			"aLengthMenu": [
				[10, 25, 50, 100, -1],
				[10, 25, 50, 100, "Tutti"]
			],
			"iDisplayLength": 10,
			"ajax": {
				"url": $('#tabellaDati-usr').attr('data-source'),
				"dataSrc": ""
			},
			"columns": [
				{ "data": "NomeUtente" },
			   { "data": "CognomeUtente" },
			   { "data": "UsernameUtente" },
				{ "data": "LivelloUtente" },
			   { "data": "MacchineUtente" },
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
			"language": linguaItaliana,
			//"dom":  "<'row'<'col-sm-6'><'col-sm-6'f>r>" + "t" + "<'row'<'col-sm-4'l><'col-sm-8'p>>"
		});


	});


	// UTENTI: CANCELLAZIONE SEGNALAZIONE DI CAMPO VUOTO
	$('#form-usr input').on('blur',function(){
		$(this).removeClass('errore');
	})



	// UTENTI: INSERIMENTO NUOVO UTENTE
	$('#nuovo-usr').on('click',function(){

		$('#form-usr input').removeClass('errore');

		$('#form-usr')[0].reset();
		$('#form-usr .selectpicker').val('default');
		$('#form-usr .selectpicker').selectpicker('refresh');

		$('#privilegi-macchine-user').hide();
		$('#privilegi-macchine-admin').hide();

		$('#form-usr').find('input#usr_azione').val('nuovo');
		$('#modal-usr-label').text('INSERIMENTO NUOVO UTENTE');
		$("#modal-usr").modal("show");
	});



	// UTENTI: CANCELLAZIONE UTENTE SELEZIONATO
	$('body').on('click','a.cancella-entry-usr',function(e){

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
				$.post("utenti.php", { azione: "cancella-usr", idRiga: idRiga })
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






	// UTENTI: CARICAMENTO POPUP PER MODIFICA UTENTE
	$('body').on('click','a.modifica-entry-usr',function(e){

		e.preventDefault();

		var idRiga = $(this).data('id_riga');


		$.post("utenti.php", { azione: "recupera-usr", idRiga: idRiga })
		.done(function(data) {

			$('#form-usr')[0].reset();

			var dati = JSON.parse(data);

			// visualizza nel popup i dati recuperati
			for(var chiave in dati)
			{
				if(dati.hasOwnProperty(chiave))
				{
					$('#form-usr').find('input#' + chiave).val(dati[chiave]);
					$('#form-usr select#' + chiave).val(dati[chiave]);
					$('#form-usr select#' + chiave).selectpicker('refresh');
				}
			}
			$('#form-usr #usr_ConfermaPassword').val(dati['usr_Password']);


			// Se utente è amministratore, nascondo la scelta delle macchine in quanto già le controlla tutte
			if(dati['usr_Mansione'] <= 2) {
				$('#form-usr #usr_ElencoRisorse').selectpicker('val', null);
				$('#privilegi-macchine-user').hide();
				$('#privilegi-macchine-admin').show();
			}
			else {
				$('#privilegi-macchine-user').show();
				$('#privilegi-macchine-admin').hide();

				$.post("utenti.php", { azione: "usr-carica-select-risorse" })
				.done(function(dataRisorse) {

					$('#form-usr #usr_ElencoRisorse').html(dataRisorse);
					$('#form-usr #usr_ElencoRisorse').selectpicker('refresh');

					if (dati['ElencoRisorse'] != null) {
						var risorseSelezionate = dati['ElencoRisorse'].split(",");
						$('#form-usr #usr_ElencoRisorse').selectpicker('val', risorseSelezionate);
						$('#form-usr #usr_ElencoRisorse').selectpicker('refresh');
					}
					else {
						$('#form-usr #usr_ElencoRisorse').selectpicker('val', risorseSelezionate);
						$('#form-usr #usr_ElencoRisorse').selectpicker('refresh');
					}
				});
			}



			// aggiungo il campo CodiceCliente come id della modifica
			$('#form-usr input').removeClass('errore');
			$('#form-usr').find('input#usr_azione').val('modifica');
			$('#modal-usr-label').text('MODIFICA UTENTE');

			$("#modal-usr").modal("show");
		});

		return false;

	});


	// UTENTI: SU CAMBIO VALORE DELLA SELECT 'MANSIONE', AGGIORNO IL CONTENUTO DELLA SELECT 'MACCHINE' D
	$('#usr_Mansione').on('change',function(){

		var mansioneUtente = $(this).val();
		var idRiga = $('#usr_IdUtente').val();
		var azione = $('#usr_azione').val();

		// Se azione è di modifica
		if (azione == 'modifica') {

			// Recupero dati utente in oggetto
			$.post("utenti.php", { azione: "recupera-usr", idRiga: idRiga })
			.done(function(data) {

				var dati = JSON.parse(data);

				// Se mansione utente è AMMINISTRATORE, nascondo div relativo alla SELECT delle macchina e mostro quello con messaggio
				if(mansioneUtente <= 2) {
					$('#form-usr #usr_ElencoRisorse').selectpicker('val', null);
					$('#privilegi-macchine-user').hide();
					$('#privilegi-macchine-admin').show();
				}
				else { // Se mansione utente è OPERATORE, visualizzo div relativo alla SELECT delle macchine e offro possibilità di selezionarle
					$('#privilegi-macchine-user').show();
					$('#privilegi-macchine-admin').hide();

					$.post("utenti.php", { azione: "usr-carica-select-risorse" })
					.done(function(dataRisorse) {

						$('#form-usr #usr_ElencoRisorse').html(dataRisorse);
						$('#form-usr #usr_ElencoRisorse').selectpicker('refresh');

					});
				}

			});
		}
		else {

			// Se mansione utente è AMMINISTRATORE, nascondo div relativo alla SELECT delle macchina e mostro quello con messaggio
			if(mansioneUtente <= 2) {
				$('#form-usr #usr_ElencoRisorse').selectpicker('val', null);
				$('#privilegi-macchine-user').hide();
				$('#privilegi-macchine-admin').show();
			}
			else { // Se mansione utente è OPERATORE, visualizzo div relativo alla SELECT delle macchine e offro possibilità di selezionarle
				$('#privilegi-macchine-user').show();
				$('#privilegi-macchine-admin').hide();

				$.post("utenti.php", { azione: "usr-carica-select-risorse" })
				.done(function(dataRisorse) {

					$('#form-usr #usr_ElencoRisorse').html(dataRisorse);
					$('#form-usr #usr_ElencoRisorse').selectpicker('refresh');

				});
			}

		}


	})



	// UTENTI: SALVATAGGIO DA POPUP MODIFICA/INSERIMENTO UTENTE
	$('body').on('click','#salva-entry-usr',function(e){

		e.preventDefault();

		// inizializzo il contatore errori
		var errori = 0;

		// Controllo riempimento campi obbligatori (INPUT)
		$('#form-usr .obbligatorio').each(function(){
			if($(this).val() == "")
			{
				errori++;
				$(this).addClass('errore');
			}
		});

		// Controllo riempimento campi obbligatori (SELECT)
		$('#form-usr .selectpicker').each(function(){
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

			var elencoRisorse = $('#usr_ElencoRisorse').val();

			// salvo i dati
			$.post("utenti.php", { azione: "salva-usr", data: $('#form-usr').serialize(), elencoRisorse: elencoRisorse})
			.done(function(data) {


				// se è tutto OK
				if(data == "OK")
				{
					$("#modal-usr").modal("hide");

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