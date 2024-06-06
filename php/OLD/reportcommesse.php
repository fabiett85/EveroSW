<?php
// in che pagina siamo
$pagina = 'reportcommesse';

include("../inc/conn.php");

// VISUALIZZAZIONE COMMESSE CONCLUSI (STATO = 4 = 'CHIUSO')
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'mostra') {
	unset($_REQUEST['azione']);
	unset($_REQUEST['_']);
	// estraggo la lista
	$sth = $conn_mes->prepare(
		"SELECT * FROM ordini_produzione  AS ODP
		LEFT JOIN stati_ordine AS SO ON ODP.op_Stato = SO.so_IdStatoOrdine
		LEFT JOIN prodotti AS P ON ODP.op_Prodotto = P.prd_IdProdotto
		LEFT JOIN rientro_linea_produzione AS RLP ON ODP.op_IdProduzione = RLP.rlp_IdProduzione
		LEFT JOIN linee_produzione AS LP ON ODP.op_LineaProduzione = LP.lp_IdLinea
		LEFT JOIN unita_misura AS UM ON ODP.op_Udm = UM.um_IdRiga
		WHERE ODP.op_Stato = 5
		AND RLP.rlp_DataInizio >= :dataInizio
		AND RLP.rlp_DataFine <= :dataFine
		ORDER BY RLP.rlp_OraInizio DESC"
	);
	$sth->execute($_REQUEST);
	$righe = $sth->fetchAll(PDO::FETCH_ASSOC);


	$output = [];

	foreach ($righe as $riga) {
		if (isset($riga['rlp_DataInizio'])) {
			$dInizio = new DateTime($riga['rlp_DataInizio'] . " " . $riga['rlp_OraInizio']);
			$stringaDataInizio = $dInizio->format('d/m/Y - H:i');
		} else {
			$stringaDataInizio = "";
		}

		if (isset($riga['rlp_DataFine'])) {
			$dInizio = new DateTime($riga['rlp_DataFine'] . " " . $riga['rlp_OraFine']);
			$stringaDataFine = $dInizio->format('d/m/Y - H:i');
		} else {
			$stringaDataFine = "IN CORSO...";
		}

		$stringaTTotale = '';
		$stringaDowntime = '';
		if (isset($riga['rlp_TTotale'])) {
			$dateDummy = new DateTime();
			$dateDummy2 = clone $dateDummy;
			$intervallo = new DateInterval('PT' . round($riga['rlp_TTotale']) . 'M');
			$dateDummy2->add($intervallo);
			$intervallo = $dateDummy->diff($dateDummy2);
			$stringaTTotale = $intervallo->format('%H:%I:%S');
			if ($intervallo->d > 0) {
				$stringaTTotale = $intervallo->format('%dg %H:%I:%S');
			}
			if ($intervallo->m > 0) {
				$stringaTTotale = $intervallo->format('%mm %dg %H:%I:%S');
			}
		}
		if (isset($riga['rlp_Downtime'])) {
			$dateDummy = new DateTime();
			$dateDummy2 = clone $dateDummy;
			$intervallo = new DateInterval('PT' . round($riga['rlp_Downtime']) . 'M');
			$dateDummy2->add($intervallo);
			$intervallo = $dateDummy->diff($dateDummy2);
			$stringaDowntime = $intervallo->format('%H:%I:%S');
			if ($intervallo->d > 0) {
				$stringaDowntime = $intervallo->format('%dg %H:%I:%S');
			}
			if ($intervallo->m > 0) {
				$stringaDowntime = $intervallo->format('%mm %dg %H:%I:%S');
			}
		}


		//Preparo i dati da visualizzare
		$output[] = [
			'IdProduzione' => ($riga['op_Riferimento'] != "" ? $riga['op_IdProduzione'] . " (" . $riga['op_Riferimento'] . ')' : $riga['op_IdProduzione']),
			'Prodotto' => $riga['prd_Descrizione'],
			'Lotto' => $riga['op_Lotto'],
			'DescrizioneLinea' => $riga['lp_Descrizione'],
			'QtaRichiesta' => round($riga['op_QtaDaProdurre']) . " " . $riga['um_Sigla'],
			'QtaConforme' => round($riga['rlp_QtaConforme']) . " " . $riga['um_Sigla'],
			'QtaScarti' => round($riga['rlp_QtaScarti']) . " " . $riga['um_Sigla'],
			'DataOraInizio' => $stringaDataInizio,
			'DataOraFine' => $stringaDataFine,
			'TTotale' => $stringaTTotale,
			'Downtime' => $stringaDowntime,
			'Oee' => isset($riga['rlp_OEELinea']) ? round($riga['rlp_OEELinea'], 1) : 0,
		];
	}
	die(json_encode($output));
}





?>
<!DOCTYPE html>
<html lang="it">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>PROSPECT40 | Dashboard commesse</title>
	<?php include("inc_css.php") ?>
</head>

<body>

	<div class="container-scroller">

		<?php include("inc_testata.php") ?>

		<div class="container-fluid page-body-wrapper">

			<div class="main-panel">

				<div class="content-wrapper">

					<div class="card" id="blocco-elenco">
						<div class="card-header">
							<!-- Tab report VISUALIZZAZIONE REPORT COMMESSE -->
							<div class="row">

								<div class="col-8">
									<h4 class="card-title m-2">REPORT COMMESSE</h4>
								</div>
								<div class="col-2">
									<div class="form-group m-0">
										<label for="rptCom_DataInizio">Dal:</label>
										<input type="date" class="form-control form-control-sm dati-report" name="rptCom_DataInizio"
											id="rptCom_DataInizio" value="">
									</div>
								</div>
								<div class="col-2">
									<div class="form-group m-0">
										<label for="rptCom_DataFine">Al:</label>
										<input type="date" class="form-control form-control-sm dati-report" name="rptCom_DataFine"
											id="rptCom_DataFine" value="">
									</div>
								</div>

							</div>
						</div>

						<div class="card-body">
							<div class="row pt-2">

								<div class="col-12">

									<div class="table-responsive">

										<table id="tabellaCommesse" class="table table-striped" style="width:100%" data-source="">
											<thead>
												<tr>
													<th>Commessa (Rif.)</th>
													<th>Prodotto </th>
													<th>Lotto</th>
													<th>Linea</th>
													<th>Qta da prod.</th>
													<th>Qta conformi</th>
													<th>Qta scarti</th>
													<th>Data-ora inizio</th>
													<th>Data-ora fine</th>
													<th>Tempo tot.</th>
													<th>Downtime</th>
													<th>OEE [%]</th>
												</tr>
											</thead>
											<tbody></tbody>

										</table>

									</div>
								</div>

							</div>
							<div class="row">
								<div class="col-10">
								</div>
								<div class="col-2">
									<button type="button" class="mdi mdi-button" id="rptCom-stampa-report">CREA REPORT</button>
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
	<script src="../js/reportcommesse.js"></script>

</body>

</html>