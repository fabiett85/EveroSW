<?php
// in che pagina siamo
$pagina = "gestionepianoorario";

include("../inc/conn.php");


// --------------------------------------------- OPERAZIONI DI CONSULTAZIONE PIANO ORARIO ---------------------------------------------

// CONSULTA PIANO ORARIO: VISUALIZZA IN TABELLA L'ELENCO DELLE DATE
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "mostra-calendario") {

	// imposto la query di SELEZIONE per ricavare l'elenco delle date presenti nella tabella
	$sth = $conn_mes->prepare("SELECT pianificazione_turni.*
									FROM pianificazione_turni
									ORDER BY pt_Data ASC", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
	$sth->execute(array());

	if ($sth->rowCount() > 0) {

		$righe = $sth->fetchAll(PDO::FETCH_ASSOC);

		$output = array();

		// scorro le righe del corpo della distinta risorse e le visualizzo
		foreach ($righe as $riga) {
			// formatto la data nel formato di visualizzazione idoneo
			$dataFormattata = date("d/m/Y", strtotime($riga["pt_Data"]));


			//Preparo i dati da visualizzare
			$output[] = array(
				"DataGiorno" => $dataFormattata,
				"DataOrdinamento" => $riga["pt_Data"],
				"OreLinea1" => round($riga["pt_Linea1"], 0),
				"OreLinea2" => round($riga["pt_Linea2"], 0),
				"OreLinea3" => round($riga["pt_Linea3"], 0),
				"OreLinea4" => round($riga["pt_Linea4"], 0),
				"OreLinea5" => round($riga["pt_Linea5"], 0),
				"azioni" => '<div class="dropdown">
						<button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Modifica riga">
						<span class="mdi mdi-lead-pencil mdi-18px"></span>
						</button>
						<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
							<a class="dropdown-item modifica-giorno" data-giorno="' . $riga["pt_Data"] . '"><i class="mdi mdi-account-edit"></i> Modifica</a>
							<a class="dropdown-item cancella-giorno" data-giorno="' . $riga["pt_Data"] . '"><i class="mdi mdi-trash-can"></i> Elimina</a>

						</div>
					</div>'
			);
		}

		die(json_encode($output));
		exit();
	} else {
		die("NO_ROWS");
	}
}


// PIANO ORARIO: RECUPERA ELENCO LINEE
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "recupera-linee") {

	$sthLineeProduzione = $conn_mes->prepare("SELECT linee_produzione.*
													FROM linee_produzione
													WHERE linee_produzione.lp_IdLinea <> 'lin_0X'
													AND linee_produzione.lp_IdLinea <> 'lin_0P'", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
	$sthLineeProduzione->execute();

	if ($sthLineeProduzione->rowCount() > 0) {

		$righe = $sthLineeProduzione->fetchAll(PDO::FETCH_ASSOC);

		foreach ($righe as $riga) {

			//Preparo i dati da visualizzare
			$output[] = array(

				"IdLinea" => $riga["lp_IdLinea"],
				"DescrizioneLinea" => strtoupper($riga["lp_Descrizione"])
			);
		}
		die(json_encode($output));
	} else {
		die("NO_ROWS");
	}
}


// PIANO ORARIO: VISUALIZZA IN TABELLA L'ELENCO DELLE DATE
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "recupera-piano-orario") {

	// imposto la query di SELEZIONE per ricavare l'elenco delle date presenti nella tabella
	$sth = $conn_mes->prepare("SELECT pianificazione_turni.*
					FROM pianificazione_turni
					ORDER BY pt_Data ASC", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
	$sth->execute(array());
	if ($sth->rowCount() > 0) {

		$righe = $sth->fetchAll(PDO::FETCH_ASSOC);

		$output = array();

		// scorro le righe del corpo della distinta risorse e le visualizzo
		foreach ($righe as $riga) {


			//Preparo i dati da visualizzare
			$output[] = array(
				"DataOrdinamento" => $riga["pt_Data"],
				"OreLinea1" => round($riga["pt_Linea1"], 0),
				"OreLinea2" => round($riga["pt_Linea2"], 0),
				"OreLinea3" => round($riga["pt_Linea3"], 0),
				"OreLinea4" => round($riga["pt_Linea4"], 0),
				"OreLinea5" => round($riga["pt_Linea5"], 0)
			);
		}

		die(json_encode($output));
		exit();
	} else {
		die("NO_ROWS");
	}
}


// PIANO ORARIO: RECUPERA DAATI DATA SELEZIONATA (VISUALIZZAZIONE IN POPUP)
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "recupera-giorno" && !empty($_REQUEST["giorno"])) {
	// definisco la query di SELEZIONE per ricavare le informazioni della data selezionata
	$sth = $conn_mes->prepare("SELECT * FROM pianificazione_turni WHERE pt_Data = :DataGiorno", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
	$sth->execute(array(":DataGiorno" => $_REQUEST["giorno"]));
	$riga = $sth->fetch(PDO::FETCH_ASSOC);

	// ritorno la riga trovata
	die(json_encode($riga));
}


// CONSULTA PIANO TURNI: CANCELLA LA DATA SELEZIONATA
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "cancella-giorno" && !empty($_REQUEST["giorno"])) {
	// definisco la query di DELETE per cancellare la data selezionata
	$sthDeleteGiorno = $conn_mes->prepare("DELETE FROM pianificazione_turni WHERE pt_Data = :DataGiorno");
	$sthDeleteGiorno->execute(array(":DataGiorno" => $_REQUEST["giorno"]));

	//Verifica esito
	if ($sthDeleteGiorno) {
		die("OK");
	} else {
		die("ERRORE");
	}
}


