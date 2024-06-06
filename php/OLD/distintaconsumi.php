<?php
// in che pagina siamo
$pagina = "distintaconsumi";

include("../inc/conn.php");


// D. MACCHINE: RECUPERO INFORMAZIONI SU COMPONENTE DISTINTA IN OGGETTO
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "recupera-dc" && !empty($_REQUEST["idConsumo"]) && !empty($_REQUEST["idRisorsa"])) {
	// recupero i dati del dettaglio distinta selezionato
	$sthRecuperaDettaglio = $conn_mes->prepare(
		"SELECT * FROM distinta_consumi
		WHERE dc_IdTipoConsumo = :IdTipoConsumo
		AND dc_IdRisorsa = :IdRisorsa"
	);
	$sthRecuperaDettaglio->execute([
		":IdTipoConsumo" => $_REQUEST["idConsumo"],
		":IdRisorsa" => $_REQUEST["idRisorsa"]
	]);
	$riga = $sthRecuperaDettaglio->fetch(PDO::FETCH_ASSOC);

	die(json_encode($riga));
}


// D. MACCHINE: VISUALIZZAZIONE DETTAGLIO DISTINTA
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "mostraDC") {

	$sth = $conn_mes->prepare(
		"SELECT DC.*, TC.*, UM.*
		FROM distinta_consumi AS DC
		LEFT JOIN tipo_consumo AS TC ON TC.tc_IdRiga = DC.dc_IdTipoConsumo
		LEFT JOIN unita_misura AS UM ON UM.um_IdRiga = TC.tc_Udm
		WHERE DC.dc_IdRisorsa = :IdRisorsa"
	);
	$sth->execute([":IdRisorsa" => $_REQUEST["idRisorsa"]]);


	$righe = $sth->fetchAll(PDO::FETCH_ASSOC);

	$output = [];
	$tipoCalcolo = ['Nessun calcolo', 'Rilevato dalla macchina', 'Calcolata in base ad ipotetico'];
	// scorro le righe del corpo della distinta risorse e le visualizzo
	foreach ($righe as $riga) {
		//Preparo i dati da visualizzare
		$output[] = [
			"Consumo" => $riga["tc_Descrizione"],
			"Udm" => $riga["um_Sigla"],
			"TipoCalcolo" => $tipoCalcolo[$riga["dc_TipoCalcolo"]],
			"ConsumoIpotetico" => $riga["dc_ValoreIpotetico"],
			"azioni" => '<div class="dropdown">
					<button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Modifica riga">
					<span class="mdi mdi-lead-pencil mdi-18px"></span>
					</button>
					<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
						<a class="dropdown-item modifica-dc" data-id-risorsa="' . $riga["dc_IdRisorsa"] . '" data-id-consumo="' . $riga["dc_IdTipoConsumo"] . '"><i class="mdi mdi-account-edit"></i> Modifica</a>
						<a class="dropdown-item cancella-dc" data-id-risorsa="' . $riga["dc_IdRisorsa"] . '" data-id-consumo="' . $riga["dc_IdTipoConsumo"] . '"><i class="mdi mdi-trash-can"></i> Elimina</a>
					</div>
				</div>'
		];
	}

	die(json_encode(['data' => $output]));
}



// D. MACCHINE: CANCELLAZIONE COMPONENTE DA DISTINTA IN OGGETTO
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "cancella-DC" && !empty($_REQUEST["idRisorsa"]) && !empty($_REQUEST["idConsumo"])) {
	// query di eliminazione della risorsa dalla distinta
	$sthDeleteDettaglio = $conn_mes->prepare(
		"DELETE FROM distinta_consumi
		WHERE dc_IdTipoConsumo = :IdTipoConsumo
		AND dc_IdRisorsa = :idRisorsa"
	);
	$sthDeleteDettaglio->execute([
		":IdTipoConsumo" => $_REQUEST["idConsumo"],
		":idRisorsa" => $_REQUEST["idRisorsa"]
	]);

	die("OK");
}



// D. MACCHINE: SALVATAGGIO DI MODIFICA/INSERIMENTO COMPONENTE SU DISTINTA IN OGGETTO
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "salva-dc" && !empty($_REQUEST["data"])) {
	// recupero i parametri dal POST
	$parametri = [];
	parse_str($_REQUEST["data"], $parametri);

	// se devo modificare
	if ($parametri["dc_Azione"] == "modifica") {



		$sthUpdate = $conn_mes->prepare(
			"UPDATE distinta_consumi SET
			dc_TipoCalcolo = :TipoCalcolo,
			dc_ValoreIpotetico = :ValoreIpotetico
			WHERE dc_IdRisorsa = :IdRisorsa AND dc_IdTipoConsumo = :IdTipoConsumo"
		);
		$sthUpdate->execute([
			":IdRisorsa" => $_REQUEST["idRisorsa"],
			":IdTipoConsumo" =>  $parametri['dc_IdTipoConsumo'],
			":TipoCalcolo" => $parametri['dc_TipoCalcolo'],
			":ValoreIpotetico" => $parametri['dc_ValoreIpotetico'],
		]);




		die("OK");
	} else {

		$sthInsert = $conn_mes->prepare(
			"INSERT INTO distinta_consumi(dc_IdTipoConsumo,dc_TipoCalcolo,dc_ValoreIpotetico,dc_IdRisorsa)
			VALUES(:IdTipoConsumo,:TipoCalcolo,:ValoreIpotetico,:IdRisorsa)"
		);
		$sthInsert->execute([
			":IdRisorsa" => $_REQUEST["idRisorsa"],
			":IdTipoConsumo" =>  $parametri['dc_IdTipoConsumo'],
			":TipoCalcolo" => $parametri['dc_TipoCalcolo'],
			":ValoreIpotetico" => $parametri['dc_ValoreIpotetico'],
		]);


		die("OK");
	}
}



