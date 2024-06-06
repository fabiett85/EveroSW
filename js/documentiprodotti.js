(($) => {
	var tabellaFiles;
	var url = 'documentiprodotti.php';
	var idProdotto = '%';

	function aggiornaTabellaFiles() {
		tabellaFiles.ajax.url(url + '?azione=mostra&idProdotto=' + idProdotto);
		tabellaFiles.ajax.reload();
	}

	$(() => {
		bsCustomFileInput.init();

		$('.selectpicker').selectpicker({
			noneSelectedText: 'Seleziona...',
		});

		tabellaFiles = $('#tabellaFiles').DataTable({
			aLengthMenu: [
				[8, 16, 24, 100, -1],
				[8, 16, 24, 100, 'Tutti'],
			],
			ajax: {
				url: url + '?azione=mostra&idProdotto=' + idProdotto,
				dataSrc: '',
			},

			iDisplayLength: 8,
			columns: [
				{ data: 'dp_IdProdotto' },
				{ data: 'prd_Descrizione' },
				{ data: 'dp_NomeFile' },
				{ data: 'dp_Descrizione' },
				{ data: 'azioni' },
			],
			columnDefs: [
				{
					targets: [4],
					className: 'center-bolded',
				},
			],
			autoWidth: false,
			language: linguaItaliana,
		});
	});

	$('#carica-file').click(function () {
		$('#form-file').removeClass('was-validated');
		$('#form-file')[0].reset();
		$('#dp_IdProdotto').val($('#prd_IdProdotto').val());
		$('.selectpicker').selectpicker('refresh');
		$('#modal-carica-file').modal('show');
	});

	$('#salva-file').click(function () {
		if ($('#form-file')[0].checkValidity()) {
			var file_data = $('#file').prop('files')[0];
			var form_data = new FormData($('#form-file')[0]);
			form_data.append('file', file_data);
			form_data.append('azione', 'carica-file');

			$.ajax({
				url: url, // <-- point to server-side PHP script
				dataType: 'text', // <-- what to expect back from the PHP script, if anything
				cache: false,
				contentType: false,
				processData: false,
				data: form_data,
				type: 'post',
				success: (data) => {
					if (data == 'OK') {
						$('#modal-carica-file').modal('hide');
						aggiornaTabellaFiles();
					} else if (data == 'FILE_PRESENTE') {
						swal({
							title: 'ATTENZIONE!',
							text: `Il file è già presente sul server con lo stesso nome.
								Controlla di non aver già il file nella lista
								o rinomina il file prima di ritentare l'upload`,
							icon: 'warning',
						});
					} else {
						console.log(data);
						swal({
							title: 'ERRORE!',
							text: 'Il file non è stato caricato correttamente.',
							icon: 'error',
						});
					}
				},
			});
		} else {
			$('#form-file').addClass('was-validated');
			swal({
				title: 'ATTENZIONE!',
				text: 'Compilare tutti i campi richiesti.',
				icon: 'warning',
			});
		}
	});

	$('#prd_IdProdotto').change(function () {
		idProdotto = $(this).val();
		aggiornaTabellaFiles();
	});

	$('body').on('click', '.elimina-file', function () {
		var riga = tabellaFiles.row($(this).parents('tr')).data();
		swal({
			title: 'Attenzione',
			text:
				'Confermi di voler eliminare il file ' +
				riga.dp_NomeFile +
				' del prodotto ' +
				riga.prd_Descrizione +
				"? L'eliminazione è irreversibile.",
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
				$.post(url, {
					azione: 'elimina-file',
					dp_IdProdotto: riga.dp_IdProdotto,
					dp_NomeFile: riga.dp_NomeFile
				}).done((data)=>{
					if (data == 'OK') {
						aggiornaTabellaFiles();
					} else {
						console.log(data);
						swal({
							title: 'ERRORE!',
							text: 'Il file non è stato eliminato.',
							icon: 'error',
						});
					}
				});
			}
		});


	});
})(jQuery);
