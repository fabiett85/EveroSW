(function ($) {
	'use strict';

	var url = 'gestionecommesseestesa.php';
	var today = moment();
	var tabellaOrdini;
	var tabellaDistintaRisorse;
	var tabellaDistintaComponenti;
	var tabellaDistintaConsumi;
	var indiceRigaModifica;
	var modOn = false;
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

	function aggiornaFine() {
		var dataOrdine = $('#form-dati-ordine #op_DataProduzione').val();
		var momentOrdine = moment(dataOrdine);
		var qta = $('#form-dati-ordine #op_QtaDaProdurre').val();
		var vel = $('#form-dati-ordine #vel_VelocitaTeoricaLinea').val();

		if (qta && vel && dataOrdine && vel != 0) {
			var ore = qta / vel;
			var minuti = (ore % 1) * 60;
			var secondi = (minuti % 1) * 60;
			ore = Math.floor(ore);
			minuti = Math.floor(minuti);
			secondi = Math.floor(secondi);
			momentOrdine.add(secondi, 's');
			momentOrdine.add(minuti, 'm');
			momentOrdine.add(ore, 'h');
			$('#form-dati-ordine #op_DataFine').val(momentOrdine.format('YYYY-MM-DDTHH:mm'));
		}
	}

	function mostraOrdini() {
		tabellaOrdini.ajax.url(url + '?azione=mostra&filtro=' + $('#filtro-ordini').val());
		tabellaOrdini.ajax.reload();
		reloadDataGantt();
	}

	function mostraOrdine(idProduzione) {
		$.post(url, {
			azione: 'mostra-ordine',
			idProduzione: idProduzione,
		}).done(function (data) {
			try {
				var dati = JSON.parse(data);

				$('#form-dati-ordine')[0].reset();
				$('#form-dati-ordine .selectpicker').val('default');
				$('#form-dati-ordine .selectpicker').selectpicker('refresh');
				$('#nuova-commessa').prop('hidden', true);
				$('#annulla-modifica-ordine').prop('hidden', false);
				$('#conferma-modifica-ordine').prop('hidden', false);
				$('#aggiungi-risorsa-ordine').prop('hidden', false);
				$('.multi-collapse').collapse('toggle');

				var ordine = dati['ordine'];

				for (const key in ordine) {
					if (Object.hasOwnProperty.call(ordine, key)) {
						const element = ordine[key];
						$('#form-dati-ordine #' + key).val(element);
					}
				}
				var dataOrdine = new moment(ordine['op_DataOrdine'] + 'T' + ordine['op_OraOrdine']);
				$('#form-dati-ordine #op_DataOrdine').val(dataOrdine.format('YYYY-MM-DDTHH:mm'));
				var dataProduzione = new moment(
					ordine['op_DataProduzione'] + 'T' + ordine['op_OraProduzione']
				);
				$('#form-dati-ordine #op_DataProduzione').val(
					dataProduzione.format('YYYY-MM-DDTHH:mm')
				);
				var dataFine = new moment(
					ordine['op_DataFineTeorica'] + 'T' + ordine['op_OraFineTeorica']
				);
				$('#form-dati-ordine #op_DataFine').val(dataFine.format('YYYY-MM-DDTHH:mm'));
				$('#form-dati-ordine .selectpicker').selectpicker('refresh');
				var dataFine = $('#form-dati-ordine #op_DataFine').val();
				if (!dataFine) {
					aggiornaFine();
				}
				tabellaDistintaRisorse.clear();
				tabellaDistintaRisorse.rows.add(dati['distintaRisorse']);
				tabellaDistintaRisorse.draw();
				tabellaDistintaComponenti.clear();
				tabellaDistintaComponenti.rows.add(dati['distintaComponenti']);
				tabellaDistintaComponenti.draw();
				tabellaDistintaConsumi.clear();
				tabellaDistintaConsumi.rows.add(dati['distintaConsumi']);
				tabellaDistintaConsumi.draw();

				modOn = true;
			} catch (error) {
				sessionStorage.removeItem('idProduzione');
				sessionStorage.removeItem('tabShown');
				console.error(error);
				console.log(data);
				swal({
					title: 'ERRORE!',
					text: "Errore nel reperimento dei dati dell'ordine " + idProduzione + '.',
					icon: 'error',
				});
			}
		});
	}

	function extractSigla(stringaUdm) {
		return stringaUdm.split(' ')[1].replace('(', '').replace(')', '');
	}

	function gestionePulsanti() {
		var idTab = $('#tab-distinte a.active').attr('id');

		if (idTab == 'tab-risorse') {
			$('#aggiungi-risorsa-ordine').prop('hidden', false);
			$('#aggiungi-componente-ordine').prop('hidden', true);
			$('#aggiungi-consumo-ordine').prop('hidden', true);
		} else if (idTab == 'tab-componenti') {
			$('#aggiungi-risorsa-ordine').prop('hidden', true);
			$('#aggiungi-componente-ordine').prop('hidden', false);
			$('#aggiungi-consumo-ordine').prop('hidden', true);
		} else if (idTab == 'tab-consumi') {
			$('#aggiungi-risorsa-ordine').prop('hidden', true);
			$('#aggiungi-componente-ordine').prop('hidden', true);
			$('#aggiungi-consumo-ordine').prop('hidden', false);
		}
	}

	//INIZIALIZZAZIONE PAGINA
	$(function () {
		$('.selectpicker').selectpicker({
			noneSelectedText: 'Seleziona...',
		});
		$.fn.dataTable.moment('DD/MM/YYYY - HH:mm');

		tabellaOrdini = $('#tabellaOrdini').DataTable({
			aLengthMenu: [
				[5, 10, 20, 50, 100, -1],
				[5, 10, 20, 50, 100, 'Tutti'],
			],
			iDisplayLength: 5,
			columns: [
				{ data: 'op_IdProduzione' },
				{ data: 'lp_Descrizione' },
				{ data: 'prd_Descrizione' },
				{ data: 'op_QtaRichiesta' },
				{ data: 'op_QtaDaProdurre' },
				{ data: 'DataOraProgrammazione' },
				{ data: 'DataOraFinePrevista' },
				{ data: 'op_Lotto' },
				{ data: 'op_Priorita' },
				{ data: 'so_Descrizione' },
				{ data: 'azioni' },
			],
			columnDefs: [
				{
					targets: [10],
					className: 'center-bolded',
				},
				{
					width: '15%',
					targets: [1, 2],
				},
				{
					width: '10%',
					targets: [0, 5, 6],
				},
				{
					width: '5%',
					targets: [7, 8, 9, 10],
				},
				{
					width: '7%',
					targets: [4, 3],
				},
			],

			language: linguaItaliana,

			drawCallback: function (settings) {
				var api = this.api();
				api.rows().every(function (rowIdx, tableLoop, rowLoop) {
					var idRiga = this.node();
					var data = this.data();
					var statoLinea = this.data()['so_Descrizione'];

					if (statoLinea == 'OK') {
						$(idRiga).css('background-color', 'rgba(0, 255, 0, 0.8)');
					} else if (statoLinea == 'CHIUSO') {
						$(idRiga).css('background-color', 'rgba(101, 108, 108, 0.2)');
					} else if (statoLinea == 'ATTIVO') {
						$(idRiga).css('background-color', 'rgba(102, 204, 255, 0.7)');
					} else if (statoLinea == 'CARICATO') {
						$(idRiga).css('background-color', 'rgba(102, 204, 255, 0.7)');
					} else if (statoLinea == 'MANUTENZIONE') {
						$(idRiga).css('background-color', 'rgba(102, 0, 204, 0.5)');
					} else if (statoLinea == 'MEMO') {
						$(idRiga).css('background-color', '#ffffff');
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
					exportOptions: {
						columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
					},
				},
				{
					extend: 'pdf',
					text: 'PDF',
					className: 'btn-danger',
					filename: 'ElencoCommesse_' + today.format('YYYYMMDD'),
					title: 'ELENCO COMMESSE AL ' + today.format('DD/MM/YYYY'),
					orientation: 'landscape',
					download: 'open',
					exportOptions: {
						columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
					},
				},
			],
			dom:
				"<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
				"<'row'<'col-sm-12'tr>>" +
				"<'row'<'col-sm-12 col-md-6 d-flex justify-content-left align-items-center'iB><'col-sm-12 col-md-6'p>>",
		});

		tabellaDistintaRisorse = $('#tabellaDistintaRisorse').DataTable({
			aLengthMenu: [
				[8, 16, 24, 100, -1],
				[8, 16, 24, 100, 'Tutti'],
			],
			iDisplayLength: 8,
			order: [6, 'asc'],

			columns: [
				{ data: 'rc_IdRisorsa' },
				{ data: 'ris_Descrizione' },
				{ data: 'ricm_Descrizione' },
				{ data: 'rc_VelocitaRisorsa' },
				{ data: 'rc_DataOraInizio' },
				{ data: 'rc_DataOraFine' },
				{ data: 'rc_NoteIniziali' },
				{ data: 'RegistraMisure' },
				{ data: 'FlagUltima' },
				{ data: 'ris_Ordinamento' },
				{ data: 'azioni' },
				{ data: 'rc_IdProduzione' },
				{ data: 'rc_LineaProduzione' },
				{ data: 'rc_RegistraMisure' },
				{ data: 'rc_FlagUltima' },
				{ data: 'rc_IdRicetta' },
			],
			columnDefs: [
				{
					visible: false,
					targets: [11, 12, 13, 14, 15],
				},
				{
					className: 'center-bolded',
					width: '5%',
					targets: [10],
				},
			],
			language: linguaItaliana,
			searching: false,
			autoWidth: false,
			preDrawCallback: function () {
				this.api()
					.rows()
					.every(function () {
						var row = this.data();
						if (row.rc_VelocitaRisorsa) {
							var splitted = row.rc_VelocitaRisorsa.split(' ');
							if (splitted.length < 2) {
								row.rc_VelocitaRisorsa +=
									' [' + $('#form-dati-ordine #op_Udm option:selected').text() + '/h]';
							}
						} else {
							row.rc_VelocitaRisorsa =
								$('#form-dati-ordine #vel_VelocitaTeoricaLinea').val() +
								' [' +
								$('#form-dati-ordine #op_Udm option:selected').text() +
								'/h]';
						}
						if (!row.rc_DataOraInizio) {
							row.rc_DataOraInizio = moment(
								$('#form-dati-ordine #op_DataProduzione').val()
							).format('DD/MM/YYYY - HH:mm');
						}
						if (!row.rc_DataOraFine) {
							row.rc_DataOraFine = moment($('#form-dati-ordine #op_DataFine').val()).format(
								'DD/MM/YYYY - HH:mm'
							);
						}

						this.data(row);
					});
			},
		});

		tabellaDistintaComponenti = $('#tabellaDistintaComponenti').DataTable({
			aLengthMenu: [
				[8, 16, 24, 100, -1],
				[8, 16, 24, 100, 'Tutti'],
			],
			iDisplayLength: 8,
			order: [1, 'asc'],

			columns: [
				{ data: 'cmp_Componente' },
				{ data: 'prd_Descrizione' },
				{ data: 'UdmComponente' },
				{ data: 'cmp_FattoreMoltiplicativo' },
				{ data: 'cmp_PezziConfezione' },
				{ data: 'Fabbisogno' },
				{ data: 'azioni' },
				{ data: 'cmp_IdProduzione' },
				{ data: 'cmp_Qta' },
				{ data: 'cmp_Udm' },
			],
			columnDefs: [
				{
					targets: [6],
					width: '5%',
					className: 'center-bolded',
				},
				{
					targets: [7, 8, 9],
					visible: false,
				},
			],
			autoWidth: false,
			language: linguaItaliana,
			searching: false,
		});

		tabellaDistintaConsumi = $('#tabellaDistintaConsumi').DataTable({
			aLengthMenu: [
				[8, 16, 24, 100, -1],
				[8, 16, 24, 100, 'Tutti'],
			],

			iDisplayLength: 8,
			columns: [
				{ data: 'ris_Descrizione' },
				{ data: 'tc_Descrizione' },
				{ data: 'um_Sigla' },
				{ data: 'con_Rilevato' },
				{ data: 'con_ConsumoPezzoIpotetico' },
				{ data: 'azioni' },
				{ data: 'con_IdProduzione' },
				{ data: 'con_IdRisorsa' },
				{ data: 'con_IdTipoConsumo' },
			],
			columnDefs: [
				{
					targets: [5],
					className: 'center-bolded',
					width: '5%',
				},
				{
					targets: [6, 7, 8],
					visible: false,
				},
			],
			autoWidth: false,
			language: linguaItaliana,
			search: false,
		});

		var filtro = sessionStorage.getItem('filtro-ordini');
		if (filtro) {
			$('#filtro-ordini').val(filtro);
			$('#filtro-ordini').selectpicker('refresh');
		}

		var idProduzione = sessionStorage.getItem('idProduzione');
		if (idProduzione) {
			var tabShown = sessionStorage.getItem('tabShown');

			if (tabShown) {
				$('#tab-distinte.nav-tabs a#' + tabShown).tab('show');
			}
			mostraOrdine(idProduzione);
			gestionePulsanti();
		}

		mostraOrdini();
		$(window).bind('beforeunload', function (e) {
			if (modOn) {
				return '';
			}
		});
	});

	// GESTIONE COMMESSE POST - REFRESH: OPERAZIONI SU SELEZIONE DEL TAB
	$('a[data-toggle="tab"]').on('shown.bs.tab', function () {
		gestionePulsanti();
		sessionStorage.setItem('tabShown', $(this).attr('id'));
	});

	//REFRESH TABELLA
	$('#filtro-ordini').on('change', function () {
		mostraOrdini();
		sessionStorage.setItem('filtro-ordini', $(this).val());
	});

	//ELIMINAZIONE E CREAZIONE ORDINI
	$('body').on('click', '.cancella-commessa', function () {
		var idProduzione = $(this).data('idProduzione');

		$.post(url, {
			azione: 'elimina-produzione',
			idProduzione: idProduzione,
		}).done(function (data) {
			if (data == 'OK') {
				swal({
					title: 'OPERAZIONE EFFETTUATA',
					icon: 'success',
				});
				mostraOrdini();
			} else {
				console.log(data);
				swal({
					title: 'ERRORE!',
					text: 'Eliminazione ordine non riuscita.',
					icon: 'error',
				});
			}
		});
	});

	$('#nuova-commessa').click(function () {
		var now = moment();
		$('#form-nuovo-ordine')[0].reset();
		$('#form-nuovo-ordine #DataOraOrdine').val(now.format('YYYY-MM-DDTHH:mm'));
		$('#form-nuovo-ordine #DataOraProduzione').val(now.format('YYYY-MM-DDTHH:mm'));
		$('#form-nuovo-ordine .selectpicker').val('default');
		$('#form-nuovo-ordine .selectpicker').selectpicker('refresh');
		$('#modal-ordine-produzione').modal('show');
	});

	$('#salva-nuovo-ordine').click(function () {
		if ($('#form-nuovo-ordine')[0].checkValidity()) {
			$('#form-nuovo-ordine').removeClass('was-validated');
			$('#form-nuovo-ordine #op_IdProduzione').removeClass('is-invalid');

			$.post(url, {
				azione: 'nuovo-ordine',
				data: $('#form-nuovo-ordine').serialize(),
			}).done(function (data) {
				if (data == 'OK') {
					swal({
						title: 'OPERAZIONE EFFETTUATA',
						icon: 'success',
					});
					mostraOrdini();
					$('#modal-ordine-produzione').modal('hide');
				} else if (data == 'KO') {
					console.log(data);
					swal({
						title: 'Attenzione!',
						text: 'Il codice commessa inserito è già presente nel sistema.',
						icon: 'warning',
					});
					$('#form-nuovo-ordine #op_IdProduzione').addClass('is-invalid');
				} else {
					console.log(data);
					swal({
						title: 'ERRORE!',
						text: 'Inserimento ordine non riuscito.',
						icon: 'error',
					});
				}
			});
		} else {
			$('#form-nuovo-ordine').addClass('was-validated');
		}
	});

	$('#form-nuovo-ordine #op_Prodotto').on('change', function () {
		var idProdotto = $(this).val();
		$.post(url, {
			azione: 'udm-prodotto',
			idProdotto: idProdotto,
		}).done(function (data) {
			if (!isNaN(data)) {
				var IdUdm = parseInt(data);
				$('#form-nuovo-ordine #op_Udm').val(IdUdm);
				$('#form-nuovo-ordine #op_Udm').selectpicker('refresh');
			} else {
				console.error(data + ' is not a number.');
			}
		});
	});

	//GESTIONE ORDINE
	$('body').on('click', '.gestisci-commessa', function name() {
		var idProduzione = $(this).data('idProduzione');
		sessionStorage.setItem('idProduzione', idProduzione);
		gestionePulsanti();
		mostraOrdine(idProduzione);
	});

	$('#annulla-modifica-ordine').click(function () {
		sessionStorage.removeItem('idProduzione');
		sessionStorage.removeItem('tabShown');
		$('#form-dati-ordine').removeClass('was-validated');
		$('#nuova-commessa').prop('hidden', false);
		$('#annulla-modifica-ordine').prop('hidden', true);
		$('#conferma-modifica-ordine').prop('hidden', true);
		$('#aggiungi-risorsa-ordine').prop('hidden', true);
		$('#aggiungi-componente-ordine').prop('hidden', true);
		$('#aggiungi-consumo-ordine').prop('hidden', true);
		$('.multi-collapse').collapse('toggle');
		modOn = false;
		mostraOrdini();
	});

	$('#form-dati-ordine #op_LineaProduzione').on('change', function () {
		var idProduzione = $('#form-dati-ordine #op_IdProduzione').val();
		var idLineaProduzione = $(this).val();
		var idProdotto = $('#form-dati-ordine #op_Prodotto').val();

		$.post(url, {
			azione: 'dati-linea',
			idProduzione: idProduzione,
			idLineaProduzione: idLineaProduzione,
			idProdotto: idProdotto,
		}).done(function (data) {
			try {
				var dati = JSON.parse(data);
				$('#form-dati-ordine #vel_VelocitaTeoricaLinea').val(dati['velocita']);
				aggiornaFine();
				tabellaDistintaRisorse.clear();
				tabellaDistintaRisorse.rows.add(dati['distintaRisorse']);
				tabellaDistintaRisorse.draw();
			} catch (error) {
				console.error(error);
				console.log(data);
				swal({
					title: 'ERRORE!',
					text: 'Errore nel reperimento dei dati della distinta risorse.',
					icon: 'error',
				});
			}
		});
	});

	$(`
		#form-dati-ordine #vel_VelocitaTeoricaLinea,
		#form-dati-ordine #op_DataProduzione
	`).on('change', function () {
		aggiornaFine();
	});

	$('#form-dati-ordine #op_QtaDaProdurre').on('change', function () {
		aggiornaFine();
		var qtaDaProdurre = parseFloat($(this).val());
		tabellaDistintaComponenti.data().each(function (d) {
			d.Fabbisogno =
				Math.ceil((qtaDaProdurre * d.cmp_FattoreMoltiplicativo) / d.cmp_PezziConfezione) +
				' ' +
				extractSigla(d.UdmComponente);
		});
		tabellaDistintaComponenti.rows().invalidate().draw();
	});

	//SALVATAGGIO MODIFICHE
	$('#conferma-modifica-ordine').click(function () {
		$('#form-dati-ordine').addClass('was-validated');

		if ($('#form-dati-ordine')[0].checkValidity()) {
			var datiForm = $('#form-dati-ordine').serialize();
			var risorseCoinvolte = tabellaDistintaRisorse.rows().data().toArray();
			var componenti = JSON.stringify(tabellaDistintaComponenti.rows().data().toArray());
			var consumi = JSON.stringify(tabellaDistintaConsumi.rows().data().toArray());

			var tmp;
			for (let index = 0; index < risorseCoinvolte.length; index++) {
				if (risorseCoinvolte[index].rc_VelocitaRisorsa) {
					tmp = parseFloat(risorseCoinvolte[index].rc_VelocitaRisorsa.split(' ')[0]);
					risorseCoinvolte[index].rc_VelocitaRisorsa = tmp;
				}
				if (risorseCoinvolte[index].rc_DataOraInizio) {
					tmp = moment(risorseCoinvolte[index].rc_DataOraInizio, 'DD/MM/YYYY - HH:mm').format(
						'YYYY-MM-DDTHH:mm' + ':00'
					);
					risorseCoinvolte[index].rc_DataOraInizio = tmp;
				}
				if (risorseCoinvolte[index].rc_DataOraFine) {
					tmp = moment(risorseCoinvolte[index].rc_DataOraFine, 'DD/MM/YYYY - HH:mm').format(
						'YYYY-MM-DDTHH:mm' + ':00'
					);
					risorseCoinvolte[index].rc_DataOraFine = tmp;
				}
			}
			risorseCoinvolte = JSON.stringify(risorseCoinvolte);

			if (tabellaDistintaRisorse.rows().data().toArray().length > 0) {
				$.post(url, {
					azione: 'salva-ordine',
					datiForm: datiForm,
					risorseCoinvolte: risorseCoinvolte,
					componenti: componenti,
					consumi: consumi,
				}).done(function (data) {
					if (data == 'OK') {
						$('#form-dati-ordine').removeClass('was-validated');
						sessionStorage.removeItem('idProduzione');
						sessionStorage.removeItem('tabShown');
						$('#nuova-commessa').prop('hidden', false);
						$('#annulla-modifica-ordine').prop('hidden', true);
						$('#conferma-modifica-ordine').prop('hidden', true);
						$('#aggiungi-risorsa-ordine').prop('hidden', true);
						$('#aggiungi-componente-ordine').prop('hidden', true);
						$('#aggiungi-consumo-ordine').prop('hidden', true);
						$('.multi-collapse').collapse('toggle');
						swal({
							title: 'OPERAZIONE EFFETTUATA!',
							icon: 'success',
						});
						modOn = false;
						mostraOrdini();
					} else {
						console.log(data);
						swal({
							title: 'ERRORE!',
							text: 'Errore nel salvattaggio dei dati.',
							icon: 'error',
						});
					}
				});
			} else {
				swal({
					title: 'ATTENZIONE',
					text: 'La distinta risorse deve contenere almeno una macchina.',
					icon: 'warning',
				});
			}
		} else {
			swal({
				title: 'ATTENZIONE',
				text: 'Compilare tutti i campi richiesti.',
				icon: 'warning',
			});
		}
	});

	// GESTIONE DISTINTA RISORSE
	$('body').on('click', '.modifica-risorsa', function () {
		var risorsa = tabellaDistintaRisorse.row($(this).parents('tr')).data();
		indiceRigaModifica = tabellaDistintaRisorse.row($(this).parents('tr')).index();
		$('#form-nuova-risorsa').removeClass('was-validated');
		$('#form-nuova-risorsa')[0].reset();

		$('#form-nuova-risorsa #rc_IdRisorsa').html(
			'<option value="' +
				risorsa['rc_IdRisorsa'] +
				'">' +
				risorsa['ris_Descrizione'] +
				'</option>'
		);

		$.post(url, {
			azione: 'select-ricette',
			idRisorsa: risorsa['rc_IdRisorsa'],
		}).done(function (data) {
			try {
				var dati = JSON.parse(data);
				$('#form-nuova-risorsa #rc_IdRicetta').html(dati);

				for (const key in risorsa) {
					if (Object.hasOwnProperty.call(risorsa, key)) {
						const element = risorsa[key];
						$('#form-nuova-risorsa #' + key).val(element);
						$('#form-nuova-risorsa .form-check-input#' + key).prop('checked', element == '1');
					}
				}

				var tmp;
				if (risorsa.rc_VelocitaRisorsa) {
					tmp = parseFloat((risorsa.rc_VelocitaRisorsa + ' ').split(' ')[0]);
					$('#form-nuova-risorsa #rc_VelocitaRisorsa').val(tmp);
				} else {
					$('#form-nuova-risorsa #rc_VelocitaRisorsa').val(
						$('#form-dati-ordine #vel_VelocitaTeoricaLinea').val()
					);
				}
				if (risorsa.rc_DataOraInizio) {
					$('#form-nuova-risorsa #rc_DataOraInizio').val(
						moment(risorsa.rc_DataOraInizio, 'DD/MM/YYYY - HH:mm').format('YYYY-MM-DDTHH:mm')
					);
				} else {
					$('#form-nuova-risorsa #rc_DataOraInizio').val(
						$('#form-dati-ordine #op_DataProduzione').val()
					);
				}
				if (risorsa.rc_DataOraFine) {
					$('#form-nuova-risorsa #rc_DataOraFine').val(
						moment(risorsa.rc_DataOraFine, 'DD/MM/YYYY - HH:mm').format('YYYY-MM-DDTHH:mm')
					);
				} else {
					$('#form-nuova-risorsa #rc_DataOraFine').val(
						$('#form-dati-ordine #op_DataFine').val()
					);
				}

				$('#form-nuova-risorsa #rc_Azione').val('modifica');
				$('#form-nuova-risorsa #rc_NomeLineaProduzione').val(
					$('#form-dati-ordine #op_LineaProduzione option:selected').text()
				);

				$('#form-nuova-risorsa .selectpicker').selectpicker('refresh');
				$('#modal-nuova-risorsa').modal('show');
			} catch (error) {
				console.error(error);
				console.log(data);
				swal({
					title: 'ERRORE!',
					text: 'Errore nel reperimento delle ricette',
					icon: 'error',
				});
			}
		});
	});

	$('#aggiungi-risorsa-ordine').click(function () {
		$('#form-nuova-risorsa').removeClass('was-validated');
		$('#form-nuova-risorsa #rc_IdRicetta').html('');
		var idProduzione = $('#form-dati-ordine #op_IdProduzione').val();
		var idLineaProduzione = $('#form-dati-ordine #op_LineaProduzione').val();

		$('#form-nuova-risorsa')[0].reset();
		$('#form-nuova-risorsa #rc_IdProduzione').val(idProduzione);
		$('#form-nuova-risorsa #rc_LineaProduzione').val(idLineaProduzione);
		$('#form-nuova-risorsa #rc_NomeLineaProduzione').val(
			$('#form-dati-ordine #op_LineaProduzione option:selected').text()
		);
		var risorseImpegnate = tabellaDistintaRisorse.column(0).data().toArray();
		$.post(url, {
			azione: 'macchine-disponibili',
			idLineaProduzione: idLineaProduzione,
			risorseImpegnate: JSON.stringify(risorseImpegnate),
		}).done(function (data) {
			try {
				var dati = JSON.parse(data);
				var html = '';
				dati.forEach((risorsa) => {
					html +=
						'<option value="' +
						risorsa.ris_IdRisorsa +
						'">' +
						risorsa.ris_Descrizione +
						'</option>';
				});
				$('#form-nuova-risorsa #rc_IdRisorsa').html(html);
				$('#form-nuova-risorsa #rc_IdRisorsa').val('');
				$('#form-nuova-risorsa .selectpicker').selectpicker('refresh');
				$('#form-nuova-risorsa #rc_Azione').val('nuovo');
				$('#form-nuova-risorsa #rc_DataOraInizio').val($('#op_DataProduzione').val());
				$('#form-nuova-risorsa #rc_DataOraFine').val($('#op_DataFine').val());
				$('#modal-nuova-risorsa').modal('show');
			} catch (error) {
				console.error(error);
				console.log(data);
				swal({
					title: 'ERRORE!',
					text: 'Errore nel reperimento delle risorse disponibili.',
					icon: 'error',
				});
			}
		});
	});

	$('#form-nuova-risorsa #rc_IdRisorsa').on('change', function () {
		$.post(url, {
			azione: 'select-ricette',
			idRisorsa: $(this).val(),
		}).done(function (data) {
			try {
				var dati = JSON.parse(data);
				$('#form-nuova-risorsa #rc_IdRicetta').html(dati);
				$('#form-nuova-risorsa .selectpicker').selectpicker('refresh');
			} catch (error) {
				console.error(error);
				console.log(data);
				swal({
					title: 'ERRORE!',
					text: 'Errore nel reperimento delle ricette',
					icon: 'error',
				});
			}
		});
	});

	function salvaRisorsa(ordineRisorsa, aggiornaSuccessive) {
		var form = $('#form-nuova-risorsa').serializeArray();
		var inizio;
		if ($('#rc_Azione').val() == 'modifica') {
			var riga = tabellaDistintaRisorse.row(indiceRigaModifica).data();
			riga.rc_IdRicetta = form[3].value;
			riga.rc_NoteIniziali = form[7].value;
			riga.rc_DataOraInizio = form[4].value;
			riga.rc_VelocitaRisorsa = form[5].value;
			riga.rc_DataOraFine = form[6].value;

			riga.rc_RegistraMisure = $('#form-nuova-risorsa #rc_RegistraMisure');
			riga.rc_RegistraMisure = riga.rc_RegistraMisure.prop('checked') ? 1 : 0;

			riga.rc_FlagUltima = $('#form-nuova-risorsa #rc_FlagUltima');
			riga.rc_FlagUltima = riga.rc_FlagUltima.prop('checked') ? 1 : 0;

			riga.ricm_Descrizione = $('#form-nuova-risorsa #rc_IdRicetta option:selected').text();
			var marked =
				'<div class="d-flex justify-content-center align-items-center" style="max-height:18px"><i class="mdi mdi-checkbox-marked mdi-18px"></i></div>';
			var unmarked =
				'<div class="d-flex justify-content-center align-items-center" style="max-height:18px"><i class="mdi mdi-checkbox-blank-outline mdi-18px"></i></div>';

			riga.RegistraMisure = riga.rc_RegistraMisure ? marked : unmarked;
			riga.FlagUltima = riga.rc_FlagUltima ? marked : unmarked;

			var tmp;
			inizio = moment(riga.rc_DataOraInizio);
			riga.rc_DataOraInizio = inizio.format('DD/MM/YYYY - HH:mm');
			tmp = moment(riga.rc_DataOraFine);
			riga.rc_DataOraFine = tmp.format('DD/MM/YYYY - HH:mm');

			riga.rc_VelocitaRisorsa +=
				' [' + $('#form-dati-ordine #op_Udm option:selected').text() + '/h]';
			tabellaDistintaRisorse.row(indiceRigaModifica).data(riga).draw();
			$('#modal-nuova-risorsa').modal('hide');
		} else {
			var riga = {};
			riga['rc_IdProduzione'] = form[0].value;
			riga['rc_LineaProduzione'] = form[5].value;
			riga['rc_IdRisorsa'] = form[2].value;
			riga['rc_IdRicetta'] = form[3].value;
			riga['rc_NoteIniziali'] = form[7].value;
			riga['rc_DataOraInizio'] = form[4].value;
			riga['rc_VelocitaRisorsa'] = form[5].value;
			riga['rc_DataOraFine'] = form[6].value;

			riga['rc_RegistraMisure'] = $('#form-nuova-risorsa #rc_RegistraMisure');
			riga['rc_RegistraMisure'] = riga.rc_RegistraMisure.prop('checked') ? 1 : 0;

			riga['rc_FlagUltima'] = $('#form-nuova-risorsa #rc_FlagUltima');
			riga['rc_FlagUltima'] = riga.rc_FlagUltima.prop('checked') ? 1 : 0;

			riga['ricm_Descrizione'] = $('#form-nuova-risorsa #rc_IdRicetta option:selected').text();
			var marked =
				'<div class="d-flex justify-content-center align-items-center" style="max-height:18px"><i class="mdi mdi-checkbox-marked mdi-18px"></i></div>';
			var unmarked =
				'<div class="d-flex justify-content-center align-items-center" style="max-height:18px"><i class="mdi mdi-checkbox-blank-outline mdi-18px"></i></div>';

			riga['RegistraMisure'] = riga['rc_RegistraMisure'] ? marked : unmarked;
			riga['FlagUltima'] = riga['rc_FlagUltima'] ? marked : unmarked;

			riga['ris_Descrizione'] = $('#form-nuova-risorsa #rc_IdRisorsa option:selected').text();
			riga['ris_Ordinamento'] = 0;
			riga['azioni'] = `<div class="dropdown">
							<button class="btn btn-primary dropdown-toggle btn-gestione-ordine" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Modifica">
							<span class="mdi mdi-lead-pencil mdi-18px"></span>
							</button>
							<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
								<a class="dropdown-item modifica-risorsa"><i class="mdi mdi-account-edit"></i> Modifica</a>
								<a class="dropdown-item cancella-risorsa"><i class="mdi mdi-trash-can"></i> Elimina</a>
							</div>
						</div>`;

			var tmp;
			inizio = moment(riga.rc_DataOraInizio);
			riga.rc_DataOraInizio = inizio.format('DD/MM/YYYY - HH:mm');
			tmp = moment(riga.rc_DataOraFine);
			riga.rc_DataOraFine = tmp.format('DD/MM/YYYY - HH:mm');

			riga.rc_VelocitaRisorsa +=
				' [' + $('#form-dati-ordine #op_Udm option:selected').text() + '/h]';

			riga['ris_Ordinamento'] = ordineRisorsa.toString();
			tabellaDistintaRisorse.row.add(riga).draw();
			$('#modal-nuova-risorsa').modal('hide');
		}

		if (aggiornaSuccessive) {
			tabellaDistintaRisorse.rows().every(function (rowIdx, tableLoop, rowLoop) {
				var risorsa = this.data();

				var ordDiff = risorsa.ris_Ordinamento - ordineRisorsa;

				if (ordDiff > 0) {
					inizio.add(5 * ordDiff, 'm');

					var dataInizio = inizio.format('DD/MM/YYYY - HH:mm');
					var vel = risorsa.rc_VelocitaRisorsa.split(' ')[0];
					var qta = $('#form-dati-ordine #op_QtaDaProdurre').val();
					var sec = (qta * 3600) / vel;

					inizio = inizio.add(sec, 's');

					var dataFine = inizio.format('DD/MM/YYYY - HH:mm');

					inizio.subtract(sec, 's');
					inizio.subtract(5 * ordDiff, 'm');

					tabellaDistintaRisorse.cell(rowIdx, 4).data(dataInizio);
					tabellaDistintaRisorse.cell(rowIdx, 5).data(dataFine);
				}
			});
		}
	}

	$('#salva-risorsa').click(function () {
		$('#form-nuova-risorsa').addClass('was-validated');
		var nuovaRisorsa = $('#form-nuova-risorsa #rc_IdRisorsa').val();

		if ($('#form-nuova-risorsa')[0].checkValidity()) {
			$.post(url, {
				azione: 'ordine-risorsa',
				idRisorsa: nuovaRisorsa,
			}).done(function (data) {
				try {
					//controllo date altre risorse
					var ordineRisorsa = parseInt(data);
					var precedenti = false;
					var successivi = false;
					tabellaDistintaRisorse.rows().every(function (rowIdx, tableLoop, rowLoop) {
						var risorsa = this.data();

						if (risorsa.rc_IdRisorsa != nuovaRisorsa) {
							if (risorsa.ris_Ordinamento < ordineRisorsa) {
								var inizioRisorsa = moment(risorsa.rc_DataOraInizio, 'DD/MM/YYYY - HH:mm');
								var inizioNuova = $('#form-nuova-risorsa #rc_DataOraInizio').val();
								inizioNuova = moment(inizioNuova, 'YYYY-MM-DD HH:mm');

								if (inizioNuova.isBefore(inizioRisorsa)) {
									precedenti = true;
								}
							} else if (risorsa.ris_Ordinamento > ordineRisorsa) {
								var inizioRisorsa = moment(risorsa.rc_DataOraInizio, 'DD/MM/YYYY - HH:mm');
								var inizioNuova = $('#form-nuova-risorsa #rc_DataOraInizio').val();
								inizioNuova = moment(inizioNuova, 'YYYY-MM-DD HH:mm');

								var after = inizioNuova.isAfter(inizioRisorsa);
								if (after) {
									successivi = true;
								}
							}
						}
					});

					if (precedenti) {
						swal({
							title: 'Attenzione',
							text: 'Esiste una risorsa precedente a quella che si sta salvando con un orario di inizio successivo a quello impostato. Continuare?',
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
									text: 'Sì',
									value: true,
									visible: true,
									className: 'btn btn-primary',
									closeModal: true,
								},
							},
						}).then((value) => {
							if (value) {
								if (successivi) {
									swal({
										title: 'Attenzione',
										text: "Esiste una risorsa successiva a quella che si sta salvando con un orario di inizio precedente a quello impostato. Si dedidera aggiornare di conseguenza l'orario?",
										icon: 'warning',
										showCancelButton: true,
										buttons: {
											cancel: {
												text: 'No',
												value: null,
												visible: true,
												className: 'btn btn-danger',
												closeModal: true,
											},
											confirm: {
												text: 'Sì',
												value: true,
												visible: true,
												className: 'btn btn-primary',
												closeModal: true,
											},
										},
									}).then((aggiornaSuccessive) => {
										salvaRisorsa(ordineRisorsa, aggiornaSuccessive);
									});
								} else {
									salvaRisorsa(ordineRisorsa, false);
								}
							}
						});
					} else {
						if (successivi) {
							swal({
								title: 'Attenzione',
								text: "Esiste una risorsa successiva a quella che si sta salvando con un orario di inizio precedente a quello impostato. Si dedidera aggiornare di conseguenza l'orario?",
								icon: 'warning',
								showCancelButton: true,
								buttons: {
									cancel: {
										text: 'No',
										value: null,
										visible: true,
										className: 'btn btn-danger',
										closeModal: true,
									},
									confirm: {
										text: 'Sì',
										value: true,
										visible: true,
										className: 'btn btn-primary',
										closeModal: true,
									},
								},
							}).then((aggiornaSuccessive) => {
								salvaRisorsa(ordineRisorsa, aggiornaSuccessive);
							});
						} else {
							salvaRisorsa(ordineRisorsa, false);
						}
					}
				} catch (error) {
					console.error(error);
					console.log(data);
				}
			});
		} else {
			swal({
				title: 'ATTENZIONE',
				text: 'Compilare tutti i campi richiesti.',
				icon: 'warning',
			});
		}
	});

	$('body').on('click', '.cancella-risorsa', function () {
		tabellaDistintaRisorse.row($(this).parents('tr')).remove().draw();
	});

	// GESTIONE DISTINTA COMPONENTI
	$('body').on('click', '.modifica-componente', function () {
		$('#form-nuovo-componente').removeClass('was-validated');
		var componente = tabellaDistintaComponenti.row($(this).parents('tr')).data();
		indiceRigaModifica = tabellaDistintaComponenti.row($(this).parents('tr')).index();

		for (const key in componente) {
			if (Object.hasOwnProperty.call(componente, key)) {
				const element = componente[key];
				$('#form-nuovo-componente #' + key).val(element);
			}
		}
		$('#form-nuovo-componente #cmp_Azione').val('modifica');
		$('#form-nuovo-componente #cmp_Componente').html(
			'<option val="' +
				componente.cmp_Componente +
				'">' +
				componente.cmp_Componente +
				' - ' +
				componente.prd_Descrizione +
				'</option>'
		);
		$('#form-nuovo-componente #cmp_Componente').prop('disabled', true);
		$('#form-nuovo-componente .selectpicker').selectpicker('refresh');

		$('#modal-nuovo-componente').modal('show');
	});

	$('#aggiungi-componente-ordine').click(function () {
		$('#form-nuovo-componente').removeClass('was-validated');
		var idProduzione = $('#form-dati-ordine #op_IdProduzione').val();
		$('#form-nuovo-componente')[0].reset();
		$('#form-nuovo-componente #cmp_IdProduzione').val(idProduzione);
		$('#form-nuovo-componente #cmp_Azione').val('nuovo');
		$('#form-nuovo-componente #cmp_Componente').prop('disabled', false);
		var componenti = tabellaDistintaComponenti.column(0).data().toArray();
		$.post(url, {
			azione: 'componenti-disponibili',
			componenti: JSON.stringify(componenti),
		}).done(function (data) {
			try {
				var dati = JSON.parse(data);
				var options = '';

				if (dati.length > 0) {
					dati.forEach((prodotto) => {
						options +=
							'<option value="' +
							prodotto.prd_IdProdotto +
							'">' +
							prodotto.prd_Descrizione +
							'</option>';
					});

					$('#form-nuovo-componente #cmp_Componente').html(options);
					$('#form-nuovo-componente .selectpicker').val('');
					$('#form-nuovo-componente .selectpicker').selectpicker('refresh');
					$('#modal-nuovo-componente').modal('show');
				} else {
					swal({
						title: 'Attenzione!',
						text: 'Non ci sono altri componenti disponibili.',
						icon: 'warning',
						button: 'Ho capito',
					});
				}
			} catch (error) {
				console.error(error);
				console.log(data);
				swal({
					title: 'ERRORE!',
					text: 'Errore nel recupero dei componenti disponibili.',
					icon: 'error',
					button: 'Ho capito',
				});
			}
		});
	});

	$('#salva-componente').click(function () {
		$('#form-nuovo-componente').addClass('was-validated');
		if ($('#form-nuovo-componente')[0].checkValidity()) {
			var form = $('#form-nuovo-componente').serializeArray();
			var qtaDaProdurre = $('#form-dati-ordine #op_QtaDaProdurre').val();

			if (form[5].value == 'modifica') {
				var riga = tabellaDistintaComponenti.row(indiceRigaModifica).data();
				riga.cmp_Udm = form[1].value;
				riga.UdmComponente = $('#form-nuovo-componente #cmp_Udm option:selected').text();
				riga.cmp_FattoreMoltiplicativo = form[2].value;
				riga.cmp_PezziConfezione = form[3].value;

				riga.Fabbisogno =
					Math.ceil(
						(qtaDaProdurre * riga.cmp_FattoreMoltiplicativo) / riga.cmp_PezziConfezione
					) +
					' ' +
					extractSigla(riga.UdmComponente);

				tabellaDistintaComponenti.row(indiceRigaModifica).data(riga).draw();
			} else {
				var riga = {};
				riga['cmp_IdProduzione'] = form[0].value;
				riga['cmp_Componente'] = form[1].value;
				riga['prd_Descrizione'] = $(
					'#form-nuovo-componente #cmp_Componente option:selected'
				).text();
				riga['cmp_Udm'] = form[2].value;
				riga['UdmComponente'] = $('#form-nuovo-componente #cmp_Udm option:selected').text();
				riga['cmp_FattoreMoltiplicativo'] = form[3].value;
				riga['cmp_PezziConfezione'] = form[4].value;
				riga['cmp_Qta'] = Math.ceil(
					(qtaDaProdurre * riga.cmp_FattoreMoltiplicativo) / riga.cmp_PezziConfezione
				);

				riga['Fabbisogno'] = riga['cmp_Qta'] + ' ' + extractSigla(riga.UdmComponente);
				riga['azioni'] = `<div class="dropdown">
					<button class="btn btn-primary dropdown-toggle btn-gestione-ordine" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Modifica riga">
					<span class="mdi mdi-lead-pencil mdi-18px"></span>
					</button>
					<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
						<a class="dropdown-item modifica-componente"><i class="mdi mdi-account-edit"></i> Modifica</a>
						<a class="dropdown-item cancella-componente"><i class="mdi mdi-trash-can"></i> Elimina</a>
					</div>
				</div>`;
				tabellaDistintaComponenti.row.add(riga).draw();
			}
			$('#modal-nuovo-componente').modal('hide');
		} else {
			swal({
				title: 'ATTENZIONE!',
				text: 'Compilare tutti i campi richiesti.',
				icon: 'warning',
			});
		}
	});

	$('body').on('click', '.cancella-componente', function () {
		tabellaDistintaComponenti.row($(this).parents('tr')).remove().draw();
	});

	// GESTIONE DISTINTA CONSUMI
	$('body').on('click', '.modifica-consumo', function () {
		$('#form-nuovo-consumo').removeClass('was-validated');
		var consumo = tabellaDistintaConsumi.row($(this).parents('tr')).data();
		indiceRigaModifica = tabellaDistintaConsumi.row($(this).parents('tr')).index();
		$('#form-nuovo-consumo').removeClass('was-validated');
		$('#form-nuovo-consumo')[0].reset();
		$('#form-nuovo-consumo #con_Azione').val('modifica');

		$('#form-nuovo-consumo #con_IdRisorsa').html(
			'<option value="' + consumo.con_IdRisorsa + '">' + consumo.ris_Descrizione + '</option>'
		);
		$('#form-nuovo-consumo #con_IdTipoConsumo').html(
			'<option value="' + consumo.con_IdTipoConsumo + '">' + consumo.tc_Descrizione + '</option>'
		);

		for (const key in consumo) {
			if (Object.hasOwnProperty.call(consumo, key)) {
				const element = consumo[key];
				$('#form-nuovo-consumo #' + key).val(element);
			}
		}

		$('#form-nuovo-consumo .selectpicker').selectpicker('refresh');
		$('#modal-nuovo-consumo').modal('show');
	});

	$('#aggiungi-consumo-ordine').click(function () {
		$('#form-nuovo-consumo').removeClass('was-validated');
		var idProduzione = $('#form-dati-ordine #op_IdProduzione').val();
		$('#form-nuovo-consumo')[0].reset();
		$('#form-nuovo-consumo #con_IdProduzione').val(idProduzione);
		$('#form-nuovo-consumo #con_Azione').val('nuovo');

		var risorseCoinvolte = tabellaDistintaRisorse.rows().data().toArray();

		var html = '';
		risorseCoinvolte.forEach((risorsa) => {
			html +=
				'<option value="' + risorsa.rc_IdRisorsa + '">' + risorsa.ris_Descrizione + '</option>';
		});
		$('#form-nuovo-consumo #con_IdRisorsa').html(html);

		$.post(url, { azione: 'tipi-consumo' }).done(function (data) {
			try {
				var dati = JSON.parse(data);
				var html = '';
				dati.forEach((tipi) => {
					html +=
						'<option value="' + tipi.tc_IdRiga + '">' + tipi.tc_Descrizione + '</option>';
				});
				$('#form-nuovo-consumo #con_IdTipoConsumo').html(html);
				$('#form-nuovo-consumo .selectpicker').val('');
				$('#form-nuovo-consumo .selectpicker').selectpicker('refresh');

				$('#modal-nuovo-consumo').modal('show');
			} catch (error) {
				console.error(error);
				console.log(data);
			}
		});
	});

	$('#salva-consumo').click(function () {
		$('#form-nuovo-consumo').addClass('was-validated');

		if ($('#form-nuovo-consumo')[0].checkValidity()) {
			var form = $('#form-nuovo-consumo').serializeArray();
			console.log(form);
			if (form[6].value == 'modifica') {
				var riga = tabellaDistintaConsumi.row(indiceRigaModifica).data();
				riga.con_IdRisorsa = form[1].value;
				riga.con_IdTipoConsumo = form[2].value;
				riga.con_Rilevato = form[3].value;
				riga.con_ConsumoPezzoIpotetico = form[4].value;

				tabellaDistintaConsumi.row(indiceRigaModifica).data(riga).draw();
				$('#modal-nuovo-consumo').modal('hide');
			} else {
				var riga = {};
				riga['con_IdProduzione'] = form[0].value;
				riga['con_IdRisorsa'] = form[1].value;
				riga['con_IdTipoConsumo'] = form[2].value;
				riga['con_Rilevato'] = form[3].value;
				riga['con_ConsumoPezzoIpotetico'] = form[4].value;
				riga['ris_Descrizione'] = $(
					'#form-nuovo-consumo #con_IdRisorsa option:selected'
				).text();
				riga['tc_Descrizione'] = $(
					'#form-nuovo-consumo #con_IdTipoConsumo option:selected'
				).text();
				riga['azioni'] = `<div class="dropdown">
					<button class="btn btn-primary dropdown-toggle btn-gestione-ordine" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Modifica riga">
					<span class="mdi mdi-lead-pencil mdi-18px"></span>
					</button>
					<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
						<a class="dropdown-item modifica-consumo"><i class="mdi mdi-account-edit"></i> Modifica</a>
						<a class="dropdown-item cancella-consumo"><i class="mdi mdi-trash-can"></i> Elimina</a>
					</div>
				</div>`;

				$.post(url, {
					azione: 'dati-consumo',
					tipoConsumo: riga['con_IdTipoConsumo'],
				}).done(function (data) {
					try {
						var dati = JSON.parse(data);
						riga['um_Sigla'] = dati.um_Sigla;
						$('#modal-nuovo-consumo').modal('hide');

						tabellaDistintaConsumi.row.add(riga).draw();
					} catch (error) {
						console.error(error);
						console.log(data);
					}
				});
			}
		} else {
			swal({
				title: 'ATTENZIONE!',
				text: 'Compilare tutti i campi richiesti.',
				icon: 'warning',
			});
		}
	});

	$('body').on('click', '.cancella-consumo', function () {
		tabellaDistintaConsumi.row($(this).parents('tr')).remove().draw();
	});

	$('#form-nuova-risorsa #rc_DataOraInizio, #form-nuova-risorsa #rc_VelocitaRisorsa').blur(
		function () {
			var inizio = moment($('#form-nuova-risorsa #rc_DataOraInizio').val());

			var vel = $('#form-nuova-risorsa #rc_VelocitaRisorsa').val();
			var qta = $('#form-dati-ordine #op_QtaDaProdurre').val();

			if (vel && qta && vel != 0) {
				var sec = (qta * 3600) / vel;

				inizio = inizio.add(sec, 's');

				$('#form-nuova-risorsa #rc_DataOraFine').val(inizio.format('YYYY-MM-DDTHH:mm'));
			}
		}
	);
})(jQuery);
