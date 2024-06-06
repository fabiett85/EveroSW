(($) => {
	var tabellaLavori;
	const url = 'calendariomacchine.php';
	var idLinea = '%';
	var idRisorsa = '%';
	var stati = [1, 2, 3, 4];

	function mostraLavori() {
		tabellaLavori.ajax.url(
			`${url}?azione=mostra-lavori&stati=${stati}&idLinea=${idLinea}&idRisorsa=${idRisorsa}`
		);
		tabellaLavori.ajax.reload();
	}

	async function popolaSelectRisorse(flagMostraLavori = false) {
		await $.post(url, { azione: 'select-risorse', idLinea: idLinea }).done((data) => {
			try {
				var dati = JSON.parse(data);

				$('#rc_IdRisorsa').html('');
				var html = '<option value="%">TUTTE</option>';
				dati.forEach((element) => {
					html += `<option value="${element['ris_IdRisorsa']}"
					>${element['ris_Descrizione'].toUpperCase()}</option>`;
				});
				$('#rc_IdRisorsa').html(html);
				$('#rc_IdRisorsa').selectpicker('refresh');
				if (flagMostraLavori) {
					mostraLavori();
				}
			} catch (error) {
				console.error(error);
				console.log(data);
			}
		});
	}

	$(async () => {
		var tmp;
		tmp = sessionStorage.getItem(`${url}stati`);
		if (tmp) {
			stati = JSON.parse(tmp);
		}

		tmp = sessionStorage.getItem(`${url}idLinea`);
		if (tmp) {
			idLinea = tmp;
			await popolaSelectRisorse();
			tmp = sessionStorage.getItem(`${url}idRisorsa`);
			if (tmp) {
				idRisorsa = tmp;
			}
		}

		$('#op_LineaProduzione').val(idLinea);
		$('#rc_IdRisorsa').val(idRisorsa);
		$('#op_Stato').val(stati);
		$('.selectpicker').selectpicker('refresh');

		$.fn.dataTable.moment('DD/MM/YYYY HH:mm');
		tabellaLavori = $('#tabellaLavori').DataTable({
			aLengthMenu: [
				[12, 24, 50, 100, -1],
				[12, 24, 50, 100, 'Tutti'],
			],
			iDisplayLength: 12,
			language: linguaItaliana,
			autoWidth: false,
			ajax: {
				url: `${url}?azione=mostra-lavori&stati=${stati}&idLinea=${idLinea}&idRisorsa=${idRisorsa}`,
				dataSrc: '',
			},
			columns: [
				{ data: 'op_IdProduzione' },
				{ data: 'op_DataConsegna' },
				{ data: 'op_QtaDaProdurre' },
				{ data: 'inizio' },
				{ data: 'fine' },
				{ data: 'op_NoteProduzione' },
			],
			order: [3, 'asc'],
		});
	});

	$('#stampa-lavori').click(() => {
		var today = moment();

		var descrizioneRisorsa = $('#rc_IdRisorsa option:selected').text();
		var doc = new jspdf.jsPDF({ orientation: 'l' });
		const pageWidth = doc.internal.pageSize.width;
		const pageHeight = doc.internal.pageSize.height;

		doc.setFontSize(10);
		doc.text(10, 10, today.format('DD/MM/YYYY HH:mm'));
		doc.text(pageWidth - 60, 10, 'Scatolificio Maffioli Turrina S.p.A.');

		doc.setFontSize(16);
		doc.text(pageWidth / 2, 20, descrizioneRisorsa, { align: 'center' });
		doc.setFontSize(10);

		var datiTabella = tabellaLavori.rows({ search: 'applied' }).data().toArray();

		var arrayAutoTable = [];

		datiTabella.forEach((riga) => {
			arrayAutoTable.push([
				riga.op_IdProduzione,
				riga.op_DataConsegna,
				riga.op_QtaDaProdurre,
				riga.inizio,
				riga.fine,
				riga.op_NoteProduzione,
			]);
		});

		doc.autoTable({
			head: [['Lavoro', 'Data consegna', 'Qta', 'Inizio previsto', 'Fine prevista', 'Note']],
			body: arrayAutoTable,
			theme: 'grid',
			margin: { horizontal: 10 },
			styles: { fontSize: 7 },
			rowPageBreak: 'avoid',
			startY: 30,
			headStyles: {
				fillColor: 'white',
				textColor: 'black',
				lineWidth: 0.1,
				lineColor: 'black',
			},
		});
		doc.save(`${today.format('YYYY-MM-DD')}-LavoriPer${descrizioneRisorsa.replace(' ', '')}`);
	});

	$('#op_LineaProduzione').change(function () {
		idLinea = $(this).val();
		sessionStorage.setItem(`${url}idLinea`, idLinea);
		sessionStorage.removeItem(`${url}idRisorsa`);
		idRisorsa = '%';

		popolaSelectRisorse(true);
	});

	$('#rc_IdRisorsa').change(function () {
		idRisorsa = $(this).val();
		sessionStorage.setItem(`${url}idRisorsa`, idRisorsa);
		mostraLavori();
	});

	$('#op_Stato').change(function () {
		stati = $(this).val();
		sessionStorage.setItem(`${url}stati`, JSON.stringify(stati));
		mostraLavori();
	});
})(jQuery);
