<?php
// in che pagina siamo
$pagina = "distintarisorse";

include("../inc/conn.php");


// D. MACCHINE: RECUPERO INFORMAZIONI PRODOTTO FINITO SELEZIONATO
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "recuperaP") {
	if (!empty($_REQUEST["idProdotto"])) {
		// estraggo la riga relativa alla distinta selezionata
		$sthRecuperaDistinta = $conn_mes->prepare(
			"SELECT prodotti.* FROM prodotti
			WHERE prodotti.prd_Tipo = 'F' AND prodotti.prd_IdProdotto = :IdProdotto"
		);
		$sthRecuperaDistinta->execute([":IdProdotto" => $_REQUEST["idProdotto"]]);
		$riga = $sthRecuperaDistinta->fetch(PDO::FETCH_ASSOC);

		die(json_encode($riga));
	} else {
		die("NO_ROWS");
	}
}


// D. MACCHINE: INIZIALIZZAZIONE DISTINTA PER PRODOTTO E LINEA SELEZIONATI (CON MACCHINE RELATIVE ALLA LINEA SELEZIONATA)
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "inizializza-distinta-risorse" && !empty($_REQUEST["idProdotto"] && !empty($_REQUEST["idLineaProduzione"]))) {
	// estraggo la riga relativa alla distinta selezionata
	$sthInizializzaDistinta = $conn_mes->prepare(
		"INSERT INTO distinta_risorse_corpo (drc_IdProdotto, drc_IdRisorsa, drc_LineaProduzione, drc_FlagUltima, drc_IdRicetta, drc_FattoreConteggi)
		SELECT :IdProdotto, R.ris_IdRisorsa, R.ris_LineaProduzione, R.ris_FlagUltima, RM.ricm_Ricetta, R.ris_FattoreConteggi
		FROM risorse AS R
		LEFT JOIN ricette_macchina AS RM ON (R.ris_IdRisorsa = RM.ricm_IdRisorsa  AND RM.ricm_IdProdotto = :IdProdotto2)
		WHERE (R.ris_LineaProduzione = :IdLineaProduzione OR R.ris_LineaProduzione = 'lin_00')"
	);
	$sthInizializzaDistinta->execute([
		":IdProdotto" => $_REQUEST["idProdotto"],
		":IdProdotto2" => $_REQUEST["idProdotto"],
		":IdLineaProduzione" => $_REQUEST["idLineaProduzione"],
	]);

	die("OK");
}


// D. MACCHINE: RESET DISTINTA CREATA PER PRODOTTO E LINEA SELEZIONATI
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "resetta-distinta-risorse" && !empty($_REQUEST["idProdotto"] && !empty($_REQUEST["idLineaProduzione"]))) {
	// estraggo la riga relativa alla distinta selezionata
	$sthResettaDistinta = $conn_mes->prepare(
		"DELETE FROM distinta_risorse_corpo
		WHERE drc_IdProdotto = :IdProdotto AND (drc_LineaProduzione = :IdLineaProduzione OR drc_LineaProduzione = 'lin_00')"
	);
	$sthResettaDistinta->execute([":IdProdotto" => $_REQUEST["idProdotto"], ":IdLineaProduzione" => $_REQUEST["idLineaProduzione"]]);

	die("OK");
}


// D. MACCHINE: RECUPERO INFORMAZIONI SU COMPONENTE DISTINTA IN OGGETTO
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "recupera-drc" && !empty($_REQUEST["idProdotto"]) && !empty($_REQUEST["idRisorsa"])) {
	// recupero i dati del dettaglio distinta selezionato
	$sthRecuperaDettaglio = $conn_mes->prepare(
		"SELECT * FROM distinta_risorse_corpo
		WHERE drc_IdProdotto = :IdProdotto
		AND drc_IdRisorsa = :IdRisorsa"
	);
	$sthRecuperaDettaglio->execute([
		":IdProdotto" => $_REQUEST["idProdotto"],
		":IdRisorsa" => $_REQUEST["idRisorsa"]
	]);
	$riga = $sthRecuperaDettaglio->fetch(PDO::FETCH_ASSOC);

	die(json_encode($riga));
}


