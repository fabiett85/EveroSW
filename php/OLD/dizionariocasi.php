<?php
// in che pagina siamo
$pagina = "dizionariocasi";

include("../inc/conn.php");

// : VISUALIZZAZIONE PRODOTTI MAGAZZINO
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "mostra") {
	$_REQUEST['idRisorsa']  = $_REQUEST['idRisorsa'] == 'undefined' ? null : $_REQUEST['idRisorsa'];
	$_REQUEST['tipoCaso']  = $_REQUEST['tipoCaso'] == 'undefined' ? null : $_REQUEST['tipoCaso'];

	if (isset($_REQUEST['idRisorsa']) && isset($_REQUEST['tipoCaso'])) {
		// estraggo la lista
		$sth = $conn_mes->prepare(
			"SELECT casi.*, risorse.ris_Descrizione FROM casi
			LEFT JOIN risorse ON casi.cas_IdRisorsa = risorse.ris_IdRisorsa
			WHERE cas_IdRisorsa LIKE :IdRisorsa AND cas_Flag LIKE :TipoCaso"
		);
		$sth->execute([
			":IdRisorsa" => $_REQUEST['idRisorsa'],
			":TipoCaso" => $_REQUEST['tipoCaso']
		]);
	} else {
		// estraggo la lista
		$sth = $conn_mes->prepare(
			"SELECT casi.*, risorse.ris_Descrizione FROM casi
			LEFT JOIN risorse ON casi.cas_IdRisorsa = risorse.ris_IdRisorsa"
		);
		$sth->execute();
	}

	$output = [];
	$righe = $sth->fetchAll(PDO::FETCH_ASSOC);

	if ($righe) {


		$marked = '<div class="d-flex justify-content-center align-items-center" style="max-height:18px"><i class="mdi mdi-checkbox-marked mdi-18px"></i></div>';
		$unmarked = '<div class="d-flex justify-content-center align-items-center" style="max-height:18px"><span class="mdi mdi-checkbox-blank-outline"></span></div>';


		foreach ($righe as $riga) {
			//Preparo i dati da visualizzare
			$output[] = [
				"DescrizioneRisorsa" => $riga["ris_Descrizione"],
				"IdEvento" => $riga["cas_IdEvento"],
				"DescrizioneEvento" => $riga["cas_DescrizioneEvento"],
				"CategoriaMES" => $riga["cas_DescrizioneCaso"],
				"Abilitazione" => ($riga["cas_Disabilitato"] == 0 ? $marked : $unmarked),
				"LogicaInvertita" => ($riga["cas_Invertito"] == 0 ? $unmarked : $marked),
				"FlagManuale" => ($riga["cas_Flag"] == 1 ? $marked : $unmarked),
				"azioni" => ($riga["cas_Flag"] == 1 ?
					'<div class="dropdown">
									<button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Modifica riga">
									<span class="mdi mdi-lead-pencil mdi-18px"></span>
									</button>
									<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
										<a class="dropdown-item modifica-caso" data-id_riga="' . $riga["cas_IdRiga"] . '"><i class="mdi mdi-account-edit"></i> Modifica</a>
										<a class="dropdown-item cancella-caso" data-id_riga="' . $riga["cas_IdRiga"] . '"><i class="mdi mdi-trash-can"></i> Elimina</a>
									</div>
								</div>'
					:
					'<div class="dropdown">
									<button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Modifica riga">
									<span class="mdi mdi-lead-pencil mdi-18px"></span>
									</button>
									<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
										<a class="dropdown-item modifica-caso" data-id_riga="' . $riga["cas_IdRiga"] . '"><i class="mdi mdi-account-edit"></i> Modifica</a>
									</div>
								</div>')

			];
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
		"SELECT * FROM casi
		WHERE cas_IdRiga = :codice"
	);
	$sth->execute([":codice" => $_REQUEST["codice"]]);
	$riga = $sth->fetch(PDO::FETCH_ASSOC);

	//debug($riga,"RIGA");

	die(json_encode($riga));
}



// DIZIONARIO CASI: GESTIONE CANCELLAZIONE
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "cancella-caso" && !empty($_REQUEST["id"])) {

	// elimino risorsa da tabella 'risorse'
	$sthDeleteCaso = $conn_mes->prepare("DELETE FROM casi WHERE cas_IdRiga = :id");
	$sthDeleteCaso->execute([":id" => $_REQUEST["id"]]);

	//Verifica esito
	if ($sthDeleteCaso) {
		die("OK");
	} else {
		die("ERRORE");
	}
}



// DIZIONARIO CASI: GESTIONE SALVATAGGIO DATI DA POPUP MODIFICA/INSERIMENTO
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "salva-caso" && !empty($_REQUEST["data"])) {
	// recupero i parametri dal POST
	$parametri = [];
	parse_str($_REQUEST["data"], $parametri);
	$conn_mes->beginTransaction();

	$statoDisabilitato = $_REQUEST["falgDisabilitato"];
	$statoInvertito = $_REQUEST["flagLogicaInvertita"];
	$descrizioneCaso = $_REQUEST["descrizioneCaso"];

	try {
		if ($parametri["azione"] == "modifica") {

			$id_modifica = $parametri["cas_IdRiga"];

			$sthUpdateInsert = $conn_mes->prepare(
				"UPDATE casi SET
				cas_DescrizioneEvento = :DescrizioneEvento,
				cas_IdCaso = :IdCaso,
				cas_DescrizioneCaso = :DescrizioneCaso,
				cas_Tipo = :Tipo,
				cas_Disabilitato = :Disabilitato,
				cas_Invertito = :Invertito,
				cas_Gruppo = :Gruppo
				WHERE cas_IdRiga = :IdRiga"
			);
			$sthUpdateInsert->execute([
				":DescrizioneEvento" => $parametri["cas_DescrizioneEvento"],
				":IdCaso" => $parametri["cas_IdCaso"],
				":DescrizioneCaso" => $descrizioneCaso,
				":Tipo" => $parametri["cas_IdCaso"],
				":Disabilitato" => $statoDisabilitato,
				":Invertito" => $statoInvertito,
				":Gruppo" => $parametri["cas_Gruppo"],
				":IdRiga" => $id_modifica
			]);
		} else // nuovo inserimento
		{

			// verifico che il codice evento inserito, che mi funge da ID, non esista in altri record
			$sthSelect = $conn_mes->prepare(
				"SELECT * FROM casi
				WHERE cas_IdRisorsa = :IdRisorsa
				AND cas_IdEvento = :IdEvento"
			);
			$sthSelect->execute([
				":IdRisorsa" => $parametri["cas_IdRisorsa"],
				":IdEvento" => $parametri["cas_IdEvento"]
			]);
			$trovati = $sthSelect->fetch(PDO::FETCH_ASSOC);

			if (!$trovati) {

				$sthUpdateInsert = $conn_mes->prepare(
					"INSERT INTO casi(cas_IdRisorsa,cas_IdEvento,cas_DescrizioneEvento,cas_IdCaso,cas_DescrizioneCaso,cas_Flag,cas_Tipo,cas_Disabilitato,cas_Invertito,cas_Gruppo)
					VALUES(:IdRisorsa,:IdEvento,:DescrizioneEvento,:IdCaso,:DescrizioneCaso,:Flag,:Tipo,:Disabilitato,:Invertito,:Gruppo)"
				);
				$sthUpdateInsert->execute([
					":IdRisorsa" => $parametri["cas_IdRisorsa"],
					":IdEvento" => $parametri["cas_IdEvento"],
					":DescrizioneEvento" => $parametri["cas_DescrizioneEvento"],
					":IdCaso" => $parametri["cas_IdCaso"],
					":DescrizioneCaso" => $descrizioneCaso,
					":Flag" => 1,
					":Tipo" => $parametri["cas_IdCaso"],
					":Disabilitato" => $statoDisabilitato,
					":Invertito" => $statoInvertito,
					":Gruppo" => $parametri["cas_Gruppo"],
				]);
			} else {
				die("Il codice evento: " . $parametri["cas_IdEvento"] . " è già utilizzato per la macchina in oggetto.");
			}
		}
		$conn_mes->commit();
		die("OK");
	} catch (\Throwable $th) {
		$conn_mes->rollBack();
		die($th->getMessage());
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

								<div class="col-8">
									<h4 class="card-title mx-2 my-2">ELENCO EVENTI GESTITI</h4>
								</div>
								<div class="col-2">
									<div class="form-group m-0">
										<label for="cas_FiltroTipi">Filtra tipologia</label>
										<select class="form-control form-control-sm selectpicker" id="cas_FiltroTipi" name="cas_FiltroTipi" data-live-search="true" required>
											<option value='%'>TUTTI</option>
											<option value='1'>MANUALI</option>
											<option value='0'>DA MACCHINE</option>
										</select>
									</div>
								</div>
								<div class="col-2">
									<div class="form-group m-0">
										<label for="cas_FiltroRisorse">Filtra macchina</label>
										<select class="form-control form-control-sm selectpicker" id="cas_FiltroRisorse" name="cas_FiltroRisorse" data-live-search="true" required>
											<?php
											$sth = $conn_mes->prepare(
												"SELECT risorse.* FROM risorse"
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

										<table id="tabellaDati-casi" class="table table-striped" style="width:100%" data-source="dizionariocasi.php?azione=mostra">
											<thead>
												<tr>
													<th>Macchina</th>
													<th>Id evento</th>
													<th>Descrizione evento</th>
													<th>Categoria MES</th>
													<th>Abilitazione</th>
													<th>Logica invertita</th>
													<th>Gest. manuale</th>
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

	<button type="button" id="nuovo-caso" class="mdi mdi-button">NUOVO EVENTO</button>


	<!-- Opup modale di modifica/inserimento EVENTO-->
	<div class="modal fade" id="modal-caso" tabindex="-1" role="dialog" aria-labelledby="modal-caso-label" aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modal-caso-label">Nuovo evento</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form class="forms-sample" id="form-caso">

						<div class="row">
							<div class="col-8">
								<div class="form-group">
									<label for="cas_IdRisorsa">Macchina</label><span style='color:red'> *</span>
									<select class="form-control form-control-sm selectpicker" id="cas_IdRisorsa" name="cas_IdRisorsa">
										<?php
										$sth = $conn_mes->prepare(
											"SELECT risorse.ris_IdRisorsa, risorse.ris_Descrizione FROM risorse
											ORDER BY risorse.ris_IdRisorsa ASC"
										);
										$sth->execute();
										$linee = $sth->fetchAll(PDO::FETCH_ASSOC);

										foreach ($linee as $linea) {
											echo "<option value='" . $linea['ris_IdRisorsa'] . "'>" . strtoupper($linea['ris_Descrizione']) . "</option>";
										}
										?>
									</select>
								</div>
							</div>
							<div class="col-4">
								<div class="form-group">
									<label for="cas_IdEvento">Codice evento</label><span style='color:red'> *</span>
									<input type="text" class="form-control form-control-sm dati-popup-modifica obbligatorio" name="cas_IdEvento" id="cas_IdEvento" autocomplete="off">
								</div>
							</div>
							<div class="col-12">
								<div class="form-group">
									<label for="cas_DescrizioneEvento">Descrizione evento</label><span style='color:red'> *</span>
									<input type="text" class="form-control form-control-sm dati-popup-modifica obbligatorio" name="cas_DescrizioneEvento" id="cas_DescrizioneEvento" autocomplete="off">
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="cas_IdCaso">Categoria evento MES</label><span style='color:red'> *</span>
									<select class="form-control form-control-sm selectpicker" id="cas_IdCaso" name="cas_IdCaso">
										<?php
										$sth = $conn_mes->prepare(
											"SELECT tipi_evento.* FROM tipi_evento
											ORDER BY te_IdCaso ASC"
										);
										$sth->execute();
										$linee = $sth->fetchAll(PDO::FETCH_ASSOC);

										foreach ($linee as $linea) {
											echo "<option value=" . $linea['te_IdTipoEvento'] . ">" . $linea['te_Descrizione'] . "</option>";
										}
										?>
									</select>
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="cas_Gruppo">Gruppo</label><span style='color:red'> *</span>
									<select class="form-control form-control-sm selectpicker" id="cas_Gruppo" name="cas_Gruppo">
										<?php
										$sth = $conn_mes->prepare(
											"SELECT gruppi_casi.* FROM gruppi_casi
											ORDER BY gc_Descrizione ASC"
										);
										$sth->execute();
										$linee = $sth->fetchAll(PDO::FETCH_ASSOC);

										foreach ($linee as $linea) {
											echo "<option value=" . $linea['gc_IdRiga'] . ">" . $linea['gc_Descrizione'] . "</option>";
										}
										?>
									</select>
								</div>
							</div>
							<div class="col-6">
								<div class="form-check pt-4">
									<input id="cas_Disabilitato" type="checkbox">
									<label for="cas_Disabilitato" style="font-weight: normal;">Abilitazione</label>
								</div>
							</div>
							<div class="col-6">
								<div class="form-check pt-4">
									<input id="cas_Invertito" type="checkbox">
									<label for="cas_Invertito" style="font-weight: normal;">Inverti logica</label>
								</div>
							</div>
						</div>

						<input type="hidden" id="cas_Tipo" name="cas_Tipo" value="">
						<input type="hidden" id="cas_Flag" name="cas_Flag" value="">
						<input type="hidden" id="cas_IdRiga" name="cas_IdRiga" value="">
						<input type="hidden" id="azione" name="azione" value="nuovo">

					</form>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-success" id="salva-caso">Salva</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
				</div>

			</div>
		</div>
	</div>


	<?php include("inc_js.php") ?>
	<script src="../js/dizionariocasi.js"></script>

</body>

</html>