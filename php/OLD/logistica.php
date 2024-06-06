<?php
// in che pagina siamo
$pagina = "logistica_gestionescarti";

include("../inc/conn.php");


// INSERIMENTO SCARTI: VISUALIZZAZIONE ELENCO ORDINI DI PRODUZIONE
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "mostra-elenco-ordini-scarti") {

	// ricavo dalla sessione l'ID della risorsa attualmente selezionata
	$idRisorsa = "";
	if (isset($_SESSION["idRisorsa"])) {
		$idRisorsa = $_SESSION["idRisorsa"];
	} else {
		$idRisorsa = "RIS_01";
	}


	// estraggo la lista degli ordini di produzione nello stato di 'OK' (id = 2) o nello stato di 'ATTIVO' (id = 4), per la risorsa attualmente selezionata, escluso quello attualmente in esecuzione sulla risorsa
	$sth = $conn_mes->prepare("SELECT RLP.rlp_DataInizio, RLP.rlp_OraInizio, RLP.rlp_QtaProdotta, RLP.rlp_QtaScarti, ODP.op_IdProduzione, ODP.op_Riferimento, ODP.op_QtaRichiesta, ODP.op_Lotto, UM.um_Sigla, P.prd_Descrizione, SO.so_Descrizione, LP.lp_Descrizione, ODP.op_Stato
									FROM rientro_linea_produzione AS RLP
									LEFT JOIN ordini_produzione AS ODP ON RLP.rlp_IdProduzione = ODP.op_IdProduzione
									LEFT JOIN linee_produzione AS LP ON ODP.op_LineaProduzione = LP.lp_IdLinea
									LEFT JOIN prodotti AS P ON ODP.op_Prodotto = P.prd_IdProdotto
									LEFT JOIN stati_ordine AS SO ON ODP.op_Stato = SO.so_IdStatoOrdine
									LEFT JOIN unita_misura AS UM ON ODP.op_Udm = UM.um_IdRiga
									WHERE ODP.op_Stato LIKE :StatoOrdine AND RLP.rlp_DataInizio >= :DataInizio AND (RLP.rlp_DataFine <= :DataFine OR RLP.rlp_DataFine IS NULL)", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));

	$sth->execute(array(":StatoOrdine" => $_REQUEST['statoOrdini'], ":DataInizio" => $_REQUEST['dataInizioPeriodo'], ":DataFine" => $_REQUEST['dataFinePeriodo']));

	if ($sth->rowCount() > 0) {

		$output = array();

		$righe = $sth->fetchAll(PDO::FETCH_ASSOC);

		// scorro il recordset restituito
		foreach ($righe as $riga) {

			if (isset($riga["rlp_DataInizio"])) {
				$dInizio = new DateTime($riga["rlp_DataInizio"] . " " . $riga["rlp_OraInizio"]);
				$stringaDataProgrammazione = $dInizio->format('d/m/Y - H:i');
			} else {
				$stringaDataProgrammazione = "";
			}

			// preparo l'array con i risultati
			$output[] = array(
				"DescrizioneLinea" => $riga["lp_Descrizione"],
				"IdProduzione" => ($riga["op_Riferimento"] != "" ? $riga["op_IdProduzione"] . " (" . $riga["op_Riferimento"] . ")" : $riga["op_IdProduzione"]),
				"DescrizioneProdotto" => $riga["prd_Descrizione"],
				"QtaProdotta" => $riga["rlp_QtaProdotta"] . " " . $riga["um_Sigla"],
				"QtaScarti" => $riga["rlp_QtaScarti"] . " " . $riga["um_Sigla"],
				"DataInizio" => $stringaDataProgrammazione,
				"Lotto" => $riga["op_Lotto"],
				"Stato" => $riga["so_Descrizione"],
				"Azioni" => ($riga["op_Stato"] == 4 ?
					'<button class="btn btn-primary mdi mdi-lead-pencil mdi-24px modifica-quantita-ordine" type="button" data-id_riga="' . $riga["op_IdProduzione"] . '" aria-haspopup="true" aria-expanded="false" title="Modifica quantità" disabled></button>'
					:
					'<button class="btn btn-primary mdi mdi-lead-pencil mdi-24px modifica-quantita-ordine" type="button" data-id_riga="' . $riga["op_IdProduzione"] . '" aria-haspopup="true" aria-expanded="false" title="Modifica quantità"></button>'
				),
				"IdProduzioneAux" => $riga["op_IdProduzione"]

			);
		}

		// codifico in JSON array dei risultati e lo ritorno
		die(json_encode($output));
	} else {
		die("NO_ROWS");
	}
}







// INSERIMENTO SCARTI: VISUALIZZAZIONE ELENCO COMMESSE DI PRODUZIONE
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "mostra-elenco-ordini-stato") {

	// ricavo dalla sessione l'ID della risorsa attualmente selezionata
	$idRisorsa = "";
	if (isset($_SESSION["idRisorsa"])) {
		$idRisorsa = $_SESSION["idRisorsa"];
	} else {
		$idRisorsa = "RIS_01";
	}


	// estraggo la lista degli ordini di produzione nello stato di 'OK' (id = 2) o nello stato di 'ATTIVO' (id = 4), per la risorsa attualmente selezionata, escluso quello attualmente in esecuzione sulla risorsa
	$sth = $conn_mes->prepare("SELECT ordini_produzione.op_IdProduzione, ordini_produzione.op_Riferimento, ordini_produzione.op_DataProduzione, ordini_produzione.op_OraProduzione, ordini_produzione.op_QtaRichiesta, ordini_produzione.op_QtaDaProdurre, ordini_produzione.op_Lotto, prodotti.prd_Descrizione, stati_ordine.so_Descrizione, linee_produzione.lp_Descrizione, unita_misura.um_Sigla
									FROM ordini_produzione
									LEFT JOIN linee_produzione ON ordini_produzione.op_LineaProduzione = linee_produzione.lp_IdLinea
									LEFT JOIN prodotti ON ordini_produzione.op_Prodotto = prodotti.prd_IdProdotto
									LEFT JOIN stati_ordine ON ordini_produzione.op_Stato = stati_ordine.so_IdStatoOrdine
									LEFT JOIN unita_misura ON ordini_produzione.op_Udm = unita_misura.um_IdRiga
									WHERE stati_ordine.so_IdStatoOrdine != 5 AND op_LineaProduzione IS NOT NULL", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));

	$sth->execute();
	$righe = $sth->fetchAll(PDO::FETCH_ASSOC);

	$output = array();

	// scorro il recordset restituito
	foreach ($righe as $riga) {

		if (isset($riga["op_DataProduzione"])) {
			$dInizio = new DateTime($riga["op_DataProduzione"] . " " . $riga["op_OraProduzione"]);
			$stringaDataProgrammazione = $dInizio->format('d/m/Y - H:i');
		} else {
			$stringaDataProgrammazione = "";
		}

		// preparo l'array con i risultati
		$output[] = array(
			"DescrizioneLinea" => $riga["lp_Descrizione"],
			"IdProduzione" => ($riga["op_Riferimento"] != "" ? $riga["op_IdProduzione"] . " (" . $riga["op_Riferimento"] . ")" : $riga["op_IdProduzione"]),
			"DescrizioneProdotto" => $riga["prd_Descrizione"],
			"QtaRichiesta" => (isset($riga["op_QtaDaProdurre"]) ? $riga["op_QtaDaProdurre"] : $riga["op_QtaRichiesta"]) . " " . $riga["um_Sigla"],
			"DataInizio" => $stringaDataProgrammazione,
			"Lotto" => $riga["op_Lotto"],
			"Stato" => $riga["so_Descrizione"],
			"IdProduzioneAux" => $riga["op_IdProduzione"]
		);
	}



	// codifico in JSON array dei risultati e lo ritorno
	die(json_encode($output));
}



// REPORT SCARTI: RECUPERA INFORMAZIONI COMMESSA SELEZIONATO
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "recupera-dettaglio-ordine" && !empty($_REQUEST['idProduzione'])) {


	// estraggo la lista degli ordini di produzione nello stato di 'OK' (id = 2) o nello stato di 'ATTIVO' (id = 4), per la risorsa attualmente selezionata, escluso quello attualmente in esecuzione sulla risorsa
	$sth = $conn_mes->prepare("SELECT linee_produzione.lp_Descrizione, ordini_produzione.op_IdProduzione, prodotti.prd_Descrizione, ordini_produzione.op_QtaRichiesta, ordini_produzione.op_DataProduzione, ordini_produzione.op_OraProduzione, ordini_produzione.op_Lotto, stati_ordine.so_Descrizione
									FROM ordini_produzione
									LEFT JOIN linee_produzione ON ordini_produzione.op_LineaProduzione = linee_produzione.lp_IdLinea
									LEFT JOIN prodotti ON ordini_produzione.op_Prodotto = prodotti.prd_IdProdotto
									LEFT JOIN stati_ordine ON ordini_produzione.op_Stato = stati_ordine.so_IdStatoOrdine
									WHERE ordini_produzione.op_IdProduzione = :IdProduzione", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));

	$sth->execute(array(":IdProduzione" => $_REQUEST['idProduzione']));
	$riga = $sth->fetch(PDO::FETCH_ASSOC);

	die(json_encode($riga));
}







// INSERIMENTO SCARTI: VISUALIZZAZIONE COMPONENTI COMMESSA SELEZIONATO
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "mostra-componenti-scarti" && !empty($_REQUEST["idProduzione"])) {



	// estraggo la lista degli ordini di produzione nello stato di 'OK' (id = 2) o nello stato di 'ATTIVO' (id = 4), per la risorsa attualmente selezionata, escluso quello attualmente in esecuzione sulla risorsa
	$sth = $conn_mes->prepare("SELECT scarti.*, prodotti.prd_Descrizione, prodotti.prd_Tipo, unita_misura.*
									FROM scarti
									LEFT JOIN prodotti ON scarti.scr_Componente = prodotti.prd_IdProdotto
									LEFT JOIN unita_misura ON scarti.scr_Udm = unita_misura.um_IdRiga
									WHERE scarti.scr_IdProduzione = :IdProduzione
									ORDER BY prodotti.prd_Tipo ASC", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));

	$sth->execute(array(":IdProduzione" => $_REQUEST["idProduzione"]));

	// se ho trovato risorse disponibili
	if ($sth->rowCount() > 0) {

		$righe = $sth->fetchAll(PDO::FETCH_ASSOC);

		$output = array();

		// scorro il recordset restituito
		foreach ($righe as $riga) {
			// preparo l'array con i risultati
			$output[] = array(
				"IdComponente" => $riga["scr_Componente"],
				"DescrizioneComponente" => $riga["prd_Descrizione"],
				"QtaScarti" => $riga["scr_QtaScarti"],
				"UnitaDiMisura" => $riga["um_Sigla"],
				"TipoComponente" => $riga["prd_Tipo"],
				"Azioni" => '<button class="btn btn-primary modifica-scarti-componente" type="button" data-id_riga="' . $riga["scr_IdRiga"] . '" aria-haspopup="true" aria-expanded="false" title="Modifica scarti">
						<span class="mdi mdi-lead-pencil mdi-18px"></span>
						</button>'
			);
		}

		// codifico in JSON array dei risultati e lo ritorno
		die(json_encode($output));
	} else {
		die("NO_ROWS");
	}
}


// INSERIMENTO SCARTI: VISUALIZZAZIONE COMPONENTI COMMESSA SELEZIONATO
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "mostra-componenti-richiesti" && !empty($_REQUEST["idProduzione"])) {

	// recupero quantità di pezzi da produrre, secondo commessa
	$qtaRichiestaCommessa = floatval($_REQUEST["qtaRichiesta"]);

	// estraggo la lista degli ordini di produzione nello stato di 'OK' (id = 2) o nello stato di 'ATTIVO' (id = 4), per la risorsa attualmente selezionata, escluso quello attualmente in esecuzione sulla risorsa
	$sth = $conn_mes->prepare("SELECT componenti.*, prodotti.prd_Descrizione, unita_misura.*
									FROM componenti
									LEFT JOIN prodotti ON componenti.cmp_Componente = prodotti.prd_IdProdotto
									LEFT JOIN unita_misura ON componenti.cmp_Udm = unita_misura.um_IdRiga
									WHERE componenti.cmp_IdProduzione = :IdProduzione", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));

	$sth->execute(array(":IdProduzione" => $_REQUEST["idProduzione"]));

	// se ho trovato risorse disponibili
	if ($sth->rowCount() > 0) {

		$righe = $sth->fetchAll(PDO::FETCH_ASSOC);

		$output = array();


		// scorro il recordset restituito
		foreach ($righe as $riga) {

			// calcolo il fabbisogno relativo al componente applicando la formula: 'fabbisogno = ((qta pezzi commessa * fattore moltiplicativo componente) / pezzi scatola componente)'
			$fabbisogno = ceil(($qtaRichiestaCommessa * $riga["cmp_FattoreMoltiplicativo"]) / $riga["cmp_PezziConfezione"]);

			// preparo l'array con i risultati
			$output[] = array(
				"IdComponente" => $riga["cmp_Componente"],
				"DescrizioneComponente" => $riga["prd_Descrizione"],
				"QtaScarti" => $fabbisogno . " " . $riga["um_Sigla"]
			);
		}

		// codifico in JSON array dei risultati e lo ritorno
		die(json_encode($output));
	} else {
		die("NO_ROWS");
	}
}



// INSERIMENTO SCARTI: RECUPERO SCARTI COMPONENTE
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "recupera-scarti-componenti" && !empty($_REQUEST["codice"])) {
	// estraggo la lista
	$sth = $conn_mes->prepare("SELECT scarti.*, prodotti.prd_Descrizione
								FROM scarti
								LEFT JOIN prodotti ON scarti.scr_Componente = prodotti.prd_IdProdotto
								WHERE scarti.scr_IdRiga = :codice", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
	$sth->execute(array(":codice" => $_REQUEST["codice"]));
	$riga = $sth->fetch(PDO::FETCH_ASSOC);

	die(json_encode($riga));
}




// INSERIMENTO SCARTI: GESTIONE SALVATAGGIO DATI DA POPUP MODIFICA/INSERIMENTO
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "salva-scarti-componente" && !empty($_REQUEST["data"])) {
	// recupero i parametri dal POST
	$parametri = array();
	parse_str($_REQUEST["data"], $parametri);

	$id_modifica = $parametri["scr_IdRiga"];

	$sqlUpdate = "UPDATE scarti SET
					scr_QtaScarti = :QtaScarti,
					scr_Udm = :UnitaDiMisura
					WHERE scr_IdRiga = :IdRiga";

	$sthUpdate = $conn_mes->prepare($sqlUpdate);
	$sthUpdate->execute(array(
		":QtaScarti" => $parametri["scr_QtaScarti"],
		":UnitaDiMisura" => $parametri["scr_Udm"],
		":IdRiga" => $id_modifica
	));

	die("OK");
}


