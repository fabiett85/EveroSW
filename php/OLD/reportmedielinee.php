<?php
// in che pagina siamo
$pagina = 'reportmedielinee';
include('../inc/conn.php');

if (!empty($_REQUEST['azione'])) {
	// OEE MEDI PER LINEA: VISUALIZZAZIONE TABELLA
	if ($_REQUEST['azione'] == 'medie-linee') {
		unset($_REQUEST['azione']);
		unset($_REQUEST['_']);

		// estraggo la lista
		$sthMedieGeneraliOEELinee = $conn_mes->prepare(
			"SELECT * FROM linee_produzione AS LP
			JOIN (SELECT OP.op_LineaProduzione, AVG(RLP.rlp_D) AS DMedio, AVG(RLP.rlp_Q) AS QMedio, AVG(RLP.rlp_E) AS EMedio, AVG(RLP.rlp_OEELinea) AS OEEMedio, MAX(RLP.rlp_OEELinea) AS OEEMax
				FROM rientro_linea_produzione AS RLP
				LEFT JOIN ordini_produzione AS OP ON RLP.rlp_IdProduzione = OP.op_IdProduzione
				WHERE RLP.rlp_DataInizio >= :dataInizio AND (RLP.rlp_DataFine IS NULL OR RLP.rlp_DataFine <= :dataFine)
				GROUP BY OP.op_LineaProduzione
			) AS medie ON medie.op_LineaProduzione = LP.lp_IdLinea
			WHERE LP.lp_IdLinea LIKE :idLinea"
		);
		$sthMedieGeneraliOEELinee->execute($_REQUEST);


		$righe = $sthMedieGeneraliOEELinee->fetchAll(PDO::FETCH_ASSOC);

		$output = [];

		foreach ($righe as $riga) {
			//Preparo i dati da visualizzare
			$output[] = [
				'DescrizioneLinea' => $riga['lp_Descrizione'],
				'DMedioLinea' => round($riga['DMedio'], 2),
				'EMedioLinea' => round($riga['EMedio'], 2),
				'QMedioLinea' => round($riga['QMedio'], 2),
				'OEEMedioLinea' => round($riga['OEEMedio'], 2),
				'OEEMiglioreLinea' => round($riga['OEEMax'], 2)
			];
		}
		die(json_encode($output));
	}

	// OEE MEDI PER LINEA (DETTAGLIO): VISUALIZZAZIONE TABELLA
	if ($_REQUEST['azione'] == 'medie-prodotto') {
		unset($_REQUEST['azione']);
		unset($_REQUEST['_']);
		// estraggo la lista
		$sthMedieOEELinee = $conn_mes->prepare(
			"SELECT * FROM linee_produzione AS LP
			JOIN (SELECT OP.op_LineaProduzione, OP.op_Prodotto, AVG(RLP.rlp_D) AS DMedio, AVG(RLP.rlp_Q) AS QMedio, AVG(RLP.rlp_E) AS EMedio, AVG(RLP.rlp_OEELinea) AS OEEMedio, MAX(RLP.rlp_OEELinea) AS OEEMax
				FROM rientro_linea_produzione AS RLP
				LEFT JOIN ordini_produzione AS OP ON RLP.rlp_IdProduzione = OP.op_IdProduzione
				WHERE RLP.rlp_DataInizio >= :dataInizio AND (RLP.rlp_DataFine IS NULL OR RLP.rlp_DataFine <= :dataFine)
				GROUP BY OP.op_LineaProduzione, OP.op_Prodotto
			) AS medie ON medie.op_LineaProduzione = LP.lp_IdLinea
			JOIN prodotti AS P ON P.prd_IdProdotto = medie.op_Prodotto
			WHERE LP.lp_IdLinea LIKE :idLinea AND medie.op_Prodotto LIKE :idProdotto"
		);
		$sthMedieOEELinee->execute($_REQUEST);

		$righe = $sthMedieOEELinee->fetchAll(PDO::FETCH_ASSOC);

		$output = [];

		foreach ($righe as $riga) {
			//Preparo i dati da visualizzare
			$output[] = [
				'DescrizioneLinea' => $riga['lp_Descrizione'],
				'DescrizioneProdotto' => $riga['prd_Descrizione'],
				'DMedioLinea' => round($riga['DMedio'], 2),
				'EMedioLinea' => round($riga['EMedio'], 2),
				'QMedioLinea' => round($riga['QMedio'], 2),
				'OEEMedioLinea' => round($riga['OEEMedio'], 2),
				'OEEMiglioreLinea' => round($riga['OEEMax'], 2)
			];
		}
		die(json_encode($output));
	}

	//REPORT LINEE AUSILIARIA: POPOLAMENTO SELECT PRODOTTI IN BASE ALLA LINEA SELEZIONATA
	if ($_REQUEST['azione'] == 'rptLinMedie-carica-select-prodotti') {

		if (trim($_REQUEST['idLineaProduzione']) == '_') {
			// estraggo gli eventuali prodotti aggiuntivi
			$sth = $conn_mes->prepare(
				'SELECT * FROM velocita_teoriche
				LEFT JOIN prodotti ON vel_IdProdotto = prd_IdProdotto
				ORDER BY prd_Descrizione ASC'
			);
			$sth->execute();
		} else {
			// estraggo gli eventuali prodotti aggiuntivi
			$sth = $conn_mes->prepare(
				'SELECT * FROM velocita_teoriche
				LEFT JOIN prodotti ON vel_IdProdotto = prd_IdProdotto
				WHERE vel_IdLineaProduzione = :IdLineaProduzione
				ORDER BY prd_Descrizione ASC'
			);
			$sth->execute([':IdLineaProduzione' => $_REQUEST['idLineaProduzione']]);
		}

		$optionValue = "";


		$prodotti = $sth->fetchAll(PDO::FETCH_ASSOC);

		$optionValue = $optionValue . "<option value='%'>TUTTI</option>";

		//Aggiungo ognuna delle sottocategorie trovate alla stringa che conterrà le possibili opzioni della select categorie, e che ritorno come risultato
		foreach ($prodotti as $prodotto) {

			//Se ho già una sottocategoria selezionata (provengo da popup 'di modifica'), preparo il contenuto della select con l'option value corretto selezionato altrimenti preparo solo il contenuto.
			if (!empty($_REQUEST['idProdotto']) && $_REQUEST['idProdotto'] == $prodotto['vel_IdProdotto']) {
				$optionValue = $optionValue . "<option value='" . $prodotto['vel_IdProdotto'] . "' selected>" . $prodotto['prd_Descrizione'] . " </option>";
			} else {
				$optionValue = $optionValue . "<option value='" . $prodotto['vel_IdProdotto'] . "'>" . $prodotto['prd_Descrizione'] . " </option>";
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
							<h4 class="card-title m-2">LINEE - RENDIMENTO MEDIO</h4>
						</div>

						<div class="card-body">


							<ul class="nav nav-tabs pt-1" id="tab-medie-linee" role="tablist">
								<li class="nav-item text-center" style="width: calc(100% / 2);">
									<a aria-controls="medie-linee-generali" aria-selected="true" class="nav-link rounded-2 show"
										data-toggle="tab" href="#medie-linee-generali" id="tab-medie-linee-generali"
										role="tab"><b>RENDIMENTO MEDIO GENERALE</b></a>
								</li>
								<li class="nav-item text-center" style="width: calc(100% / 2);">
									<a aria-controls="medie-linee-dettaglio" aria-selected="true" class="nav-link rounded-2"
										data-toggle="tab" href="#medie-linee-dettaglio" id="tab-medie-linee-dettaglio"
										role="tab"><b>RENDIMENTO MEDIO PER PRODOTTO</b></a>
								</li>
							</ul>

							<div class="tab-content tab-medie-linee">

								<!-- Tab VISUALIZZAZIONE MEDIE LINEE - GENERALI -->
								<div aria-labelledby="tab-medie-linee-generali" class="tab-pane show" id="medie-linee-generali"
									role="tabpanel">

									<div class="row mt-1">
										<div class="col-3">

											<div class="form-group">
												<label for="rptLinMedieGenerali_LineeProduzione">Linea</label>
												<select class="form-control form-control-sm  selectpicker"
													id="rptLinMedieGenerali_LineeProduzione" name="rptLinMedieGenerali_LineeProduzione"
													data-live-search="true" required>
													<?php
													$sth = $conn_mes->prepare("SELECT linee_produzione.*
																					FROM linee_produzione
																					WHERE linee_produzione.lp_IdLinea != 'lin_0P' AND linee_produzione.lp_IdLinea != 'lin_0X'", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
													$sth->execute();
													$linee = $sth->fetchAll(PDO::FETCH_ASSOC);
													echo "<option value='%'>TUTTE</option>";
													foreach ($linee as $linea) {
														echo "<option value='" . $linea['lp_IdLinea'] . "'>" . strtoupper($linea['lp_Descrizione']) . "</option>";
													}
													?>
												</select>
											</div>
										</div>
										<div class="col-3">

										</div>
										<div class="col-3">
											<div class="form-group">
												<label for="rptLin_DataInizio">Dal:</label>
												<input type="date" class="form-control form-control-sm dati-report"
													name="MedieGenerali_DataInizio" id="MedieGenerali_DataInizio" value="">
											</div>
										</div>
										<div class="col-3">
											<div class="form-group">
												<label for="rptLin_DataFine">Al:</label>
												<input type="date" class="form-control form-control-sm dati-report"
													name="MedieGenerali_DataFine" id="MedieGenerali_DataFine" value="">
											</div>
										</div>
									</div>
									<div class="table-responsive mb-5">

										<table id="rptLin-OEE-medi-generali" class="table table-striped" style="width:100%" data-source="">
											<thead>
												<tr>
													<th>Linea</th>
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
											<button type="button" class="mdi mdi-button" id="rptLinMedieGenerali-stampa-report"
												style="font-size: 0.6vw;">CREA REPORT</button>
										</div>
									</div>

								</div>



								<!-- Tab VISUALIZZAZIONE MEDIE LINEE - DETTAGLI -->
								<div aria-labelledby="tab-medie-linee-dettaglio" class="tab-pane" id="medie-linee-dettaglio"
									role="tabpanel">

									<div class="row mt-1">
										<div class="col-3">
											<div class="form-group">
												<label for="rptLinMedie_LineeProduzione">Linea</label>
												<select class="form-control form-control-sm  selectpicker" id="rptLinMedie_LineeProduzione"
													name="rptLinMedie_LineeProduzione" data-live-search="true" required>
													<?php
													$sth = $conn_mes->prepare("SELECT linee_produzione.*
																					FROM linee_produzione
																					WHERE linee_produzione.lp_IdLinea != 'lin_0P' AND linee_produzione.lp_IdLinea != 'lin_0X'", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
													$sth->execute();
													$linee = $sth->fetchAll(PDO::FETCH_ASSOC);
													echo "<option value='%'>TUTTE</option>";
													foreach ($linee as $linea) {
														echo "<option value='" . $linea['lp_IdLinea'] . "'>" . strtoupper($linea['lp_Descrizione']) . "</option>";
													}
													?>
												</select>
											</div>

										</div>
										<div class="col-3">
											<div class="form-group">
												<label for="rptLinMedie_Prodotti">Prodotto</label>
												<select class="form-control form-control-sm selectpicker" id="rptLinMedie_Prodotti"
													name="rptLinMedie_Prodotti" data-live-search="true" required>

												</select>
											</div>
										</div>
										<div class="col-3">
											<div class="form-group">
												<label for="rptLinMedie_DataInizio">Dal:</label>
												<input type="date" class="form-control form-control-sm dati-report"
													name="MedieProdotto_DataInizio" id="MedieProdotto_DataInizio" value="">
											</div>
										</div>
										<div class="col-3">
											<div class="form-group">
												<label for="rptLinMedie_DataFine">Al:</label>
												<input type="date" class="form-control form-control-sm dati-report"
													name="MedieProdotto_DataFine" id="MedieProdotto_DataFine" value="">
											</div>
										</div>
									</div>
									<div class="table-responsive mb-4">

										<table id="rptLin-OEE-medi" class="table table-striped" style="width:100%" data-source="">
											<thead>
												<tr>
													<th>Linea</th>
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
											<button type="button" class="mdi mdi-button" id="rptLinMedie-stampa-report"
												style="font-size: 0.6vw;">CREA REPORT</button>
										</div>
									</div>

								</div>

							</div>
						</div>
					</div>

				</div>

				<?php include('inc_footer.php') ?>

			</div>

		</div>

	</div>



	<?php include('inc_js.php') ?>

	<script src="../js/reportmedielinee.js"></script>


</body>

</html>