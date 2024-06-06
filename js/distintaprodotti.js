(function ($) {
	'use strict';

	var tabellaDatiDPC;

	var g_IdProdotto;
	var g_nomeProdotto;

	var linguaItaliana = {
		processing: 'Caricamento...',
		search: 'Ricerca',
		lengthMenu: '_MENU_ righe per pagina',
		zeroRecords: 'Distinta base non compilata per il prodotto selezionato.',
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

	function mostraDistinta() {
		$.post('distintaprodotti.php', { azione: 'recuperaP', idProdotto: g_IdProdotto }).done(
			function (data) {
				if (data != 'NO_ROWS') {
					var dati = JSON.parse(data);

					$('#dp_DescrizioneProdottoSelezionato').val(dati['prd_Descrizione']);
					$('#dp_TempoTeoricoProdottoSelezionato').val(dati['prd_TempoTeorico']);

					tabellaDatiDPC.ajax.url(
						'distintaprodotti.php?azione=mostra-dpc&idProdotto=' + g_IdProdotto
					);
					tabellaDatiDPC.ajax.reload();
				} else {
					$('#aggiungi-componente').prop('disabled', true);
				}
			}
		);
	}

	//Visualizzazione tabella CORPO DISTINTA RISORSE
	$(function () {
		//VALORE DEFAULT SELECTPICKER
		$('.selectpicker').selectpicker({
			noneSelectedText: 'Seleziona...',
		});

		tabellaDatiDPC = $('#tabellaDati-DPC').DataTable({
			order: [[0, 'asc']],
			aLengthMenu: [
				[10, 25, 50, 100, -1],
				[10, 25, 50, 100, 'Tutti'],
			],
			iDisplayLength: 25,
			columns: [
				{ data: 'Componente' },
				{ data: 'CodiceComponente' },
				{ data: 'UnitaDiMisura' },
				{ data: 'FattoreMoltiplicativo' },
				{ data: 'PezziConfezione' },
				{ data: 'azioni' },
			],
			columnDefs: [
				{
					targets: [5],
					width: '5%',
					className: 'center-bolded',
				},
			],
			language: linguaItaliana,
			dom: "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4'l><'col-sm-8'p>>",
		});

		// D. COMPONENTI POST REFRESH: IMPOSTO IL PULSANTE DI NUOVA DISTINTA COME DISABILITATO
		$('#nuova-DPC').prop('disabled', true);

		// D. COMPONENTI POST REFRESH: RECUPERO E SETTO CORRETTAMENTE I VALORI DELLA SELECT DI FILTRO
		if (sessionStorage.getItem('dp_idProdotto') === null) {
			g_nomeProdotto = $('#dp_ProdottoSelezionato option:selected').text();
			g_IdProdotto = $('#dp_ProdottoSelezionato option:selected').val();
		} else {
			g_IdProdotto = sessionStorage.getItem('dp_idProdotto');
			g_nomeProdotto = sessionStorage.getItem('dp_nomeProdotto');
		}
		$('#dp_ProdottoSelezionato').val(g_IdProdotto);
		$('#dp_ProdottoSelezionato').selectpicker('refresh');

		// D. COMPONENTI POST REFRESH: VISUALIZZAZIONE DETTAGLIO DISTINTA
		mostraDistinta();
	});

	// D. COMPONENTI: RIMUOVO BORDO DI ERRORE SU SELEZIONE CAMPO
	$('#form-nuovo-componente input').on('blur', function () {
		$(this).removeClass('errore');
	});

	// D. COMPONENTI: CAMBIO SU SELECT PRODOTTO: VISUALIZZAZIONE DETTAGLIO DISTINTA
	$('#dp_ProdottoSelezionato').on('change', function () {
		g_IdProdotto = $(this).val();
		g_nomeProdotto = $('#dp_ProdottoSelezionato option:selected').text();
		sessionStorage.setItem('dp_idProdotto', g_IdProdotto);
		sessionStorage.setItem('dp_nomeProdotto', g_nomeProdotto);

		mostraDistinta();
	});

	// D. COMPONENTI: VISUALIZZAZONE POPUP 'AGGIUNTA NUOVO ELEMENTO A DISTINTA'
	$('#aggiungi-componente').on('click', function () {
		$('#form-nuovo-componente input').removeClass('errore');

		$('#form-nuovo-componente')[0].reset();
		$('#form-nuovo-componente #dpc_Prodotto').val(g_IdProdotto);
		$('#form-nuovo-componente #dpc_NomeProdotto').val(g_nomeProdotto);
		$('#form-nuovo-componente #dpc_Udm').val('default');
		$('#form-nuovo-componente #dpc_Udm').selectpicker('refresh');
		$('#form-nuovo-componente #dpc_FattoreMoltiplicativo').val(1);
		$('#form-nuovo-componente #dpc_PezziConfezione').val(1);

		//Recupero i componenti disponibili e non ancora aggiunti alla distinta, per popolare la relativa SELECT
		$.post('distintaprodotti.php', {
			azione: 'caricaSelectComponenti',
			idProdotto: g_IdProdotto,
		}).done(function (data) {
			if (data == 'NO_CMP') {
				$('#form-nuovo-componente #dpc_Componente').html(
					'<option value="">Nessun componente disponibile</option>'
				);
				$('#form-nuovo-componente #dpc_Componente').prop('disabled', true);
				$('#form-nuovo-componente #dpc_Componente').selectpicker('refresh');
				$('#salva-dpc').prop('disabled', true);
			} else {
				$('#form-nuovo-componente #dpc_Componente').html(data);
				$('#form-nuovo-componente #dpc_Componente').val('default');
				$('#form-nuovo-componente #dpc_Componente').prop('disabled', false);
				$('#form-nuovo-componente #dpc_Componente').selectpicker('refresh');
				$('#salva-dpc').prop('disabled', false);
			}
		});

		$('#form-nuovo-componente').find('input#dpc_Azione').val('nuovo');
		$('#modal-nuovo-componente-label').text('AGGIUNTA COMPONENTE DISTINTA');
		$('#modal-nuovo-componente').modal('show');
	});

	// SU CAMBIO PRODOTTO SELEZIONATO, RECUPERO LA RELATIVA UNITA' DI MISURA E IMPOSTO LA MEDESIMA ANCHE PER LA COMMESSA IN OGGETTO
	$('#dpc_Componente').on('change', function () {
		var idProdotto = $(this).val();

		$.post('utilities.php', { azione: 'recupera-udm', codice: idProdotto }).done(function (data) {
			$('#form-nuovo-componente #dpc_Udm').val(data);
			$('#form-nuovo-componente #dpc_Udm').selectpicker('refresh');
		});
	});

	// D. COMPONENTI: VISUALIZZAZIONE POPUP 'MODIFICA ELEMENTO DISTINTA'
	$('body').on('click', 'a.modifica-dpc', function (e) {
		e.preventDefault();

		$('#form-nuovo-componente input').removeClass('errore');

		$('#form-nuovo-componente')[0].reset();

		// Valorizzazione campi popup
		var idComponente = $(this).data('id-componente');
		var idProdottoFinito = $(this).data('id-prodotto');
		$('#form-nuovo-componente #dpc_NomeProdotto').val(g_nomeProdotto);

		// Invoco il metodo per recuperare i valori per la riga selezionata
		$.post('distintaprodotti.php', {
			azione: 'recupera-dpc',
			idComponente: idComponente,
			idProdotto: idProdottoFinito,
		}).done(function (data) {
			var dati = JSON.parse(data);

			// Popolo il popup con i dati recuperati
			for (var chiave in dati) {
				if (dati.hasOwnProperty(chiave)) {
					$('#form-nuovo-componente')
						.find('input#' + chiave)
						.val(dati[chiave]);
					$('#form-nuovo-componente select#' + chiave).val(dati[chiave]);
					$('#form-nuovo-componente select#' + chiave).selectpicker('refresh');
				}
			}

			// Tratto separatamente il popolamento della select 'componenti'
			$.post('distintaprodotti.php', {
				azione: 'caricaSelectComponenti',
				idProdotto: idProdottoFinito,
				componente: idComponente,
			}).done(function (data) {
				$('#form-nuovo-componente #dpc_Componente').html(data);
				$('#form-nuovo-componente #dpc_Componente').prop('disabled', true);
				$('#form-nuovo-componente #dpc_Componente').selectpicker('refresh');
			});

			$('#salva-dpc').prop('disabled', false);

			$('#form-nuovo-componente').find('input#dpc_Azione').val('modifica');
			$('#modal-nuovo-componente-label').text('MODIFICA COMPONENTE DISTINTA');
			$('#modal-nuovo-componente').modal('show');
		});

		return false;
	});

	// D. COMPONENTI: SALVATAGGIO DA POPUP MODIFICA/INSERIMENTO ELEMENTO DISTINTA
	$('body').on('click', '#salva-dpc', function (e) {
		e.preventDefault();

		// inizializzo il contatore errori
		var errori = 0;

		// Controllo riempimento campi obbligatori (INPUT)
		$('#form-nuovo-componente .obbligatorio').each(function () {
			if ($(this).val() == '') {
				errori++;
				$(this).addClass('errore');
			}
		});

		// Controllo riempimento campi obbligatori (SELECT)
		$('#form-nuovo-componente .selectpicker').each(function () {
			if ($(this).val() == null) {
				errori++;
				$(this).addClass('errore');
			}
		});

		// se ho anche solo un errore mi fermo qui
		if (errori > 0) {
			swal({
				title: 'ATTENZIONE!',
				text: 'Compilare tutti i campi obbligatori contrassegnati con *',
				icon: 'warning',
				button: 'Ho capito',

				closeModal: true,
			});
		} // nessun errore, posso continuare
		else {
			// PER PERMETTERE SERIALIZE DEL FORM: riabilito le caselle contenenti 'id prodotto' e 'numero riga'
			$('#form-nuovo-componente #dpc_Componente').prop('disabled', false);

			// salvo i dati
			$.post('distintaprodotti.php', {
				azione: 'salva-dpc',
				data: $('#form-nuovo-componente').serialize(),
			}).done(function (data) {
				// se è tutto OK
				if (data == 'OK') {
					$('#modal-nuovo-componente').modal('hide');

					// ricarico la datatable per vedere le modifiche
					tabellaDatiDPC.ajax.reload();
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

			//DOPO SERIALIZE DEL FORM: disabilito nuovamente le caselle contenenti 'id prodotto' e 'numero riga'
			$('#form-nuovo-componente #dpc_Componente').prop('disabled', true);
		}

		return false;
	});

	// D. COMPONENTI: CANCELLAZIONE ELEMENTO DA DISTINTA
	$('body').on('click', 'a.cancella-dpc', function (e) {
		e.preventDefault();

		var idComponente = $(this).data('id-componente');
		var idProdottoFinito = $(this).data('id-prodotto');

		swal({
			title: 'Attenzione',
			text: "Confermi di voler eliminare il componente in oggetto dalla distinta? L'eliminazione è irreversibile.",
			icon: 'warning',
			showCancelButton: true,
			buttons: {
				cancel: {
					text: 'Annulla',
					value: null,
					visible: true,
					className: 'btn btn-danger',
					closeModal: true,
				},
				confirm: {
					text: 'Sì, elimina',
					value: true,
					visible: true,
					className: 'btn btn-primary',
					closeModal: true,
				},
			},
		}).then((procedi) => {
			if (procedi) {
				$.post('distintaprodotti.php', {
					azione: 'cancella-dpc',
					idComponente: idComponente,
					idProdotto: idProdottoFinito,
				}).done(function (data) {
					// chiudo
					swal.close();

					// ricarico la datatable per vedere le modifiche
					tabellaDatiDPC.ajax.reload();

				});
			} else {
				swal.close();
			}
		});
		return false;
	});
})(jQuery);
