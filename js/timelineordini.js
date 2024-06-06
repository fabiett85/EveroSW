// VARIABILI GLOBALI
var timeline = null;

var options = null;
var container = null;
var groups = new vis.DataSet();
var items = new vis.DataSet();

var datiGrafico = [];
var gruppi = [];

var today = new Date(new Date().setHours(0, 0, 0, 0));
var tomorrow = new Date(new Date().setHours(23, 59, 59, 999) + 1);

// FUNZIONE: RECUPERO I DATI DA VISUALIZZARE SUL GANTT (GRUPPI = LINEE, ITEMS = COMMESSEG)
function recuperaDatiGantt() {
	// Azzero le variabili utilizzate
	groups.clear();
	items.clear();
	gruppi = [];
	datiGrafico = [];

	// Recupero le linee definite nel sistema (saranno i GRUPPI DATI del GAntt)
	$.ajaxSetup({ async: false });
	$.post('timelineordini.php', { azione: 'recupera-linee' }).done(function (dataAjaxLinee) {
		if (dataAjaxLinee != 'NO_ROWS') {
			var gruppo;
			var datiLinee = JSON.parse(dataAjaxLinee);

			// Scorrendo una linea alla volta, recupero le commesse definite su di essa
			for (var i = 0; i < datiLinee.length; i++) {
				var idLinea = datiLinee[i].IdLinea;
				var descrizioneLinea = datiLinee[i].DescrizioneLinea;

				// Formatto opportunamente la stringa rappresentante il gruppo e la aggiungo al relativo array (gruppi)
				gruppo = {
					content: descrizioneLinea,
					id: idLinea,
					value: idLinea,
					className: 'gruppo-linee',
				};
				gruppi.push(gruppo);

				// Recupero le commesse definite sulla linea considerata
				$.ajaxSetup({ async: false });
				$.post('timelineordini.php', {
					azione: 'recupera-lavori-linea',
					idLineaProduzione: idLinea,
				}).done(function (dataAjaxOrdini) {
					if (dataAjaxOrdini != 'NO_ROWS') {
						var datiOrdini = JSON.parse(dataAjaxOrdini);

						// Scorrendo una commessa alla volta
						for (var j = 0; j < datiOrdini.length; j++) {
							// Formatto opportunamente la stringa rappresentante la singola commesssa e la aggiungo al relativo array (datiGrafico)
							var statoOrdine = datiOrdini[j].StatoOrdine;
							var codiceCommessaERiferimento;

							if (datiOrdini[j].RiferimentoProduzione == null) {
								codiceCommessaERiferimento = datiOrdini[j].IdProduzione;
							} else {
								codiceCommessaERiferimento =
									datiOrdini[j].IdProduzione +
									' (' +
									datiOrdini[j].RiferimentoProduzione +
									')';
							}
							datiGrafico.push({
								start: datiOrdini[j].DataInizio,
								end: datiOrdini[j].DataFine,
								group: idLinea,
								className: statoOrdine,
								content: codiceCommessaERiferimento,
								id: datiOrdini[j].IdProduzione,
							});
						}
					}
				});
			}

			// Aggiungo dli array di appoggio (datiGrafico e gruppi)nelle corrispettive variabili del Gantt (ITEMS e GROUPS)
			groups.add(gruppi);
			items.add(datiGrafico);
		} else {
			// visualizzo messaggio di errore
			swal({
				title: 'ATTENZIONE',
				text: 'Nessuna linea definita nel sistema.',
				icon: 'warning',
				confirmButtonText: 'Ho capito',
				showCancelButton: false,
				closeOnConfirm: true,
				animation: 'slide-from-top',
			});
		}
	});
}

// FUNZIONE: CARICAMENTO GRAFICO GANTT
function reloadDataGantt() {
	// Recupero e predispongo i dati
	recuperaDatiGantt();

	// Configuro nel Gantt opzioni, gruppi e dati.
	timeline.setGroups(groups);
	timeline.setItems(items);
}