// D. MACCHINE: VISUALIZZAZIONE DETTAGLIO DISTINTA
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "mostraDRC") {

	$sth = $conn_mes->prepare(
		"SELECT * FROM distinta_risorse_corpo AS DRC
		LEFT JOIN prodotti AS P ON DRC.drc_IdProdotto = P.prd_IdProdotto
		LEFT JOIN risorse AS R ON DRC.drc_IdRisorsa = R.ris_IdRIsorsa
		LEFT JOIN ricette_macchina AS RM ON (DRC.drc_IdRisorsa = RM.ricm_IdRisorsa AND DRC.drc_IdRicetta = RM.ricm_Ricetta AND DRC.drc_IdProdotto = RM.ricm_IdProdotto)
		WHERE DRC.drc_IdProdotto = :IdProdotto AND (DRC.drc_LineaProduzione = :IdLineaProduzione)
		ORDER BY DRC.drc_IdProdotto ASC"
	);
	$sth->execute([
		":IdProdotto" => $_REQUEST["idProdotto"],
		":IdLineaProduzione" => $_REQUEST["idLineaProduzione"]
	]);
	$righe = $sth->fetchAll(PDO::FETCH_ASSOC);
	if ($righe) {


		$output = [];

		// scorro le righe del corpo della distinta risorse e le visualizzo
		foreach ($righe as $riga) {
			//Preparo i dati da visualizzare
			$output[] = [
				"IdRisorsa" => $riga["drc_IdRisorsa"],
				"FattoreConteggi" => $riga["drc_FattoreConteggi"],
				"Descrizione" => $riga["ris_Descrizione"],
				"Ricetta" => (isset($riga["ricm_Ricetta"]) ? $riga["ricm_Ricetta"] . " - " . $riga["ricm_Descrizione"] : "ND"),
				"NoteSetup" => $riga["drc_NoteSetup"],
				"AbiMisure" => ($riga["ris_AbiMisure"] ? 'ABILITATA' : 'DISABILITATA'),
				"FlagUltimaMacchina" => ($riga["drc_FlagUltima"] ? '<span class="mdi mdi-checkbox-marked mdi-18px"></span>' : '<span class="mdi mdi-checkbox-blank-outline mdi-18px"></span>'),
				"azioni" => '<div class="dropdown">
						<button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Modifica riga">
						<span class="mdi mdi-lead-pencil mdi-18px"></span>
						</button>
						<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
							<a class="dropdown-item modifica-drc" data-id-risorsa="' . $riga["drc_IdRisorsa"] . '" data-id-prodotto="' . $_REQUEST["idProdotto"] . '"><i class="mdi mdi-account-edit"></i> Modifica</a>
							<a class="dropdown-item cancella-drc" data-id-risorsa="' . $riga["drc_IdRisorsa"] . '" data-id-prodotto="' . $_REQUEST["idProdotto"] . '"><i class="mdi mdi-trash-can"></i> Elimina</a>
						</div>
					</div>',
				"Ordinamento" => $riga["ris_Ordinamento"]
			];
		}

		die(json_encode($output));
		exit();
	} else {
		die("NO_ROWS");
	}
}



// D. MACCHINE: CANCELLAZIONE COMPONENTE DA DISTINTA IN OGGETTO
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "cancella-DRC" && !empty($_REQUEST["idRisorsa"]) && !empty($_REQUEST["idProdotto"])) {
	// query di eliminazione della risorsa dalla distinta
	$sthDeleteDettaglio = $conn_mes->prepare(
		"DELETE FROM distinta_risorse_corpo
		WHERE drc_IdProdotto = :idProdotto
		AND drc_IdRisorsa = :idRisorsa"
	);
	$sthDeleteDettaglio->execute([
		":idProdotto" => $_REQUEST["idProdotto"],
		":idRisorsa" => $_REQUEST["idRisorsa"]
	]);

	die("OK");
}



