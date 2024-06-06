(function ($) {
	'use strict';
	var url = 'sinotticolinee.php';
	// FUNZIONE: RECUPERO STATO DELLE RISORSE VISUALIZZATE IN ELENCO
	function recuperaDatiSinottico() {
		// Recupero dello stato delle risorse in elenco
		$.post(url, { azione: 'aggiorna' }).done(function (data) {
			try {
				var dati = JSON.parse(data);

				$('.dettagli-lavoro').html(
					`<div>
						[
							<span>Commessa: ND</span>
							<span class="ml-4">Lotto commessa: ND</span>
							<span class="ml-4">Prodotto: ND</span>
							<span class="ml-4">Qta richiesta: ND</span>
							<span class="ml-4">Qta prodotta: ND</span>
						]
						</div>`
				);
				var idLinea = '';
				dati.linee.forEach((linea) => {
					if (linea.lp_IdLinea != idLinea) {
						$('.dettagli-lavoro#' + linea.lp_IdLinea).html('');
						idLinea = linea.lp_IdLinea;
					}
					var html = $('.dettagli-lavoro#' + linea.lp_IdLinea).html();
					$('.dettagli-lavoro#' + linea.lp_IdLinea).html(
						html +
							`<div>[<span>Commessa: ` +
							linea['op_IdProduzione'] +
							`</span><span class="ml-4">Lotto commessa: ` +
							linea['op_Lotto'] +
							`</span><span class="ml-4">Prodotto: ` +
							linea['prd_Descrizione'] +
							`</span><span class="ml-4">Qta richiesta: ` +
							linea['op_QtaRichiesta'] +
							`</span><span class="ml-4">Qta prodotta: ` +
							linea['op_QtaProdotta'] +
							`</span>]</div>`
					);
				});

				dati.risorse.forEach((risorsa) => {
					$('#' + risorsa.IdRisorsa + ' #ris_IdProduzione').text(risorsa.IdProduzioneCaricata);
					$('#' + risorsa.IdRisorsa + ' #ris_StatoOrdine').text(risorsa.StatoOrdine);

					if (risorsa.StatoOrdine != 'OK') {
						$('#' + risorsa.IdRisorsa + ' .stato-macchina').css(
							'background-color',
							'#708090'
						);
						$('#' + risorsa.IdRisorsa + ' .stato-macchina').stop(true, true);
					} else if (risorsa.AvariaAuto == true || risorsa.AvariaMan == true) {
						$('#' + risorsa.IdRisorsa + ' .stato-macchina').css('background-color', 'red');
						$('#' + risorsa.IdRisorsa + ' .stato-macchina')
							.fadeOut(250)
							.fadeIn(250);
					} else if (risorsa.AttrezzaggioAuto == true || risorsa.AttrezzaggioMan == true) {
						$('#' + risorsa.IdRisorsa + ' .stato-macchina').css(
							'background-color',
							'rgba(	212, 158, 0, 1)'
						);
						$('#' + risorsa.IdRisorsa + ' .stato-macchina')
							.fadeOut(250)
							.fadeIn(250);
					} else if (risorsa.PausaPrevistaAuto == true || risorsa.PausaPrevistaMan == true) {
						$('#' + risorsa.IdRisorsa + ' .stato-macchina').css(
							'background-color',
							'#67a3ff'
						);
						$('#' + risorsa.IdRisorsa + ' .stato-macchina')
							.fadeOut(250)
							.fadeIn(250);
					} else {
						$('#' + risorsa.IdRisorsa + ' .stato-macchina').css(
							'background-color',
							'rgba(0, 179, 0, 1)'
						);
						$('#' + risorsa.IdRisorsa + ' .stato-macchina').stop(true, true);
					}
				});
			} catch (error) {
				console.error(error);
				console.log(data);
			}
		});
	}

	//VISUALIZZA DATATABLE RISORSE
	$(function () {
		recuperaDatiSinottico();

		$('.selectpicker').selectpicker({
			noneSelectedText: 'Seleziona una misura', // by this default 'Nothing selected' -->will change to Please Select
		});

		setInterval(function () {
			recuperaDatiSinottico();
		}, 2000);
	});

	// RECUPERA INFORMAZIONI RISORSA, AL CLIC SU UNA RISORSA DELL'ELENCO
	$('body').on('click', '.riquadro-risorsa-sinottico', function (e) {
		e.preventDefault();

		var idRisorsa = $(this).attr('id');

		$.post(url, { azione: 'dettaglio-risorsa', idRisorsa: idRisorsa }).done(function (data) {
			try {
				var dati = JSON.parse(data);

				var risorsa = dati.risorsa;

				for (const key in risorsa) {
					if (Object.hasOwnProperty.call(risorsa, key)) {
						const element = risorsa[key];
						$('#modal-dettagli-risorsa #' + key).html(element);
					}
				}

				var htmlMisure = '';

				dati.misure.forEach((misura) => {
					htmlMisure +=
						'<div class="col-6">' + misura.mis_Descrizione.toUpperCase() + '</div>';
					htmlMisure +=
						'<div class="col-6">' +
						misura.mis_ValoreIstantaneo +
						' [' +
						misura.mis_Udm +
						']</div>';
				});
				$('.dettagli-risorsa-misure').html(htmlMisure);
			} catch (error) {
				console.error(error);
				console.log(data);
			}
		});

		$('#modal-dettagli-risorsa').modal('show');
		return false;
	});
})(jQuery);
