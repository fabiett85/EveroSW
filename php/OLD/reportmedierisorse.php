<?php
// in che pagina siamo
$pagina = 'reportistica_3';
include('../inc/conn.php');

if (!empty($_REQUEST['azione'])) {
	// OEE MEDI PER RISORSA: VISUALIZZAZIONE TABELLA
	if ($_REQUEST['azione'] == 'rptRis-mostra-OEE-medi-generali') {

		$sthMedieGeneraliOEERisorse = $conn_mes->prepare(
			"SELECT * FROM risorse AS R
			JOIN (SELECT RP.rp_IdRisorsa, AVG(RP.rp_D) AS DMedio, AVG(RP.rp_Q) AS QMedio, AVG(RP.rp_E) AS EMedio, AVG(RP.rp_OEE) AS OEEMedio, MAX(RP.rp_OEE) AS OEEMax
				FROM risorsa_produzione AS RP
				WHERE RP.rp_DataInizio >= :DataInizio AND (RP.rp_DataFine IS NULL OR RP.rp_DataFine <= :DataFine)
				GROUP BY RP.rp_IdRisorsa
			) AS medie ON medie.rp_IdRisorsa = R.ris_IdRisorsa
			WHERE R.ris_IdRisorsa LIKE :IdRisorsa
			ORDER BY R.ris_Ordinamento ASC"
		);
		$sthMedieGeneraliOEERisorse->execute([
			':IdRisorsa' => $_REQUEST['idRisorsa'],
			':DataInizio' => $_REQUEST['dataInizio'],
			':DataFine' => $_REQUEST['dataFine']
		]);

		$output = [];

		$righe = $sthMedieGeneraliOEERisorse->fetchAll(PDO::FETCH_ASSOC);

		foreach ($righe as $riga) {
			//Preparo i dati da visualizzare

			$output[] = [
				'DescrizioneRisorsa' => $riga['ris_Descrizione'],
				'DMedioRisorsa' => round($riga['DMedio'], 2),
				'EMedioRisorsa' => round($riga['EMedio'], 2),
				'QMedioRisorsa' => round($riga['QMedio'], 2),
				'OEEMedioRisorsa' => round($riga['OEEMedio'], 2),
				'OEEMiglioreRisorsa' => round($riga['OEEMax'], 2)
			];
		}
		die(json_encode($output));
	}

	// OEE MEDI PER RISORSA: VISUALIZZAZIONE TABELLA
	if ($_REQUEST['azione'] == 'rptRis-mostra-OEE-medi') {

		$sthMedieOEERisorse = $conn_mes->prepare(
			"SELECT R.ris_Descrizione, P.prd_Descrizione, medie.DMedio, medie.QMedio, medie.EMedio, medie.OEEMedio, medie.OEEMax
			FROM risorse AS R
			JOIN (SELECT RP.rp_IdRisorsa, OP.op_Prodotto, AVG(RP.rp_D) AS DMedio, AVG(RP.rp_Q) AS QMedio, AVG(RP.rp_E) AS EMedio, AVG(RP.rp_OEE) AS OEEMedio, MAX(RP.rp_OEE) AS OEEMax
				FROM risorsa_produzione AS RP
				LEFT JOIN ordini_produzione AS OP ON RP.rp_IdProduzione = OP.op_IdProduzione
				WHERE RP.rp_DataInizio >= :DataInizio AND (RP.rp_DataFine IS NULL OR RP.rp_DataFine <= :DataFine)
				GROUP BY OP.op_Prodotto, RP.rp_IdRisorsa
			) AS medie ON medie.rp_IdRisorsa = R.ris_IdRisorsa
			JOIN prodotti AS P ON P.prd_IdProdotto = medie.op_Prodotto
			WHERE R.ris_IdRisorsa LIKE :IdRisorsa AND medie.op_Prodotto LIKE :Prodotto
			ORDER BY R.ris_Ordinamento ASC"
		);
		$sthMedieOEERisorse->execute([
			':IdRisorsa' => $_REQUEST['idRisorsa'],
			':Prodotto' => $_REQUEST['idProdotto'],
			':DataInizio' => $_REQUEST['dataInizio'],
			':DataFine' => $_REQUEST['dataFine']
		]);

		$output = [];

		$righe = $sthMedieOEERisorse->fetchAll(PDO::FETCH_ASSOC);

		foreach ($righe as $riga) {
			//Preparo i dati da visualizzare

			$output[] = [
				'DescrizioneRisorsa' => $riga['ris_Descrizione'],
				'DescrizioneProdotto' => $riga['prd_Descrizione'],
				'DMedioRisorsa' => round($riga['DMedio'], 2),
				'EMedioRisorsa' => round($riga['EMedio'], 2),
				'QMedioRisorsa' => round($riga['QMedio'], 2),
				'OEEMedioRisorsa' => round($riga['OEEMedio'], 2),
				'OEEMiglioreRisorsa' => round($riga['OEEMax'], 2)
			];
		}
		die(json_encode($output));
	}

	//REPORT LINEE AUSILIARIA: POPOLAMENTO SELECT PRODOTTI IN BASE ALLA RISORSA SELEZIONATA
	if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'rptRisMedie-carica-select-prodotti' && !empty($_REQUEST['idRisorsa']) && !empty($_REQUEST['idProdotto'])) {


		if (trim($_REQUEST['idRisorsa']) == '%') {

			// estraggo gli eventuali prodotti aggiuntivi
			$sth = $conn_mes->prepare(
				"SELECT DISTINCT prd_IdProdotto, prd_Descrizione FROM efficienza_risorse
				LEFT JOIN prodotti ON er_IdProdotto = prd_IdProdotto
				ORDER BY prd_Descrizione ASC"
			);
			$sth->execute([]);
		} else {
			// estraggo gli eventuali prodotti aggiuntivi
			$sth = $conn_mes->prepare(
				"SELECT DISTINCT prd_IdProdotto, prd_Descrizione FROM efficienza_risorse
				LEFT JOIN prodotti ON er_IdProdotto = prd_IdProdotto
				WHERE er_IdRisorsa = :IdRisorsa
				ORDER BY prd_Descrizione ASC"
			);
			$sth->execute([':IdRisorsa' => $_REQUEST['idRisorsa']]);
		}

		$optionValue = "";


		$prodotti = $sth->fetchAll(PDO::FETCH_ASSOC);

		$optionValue = $optionValue . "<option value='%'>TUTTI</option>";

		//Aggiungo ognuna delle sottocategorie trovate alla stringa che conterrà le possibili opzioni della select categorie, e che ritorno come risultato
		foreach ($prodotti as $prodotto) {

			//Se ho già una sottocategoria selezionata (provengo da popup "di modifica"), preparo il contenuto della select con l'option value corretto selezionato altrimenti preparo solo il contenuto.
			if (!empty($_REQUEST['idProdotto']) && $_REQUEST['idProdotto'] == $prodotto['prd_IdProdotto']) {
				$optionValue = $optionValue . "<option value='" . $prodotto['prd_IdProdotto'] . "' selected>" . $prodotto['prd_Descrizione'] . " </option>";
			} else {
				$optionValue = $optionValue . "<option value='" . $prodotto['prd_IdProdotto'] . "'>" . $prodotto['prd_Descrizione'] . " </option>";
			}
		}

		die($optionValue);
	}
}