(function ($) {
	// Istanzio il grafico
	container = document.getElementById('timeline_ordini');
	timeline = new vis.Timeline(container);

	// Recupero gruppi e dati da visualizzare
	recuperaDatiGantt();

	// Configurazione opzioni grafico
	options = {
		// option groupOrder can be a property name or a sort function
		// the sort function must compare two groups and return a value
		//     > 0 when a > b
		//     < 0 when a < b
		//       0 when a == b
		locale: 'it',
		groupOrder: function (a, b) {
			return a.value - b.value;
		},
		groupOrderSwap: function (a, b, groups) {
			var v = a.value;
			a.value = b.value;
			b.value = v;
		},
		groupTemplate: function (group) {
			var container = document.createElement('div');
			var label = document.createElement('span');
			label.innerHTML = group.content + ' ';
			container.insertAdjacentElement('afterBegin', label);

			return container;
		},
		orientation: 'top',
		start: today,
		end: tomorrow,
		stack: true,
		margin: { axis: 5, item: { vertical: 10, horizontal: 5 } },
	};

	// Setto nel Gantt opzioni, gruppi, dati e range di visualizzazione.
	timeline.setOptions(options);
	timeline.setGroups(groups);
	timeline.setItems(items);
	timeline.range.options.min = '2020-01-01T00:00:00';
	timeline.range.options.max = '2080-01-01T00:00:00';

	// DETTAGLI EVENTO GANTT: AL CLIC SULL'EVENTO, VISUALIZZO POPUP DI MODIFICA DATI
	timeline.on('click', function (properties) {
		var target = properties.event.target;

		if (properties.item != null) {
			var item = items.get(properties.item);
			var statoOrdine = item.className;
			var idProduzione = item.id;

			$.post('timelineordini.php', {
				azione: 'recupera-info-produzione',
				idProduzione: idProduzione,
			}).done(function (dataAjaxDettaglioOrdine) {
				$('#form-dettagli-ordine')[0].reset();

				var dati = JSON.parse(dataAjaxDettaglioOrdine);

				// Visualizza nel popup i dati recuperati
				for (var chiave in dati) {
					if (dati.hasOwnProperty(chiave)) {
						$('#form-dettagli-ordine')
							.find('input#' + chiave + 'Gantt')
							.val(dati[chiave]);
						$('#form-dettagli-ordine select#' + chiave + 'Gantt').val(dati[chiave]);
						$('#form-dettagli-ordine select#' + chiave + 'Gantt').selectpicker('refresh');
					}
				}

				// Formatto opportunamente il campo data/ora di programmazione
				var dataOraProgrammazione = dati['op_DataProduzione'] + 'T' + dati['op_OraProduzione'];
				$('#form-dettagli-ordine #DataOraProduzioneGantt').val(dataOraProgrammazione);

				// Formatto opportunamente il campo data/ora di fine teorica
				var dataOraFine = dati['op_DataFineTeorica'] + 'T' + dati['op_OraFineTeorica'];
				$('#form-dettagli-ordine #DataOraFineTeoricaGantt').val(dataOraFine);

				if (statoOrdine != 'manutenzione') {
					// In base allo stato della commessa, abilito o disabilito la possibilità di modificarlo
					if (dati['op_Stato'] == 4) {
						$('#form-dettagli-ordine #DataOraProduzioneGantt').prop('disabled', true);
						$('#form-dettagli-ordine #DataOraFineTeoricaGantt').prop('disabled', true);

						$('#form-dettagli-ordine #op_StatoGantt').html('<option value="">OK</option>');
						$('#form-dettagli-ordine #op_StatoGantt').prop('disabled', true);
						$('.selectpicker').selectpicker('refresh');
					} else {
						$('#form-dettagli-ordine #DataOraProduzioneGantt').prop('disabled', false);
						$('#form-dettagli-ordine #DataOraFineTeoricaGantt').prop('disabled', false);

						$('#form-dettagli-ordine #op_StatoGantt').prop('disabled', false);
						$('.selectpicker').selectpicker('refresh');
					}

					$('#salva-lavoro-gantt').prop('disabled', true);
					$('#modal-dettagli-ordine').modal('show');
				} else {
				}
			});
		}
	});



	$('#op_DataOraProduzione').on('blur', function () {
		var inizio = moment($(this).val());

		var vel = $('#form-dettagli-ordine #rc_VelocitaRisorsa').val();
		var qta = $('#form-dettagli-ordine #op_QtaDaProdurre').val();

		if (vel && qta && vel != 0) {
			var sec = (qta * 3600) / vel;

			inizio.add(sec, 's');

			$('#form-dettagli-ordine #op_DataOraFineTeorica').val(inizio.format('YYYY-MM-DDTHH:mm'));
		}
	});
})(jQuery);