// D. MACCHINE: SALVATAGGIO DI MODIFICA/INSERIMENTO COMPONENTE SU DISTINTA IN OGGETTO
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "salva-drc" && !empty($_REQUEST["data"])) {
	// recupero i parametri dal POST
	$parametri = [];
	parse_str($_REQUEST["data"], $parametri);

	$statoFlagUltimaMacchina = $_REQUEST["flagUltimaMacchina"];

	if ($statoFlagUltimaMacchina == 1) {
		$sthUpdateResetFlagUltima = $conn_mes->prepare(
			"UPDATE distinta_risorse_corpo SET
			drc_FlagUltima = '0'
			WHERE drc_IdProdotto = :IdProdotto AND drc_LineaProduzione = :IdLineaProduzione"
		);
		$sthUpdateResetFlagUltima->execute([
			":IdProdotto" => $parametri['drc_Prodotto'],
			":IdLineaProduzione" => $parametri['drc_IdLineaProduzione']
		]);
	}


	// se devo modificare
	if ($parametri["drc_Azione"] == "modifica") {

		// modifica

		$sthUpdate = $conn_mes->prepare(
			"UPDATE distinta_risorse_corpo SET
			drc_NoteSetup = :NoteSetup,
			drc_FlagUltima = :FlagUltima,
			drc_FattoreConteggi = :FattoreConteggi,
			drc_IdRicetta = :IdRicetta
			WHERE drc_IdProdotto = :IdProdotto
			AND drc_IdRisorsa = :IdRisorsa
			AND drc_LineaProduzione = :IdLineaProduzione"
		);
		$sthUpdate->execute([
			":IdRisorsa" => $parametri["drc_IdRisorsa"],
			":NoteSetup" =>  $parametri['drc_NoteSetup'],
			":FlagUltima" =>  $statoFlagUltimaMacchina,
			":IdRicetta" => $parametri['drc_IdRicetta'],
			":IdProdotto" => $parametri['drc_Prodotto'],
			":FattoreConteggi" => $parametri['drc_FattoreConteggi'],
			":IdLineaProduzione" => $parametri['drc_IdLineaProduzione']
		]);


		die("OK");
	} else {
		// inserimento
		$sqlInsert =
			"INSERT INTO distinta_risorse_corpo(drc_IdProdotto,drc_IdRisorsa,drc_LineaProduzione,drc_NoteSetup,drc_FlagUltima,drc_IdRicetta,drc_FattoreConteggi)
		VALUES(:IdProdotto,:IdRisorsa,:IdLineaProduzione,:NoteSetup,:FlagUltima,:IdRicetta,:FattoreConteggi)";

		$sthInsert = $conn_mes->prepare($sqlInsert);
		$sthInsert->execute([
			":IdProdotto" => $parametri["drc_Prodotto"],
			":IdRisorsa" => $parametri["drc_IdRisorsa"],
			":IdLineaProduzione" => $parametri['drc_IdLineaProduzione'],
			":NoteSetup" =>  $parametri['drc_NoteSetup'],
			":FlagUltima" =>  $statoFlagUltimaMacchina,
			":FattoreConteggi" => $parametri['drc_FattoreConteggi'],
			":IdRicetta" => $parametri['drc_IdRicetta']
		]);


		if ($sthInsert) {
			die("OK");
		}
	}
}



