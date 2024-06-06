(function ($) {
	var today = moment();
	var tabellaComponenti;
	var url = 'visualizzapianocommesse.php';

	$(function () {
		var tabellaComponenti = $('#tabellaComponenti').DataTable({
			aLengthMenu: [
				[5, 10, 20, 50, 100, -1],
				[5, 10, 20, 50, 100, 'Tutti'],
			],
			autoWidth: false,
			iDisplayLength: -1,
			columns: [
				{ data: 'op_IdProduzione' },
				{ data: 'lp_Descrizione' },
				{ data: 'ProdottoFinito' },
				{ data: 'op_QtaDaProdurre' },
				{ data: 'DataOraProgrammazione' },
				{ data: 'DataOraFinePrevista' },
				{ data: 'op_Lotto' },
				{ data: 'op_Priorita' },
				{ data: 'so_Descrizione' },
				{ data: 'Componente' },
				{ data: 'cmp_Qta' },
				{ data: 'Mancanti' },
			],
			columnDefs: [
				{
					targets: [0, 1, 2, 3, 4, 5, 6, 7, 8],
					visible: false,
				},
			],

			language: linguaItaliana,
			sorting: false,
			paging: false,
			ajax: {
				url: url + '?azione=componenti',
				dataSrc: '',
			},
			drawCallback: function (settings) {
				var api = this.api();
				var rows = api.rows({ page: 'current' }).nodes();
				var last = null;
				$('#tabellaComponenti thead').prop('hidden', true);

				api.column(0, { page: 'current' })
					.data()
					.each(function (group, i) {
						if (last !== group) {
							var riga = tabellaComponenti
								.rows({ order: 'current', page: 'all', search: 'applied' })
								.data()[i];
							$(rows)
								.eq(i)
								.before(
									`
										<tr class="head">
											<td colspan="3" style="background-color:white"></td>
										</tr>` +
										'<tr class="group-' +
										riga.so_Descrizione +
										'"><td colspan="3"><div class="row">' +
										'<div class="col-2 d-flex align-items-center">COMMESSA: ' +
										group +
										'</div>' +
										'<div class="col-2 d-flex align-items-center">LINEA: ' +
										riga.lp_Descrizione +
										'</div>' +
										'<div class="col-3 d-flex align-items-center">PRODOTTO FINITO: ' +
										riga.ProdottoFinito +
										'</div>' +
										'<div class="col-1 d-flex align-items-center">QTA: ' +
										riga.op_QtaDaProdurre +
										'</div>' +
										'<div class="col-2 d-flex align-items-center">DATA: ' +
										riga.DataOraProgrammazione +
										'</div>' +
										'<div class="col-2 d-flex align-items-center">LOTTO: ' +
										riga.op_Lotto +
										'</div>' +
										'</td></tr>' +
										`
										<tr class="head">
											<th>Componente</th>
											<th>Fabbisogno</th>
											<th>Mancanti</th>
										</tr>`
								);

							last = group;
						}
					});
			},
			buttons: [
				{
					extend: 'excel',
					text: 'EXCEL',
					className: 'btn-success',
					filename: 'ElencoCommesse_' + today.format('YYYYMMDD'),
					title: 'ELENCO COMMESSE AL ' + today.format('DD/MM/YYYY'),
				},
				{
					extend: 'pdf',
					text: 'PDF',
					className: 'btn-danger',
					filename: 'ElencoCommesse_' + today.format('YYYYMMDD'),
					title: 'ELENCO COMMESSE AL ' + today.format('DD/MM/YYYY'),
					orientation: 'landscape',
					download: 'open',
				},
			],
			dom:
				"<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
				"<'row'<'col-sm-12'tr>>" +
				"<'row'<'col-sm-12 col-md-6 d-flex justify-content-left align-items-center'iB><'col-sm-12 col-md-6'p>>",
		});

		setInterval(() => {
			reloadDataGantt();
			tabellaComponenti.ajax.reload();
		}, 20000);
	});
})(jQuery);