// PIANO ORARIO: SALVA MODIFICHE ALLA DATA SELEZIONATA (SALVA MODIFICHE IN POPUP)
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "salva-calendario" && !empty($_REQUEST["data"])) {
	// recupero i parametri dal POST
	$parametri = array();
	parse_str($_REQUEST["data"], $parametri);


	// definisco la query di UPDATE per aggiornare le informazioni sulle ore turno relative alla data selezionata
	$sqlUpdate = "UPDATE pianificazione_turni SET
					pt_Linea1 = :OreLinea1,
					pt_Linea2 = :OreLinea2,
					pt_Linea3 = :OreLinea3,
					pt_Linea4 = :OreLinea4,
					pt_Linea5 = :OreLinea5,
					pt_Linea6 = :OreLinea6,
					pt_Linea7 = :OreLinea7,
					pt_Linea8 = :OreLinea8,
					pt_Linea9 = :OreLinea9,
					pt_Linea10 = :OreLinea10
					WHERE pt_Data = :DataGiorno";

	$sthUpdate = $conn_mes->prepare($sqlUpdate);
	$sthUpdate->execute(array(
		":OreLinea1" => ((isset($parametri["pt_Linea1"]) && $parametri["pt_Linea1"] != "") ? $parametri["pt_Linea1"] : NULL),
		":OreLinea2" => ((isset($parametri["pt_Linea2"]) && $parametri["pt_Linea2"] != "") ? $parametri["pt_Linea2"] : NULL),
		":OreLinea3" => ((isset($parametri["pt_Linea3"]) && $parametri["pt_Linea3"] != "") ? $parametri["pt_Linea3"] : NULL),
		":OreLinea4" => ((isset($parametri["pt_Linea4"]) && $parametri["pt_Linea4"] != "") ? $parametri["pt_Linea4"] : NULL),
		":OreLinea5" => ((isset($parametri["pt_Linea5"]) && $parametri["pt_Linea5"] != "") ? $parametri["pt_Linea5"] : NULL),
		":OreLinea6" => ((isset($parametri["pt_Linea6"]) && $parametri["pt_Linea6"] != "") ? $parametri["pt_Linea6"] : NULL),
		":OreLinea7" => ((isset($parametri["pt_Linea7"]) && $parametri["pt_Linea7"] != "") ? $parametri["pt_Linea7"] : NULL),
		":OreLinea8" => ((isset($parametri["pt_Linea8"]) && $parametri["pt_Linea8"] != "") ? $parametri["pt_Linea8"] : NULL),
		":OreLinea9" => ((isset($parametri["pt_Linea9"]) && $parametri["pt_Linea9"] != "") ? $parametri["pt_Linea9"] : NULL),
		":OreLinea10" => ((isset($parametri["pt_Linea10"]) && $parametri["pt_Linea10"] != "") ? $parametri["pt_Linea10"] : NULL),
		":DataGiorno" => $parametri["pt_Data"]
	));

	die("OK");
}



