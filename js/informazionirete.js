(function ($) {
	'use strict';

	var tabellaDatiIndirizzamento;

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
		//VISUALIZZA DATATABLE LINEA
		tabellaDatiIndirizzamento = $('#tabellaDati-indirizzi').DataTable({
			order: [[1, 'asc']],
			aLengthMenu: [
				[10, 25, 50, 100, -1],
				[10, 25, 50, 100, 'Tutti'],
			],
			iDisplayLength: 10,
			ajax: {
				url: $('#tabellaDati-indirizzi').attr('data-source'),
				dataSrc: '',
			},
			columns: [
				{ data: 'IdRisorsa' },
				{ data: 'DescrizioneRisorsa' },
				{ data: 'Indirizzo1Risorsa' },
				{ data: 'NoteRisorsa' },
			],
			columnDefs: [
				{
					width: '15%',
					targets: [0],
				},
				{
					width: '25%',
					targets: [1],
				},
				{
					width: '15%',
					targets: [2],
				},
			],
			language: linguaItaliana,
			paging: false,
			ordering: false,
			info: false,
			//"dom":  "<'row'<'col-sm-6'><'col-sm-6'f>r>" + "t" + "<'row'<'col-sm-4'l><'col-sm-8'p>>"
		});
	});
})(jQuery);