//AUSILIARIA: POPOLAMENTO SELECT 'RISORSE DISPONIBILI'
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "caricaSelectRisorse" && !empty($_REQUEST["idProdotto"]) && !empty($_REQUEST["idLineaProduzione"])) {

	// estraggo le risorse disponibili che non fanno ancora parte della distinta per quel prodotto
	if (empty($_REQUEST["risorsa"])) {
		$sth = $conn_mes->prepare(
			"SELECT risorse.* FROM risorse
			WHERE (
				risorse.ris_LineaProduzione = :IdLineaProduzione1
				OR risorse.ris_LineaProduzione = 'lin_0X'
				OR risorse.ris_LineaProduzione = 'lin_0P'
			)
			AND risorse.ris_IdRisorsa NOT IN (
				SELECT distinta_risorse_corpo.drc_IdRisorsa FROM distinta_risorse_corpo
				WHERE distinta_risorse_corpo.drc_IdProdotto = :IdProdotto AND (
					distinta_risorse_corpo.drc_LineaProduzione = :IdLineaProduzione
					OR distinta_risorse_corpo.drc_LineaProduzione = 'lin_00'
				)
			)"
		);
		$sth->execute([
			":IdProdotto" => $_REQUEST["idProdotto"],
			":IdLineaProduzione1" => $_REQUEST["idLineaProduzione"],
			":IdLineaProduzione" => $_REQUEST["idLineaProduzione"],
		]);
	} else {
		$sth = $conn_mes->prepare(
			"SELECT risorse.* FROM risorse
			WHERE (
				risorse.ris_LineaProduzione = :IdLineaProduzione1
				OR risorse.ris_LineaProduzione = 'lin_0X'
				OR risorse.ris_LineaProduzione = 'lin_0P'
			)
			AND risorse.ris_IdRisorsa NOT IN (
				SELECT distinta_risorse_corpo.drc_IdRisorsa FROM distinta_risorse_corpo
				WHERE distinta_risorse_corpo.drc_IdProdotto = :IdProdotto
				AND (
					distinta_risorse_corpo.drc_LineaProduzione = :IdLineaProduzione
					OR distinta_risorse_corpo.drc_LineaProduzione = 'lin_00'
				)
			)
			OR risorse.ris_IdRisorsa = :RisorsaSelezionata"
		);
		$sth->execute([
			":IdProdotto" => $_REQUEST["idProdotto"],
			":RisorsaSelezionata" => $_REQUEST["risorsa"],
			":IdLineaProduzione1" => $_REQUEST["idLineaProduzione"],
			":IdLineaProduzione" => $_REQUEST["idLineaProduzione"],
		]);
	}


	$risorseDisponibili = $sth->fetchAll(PDO::FETCH_ASSOC);
	$optionValue = "";

	//Se ho trovato risorse disponibili
	if ($risorseDisponibili) {

		//Aggiungo ognuna delle risorse trovate alla select
		foreach ($risorseDisponibili as $risorsa) {
			if (!empty($_REQUEST["risorsa"]) && $_REQUEST["risorsa"] == $risorsa['ris_IdRisorsa']) {
				$optionValue = $optionValue . "<option value='" . $risorsa['ris_IdRisorsa'] . "' selected >" . $risorsa['ris_Descrizione'] . " </option>";
			} else {
				$optionValue = $optionValue . "<option value='" . $risorsa['ris_IdRisorsa'] . "'>" . $risorsa['ris_Descrizione'] . " </option>";
			}
		}


		echo $optionValue;
		exit();
	} else {

		echo "NO_RIS";
		exit();
	}
}