?>
<!DOCTYPE html>
<html lang="it">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>PROSPECT40 | Reportistica - Media rendimento</title>
	<?php include("inc_css.php") ?>
</head>

<body>

	<div class="container-scroller">

		<?php include("inc_testata.php") ?>

		<div class="container-fluid page-body-wrapper">



			<div class="main-panel">

				<div class="content-wrapper">

					<div class="card">
						<div class="card-header">
							<h4 class="card-title m-2">MACCHINE - RENDIMENTO MEDIO</h4>
						</div>
						<div class="card-body">


							<ul class="nav nav-tabs pt-1" id="tab-medie-risorse" role="tablist">
								<li class="nav-item text-center" style="width: calc(100% / 2);">
									<a aria-controls="medie-risorse-generali" aria-selected="true" class="nav-link rounded-2 show"
										data-toggle="tab" href="#medie-risorse-generali" id="tab-medie-risorse-generali"
										role="tab"><b>RENDIMENTO MEDIO GENERALE</b></a>
								</li>
								<li class="nav-item text-center" style="width: calc(100% / 2);">
									<a aria-controls="medie-risorse-dettaglio" aria-selected="true" class="nav-link rounded-2"
										data-toggle="tab" href="#medie-risorse-dettaglio" id="tab-medie-risorse-dettaglio"
										role="tab"><b>RENDIMENTO MEDIO PER PRODOTTO</b></a>
								</li>
							</ul>

							<div class="tab-content tab-medie-risorse">

								<!-- Tab VISUALIZZAZIONE MEDIE RISORSE - GENERALI -->
								<div aria-labelledby="tab-medie-risorse-generali" class="tab-pane show" id="medie-risorse-generali"
									role="tabpanel">

									<div class="row mt-1">
										<div class="col-3">

											<div class="form-group">
												<label for="rptRisMedieGenerali_Risorse">MACCHINA</label>
												<select class="form-control form-control-sm selectpicker" id="rptRisMedieGenerali_Risorse"
													name="rptRisMedieGenerali_Risorse" data-live-search="true" required>
													<?php
													$sth = $conn_mes->prepare("SELECT risorse.*
																					FROM risorse", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
													$sth->execute();
													$linee = $sth->fetchAll(PDO::FETCH_ASSOC);
													echo "<option value='%'>TUTTE</option>";
													foreach ($linee as $linea) {
														echo "<option value='" . $linea['ris_IdRisorsa'] . "'>" . strtoupper($linea['ris_Descrizione']) . "</option>";
													}
													?>
												</select>
											</div>
										</div>
										<div class="col-3">

										</div>
										<div class="col-3">
											<div class="form-group">
												<label for="rptRis_DataInizio">Dal:</label>
												<input type="date" class="form-control form-control-sm dati-report" name="rptRis_DataInizio"
													id="rptRis_DataInizio" value="">
											</div>
										</div>
										<div class="col-3">
											<div class="form-group">
												<label for="rptRis_DataFine">Al:</label>
												<input type="date" class="form-control form-control-sm dati-report" name="rptRis_DataFine"
													id="rptRis_DataFine" value="">
											</div>
										</div>
									</div>

									<div class="table-responsive mb-5">

										<table id="rptRis-OEE-medi-generali" class="table table-striped" style="width:100%" data-source="">
											<thead>
												<tr>
													<th>Macchina</th>
													<th>(D)isp. media [%]</th>
													<th>(E)ffic. media [%]</th>
													<th>(Q)ual. media [%]</th>
													<th>OEE medio [%]</th>
													<th>OEE migliore [%]</th>
												</tr>
											</thead>
											<tbody></tbody>

										</table>

									</div>
									<div class="row">
										<div class="col-10">
										</div>
										<div class="col-2">
											<button type="button" class="mdi mdi-button" id="rptRisMedieGenerali-stampa-report"
												style="font-size: 0.6vw;">CREA REPORT</button>
										</div>
									</div>

								</div>



								<!-- Tab VISUALIZZAZIONE MEDIE RISORSE - DETTAGLI  -->
								<div aria-labelledby="tab-medie-risorse-dettaglio" class="tab-pane" id="medie-risorse-dettaglio"
									role="tabpanel">

									<div class="row mt-1">
										<div class="col-3">

											<div class="form-group">
												<label for="rptRisMedie_Risorse">MACCHINA</label>
												<select class="form-control form-control-sm selectpicker" id="rptRisMedie_Risorse"
													name="rptRisMedie_Risorse" data-live-search="true" required>
													<?php
													$sth = $conn_mes->prepare("SELECT risorse.*
																					FROM risorse", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
													$sth->execute();
													$linee = $sth->fetchAll(PDO::FETCH_ASSOC);
													echo "<option value='%'>TUTTE</option>";
													foreach ($linee as $linea) {
														echo "<option value='" . $linea['ris_IdRisorsa'] . "'>" . strtoupper($linea['ris_Descrizione']) . "</option>";
													}
													?>
												</select>
											</div>
										</div>
										<div class="col-3">

											<div class="form-group">
												<label for="rptRisMedie_Prodotti">PRODOTTO</label>
												<select class="form-control form-control-sm  selectpicker" id="rptRisMedie_Prodotti"
													name="rptRisMedie_Prodotti" data-live-search="true" required>

												</select>
											</div>
										</div>
										<div class="col-3">
											<div class="form-group">
												<label for="rptRisMedie_DataInizio">Dal:</label>
												<input type="date" class="form-control form-control-sm dati-report"
													name="rptRisMedie_DataInizio" id="rptRisMedie_DataInizio" value="">
											</div>
										</div>
										<div class="col-3">
											<div class="form-group">
												<label for="rptRisMedie_DataFine">Al:</label>
												<input type="date" class="form-control form-control-sm dati-report" name="rptRisMedie_DataFine"
													id="rptRisMedie_DataFine" value="">
											</div>
										</div>
									</div>

									<div class="table-responsive mb-4">

										<table id="rptRis-OEE-medi" class="table table-striped" style="width:100%" data-source="">
											<thead>
												<tr>
													<th>Macchina</th>
													<th>Prodotto</th>
													<th>(D)isp. media [%]</th>
													<th>(E)ffic. media [%]</th>
													<th>(Q)ual. media [%]</th>
													<th>OEE medio [%]</th>
													<th>OEE migliore [%]</th>
												</tr>
											</thead>
											<tbody></tbody>

										</table>

									</div>
									<div class="row">
										<div class="col-10">
										</div>
										<div class="col-2">
											<button type="button" class="mdi mdi-button" id="rptRisMedie-stampa-report"
												style="font-size: 0.6vw;">CREA REPORT</button>
										</div>
									</div>

								</div>

							</div>
						</div>
					</div>

				</div>

				<?php include("inc_footer.php") ?>

			</div>

		</div>

	</div>



	<?php include("inc_js.php") ?>

	<script src="../js/reportmedierisorse.js"></script>


</body>

</html>