// --------------------------------------------- OPERAZIONI DI INIZIALIZZAZIONE PIANO ORARIO  ---------------------------------------------

///N.B: l'inizializzazione del calendario orario turni consta di due fasi:
// - inserimento in tabella 'pianificazione_turni' delle date comprese nel periodo definito
// - aggiornamento delle date con le ore turno previste per ciascuna giornata


// INIZIALIZZA PIANO ORARIO: RICERCA LA PRIMA DATA DISPONIBILE NEL CALENDARIO
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "ricerca-data-disponibile") {

	// definisco la query di SELEZIONE per ricercare la prima data libera non ancora inserita in calendario
	$sth = $conn_mes->prepare("SELECT DATEADD(day, 1, MAX(pt_Data)) AS PrimaDataDisponibile FROM pianificazione_turni", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
	$sth->execute();
	$righeTrovate = $sth->fetch(PDO::FETCH_ASSOC);

	// se ho trovato una data disponibile, la restituisco e sarà visualizzata nella casella di selezione, altrimenti restituisco la data odierna
	if (isset($righeTrovate['PrimaDataDisponibile'])) {
		die($righeTrovate['PrimaDataDisponibile']);
	} else {
		die(date('Y-m-d'));
	}
}



// INIZIALIZZA PIANO ORARIO: VERIFICA SE PER IL TRACCIATE INSERITO (Data inizio - Data fine) E' GIA' STATO DEFINITO UN PIANO-TURNI
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "verifica-piano-esistente" && !empty($_REQUEST["data"])) {

	// recupero i parametri dal POST
	$parametri = array();
	parse_str($_REQUEST["data"], $parametri);

	// ricavo il periodo di riferimento
	$dataInizioPeriodo = $parametri["gt_DataInizio"];
	$dataFinePeriodo = $parametri["gt_DataFine"];

	// definisco la query di SELEZIONE per verificare se esiste già un piano turni definito per il periodo considerato
	$sth = $conn_mes->prepare("SELECT COUNT(*) AS DateRilevate
									FROM pianificazione_turni
									WHERE pt_Data >= :DataInizio
									AND pt_Data <= :DataFine", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
	$sth->execute(array(
		":DataInizio" => $parametri["gt_DataInizio"],
		":DataFine" => $parametri["gt_DataFine"]
	));
	$righeTrovate = $sth->fetch(PDO::FETCH_ASSOC);

	// se query non ha restituito righe, non ho piano turno definito per il periodo considerato
	if ($righeTrovate['DateRilevate'] == 0) {
		die("VERIFICA_OK");
	} else {
		die("VERIFICA_KO");
	}
}



// INIZIALIZZA PIANO ORARIO: ELIMINA IL PIANO TURNI ESISTENTE PER IL TRACCIATE IN OGGETTO (Data inizio - Data fine)
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "elimina-piano-esistente" && !empty($_REQUEST["data"])) {
	try {

		// recupero i parametri dal POST
		$parametri = array();
		parse_str($_REQUEST["data"], $parametri);

		// ricavo il periodo di riferimento
		$dataInizioPeriodo = $parametri["gt_DataInizio"];
		$dataFinePeriodo = $parametri["gt_DataFine"];


		// definisco la query di DELETE per la cancellazione del periodo definito
		$sth = $conn_mes->prepare("DELETE FROM pianificazione_turni
										WHERE pt_Data >= :DataInizio
										AND pt_Data <= :DataFine");
		$sth->execute(array(
			":DataInizio" => $parametri["gt_DataInizio"],
			":DataFine" => $parametri["gt_DataFine"]
		));


		die("DELETE_OK");
	} catch (Throwable $t) {

		die("DELETE_KO");
	}
}



// INIZIALIZZA PIANO ORARIO: ISTANZIA IL PIANO TURNI PER IL TRACCIATE CONSIDERATO (Data inizio - Data fine)
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "inizializza-piano-turni" && !empty($_REQUEST["data"])) {

	$conn_mes->beginTransaction();
	try {

		// recupero i parametri dal POST
		$parametri = array();
		parse_str($_REQUEST["data"], $parametri);

		// ricavo il periodo di riferimento
		$dataInizioPeriodo = $parametri["gt_DataInizio"];
		$dataFinePeriodo = $parametri["gt_DataFine"];

		$dip = new DateTime($parametri["gt_DataInizio"]);
		$dfp = new DateTime($parametri["gt_DataFine"]);

		$dataInizioPeriodoQuery = $dip->format('d/m/Y');
		$dataFinePeriodoQuery = $dfp->format('d/m/Y');


		// definisco la query di INSERT per l'inserimento del calendario:
		// inserisco nella tabella 'pianificazione_turni' i giorni compresi nel periodo considerato
		$sqlInsertCalendario = "DECLARE @Counter INT, @TotalCount INT
										SET @Counter = 0
										SET @TotalCount = DateDiff(DD,:DataInizioPeriodo,:DataFinePeriodo)
										  
										WHILE (@Counter <=  @TotalCount)
										BEGIN
										  DECLARE @DateValue DATETIME
										  SET  @DateValue= DATEADD(DD,@Counter,:DataInizioPeriodo2)
										  
										    INSERT INTO pianificazione_turni(pt_Data)
										    VALUES(@DateValue)
										 
										    SET @Counter = @Counter + 1
										END";

		$sthInsertCalendario = $conn_mes->prepare($sqlInsertCalendario);
		$sthInsertCalendario->execute(array(
			":DataInizioPeriodo" => $dataInizioPeriodoQuery,
			":DataInizioPeriodo2" => $dataInizioPeriodoQuery,
			":DataFinePeriodo" => $dataFinePeriodoQuery
		));



		// Ricavo l'elenco delle macchine su cui ha diritto, come impostato da popup
		$elencoLineeSelezionate = $_REQUEST["elencoLineeSelezionate"];
		if (isset($elencoLineeSelezionate)) {

			foreach ($elencoLineeSelezionate as $linea) {

				$nomeCampoLinea = "pt_Linea" . $linea;

				// imposto le variabili contenenti le ore turno per i vari giorni, ricavate dal form
				$oreFeriali = (int) $parametri['gt_OreFeriali'];
				$oreSabato = (int) $parametri['gt_OreSabato'];
				$oreDomenica = (int) $parametri['gt_OreDomenica'];
				$oreFestive = (int) 0;

				// definisco la query di UPDATE per la definizione delle ore turno in base al giorno e per il completamento dei restanti campi (fa sempre riferimento al periodo considerato)
				// tiene conto di:
				// - festività tipiche del calendario italiano (gli vengono assegnate le 'ORE TURNO FESTIVITA'
				// - sabati (gli vengono assegnate le 'ORE TURNO SABATO'
				// - domeniche tipiche del calendario italiano (gli vengono assegnate le 'ORE TURNO DOMENICA'
				// - giorni feriali (gli vengono assegnate le 'ORE TURNO FERIALI'
				$sqlUpdateTurniLinea = "UPDATE pianificazione_turni
											SET
											pt_Anno = YEAR(pt_Data),
											pt_Mese = MONTH(pt_Data),
											pt_Giorno = DAY(pt_Data),
											" . $nomeCampoLinea . "  =
												CASE
													WHEN (DAY(pt_data) = 25 AND MONTH(pt_Data) = 4) THEN :OreFestive1
													WHEN (DAY(pt_data) = 1 AND MONTH(pt_Data) = 1)  THEN :OreFestive2
													WHEN (DAY(pt_data) = 1 AND MONTH(pt_Data) = 5) THEN :OreFestive3
													WHEN (DAY(pt_data) = 2 AND MONTH(pt_Data) = 6)  THEN :OreFestive4
													WHEN (DAY(pt_data) = 15 AND MONTH(pt_Data) = 8) THEN :OreFestive5
													WHEN (DAY(pt_data) = 1 AND MONTH(pt_Data) = 11)  THEN :OreFestive6
													WHEN (DAY(pt_data) = 8 AND MONTH(pt_Data) = 12) THEN :OreFestive7
													WHEN (DAY(pt_data) = 25 AND MONTH(pt_Data) = 12)  THEN :OreFestive8
													WHEN (DAY(pt_data) = 26 AND MONTH(pt_Data) = 12) THEN :OreFestive9
													WHEN (DATEPART(weekday,(pt_Data)) = 6)  THEN :OreSabato
													WHEN (DATEPART(weekday,(pt_data)) = 7) THEN :OreDomenica
													WHEN (DATEPART(weekday,(pt_data)) > 0 AND DATEPART(weekday,(pt_Data)) < 6)  THEN :OreFeriali
												END
											WHERE pt_Data BETWEEN :DataInizioPeriodo AND :DataFinePeriodo";

				$sthUpdateTurniLinea = $conn_mes->prepare($sqlUpdateTurniLinea);
				$sthUpdateTurniLinea->execute(array(
					":OreFestive1" => $oreFestive,
					":OreFestive2" => $oreFestive,
					":OreFestive3" => $oreFestive,
					":OreFestive4" => $oreFestive,
					":OreFestive5" => $oreFestive,
					":OreFestive6" => $oreFestive,
					":OreFestive7" => $oreFestive,
					":OreFestive8" => $oreFestive,
					":OreFestive9" => $oreFestive,
					":OreSabato" => $oreSabato,
					":OreDomenica" =>  $oreDomenica,
					":OreFeriali" => $oreFeriali,
					":DataInizioPeriodo" => $dataInizioPeriodo,
					":DataFinePeriodo" => $dataFinePeriodo
				));
			}
		}

		// Eseguo commit della transazione
		$conn_mes->commit();
		die("OK");
	} catch (Throwable $t) {
		// Eseguo rollback della transazione
		$conn_mes->rollBack();
		die("ERROR");
	}
}