//AUSILIARIA: POPOLAMENTO SELECT 'RICETTE'
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "caricaSelectRicette" && !empty($_REQUEST["idRisorsa"])) {
	$sth = $conn_mes->prepare(
		'SELECT ricette_macchina.ricm_Ricetta, ricette_macchina.ricm_Descrizione, ricette_macchina.ricm_IdProdotto
		FROM ricette_macchina WHERE ricette_macchina.ricm_IdRisorsa = :IdRisorsa'
	);
	$sth->execute([":IdRisorsa" => $_REQUEST["idRisorsa"]]);


	$ricetteTrovate = $sth->fetchAll(PDO::FETCH_ASSOC);

	$optionValue = "";

	//Se ho trovato risorse disponibili
	if ($ricetteTrovate) {

		$optionValue = $optionValue . "<option value='ND'>Ricetta non definita</option>";

		//Aggiungo ognuna delle risorse trovate alla select
		foreach ($ricetteTrovate as $ricetta) {
			if (!empty($_REQUEST["idRicetta"]) && $_REQUEST["idRicetta"] == $ricetta['ricm_Ricetta']) {
				$optionValue = $optionValue . "<option value='" . $ricetta['ricm_Ricetta'] . "' selected >" . $ricetta['ricm_Ricetta'] . " - " . $ricetta['ricm_Descrizione'] . " </option>";
			} else if (!empty($_REQUEST["idProdotto"]) && $_REQUEST["idProdotto"] == $ricetta['ricm_IdProdotto']) {
				$optionValue = $optionValue . "<option value='" . $ricetta['ricm_Ricetta'] . "' selected >" . $ricetta['ricm_Ricetta'] . " - " . $ricetta['ricm_Descrizione'] . " </option>";
			} else {
				$optionValue = $optionValue . "<option value='" . $ricetta['ricm_Ricetta'] . "'>" . $ricetta['ricm_Ricetta'] . " - " . $ricetta['ricm_Descrizione'] . " </option>";
			}
		}


		echo $optionValue;
		exit();
	} else {

		$optionValue = $optionValue . "<option value='ND'>Ricetta non definita</option>";
		echo $optionValue;
		exit();
	}
}

