var timeline;

// FUNZIONE: CARICAMENTO GRAFICO GANTT
function reloadDataGantt() {
	// Recupero e predispongo i dati

	var items = [];
	var groups = [];

	$.post('timelineordini.php', { azione: 'dati-gantt' }).done(function (data) {
		try {
			var dati = JSON.parse(data);

			groups = new vis.DataSet(dati['groups']);
			items = new vis.DataSet(dati['items']);

			timeline.setGroups(groups);
			timeline.setItems(items);
		} catch (error) {
			console.error(error);
			console.log(data);
			swal({
				title: 'ERRORE!',
				text: 'Errore nel caricamento del gantt',
				icon: 'error',
			});
		}
	});
}

(function ($) {
	var today = moment();
	timeline = new vis.Timeline($('#timeline_ordini')[0]);

	var options = {
		selectable: false,
		start: today.subtract(1, 'd').format('YYYY-MM-DD hh:mm:ss'),
		end: today.add(3, 'd').format('YYYY-MM-DD hh:mm:ss'),
	};

	timeline.setOptions(options);

	$(function () {
		reloadDataGantt();
	});

	$('#timeline_ordini').click(function (event) {
		var idProduzione = timeline.getEventProperties(event).item;

		if (idProduzione) {
			$.post('timelineordini.php', {
				azione: 'info-ordine',
				idProduzione: idProduzione,
			}).done(function (data) {
				try {
					var dati = JSON.parse(data);

					for (const key in dati) {
						if (Object.hasOwnProperty.call(dati, key)) {
							const element = dati[key];
							$('#form-dettagli-ordine #' + key).val(element);
						}
					}

					/* if (dati.op_Stato == 4) {
						$('#form-dettagli-ordine .data').prop('readonly', true);
						$('#form-dettagli-ordine .data').prop('disabled', true);
					} else {
						$('#form-dettagli-ordine input.data').prop('readonly', false);
						$('#form-dettagli-ordine input.data').prop('disabled', false);
					} */

					$('#modal-dettagli-ordine').modal('show');
				} catch (error) {
					console.error(error);
					console.log(data);
				}
			});
		}
	});

	$('#op_DataOraProduzione').on('blur', function () {
		var inizio = moment($(this).val());
		console.log(inizio);

		var vel = $('#form-dettagli-ordine #vel_VelocitaTeoricaLinea').val();
		var qta = $('#form-dettagli-ordine #op_QtaDaProdurre').val();

		if (vel && qta && vel != 0) {
			var sec = (qta * 3600) / vel;

			inizio = inizio.add(sec, 's');
			console.log(inizio);

			$('#form-dettagli-ordine #op_DataOraFineTeorica').val(inizio.format('YYYY-MM-DDTHH:mm'));
		}
	});

	$('#salva-lavoro-gantt').click(function () {
		$.post('timelineordini.php', {
			azione: 'salva-ordine-gantt',
			data: $('#form-dettagli-ordine').serialize(),
		}).done(function (data) {
			if (data == 'OK') {
				reloadDataGantt();
			} else {
				console.log(data);
				swal({
					title: 'ERRORE!',
					icon: 'error',
				});
			}
		});
	});
})(jQuery);
