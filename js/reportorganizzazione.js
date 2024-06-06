// VARIABILI GLOBALI
var g_rptOrg_idLineaProduzione = "lin_02";
var g_rptOrg_tipologiaGrafico = "oee";



// GENERO DATA E ORA ODIERNA
var today = new Date();
var dd = today.getDate();
var mm = today.getMonth()+1; //January is 0!
var yyyy = today.getFullYear();
if(dd<10) {
	dd = '0'+dd;
}
if(mm<10) {
	mm = '0'+mm;
}
var g_rptOrg_DataOdierna = yyyy + "-" + mm + "-" + dd;


// RIFERIMENTO AL GRAFICO
var riferimentoGraficoOEEOrganizzazione = document.getElementById("grOEEOrganizzazione");
var datiGraficoOEEOrganizzazione;


// DEFINIZIONE OPZIONI DEL GRAFICO
var opzioniGraficoOEEOrganizzazione = {

    tooltips: {
        enabled: false
    },
	layout: {
            padding: {
                left: 25,
                right: 25,
                top: 35,
                bottom: 0
            }
    },
	legend: {
		display: true,
		position: 'right',
		labels: {
			padding: 15,
			boxWidth: 10,
			fontColor: 'black'
		},
		onClick: function(e, legendItem) {
			var index = legendItem.datasetIndex;
			var ci = this.chart;
			var alreadyHidden = (ci.getDatasetMeta(index).hidden === null) ? false : ci.getDatasetMeta(index).hidden;

			ci.data.datasets.forEach(function(e, i) {
				var meta = ci.getDatasetMeta(i);

				if (i !== index) {
					if (!alreadyHidden) {
						meta.hidden = meta.hidden === null ? !meta.hidden : null;
					}
					else if (meta.hidden === null) {
						meta.hidden = true;
					}
				}
				else if (i === index) {
					meta.hidden = null;
				}
			});
			ci.update();
		},
	},
    animation: {
        onProgress: function () {

			var chartInstance = this.chart,
            ctx = chartInstance.ctx;

			ctx.font = "0.7rem Arial";

            this.data.datasets.forEach(function (dataset, i) {
                var meta = chartInstance.controller.getDatasetMeta(i);
                meta.data.forEach(function (bar, index) {
					if (dataset.data[index] != 0) {
						if (i == 0 && meta.hidden == null) {
							ctx.textAlign = 'center';
							ctx.fillStyle = 'green';
							var data = dataset.data[index] + "%";
							ctx.fillText(data, bar._model.x, bar._model.y - 15);
						}
						else if (i == 1 && meta.hidden == null) {
							ctx.textAlign = 'center';
							ctx.fillStyle = 'blue';
							var data = dataset.data[index] + "%";
							ctx.fillText(data, bar._model.x, bar._model.y - 15);
						}
						else if (i == 2 && meta.hidden == null) {
							ctx.textAlign = 'center';
							ctx.fillStyle = 'red';
							var data = dataset.data[index] + "%";
							ctx.fillText(data, bar._model.x, bar._model.y - 15);
						}
						else if (i == 3 && meta.hidden == null) {
							ctx.textAlign = 'center';
							ctx.fillStyle = 'orange';
							var data = dataset.data[index] + "%";
							ctx.fillText(data, bar._model.x, bar._model.y - 15);
						}
					}

                });
            });
        }
    },
	responsive: true,
	scales: {
		xAxes: [{
			ticks: {
				autoSkip: false,
				maxRotation: 40,
				minRotation: 40,
				fontSize: 10,
                fontStyle: 'italic',
				fontColor: 'black',
				padding: 5
			},
			 scaleLabel: {
				display: true,
				fontColor: 'black',
				labelString: 'PERIODO CONSIDERATO ',
				fontSize: 10,
				fontStyle: 'bold'
			}
		}],
        yAxes: [{
            ticks: {
                fontSize: 10,
                fontStyle: 'italic',
				fontColor: 'black',
				padding: 15
            },
			scaleLabel: {
				display: true,
				fontColor: 'black',
				labelString: 'OEE [%]',
				fontSize: 10,
				fontStyle: 'bold'
			},
        }],
	}

};


