var timeline;

function hexToRGB(hex, alpha) {
	var r = parseInt(hex.slice(1, 3), 16),
		g = parseInt(hex.slice(3, 5), 16),
		b = parseInt(hex.slice(5, 7), 16);

	if (alpha) {
		return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + alpha + ')';
	} else {
		return 'rgb(' + r + ', ' + g + ', ' + b + ')';
	}
}

// FUNZIONE: CARICAMENTO GRAFICO GANTT
function reloadDataGantt() {
	// Recupero e predispongo i dati

	var items = [];
	var groups = [];

	$.post('timelineordinirisorse.php', { azione: 'dati-gantt' }).done(function (data) {
		try {
			var dati = JSON.parse(data);

			var coloriOrdine = {};

			var counter = 0;
			for (let index = 0; index < dati['items'].length; index++) {
				const element = dati['items'][index];
				var idProduzione = element.id.split('!')[0];
				var colore;
				if (coloriOrdine.hasOwnProperty(idProduzione)) {
					colore = coloriOrdine[idProduzione];
				} else {
					colore = rainbow(dati['items'].length + 5, counter + 1);
					counter++;
					coloriOrdine[idProduzione] = colore;
				}

				var coloreTrasp = hexToRGB(colore, 0.5);

				dati['items'][index].style =
					'background-color: ' + coloreTrasp + '; border-color:' + colore;

				dati['items'][index].start = (dati['items'][index].start);
				dati['items'][index].end = (dati['items'][index].end);
			}

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
		start: today.subtract(1, 'd').format('YYYY-MM-DDThh:mm:ss'),
		end: today.add(3, 'd').format('YYYY-MM-DDThh:mm:ss'),
	};

	timeline.setOptions(options);

	$(function () {
		reloadDataGantt();
	});

	$('#timeline_ordini').click(function (event) {
		var splitted = timeline.getEventProperties(event).item.split('!');
		var idProduzione = splitted[0];
		var idRisorsa = splitted[1];

		if (idProduzione) {
			$.post('timelineordinirisorse.php', {
				azione: 'info-ordine',
				idProduzione: idProduzione,
				idRisorsa: idRisorsa,
			}).done(function (data) {
				try {
					var dati = JSON.parse(data);

					for (const key in dati) {
						if (Object.hasOwnProperty.call(dati, key)) {
							const element = dati[key];
							$('#form-dettagli-ordine #' + key).val(element);
						}
					}
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

		var vel = $('#form-dettagli-ordine #rc_VelocitaRisorsa').val();
		var qta = $('#form-dettagli-ordine #op_QtaDaProdurre').val();

		if (vel && qta && vel != 0) {
			var sec = (qta * 3600) / vel;

			inizio.add(sec, 's');

			$('#form-dettagli-ordine #op_DataOraFineTeorica').val(inizio.format('YYYY-MM-DDTHH:mm'));
		}
	});

	function salvaRisorsa(aggiornaSuccessive) {
		$.post('timelineordinirisorse.php', {
			azione: 'salva',
			data: $('#form-dettagli-ordine').serialize(),
			aggiornaSuccessive: aggiornaSuccessive,
		}).done(function (data) {
			if (data == 'OK') {
				reloadDataGantt();
				$('#modal-dettagli-ordine').modal('hide');
			} else {
				console.log(data);
				swal({
					title: 'ERRORE!',
					icon: 'error',
				});
			}
		});
	}

	$('#salva-lavoro-gantt').click(function () {
		$.post('timelineordinirisorse.php', {
			azione: 'controllo-conflitti',
			data: $('#form-dettagli-ordine').serialize(),
		}).done(function (data) {
			try {
				var dati = JSON.parse(data);
				var precedenti = parseInt(dati['precedenti']) == 1;
				var successivi = parseInt(dati['successivi']) == 1;

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
									salvaRisorsa(aggiornaSuccessive);
								});
							} else {
								salvaRisorsa(false);
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
							salvaRisorsa(aggiornaSuccessive);
						});
					} else {
						salvaRisorsa(false);
					}
				}
			} catch (error) {
				console.error(error);
				console.log(data);
				swal({
					title: 'ERRORE!',
					icon: 'error',
				});
			}
		});
	});
})(jQuery);