// INSERIMENTO SCARTI: RECUPERO QUANTITA' ORDINE
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "recupera-quantita-ordine" && !empty($_REQUEST["codice"])) {
	// estraggo la lista
	$sth = $conn_mes->prepare("SELECT RLP.rlp_IdProduzione, RLP.rlp_DataInizio, RLP.rlp_OraInizio, RLP.rlp_DataFine, RLP.rlp_OraFine, RLP.rlp_QtaProdotta, RLP.rlp_QtaScarti, UM.um_Sigla
									FROM rientro_linea_produzione AS RLP
									LEFT JOIN ordini_produzione AS ODP ON RLP.rlp_IdProduzione = ODP.op_IdProduzione
									LEFT JOIN unita_misura AS UM ON ODP.op_Udm = UM.um_IdRiga
									WHERE RLP.rlp_IdProduzione = :IdProduzione", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
	$sth->execute(array(":IdProduzione" => $_REQUEST["codice"]));
	$riga = $sth->fetch(PDO::FETCH_ASSOC);

	if (isset($riga["rlp_DataInizio"])) {
		$dInizio = new DateTime($riga["rlp_DataInizio"] . " " . $riga["rlp_OraInizio"]);
		$stringaDataInizioOrdine = $dInizio->format('d/m/Y - H:i');
	} else {
		$stringaDataInizioOrdine = "";
	}

	if (isset($riga["rlp_DataFine"])) {
		$dFine = new DateTime($riga["rlp_DataFine"] . " " . $riga["rlp_OraFine"]);
		$stringaDataFineOrdine = $dFine->format('d/m/Y - H:i');
	} else {
		$stringaDataFineOrdine = "";
	}

	// preparo l'array con i risultati
	$output = array(
		"rlp_IdProduzione" => $riga["rlp_IdProduzione"],
		"rlp_QtaProdotta" => $riga["rlp_QtaProdotta"],
		"rlp_QtaScarti" => $riga["rlp_QtaScarti"],
		"rlp_DataInizio" => $stringaDataInizioOrdine,
		"rlp_DataFine" => $stringaDataFineOrdine,
		"um_Sigla" => $riga["um_Sigla"],
	);

	die(json_encode($output));
}



// INSERIMENTO SCARTI: GESTIONE SALVATAGGIO QTA COMMESSA RETTIFICATE
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "salva-qta-ordine" && !empty($_REQUEST["data"])) {
	// recupero i parametri dal POST
	$parametri = array();
	parse_str($_REQUEST["data"], $parametri);


	// Ricavo la quantità conforme, avendo quella totale e quella scartata
	$qtaConforme = floatval($parametri["rlp_QtaProdotta"] - $parametri["rlp_QtaScarti"]);



	// Calcolo i valori di |OEE|, |D|, |E|, |Q| aggiornati in relazione alle nuove quantità impostate!
	$sthInformazioniCommessa = $conn_mes->prepare("SELECT RLP.*, VT.vel_VelocitaTeoricaLinea
									FROM rientro_linea_produzione AS RLP
									LEFT JOIN ordini_produzione AS ODP ON RLP.rlp_IdProduzione = ODP.op_IdProduzione
									LEFT JOIN velocita_teoriche AS VT ON ODP.op_Prodotto = VT.vel_IdProdotto AND ODP.op_LineaProduzione = VT.vel_IdLineaProduzione
									WHERE RLP.rlp_IdProduzione = :IdProduzione", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
	$sthInformazioniCommessa->execute(array(":IdProduzione" => $parametri["rlp_IdProduzione"]));
	$rigaInformazioniCommessa = $sthInformazioniCommessa->fetch(PDO::FETCH_ASSOC);

	// Calcolo |VELOCITA' LINEA|
	$uptimeLinea_ore = floatval($rigaInformazioniCommessa['rlp_TTotale'] / 60);
	$velocitaLinea = round(intval($parametri["rlp_QtaProdotta"] / ($uptimeLinea_ore != 0 ? $uptimeLinea_ore : 1)), 2);

	// Calcolo quindi il T. TEORICO PEZZO (in secondi)
	$tempoTeoricoPezzoLinea_pzh = isset($rigaInformazioniCommessa["vel_VelocitaTeoricaLinea"]) ? floatval($rigaInformazioniCommessa["vel_VelocitaTeoricaLinea"]) : 1;
	$tempoTeoricoPezzoLinea_min = floatval($tempoTeoricoPezzoLinea_pzh / 60);

	// Calcolo |OEE LINEA| (N.B: formula modificata senza utilizzo delle 3 componenti: n° pezzi conformi / n° pezzi teorici nel tempo totale)
	$numPezziTeoriciLinea = intval($rigaInformazioniCommessa['rlp_TTotale'] * $tempoTeoricoPezzoLinea_min);
	$numPezziConformiLinea = intval($parametri["rlp_QtaProdotta"]) - intval($parametri["rlp_QtaScarti"]);
	$OEELinea = round(floatval($numPezziConformiLinea / ($numPezziTeoriciLinea != 0 ? $numPezziTeoriciLinea : 1)), 4);
	$OEELinea_perc = floatval($OEELinea * 100);

	// Fattore |D LINEA|
	$uptimeLinea_min = round(floatval($rigaInformazioniCommessa['rlp_TTotale'] - $rigaInformazioniCommessa['rlp_Downtime']), 2);
	$sommaUptimeDowntimeLinea_min = $rigaInformazioniCommessa['rlp_TTotale'];
	$fattoreDLinea = round(floatval($uptimeLinea_min / ($sommaUptimeDowntimeLinea_min != 0 ? $sommaUptimeDowntimeLinea_min : 1)), 4);
	$fattoreDLinea_perc = floatval($fattoreDLinea * 100);

	// Fattore |E LINEA|
	$numPezziTeoriciLineaUptime = intval($uptimeLinea_min * $tempoTeoricoPezzoLinea_min);
	$fattoreELinea = round(floatval($parametri["rlp_QtaProdotta"] / ($numPezziTeoriciLineaUptime != 0 ? $numPezziTeoriciLineaUptime : 1)), 4);
	$fattoreELinea_perc = floatval($fattoreELinea * 100);

	// Fattore |Q LINEA|
	if ($fattoreDLinea != 0 && $fattoreELinea != 0) {
		$prodTempFattori = floatval($fattoreDLinea * $fattoreELinea);
	} else {
		$prodTempFattori = 1;
	}
	$fattoreQLinea = round(floatval($OEELinea / $prodTempFattori), 4);
	$fattoreQLinea_perc = floatval($fattoreQLinea * 100);


	// Aggiorno quindi la entry nella tabella 'rientro_linea_produzione'
	$sqlUpdate = "UPDATE rientro_linea_produzione SET
					rlp_QtaProdotta = :QtaProdotta,
					rlp_QtaConforme = :QtaConforme,
					rlp_QtaScarti = :QtaScarti,
					rlp_D = :FattoreD,
					rlp_E = :FattoreE,
					rlp_Q = :FattoreQ,
					rlp_OEELInea = :OEELinea,
					rlp_VelocitaLinea = :VelocitaLinea
					WHERE rlp_IdProduzione = :IdProduzione";

	$sthUpdate = $conn_mes->prepare($sqlUpdate);
	$sthUpdate->execute(array(
		":QtaProdotta" => floatval($parametri["rlp_QtaProdotta"]),
		":QtaConforme" => $qtaConforme,
		":QtaScarti" => floatval($parametri["rlp_QtaScarti"]),
		":FattoreD" => floatval($fattoreDLinea_perc),
		":FattoreE" => floatval($fattoreELinea_perc),
		":FattoreQ" => floatval($fattoreQLinea_perc),
		":OEELinea" => floatval($OEELinea_perc),
		":VelocitaLinea" => floatval($velocitaLinea),
		":IdProduzione" => $parametri["rlp_IdProduzione"]
	));


	// DEFINITIVO: sintesi esito operazioni
	$conn_mes->beginTransaction();

	if ($sthInformazioniCommessa && $sthUpdate) {
		$conn_mes->commit();
		die("OK");
	} else {
		$conn_mes->rollBack();
		die("ERRORE");
	}
}



?>



<!DOCTYPE html>
<html lang="it">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>PROSPECT40 | LOGISTICA - Gestione logistica</title>
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
							<h4 class="card-title m-2">LOGISTICA</h4>
						</div>
						<div class="card-body">


							<ul class="nav nav-tabs pt-2" id="tab-logistica" role="tablist">
								<li class="nav-item text-center" style="width: calc(100% / 2);">
									<a aria-controls="logistica-stato-ordini" aria-selected="true" class="nav-link rounded-2 show"
										data-toggle="tab" href="#logistica-stato-ordini" id="tab-logistica-stato-ordini"
										role="tab"><b>MONITORAGGIO COMMESSE </b></a>
								</li>
								<li class="nav-item text-center" style="width: calc(100% / 2);">
									<a aria-controls="logistica-gestione-scarti" aria-selected="true" class="nav-link rounded-2"
										data-toggle="tab" href="#logistica-gestione-scarti" id="tab-logistica-gestione-scarti"
										role="tab"><b>GESTIONE SCARTI</b></a>
								</li>
							</ul>

							<div class="tab-content tab-medie-linee">

								<!-- Tab LOGISTICA - MONITORAGGIO STATO COMMESSE -->
								<div aria-labelledby="tab-logistica-stato-ordini" class="tab-pane show" id="logistica-stato-ordini"
									role="tabpanel">

									<div class="row pt-3">

										<div class="col-7">

											<h6>ELENCO COMMESSE IN CORSO O ATTIVI</h6>
											<div class="table-responsive pt-2">

												<table id="tabellaOrdini_statoOrdiniLogistica" class="table table-striped"
													data-source="logistica.php?azione=mostra-elenco-ordini-stato"
													style="margin: 0 auto; width: 100%;">
													<thead>
														<tr>
															<th>Linea</th>
															<th>Codice commessa</th>
															<th>Prodotto </th>
															<th>Qta. richiesta</th>
															<th>Data/ora programmazione</th>
															<th>Stato</th>
															<th>Codice commessa aux</th>
														</tr>
													</thead>
													<tbody></tbody>
												</table>
											</div>
										</div>

										<div class="col-5">

											<h6>COMPONENTI RICHIESTI</h6>
											<div class="table-responsive pt-2">

												<table id="tabellaComponenti_statoOrdiniLogistica" class="table table-striped"
													style="margin: 0 auto; width: 100%;">
													<thead>
														<tr>
															<th>Codice componente </th>
															<th>Descrizione</th>
															<th>Fabbisogno</th>
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
											<button type="button" class="mdi mdi-button" id="logistica-stampa-bolla-prelievo" disabled>STAMPA
												BOLLA DI PRELIEVO</button>
										</div>
									</div>

								</div>


								<!-- Tab LOGISTICA - GESTIONE SCARTI -->
								<div aria-labelledby="tab-logistica-gestione-scarti" class="tab-pane" id="logistica-gestione-scarti"
									role="tabpanel">

									<div class="row pt-2">

										<div class="col-4">
											<div class="form-group">
												<label for="scr_StatoOrdini">STATO COMMESSE</label>
												<select class="form-control form-control-sm selectpicker dati-report" name="scr_StatoOrdini"
													id="scr_StatoOrdini">
													<option value="%">TUTTI</option>
													<option value="4">IN CORSO</option>
													<option value="5">COMPLETATE</option>
												</select>
											</div>
										</div>
										<div class="col-4">
											<div class="form-group">
												<label for="scr_DataInizio">INIZIO PERIODO</label>
												<input type="date" class="form-control form-control-sm obbligatorio dati-report"
													name="scr_DataInizio" id="scr_DataInizio" value="">
											</div>
										</div>
										<div class="col-4">
											<div class="form-group">
												<label for="scr_DataFine">FINE PERIODO</label>
												<input type="date" class="form-control form-control-sm obbligatorio dati-report"
													name="scr_DataFine" id="scr_DataFine" value="">
											</div>

										</div>

									</div>
									<div class="row pt-3">

										<div class="col-7">

											<h6>ELENCO COMMESSE</h6>
											<div class="table-responsive pt-2">

												<table id="tabellaOrdini_regScartiLogistica" class="table table-striped"
													style="margin: 0 auto; width: 100%;">
													<thead>
														<tr>
															<th>Linea</th>
															<th>Codice commessa (Rif.)</th>
															<th>Prodotto </th>
															<th>Qta prod.</th>
															<th>Qta scart.</th>
															<th>Data/ora inizio</th>
															<th>Stato</th>
															<th>Azioni</th>
															<th>Codice commessa aux</th>
														</tr>
													</thead>
													<tbody></tbody>
												</table>

											</div>
										</div>

										<div class="col-5">


											<h6>COMPONENTI UTILIZZATI</h6>
											<div class="table-responsive pt-2">

												<table id="tabellaComponenti_regScartiLogistica" class="table table-striped"
													data-source="logistica.php?azione=mostra-elenco-componenti-scarti"
													style="margin: 0 auto; width: 100%;">
													<thead>
														<tr>
															<th>Codice componente </th>
															<th>Descrizione</th>
															<th>Qta scarti</th>
															<th>Udm</th>
															<th>Tipo componente</th>
															<th>Azioni</th>
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
											<button type="button" class="mdi mdi-button" id="logistica-stampa-report-scarti" disabled>STAMPA
												REPORT SCARTI</button>
										</div>
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


	<!-- Popup modale di aggiunta MODIFICA SCARTI -->
	<div class="modal fade" id="modal-modifica-scarti" tabindex="-1" role="dialog"
		aria-labelledby="modal-modifica-scarti-label" aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modal-modifica-scarti-label">SCARTI COMPONENTE</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">

					<form class="" id="form-scarti-componenti">

						<div class="row">

							<div class="col-12">
								<div class="form-group">
									<label for="scr_Componente">CODICE COMPONENTE</label>
									<input type="text" class="form-control form-control-sm dati-popup-modifica" name="scr_Componente"
										id="scr_Componente" placeholder="" readonly>
								</div>
							</div>

							<div class="col-12">
								<div class="form-group">
									<label for="prd_Descrizione">COMPONENTE</label>
									<input type="text" class="form-control form-control-sm dati-popup-modifica" name="prd_Descrizione"
										id="prd_Descrizione" placeholder="" readonly>
								</div>
							</div>

							<div class="col-6">
								<div class="form-group">
									<label for="scr_QtaScarti">QTA SCARTI</label>
									<input type="number" class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="scr_QtaScarti" id="scr_QtaScarti" placeholder="Quantità">
								</div>
							</div>

							<div class="col-6">
								<div class="form-group">
									<label for="scr_Udm">UNITA' DI MISURA</label>
									<select class="form-control form-control-sm dati-popup-modifica selectpicker" name="scr_Udm"
										id="scr_Udm">
										<?php
										$sth = $conn_mes->prepare("SELECT * FROM unita_misura", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
										$sth->execute();
										$trovate = $sth->fetchAll(PDO::FETCH_ASSOC);
										foreach ($trovate as $udm) {
											echo "<option value='" . $udm['um_IdRiga'] . "'>" . $udm['um_Sigla'] . "</option>";
										}
										?>
									</select>
								</div>
							</div>
						</div>

						<input type="hidden" id="scr_IdRiga" name="scr_IdRiga" value="">
						<input type="hidden" id="scr_IdProduzione" name="scr_IdProduzione" value="">

					</form>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-success" id="salva-scarti-componente">Salva</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
				</div>

			</div>
		</div>
	</div>


	<!-- Popup modale di RETTIFICA QUANTITA' ORDINI TERMINATI -->
	<div class="modal fade" id="modal-rettifica-qta-ordine" tabindex="-1" role="dialog"
		aria-labelledby="modal-rettifica-qta-ordine-label" aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modal-rettifica-qta-ordine-label">RETTIFICA QUANTITA' COMMESSA </h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form class="" id="form-rettifica-qta-ordine">

						<div class="row">

							<div class="col-12">
								<div class="form-group">
									<label for="rlp_IdProduzione">Codice commessa (rif.)</label>
									<input type="text" class="form-control dati-popup-modifica" name="rlp_IdProduzione"
										id="rlp_IdProduzione" placeholder="" readonly>
								</div>
							</div>

							<div class="col-6">
								<div class="form-group">
									<label for="rlp_DataInizio">Data inizio</label>
									<input type="text" class="form-control dati-popup-modifica" name="rlp_DataInizio" id="rlp_DataInizio"
										placeholder="" readonly>
								</div>
							</div>

							<div class="col-6">
								<div class="form-group">
									<label for="rlp_DataFine">Data fine</label>
									<input type="text" class="form-control dati-popup-modifica" name="rlp_DataFine" id="rlp_DataFine"
										placeholder="" readonly>
								</div>
							</div>

							<div class="col-6">
								<div class="form-group">
									<label for="rlp_QtaProdotta">Qta pezzi prodotti<span class="udm-popup-qta"> [Bt]</span></label>
									<input type="number" class="form-control dati-popup-modifica obbligatorio" name="rlp_QtaProdotta"
										id="rlp_QtaProdotta" placeholder="Quantità prodotta">
								</div>
							</div>

							<div class="col-6">
								<div class="form-group">
									<label for="rlp_QtaScarti">Qta pezzi scartati<span class="udm-popup-qta"> [Bt]</span></label>
									<input type="number" class="form-control dati-popup-modifica obbligatorio" name="rlp_QtaScarti"
										id="rlp_QtaScarti" placeholder="Quantità scarti">
								</div>
							</div>

						</div>
					</form>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-success" id="salva-qta-ordine">CONFERMA</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
				</div>

			</div>
		</div>
	</div>


	<?php include("inc_js.php") ?>
	<script src="../js/logistica.js"></script>

</body>

</html>