// ISTANZIO IL GRAFICO
var graficoOEEOrganizzazione = new Chart(riferimentoGraficoOEEOrganizzazione, {
	type: 'line',
	data: datiGraficoOEEOrganizzazione,
	options: opzioniGraficoOEEOrganizzazione,
});


// FUNZIONE DI RECUPERO DATI PERIODO E RELATIVA VISUALIZZAZIONE IN TABELLA E GRAFICO
function recuperaDatiGraficoOEEOrganizzazione() {

	// Se ho una linea selezionata
	if (g_rptOrg_idLineaProduzione != null) {

		// Recupero i dati relativi all'OEE, secondo i criteri impsotati
		$.post("inc_reportorganizzazione.php", { azione: "rptOrg-calcola-oee-periodo", idLineaProduzione: g_rptOrg_idLineaProduzione, dataInizioPeriodo: g_DataInizio, dataFinePeriodo: g_DataFine })
		.done(function(dataAjax) {

			var labelsGrOrganizzazione = [];
			var datiGrOEEOrganizzazione = [];
			var datiGrFattoreDOrganizzazione = [];
			var datiGrFattoreEOrganizzazione = [];
			var datiGrFattoreQOrganizzazione = [];

			if (dataAjax != "NO_ROWS") {
				var datiPeriodo = JSON.parse(dataAjax);

				$('#rptOrg_DPeriodo').val(datiPeriodo['FattoreDPeriodo']);
				$('#rptOrg_EPeriodo').val(datiPeriodo['FattoreEPeriodo']);
				$('#rptOrg_QPeriodo').val(datiPeriodo['FattoreQPeriodo']);

				$('#rptOrg_OEEPeriodo').val(datiPeriodo['OEEPeriodo']);

				$.post("inc_reportorganizzazione.php", { azione: "rptOrg-calcola-oee-giorno", idLineaProduzione: g_rptOrg_idLineaProduzione, dataInizioPeriodo: g_DataInizio, dataFinePeriodo: g_DataFine })
				.done(function(dataAjax) {

					if (dataAjax != "NO_ROWS") {

						var dati = JSON.parse(dataAjax);
						var OEEPeriodo;

						dati.forEach(function(entry) {
							labelsGrOrganizzazione.push(entry.DataGiorno);

							datiGrOEEOrganizzazione.push(entry.OEEGiorno);
							datiGrFattoreDOrganizzazione.push(entry.FattoreDGiorno);
							datiGrFattoreEOrganizzazione.push(entry.FattoreEGiorno);
							datiGrFattoreQOrganizzazione.push(entry.FattoreQGiorno);

						});


						datiGraficoOEEOrganizzazione = {
							labels: labelsGrOrganizzazione,
							datasets: [{
									label: 'OEE',
									data: datiGrOEEOrganizzazione,
									lineTension: 0,
									fill: true,
									borderColor: 'green',
									backgroundColor: 'transparent',
									pointBorderColor: 'green',
									pointBackgroundColor: 'green',
									pointRadius: 2,
									pointHoverRadius: 3,
									pointHitRadius: 30,
									pointBorderWidth: 2,
									borderWidth: 1
								  },
								  {
									label: '(D)ISP.',
									data: datiGrFattoreDOrganizzazione,
									lineTension: 0,
									fill: true,
									borderColor: 'blue',
									backgroundColor: 'transparent',
									pointBorderColor: 'blue',
									pointBackgroundColor: 'blue',
									pointRadius: 2,
									pointHoverRadius: 3,
									pointHitRadius: 30,
									pointBorderWidth: 2,
									borderWidth: 1
								  },
								  {
									label: '(E)FFIC.',
									data: datiGrFattoreEOrganizzazione,
									lineTension: 0,
									fill: true,
									borderColor: 'red',
									backgroundColor: 'transparent',
									pointBorderColor: 'red',
									pointBackgroundColor: 'red',
									pointRadius: 2,
									pointHoverRadius: 3,
									pointHitRadius: 30,
									pointBorderWidth: 2,
									borderWidth: 1
								  },
								  {
									label: '(Q)UAL.',
									data: datiGrFattoreQOrganizzazione,
									lineTension: 0,
									fill: true,
									borderColor: 'orange',
									backgroundColor: 'transparent',
									pointBorderColor: 'orange',
									pointBackgroundColor: 'orange',
									pointRadius: 2,
									pointHoverRadius: 3,
									pointHitRadius: 30,
									pointBorderWidth: 2,
									borderWidth: 1
								  }]
						};


						graficoOEEOrganizzazione.data = datiGraficoOEEOrganizzazione;
						graficoOEEOrganizzazione.data.datasets.forEach((dataSet, i) => {
							var meta = graficoOEEOrganizzazione.getDatasetMeta(i);
								if(i == 0) {
									meta.hidden = null;
								}
								else {
									meta.hidden = true;
								}

						});
						graficoOEEOrganizzazione.update();
					}
					else {

						swal({
							title: "ATTENZIONE!",
							text: "Nessun ordine presente nel periodo selezionato o piano orario lavorativo non definito: report non generabile.",
							icon: "warning",
							button: "Ho capito",
							closeModal: true,
						});

						$('#rptOrg_DPeriodo').val("0");
						$('#rptOrg_EPeriodo').val("0");
						$('#rptOrg_QPeriodo').val("0");

						$('#rptOrg_OEEPeriodo').val("0");
					}
				});

			}
			else {

				$('#rptOrg_DPeriodo').val("0");
				$('#rptOrg_EPeriodo').val("0");
				$('#rptOrg_QPeriodo').val("0");

				$('#rptOrg_OEEPeriodo').val("0");

				datiGraficoOEEOrganizzazione = {
					labels: labelsGrOrganizzazione,
					datasets: [{
							label: 'OEE',
							data: [],
							lineTension: 0,
							fill: true,
							borderColor: 'green',
							backgroundColor: 'transparent',
							pointBorderColor: 'green',
							pointBackgroundColor: 'green',
							pointRadius: 2,
							pointHoverRadius: 3,
							pointHitRadius: 30,
							pointBorderWidth: 2,
							borderWidth: 1
						  },
						  {
							label: '(D)isponibilità',
							data: [],
							lineTension: 0,
							fill: true,
							borderColor: 'blue',
							backgroundColor: 'transparent',
							pointBorderColor: 'blue',
							pointBackgroundColor: 'blue',
							pointRadius: 2,
							pointHoverRadius: 3,
							pointHitRadius: 30,
							pointBorderWidth: 2,
							borderWidth: 1
						  },
						  {
							label: '(E)fficienza',
							data: [],
							lineTension: 0,
							fill: true,
							borderColor: 'red',
							backgroundColor: 'transparent',
							pointBorderColor: 'red',
							pointBackgroundColor: 'red',
							pointRadius: 2,
							pointHoverRadius: 3,
							pointHitRadius: 30,
							pointBorderWidth: 2,
							borderWidth: 1
						  },
						  {
							label: '(Q)ualità',
							data: [],
							lineTension: 0,
							fill: true,
							borderColor: 'orange',
							backgroundColor: 'transparent',
							pointBorderColor: 'orange',
							pointBackgroundColor: 'orange',
							pointRadius: 2,
							pointHoverRadius: 3,
							pointHitRadius: 30,
							pointBorderWidth: 2,
							borderWidth: 1
						  }]
				};

				graficoOEEOrganizzazione.data = datiGraficoOEEOrganizzazione;
				graficoOEEOrganizzazione.update();

				swal({
					title: "ATTENZIONE!",
					text: "Nessun ordine presente nel periodo selezionato: report non generabile",
					icon: "warning",
					button: "Ho capito",

					closeModal: true,

				});
			}
		});
	}

}




