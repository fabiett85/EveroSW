(function ($) {
	'use strict';

	var tabellaDati;
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

	$(function () {
		//VALORE DEFAULT SELECTPICKER
		$('.selectpicker').selectpicker({
			noneSelectedText: 'Seleziona...',
		});

		tabellaDati = $('#tabellaDati-prodotti').DataTable({
			order: [[2, 'asc']],
			aLengthMenu: [
				[12, 24, 36, 50, 100, -1],
				[12, 24, 36, 50, 100, 'Tutti'],
			],
			iDisplayLength: 50,
			ajax: {
				url: $('#tabellaDati-prodotti').data('source'),
				dataSrc: '',
			},
			columns: [
				{ data: 'IdProdotto' },
				{ data: 'Descrizione' },
				{ data: 'IdTipo' },
				{ data: 'Tipo' },
				{ data: 'IdCategoria' },
				{ data: 'Categoria' },
				{ data: 'IdSottocategoria' },
				{ data: 'Sottocategoria' },
				{ data: 'IdUnitaMisura' },
				{ data: 'UnitaMisura' },
				{ data: 'PezziConfezione' },
				{ data: 'Quantita' },
				{ data: 'azioni' },
			],
			columnDefs: [
				{
					targets: [4,6,11],
					visible: false,
				},
				{
					targets: [12],
					className: 'center-bolded',
					width: '5%',
				},
			],
			language: linguaItaliana,
			buttons: [],
			dom:
				"<'row'<'col-sm-12 col-md-6 d-flex justify-content-left align-items-center'lB><'col-sm-12 col-md-6'f>>" +
				"<'row'<'col-sm-12'tr>>" +
				"<'row'<'col-sm-12 col-md-6'i><'col-sm-12 col-md-6'p>>",
		});
	});

	// PRODOTTI: GESTIONE INSERIMENTO NUOVO PRODOTTO
	$('#nuovo-prodotto').on('click', function () {
		$('#form-prodotti').removeClass('was-validated');
		$('#form-prodotti')[0].reset();

		$('#form-prodotti .selectpicker').val('default');
		$('#form-prodotti #prd_Categoria').val(0);
		$('#form-prodotti .selectpicker').selectpicker('refresh');

		var categoriaSelezionata = $('#prd_Categoria').val();
		$.post('prodotti.php', {
			azione: 'caricaSelectSottocategorie',
			categoria: categoriaSelezionata,
		}).done(function (data) {
			$('#form-prodotti #prd_Sottocategoria').html(data);
			$('#form-prodotti #prd_Sottocategoria').selectpicker('refresh');
		});

		$('#form-prodotti').find('input#azione').val('nuovo');
		$('#modalProdottiLabel').text('INSERIMENTO NUOVO PRODOTTO');
		$('#modalProdotti').modal('show');
	});

	// PRODOTTI: GESTIONE MODIFICA
	$('body').on('click', 'a.modifica-prodotto', function (e) {
		$('#form-prodotti').removeClass('was-validated');
		e.preventDefault();

		var idRiga = $(this).data('id_riga');

		$.post('prodotti.php', { azione: 'recupera', codice: idRiga }).done(function (data) {
			$('#form-prodotti')[0].reset();

			var dati = JSON.parse(data);

			// Visulizzo nel popup i valori recuperati
			for (var chiave in dati) {
				if (dati.hasOwnProperty(chiave)) {
					$('#form-prodotti #' + chiave).val(dati[chiave]);
				}
			}

			//Gestisco a parte i valori selezionati per le due select 'Categoria' e 'Sottocategoria'
			var categoriaSelezionata = dati['prd_Categoria'];
			var sottocategoriaSelezionata = dati['prd_Sottocategoria'];

			$.post('prodotti.php', {
				azione: 'caricaSelectSottocategorie',
				categoria: categoriaSelezionata,
				sottocategoria: sottocategoriaSelezionata,
			}).done(function (data) {
				$('#prd_Sottocategoria').html(data);
			});

			// Aggiungo il campo IdProdotto come id della modifica
			$('#form-prodotti #prd_IdProdotto_Aux').val(dati['prd_IdProdotto']);
			$('#form-prodotti #azione').val('modifica');
			$('#modalProdottiLabel').text('MODIFICA PRODOTTO');
			$('#form-prodotti .selectpicker').selectpicker('refresh');

			$('#modalProdotti').modal('show');
		});

		return false;
	});

	// PRODOTTI: GESTIONE CANCELLAZIONE
	$('body').on('click', 'a.cancella-prodotto', function (e) {
		e.preventDefault();

		var idRiga = $(this).data('id_riga');

		swal({
			title: 'Attenzione',
			text: "Confermi di voler eliminare il prodotto in oggetto? L'eliminazione è irreversibile.",
			icon: 'warning',
			showCancelButton: true,
			buttons: {
				cancel: {
					text: 'ANNULLA',
					value: null,
					visible: true,
					className: 'btn btn-secondary',
				},
				confirm: {
					text: 'CONFERMA',
					value: true,
					visible: true,
					className: 'btn btn-danger',
				},
			},
		}).then((procedi) => {
			if (procedi) {
				$.post('prodotti.php', {
					azione: 'cancella-prodotto',
					id: idRiga,
				}).done(function (data) {
					if (data == 'PRODOTTO_OCCUPATO') {
						swal({
							title: 'ATTENZIONE!',
							text: 'Il prodotto ha ordini pendenti, impossibile procedere.',
							icon: 'warning',
							closeModal: true,
						});
					} else if (data == 'OK') {
						// Ricarico la datatable per vedere le modifiche
						tabellaDati.ajax.reload(null, false);
					} else {
						console.log(data);
						swal({
							title: 'ERRORE!',
							text: "Errore durante l'eliminazione, riprovare.",
							icon: 'error',
						});
					}

					swal.close();
				});
			} else {
				swal.close();
			}
		});
		return false;
	});

	// PRODOTTI: GESTIONE CARICAMENTO SELECT SOTTOCATEGORIE, IN BASE A CATEGORIA SELEZIONATA
	$('#prd_Categoria').on('change', function () {
		var categoriaSelezionata = $(this).children('option:selected').val();
		$.post('prodotti.php', {
			azione: 'caricaSelectSottocategorie',
			categoria: categoriaSelezionata,
		}).done(function (data) {
			$('#prd_Sottocategoria').html(data);
			$('#prd_Sottocategoria').selectpicker('refresh');
		});
	});

	// PRODOTTI: SALVATAGGIO DA POPUP MODIFICA/INSERIMENTO
	$('body').on('click', '#salva-prodotto', function (e) {
		e.preventDefault();
		$('#form-prodotti').addClass('was-validated');
		if (!$('#form-prodotti')[0].checkValidity()) {
			swal({
				title: 'ATTENZIONE!',
				text: 'Compilare tutti i campi obbligatori contrassegnati con *',
				icon: 'warning',
			});
		} // nessun errore, posso continuare
		else {
			// salvo i dati
			$.post('prodotti.php', {
				azione: 'salva-prodotto',
				data: $('#form-prodotti').serialize(),
			}).done(function (data) {
				// se è tutto OK
				if (data == 'OK') {
					$('#modalProdotti').modal('hide');
					// ricarico la datatable per vedere le modifiche
					tabellaDati.ajax.reload(null, false);
				} // restituisco un errore senza chiudere la modale
				else {
					console.log(data);
					swal({
						title: 'ERRORE!',
						text: 'Errore durante il salvataggio del prodotto.',
						icon: 'error',
					});
				}
			});
		}

		return false;
	});

	$('body').on('click', '.importa', function () {
		$('#modal-import').modal('show');
	});

	$('body').on('click', '#importa-prodotti', function () {
		var file = $('#file')[0].files[0];
		var form = document.forms.namedItem('form-importazione');
		var formData = new FormData(form);

		$.ajax({
			url: 'importaprodotti.php',
			type: 'post',
			data: formData,
			contentType: false,
			processData: false,
			cache: false,
			success: function (response) {
				if (response != 0) {
					alert('file uploaded');
				} else {
					alert('file not uploaded');
				}
			},
		});
	});
})(jQuery);
