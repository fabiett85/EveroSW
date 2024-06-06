<?php
// in che pagina siamo
$pagina = "dashboard";

include("../inc/conn.php");

if (isset($_REQUEST['azione'])) {
	$azione = $_REQUEST['azione'];
	unset($_REQUEST['azione']);
	unset($_REQUEST['_']);

	if ($azione == 'mostra-lavori') {

		$stringa = '';
		$stati = explode(',', $_REQUEST['stati']);
		if (empty($stati)) {
			die(json_encode([]));
		}
		foreach ($stati as $stato) {
			$stringa .= intval($stato) . ',';
		}
		$stringa = trim($stringa, ',');
		unset($_REQUEST['stati']);

		$sth = $conn_mes->prepare(
			"SELECT * FROM ordini_produzione
			LEFT JOIN risorse_coinvolte ON rc_IdProduzione = op_IdProduzione
			WHERE op_LineaProduzione LIKE :idLinea AND rc_IdRisorsa LIKE :idRisorsa AND op_Stato IN ($stringa)"
		);
		$sth->execute($_REQUEST);
		$lavori = $sth->fetchAll();
		$output = [];
		foreach ($lavori as $lavoro) {
			$inizio = 'ND';
			if (isset($lavoro['rc_DataOraInizio'])) {
				$inizio = (new DateTime($lavoro['rc_DataOraInizio']))->format('d/m/Y H:i');
			} elseif (isset($lavoro['op_DataProduzione'])) {
				$inizio = (new DateTime($lavoro['op_DataProduzione'] . ' ' . $lavoro['op_OraProduzione']))->format('d/m/Y H:i');
			}

			$fine = 'ND';
			if (isset($lavoro['rc_DataOraFine'])) {
				$fine = (new DateTime($lavoro['rc_DataOraFine']))->format('d/m/Y H:i');
			} elseif (isset($lavoro['op_DataFineTeorica'])) {
				$fine = (new DateTime($lavoro['op_DataFineTeorica'] . ' ' . $lavoro['op_OraFineTeorica']))->format('d/m/Y H:i');
			}

			$consegna = 'ND';
			if (isset($lavoro['op_DataConsegna'])) {
				$consegna = (new DateTime($lavoro['op_DataConsegna']))->format('d/m/Y');
			}

			$output[] = [
				'op_IdProduzione' => $lavoro['op_IdProduzione'],
				'op_DataConsegna' => $consegna,
				'op_QtaDaProdurre' => $lavoro['op_QtaDaProdurre'],
				'inizio' => $inizio,
				'fine' => $fine,
				'op_NoteProduzione' => $lavoro['op_NoteProduzione'],
			];
		}
		die(json_encode($output));
	}

	if ($azione = 'select-risorse') {
		$sth = $conn_mes->prepare(
			"SELECT * FROM risorse
			WHERE ris_LineaProduzione = :idLinea"
		);
		$sth->execute($_REQUEST);
		die(json_encode($sth->fetchAll()));
	}
}


?>
<!DOCTYPE html>
<html lang="it">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>PROSPECT40 | Calendario macchine</title>
	<?php include("inc_css.php") ?>
</head>

<body class="<?= $classe_body_zoom ?>" <?= $data_body_zoom ?>>

	<div class="container-scroller">

		<?php include("inc_testata.php") ?>

		<div class="container-fluid page-body-wrapper">

			<div class="main-panel">

				<div class="content-wrapper">

					<div class="card" style="min-height: 100%;">

						<div class="card-header">
							<div class="row">
								<div class="col-4">
									<h4 class="card-title m-2">CALENDARIO MACCHINE</h4>
								</div>
								<div class="col-2">
									<div class="form-group m-0">
										<label for="op_Stato">Stati</label>
										<select class="form-control form-control-sm selectpicker" id="op_Stato" name="op_Stato" multiple>
											<option value="1">MEMO</option>
											<option value="2">ATTIVO</option>
											<option value="3">CARICATO</option>
											<option value="4">AVVIATO</option>
										</select>
									</div>
								</div>
								<div class="col-3">
									<div class="form-group m-0">
										<label for="op_LineaProduzione">Linee</label>
										<select class="form-control form-control-sm selectpicker" id="op_LineaProduzione"
											name="op_LineaProduzione" data-live-search="true">
											<?php
											$sth = $conn_mes->prepare(
												"SELECT linee_produzione.* FROM linee_produzione
													WHERE linee_produzione.lp_IdLinea != 'lin_0P'
													AND linee_produzione.lp_IdLinea != 'lin_0X'
													ORDER BY linee_produzione.lp_Descrizione ASC"
											);
											$sth->execute();
											$linee = $sth->fetchAll(PDO::FETCH_ASSOC);
											echo "<option value='%'>TUTTE</option>";
											if ($linee) {
												foreach ($linee as $linea) {
													echo "<option value='" . $linea['lp_IdLinea'] . "'>" . strtoupper($linea['lp_Descrizione']) . "</option>";
												}
											} else {
												echo "<option value=''>Nessuna linea definita</option>";
											}
											?>
										</select>
									</div>
								</div>
								<div class="col-3">
									<div class="form-group m-0">
										<label for="rc_IdRisorsa">Macchine</label>
										<select class="form-control form-control-sm selectpicker" id="rc_IdRisorsa" name="rc_IdRisorsa"
											data-live-search="true" required>
											<option value="%">TUTTE</option>
										</select>
									</div>
								</div>
							</div>
						</div>
						<div class="card-body">
							<div class="table-responsive pt-1">

								<table id="tabellaLavori" class="table table-striped">
									<thead>
										<tr>
											<th>Lavoro</th>
											<th>Data consegna</th>
											<th>Qta</th>
											<th>Inizio previsto</th>
											<th>Fine prevista</th>
											<th>Note</th>
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

	</div>

	<button class="mdi mdi-button bottone-basso-destra" id="stampa-lavori">STAMPA</button>

	<?php include("inc_js.php") ?>
	<script src="../js/calendariomacchine.js"></script>

</body>

</html>