(function($) {

	'use strict';

	var rptOrg_tabellaDatiOrdiniGiorno;

	var rptOrg_linguaItaliana = {
		"processing": "Caricamento...",
		"search": "Ricerca: ",
		"lengthMenu": "_MENU_ righe per pagina",
		"zeroRecords": "Nessun ordine per la giornata selezionata",
		"info": "Pagina _PAGE_ di _PAGES_",
		"infoEmpty": "Nessun dato disponibile",
		"infoFiltered": "(filtrate da _MAX_ righe totali)",
		"paginate": {
		    "first":      "Prima",
		    "last":       "Ultima",
		    "next":       "Prossima",
		    "previous":   "Precedente"
		}
	}


	// DATATABLE COMMESSE TROVATI GRAFICO
	$(function() {

		$.fn.dataTable.moment("DD/MM/YYYY HH:mm:ss");

		// DATATABLE DATI COMMESSA SELEZIONATO
		rptOrg_tabellaDatiOrdiniGiorno = $('#rptOrg-tabella-ordini-giorno').DataTable({
			"aLengthMenu": [
				[5, 10, -1],
				[5, 10, "Tutti"]
			],
			"iDisplayLength": 5,

			"order": [[ 2, "desc" ]],

			"columns": [
			    { "data": "IdProduzione"},
			    { "data": "DescrizioneProdotto"},
				{ "data": "DataInizio"},
				{ "data": "DataFine"},
				{ "data": "QtaProdotta"},
				{ "data": "QtaConforme"},
				{ "data": "D"},
			    { "data": "E"},
				{ "data": "Q"},
				{ "data": "OEELinea"}

			],

			"columnDefs": [
				{ className: "td-d", "targets": [ 6 ] },
				{ className: "td-e", "targets": [ 7 ] },
				{ className: "td-q", "targets": [ 8 ] },
				{ className: "td-oee", "targets": [ 9 ] }
			],

			"language": 	rptOrg_linguaItaliana,
			"info":    		false
			//"dom": "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4'l><'col-sm-8'p>>"

		});

	});







	// ****** OPERAZIONI SVOLTE SU COMANDO UTENTE ******

	// AL CLIC SU UNO DEI PUNTI VISUALIZZATI SUL GRAFICO (OEE COMMESSA), RICAVO LABEL E VALORE
	$('body').on('click', '#grOEEOrganizzazione', function (e) {

		var activePoints = graficoOEEOrganizzazione.getElementsAtEventForMode(e, 'point', graficoOEEOrganizzazione.options);
		var firstPoint = activePoints[0];
		if (firstPoint != null) {

			var label = graficoOEEOrganizzazione.data.labels[firstPoint._index];
			var descrizioneLinea =  $("#rptOrg_LineeProduzione option:selected").text();

			var dataGiornoSelezionato = label.substring(6, 10) + "-" + label.substring(3, 5) + "-" + label.substring(0, 2);

			$('#modal-organizzazione-label-data').text(label).css('font-style', 'italic').css('font-weight', 'bold');
			$('#modal-organizzazione-label-descLinea').text(descrizioneLinea).css('font-style', 'italic').css('font-weight', 'bold');


			$.post("inc_reportorganizzazione.php", { azione: "rptOrg-recupera-ordini-giorno", idLineaProduzione: g_rptOrg_idLineaProduzione, dataInizioPeriodo: dataGiornoSelezionato, dataFinePeriodo: dataGiornoSelezionato })
			.done(function(dataAjax) {

				if (dataAjax != "NO_ROWS") {
					$('#rptOrg-tabella-ordini-giorno').dataTable().fnClearTable();
					$('#rptOrg-tabella-ordini-giorno').dataTable().fnAddData(JSON.parse(dataAjax));
				}
				else {
					$('#rptOrg-tabella-ordini-giorno').dataTable().fnClearTable();
				}
			});

			$("#modal-report-organizzazione").modal("show");

		}
	});



	// ALLA VARIAZIONE DELLA LINEA SELEZIONATA, AGGIORNO I DATI VISUALIZZATI
	$('#rptOrg_LineeProduzione').on('change',function(){

		// Aggiorno le variabili globali con i valori attualmente selezionati
		g_rptOrg_idLineaProduzione = $('#rptOrg_LineeProduzione').val();

		// Memorizzo nelle variabili di sessione i valori recuperati
		sessionStorage.setItem('rptOrg_idLineaProduzione', g_rptOrg_idLineaProduzione);
		sessionStorage.setItem('rptLin_idLineaProduzione', g_rptOrg_idLineaProduzione);
		sessionStorage.setItem('rptRis_idLineaProduzione', g_rptOrg_idLineaProduzione);
		sessionStorage.setItem('rptDia_idLineaProduzione', g_rptOrg_idLineaProduzione);

		// Invoco funzione per recupero dati periodo
		recuperaDatiGraficoOEEOrganizzazione();

	});


	$('#rptOrg_DataInizio, #rptOrg_DataFine').on('blur',function(){
		var nome = $(this).attr('id')
		window['g_'+nome+'Periodo'] = $(this).val()
		sessionStorage.setItem(nome+'Periodo', window['g_'+nome+'Periodo'])
		var prefisso = nome.split('_')[0]
		var dataInizio = $('#'+prefisso+'_DataInizio').val()
		var dataFine = $('#'+prefisso+'_DataFine').val()
		if (dataFine>=dataInizio){
			recuperaDatiGraficoOEEOrganizzazione();
		}
		else{
			swal({
				title: "ATTENZIONE!",
				text: "La data di inizio periodo è oltre la data di fine.",
				icon: "warning",
				button: "Ho capito",

				closeModal: true,

			});
		}
	});




	// ESPORTAZIONE GRAFICO E TABELLA COMMESSE NEL PERIODO CONSIDERATO
	$('#rptOrg-stampa-report').on('click',function(){

		// Ricavo la data attuale per generazione nome report
		var today = new Date();
		var dd = today.getDate();
		var mm = today.getMonth()+1; //January is 0!
		var yyyy = today.getFullYear();
		if(dd<10) {
			dd = '0'+dd;
		}
		if(mm<10) {
			mm = '0'+mm;
		}


		var dataReport = yyyy +""+ mm + "" +dd;
		var dataReportIntestazione = dd + "/" + mm + "/" + yyyy;
		var nomeFilePDF = "";
		var descrizioneLinea =  $("#rptOrg_LineeProduzione option:selected").text();

		var valoriRiga = [];
		var valoriTabella = [];

		// Credo il documento
		var doc = new jspdf.jsPDF({orientation: "landscape"})
		const pageWidth = doc.internal.pageSize.width;
		const pageHeight = doc.internal.pageSize.height;

		// Scrittura intestazione report
		doc.setFontSize(11);
		doc.text(10, 10, "ANALISI RENDIMENTO ORGANIZZAZIONE");
		doc.text(pageWidth - 60, 10, "Data: " + dataReportIntestazione);
		doc.setFontSize(9);
		doc.text(10, 15, "Linea: " + descrizioneLinea);
		doc.text(10, 20, "Periodo considerato: " + g_DataInizio.substring(8, 10)+"/"+g_DataInizio.substring(5, 7)+"/"+g_DataInizio.substring(0, 4) + " - " +  + g_DataFine.substring(8, 10)+"/"+g_DataFine.substring(5, 7)+"/"+g_DataFine.substring(0, 4));


		// Recupero i dati degli ordini nel periodo
		$.post("inc_reportorganizzazione.php", { azione: "rptOrg-calcola-oee-giorno", idLineaProduzione: g_rptOrg_idLineaProduzione, dataInizioPeriodo: g_DataInizio, dataFinePeriodo: g_DataFine })
		.done(function(dataAjax) {

			// Se ho dati nel periodo selezionato
			if ((dataAjax != 'NO_ROWS') && (dataAjax != '')) {

				var dati;
				dati = JSON.parse(dataAjax);

				// Definisco il nome del report
				nomeFilePDF = "RptOEEOrganizzazione"+dataReport+"_"+g_DataInizio+"_"+g_DataFine;


				// Converto il grafico in immagine PNG
				var canvasImg = riferimentoGraficoOEEOrganizzazione.toDataURL("image/png", 1.0);

				// Costanti per formattazione corretta del grafico
				const imgProps= doc.getImageProperties(canvasImg);
				const pdfWidth = doc.internal.pageSize.getWidth();
				const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;

				// Aggiungo al documento il grafico degli ordini nel periodo
				doc.addImage(canvasImg, 'JPEG', 10, 20, pdfWidth - 20, pdfHeight );


				// Stampa numerazione pagine
				const pages = doc.internal.getNumberOfPages();

				doc.setFontSize(8);
				for (let j = 1; j < pages + 1 ; j++) {
					let horizontalPos = pageWidth / 2;  //Can be fixed number
					let verticalPos = pageHeight - 5;  //Can be fixed number
					doc.setPage(j);
					doc.text(`Pag. ${j} di ${pages}`, horizontalPos, verticalPos, {align: 'center' });
				}


				// Esporto e salvo il documento creato
				doc.save(nomeFilePDF+'.pdf')
			}
			else {
				swal({
					title: "ATTENZIONE!",
					text: "Nessun dato disponibile.",
					icon: "warning",
					button: "Ho capito",

					closeModal: true,

				});
			}
		});
	});




	// ESPORTAZIONE DETTAGLIO COMMESSA SELEZIONATO PER LA LINEA IN OGGETTO
	$('#rptOrg-stampa-report-dettaglio').on('click',function(){

		// Ricavo la data attuale per generazione nome report
		var today = new Date();
		var dd = today.getDate();
		var mm = today.getMonth()+1; //January is 0!
		var yyyy = today.getFullYear();
		if(dd<10) {
			dd = '0'+dd;
		}
		if(mm<10) {
			mm = '0'+mm;
		}


		var dataReport = yyyy +""+ mm + "" +dd;
		var dataReportIntestazione = dd + "/" + mm + "/" + yyyy;
		var descrizioneLinea =  $("#rptOrg_LineeProduzione option:selected").text();
		var idProduzioneSelezionato = $('#modal-linea-label-codOrdine').text();
		var dati;

		var valoriRiga = [];
		var valoriTabella = [];


		// Creo il documento e ricavo le dimensioni
		var doc = new jspdf.jsPDF({orientation: "landscape"})
		const pageWidth = doc.internal.pageSize.width;
		const pageHeight = doc.internal.pageSize.height;

		// Definisco il nome del report
		var nomeFilePDF = "Rpt" + dataReport + "_DettaglioLinea_Ord" + idProduzioneSelezionato;

		// Scrittura intestazione report
		doc.setFontSize(10);
		doc.text(pageWidth - 60, 10, "Report in data: " + dataReportIntestazione);
		doc.text(10, 10, "Linea: " + descrizioneLinea);
		doc.text(10, 15, "Commessa selezionata: " + idProduzioneSelezionato);


		// DETTAGLIO COMMESSA SELEZIONATO: visualizzazione distinta risorse per l'ordine di produzione selezionato
		$.post("inc_reportorganizzazione.php", { azione: "mostra-produzione", idProduzione: idProduzioneSelezionato })
		.done(function(data) {

			dati = JSON.parse(data);

			// Conversione: array di array associativi --> array di array
			// Scorro il file JSON dei dati ottenuti (Array di array associativi) iterando sui vari array associativi
			for (var i=0; i < dati.length; i++) {

				// Aggiungo ogni dato dell'array associativo all'array dei risultati
				$.each(dati[i], function (key, value) {
					valoriRiga.push(value);
				});

				// Terminato il controllo del primo array associativo, aggiungo l'array ottenuto al nuovo array di array dei risultati
				valoriTabella.push(valoriRiga);
				valoriRiga = [];
			}


			// Aggiungo al documento la tabella con la lista ordini nel periodo
			doc.autoTable({
				head: [['Codice', 'Prodotto', 'Data inizio', 'Data fine', 'Qta tot.', 'Qta ok', 'Lotto', 'T. tot.', 'T. down', 'T. attr.', 'Vel. linea [pz/h]', 'Disp. [%]', 'Eff. [%]', 'Qual. [%]', 'OEE linea. [%]']],
				body: valoriTabella,
				theme: 'grid',
				margin: {horizontal: 10},
				rowPageBreak: 'avoid',
				styles: {fontSize: 7},
				headerStyles: {
					lineWidth: 0.1,
					lineColor: [199, 199, 199]
				},
				startY: 20
			})

			// Azzero array
			valoriRiga = [];
			valoriTabella = [];


			doc.setLineWidth(1.0);

			// DISTINTA RISORSE: visualizzazione distinta risorse per l'ordine di produzione selezionato
			$.post("inc_reportorganizzazione.php", { azione: "mostra-distinta-risorse", idProduzione: idProduzioneSelezionato, tipoModal: "lin" })
			.done(function(data) {

				if (data != "NO_ROWS" ) {
					dati = JSON.parse(data);

					// Conversione: array di array associativi --> array di array
					// Scorro il file JSON dei dati ottenuti (Array di array associativi) iterando sui vari array associativi
					for (var i=0; i < dati.length; i++) {

						// Aggiungo ogni dato dell'array associativo all'array dei risultati
						$.each(dati[i], function (key, value) {
							valoriRiga.push(value);
						});

						// Terminato il controllo del primo array associativo, aggiungo l'array ottenuto al nuovo array di array dei risultati
						valoriTabella.push(valoriRiga);
						valoriRiga = [];
					}
				}

				// Aggiungo al documento la tabella con la lista ordini nel periodo
				doc.autoTable({
					head: [[ {content: 'ELENCO RISORSE COINVOLTE', colSpan: 9, styles: {halign: 'left', fillColor: [22, 160, 133]}}], ['Descrizione', 'Orario inizio', 'Orario fine', 'T. tot. [min]', 'T. down [min]', 'T. attr. [min]', 'Delta T. attr. [min]', 'OEE [%]', 'Vel. reale [pz/h]']],
					body: valoriTabella,
					theme: 'grid',
					margin: {horizontal: 10},
					styles: {fontSize: 7},
					headerStyles: {
						lineWidth: 0.1,
						lineColor: [199, 199, 199]
					},
					rowPageBreak: 'avoid'
				})

				// Azzero array
				valoriRiga = [];
				valoriTabella = [];


				// DISTINTA COMPONENTI: visualizzazione distinta componenti per l'ordine di produzione selezionato
				$.post("inc_reportorganizzazione.php", { azione: "mostra-distinta-componenti", idProduzione: idProduzioneSelezionato, tipoModal: "lin" })
				.done(function(data) {

					if (data != "NO_ROWS" ) {
						dati = JSON.parse(data);

						// Conversione: array di array associativi --> array di array
						// Scorro il file JSON dei dati ottenuti (Array di array associativi) iterando sui vari array associativi
						for (var i=0; i < dati.length; i++) {

							// Aggiungo ogni dato dell'array associativo all'array dei risultati
							$.each(dati[i], function (key, value) {
								valoriRiga.push(value);
							});

							// Terminato il controllo del primo array associativo, aggiungo l'array ottenuto al nuovo array di array dei risultati
							valoriTabella.push(valoriRiga);
							valoriRiga = [];
						}
					}


					// Aggiungo al documento la tabella con la lista ordini nel periodo
					doc.autoTable({
						head: [[ {content: 'ELENCO COMPONENTI PRODOTTO', colSpan: 3, styles: {halign: 'left', fillColor: [22, 160, 133]}}], ['Componente', 'Descrizione', 'Qta']],
						body: valoriTabella,
						theme: 'grid',
						margin: {horizontal: 10},
						styles: {fontSize: 7},
						headerStyles: {
							lineWidth: 0.1,
							lineColor: [199, 199, 199]
						},
						rowPageBreak: 'avoid'
					})

					// Azzero array
					valoriRiga = [];
					valoriTabella = [];


					// DETTAGLIO EVENTI: visualizzazione elenco casi verificatisi per l'ordine di produzione selezionato
					$.post("inc_reportorganizzazione.php", { azione: "mostra-casi-produzione", idProduzione: idProduzioneSelezionato, tipoModal: "lin" })
					.done(function(data) {

						if (data != "NO_ROWS" ) {

							dati = JSON.parse(data);

							// Conversione: array di array associativi --> array di array
							// Scorro il file JSON dei dati ottenuti (Array di array associativi) iterando sui vari array associativi
							for (var i=0; i < dati.length; i++) {

								// Aggiungo ogni dato dell'array associativo all'array dei risultati
								$.each(dati[i], function (key, value) {
									valoriRiga.push(value);
								});

								// Terminato il controllo del primo array associativo, aggiungo l'array ottenuto al nuovo array di array dei risultati
								valoriTabella.push(valoriRiga);
								valoriRiga = [];
							}
						}


						// Aggiungo al documento la tabella con la lista ordini nel periodo
						doc.autoTable({
							head: [[ {content: 'DETTAGLIO EVENTI', colSpan: 7, styles: {halign: 'left', fillColor: [22, 160, 133]}}], ['Risorsa', 'Evento', 'Tipo.', 'Orario inizio', 'Orario fine', 'Durata [min]', 'Note/segnalazioni']],
							body: valoriTabella,
							theme: 'grid',
							margin: {horizontal: 10},
							styles: {fontSize: 7},
							headerStyles: {
								lineWidth: 0.1,
								lineColor: [199, 199, 199]
							},
							rowPageBreak: 'avoid'
						})

						// Azzero array
						valoriRiga = [];
						valoriTabella = [];


						// DETTAGLIO DOWNTIME: visualizzazione periodi di downtime per l'ordine di produzione selezionato
						$.post("inc_reportorganizzazione.php", { azione: "mostra-dettagli-downtime", idProduzione: idProduzioneSelezionato, tipoModal: "lin" })
						.done(function(data) {

							if (data != "NO_ROWS" ) {

								dati = JSON.parse(data);

								// Conversione: array di array associativi --> array di array
								// Scorro il file JSON dei dati ottenuti (Array di array associativi) iterando sui vari array associativi
								for (var i=0; i < dati.length; i++) {

									// Aggiungo ogni dato dell'array associativo all'array dei risultati
									$.each(dati[i], function (key, value) {
										valoriRiga.push(value);
									});

									// Terminato il controllo del primo array associativo, aggiungo l'array ottenuto al nuovo array di array dei risultati
									valoriTabella.push(valoriRiga);
									valoriRiga = [];
								}
							}


							// Aggiungo al documento la tabella con la lista ordini nel periodo
							doc.autoTable({
								head: [[ {content: 'DETTAGLIO DOWNTIME', colSpan: 4, styles: {halign: 'left', fillColor: [22, 160, 133]}}], ['Risorsa', 'Orario inizio', 'Orario fine', 'Durata [min]']],
								body: valoriTabella,
								theme: 'grid',
								styles: {fontSize: 7},
								headerStyles: {
									lineWidth: 0.1,
									lineColor: [199, 199, 199]
								},
								rowPageBreak: 'avoid'
							})

							// Stampa numerazione pagine
							const pages = doc.internal.getNumberOfPages();
							const pageWidth = doc.internal.pageSize.width;
							const pageHeight = doc.internal.pageSize.height;
							for (let j = 1; j < pages + 1 ; j++) {
								let horizontalPos = pageWidth / 2;
								let verticalPos = pageHeight - 5;
								doc.setPage(j);
								doc.text(`Pag. ${j} di ${pages}`, horizontalPos, verticalPos, {align: 'center' });
							}

							// Esporto e salvo il documento creato
							doc.save(nomeFilePDF+'.pdf')

						});

					});

				});

			});

		});

	});


})(jQuery);

