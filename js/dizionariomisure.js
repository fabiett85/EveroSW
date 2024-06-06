(function ($) {
	'use strict';
	var g_idRisorsa = '%';

	var tabellaDatiMisure;
	var linguaItaliana = {
		processing: 'Caricamento...',
		search: 'Ricerca: ',
		lengthMenu: '_MENU_ righe per pagina',
		zeroRecords: 'Nessun record presente',
		info: 'Pagina _PAGE_ di _PAGES_',
		infoEmpty: 'Nessun dato disponibile',
		infoFiltered: '(filtrate da _MAX_ righe totali)',
		paginate: {
			first: 'Prima',
			last: 'Ultima',
			next: 'Prossima',
			previous: 'Precedente',
		},
	};

	// FUNZIONE: RECUPERA ELENCO MISURE
	function visualizzaElencoEventi(idRisorsa) {
		// Visualizzo elenco delle misure previste
		tabellaDatiMisure.ajax.url(
			'dizionariomisure.php?azione=mostra&idRisorsa=' + idRisorsa
		);
		tabellaDatiMisure.ajax.reload();
	}

	//VISUALIZZA DATATABLE RISORSE
	$(function () {
		//VALORE DEFAULT SELECTPICKER
		$('.selectpicker').selectpicker({
			noneSelectedText: 'Seleziona...',
		});

		tabellaDatiMisure = $('#tabellaDati-misure').DataTable({
			order: [
				[0, 'asc'],
				[1, 'asc'],
			],
			aLengthMenu: [
				[10, 20, 30, 50, 100, -1],
				[10, 20, 30, 50, 100, 'Tutti'],
			],
			iDisplayLength: 10,

			columns: [
				{ data: 'DescrizioneRisorsa' },
				{ data: 'IdMisura' },
				{ data: 'DescrizioneMisura' },
				{ data: 'UdmMisura' },
				{ data: 'AbiLetturaIstantanea' },
				{ data: 'AbiTracciamento' },
				{ data: 'azioni' },
			],
			columnDefs: [
				{
					targets: [6],
					className: 'center-bolded',
				},
				{
					width: '5%',
					targets: [6],
				},
				{
					width: '10%',
					targets: [3, 4, 5],
				},
				{
					visible: false,
					targets: [],
				},
			],
			language: linguaItaliana,
		});

		// DIZIONARIO MISURE: VISUALIZZA TABELLA
		visualizzaElencoEventi(g_idRisorsa);
	});

	// DIZIONARIO MISURE: CANCELLAZIONE SEGNALAZIONE DI CAMPO VUOTO
	$('#form-caso input').on('blur', function () {
		$(this).removeClass('errore');
	});

	// DIZIONARIO MISURE: SU CAMBIO SELECT DI FILTRO (ID RISORSA) VISUALIZZAZIONE ELENCO CASI
	$('#mis_FiltroRisorse').on('change', function () {
		g_idRisorsa = $(this).val();

		visualizzaElencoEventi(g_idRisorsa);
	});

	// DIZIONARIO MISURE: CARICAMENTO POPUP PER MODIFICA MISURA
	$('body').on('click', 'a.modifica-misura', function (e) {
		e.preventDefault();

		$('#form-misura input').removeClass('errore');

		$('#form-misura')[0].reset();

		var idRiga = $(this).data('id_riga');

		$.post('dizionariomisure.php', {
			azione: 'recupera',
			codice: idRiga,
		}).done(function (data) {
			var dati = JSON.parse(data);

			// Valorizzo popup con i dati recuperati
			for (var chiave in dati) {
				if (dati.hasOwnProperty(chiave)) {
					$('#form-misura')
						.find('input#' + chiave)
						.val(dati[chiave]);
					$('#form-misura select#' + chiave).val(dati[chiave]);
					$('#form-misura select#' + chiave).selectpicker('refresh');
				}
			}

			// Tratto separatamente la valorizzazione delle checkbox:
			// 'abilitazione lettura istantanea'
			if (dati['mis_AbiLetturaIstantanea'] == 1) {
				$('#form-misura')
					.find('input#mis_AbiLetturaIstantanea')
					.prop('checked', true);
			} else {
				$('#form-misura')
					.find('input#mis_AbiLetturaIstantanea')
					.prop('checked', false);
			}

			// 'abilitazione tracciamento misura'
			if (dati['mis_AbiTracciamento'] == 1) {
				$('#form-misura')
					.find('input#mis_AbiTracciamento')
					.prop('checked', true);
			} else {
				$('#form-misura')
					.find('input#mis_AbiTracciamento')
					.prop('checked', false);
			}

			// Imposto eventuali disabilitazioni
			$('#form-misura #mis_IdMisura').attr('disabled', true);
			$('#form-misura #mis_IdRisorsa').attr('disabled', true);

			$('#form-misura').find('input#azione').val('modifica');
			$('#modal-misura-label').text('MODIFICA MISURA');
			$('#modal-misura').modal('show');
		});

		return false;
	});

	// DIZIONARIO MISURE: SALVATAGGIO DA POPUP MODIFICA/INSERIMENTO MISURA
	$('body').on('click', '#salva-misura', function (e) {
		e.preventDefault();

		// inizializzo il contatore errori
		var errori = 0;

		// Controllo riempimento campi obbligatori (INPUT)
		$('#form-misura .obbligatorio').each(function () {
			if ($(this).val() == '') {
				errori++;
				$(this).addClass('errore');
			}
		});

		// Controllo riempimento campi obbligatori (SELECT)
		$('#form-misura .selectpicker').each(function () {
			if ($(this).val() == null) {
				errori++;
				$(this).addClass('errore');
			}
		});

		// se ho anche solo un errore mi fermo qui
		if (errori > 0) {
			swal({
				title: 'Attenzione',
				text: 'Compilare tutti i campi obbligatori contrassegnati con *',
				icon: 'warning',
				button: 'Ho capito',
				closeModal: true,
			});
		} // nessun errore, posso continuare
		else {
			var flagAbiLetturaIstantanea;
			var flagAbiTracciamento;

			// Tratto separatamente la valorizzazione delle checkbox:
			// 'abilitazione lettura istantanea'
			if ($('#mis_AbiLetturaIstantanea').is(':checked')) {
				flagAbiLetturaIstantanea = 1;
			} else {
				flagAbiLetturaIstantanea = 0;
			}
			// 'abilitazione tracciamento misura'
			if ($('#mis_AbiTracciamento').is(':checked')) {
				flagAbiTracciamento = 1;
			} else {
				flagAbiTracciamento = 0;
			}

			// salvo i dati
			$.post('dizionariomisure.php', {
				azione: 'salva-misura',
				data: $('#form-misura').serialize(),
				flagAbiLetturaIstantanea: flagAbiLetturaIstantanea,
				flagAbiTracciamento: flagAbiTracciamento,
			}).done(function (data) {
				// se è tutto OK
				if (data == 'OK') {
					$('#modal-misura').modal('hide');

					// ricarico la datatable per vedere le modifiche
					visualizzaElencoEventi(g_idRisorsa);
				} // restituisco un errore senza chiudere la modale
				else {
					swal({
						title: 'Operazione non eseguita.',
						text: data,
						icon: 'warning',
						button: 'Ho capito',
						closeModal: true,
					});
				}
			});
		}

		return false;
	});
})(jQuery);
