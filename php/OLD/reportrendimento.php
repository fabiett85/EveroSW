<?php
// in che pagina siamo
$pagina = 'reportrendimento';
require_once('../inc/conn.php');

include('inc_reportorganizzazione.php');
include('inc_reportlinee.php');
include('inc_reportrisorse.php');
include('inc_reportdiagnostica.php');


?>
<!DOCTYPE html>
<html lang="it">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>PROSPECT40 | Reportistica - Statistiche periodo</title>
	<?php include('inc_css.php') ?>

</head>

<body>

	<div class="container-scroller">

		<?php include('inc_testata.php') ?>

		<div class="container-fluid page-body-wrapper">

			<div class="main-panel">

				<div class="content-wrapper">

					<div class="card" id="blocco-elenco">
						<div class="card-header">
							<h4 class="card-title m-2">ANALISI RENDIMENTO</h4>
						</div>

						<div class="card-body">



							<ul class="nav nav-tabs pt-1" id="tab-statistiche" role="tablist">
								<li class="nav-item text-center" style="width: calc(100% / 4);" hidden>
									<a aria-controls="report-organizzazione" aria-selected="true" class="nav-link rounded-2"
										data-toggle="tab" href="#report-organizzazione" id="tab-report-organizzazione"
										role="tab"><b>ORGANIZZAZIONE</b></a>
								</li>
								<li class="nav-item text-center" style="width: calc(100% / 3);">
									<a aria-controls="report-produzioni" aria-selected="true" class="nav-link rounded-2" data-toggle="tab"
										href="#report-produzioni" id="tab-report-produzioni" role="tab"><b>LINEE</b></a>
								</li>
								<li class="nav-item text-center" style="width: calc(100% / 3);">
									<a aria-controls="report-risorse" aria-selected="true" class="nav-link rounded-2" data-toggle="tab"
										href="#report-risorse" id="tab-report-risorse" role="tab"><b>MACCHINE</b></a>
								</li>
								<li class="nav-item text-center" style="width: calc(100% / 3);">
									<a aria-controls="report-diagnostica" aria-selected="true" class="nav-link rounded-2"
										data-toggle="tab" href="#report-diagnostica" id="tab-report-diagnostica"
										role="tab"><b>DIAGNOSTICA</b></a>
								</li>
							</ul>

							<div class="tab-content tab-reportistica">

								<!-- Tab report RENDIMENTO ORGANIZZAZIONE -->
								<div aria-labelledby="tab-report-organizzazione" class="tab-pane fade" id="report-organizzazione"
									role="tabpanel">

									<div class="row mt-1 mx-1">

										<div class="col-8">

											<div class="form-group">
												<label for="rptOrg_LineeProduzione">Linea</label>
												<select class="form-control form-control-sm selectpicker dati-report"
													id="rptOrg_LineeProduzione" name="rptOrg_LineeProduzione" data-live-search="true" required>
													<?php
													$sth = $conn_mes->prepare(
														"SELECT * FROM linee_produzione
														WHERE lp_IdLinea != 'lin_0P' AND lp_IdLinea != 'lin_0X'
														ORDER BY lp_Descrizione ASC"
													);
													$sth->execute();
													$linee = $sth->fetchAll(PDO::FETCH_ASSOC);

													foreach ($linee as $linea) {
														echo "<option value='" . $linea['lp_IdLinea'] . "'>" . strtoupper($linea['lp_Descrizione']) . "</option>";
													}
													?>
												</select>
											</div>
										</div>
										<!--
										<div class="col-3">
										</div>
										-->
										<div class="col-2">
											<div class="form-group">
												<label for="rptOrg_DataInizio">Dal:</label>
												<input type="date" class="form-control form-control-sm obbligatorio dati-report dataInizio"
													name="rptOrg_DataInizio" id="rptOrg_DataInizio" value="">
											</div>
										</div>
										<div class="col-2">
											<div class="form-group">
												<label for="rptOrg_DataFine">Al:</label>
												<input type="date" class="form-control form-control-sm obbligatorio dati-report dataFine"
													name="rptOrg_DataFine" id="rptOrg_DataFine" value="">
											</div>
										</div>
										<div class="col-2">
										</div>
										<div class="col-2">
											<div class="form-group">
												<label for="rptOrg_OEEPeriodo">OEE [%]</label>
												<input readonly type="text"
													class="form-control form-control-sm dati-ordine dati-report sfondo-oee"
													name="rptOrg_OEEPeriodo" id="rptOrg_OEEPeriodo" value="">
											</div>
										</div>
										<div class="col-2">
											<div class="form-group">
												<label for="rptOrg_DPeriodo">(D)isponibilità [%]</label>
												<input readonly type="text"
													class="form-control form-control-sm dati-ordine dati-report sfondo-d" name="rptOrg_DPeriodo"
													id="rptOrg_DPeriodo" value="">
											</div>
										</div>
										<div class="col-2">
											<div class="form-group">
												<label for="rptOrg_EPeriodo">(E)fficienza [%]</label>
												<input readonly type="text"
													class="form-control form-control-sm dati-ordine dati-report sfondo-e" name="rptOrg_EPeriodo"
													id="rptOrg_EPeriodo" value="">
											</div>
										</div>
										<div class="col-2">
											<div class="form-group">
												<label for="rptOrg_QPeriodo">(Q)ualità [%]</label>
												<input readonly type="text"
													class="form-control form-control-sm dati-ordine dati-report sfondo-q" name="rptOrg_QPeriodo"
													id="rptOrg_QPeriodo" value="">
											</div>
										</div>

									</div>


									<!-- Tab report risorse -->
									<div class="row">
										<div class="container-grafico pl-3 pr-2">
											<canvas id="grOEEOrganizzazione" height="100%"></canvas>
										</div>
									</div>


									<div class="row">
										<div class="col-10">
										</div>
										<div class="col-2">
											<button type="button" class="mdi mdi-button" id="rptOrg-stampa-report">CREA REPORT</button>
										</div>
									</div>
								</div>


								<!-- Tab report RENDIMENTO LINEE -->
								<div aria-labelledby="tab-report-produzioni" class="tab-pane fade" id="report-produzioni"
									role="tabpanel">

									<div class="row mt-1 mx-1">
										<div class="col-4">

											<div class="form-group">
												<label for="rptLin_LineeProduzione">Linea</label>
												<select class="form-control form-control-sm selectpicker dati-report"
													id="rptLin_LineeProduzione" name="rptLin_LineeProduzione" data-live-search="true" required>
													<?php
													$sth = $conn_mes->prepare(
														"SELECT * FROM linee_produzione
														WHERE lp_IdLinea != 'lin_0P' AND lp_IdLinea != 'lin_0X'
														ORDER BY lp_Descrizione ASC"
													);
													$sth->execute();
													$linee = $sth->fetchAll(PDO::FETCH_ASSOC);

													foreach ($linee as $linea) {
														echo "<option value='" . $linea['lp_IdLinea'] . "'>" . strtoupper($linea['lp_Descrizione']) . "</option>";
													}
													?>
												</select>
											</div>
										</div>
										<div class="col-4">

											<div class="form-group">
												<label for="rptLin_Prodotti">Prodotto</label>
												<select class="form-control form-control-sm selectpicker dati-report" id="rptLin_Prodotti"
													name="rptLin_Prodotti" data-live-search="true" required>

												</select>
											</div>
										</div>
										<div class="col-2">
											<div class="form-group">
												<label for="rptLin_DataInizio">Dal:</label>
												<input type="date" class="form-control form-control-sm obbligatorio dati-report dataInizio"
													name="rptLin_DataInizio" id="rptLin_DataInizio" value="">
											</div>
										</div>
										<div class="col-2">
											<div class="form-group">
												<label for="rptLin_DataFine">Al:</label>
												<input type="date" class="form-control form-control-sm obbligatorio dati-report dataFine"
													name="rptLin_DataFine" id="rptLin_DataFine" value="">
											</div>
										</div>
										<div class="col-2">
										</div>
										<div class="col-2">
											<div class="form-group">
												<label for="rptLin_OEEMedio">OEE [%]</label>
												<input readonly type="text"
													class="form-control form-control-sm dati-ordine dati-report sfondo-oee" name="rptLin_OEEMedio"
													id="rptLin_OEEMedio" value="">
											</div>
										</div>
										<div class="col-2">
											<div class="form-group">
												<label for="rptLin_DMedio">(D)isponibilità [%]</label>
												<input readonly type="text"
													class="form-control form-control-sm dati-ordine dati-report sfondo-d" name="rptLin_DMedio"
													id="rptLin_DMedio" value="">
											</div>
										</div>
										<div class="col-2">
											<div class="form-group">
												<label for="rptLin_EMedio">(E)fficienza [%]</label>
												<input readonly type="text"
													class="form-control form-control-sm dati-ordine dati-report sfondo-e" name="rptLin_EMedio"
													id="rptLin_EMedio" value="">
											</div>
										</div>
										<div class="col-2">
											<div class="form-group">
												<label for="rptLin_QMedio">(Q)ualità [%]</label>
												<input readonly type="text"
													class="form-control form-control-sm dati-ordine dati-report sfondo-q" name="rptLin_QMedio"
													id="rptLin_QMedio" value="">
											</div>
										</div>
									</div>


									<!-- Tab report risorse -->
									<div class="row">
										<div class="container-grafico pl-3 pr-2">
											<canvas id="grOEELinea" height="100%"></canvas>
										</div>
									</div>

									<hr>

									<div class="table-responsive mb-5">

										<!-- Tabella dettagli ordine selezionato-->
										<table id="rptLin-tabella-ordini" class="table table-striped" data-source="">
											<thead>
												<tr>
													<th>Codice commessa</th>
													<th>Prodotto </th>
													<th>Qta prodotta</th>
													<th>Qta conforme</th>
													<th>Data inizio</th>
													<th>Data fine</th>
													<th>Lotto</th>
													<th>Vel. linea [pz/h]</th>
													<th>(D)isp. linea [%]</th>
													<th>(E)ffic. linea [%]</th>
													<th>(Q)ual. linea [%]</th>
													<th>OEE di linea (ordine) [%]</th>
												</tr>
											</thead>
											<tbody>
											</tbody>

										</table>

									</div>

									<div class="row">
										<div class="col-10">
										</div>
										<div class="col-2">
											<button type="button" class="mdi mdi-button" id="rptLin-stampa-report">CREA REPORT</button>
										</div>
									</div>
								</div>


								<!-- Tab report RENDIMENTO RISORSE -->
								<div aria-labelledby="tab-report-risorse" class="tab-pane fade" id="report-risorse" role="tabpanel">

									<div class="row mt-1 mx-1">
										<div class="col-2">

											<div class="form-group">
												<label for="rptRis_LineeProduzione">Linea</label>
												<select class="form-control form-control-sm selectpicker dati-report"
													id="rptRis_LineeProduzione" name="rptRis_LineeProduzione" data-live-search="true" required>
													<?php
													$sth = $conn_mes->prepare(
														"SELECT * FROM linee_produzione
														WHERE lp_IdLinea != 'lin_0P' AND lp_IdLinea != 'lin_0X'
														ORDER BY lp_Descrizione ASC"
													);
													$sth->execute();
													$linee = $sth->fetchAll();

													foreach ($linee as $linea) {
														echo "<option value='" . $linea['lp_IdLinea'] . "'>" . strtoupper($linea['lp_Descrizione']) . "</option>";
													}
													?>
												</select>
											</div>
										</div>
										<div class="col-2">
											<div class="form-group">
												<label for="rptRis_RisorseLinea">Macchina</label>
												<select class="form-control form-control-sm selectpicker dati-report" id="rptRis_RisorseLinea"
													name="rptRis_RisorseLinea" data-live-search="true" required>

												</select>
											</div>
										</div>
										<div class="col-4">
											<div class="form-group">
												<label for="rptRis_Prodotti">Prodotto</label>
												<select class="form-control form-control-sm selectpicker dati-report" id="rptRis_Prodotti"
													name="rptRis_Prodotti" data-live-search="true" required>

												</select>
											</div>
										</div>
										<div class="col-2">
											<div class="form-group">
												<label for="rptRis_DataInizio">Dal:</label>
												<input type="date" class="form-control form-control-sm obbligatorio dati-report dataInizio"
													name="rptRis_DataInizio" id="rptRis_DataInizio" value="">
											</div>
										</div>
										<div class="col-2">
											<div class="form-group">
												<label for="rptRis_DataFine">Al:</label>
												<input type="date" class="form-control form-control-sm obbligatorio dati-report dataFine"
													name="rptRis_DataFine" id="rptRis_DataFine" value="">
											</div>
										</div>
										<div class="col-2">
										</div>
										<div class="col-2">
											<div class="form-group">
												<label for="rptRis_OEEMedio">OEE [%]</label>
												<input readonly type="text" class="form-control form-control-sm dati-ordine sfondo-oee"
													name="rptRis_OEEMedio" id="rptRis_OEEMedio" value="">
											</div>
										</div>
										<div class="col-2">
											<div class="form-group">
												<label for="rptRis_DMedio">(D)isponibilità [%]</label>
												<input readonly type="text" class="form-control form-control-sm dati-ordine sfondo-d"
													name="rptRis_DMedio" id="rptRis_DMedio" value="">
											</div>
										</div>
										<div class="col-2">
											<div class="form-group">
												<label for="rptRis_EMedio">(E)fficienza [%]</label>
												<input readonly type="text" class="form-control form-control-sm dati-ordine sfondo-e"
													name="rptRis_EMedio" id="rptRis_EMedio" value="">
											</div>
										</div>
										<div class="col-2">
											<div class="form-group">
												<label for="rptRis_QMedio">(Q)ualità [%]</label>
												<input readonly type="text" class="form-control form-control-sm dati-ordine sfondo-q"
													name="rptRis_QMedio" id="rptRis_QMedio" value="">
											</div>
										</div>
									</div>


									<!-- Tab report risorse -->
									<div class="row">

										<div class="container-grafico pl-3 pr-2">
											<canvas id="grOEERisorse" height="100%"></canvas>
										</div>
									</div>

									<hr>

									<div class="table-responsive mb-5">

										<!-- Tabella dettagli ordine selezionato-->
										<table id="rptRis-tabella-ordini" class="table table-striped" data-source="">
											<thead>
												<tr>
													<th>Codice commessa</th>
													<th>Prodotto </th>
													<th>Qta prodotta</th>
													<th>Qta conforme</th>
													<th>Data inizio</th>
													<th>Data fine</th>
													<th>Vel. ris. [pz/h]</th>
													<th>(D)isp. ris. [%]</th>
													<th>(E)ffic. ris. [%]</th>
													<th>(Q)ual. ris. [%]</th>
													<th>OEE di ris. (ordine) [%]</th>
													<th>OEE di ris. (media prodotto) [%]</th>
												</tr>
											</thead>
											<tbody>
											</tbody>

										</table>

									</div>

									<div class="row">
										<div class="col-10">
										</div>
										<div class="col-2">
											<button type="button" class="mdi mdi-button" id="rptRis-stampa-report">CREA REPORT</button>
										</div>
									</div>

								</div>


								<!-- Tab report RENDIMENTO DIAGNOSTICA -->
								<div aria-labelledby="tab-report-diagnostica" class="tab-pane fade" id="report-diagnostica"
									role="tabpanel">

									<div class="row mt-1 mx-1">
										<div class="col-4">

											<div class="form-group">
												<label for="rptDia_LineeProduzione">Linea</label>
												<select class="form-control form-control-sm selectpicker dati-report"
													id="rptDia_LineeProduzione" name="rptDia_LineeProduzione" data-live-search="true" required>
													<?php
													$sth = $conn_mes->prepare(
														"SELECT * FROM linee_produzione
														WHERE lp_IdLinea != 'lin_0P' AND lp_IdLinea != 'lin_0X'
														ORDER BY lp_Descrizione ASC"
													);
													$sth->execute();
													$linee = $sth->fetchAll(PDO::FETCH_ASSOC);

													echo "<option value='_'>TUTTE</option>";
													foreach ($linee as $linea) {
														echo "<option value='" . $linea['lp_IdLinea'] . "'>" . strtoupper($linea['lp_Descrizione']) . "</option>";
													}
													?>
												</select>
											</div>
										</div>
										<div class="col-4">
											<div class="form-group">
												<label for="rptDia_RisorseLinea">Macchina</label>
												<select class="form-control form-control-sm selectpicker dati-report" id="rptDia_RisorseLinea"
													name="rptDia_RisorseLinea" data-live-search="true" required>

												</select>
											</div>
										</div>
										<div class="col-2">
											<div class="form-group">
												<label for="rptDia_DataInizio">Dal:</label>
												<input type="date" class="form-control form-control-sm obbligatorio dati-report dataInizio"
													name="rptDia_DataInizio" id="rptDia_DataInizio" value="">
											</div>
										</div>
										<div class="col-2">
											<div class="form-group">
												<label for="rptDia_DataFine">Al:</label>
												<input type="date" class="form-control form-control-sm obbligatorio dati-report dataFine"
													name="rptDia_DataFine" id="rptDia_DataFine" value="">
											</div>
										</div>
										<div class="col-6">
										</div>
									</div>


									<!-- Tab report risorse -->
									<div class="row">
										<div class="container-grafico pl-3 pr-2">
											<canvas id="grDiagnosticaLinee" height="70%"></canvas>
										</div>
									</div>


									<div class="table-responsive mb-5">

										<!-- Tabella dettagli ordine selezionato-->
										<table id="rptDia-tabella-diagnostica" class="table table-striped" data-source="">
											<thead>
												<tr>
													<th id="intestazione-th1">Tipologia</th>
													<th>Totale tempo improduttivo [min] </th>
												</tr>
											</thead>
											<tbody>
											</tbody>

										</table>

									</div>


									<div class="row">
										<div class="col-10">
										</div>
										<div class="col-2">
											<button type="button" class="mdi mdi-button" id="rptDia-stampa-report">CREA REPORT</button>
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



	<!-- REPORT ORGANIZZAZIONE: popup modale di visualizzazione ordini per il giorno selezionato -->
	<div class="modal fade" id="modal-report-organizzazione" tabindex="-1" role="dialog"
		aria-labelledby="modal-report-oganizzazione-label" aria-hidden="true">
		<div class="modal-dialog modal-lg" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modal-report-organizzazione-int1">COMMESSE ESEGUITE IN DATA:</h5>
					<h5 class="modal-title ml-2" id="modal-organizzazione-label-data"></h5>
					<h5 class="modal-title ml-2" id="modal-report-organizzazione-label-int2">SU LINEA:</h5>
					<h5 class="modal-title ml-2" id="modal-organizzazione-label-descLinea"></h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<div class="container-fluid">

						<div class="table-responsive">

							<!-- Tabella dettagli ordine selezionato-->
							<table id="rptOrg-tabella-ordini-giorno" class="table table-striped" data-source="">
								<thead>
									<tr>
										<th>Codice commessa</th>
										<th>Prodotto </th>
										<th>Data inizio</th>
										<th>Data fine</th>
										<th>Qta prodotta</th>
										<th>Qta conforme</th>
										<th>(D)isp. linea [%]</th>
										<th>(E)ffic. linea [%]</th>
										<th>(Q)ual. linea [%]</th>
										<th>OEE linea [%]</th>

									</tr>
								</thead>
								<tbody>
								</tbody>

							</table>

						</div>
					</div>
				</div>

				<div class="modal-footer">
					<!-- <button type="button" class="btn btn-secondary" id="rptOrg-stampa-report-dettaglio">Report</button> -->
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Chiudi</button>
				</div>

			</div>
		</div>
	</div>


	<!-- REPORT LINEA: popup modale di visualizzazione dettagli produzione selezionata -->
	<div class="modal fade" id="modal-report-linea" tabindex="-1" role="dialog" aria-labelledby="modal-report-linea-label"
		aria-hidden="true">
		<div class="modal-dialog modal-lg" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modal-report-linea-int1"> DETTAGLIO COMMESSA:</h5>
					<h5 class="modal-title ml-2" id="modal-linea-label-codOrdine"></h5>
					<h5 class="modal-title ml-2" id="modal-report-linea-label-int2">PER LINEA:</h5>
					<h5 class="modal-title ml-2" id="modal-linea-label-descLinea"></h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<div class="container-fluid">

						<div class="table-responsive mb-5 mt-2">

							<!-- Tabella dettagli ordine selezionato-->
							<table id="rptLin-tabella-ordine-selezionato" class="table table-striped" data-source="">
								<thead>
									<tr>
										<th>Codice commessa</th>
										<th>Prodotto </th>
										<th>Data inizio</th>
										<th>Data fine</th>
										<th>Qta prodotta</th>
										<th>Qta conforme</th>
										<th>Lotto</th>
										<th>T. Tot [min]</th>
										<th>T. Down [min]</th>
										<th>T. Attr. [min]</th>
										<th>V. linea [pz/h]</th>
										<th>(D)isp. linea [%]</th>
										<th>(E)ffic. linea [%]</th>
										<th>(Q)ual. linea [%]</th>
										<th>OEE linea [%]</th>

									</tr>
								</thead>
								<tbody>
								</tbody>

							</table>

						</div>


						<!-- Tab per visualizzazione dettagli produzione -->
						<ul class="nav nav-tabs rptLin-tabs-linea" id="tab-elenchi" role="tablist">
							<li class="nav-item text-center" style="width: calc(100% / 4);">
								<a aria-controls="rptLin-risorse" aria-selected="true" class="nav-link active show" data-toggle="tab"
									href="#rptLin-risorse" id="rptLin-tab-risorse" role="tab"><b>ELENCO MACCHINE</b></a>
							</li>
							<li class="nav-item text-center" style="width: calc(100% / 4);">
								<a aria-controls="rptLin-componenti" aria-selected="true" class="nav-link" data-toggle="tab"
									href="#rptLin-componenti" id="rptLin-tab-componenti" role="tab"><b>ELENCO COMPONENTI</b></a>
							</li>
							<li class="nav-item text-center" style="width: calc(100% / 4);">
								<a aria-controls="rptLin-casi-cumulativo" aria-selected="true" class="nav-link" data-toggle="tab"
									href="#rptLin-casi-cumulativo" id="rptLin-tab-casi-cumulativo" role="tab"><b>CUMULATIVO EVENTI</b></a>
							</li>
							<li class="nav-item text-center" style="width: calc(100% / 4);">
								<a aria-controls="rptLin-casi" aria-selected="true" class="nav-link" data-toggle="tab"
									href="#rptLin-casi" id="rptLin-tab-casi" role="tab"><b>DETTAGLIO EVENTI</b></a>
							</li>
							<!--
							<li class="nav-item text-center" style = "width: calc(100% / 4);">
								<a aria-controls="rptLin-casi" aria-selected="true" class="nav-link" data-toggle="tab" href="#rptLin-downtime" id="rptLin-tab-downtime" role="tab"><b>DETTAGLIO DOWNTIME</b></a>
							</li>
							-->
						</ul>

						<div class="tab-content rptLin-tabs-linea">

							<div aria-labelledby="rptLin-tab-risorse" class="tab-pane fade show active" id="rptLin-risorse"
								role="tabpanel">

								<div class="table-responsive">

									<table id="rptLin-tabella-risorse-coinvolte" class="table table-striped" style="width:100%"
										data-source="">
										<thead>
											<tr>
												<th>Macchina</th>
												<th>Orario inizio</th>
												<th>Orario fine</th>
												<th>T. Tot. [min]</th>
												<th>Downtime [min]</th>
												<th>T. Attr. [min]</th>
												<th>Δ T. Attr. [min]</th>
												<th>Vel. reale [pz/h]</th>
												<th>OEE ris. [%]</th>
											</tr>
										</thead>
										<tbody></tbody>

									</table>

								</div>
							</div>

							<div aria-labelledby="rptLin-tab-componenti" class="tab-pane fade" id="rptLin-componenti" role="tabpanel">

								<div class="table-responsive">

									<table id="rptLin-tabella-componenti" class="table table-striped" style="width:100%" data-source="">
										<thead>
											<tr>
												<th>Codice componente</th>
												<th>Componente</th>
												<th>Qta</th>
											</tr>
										</thead>
										<tbody></tbody>

									</table>

								</div>

							</div>

							<div aria-labelledby="rptLin-tab-casi-cumulativo" class="tab-pane fade" id="rptLin-casi-cumulativo"
								role="tabpanel">

								<div class="table-responsive">

									<table id="rptLin-tabella-casi-cumulativo" class="table table-striped" style="width:100%"
										data-source="">
										<thead>
											<th>Macchina</th>
											<th>Descrizione evento</th>
											<th>Tipo</th>
											<th>N° eventi</th>
											<th>Durata [min]</th>
											</tr>
										</thead>
										<tbody></tbody>

									</table>

								</div>

							</div>

							<div aria-labelledby="rptLin-tab-casi" class="tab-pane fade" id="rptLin-casi" role="tabpanel">

								<div class="table-responsive">

									<table id="rptLin-tabella-casi" class="table table-striped" style="width:100%" data-source="">
										<thead>
											<tr>
												<th>Macchina</th>
												<th>Evento</th>
												<th>Tipo</th>
												<th>Orario inizio</th>
												<th>Orario fine</th>
												<th>Durata [min]</th>
												<th>Note e segnalazioni</th>
											</tr>
										</thead>
										<tbody></tbody>

									</table>

								</div>

							</div>

							<div aria-labelledby="rptLin-tab-downtime" class="tab-pane fade" id="rptLin-downtime" role="tabpanel">

								<div class="table-responsive">

									<table id="rptLin-tabella-downtime" class="table table-striped" style="width:100%" data-source="">
										<thead>
											<tr>
												<th>Macchina</th>
												<th>Orario inizio</th>
												<th>Orario fine</th>
												<th>Durata [min]</th>
											</tr>
										</thead>
										<tbody></tbody>

									</table>

								</div>

							</div>

						</div>

					</div>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" id="rptLin-stampa-report-dettaglio">Report</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Chiudi</button>
				</div>

			</div>
		</div>
	</div>


	<!-- REPORT RISORSA: popup modale di visualizzazione dettagli produzione selezionata -->
	<div class="modal fade" id="modal-report-risorsa" tabindex="-1" role="dialog"
		aria-labelledby="modal-report-risorsa-label" aria-hidden="true">
		<div class="modal-dialog modal-lg" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modal-report-risorsa-int1"> DETTAGLIO COMMESSA:</h5>
					<h5 class="modal-title ml-2" id="modal-risorsa-label-codOrdine"></h5>
					<h5 class="modal-title ml-2" id="modal-report-risorsa-label-int2">PER MACCHINA:</h5>
					<h5 class="modal-title ml-2" id="modal-risorsa-label-descRisorsa"></h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<div class="container-fluid">

						<div class="table-responsive mb-5 mt-2">

							<!-- Tabella dettagli ordine selezionato-->
							<table id="rptRis-tabella-ordine-selezionato" style="width:100%" class="table table-striped"
								data-source="">
								<thead>
									<tr>
										<th>Codice commessa</th>
										<th>Prodotto </th>
										<th>Data inizio</th>
										<th>Data fine</th>
										<th>Qta prodotta</th>
										<th>Qta conforme</th>
										<th>T. Tot [min]</th>
										<th>T. Down [min]</th>
										<th>T. Attr. [min]</th>
										<th>Δ T. Attr. [min]</th>
										<th>V. ris. [pz/h]</th>
										<th>(D)isp. ris. [%]</th>
										<th>(E)ffic. ris. [%]</th>
										<th>(Q)ual. ris. [%]</th>
										<th>OEE ris. [%]</th>
									</tr>
								</thead>
								<tbody>
								</tbody>

							</table>

						</div>


						<!-- Tab per visualizzazione dettagli produzione -->
						<ul class="nav nav-tabs tabs-risorse" id="tab-dettagli-risorsa" role="tablist">
							<li class="nav-item text-center" style="width: calc(100% / 2);">
								<a aria-controls="rptRis--cumulativo" aria-selected="true" class="nav-link" data-toggle="tab"
									href="#rptRis-casi-cumulativo" id="rptRis-tab-casi-cumulativo" role="tab"><b>CUMULATIVO EVENTI</b></a>
							</li>
							<li class="nav-item text-center" style="width: calc(100% / 2);">
								<a aria-controls="rptRis-casi" aria-selected="true" class="nav-link active show" data-toggle="tab"
									href="#rptRis-casi" id="rptRis-tab-casi" role="tab"><b>DETTAGLIO EVENTI</b></a>
							</li>
							<!--
							<li class="nav-item text-center" style = "width: calc(100% / 2);">
								<a aria-controls="rptRis-downtime" aria-selected="true" class="nav-link" data-toggle="tab" href="#rptRis-downtime" id="rptRis-tab-downtime" role="tab"><b>DETTAGLIO DOWNTIME</b></a>
							</li>
							-->
						</ul>

						<div class="tab-content tabs-risorse">

							<div aria-labelledby="rptRis-tab-casi-cumulativo" class="tab-pane fade" id="rptRis-casi-cumulativo"
								role="tabpanel">

								<div class="table-responsive">

									<table id="rptRis-tabella-casi-cumulativo" class="table table-striped" style="width:100%"
										data-source="">
										<thead>
											<th>Macchina</th>
											<th>Descrizione evento</th>
											<th>Tipo</th>
											<th>N° eventi</th>
											<th>Durata [min]</th>
											</tr>
										</thead>
										<tbody></tbody>

									</table>

								</div>

							</div>

							<div aria-labelledby="rptRis-tab-casi" class="tab-pane fade show active" id="rptRis-casi" role="tabpanel">

								<div class="table-responsive">

									<table id="rptRis-tabella-casi" class="table table-striped" style="width:100%" data-source="">
										<thead>
											<tr>
												<th>Macchina</th>
												<th>Evento</th>
												<th>Tipo</th>
												<th>Orario inizio</th>
												<th>Orario fine</th>
												<th>Durata [min]</th>
												<th>Note e segnalazioni</th>
											</tr>
										</thead>
										<tbody></tbody>

									</table>

								</div>

							</div>

							<div aria-labelledby="rptRis-tab-downtime" class="tab-pane fade" id="rptRis-downtime" role="tabpanel">

								<div class="table-responsive">

									<table id="rptRis-tabella-downtime" class="table table-striped" style="width:100%" data-source="">
										<thead>
											<tr>
												<th>Macchina</th>
												<th>Orario inizio</th>
												<th>Orario fine</th>
												<th>Durata [min]</th>
											</tr>
										</thead>
										<tbody></tbody>

									</table>

								</div>

							</div>

						</div>

					</div>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" id="rptRis-stampa-report-dettaglio">Report</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Chiudi</button>
				</div>

			</div>
		</div>
	</div>

	<!-- REPORT DIAGNOSTICA RISORSA: popup modale di visualizzazione dettaglio downtime per la risorsa e il periodo selezionati -->
	<div class="modal fade" id="modal-report-diagnostica-risorsa" tabindex="-1" role="dialog"
		aria-labelledby="modal-report-diagnostica-risorsa-label" aria-hidden="true">
		<div class="modal-dialog modal-lg" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modal-report-diagnostica-risorsa-int1">DETTAGLIO DOWNTIME: </h5>
					<h5 class="modal-title ml-2" id="modal-diagnostica-risorsa-label-descRisorsa"></h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<div class="container-fluid">
						<!-- Grafico dettaglio DOWNTIME RISORSA -->
						<div class="row">
							<div class="container-grafico pl-3 pr-2">
								<canvas id="grDettaglioDowntimeRisorsa" height="80%"></canvas>
							</div>
						</div>
					</div>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" id="rptDia-stampa-report-dettaglio">Report</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Chiudi</button>
				</div>

			</div>
		</div>
	</div>

	<?php include('inc_js.php') ?>

	<script src="../js/reportorganizzazione.js"></script>
	<script src="../js/reportlinee.js"></script>
	<script src="../js/reportrisorse.js"></script>
	<script src="../js/reportdiagnostica.js"></script>
	<script src="../js/reportgestionetab.js"></script>
	<!-- <script src="../js/reportrendimento.js"></script> -->



</body>

</html>