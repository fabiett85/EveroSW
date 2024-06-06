<?php
// in che pagina siamo
$pagina = "dizionariomisure";

include("../inc/conn.php");

// : VISUALIZZAZIONE PRODOTTI MAGAZZINO
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "mostra") {

	if (isset($_REQUEST['idRisorsa'])) {
		// estraggo la lista
		$sth = $conn_mes->prepare(
			"SELECT misure.*, risorse.ris_Descrizione FROM misure
			LEFT JOIN risorse ON misure.mis_IdRisorsa = risorse.ris_IdRisorsa
			WHERE mis_IdRisorsa LIKE :IdRisorsa"
		);
		$sth->execute(array(":IdRisorsa" => $_REQUEST['idRisorsa']));
	} else {
		// estraggo la lista
		$sth = $conn_mes->prepare(
			"SELECT misure.*, risorse.ris_Descrizione FROM misure
			LEFT JOIN risorse ON misure.mis_IdRisorsa = risorse.ris_IdRisorsa"
		);
		$sth->execute();
	}

	$output = array();
	$righe = $sth->fetchAll(PDO::FETCH_ASSOC);

	if ($righe) {


		$marked = '<div class="d-flex justify-content-center align-items-center" style="max-height:18px"><i class="mdi mdi-checkbox-marked mdi-18px"></i></div>';
		$unmarked = '<div class="d-flex justify-content-center align-items-center" style="max-height:18px"><span class="mdi mdi-checkbox-blank-outline"></span></div>';


		foreach ($righe as $riga) {
			//Preparo i dati da visualizzare
			$output[] = array(
				"DescrizioneRisorsa" => $riga["ris_Descrizione"],
				"IdMisura" => $riga["mis_IdMisura"],
				"DescrizioneMisura" => $riga["mis_Descrizione"],
				"UdmMisura" => $riga["mis_Udm"],
				"AbiLetturaIstantanea" => ($riga["mis_AbiLetturaIstantanea"] == 1 ? $marked : $unmarked),
				"AbiTracciamento" => ($riga["mis_AbiTracciamento"] == 1 ? $marked : $unmarked),
				"azioni" => '<div class="dropdown">
									<button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Modifica riga">
									<span class="mdi mdi-lead-pencil mdi-18px"></span>
									</button>
									<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
										<a class="dropdown-item modifica-misura" data-id_riga="' . $riga["mis_IdRiga"] . '"><i class="mdi mdi-account-edit"></i> Modifica</a>
									</div>
								</div>'

			);
		}
		die(json_encode(['data' => $output]));
	} else {
		die(json_encode(['data' => []]));
	}
}


// DIZIONARIO CASI: RECUPERO VALORI DELLA RISORSA SELEZIONATA
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "recupera" && !empty($_REQUEST["codice"])) {
	// estraggo la lista
	$sth = $conn_mes->prepare(
		"SELECT * FROM misure
		WHERE mis_IdRiga = :codice"
	);
	$sth->execute(array(":codice" => $_REQUEST["codice"]));
	$riga = $sth->fetch(PDO::FETCH_ASSOC);

	die(json_encode($riga));
}




// DIZIONARIO CASI: GESTIONE SALVATAGGIO DATI DA POPUP MODIFICA/INSERIMENTO
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "salva-misura" && !empty($_REQUEST["data"])) {
	// recupero i parametri dal POST
	$parametri = array();
	parse_str($_REQUEST["data"], $parametri);

	$statoAbiLetturaIstantanea = $_REQUEST["flagAbiLetturaIstantanea"];
	$statoAbiTraccciamento = $_REQUEST["flagAbiTracciamento"];


	// se devo modificare
	if ($parametri["azione"] == "modifica") {

		$id_modifica = $parametri["mis_IdRiga"];

		$sthUpdateInsert = $conn_mes->prepare(
			"UPDATE misure SET
			mis_Descrizione = :DescrizioneMisura,
			mis_Udm = :UdmMisura,
			mis_AbiLetturaIstantanea = :AbiLetturaInstantaneaMisura,
			mis_AbiTracciamento = :AbiTracciamentoMisura
			WHERE mis_IdRiga = :IdRiga"
		);
		$sthUpdateInsert->execute(array(
			":DescrizioneMisura" => $parametri["mis_Descrizione"],
			":UdmMisura" => $parametri["mis_Udm"],
			":AbiLetturaInstantaneaMisura" => $statoAbiLetturaIstantanea,
			":AbiTracciamentoMisura" => $statoAbiTraccciamento,
			":IdRiga" => $id_modifica
		));

		// SINTESI ESITO OPERAZIONI
		// in base ai valori ritornati dall'esecuzione delle query, eseguo commit/rollout della transazione SQL
		// definisco transazione SQL

		if ($sthUpdateInsert) {
			die("OK");
		} else {
			die("ERRORE");
		}
	}
}







