<?php
// in che pagina siamo
$pagina = 'reportmisure';
require_once("../inc/conn.php");



?>
<!DOCTYPE html>
<html lang="it">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>PROSPECT40 | Reportistica - Analisi misure</title>
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
							<!-- Tab report VISUALIZZAZIONE MISURE -->
							<div class="row">
								<div class="col-2">
									<h4 class="card-title m-2">ANALISI MISURE</h4>
								</div>

								<div class="col-2">
									<div class="form-group m-0">
										<label for="rptMis_RisorseLinea">Macchina</label>
										<select class="form-control form-control-sm selectpicker dati-report" id="rptMis_RisorseLinea"
											name="rptMis_RisorseLinea" data-live-search="true">
											<?php
											$sth = $conn_mes->prepare(
												"SELECT ris_IdRisorsa, ris_Descrizione, lp_Descrizione FROM risorse
												LEFT JOIN linee_produzione ON ris_LineaProduzione = lp_IdLinea
												ORDER BY lp_Descrizione ASC, ris_Descrizione ASC"
											);
											$sth->execute();
											$risorse = $sth->fetchAll(PDO::FETCH_ASSOC);

											foreach ($risorse as $risorsa) {
												echo "<option value='" . $risorsa['ris_IdRisorsa'] . "'>" . strtoupper($risorsa['lp_Descrizione'] . " - " . $risorsa['ris_Descrizione']) . "</option>";
											}
											?>
										</select>
									</div>
								</div>
								<div class="col-2">
									<div class="form-group m-0">
										<label for="rptMis_Misure">Misure disponibili</label>
										<select class="form-control form-control-sm selectpicker dati-report" id="rptMis_Misure"
											name="rptMis_Misure" multiple data-live-search="true">

										</select>
									</div>
								</div>
								<div class="col-2">
									<div class="form-group m-0">
										<label for="rptMis_Commesse">Commessa</label>
										<select class="form-control form-control-sm selectpicker dati-report" id="rptMis_Commesse"
											name="rptMis_Commesse" data-live-search="true">

										</select>
									</div>
								</div>
								<div class="col-2">
									<div class="form-group m-0">
										<label for="rptMis_DataInizio">Dal:</label>
										<input type="date" class="form-control form-control-sm obbligatorio dati-report"
											name="rptMis_DataInizio" id="rptMis_DataInizio" value="">
									</div>
								</div>
								<div class="col-2">
									<div class="form-group m-0">
										<label for="rptMis_DataFine">Al:</label>
										<input type="date" class="form-control form-control-sm obbligatorio dati-report"
											name="rptMis_DataFine" id="rptMis_DataFine" value="">
									</div>
								</div>

							</div>
						</div>

						<div class="card-body">





							<!-- Tab report risorse -->
							<div class="row">

								<div class="container-grafico pl-3 pr-2" style="color: #ffffff !important;">
									<canvas id="grMisure" height="90%" style="color: #ffffff !important;"></canvas>
								</div>
							</div>

							<div class="row">
								<div class="col-10">
								</div>
								<div class="col-2">
									<button type="button" class="mdi mdi-button" id="rptMis-stampa-report">CREA REPORT</button>
								</div>
							</div>

							<hr>

							<div class="table-responsive mb-5">

								<!-- Tabella dettagli ordine selezionato-->
								<table id="rptMis-tabella-misure" class="table table-striped" data-source="">
									<thead>
										<tr>

											<th>Misura</th>
											<th>Macchina </th>
											<th>Commessa</th>
											<th>Valore</th>
											<th>Data ora</th>
											<th></th>
										</tr>
									</thead>
									<tbody>
									</tbody>

								</table>

							</div>




						</div>
					</div>

				</div>

				<?php include("inc_footer.php") ?>

			</div>

		</div>

	</div>


	<?php include("inc_js.php") ?>

	<script src="../js/reportmisure.js"></script>


</body>

</html>