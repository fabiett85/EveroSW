<?php
// in che pagina siamo
$pagina = 'reportcosti';
require_once("../inc/conn.php");

// REPORT RENDIMENTO RISORSE: RECUPERO VALORI OEE (GRAFICO E TABELLA)
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == "recupera-costi") {
	unset($_REQUEST['azione']);
	unset($_REQUEST['_']);

	$sthRecuperoCosti = $conn_mes->prepare(
		"SELECT *, B.TotaleOreInterventoOrdinario + B.TotaleOreInterventoStraordinario AS OreManutenzione FROM
		(
			SELECT RLP.rlp_IdProduzione, rlp_TTotale AS OreLavorate, rlp_Downtime AS OreDown, rlp_Attrezzaggio AS OreAttrezzaggio, 0 AS Consumi
			FROM rientro_linea_produzione AS RLP
			WHERE rlp_DataInizio >= :dataInizio AND rlp_DataFine <= :dataFine
		) AS A
		LEFT JOIN
		(
			SELECT AC.ac_IdProduzione,
			SUM(IIF(IMORD.im_DataInterventoInizio IS NOT NULL, DATEDIFF(HOUR, CONVERT(datetime, CONCAT(IMORD.im_DataInterventoInizio, 'T', IMORD.im_OraInterventoInizio,':00')), CONVERT(datetime, CONCAT(IMORD.im_DataInterventoFine,'T', IMORD.im_OraInterventoFine,':00'))),0)) AS TotaleOreInterventoOrdinario,
			SUM(IIF(IMSTR.im_DataInterventoInizio IS NOT NULL, DATEDIFF(HOUR, CONVERT(datetime, CONCAT(IMSTR.im_DataInterventoInizio, 'T', IMSTR.im_OraInterventoInizio,':00')), CONVERT(datetime, CONCAT(IMSTR.im_DataInterventoFine,'T', IMSTR.im_OraInterventoFine,':00'))),0)) AS TotaleOreInterventoStraordinario
			FROM attivita_casi AS AC
			LEFT JOIN interventi_manutenzione AS IMORD ON (AC.ac_ManOrd_Progressivo = IMORD.im_ProgressivoManOrdinaria  AND AC.ac_ManOrd_DataInizioPrevista = IMORD.im_DataInizioPrevista AND AC.ac_ManOrd_OraInizioPrevista = IMORD.im_OraInizioPrevista AND AC.ac_ManOrd_DataFinePrevista = IMORD.im_DataFinePrevista AND AC.ac_ManOrd_OraFinePrevista = IMORD.im_OraFinePrevista)
			LEFT JOIN interventi_manutenzione AS IMSTR ON (AC.ac_idRisorsa = IMSTR.im_IdRisorsa AND AC.ac_IdEvento = IMSTR.im_IdEvento AND AC.ac_DataInizio = IMSTR.im_DataEvento AND AC.ac_OraInizio = IMSTR.im_OraEvento)
			LEFT JOIN risorse ON AC.ac_IdRisorsa = risorse.ris_IdRisorsa
			GROUP BY AC.ac_IdProduzione
		) AS B ON A.rlp_IdProduzione = B.ac_IdProduzione
		LEFT JOIN ordini_produzione AS ODP ON A.rlp_IdProduzione = ODP.op_IdProduzione
		LEFT JOIN prodotti AS P ON P.prd_IdProdotto = ODP.op_Prodotto
		LEFT JOIN rientro_linea_produzione AS RLP ON RLP.rlp_IdProduzione = ODP.op_IdProduzione
		LEFT JOIN unita_misura AS UM ON UM.um_IdRiga = P.prd_UnitaMisura
		LEFT JOIN linee_produzione AS LP ON LP.lp_IdLinea = ODP.op_LineaProduzione"
	);
	$sthRecuperoCosti->execute($_REQUEST);



	$righe = $sthRecuperoCosti->fetchAll(PDO::FETCH_ASSOC);

	$output = [];
	foreach ($righe as $riga) {

		$ore = floor($riga['OreLavorate']);
		$minuti = ($riga['OreLavorate'] - $ore) / 60;
		//Preparo i dati da visualizzare
		$output[] = [
			'Prodotto' => $riga['prd_Descrizione'],
			'Commessa' => $riga['rlp_IdProduzione'],
			'OreLavorate' => isset($riga['OreLavorate']) ? round($riga['OreLavorate'] / 60, 2) . ' [h]' : '0' . ' [h]',
			'OreManutenzione' => isset($riga['OreManutenzione']) ? round($riga['OreManutenzione'], 2) . ' [h]' : '0' . ' [h]',
			'QtaConformi' => isset($riga['rlp_QtaConforme']) ? round($riga['rlp_QtaConforme'], 2) . ' [' . $riga['um_Sigla'] . ']' : 0,
			'TempoPerProdotto' => !empty($riga['OreLavorate']) && !empty($riga['rlp_QtaConforme']) && $riga['rlp_QtaConforme'] != 0 ? round($riga['OreLavorate'] / $riga['rlp_QtaConforme'] * 60, 2) . ' [sec/' . $riga['um_Sigla'] . ']' : '0' . ' [sec/' . $riga['um_Sigla'] . ']',
			'CostoPerProdotto' => !empty($riga['OreLavorate']) && !empty($riga['rlp_QtaConforme']) && $riga['rlp_QtaConforme'] != 0 ? round($riga['OreLavorate'] / $riga['rlp_QtaConforme'] / 60 * $riga['lp_CostoOrario'], 2) . ' [€/' . $riga['um_Sigla'] . ']' : '0' . ' [€/' . $riga['um_Sigla'] . ']',
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
	<title>PROSPECT40 | Reportistica - Analisi costi</title>
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
							<div class="row">

								<div class="col-8">
									<h4 class="card-title m-2">REPORT COSTI LAVORO</h4>
								</div>
								<div class="col-2">
									<div class="form-group m-0">
										<label for="DataInizio">Dal:</label>
										<input type="date" class="form-control form-control-sm obbligatorio dati-report" name="DataInizio"
											id="DataInizio" value="">
									</div>
								</div>
								<div class="col-2">
									<div class="form-group m-0">
										<label for="DataFine">Al:</label>
										<input type="date" class="form-control form-control-sm obbligatorio dati-report" name="DataFine"
											id="DataFine" value="">
									</div>
								</div>

							</div>
						</div>

						<div class="card-body">


							<div class="row pt-2">

								<div class="col-12">

									<div class="table-responsive mb-5">

										<!-- Tabella dettagli ordine selezionato-->
										<table id="tabellaCosti" class="table table-striped" data-source="">
											<thead>
												<tr>
													<th>Prodotto</th>
													<th>Commessa</th>
													<th>Ore lavorate </th>
													<th>Ore manutenzione</th>
													<th>Qta</th>
													<th>Tempo per prodotto</th>
													<th>Costo per prodotto</th>
												</tr>
											</thead>
											<tbody>
											</tbody>

										</table>

									</div>
								</div>

							</div>


						</div>
					</div>

				</div>
				<button class="mdi mdi-button bottone-basso-destra" id="report">CREA REPORT</button>
				<?php include("inc_footer.php") ?>

			</div>

		</div>

	</div>


	<?php include("inc_js.php") ?>

	<script src="../js/reportcosti.js"></script>


</body>

</html>