?>



<!DOCTYPE html>
<html lang="it">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>PROSPECT40 | Consultazione piano turni</title>
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
							<h4 class="card-title m-2">CALENDARIO ORARIO LAVORATIVO</h4>
						</div>
						<div class="card-body">


							<div class="row">

								<div class="col-12" style="height: 100%;">
									<div id="calendar"></div>
								</div>

							</div>
						</div>
					</div>
				</div>
				<?php include("inc_footer.php") ?>

			</div>

		</div>

	</div>

	<button type="button" id="inizializza-orari-lavoro" class="mdi mdi-button">INIZIALIZZA ORARI</button>




	<!-- Popup modale di MODIFICA ORARI GIORNO -->
	<div class="modal fade" id="modal-modifica-calendario" tabindex="-1" role="dialog" aria-labelledby="modal-modifica-calendario-label" aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modal-modifica-calendario-label">MODIFICA ORARI</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">

					<form class="forms-sample" id="form-modifica-calendario">

						<div class="col-12">

							<div class="form-group">
								<label for="pt_GiornoSelezionato">Giorno selezionato</label>
								<input type="text" class="form-control form-control-sm dati-popup-modifica " name="pt_GiornoSelezionato" id="pt_GiornoSelezionato" readonly>
							</div>

							<?php

							// definisco la query per la ricerca delle linee produzione disponibili
							$sthLineeProduzione = $conn_mes->prepare("SELECT linee_produzione.*
																		FROM linee_produzione
																		WHERE linee_produzione.lp_IdLinea != 'lin_0P' AND linee_produzione.lp_IdLinea != 'lin_0X'
																		ORDER BY linee_produzione.lp_Descrizione ASC", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
							$sthLineeProduzione->execute();
							$linee = $sthLineeProduzione->fetchAll(PDO::FETCH_ASSOC);

							$indice = 1;

							// per ognuna delle linee disponibili, predispongo il le text box necessarie a contenere le ore turno
							foreach ($linee as $linea) {
								echo
								"<div class='form-group'>
										<label for='pt_Linea" . $linea['lp_IndiceNumericoLinea'] . "'>" . $linea['lp_Descrizione'] . "</label>
										<input type='number' class='form-control form-control-sm dati-popup-modifica' name='pt_Linea" . $linea['lp_IndiceNumericoLinea'] . "' id='pt_Linea" . $linea['lp_IndiceNumericoLinea'] . "'>
									</div>";

								// incremnento l'indice per scorrere le linee
								$indice = $indice + 1;
							}

							?>
							<input type="hidden" id="pt_Data" name="pt_Data" value="">
							<p class="pt-2">Lasciare i campi vuoti se non sono definiti orari per la linea in oggetto.</p>
						</div>
					</form>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-success" id="salva-calendario">Salva</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
				</div>

			</div>
		</div>
	</div>



	<!-- Popup modale di COMPILAZIONE PIANO ORARIO (INIZIALIZZAZIONE) -->
	<div class="modal fade" id="modal-inizializza-orari" tabindex="-1" role="dialog" aria-labelledby="modal-inizializza-orari-label" aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modal-inizializza-orari-label">INIZIALIZZAIONE CALENDARIO LAVORATIVO</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form class="forms-sample" id="form-piano-turni">

						<div class="row">
							<div class="col-12">
								<!-- LINEE PRODUZIONE DISPONIBILI -->
								<div class="form-group">
									<label for="gt_LineeProduzione">Linee definite</label><span style='color:red'> *</span>
									<select class="form-control form-control-sm selectpicker" id="gt_LineeProduzione" name="gt_LineeProduzione" multiple data-live-search="true">
										<?php
										$sth = $conn_mes->prepare("SELECT linee_produzione.*
																		FROM linee_produzione
																		WHERE linee_produzione.lp_IdLinea != 'lin_0P' AND linee_produzione.lp_IdLinea != 'lin_0X' ORDER BY linee_produzione.lp_Descrizione ASC", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
										$sth->execute();
										$linee = $sth->fetchAll(PDO::FETCH_ASSOC);

										if ($linee) {
											foreach ($linee as $linea) {
												echo "<option value='" . $linea['lp_IndiceNumericoLinea'] . "'>" . strtoupper($linea['lp_Descrizione']) . "</option>";
											}
										}
										?>
									</select>
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="gt_DataInizio">Dal:</label><span style='color:red'> *</span>
									<input type="date" class="form-control form-control-sm dati-popup-modifica  obbligatorio" name="gt_DataInizio" id="gt_DataInizio" value="<?php echo date("Y-m-d") ?>">
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="gt_DataFine">Al:</label><span style='color:red'> *</span>
									<input type="date" class="form-control form-control-sm dati-popup-modifica  obbligatorio" name="gt_DataFine" id="gt_DataFine" value="<?php echo date("Y-m-d") ?>">
								</div>
							</div>

							<div class='col-12'>
								<hr>
							</div>
							<div class='col-4'>
								<div class='form-group'>
									<label for='gt_OreFeriali'>Ore FERIALI</label><span style='color:red'> *</span>
									<input type='number' class='form-control form-control-sm dati-popup-modifica input-piano-turni obbligatorio' name='gt_OreFeriali' id='gt_OreFeriali'>
								</div>
							</div>
							<div class='col-4'>
								<div class='form-group'>
									<label for='gt_OreSabato'>Ore SABATO</label><span style='color:red'> *</span>
									<input type='number' class='form-control form-control-sm dati-popup-modifica input-piano-turni obbligatorio' name='gt_OreSabato' id='gt_OreSabato'>
								</div>
							</div>
							<div class='col-4'>
								<div class='form-group'>
									<label for='gt_OreDomenica'>Ore DOMENICA</label><span style='color:red'> *</span>
									<input type='number' class='form-control form-control-sm dati-popup-modifica input-piano-turni obbligatorio' name='gt_OreDomenica' id='gt_OreDomenica'>
								</div>
							</div>

						</div>


					</form>
					<p class="pt-2">Vengono considerati <u>non lavorativi</u>:<br />1 gennaio, 25 aprile, 1 maggio, 2 giugno, 15 agosto, 1 novembre, 8, 25 e 26 dicembre.</p>
				</div>
				<div class="modal-footer">
					<button type="button" id="conferma-piano-turni" class="btn btn-success" data-dismiss="modal">Salva</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
				</div>

			</div>
		</div>
	</div>


	<?php include("inc_js.php") ?>
	<script src="../js/gestionepianoorario.js"></script>

</body>

</html>