if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "fattoreConteggi" && !empty($_REQUEST["idRisorsa"])) {
	$sth = $conn_mes->prepare(
		'SELECT ris_FattoreConteggi FROM risorse
		WHERE ris_IdRisorsa = :IdRisorsa'
	);
	$sth->execute([":IdRisorsa" => $_REQUEST["idRisorsa"]]);
	$risorsa = $sth->fetch();

	die($risorsa['ris_FattoreConteggi']);
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
									<div class="col-4">
										<h4 class="card-title mx-2 my-2">COMPILAZIONE DISTINTA MACCHINE</h4>
									</div>
									<div class="col-4">
										<!-- ELENCO PRODOTTI FINITI -->
										<div class="form-group m-0">
											<label for="dr_prodottoSelezionato">Elenco prodotti</label>
											<select class="form-control form-control-sm selectpicker" id="dr_ProdottoSelezionato"
												name="dr_prodottoSelezionato" data-live-search="true" required>
												<?php
												$sth = $conn_mes->prepare(
													"SELECT * FROM prodotti
													WHERE prodotti.prd_Tipo = 'F' OR prodotti.prd_Tipo = 'S'
													ORDER BY prodotti.prd_Descrizione ASC",
												);
												$sth->execute();
												$prodotti = $sth->fetchAll(PDO::FETCH_ASSOC);

												if ($prodotti) {
													foreach ($prodotti as $prodotto) {
														echo "<option value='" . $prodotto['prd_IdProdotto'] . "'>" . strtoupper($prodotto['prd_Descrizione']) . "</option>";
													}
												} else {
													echo "<option value=''>Nessun prodotto definito</option>";
												}
												?>
											</select>
										</div>
									</div>

									<div class="col-4">
										<!-- LINEE PRODUZIONE DISPONIBILI -->
										<div class="form-group m-0">
											<label for="dr_LineeProduzione">Linee di produzione disponibili</label>
											<select class="form-control form-control-sm selectpicker" id="dr_LineeProduzione"
												name="dr_LineeProduzione" data-live-search="true" required>
												<?php
												$sth = $conn_mes->prepare(
													"SELECT linee_produzione.* FROM linee_produzione
													WHERE linee_produzione.lp_IdLinea != 'lin_0P'
													AND linee_produzione.lp_IdLinea != 'lin_0X'
													ORDER BY linee_produzione.lp_Descrizione ASC"
												);
												$sth->execute();
												$linee = $sth->fetchAll(PDO::FETCH_ASSOC);

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


								</div>

							</form>
						</div>

						<div class="card-body">



							<!-- Visualizzazione dettaglio della distinta risorse selezionata -->
							<div class="row mt-1">

								<div class="col-12">

									<div class="table-responsive pt-1">

										<table id="tabellaDati-DRC" class="table table-striped"
											data-source="distintarisorse.php?azione=mostraDRC">
											<thead>
												<tr>
													<th>Macchina</th>
													<th>Descrizione</th>
													<th>Ricetta</th>
													<th>Note setup</th>
													<th>Ultima</th>
													<!-- <th>Reg. misure</th> -->
													<!-- <th>T. teorico pezzo (pezzi/ora)</th> -->
													<th></th>
													<th>Ordinamento</th> <!-- Nascosto -->
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
	<button type="button" id="aggiungi-risorsa" class="mdi mdi-button">AGGIUNGI MACCHINA</button>
	<button type="button" id="inizializza-distinta-risorse" class="mdi mdi-button">CREA DISTINTA</button>
	<button type="button" id="resetta-distinta-risorse" class="mdi mdi-button">CANCELLA DISTINTA</button>



	<!-- Popup modale di aggiunta risorsa alla distinta selezionata -->
	<div class="modal fade" id="modal-nuova-risorsa" tabindex="-1" role="dialog"
		aria-labelledby="modal-nuova-risorsa-label" aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modal-nuova-risorsa-label">AGGIUNGI MACCHINA A DISTINTA</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form class="forms-sample" id="form-nuova-risorsa">
						<div class="row">
							<div class="col-6">
								<div class="form-group">
									<label for="drc_NomeProdotto">Prodotto</label>
									<input type="text" class="form-control form-control-sm dati-popup-modifica" name="drc_NomeProdotto"
										id="drc_NomeProdotto" autocomplete="off" readonly>
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="drc_NomeLineaProduzione">Linea di produzione</label>
									<input type="text" class="form-control form-control-sm dati-popup-modifica"
										name="drc_NomeLineaProduzione" id="drc_NomeLineaProduzione" autocomplete="off" readonly>
								</div>
							</div>
							<div class="col-12">
								<div class="form-group">
									<label for="drc_IdRisorsa">Macchina</label><span style='color:red'> *</span>
									<select class="form-control form-control-sm dati-popup-modifica selectpicker" id="drc_IdRisorsa"
										name="drc_IdRisorsa" autocomplete="off">
									</select>
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="drc_IdRicetta">Ricetta</label><span style='color:red'> *</span>
									<select class="form-control form-control-sm dati-popup-modifica selectpicker" id="drc_IdRicetta"
										name="drc_IdRicetta">

									</select>
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="drc_FattoreConteggi">Fattore conteggi</label>
									<input type="text" class="form-control form-control-sm dati-popup-modifica" name="drc_FattoreConteggi"
										id="drc_FattoreConteggi" autocomplete="off" value=1>
								</div>
							</div>
							<div class="col-12">
								<div class="form-group">
									<label for="drc_NoteSetup">Note di SETUP</label>
									<input type="text" class="form-control form-control-sm dati-popup-modifica" name="drc_NoteSetup"
										id="drc_NoteSetup" autocomplete="off">
								</div>
							</div>
							<div class="col-12">
								<div class="form-check pt-12">
									<input id="drc_FlagUltima" type="checkbox">
									<label for="drc_FlagUltima" style="font-weight: normal;">Ultima macchina </label>
								</div>
							</div>
						</div>

						<input type="hidden" id="drc_Prodotto" name="drc_Prodotto" value="">
						<input type="hidden" id="drc_IdLineaProduzione" name="drc_IdLineaProduzione" value="">
						<input type="hidden" id="drc_Azione" name="drc_Azione" value="nuovo">


					</form>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-success" id="salva-drc">Salva</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
				</div>

			</div>
		</div>
	</div>


	<?php include("inc_js.php") ?>
	<script src="../js/distintarisorse.js"></script>

</body>

</html>