//AUSILIARIA: POPOLAMENTO SELECT 'RISORSE DISPONIBILI'
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "caricaSelectConsumi" && !empty($_REQUEST["idRisorsa"])) {

	if (empty($_REQUEST['idConsumo'])) {
		$sth = $conn_mes->prepare(
			'SELECT * FROM tipo_consumo
			WHERE tc_IdRiga NOT IN (
				SELECT dc_IdTipoConsumo FROM distinta_consumi
				WHERE dc_IdRisorsa = :IdRisorsa
			)'
		);
		$sth->execute([":IdRisorsa" => $_REQUEST["idRisorsa"]]);
	} else {
		$sth = $conn_mes->prepare(
			'SELECT * FROM tipo_consumo
			WHERE tc_IdRiga = :IdConsumo'
		);
		$sth->execute([":IdConsumo" => $_REQUEST["idConsumo"]]);
	}


	$consumiDisponibili = $sth->fetchAll(PDO::FETCH_ASSOC);
	$optionValue = "";

	//Se ho trovato risorse disponibili
	if ($consumiDisponibili) {

		//Aggiungo ognuna delle risorse trovate alla select
		foreach ($consumiDisponibili as $consumo) {
			$optionValue = $optionValue . "<option value='" . $consumo['tc_IdRiga'] . "'>" . $consumo['tc_Descrizione'] . " </option>";
		}
		die($optionValue);
	} else {
		die("NO_RIS");
	}
}

?>



<!DOCTYPE html>
<html lang="it">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>PROSPECT40 | Compilazione distinte</title>
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
							<form class="forms-sample" id="form-dati-prodotto-finito">

								<!-- Visualizzazione distinte prodotto presenti e dati di quella selezionata -->
								<div class="row">
									<div class="col-8">
										<h4 class="card-title mx-2 my-2">COMPILAZIONE DISTINTA CONSUMI</h4>
									</div>

									<div class="col-4">
										<!-- ELENCO PRODOTTI FINITI -->
										<div class="form-group m-0">
											<label for="dc_IdRisorsa">Elenco macchine</label>
											<select class="form-control form-control-sm selectpicker" id="dc_IdRisorsa" name="dc_IdRisorsa" data-live-search="true" required>
												<?php
												$sth = $conn_mes->prepare("SELECT *
												FROM risorse");
												$sth->execute();
												$risorse = $sth->fetchAll(PDO::FETCH_ASSOC);

												if ($risorse) {
													foreach ($risorse as $risorsa) {
														echo "<option value='" . $risorsa['ris_IdRisorsa'] . "'>" . strtoupper($risorsa['ris_Descrizione']) . "</option>";
													}
												} else {
													echo "<option value=''>Nessuna risorsa definita</option>";
												}
												?>
											</select>
										</div>
									</div>




								</div>

							</form>
						</div>

						<div class="card-body">




							<!-- Visualizzazione dettaglio della distinta risorse selezionata -->
							<div class="row mt-1">

								<div class="col-12">

									<div class="table-responsive pt-1">

										<table id="tabellaDati-DC" class="table table-striped" data-source="distintaconsumi.php?azione=mostraDC">
											<thead>
												<tr>
													<th>Consumo</th>
													<th>Unità di misura</th>
													<th>Tipo calcolo</th>
													<th>Consumo ipotetico</th>
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
					</div>


				</div>
				<?php include("inc_footer.php") ?>

			</div>

		</div>

	</div>

	<!-- Pulsante aggiunta nuova risorsa alla distinta -->
	<button type="button" id="aggiungi-risorsa" class="mdi mdi-button">AGGIUNGI CONSUMO</button>



	<!-- Popup modale di aggiunta risorsa alla distinta selezionata -->
	<div class="modal fade" id="modal-nuovo-consumo" tabindex="-1" role="dialog" aria-labelledby="modal-nuovo-consumo-label" aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modal-nuovo-consumo-label">AGGIUNGI MACCHINA A DISTINTA</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form class="forms-sample" id="form-nuovo-consumo">
						<div class="row">
							<div class="col-6">
								<div class="form-group">
									<label for="dc_IdTipoConsumo">Consumo</label><span style='color:red'> *</span>
									<select class="form-control form-control-sm dati-popup-modifica selectpicker" id="dc_IdTipoConsumo" name="dc_IdTipoConsumo" autocomplete="off">

									</select>
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="dc_TipoCalcolo">Tipo calcolo</label><span style='color:red'> *</span>
									<select class="form-control form-control-sm dati-popup-modifica selectpicker" id="dc_TipoCalcolo" name="dc_TipoCalcolo" autocomplete="off">
										<option value=0>Nessun calcolo</option>
										<option value=1>Rilevato dalla macchina</option>
										<option value=2>Calcolata in base ad ipotetico</option>
									</select>
								</div>
							</div>
							<div class="col-12">
								<div class="form-group">
									<label for="dc_ValoreIpotetico">Consumo per pezzo ipotetico</label>
									<input type="number" class="form-control form-control-sm dati-popup-modifica" name="dc_ValoreIpotetico" id="dc_ValoreIpotetico" autocomplete="off">
								</div>
							</div>
						</div>

						<input type="hidden" id="dc_Risorsa" name="dc_Risorsa" value="">
						<input type="hidden" id="dc_Azione" name="dc_Azione" value="">
					</form>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-success" id="salva-dc">Salva</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
				</div>

			</div>
		</div>
	</div>


	<?php include("inc_js.php") ?>
	<script src="../js/distintaconsumi.js"></script>

</body>

</html>