?>
<!DOCTYPE html>
<html lang="it">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>PROSPECT40 | Gestione archivi</title>
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
								<div class="col-10">
									<h4 class="card-title mx-2 my-2">ELENCO MISURE GESTITE</h4>
								</div>
								<div class="col-2">
									<!-- ELENCO PRODOTTI FINITI -->
									<div class="form-group m-0">
										<label for="mis_FiltroRisorse">Filtra macchina</label>
										<select class="form-control form-control-sm selectpicker" id="mis_FiltroRisorse" name="mis_FiltroRisorse" data-live-search="true" required>
											<?php
											$sth = $conn_mes->prepare(
												"SELECT risorse.*
												FROM risorse"
											);
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
							</div>
						</div>

						<div class="card-body">



							<div class="row">

								<div class="col-12">
									<div class="table-responsive">

										<table id="tabellaDati-misure" class="table table-striped" style="width:100%" data-source="dizionariomisure.php?azione=mostra">
											<thead>
												<tr>
													<th>Macchina</th>
													<th>Id misura</th>
													<th>Descrizione misura</th>
													<th>Udm</th>
													<th>Lettura istantanea</th>
													<th>Registra trend</th>
													<th>Azioni</th>
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

				<?php include("inc_footer.php") ?>

			</div>

		</div>

	</div>


	<!-- Opup modale di modifica/inserimento MISURA-->
	<div class="modal fade" id="modal-misura" tabindex="-1" role="dialog" aria-labelledby="modal-misura-label" aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modal-misura-label">Nuova misura</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form class="forms-sample" id="form-misura">

						<div class="row">
							<div class="col-9">
								<div class="form-group">
									<label for="mis_IdRisorsa">Macchina</label><span style='color:red'> *</span>
									<select class="form-control form-control-sm selectpicker" id="mis_IdRisorsa" name="mis_IdRisorsa">
										<?php
										$sth = $conn_mes->prepare("SELECT risorse.ris_IdRisorsa, risorse.ris_Descrizione
																		FROM risorse
																		ORDER BY risorse.ris_IdRisorsa ASC", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
										$sth->execute();
										$linee = $sth->fetchAll(PDO::FETCH_ASSOC);

										foreach ($linee as $linea) {
											echo "<option value='" . $linea['ris_IdRisorsa'] . "'>" . strtoupper($linea['ris_Descrizione']) . "</option>";
										}
										?>
									</select>
								</div>
							</div>
							<div class="col-3">
								<div class="form-group">
									<label for="mis_IdMisura">Codice misura</label><span style='color:red'> *</span>
									<input type="text" class="form-control form-control-sm dati-popup-modifica obbligatorio" name="mis_IdMisura" id="mis_IdMisura" autocomplete="off">
								</div>
							</div>
							<div class="col-9">
								<div class="form-group">
									<label for="mis_Descrizione">Descrizione misura</label><span style='color:red'> *</span>
									<input type="text" class="form-control form-control-sm dati-popup-modifica obbligatorio" name="mis_Descrizione" id="mis_Descrizione" autocomplete="off">
								</div>
							</div>
							<div class="col-3">
								<div class="form-group">
									<label for="mis_Udm">Udm</label><span style='color:red'> *</span>
									<input type="text" class="form-control form-control-sm dati-popup-modifica obbligatorio" name="mis_Udm" id="mis_Udm" autocomplete="off">
								</div>
							</div>
							<div class="col-6">
								<div class="form-check pt-4">
									<input id="mis_AbiLetturaIstantanea" type="checkbox">
									<label for="mis_AbiLetturaIstantanea" style="font-weight: normal;">Lettura istantanea</label>
								</div>
							</div>
							<div class="col-6">
								<div class="form-check pt-4">
									<input id="mis_AbiTracciamento" type="checkbox">
									<label for="mis_AbiTracciamento" style="font-weight: normal;">Trend misura</label>
								</div>
							</div>
						</div>

						<input type="hidden" id="mis_IdRiga" name="mis_IdRiga" value="">
						<input type="hidden" id="azione" name="azione" value="nuovo">

					</form>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-success" id="salva-misura">Salva</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
				</div>

			</div>
		</div>
	</div>


	<?php include("inc_js.php") ?>
	<script src="../js/dizionariomisure.js"></script>

</body>

</html>