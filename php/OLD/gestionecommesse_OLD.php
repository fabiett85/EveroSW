<?php
// in che pagina siamo
$pagina = "gestioneordini";

include("../inc/conn.php");


// debug($_SESSION["utente"],"Utente");

// VISUALIZZAZIONE COMMESSE DI PRODUZIONE GESTIBILI (STATO = 'MEMO', 'ATTIVO' O 'NON ATTIVO')
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "mostra") {
	if (isset($_REQUEST['idStatoOrdine']) && $_REQUEST['idStatoOrdine'] != 0) {
		if ($_REQUEST['idStatoOrdine'] == 10) {
			// estraggo la lista
			$sth = $conn_mes->prepare(
				"SELECT ODP.op_IdProduzione, ODP.op_LineaProduzione, ODP.op_ProgressivoParziale, ODP.op_Riferimento, ODP.op_QtaRichiesta, ODP.op_QtaDaProdurre, ODP.op_Priorita, ODP.op_NoteProduzione, ODP.op_DataFineTeorica, ODP.op_DataProduzione, ODP.op_OraFineTeorica, ODP.op_OraProduzione, ODP.op_Lotto, ODP.op_Stato, P.prd_Descrizione, SO.so_Descrizione, UM.um_Sigla, LP.lp_Descrizione
				FROM ordini_produzione AS ODP
				LEFT JOIN stati_ordine AS SO ON ODP.op_Stato = SO.so_IdStatoOrdine
				LEFT JOIN prodotti AS P ON ODP.op_Prodotto = P.prd_IdProdotto
				LEFT JOIN unita_misura AS UM ON ODP.op_Udm = UM.um_IdRiga
				LEFT JOIN linee_produzione AS LP ON ODP.op_LineaProduzione = LP.lp_IdLinea
				WHERE ODP.op_Stato >= 1 AND ODP.op_Stato <= 3"
			);
			$sth->execute([]);
		} else {
			// estraggo la lista
			$sth = $conn_mes->prepare(
				"SELECT ODP.op_IdProduzione, ODP.op_LineaProduzione, ODP.op_ProgressivoParziale, ODP.op_Riferimento, ODP.op_QtaRichiesta, ODP.op_QtaDaProdurre, ODP.op_Priorita, ODP.op_NoteProduzione, ODP.op_DataFineTeorica, ODP.op_DataProduzione, ODP.op_OraFineTeorica, ODP.op_OraProduzione, ODP.op_Lotto, ODP.op_Stato, P.prd_Descrizione, SO.so_Descrizione, UM.um_Sigla, LP.lp_Descrizione
				FROM ordini_produzione AS ODP
				LEFT JOIN stati_ordine AS SO ON ODP.op_Stato = SO.so_IdStatoOrdine
				LEFT JOIN prodotti AS P ON ODP.op_Prodotto = P.prd_IdProdotto
				LEFT JOIN unita_misura AS UM ON ODP.op_Udm = UM.um_IdRiga
				LEFT JOIN linee_produzione AS LP ON ODP.op_LineaProduzione = LP.lp_IdLinea
				WHERE ODP.op_Stato LIKE :IdStatoOrdine"
			);
			$sth->execute([
				":IdStatoOrdine" => $_REQUEST['idStatoOrdine']
			]);
		}
	} else {
		$sth = $conn_mes->prepare(
			"SELECT ODP.op_IdProduzione, ODP.op_LineaProduzione, ODP.op_ProgressivoParziale, ODP.op_Riferimento, ODP.op_QtaRichiesta, ODP.op_QtaDaProdurre, ODP.op_Priorita, ODP.op_NoteProduzione, ODP.op_DataFineTeorica, ODP.op_DataProduzione, ODP.op_OraFineTeorica, ODP.op_OraProduzione, ODP.op_Lotto, ODP.op_Stato, P.prd_Descrizione, SO.so_Descrizione, UM.um_Sigla, LP.lp_Descrizione
			FROM ordini_produzione AS ODP
			LEFT JOIN stati_ordine AS SO ON ODP.op_Stato = SO.so_IdStatoOrdine
			LEFT JOIN prodotti AS P ON ODP.op_Prodotto = P.prd_IdProdotto
			LEFT JOIN unita_misura AS UM ON ODP.op_Udm = UM.um_IdRiga
			LEFT JOIN linee_produzione AS LP ON ODP.op_LineaProduzione = LP.lp_IdLinea"
		);
		$sth->execute([]);
	}
	$righe = $sth->fetchAll(PDO::FETCH_ASSOC);

	if ($righe) {


		$output = [];

		foreach ($righe as $riga) {

			// formattazione corretta delle date
			$dp = new DateTime($riga["op_DataProduzione"]);
			$op = strtotime($riga["op_OraProduzione"]);
			if (isset($riga["op_DataFineTeorica"])) {
				$dft = new DateTime($riga["op_DataFineTeorica"]);
				$oft = strtotime($riga["op_OraFineTeorica"]);
				$stringaDataFineTeorica = $dft->format('d/m/Y') . " - " . date('H:i', $oft);
			} else {
				$stringaDataFineTeorica = "";
			}

			// Gestione visualizzazione pulsanti 'GESTISCI/ELIMINA'
			if (($riga["op_Stato"] >= 3) && ($riga["op_Stato"] <= 4)) { 	// Ordini caricati, ordini avviati -> mostro pulsanti di AZIONE disabilitati
				$stringaPulsantiAzione = '<div class="dropdown"><button class="btn btn-primary dropdown-toggle mdi mdi-lead-pencil mdi-18px" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Modifica riga" disabled></button></div>';
			} else if ($riga["op_Stato"] == 5) { 								// Ordini chiusi -> mostro pulsante 'RIPRENDI'
				$stringaPulsantiAzione = '<button type="button" class="btn btn-primary btn-lg py-1" id="riprendi-ordine-parziale" data-id-ordine-produzione="' . $riga["op_IdProduzione"] . '" data-id_linea_produzione="' . $riga["op_LineaProduzione"] . '" data-id-progressivo-parziale="' . $riga["op_ProgressivoParziale"] . '" title="Riprendi ordine">RIPRENDI</span></button>';
			} else if ($riga["op_Stato"] == 6) { 								// Manutenzione ordinarie -> mostro pulsante di AZIONE-ELIMINA abilitato
				$stringaPulsantiAzione = '<div class="dropdown">
												<button class="btn btn-primary dropdown-toggle mdi mdi-lead-pencil mdi-18px" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Modifica riga"></button>
												<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
													<a class="dropdown-item cancella-commessa" data-id-ordine-produzione="' . $riga["op_IdProduzione"] . '"  data-id_linea_produzione="' . $riga["op_LineaProduzione"] . '" data-stato-ordine="' . $riga["op_Stato"] . '"><i class="mdi mdi-trash-can"></i>  ELIMINA</a>
												</div>
											</div>';
			} else { 															// Ordini memo, ordini attivi -> mostro pulsanti di AZIONE abilitati
				$stringaPulsantiAzione = '<div class="dropdown">
												<button class="btn btn-primary dropdown-toggle mdi mdi-lead-pencil mdi-18px" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Modifica riga"></button>
												<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
													<a class="dropdown-item gestisci-commessa" data-id-ordine-produzione="' . $riga["op_IdProduzione"] . '"  data-id_linea_produzione="' . $riga["op_LineaProduzione"] . '"><i class="mdi mdi-cog"></i>  GESTISCI</a>
													<a class="dropdown-item cancella-commessa" data-id-ordine-produzione="' . $riga["op_IdProduzione"] . '"  data-id_linea_produzione="' . $riga["op_LineaProduzione"] . '" data-stato-ordine="' . $riga["op_Stato"] . '"><i class="mdi mdi-trash-can"></i>  ELIMINA</a>
												</div>
											</div>';
			}

			//Preparo i dati da visualizzare
			$output[] = [
				"IdProduzione" => ($riga["op_Riferimento"] != "" ? $riga["op_IdProduzione"] . " (" . $riga["op_Riferimento"] . ")" : $riga["op_IdProduzione"]),
				"LineaProduzione" => $riga["lp_Descrizione"],
				"Prodotto" => $riga["prd_Descrizione"],
				"QtaRichiesta" => $riga["op_QtaRichiesta"] . " " . $riga["um_Sigla"],
				"QtaDaProdurre" => $riga["op_QtaDaProdurre"] . " " . $riga["um_Sigla"],
				"DataOraProgrammazione" => $dp->format('d/m/Y') . " - " . date('H:i', $op),
				"DataOraFinePrevista" => $stringaDataFineTeorica,
				"Lotto" => $riga["op_Lotto"],
				"Priorita" => $riga["op_Priorita"],
				"StatoOrdine" => $riga["so_Descrizione"],
				"azioni" => $stringaPulsantiAzione
			];
		}

		die(json_encode(['data' => $output]));
	} else {
		die(['data' => []]);
	}
}


// CORPO DISTINTA PRODOTTI (DPC): RECUPERA VALORI RIGA SELEZIONATA
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "gestisci-ordine-produzione" && !empty($_REQUEST["idProduzione"])) {

	// seleziono i dati della tabella di lavoro 'risorse_coinvolte_work'
	$sth = $conn_mes->prepare(
		"SELECT ordini_produzione.* FROM ordini_produzione
		WHERE ordini_produzione.op_IdProduzione = :IdOrdineProduzione
		AND ordini_produzione.op_Mod = 1"
	);
	$sth->execute([
		":IdOrdineProduzione" => $_REQUEST["idProduzione"]
	]);

	if ($sth->rowCount() == 0) {


		// recupero i dati del dettaglio distinta selezionato
		$sthRecuperaDettaglio = $conn_mes->prepare(
			"SELECT ordini_produzione.*, prodotti.prd_Descrizione, stati_ordine.so_Descrizione, velocita_teoriche.vel_VelocitaTeoricaLinea, unita_misura.um_Sigla
			FROM ordini_produzione LEFT JOIN stati_ordine ON ordini_produzione.op_Stato = stati_ordine.so_IdStatoOrdine
			LEFT JOIN prodotti ON ordini_produzione.op_Prodotto = prodotti.prd_IdProdotto
			LEFT JOIN velocita_teoriche ON prodotti.prd_IdProdotto = velocita_teoriche.vel_IdProdotto AND ordini_produzione.op_LineaProduzione = velocita_teoriche.vel_IdLineaProduzione
			LEFT JOIN unita_misura ON ordini_produzione.op_Udm = unita_misura.um_IdRiga
			WHERE ordini_produzione.op_IdProduzione = :IdProduzione"
		);
		$sthRecuperaDettaglio->execute([
			":IdProduzione" => $_REQUEST["idProduzione"]
		]);
		$riga = $sthRecuperaDettaglio->fetch(PDO::FETCH_ASSOC);

		die(json_encode($riga));
	} else {
		die("BLOCCATO");
	}
}



// CORPO DISTINTA PRODOTTI (DPC): VERIFICA SE UNICA LINEA
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "verifica-unica-linea") {

	// seleziono i dati della tabella di lavoro 'risorse_coinvolte_work'
	$sth = $conn_mes->prepare(
		"SELECT linee_produzione.lp_IdLinea FROM linee_produzione
		WHERE linee_produzione.lp_IdLinea != 'lin_0X' AND linee_produzione.lp_IdLinea != 'lin_0P'"
	);
	$sth->execute([]);
	$riga = $sth->fetch(PDO::FETCH_ASSOC);

	if ($sth->rowCount()) {
		die(json_encode($riga));
	} else {
		die("MULTIPLA");
	}
}


// DETTAGLIO DISTINTA RISORSE PER L'COMMESSA DI PRODUZIONE CONSIDERATO (PREDISPONGO DATI NELLA RELATIVA TABELLA DI LAVORO E LA VISUALIZZO)
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "inizializza-distinte" && !empty($_REQUEST["idProduzione"]) && !empty($_REQUEST["idProdotto"])) {
	$conn_mes->beginTransaction();
	try {
		if (isset($_REQUEST["soloRisorse"]) && ($_REQUEST["soloRisorse"] == 1)) {

			// query di eliminazione della risorsa dalla tabella di lavoro 'risorse_coinvolte'
			$sthDeleteRisorsaTabellaLavoro = $conn_mes->prepare(
				"DELETE FROM risorse_coinvolte_work
				WHERE risorse_coinvolte_work.rc_IdProduzione = :IdOrdineProduzione"
			);
			$sthDeleteRisorsaTabellaLavoro->execute([":IdOrdineProduzione" => $_REQUEST["idProduzione"]]);


			// verifico se in tabella 'risorse_coinvolte' ho già entry per quell'Id produzione
			$sthVerificaRisorseCoinvolte = $conn_mes->prepare(
				"SELECT risorse_coinvolte.* FROM risorse_coinvolte
				WHERE risorse_coinvolte.rc_IdProduzione = :IdOrdineProduzione
				AND risorse_coinvolte.rc_LineaProduzione = :IdLineaProduzione"
			);
			$sthVerificaRisorseCoinvolte->execute([
				":IdOrdineProduzione" => $_REQUEST["idProduzione"],
				":IdLineaProduzione" => $_REQUEST["idLineaProduzione"]
			]);
			$risorse = $sthVerificaRisorseCoinvolte->fetch();
			// se in tabella 'risorse_coinvolte' ho già entry relative all'Id produzione considerato, le prelevo e le inserisco nella tabella di lavoro temporanea, in caso contrario popolo questa con le entry provenienti dalla distinta risorse base.
			if ($risorse) {
				$sthInizializzaDistintaRisorse = $conn_mes->prepare(
					"INSERT INTO risorse_coinvolte_WORK (rc_IdProduzione, rc_IdRisorsa, rc_LineaProduzione, rc_NoteIniziali, rc_RegistraMisure, rc_FlagUltima, rc_IdRicetta)
					SELECT rc_IdProduzione, rc_IdRisorsa, rc_LineaProduzione, rc_NoteIniziali, rc_RegistraMisure, rc_FlagUltima, rc_IdRicetta FROM risorse_coinvolte
					WHERE (risorse_coinvolte.rc_IdProduzione = :IdOrdineProduzione AND risorse_coinvolte.rc_LineaProduzione = :IdLineaProduzione)"
				);
				$sthInizializzaDistintaRisorse->execute([
					":IdOrdineProduzione" => $_REQUEST["idProduzione"],
					":IdLineaProduzione" => $_REQUEST["idLineaProduzione"]
				]);
			} else {
				$sthVerificaDistintaRisorse = $conn_mes->prepare(
					"SELECT distinta_risorse_corpo.* FROM distinta_risorse_corpo
					WHERE distinta_risorse_corpo.drc_IdProdotto = :IdProdotto
					AND distinta_risorse_corpo.drc_LineaProduzione = :IdLineaProduzione"
				);
				$sthVerificaDistintaRisorse->execute([
					":IdProdotto" => $_REQUEST["idProdotto"],
					":IdLineaProduzione" => $_REQUEST["idLineaProduzione"]
				]);
				$distintaRisorse = $sthVerificaDistintaRisorse->fetch();

				if ($distintaRisorse) {
					$sthInizializzaDistintaRisorse = $conn_mes->prepare(
						"INSERT INTO risorse_coinvolte_WORK (rc_IdProduzione, rc_IdRisorsa, rc_LineaProduzione, rc_NoteIniziali,  rc_RegistraMisure, rc_FlagUltima, rc_IdRicetta)
						SELECT :IdOrdineProduzione, drc_IdRisorsa, drc_LineaProduzione, drc_NoteSetup, ris_AbiMisure, drc_FlagUltima, drc_IdRicetta FROM distinta_risorse_corpo
						LEFT JOIN risorse ON distinta_risorse_corpo.drc_IdRisorsa = risorse.ris_IdRisorsa
						WHERE (
							distinta_risorse_corpo.drc_IdProdotto = :IdProdotto AND (
								distinta_risorse_corpo.drc_LineaProduzione = :IdLineaProduzione
								OR distinta_risorse_corpo.drc_LineaProduzione = 'lin_00'
							)
						)"
					);
					$sthInizializzaDistintaRisorse->execute([
						":IdOrdineProduzione" => $_REQUEST["idProduzione"],
						":IdProdotto" => $_REQUEST["idProdotto"],
						":IdLineaProduzione" => $_REQUEST["idLineaProduzione"]
					]);
				} else {
					$sthInizializzaDistintaRisorse = $conn_mes->prepare(
						"INSERT INTO risorse_coinvolte_WORK (rc_IdProduzione, rc_IdRisorsa, rc_LineaProduzione,  rc_RegistraMisure, rc_FlagUltima)
						SELECT :IdOrdineProduzione, ris_IdRisorsa, ris_LineaProduzione,  ris_AbiMisure, ris_FlagUltima FROM risorse
						WHERE risorse.ris_LineaProduzione = :IdLineaProduzione"
					);
					$sthInizializzaDistintaRisorse->execute([":IdOrdineProduzione" => $_REQUEST["idProduzione"], ":IdLineaProduzione" => $_REQUEST["idLineaProduzione"]]);
				}
			}
		} else {

			// query di eliminazione della risorsa dalla tabella di lavoro 'risorse_coinvolte'
			$sthDeleteRisorsaTabellaLavoro = $conn_mes->prepare(
				"DELETE FROM risorse_coinvolte_work
				WHERE risorse_coinvolte_work.rc_IdProduzione = :IdOrdineProduzione"
			);
			$sthDeleteRisorsaTabellaLavoro->execute([":IdOrdineProduzione" => $_REQUEST["idProduzione"]]);


			// verifico se in tabella 'risorse_coinvolte' ho già entry per quell'Id produzione
			$sthVerificaRisorseCoinvolte = $conn_mes->prepare(
				"SELECT risorse_coinvolte.* FROM risorse_coinvolte
				WHERE risorse_coinvolte.rc_IdProduzione = :IdOrdineProduzione
				AND risorse_coinvolte.rc_LineaProduzione = :IdLineaProduzione"
			);
			$sthVerificaRisorseCoinvolte->execute([":IdOrdineProduzione" => $_REQUEST["idProduzione"], ":IdLineaProduzione" => $_REQUEST["idLineaProduzione"]]);
			$risorse = $sthVerificaRisorseCoinvolte->fetch();

			// se in tabella 'risorse_coinvolte' ho già entry relative all'Id produzione considerato, le prelevo e le inserisco nella tabella di lavoro temporanea, in caso contrario popolo questa con le entry provenienti dalla distinta risorse base.
			if ($risorse) {
				$sthInizializzaDistintaRisorse = $conn_mes->prepare(
					"INSERT INTO risorse_coinvolte_WORK (rc_IdProduzione, rc_IdRisorsa, rc_LineaProduzione, rc_NoteIniziali, rc_RegistraMisure, rc_FlagUltima, rc_IdRicetta)
					SELECT rc_IdProduzione, rc_IdRisorsa, rc_LineaProduzione, rc_NoteIniziali, rc_RegistraMisure, rc_FlagUltima, rc_IdRicetta FROM risorse_coinvolte
					WHERE risorse_coinvolte.rc_IdProduzione = :IdOrdineProduzione
					AND risorse_coinvolte.rc_LineaProduzione = :IdLineaProduzione"
				);
				$sthInizializzaDistintaRisorse->execute([
					":IdOrdineProduzione" => $_REQUEST["idProduzione"],
					":IdLineaProduzione" => $_REQUEST["idLineaProduzione"]
				]);
			} else {
				$sthVerificaDistintaRisorse = $conn_mes->prepare(
					"SELECT distinta_risorse_corpo.* FROM distinta_risorse_corpo
					WHERE distinta_risorse_corpo.drc_IdProdotto = :IdProdotto
					AND distinta_risorse_corpo.drc_LineaProduzione = :IdLineaProduzione"
				);
				$sthVerificaDistintaRisorse->execute([":IdProdotto" => $_REQUEST["idProdotto"], ":IdLineaProduzione" => $_REQUEST["idLineaProduzione"]]);
				$distintaRisorse = $sthVerificaDistintaRisorse->fetch();

				if ($distintaRisorse) {
					$sthInizializzaDistintaRisorse = $conn_mes->prepare(
						"INSERT INTO risorse_coinvolte_WORK (rc_IdProduzione, rc_IdRisorsa, rc_LineaProduzione, rc_NoteIniziali,  rc_RegistraMisure, rc_FlagUltima, rc_IdRicetta)
						SELECT :IdOrdineProduzione, drc_IdRisorsa, drc_LineaProduzione, drc_NoteSetup, ris_AbiMisure, drc_FlagUltima, drc_IdRicetta FROM distinta_risorse_corpo
						LEFT JOIN risorse ON distinta_risorse_corpo.drc_IdRisorsa = risorse.ris_IdRisorsa
						WHERE (
							distinta_risorse_corpo.drc_IdProdotto = :IdProdotto AND (
								distinta_risorse_corpo.drc_LineaProduzione = :IdLineaProduzione
								OR distinta_risorse_corpo.drc_LineaProduzione = 'lin_00'
							)
						)"
					);
					$sthInizializzaDistintaRisorse->execute([
						":IdOrdineProduzione" => $_REQUEST["idProduzione"],
						":IdProdotto" => $_REQUEST["idProdotto"],
						":IdLineaProduzione" => $_REQUEST["idLineaProduzione"]
					]);
				} else {
					$sthInizializzaDistintaRisorse = $conn_mes->prepare(
						"INSERT INTO risorse_coinvolte_WORK (rc_IdProduzione, rc_IdRisorsa, rc_LineaProduzione,  rc_RegistraMisure, rc_FlagUltima)
						SELECT :IdOrdineProduzione, ris_IdRisorsa, ris_LineaProduzione,  ris_AbiMisure, ris_FlagUltima FROM risorse
						WHERE risorse.ris_LineaProduzione = :IdLineaProduzione"
					);
					$sthInizializzaDistintaRisorse->execute([
						":IdOrdineProduzione" => $_REQUEST["idProduzione"],
						":IdLineaProduzione" => $_REQUEST["idLineaProduzione"]
					]);
				}
			}


			// query di eliminazione del componente dalla tabella di lavoro 'componenti_work'
			$sthDeleteComponenteTabellaLavoro = $conn_mes->prepare(
				"DELETE FROM componenti_work
				WHERE componenti_work.cmp_IdProduzione = :IdOrdineProduzione"
			);
			$sthDeleteComponenteTabellaLavoro->execute([":IdOrdineProduzione" => $_REQUEST["idProduzione"]]);


			// verifico se in tabella 'componenti' ho già entry per quell'Id produzione
			$sthVerificaComponenti = $conn_mes->prepare(
				"SELECT componenti.* FROM componenti
				WHERE componenti.cmp_IdProduzione = :IdOrdineProduzione"
			);
			$sthVerificaComponenti->execute([":IdOrdineProduzione" => $_REQUEST["idProduzione"]]);
			$componenti = $sthVerificaComponenti->fetch();

			// se in tabella 'componenti' ho già entry relative all'Id produzione considerato, le prelevo e le inserisco nella tabella di lavoro temporanea, in caso contrario popolo questa con le entry provenienti dalla distinta risorse base.
			if ($componenti) {
				$sthInizializzaDistintaComponenti = $conn_mes->prepare(
					"INSERT INTO componenti_WORK (cmp_IdProduzione, cmp_Componente, cmp_LineaProduzione, cmp_Qta, cmp_Udm, cmp_FattoreMoltiplicativo, cmp_PezziConfezione)
					SELECT cmp_IdProduzione, cmp_Componente, cmp_LineaProduzione, cmp_Qta, cmp_Udm, cmp_FattoreMoltiplicativo, cmp_PezziConfezione FROM componenti
					WHERE componenti.cmp_IdProduzione = :IdOrdineProduzione"
				);
				$sthInizializzaDistintaComponenti->execute([":IdOrdineProduzione" => $_REQUEST["idProduzione"]]);
			} else {
				$sthInizializzaDistintaComponenti = $conn_mes->prepare(
					"INSERT INTO componenti_WORK (cmp_IdProduzione, cmp_Componente, cmp_Qta, cmp_Udm, cmp_FattoreMoltiplicativo, cmp_PezziConfezione)
					SELECT :IdOrdineProduzione, dpc_Componente, dpc_Quantita, dpc_Udm, dpc_FattoreMoltiplicativo, dpc_PezziConfezione FROM distinta_prodotti_corpo
					LEFT JOIN prodotti ON distinta_prodotti_corpo.dpc_Componente = prodotti.prd_IdProdotto
					WHERE distinta_prodotti_corpo.dpc_Prodotto = :IdProdotto"
				);
				$sthInizializzaDistintaComponenti->execute([
					":IdOrdineProduzione" => $_REQUEST["idProduzione"],
					":IdProdotto" => $_REQUEST["idProdotto"]
				]);
			}

			// query di eliminazione del componente dalla tabella di lavoro 'consumi_work'
			$sthDeleteComponenteTabellaLavoro = $conn_mes->prepare(
				"DELETE FROM consumi_work
				WHERE consumi_work.con_IdProduzione = :IdOrdineProduzione"
			);
			$sthDeleteComponenteTabellaLavoro->execute([":IdOrdineProduzione" => $_REQUEST["idProduzione"]]);


			// verifico se in tabella 'consumi' ho già entry per quell'Id produzione
			$sthVerificaConsumi = $conn_mes->prepare(
				"SELECT consumi.* FROM consumi
				WHERE consumi.con_IdProduzione = :IdOrdineProduzione"
			);
			$sthVerificaConsumi->execute([":IdOrdineProduzione" => $_REQUEST["idProduzione"]]);
			$consumi = $sthVerificaConsumi->fetch();

			// se in tabella 'consumi' ho già entry relative all'Id produzione considerato, le prelevo e le inserisco nella tabella di lavoro temporanea, in caso contrario popolo questa con le entry provenienti dalla distinta risorse base.
			if ($consumi) {
				$sthInizializzaDistintaConsumi = $conn_mes->prepare(
					"INSERT INTO consumi_WORK (con_IdProduzione, con_IdRisorsa, con_IdTipoConsumo, con_ConsumoPezzoIpotetico, con_Rilevato)
					SELECT con_IdProduzione, con_IdRisorsa, con_IdTipoConsumo, con_ConsumoPezzoIpotetico, con_Rilevato FROM consumi
					WHERE consumi.con_IdProduzione = :IdOrdineProduzione"
				);
				$sthInizializzaDistintaConsumi->execute([":IdOrdineProduzione" => $_REQUEST["idProduzione"]]);
			} else {
				$sthInizializzaDistintaConsumi = $conn_mes->prepare(
					"INSERT INTO consumi_WORK (con_IdProduzione, con_IdRisorsa, con_IdTipoConsumo, con_ConsumoPezzoIpotetico, con_Rilevato)
					SELECT :IdOrdineProduzione, dc_IdRisorsa, dc_IdTipoConsumo, dc_ValoreIpotetico, dc_TipoCalcolo FROM distinta_consumi
					LEFT JOIN risorse ON risorse.ris_IdRisorsa = distinta_consumi.dc_IdRisorsa
					WHERE risorse.ris_LineaProduzione = :IdLineaProduzione"
				);
				$sthInizializzaDistintaConsumi->execute([
					":IdOrdineProduzione" => $_REQUEST["idProduzione"],
					":IdLineaProduzione" => $_REQUEST["idLineaProduzione"]
				]);
			}
		}
		$conn_mes->commit();

		die("OK");
	} catch (Exception $exc) {
		$conn_mes->rollBack();
		die($exc->getMessage());
	}
}


// CORPO DISTINTA RISORSE (DPC): CONFERMA
if (!empty($_REQUEST["azione"])  && $_REQUEST["azione"] == "blocca-ordine" && !empty($_REQUEST["idProduzione"])) {

	// aggiorno l'ordine di produzione impsotandone il blocco per modifica in corso
	$sthUpdate = $conn_mes->prepare(
		"UPDATE ordini_produzione SET
		op_Mod = 1
		WHERE op_IdProduzione = :IdProduzione"
	);
	$sthUpdate->execute([":IdProduzione" => $_REQUEST['idProduzione']]);

	die("OK");
}


// CORPO DISTINTA RISORSE (DPC): CONFERMA
if (!empty($_POST["azione"])  && $_POST["azione"] == "sblocca-ordine" && !empty($_POST["idProduzione"])) {


	// aggiorno l'ordine di produzione impsotandone il blocco per modifica in corso
	$sthUpdate = $conn_mes->prepare(
		"UPDATE ordini_produzione SET
		op_Mod = 0
		WHERE op_IdProduzione = :IdProduzione"
	);
	$sthUpdate->execute([":IdProduzione" => $_POST['idProduzione']]);

	die("OK");
}


// PULIZIA TABELLE DI LAVORO
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "pulizia-tabelle-lavoro" && !empty($_REQUEST["idProduzione"])) {

	$conn_mes->beginTransaction();

	try {
		// query di eliminazione della risorsa dalla tabella di lavoro 'risorse_coinvolte'
		$sthDeleteRisorsaTabellaLavoro = $conn_mes->prepare(
			"DELETE FROM risorse_coinvolte_work
		WHERE risorse_coinvolte_work.rc_IdProduzione = :IdOrdineProduzione"
		);
		$sthDeleteRisorsaTabellaLavoro->execute([":IdOrdineProduzione" => $_REQUEST["idProduzione"]]);



		// query di eliminazione del componente dalla tabella di lavoro 'componenti_work'
		$sthDeleteComponenteTabellaLavoro = $conn_mes->prepare(
			"DELETE FROM componenti_work
		WHERE componenti_work.cmp_IdProduzione = :IdOrdineProduzione"
		);
		$sthDeleteComponenteTabellaLavoro->execute([":IdOrdineProduzione" => $_REQUEST["idProduzione"]]);

		$sthDeleteConsumoTabellaLavoro = $conn_mes->prepare(
			"DELETE FROM consumi_work
		WHERE con_IdProduzione = :IdOrdineProduzione"
		);
		$sthDeleteConsumoTabellaLavoro->execute([":IdOrdineProduzione" => $_REQUEST["idProduzione"]]);

		$conn_mes->commit();
		die("OK");
	} catch (\Throwable $th) {
		$conn_mes->rollBack();
		die($th->getMessage());
		//throw $th;
	}
}





// DETTAGLIO DISTINTA RISORSE PER L'COMMESSA DI PRODUZIONE CONSIDERATO (PREDISPONGO DATI NELLA RELATIVA TABELLA DI LAVORO E LA VISUALIZZO)
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "mostra-distinta-risorse" && !empty($_REQUEST["idProduzione"])) {

	// seleziono i dati della tabella di lavoro 'risorse_coinvolte_work'
	$sth = $conn_mes->prepare(
		"SELECT RCW.*, R.ris_Descrizione, R.ris_Ordinamento, RM.ricm_Ricetta, RM.ricm_Descrizione
		FROM risorse_coinvolte_work AS RCW
		LEFT JOIN risorse AS R ON RCW.rc_IdRisorsa = R.ris_IdRisorsa
		LEFT JOIN ordini_produzione AS ODP ON RCW.rc_IdProduzione = ODP.op_IdProduzione
		LEFT JOIN ricette_macchina AS RM ON RCW.rc_IdRicetta = RM.ricm_Ricetta
		WHERE RCW.rc_IdProduzione = :IdOrdineProduzione"
	);
	$sth->execute([":IdOrdineProduzione" => $_REQUEST["idProduzione"]]);

	$output = [];

	if ($sth->rowCount() > 0) {
		$righe = $sth->fetchAll(PDO::FETCH_ASSOC);

		foreach ($righe as $riga) {

			$marked = '<div class="d-flex justify-content-center align-items-center" style="max-height:18px"><i class="mdi mdi-checkbox-marked mdi-18px"></i></div>';
			$unmarked = '<div class="d-flex justify-content-center align-items-center" style="max-height:18px"><span class="mdi mdi-checkbox-blank-outline mdi-18px"></span></div>';


			//Preparo i dati da visualizzare
			$output[] = [
				"IdRisorsa" => $riga["rc_IdRisorsa"],
				"Descrizione" => $riga["ris_Descrizione"],
				"Ricetta" => (isset($riga["ricm_Ricetta"]) ? $riga["ricm_Ricetta"] . " - " . $riga["ricm_Descrizione"] : "ND"),
				"NoteIniziali" => $riga["rc_NoteIniziali"],
				"RegistraMisure" => ($riga["rc_RegistraMisure"] ? $marked : $unmarked),
				"FlagUltima" => ($riga["rc_FlagUltima"] ? $marked : $unmarked),
				"azioni" => '<div class="dropdown">
						<button class="btn btn-primary dropdown-toggle btn-gestione-ordine" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Modifica riga">
						<span class="mdi mdi-lead-pencil mdi-18px"></span>
						</button>
						<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
							<a class="dropdown-item modifica-risorsa-ordine" data-id-risorsa-ordine="' . $riga["rc_IdRisorsa"] . '" data-id-ordine-produzione="' . $_REQUEST["idProduzione"] . '"><i class="mdi mdi-account-edit"></i> Modifica</a>
							<a class="dropdown-item cancella-risorsa-ordine" data-id-risorsa-ordine="' . $riga["rc_IdRisorsa"] . '" data-id-ordine-produzione="' . $_REQUEST["idProduzione"] . '"><i class="mdi mdi-trash-can"></i> Elimina</a>
						</div>
					</div>',
				"Ordinamento" => $riga["ris_Ordinamento"]
			];
		}

		die(json_encode($output));
	} else {
		die("NO_ROWS");
	}
}


// RECUPERO VELOCITA TEORICA LINEA ATTUALE AL CAMBIAMENTO DELLA LINEA DI PRODUZIONE O DEL PRODOTTO FINITO CONSIDERATI
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "recupera-velocita-teorica" && !empty($_REQUEST["idProdotto"])) {

	//estraggo informazioni su eventuali casi pendenti per la risorsa in oggetto
	$sth = $conn_mes->prepare(
		"SELECT vel_VelocitaTeoricaLinea FROM velocita_teoriche
		WHERE vel_IdProdotto = :IdProdotto AND vel_IdLineaProduzione = :IdLineaProduzione"
	);
	$sth->execute([
		":IdProdotto" => $_REQUEST["idProdotto"],
		":IdLineaProduzione" => $_REQUEST["idLineaProduzione"]
	]);
	$riga = $sth->fetch(PDO::FETCH_ASSOC);

	// aggiungo stato di 'caso pendente' all'array dei risultati
	if ($riga) {
		die($riga["vel_VelocitaTeoricaLinea"]);
	} else {
		die("NO_ROWS");
	}
}




// DETTAGLIO DISTINTA COMPONENTI PER L'COMMESSA DI PRODUZIONE CONSIDERATO (PREDISPONGO DATI NELLA RELATIVA TABELLA DI LAVORO E LA VISUALIZZO)
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "mostra-distinta-componenti" && !empty($_REQUEST["idProduzione"])) {

	// seleziono i dati della tabella di lavoro 'componenti_work'
	$sth = $conn_mes->prepare(
		"SELECT componenti_work.*, prodotti.prd_Descrizione, unita_misura.*, ordini_produzione.op_QtaDaProdurre
		FROM componenti_work
		LEFT JOIN prodotti ON componenti_work.cmp_Componente = prodotti.prd_IdProdotto
		LEFT JOIN ordini_produzione ON componenti_work.cmp_IdProduzione = ordini_produzione.op_IdProduzione
		LEFT JOIN unita_misura ON componenti_work.cmp_Udm = unita_misura.um_IdRiga
		WHERE componenti_work.cmp_IdProduzione = :IdOrdineProduzione"
	);
	$sth->execute([":IdOrdineProduzione" => $_REQUEST["idProduzione"]]);
	$righe = $sth->fetchAll(PDO::FETCH_ASSOC);

	$output = [];

	if ($righe) {

		foreach ($righe as $riga) {

			//Preparo i dati da visualizzare
			$output[] = [
				"IdProdotto" => $riga["cmp_Componente"],
				"Descrizione" => $riga["prd_Descrizione"],
				"UdmComponente" => $riga["um_Sigla"] . " (" . $riga["um_Descrizione"] . ")",
				"FattoreMoltiplicativo" => $riga["cmp_FattoreMoltiplicativo"],
				"PezziConfezione" => $riga["cmp_PezziConfezione"],
				"Fabbisogno" => ($_REQUEST["qtaDaProdurre"] != "" ? (ceil(($_REQUEST["qtaDaProdurre"] * $riga["cmp_FattoreMoltiplicativo"]) / $riga["cmp_PezziConfezione"])) . " " . $riga["um_Sigla"] : ""),
				"azioni" => '<div class="dropdown">
						<button class="btn btn-primary dropdown-toggle btn-gestione-ordine" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Modifica riga">
						<span class="mdi mdi-lead-pencil mdi-18px"></span>
						</button>
						<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
							<a class="dropdown-item modifica-componente-ordine" data-id-componente-ordine="' . $riga["cmp_Componente"] . '" data-id-ordine-produzione="' . $_REQUEST["idProduzione"] . '"><i class="mdi mdi-account-edit"></i> Modifica</a>
							<a class="dropdown-item cancella-componente-ordine" data-id-componente-ordine="' . $riga["cmp_Componente"] . '" data-id-ordine-produzione="' . $_REQUEST["idProduzione"] . '"><i class="mdi mdi-trash-can"></i> Elimina</a>
						</div>
					</div>'
			];
		}

		die(json_encode($output));
	} else {
		die("NO_ROWS");
	}
}

// DETTAGLIO DISTINTA COMPONENTI PER L'COMMESSA DI PRODUZIONE CONSIDERATO (PREDISPONGO DATI NELLA RELATIVA TABELLA DI LAVORO E LA VISUALIZZO)
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "mostra-distinta-consumi" && !empty($_REQUEST["idProduzione"])) {

	// seleziono i dati della tabella di lavoro 'consumi_work'
	$sth = $conn_mes->prepare(
		"SELECT consumi_work.*, tipo_consumo.tc_Descrizione, unita_misura.*, risorse.ris_Descrizione FROM consumi_work
		LEFT JOIN tipo_consumo ON consumi_work.con_IdTipoConsumo = tipo_consumo.tc_IdRiga
		LEFT JOIN unita_misura ON tipo_consumo.tc_Udm = unita_misura.um_IdRiga
		LEFT JOIN risorse ON consumi_work.con_IdRisorsa = risorse.ris_IdRisorsa
		WHERE consumi_work.con_IdProduzione = :IdOrdineProduzione"
	);
	$sth->execute([":IdOrdineProduzione" => $_REQUEST["idProduzione"]]);
	$righe = $sth->fetchAll(PDO::FETCH_ASSOC);

	$output = [];
	$tipoCalcolo = ['Nessun calcolo', 'Rilevato dalla macchina', 'Calcolata in base ad ipotetico'];

	if ($righe) {

		foreach ($righe as $riga) {
			//Preparo i dati da visualizzare
			$output[] = [
				"Macchina" => $riga["ris_Descrizione"],
				"Consumo" => $riga["tc_Descrizione"],
				"Udm" => $riga["um_Sigla"] . " (" . $riga["um_Descrizione"] . ")",
				"TipoCalcolo" => $tipoCalcolo[$riga["con_Rilevato"]],
				"ConsumoIpotetico" => $riga["con_ConsumoPezzoIpotetico"],
				"azioni" =>
				'<div class="dropdown">
					<button class="btn btn-primary dropdown-toggle btn-gestione-ordine" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Modifica riga">
					<span class="mdi mdi-lead-pencil mdi-18px"></span>
					</button>
					<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
						<a class="dropdown-item modifica-consumo-ordine" data-id-consumo="' . $riga["con_IdTipoConsumo"] . '" data-id-ordine-produzione="' . $_REQUEST["idProduzione"] . '" data-id-risorsa="' . $riga["con_IdRisorsa"] . '"><i class="mdi mdi-account-edit"></i> Modifica</a>
						<a class="dropdown-item cancella-consumo-ordine" data-id-consumo="' . $riga["con_IdTipoConsumo"] . '" data-id-ordine-produzione="' . $_REQUEST["idProduzione"] . '" data-id-risorsa="' . $riga["con_IdRisorsa"] . '"><i class="mdi mdi-trash-can"></i> Elimina</a>
					</div>
				</div>'
			];
		}

		die(json_encode($output));
	} else {
		die("NO_ROWS");
	}
}


// INSERISCI DISTINTA MACCHINE
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "inserisci-distinta-risorse" && !empty($_REQUEST["idProduzione"]) && !empty($_REQUEST["idLineaProduzione"])) {

	$conn_mes->beginTransaction();

	try {
		// query di eliminazione della risorsa dalla distinta
		$sthDeleteRisorseCoinvolte = $conn_mes->prepare(
			"DELETE FROM risorse_coinvolte
			WHERE risorse_coinvolte.rc_IdProduzione = :IdOrdineProduzione"
		);
		$sthDeleteRisorseCoinvolte->execute([":IdOrdineProduzione" => $_REQUEST["idProduzione"]]);


		$sthInizializzaDistinta = $conn_mes->prepare(
			"INSERT INTO risorse_coinvolte (rc_IdProduzione, rc_IdRisorsa, rc_LineaProduzione, rc_NoteIniziali,  rc_RegistraMisure, rc_FlagUltima)
			SELECT rc_IdProduzione, rc_IdRisorsa, rc_LineaProduzione, rc_NoteIniziali, rc_RegistraMisure, rc_FlagUltima FROM risorse_coinvolte_WORK
			WHERE risorse_coinvolte_WORK.rc_IdProduzione = :IdOrdineProduzione AND (
				risorse_coinvolte_WORK.rc_LineaProduzione = :IdLineaProduzione
				OR risorse_coinvolte_WORK.rc_LineaProduzione = 'lin_00'
			)"
		);
		$sthInizializzaDistinta->execute([
			":IdOrdineProduzione" => $_REQUEST["idProduzione"],
			":IdLineaProduzione" => $_REQUEST["idLineaProduzione"]
		]);

		$conn_mes->commit();
		die("OK");
	} catch (\Throwable $th) {
		$conn_mes->rollBack();
		die($th->getMessage());
		//throw $th;
	}
}


// INSERISCI DISTINTA COMPONENTI
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "inserisci-distinta-componenti" && !empty($_REQUEST["idProduzione"]) && !empty($_REQUEST["idLineaProduzione"]) && !empty($_REQUEST["idProdotto"])) {
	$conn_mes->beginTransaction();

	try {
		// query di eliminazione della risorsa dalla distinta
		$sthDeleteComponenti = $conn_mes->prepare(
			"DELETE FROM componenti
			WHERE componenti.cmp_IdProduzione = :IdOrdineProduzione"
		);
		$sthDeleteComponenti->execute([":IdOrdineProduzione" => $_REQUEST["idProduzione"]]);


		$sthInizializzaComponenti = $conn_mes->prepare(
			"INSERT INTO componenti (cmp_IdProduzione, cmp_Componente, cmp_LineaProduzione, cmp_Qta, cmp_Udm, cmp_FattoreMoltiplicativo, cmp_PezziConfezione)
			SELECT cmp_IdProduzione, cmp_Componente, cmp_LineaProduzione, cmp_Qta, cmp_Udm, cmp_FattoreMoltiplicativo, cmp_PezziConfezione FROM componenti_WORK
			WHERE componenti_WORK.cmp_IdProduzione = :IdOrdineProduzione"
		);
		$sthInizializzaComponenti->execute([":IdOrdineProduzione" => $_REQUEST["idProduzione"]]);



		// query di eliminazione della risorsa dalla distinta
		$sthDeleteComponentiScarti = $conn_mes->prepare(
			"DELETE FROM scarti
			WHERE scarti.scr_IdProduzione = :IdOrdineProduzione"
		);
		$sthDeleteComponentiScarti->execute([":IdOrdineProduzione" => $_REQUEST["idProduzione"]]);


		/*
		$sthInizializzaComponentiScarti = $conn_mes->prepare("INSERT INTO scarti (scr_IdProduzione, scr_Componente) VALUES (:IdOrdineProduzione, :IdProdotto)");
		$sthInizializzaComponentiScarti->execute([":IdOrdineProduzione" => $_REQUEST["idProduzione"], ":IdProdotto" => $_REQUEST["idProdotto"]]);
		*/

		$sthInizializzaComponentiScarti = $conn_mes->prepare(
			"INSERT INTO scarti (scr_IdProduzione, scr_Componente, scr_Udm)
			SELECT cmp_IdProduzione, cmp_Componente, cmp_Udm FROM componenti_WORK
			WHERE componenti_WORK.cmp_IdProduzione = :IdOrdineProduzione"
		);
		$sthInizializzaComponentiScarti->execute([":IdOrdineProduzione" => $_REQUEST["idProduzione"]]);

		$conn_mes->commit();
		die("OK");
	} catch (\Throwable $th) {
		$conn_mes->rollBack();
		die($th->getMessage());
		//throw $th;
	}
}

if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "inserisci-distinta-consumi" && !empty($_REQUEST["idProduzione"]) && !empty($_REQUEST["idLineaProduzione"]) && !empty($_REQUEST["idProdotto"])) {
	$conn_mes->beginTransaction();

	try {
		// query di eliminazione della risorsa dalla distinta
		$sthDeleteComponenti = $conn_mes->prepare(
			"DELETE FROM consumi
			WHERE con_IdProduzione = :IdOrdineProduzione"
		);
		$sthDeleteComponenti->execute([":IdOrdineProduzione" => $_REQUEST["idProduzione"]]);


		$sthInizializzaComponenti = $conn_mes->prepare(
			"INSERT INTO consumi (con_IdProduzione, con_IdRisorsa, con_IdTipoConsumo, con_ConsumoPezzoIpotetico, con_Rilevato)
			SELECT con_IdProduzione, con_IdRisorsa, con_IdTipoConsumo, con_ConsumoPezzoIpotetico, con_Rilevato
			FROM consumi_work WHERE consumi_work.con_IdProduzione = :IdOrdineProduzione"
		);
		$sthInizializzaComponenti->execute([":IdOrdineProduzione" => $_REQUEST["idProduzione"]]);
		$conn_mes->commit();
		die("OK");
	} catch (\Throwable $th) {
		$conn_mes->rollBack();
		die($th->getMessage());
	}
}

// AGGIORNA DATA E ORA TEORICA DI FINE PRODUZIONE
if (!empty($_REQUEST["azione"])  && $_REQUEST["azione"] == "aggiorna-data-fine") {

	$velTeoricaLinea = (float)$_REQUEST['velocitaTeoricaLinea'];
	$pezziAlSecondo = floatval($velTeoricaLinea / 3600);
	$tempoTeoricoDurataOrdine = intval($_REQUEST['qtaDaProdurre'] / ($pezziAlSecondo != 0 ? $pezziAlSecondo : 1));


	$dtTeoricaFine = new DateTime($_REQUEST['dataProduzione']);
	$dtTeoricaFine->modify('+ ' . $tempoTeoricoDurataOrdine . ' seconds');
	$strTeoricaFine =  $dtTeoricaFine->format('Y-m-d H:i');

	$dataTeoricaFine = substr($strTeoricaFine, 0, 10);
	$oraTeoricaFine = substr($strTeoricaFine, 11, 15);

	die($dataTeoricaFine . "T" . $oraTeoricaFine);
}


// AGGIORNAMENTO COMMESSA PRODUZIONE
if (!empty($_REQUEST["azione"])  && $_REQUEST["azione"] == "aggiorna-ordine-produzione" && !empty($_REQUEST["data"])) {

	// definisco transazione SQL
	$conn_mes->beginTransaction();

	// recupero i parametri dal POST
	$parametri = [];
	parse_str($_REQUEST["data"], $parametri);

	$dataProgrammazione = substr($parametri['op_DataProduzione'], 0, 10);
	$oraProgrammazione = substr($parametri['op_DataProduzione'], 11, 15);

	$dataFineTeorica = substr($parametri['op_DataFine'], 0, 10);
	$oraFineTeorica = substr($parametri['op_DataFine'], 11, 15);

	try {
		// aggiorno l'ordine di produzione impsotandone il blocco per modifica in corso
		$sthUpdateOrdineProduzione = $conn_mes->prepare(
			"UPDATE ordini_produzione SET
			op_DataProduzione = :DataProduzione,
			op_OraProduzione = :OraProduzione,
			op_QtaDaProdurre = :QtaDaProdurre,
			op_Lotto = :Lotto,
			op_NoteProduzione = :NoteProduzione,
			op_Priorita = :Priorita,
			op_Stato = :StatoOrdine,
			op_LineaProduzione = :IdLineaProduzione,
			op_DataFineTeorica = :DataFineTeorica,
			op_OraFineTeorica = :OraFineTeorica,
			op_Udm = :UnitaMisura
			WHERE op_IdProduzione = :IdOrdineProduzione"
		);
		$sthUpdateOrdineProduzione->execute([
			":DataProduzione" => $dataProgrammazione,
			":OraProduzione" => $oraProgrammazione,
			":QtaDaProdurre" => $parametri['op_QtaDaProdurre'],
			":Lotto" => $parametri['op_Lotto'],
			":NoteProduzione" => $parametri['op_NoteProduzione'],
			":Priorita" => $parametri['op_Priorita'],
			":StatoOrdine" => $parametri['op_Stato'],
			":IdLineaProduzione" => $parametri['op_LineeProduzione'],
			":DataFineTeorica" => $dataFineTeorica,
			":OraFineTeorica" => $oraFineTeorica,
			":UnitaMisura" => $parametri['op_Udm'],
			":IdOrdineProduzione" => $parametri['op_IdProduzione']
		]);

		// query di eliminazione da tabella 'velocita_teoriche' per l'ID prodotto e l'ID linea considerati
		$sthDeleteVelocitaTeorica = $conn_mes->prepare(
			"DELETE FROM velocita_teoriche
			WHERE velocita_teoriche.vel_IdProdotto = :IdProdotto
			AND velocita_teoriche.vel_IdLineaProduzione = :IdLineaProduzione"
		);
		$sthDeleteVelocitaTeorica->execute([
			":IdProdotto" => $parametri["op_Prodotto"],
			":IdLineaProduzione" => $parametri["op_LineeProduzione"]
		]);


		// query di inserimento in tabella 'velocita_teoriche' per l'ID prodotto e l'ID linea considerati

		$sthInsertVelocitaTeorica = $conn_mes->prepare(
			"INSERT INTO velocita_teoriche(vel_IdProdotto,vel_IdLineaProduzione,vel_VelocitaTeoricaLinea)
			VALUES(:IdProdotto,:IdLineaProduzione,:VelocitaTeoricaLinea)"
		);
		$sthInsertVelocitaTeorica->execute([
			":IdProdotto" => $parametri["op_Prodotto"],
			":IdLineaProduzione" => $parametri["op_LineeProduzione"],
			":VelocitaTeoricaLinea" => (float) $parametri['vel_VelocitaTeoricaLinea']
		]);
		$conn_mes->commit();
		die("OK");
	} catch (\Throwable $th) {
		$conn_mes->rollBack();
		die($th->getMessage());
		//throw $th;
	}
}




// DISTINTA COMPONENTI (TAB. DI LAVORO): RECUPERA VALORI COMPONENTE SELEZIONATO
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "recupera-componente-ordine" && !empty($_REQUEST["idProduzione"]) && !empty($_REQUEST["idComponente"])) {
	// recupero i dati del dettaglio distinta selezionato
	$sthRecuperaDettaglio = $conn_mes->prepare(
		"SELECT * FROM componenti_work
		WHERE cmp_IdProduzione = :IdOrdineProduzione
		AND cmp_Componente = :IdComponente"
	);
	$sthRecuperaDettaglio->execute([
		":IdOrdineProduzione" => $_REQUEST["idProduzione"],
		":IdComponente" => $_REQUEST["idComponente"]
	]);
	$riga = $sthRecuperaDettaglio->fetch(PDO::FETCH_ASSOC);

	//debug($riga,"RIGA");

	die(json_encode($riga));
}

// DISTINTA COMPONENTI (TAB. DI LAVORO): RECUPERA VALORI COMPONENTE SELEZIONATO
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "recupera-consumo-ordine" && !empty($_REQUEST["idConsumo"]) && !empty($_REQUEST["idProduzione"]) && !empty($_REQUEST["idRisorsa"])) {
	// recupero i dati del dettaglio distinta selezionato
	$sthRecuperaDettaglio = $conn_mes->prepare(
		"SELECT * FROM consumi_work
		WHERE consumi_work.con_IdRisorsa = :IdRisorsa
		AND consumi_work.con_IdProduzione = :IdProduzione
		AND consumi_work.con_IdTipoConsumo = :IdTipoConsumo"
	);
	$sthRecuperaDettaglio->execute([
		":IdRisorsa" => $_REQUEST["idRisorsa"],
		":IdProduzione" => $_REQUEST["idProduzione"],
		":IdTipoConsumo" => $_REQUEST["idConsumo"]
	]);
	$riga = $sthRecuperaDettaglio->fetch(PDO::FETCH_ASSOC);

	die(json_encode($riga));
}

// DISTINTA COMPONENTI (TAB. DI LAVORO): CANCELLAZIONE COMPONENTE DA TAB. DI LAVORO
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "cancella-componente-ordine" && !empty($_REQUEST["idComponente"]) && !empty($_REQUEST["idProduzione"])) {
	// query di eliminazione della risorsa dalla distinta
	$sthDeleteComponente = $conn_mes->prepare(
		"DELETE FROM componenti_work
		WHERE componenti_work.cmp_Componente = :IdComponente
		AND componenti_work.cmp_IdProduzione = :IdProduzione"
	);
	$sthDeleteComponente->execute([
		":IdComponente" => $_REQUEST["idComponente"],
		":IdProduzione" => $_REQUEST["idProduzione"]
	]);

	die("OK");
}

if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "cancella-consumo-ordine" && !empty($_REQUEST["idConsumo"]) && !empty($_REQUEST["idProduzione"]) && !empty($_REQUEST["idRisorsa"])) {
	// query di eliminazione della risorsa dalla distinta
	$sthDeleteComponente = $conn_mes->prepare(
		"DELETE FROM consumi_work
		WHERE consumi_work.con_IdRisorsa = :IdRisorsa
		AND consumi_work.con_IdProduzione = :IdProduzione
		AND consumi_work.con_IdTipoConsumo = :IdTipoConsumo"
	);
	$sthDeleteComponente->execute([
		":IdRisorsa" => $_REQUEST["idRisorsa"],
		":IdProduzione" => $_REQUEST["idProduzione"],
		":IdTipoConsumo" => $_REQUEST["idConsumo"]
	]);

	die("OK");
}

// DISTINTA COMPONENTI (TAB. DI LAVORO): SALVATAGGIO COMPONENTE AGGIUNTO IN TAB. DI LAVORO
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "salva-componente-ordine" && !empty($_REQUEST["data"])) {
	// recupero i parametri dal POST
	$parametri = [];
	parse_str($_REQUEST["data"], $parametri);


	// se devo modificare
	if ($parametri["cmp_Azione"] == "modifica") {

		// modifica

		$sthUpdate = $conn_mes->prepare(
			"UPDATE componenti_work SET
			cmp_Udm = :UnitaDiMisura,
			cmp_FattoreMoltiplicativo = :FattoreMoltiplicativo,
			cmp_PezziConfezione = :PezziConfezione
			WHERE cmp_IdProduzione = :IdOrdineProduzione AND cmp_Componente = :IdComponente"
		);
		$sthUpdate->execute([
			":UnitaDiMisura" => $parametri['cmp_Udm'],
			":FattoreMoltiplicativo" => $parametri['cmp_FattoreMoltiplicativo'],
			":PezziConfezione" => $parametri['cmp_PezziConfezione'],
			":IdOrdineProduzione" => $parametri["cmp_IdProduzione"],
			":IdComponente" => $parametri["cmp_Componente"]
		]);

		die("OK");
	} else {

		$sthInsert = $conn_mes->prepare(
			"INSERT INTO componenti_work(cmp_Componente,cmp_IdProduzione,cmp_LineaProduzione,cmp_Udm,cmp_FattoreMoltiplicativo,cmp_PezziConfezione)
			VALUES(:IdComponente,:IdOrdineProduzione,:IdLineaProduzione,:UnitaDiMisura,:FattoreMoltiplicativo,:PezziConfezione)"
		);
		$sthInsert->execute([
			":IdComponente" => $parametri["cmp_Componente"],
			":IdOrdineProduzione" => $parametri["cmp_IdProduzione"],
			":IdLineaProduzione" => $parametri['cmp_IdLineaProduzione'],
			":UnitaDiMisura" => $parametri['cmp_Udm'],
			":FattoreMoltiplicativo" => $parametri['cmp_FattoreMoltiplicativo'],
			":PezziConfezione" => $parametri['cmp_PezziConfezione']
		]);

		die("OK");
	}
}

// DISTINTA COMPONENTI (TAB. DI LAVORO): SALVATAGGIO COMPONENTE AGGIUNTO IN TAB. DI LAVORO
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "salva-consumo-ordine" && !empty($_REQUEST["data"])) {
	// recupero i parametri dal POST
	$parametri = [];
	parse_str($_REQUEST["data"], $parametri);


	// se devo modificare
	if ($parametri["con_Azione"] == "modifica") {

		// modifica

		$sthUpdate = $conn_mes->prepare(
			"UPDATE consumi_work SET
			con_Rilevato = :Rilevato,
			con_ConsumoPezzoIpotetico = :ConsumoPezzoIpotetico
			WHERE con_IdProduzione = :IdProduzione
			AND con_IdRisorsa = :IdRisorsa
			AND con_IdTipoConsumo = :IdTipoConsumo"
		);
		$sthUpdate->execute([
			":Rilevato" => $parametri['con_Rilevato'],
			":ConsumoPezzoIpotetico" => $parametri['con_ConsumoPezzoIpotetico'],
			":IdProduzione" => $parametri["con_IdProduzione"],
			":IdRisorsa" => $parametri["con_IdRisorsa"],
			":IdTipoConsumo" => $parametri["con_IdTipoConsumo"]
		]);

		die("OK");
	} else {
		// inserimento

		$sthInsert = $conn_mes->prepare(
			"INSERT INTO consumi_work(con_Rilevato,con_ConsumoPezzoIpotetico,con_IdProduzione,con_IdRisorsa,con_IdTipoConsumo)
			VALUES(:Rilevato,:ConsumoPezzoIpotetico,:IdProduzione,:IdRisorsa,:IdTipoConsumo)"
		);
		$sthInsert->execute([
			":Rilevato" => $parametri['con_Rilevato'],
			":ConsumoPezzoIpotetico" => $parametri['con_ConsumoPezzoIpotetico'],
			":IdProduzione" => $parametri["con_IdProduzione"],
			":IdRisorsa" => $parametri["con_IdRisorsa"],
			":IdTipoConsumo" => $parametri["con_IdTipoConsumo"]
		]);

		die("OK");
	}
}


// DISTINTA RISORSE COINVOLTE (TAB. DI LAVORO): RECUPERA VALORI RISORSA SELEZIONATA
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "recupera-risorsa-ordine" && !empty($_REQUEST["idProduzione"]) && !empty($_REQUEST["idRisorsa"])) {
	// recupero i dati del dettaglio distinta selezionato
	$sthRecuperaDettaglio = $conn_mes->prepare(
		"SELECT * FROM risorse_coinvolte_work
		WHERE rc_IdProduzione = :IdOrdineProduzione
		AND rc_IdRisorsa = :IdRisorsa"
	);
	$sthRecuperaDettaglio->execute([
		":IdOrdineProduzione" => $_REQUEST["idProduzione"],
		":IdRisorsa" => $_REQUEST["idRisorsa"]
	]);
	$riga = $sthRecuperaDettaglio->fetch(PDO::FETCH_ASSOC);

	die(json_encode($riga));
}


// DISTINTA RISORSE COINVOLTE (TAB. DI LAVORO): CANCELLAZIONE RISORSA DA TAB. DI LAVORO
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "cancella-risorsa-ordine" && !empty($_REQUEST["idRisorsa"]) && !empty($_REQUEST["idProduzione"])) {
	// query di eliminazione della risorsa dalla distinta
	$sthDeleteRisorsa = $conn_mes->prepare(
		"DELETE FROM risorse_coinvolte_work
		WHERE risorse_coinvolte_work.rc_IdRisorsa = :IdRisorsa
		AND risorse_coinvolte_work.rc_IdProduzione = :IdProduzione"
	);
	$sthDeleteRisorsa->execute([
		":IdRisorsa" => $_REQUEST["idRisorsa"],
		":IdProduzione" => $_REQUEST["idProduzione"]
	]);

	die("OK");
}


// DISTINTA RISORSE COINVOLTE (TAB. DI LAVORO): SALVATAGGIO RISORSA AGGIUNTA IN TAB. DI LAVORO
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "salva-risorsa-ordine" && !empty($_REQUEST["data"])) {
	// recupero i parametri dal POST
	$parametri = [];
	parse_str($_REQUEST["data"], $parametri);

	$statoAbiMisure = $_REQUEST["abiMisure"];
	$statoFlagUltimaMacchina = $_REQUEST["flagUltima"];

	if ($statoFlagUltimaMacchina == 1) {
		$sthUpdateResetFlagUltima = $conn_mes->prepare(
			"UPDATE risorse_coinvolte_work SET
			rc_FlagUltima = '0'
			WHERE rc_IdProduzione = :IdOrdineProduzione"
		);
		$sthUpdateResetFlagUltima->execute([":IdOrdineProduzione" => $parametri["rc_IdProduzione"]]);
	}

	// se devo modificare
	if ($parametri["rc_Azione"] == "modifica") {

		// modifica

		$sthUpdate = $conn_mes->prepare(
			"UPDATE risorse_coinvolte_work SET
			rc_RegistraMisure = :RegistraMisure,
			rc_FlagUltima = :FlagUltima,
			rc_NoteIniziali = :NoteIniziali,
			rc_IdRicetta = :IdRicetta
			WHERE rc_IdProduzione = :IdOrdineProduzione AND rc_IdRisorsa = :IdRisorsa"
		);
		$sthUpdate->execute([
			":RegistraMisure" => $statoAbiMisure,
			":FlagUltima" => $statoFlagUltimaMacchina,
			":NoteIniziali" =>  $parametri['rc_NoteIniziali'],
			":IdRicetta" => $parametri['rc_IdRicetta'],
			":IdOrdineProduzione" => $parametri["rc_IdProduzione"],
			":IdRisorsa" => $parametri["rc_IdRisorsa"]
		]);

		die("OK");
	} else {
		// inserimento

		$sthInsert = $conn_mes->prepare(
			"INSERT INTO risorse_coinvolte_work(rc_IdRisorsa,rc_IdProduzione,rc_LineaProduzione,rc_RegistraMisure,rc_FlagUltima,rc_NoteIniziali,rc_IdRicetta)
			VALUES(:IdRisorsa,:IdOrdineProduzione,:IdLineaProduzione,:RegistraMisure,:FlagUltima,:NoteIniziali,:IdRicetta)"
		);
		$sthInsert->execute([
			":IdRisorsa" => $parametri["rc_IdRisorsa"],
			":IdOrdineProduzione" => $parametri["rc_IdProduzione"],
			":IdLineaProduzione" => $parametri['rc_IdLineaProduzione'],
			":RegistraMisure" => $statoAbiMisure,
			":FlagUltima" => $statoFlagUltimaMacchina,
			":NoteIniziali" =>  $parametri['rc_NoteIniziali'],
			":IdRicetta" => $parametri['rc_IdRicetta']
		]);

		die("OK");
	}
}







// INSERIMENTO COMMESSE: GESTIONE SALVATAGGIO DATI DA POPUP MODIFICA/INSERIMENTO
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "verifica-valori-ripetuti" && !empty($_REQUEST["idProduzione"])) {
	// verifico che il codice cliente inserito, che mi funge da ID, non esista in altri record
	$sthSelectLotto = $conn_mes->prepare(
		"SELECT COUNT(*) AS LottiRipetuti FROM ordini_produzione
		WHERE op_Lotto = :Lotto AND op_IdProduzione != :IdProduzione"
	);
	$sthSelectLotto->execute(
		[
			":Lotto" => $_REQUEST["lottoInserito"],
			":IdProduzione" => $_REQUEST["idProduzione"]
		]
	);
	$trovatiVerificaLotto = $sthSelectLotto->fetch(PDO::FETCH_ASSOC);


	if ($trovatiVerificaLotto['LottiRipetuti'] != 0) {
		die("RIPETUTI");
	} else {
		die("OK");
	}
}



// INSERIMENTO COMMESSE: GESTIONE SALVATAGGIO DATI DA POPUP MODIFICA/INSERIMENTO
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "salva-ordine-produzione" && !empty($_REQUEST["data"])) {
	// recupero i parametri dal POST
	$parametri = [];
	parse_str($_REQUEST["data"], $parametri);


	// se devo modificare
	if ($parametri["azioneComm"] == "modifica") {

		// verifico che il codice cliente inserito, che mi funge da ID, non esista in altri record se non in quello che sto modificando
		$sth = $conn_mes->prepare(
			"SELECT * FROM ordini_produzione
			WHERE op_IdProduzione = :IdProduzione
			AND op_IdProduzione != :IdRiga"
		);
		$sth->execute([
			":IdProduzione" => $parametri["op_IdProduzioneComm"],
			":IdRiga" => $parametri["op_IdOrdine_AuxComm"]
		]);
		$righeTrovate = $sth->fetch(PDO::FETCH_ASSOC);

		if (!$righeTrovate) {
			$id_modifica = $parametri["op_IdOrdine_Aux"];

			$sthUpdate = $conn_mes->prepare(
				"UPDATE ordini_produzione SET
				op_IdProduzione = :IdProduzione,
				op_Riferimento = :RiferimentoCommessa,
				op_Prodotto = :IdProdotto,
				op_DataOrdine = :DataOrdine,
				op_OraOrdine = :OraOrdine,
				op_DataProduzione = :DataProduzione,
				op_OraProduzione = :OraProduzione,
				op_QtaRichiesta = :QtaRichiesta,
				op_QtaDaProdurre = :QtaRichiesta2,
				op_Udm = :UnitaDiMisura,
				op_Lotto = :Lotto,
				op_NoteProduzione = :NoteProduzione
				WHERE op_IdProduzione = :IdRiga"
			);
			$sthUpdate->execute([
				":IdProduzione" => $parametri["op_IdProduzioneComm"],
				":RiferimentoCommessa" => $parametri["op_RiferimentoComm"],
				":IdProdotto" => $parametri["op_ProdottoComm"],
				":DataOrdine" => $parametri["op_DataOrdineComm"],
				":OraOrdine" => $parametri["op_OraOrdineComm"],
				":DataProduzione" => $parametri["op_DataProduzioneComm"],
				":OraProduzione" => $parametri["op_OraProduzioneComm"],
				":QtaRichiesta" => (float)$parametri["op_QtaRichiestaComm"],
				":QtaRichiesta2" => (float)$parametri["op_QtaRichiestaComm"],
				":UnitaDiMisura" => $parametri["op_UdmComm"],
				":Lotto" => $parametri["op_LottoComm"],
				":NoteProduzione" => $parametri["op_NoteProduzioneComm"],
				":IdRiga" => $parametri["op_IdOrdine_Aux"]
			]);
		} else {
			die("L'id inserito: " . $parametri["op_IdProduzioneComm"] . " è già assegnato ad un altro ordine.");
		}
	} else // nuovo inserimento
	{

		// verifico che il codice cliente inserito, che mi funge da ID, non esista in altri record
		$sthSelect = $conn_mes->prepare(
			"SELECT * FROM ordini_produzione
			WHERE op_IdProduzione = :IdProduzione"
		);
		$sthSelect->execute([":IdProduzione" => $parametri["op_IdProduzioneComm"]]);
		$trovati = $sthSelect->fetch(PDO::FETCH_ASSOC);


		if (!$trovati) {

			$sthInsert = $conn_mes->prepare(
				"INSERT INTO ordini_produzione(op_IdProduzione,op_Riferimento,op_Prodotto,op_QtaRichiesta,op_QtaDaProdurre,op_Udm,op_DataOrdine,op_OraOrdine,op_DataProduzione,op_OraProduzione,op_Lotto,op_NoteProduzione)
				VALUES(:IdProduzione,:RiferimentoCommessa,:IdProdotto,:QtaRichiesta,:QtaRichiesta2,:UnitaDiMisura,:DataOrdine,:OraOrdine,:DataProduzione,:OraProduzione,:Lotto,:NoteProduzione)"
			);
			$sthInsert->execute([
				":IdProduzione" => $parametri["op_IdProduzioneComm"],
				":RiferimentoCommessa" => $parametri["op_RiferimentoComm"],
				":IdProdotto" => $parametri["op_ProdottoComm"],
				":QtaRichiesta" => (float)$parametri["op_QtaRichiestaComm"],
				":QtaRichiesta2" => (float)$parametri["op_QtaRichiestaComm"],
				":UnitaDiMisura" => $parametri["op_UdmComm"],
				":DataOrdine" => $parametri["op_DataOrdineComm"],
				":OraOrdine" => $parametri["op_OraOrdineComm"],
				":DataProduzione" => $parametri["op_DataProduzioneComm"],
				":OraProduzione" => $parametri["op_OraProduzioneComm"],
				":Lotto" => $parametri["op_LottoComm"],
				":NoteProduzione" => $parametri["op_NoteProduzioneComm"]
			]);
		} else {
			die("L'id inserito: " . $parametri["op_IdProduzioneComm"] . " è già assegnato ad un altro ordine.");
		}
	}
	die("OK");
}


// INSERIMENTO COMMESSE: GESTIONE CANCELLAZIONE
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "cancella-ordine-produzione" && !empty($_REQUEST["id"])) {
	$conn_mes->beginTransaction();

	try {
		// elimino risorsa da tabella 'ordini_produzione'
		$sthDeleteOrdiniProduzione = $conn_mes->prepare(
			"DELETE FROM ordini_produzione
			WHERE op_IdProduzione = :id"
		);
		$sthDeleteOrdiniProduzione->execute([":id" => $_REQUEST["id"]]);

		// elimino risorsa da tabella 'risorse_coinvolte'
		$sthDeleteRisorseCoinvolte = $conn_mes->prepare(
			"DELETE FROM risorse_coinvolte
			WHERE rc_IdProduzione = :id"
		);
		$sthDeleteRisorseCoinvolte->execute([":id" => $_REQUEST["id"]]);
		$conn_mes->commit();
		die("OK");
	} catch (\Throwable $th) {
		$conn_mes->rollBack();
		die($th->getMessage());
	}
}






//AUSILIARIA: POPOLAMENTO SELECT 'RISORSE DISPONIBILI'
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "caricaSelectRisorse" && !empty($_REQUEST["idProduzione"]) && !empty($_REQUEST["idLineaProduzione"])) {

	// estraggo le risorse disponibili che non fanno ancora parte della distinta per quel prodotto
	if (empty($_REQUEST["risorsa"])) {
		$sth = $conn_mes->prepare(
			"SELECT risorse.* FROM risorse
			WHERE (risorse.ris_LineaProduzione = :IdLineaProduzione OR risorse.ris_LineaProduzione = 'lin_00')
			AND risorse.ris_IdRisorsa NOT IN (
				SELECT risorse_coinvolte_work.rc_IdRisorsa FROM risorse_coinvolte_work
				WHERE risorse_coinvolte_work.rc_IdProduzione = :IdOrdineProduzione AND (
					risorse_coinvolte_work.rc_LineaProduzione = :IdLineaProduzione
					OR risorse_coinvolte_work.rc_LineaProduzione = 'lin_00'
				)
			)"
		);
		$sth->execute([
			":IdOrdineProduzione" => $_REQUEST["idProduzione"],
			":IdLineaProduzione" => $_REQUEST["idLineaProduzione"]
		]);
	} else {
		$sth = $conn_mes->prepare(
			"SELECT risorse.* FROM risorse
			WHERE (risorse.ris_LineaProduzione = :IdLineaProduzione OR risorse.ris_LineaProduzione = 'lin_00')
			AND risorse.ris_IdRisorsa NOT IN (
				SELECT risorse_coinvolte_work.rc_IdRisorsa FROM risorse_coinvolte_work
				WHERE risorse_coinvolte_work.rc_IdProduzione = :IdOrdineProduzione AND (
					risorse_coinvolte_work.rc_LineaProduzione = :IdLineaProduzione
					OR risorse_coinvolte_work.rc_LineaProduzione = 'lin_00'
				)
			) OR risorse.ris_IdRisorsa = :RisorsaSelezionata"
		);
		$sth->execute([":IdOrdineProduzione" => $_REQUEST["idProduzione"], ":RisorsaSelezionata" => $_REQUEST["risorsa"], ":IdLineaProduzione" => $_REQUEST["idLineaProduzione"]]);
	}
	$risorseDisponibili = $sth->fetchAll(PDO::FETCH_ASSOC);

	$optionValue = "";

	//Se ho trovato risorse disponibili
	if ($risorseDisponibili) {


		$optionValue = $optionValue . "<option value=''>Seleziona macchina da aggiungere...</option>";

		//Aggiungo ognuna delle risorse trovate alla select
		foreach ($risorseDisponibili as $risorsa) {
			if (!empty($_REQUEST["risorsa"]) && $_REQUEST["risorsa"] == $risorsa['ris_IdRisorsa']) {
				$optionValue = $optionValue . "<option value=" . $risorsa['ris_IdRisorsa'] . " selected >" . $risorsa['ris_Descrizione'] . " </option>";
			} else {
				$optionValue = $optionValue . "<option value=" . $risorsa['ris_IdRisorsa'] . ">" . $risorsa['ris_Descrizione'] . " </option>";
			}
		}

		echo $optionValue;
		exit();
	} else {

		echo "NO_RIS";
		exit();
	}
}


// AUSILIARIA: POPOLAMENTO SELECT 'COMPONENTI DISPONIBILI'
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "caricaSelectComponenti" && !empty($_REQUEST["idProduzione"])) {
	// estraggo i prodotti disponibili che non fanno ancora parte della distinta per quel prodotto
	if (empty($_REQUEST["componente"])) {
		$sth = $conn_mes->prepare(
			"SELECT prodotti.* FROM prodotti
			WHERE prodotti.prd_Tipo != 'F'
			AND prodotti.prd_IdProdotto NOT IN (
				SELECT componenti_work.cmp_Componente FROM componenti_work
				WHERE componenti_work.cmp_IdProduzione = :IdOrdineProduzione
			)"
		);
		$sth->execute([":IdOrdineProduzione" => $_REQUEST["idProduzione"]]);
	} else {
		$sth = $conn_mes->prepare(
			"SELECT prodotti.* FROM prodotti
			WHERE prodotti.prd_Tipo != 'F'
			AND prodotti.prd_IdProdotto NOT IN (
				SELECT componenti_work.cmp_Componente FROM componenti_work
				WHERE componenti_work.cmp_IdProduzione = :IdOrdineProduzione
			) OR prodotti.prd_IdProdotto = :ComponenteSelezionato"
		);
		$sth->execute([
			":IdOrdineProduzione" => $_REQUEST["idProduzione"],
			":ComponenteSelezionato" => $_REQUEST["componente"]
		]);
	}
	$componentiDisponibili = $sth->fetchAll(PDO::FETCH_ASSOC);

	$optionValue = "";

	//Se ho trovato prodotti disponibili
	if ($componentiDisponibili) {


		//Aggiungo ognuno dei prodotti trovati alla select
		foreach ($componentiDisponibili as $componente) {
			if (!empty($_REQUEST["componente"]) && $_REQUEST["componente"] == $componente['prd_IdProdotto']) {
				$optionValue = $optionValue . "<option value=" . $componente['prd_IdProdotto'] . " selected >" . $componente['prd_Descrizione'] . " </option>";
			} else {
				$optionValue = $optionValue . "<option value=" . $componente['prd_IdProdotto'] . ">" . $componente['prd_Descrizione'] . " </option>";
			}
		}
		die($optionValue);
		exit();
	} else {
		die("NO_CMP");
		exit();
	}
}


//AUSILIARIA: POPOLAMENTO SELECT 'RICETTE'
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "caricaSelectRicette" && !empty($_REQUEST["idRisorsa"])) {
	$sth = $conn_mes->prepare(
		'SELECT ricm_Ricetta, ricm_Descrizione, ricm_IdProdotto
		FROM ricette_macchina WHERE ricm_IdRisorsa = :IdRisorsa'
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

//AUSILIARIA: POPOLAMENTO SELECT 'RICETTE'
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "caricaSelectRisorseConsumi" && !empty($_REQUEST["idProduzione"])) {
	if (empty($_REQUEST['idRisorsa'])) {
		$sth = $conn_mes->prepare(
			'SELECT risorse.ris_IdRisorsa, risorse.ris_Descrizione FROM risorse_coinvolte_work
			LEFT JOIN risorse ON ris_IdRisorsa = rc_IdRisorsa
			WHERE rc_IdProduzione = :IdProduzione'
		);
		$sth->execute([':IdProduzione' => $_REQUEST['idProduzione']]);
	} else {
		$sth = $conn_mes->prepare(
			'SELECT ris_IdRisorsa, ris_Descrizione FROM risorse
			WHERE ris_IdRisorsa = :IdRisorsa'
		);
		$sth->execute([':IdRisorsa' => $_REQUEST['idRisorsa']]);
	}

	$risorseTrovate = $sth->fetchAll(PDO::FETCH_ASSOC);

	$optionValue = "";

	//Se ho trovato risorse disponibili
	if ($risorseTrovate) {


		//Aggiungo ognuna delle risorse trovate alla select
		foreach ($risorseTrovate as $risorsa) {

			$optionValue = $optionValue . "<option value='" . $risorsa['ris_IdRisorsa'] . "'>" . $risorsa['ris_Descrizione'] . " </option>";
		}


		echo $optionValue;
		exit();
	} else {
		echo 'NO_RIS';
		exit();
	}
}

if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "caricaSelectConsumi" && !empty($_REQUEST["idRisorsa"])) {

	if (empty($_REQUEST['idConsumo'])) {
		$sth = $conn_mes->prepare(
			'SELECT * FROM tipo_consumo
			WHERE tc_IdRiga NOT IN (
				SELECT con_IdTipoConsumo FROM consumi_work
				WHERE con_IdRisorsa = :IdRisorsa
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


		echo $optionValue;
		exit();
	} else {

		echo "NO_RIS";
		exit();
	}
}

?>
<!DOCTYPE html>
<html lang="it">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>PROSPECT40 | Gestione commesse</title>
	<?php include("inc_css.php") ?>


</head>

<body>

	<div class="container-scroller">

		<?php include("inc_testata.php") ?>

		<div class="container-fluid page-body-wrapper">



			<div class="main-panel">

				<div class="content-wrapper">

					<div class="card" id="blocco-elenco">

						<div class="card-header" id="headingOne" style="background-color: #ffffff;">
							<h4 class="card-title my-2 mx-2">COMMESSE DA GESTIRE</h4>
						</div>

						<div id="collapseOne" class="collapse multi-collapse show" aria-labelledby="headingOne">

							<div class="card-body">

								<div class="row">

									<!-- Gantt commesse pianificate -->
									<div class="col-12 pt2">
										<div id="timeline_ordini"></div>
									</div>

								</div>
								<div class="row mt-3">
									<div class="col-10">
									</div>
									<div class="col-2">
										<!-- Filtro stato ordini -->
										<div class="form-group">
											<select class="form-control form-control-sm selectpicker" id="filtro-ordini" name="filtro-ordini">
												<?php
												$sth = $conn_mes->prepare(
													"SELECT * FROM stati_ordine
													WHERE stati_ordine.so_IdStatoOrdine = 1
													OR stati_ordine.so_IdStatoOrdine = 2
													OR stati_ordine.so_IdStatoOrdine = 5"
												);
												$sth->execute();
												$prodotti = $sth->fetchAll(PDO::FETCH_ASSOC);

												echo "<option value='%'>Mostra TUTTE</option>";
												echo "<option value=10>Mostra MEMO e ATTIVI</option>";
												foreach ($prodotti as $prodotto) {
													echo "<option value=" . $prodotto['so_IdStatoOrdine'] . ">Mostra " . strtoupper($prodotto['so_TestoSelect']) . "</option>";
												}
												?>
											</select>
										</div>
									</div>

								</div>



								<div class="row">

									<div class="col-12">

										<div class="table-responsive">

											<table id="tabellaDati-ordini" class="table table-striped" style="width:100%">
												<thead>
													<tr>
														<th>Codice commessa (Rif.)</th>
														<th>Linea </th>
														<th>Prodotto </th>
														<th>Qta ric.</th>
														<th>Qta da prod.</th>
														<th>Data programmazione</th>
														<th>Data fine prevista</th>
														<th>Lotto</th>
														<th>Priorità</th>
														<th>Stato</th>
														<th></th>
													</tr>
												</thead>
												<tbody></tbody>

											</table>

										</div>
									</div>
								</div>

							</div>
						</div>
					</div>


					<div class="card mt-2" id="blocco-modifica">

						<div class="card-header" id="headingOne" style="background-color: #ffffff;">
							<h4 class="card-title my-2 mx-2">DETTAGLIO COMMESSA</h4>
						</div>

						<div id="collapseTwo" class="collapse multi-collapse" aria-labelledby="headingOne">

							<div class="card-body">

								<form class="forms-sample" id="form-dati-ordine">
									<!-- Visualizzazione distinte prodotto presenti e dati di quella selezionata -->
									<div class="row">

										<div class="col-lg-3">
											<div class="form-group">
												<label for="op_IdProduzione">Codice commessa</label>
												<input readonly type="text" class="form-control form-control-sm dati-ordine" id="op_IdProduzione" name="op_IdProduzione" aria-label="" aria-describedby="inputGroup-sizing-lg">
											</div>
										</div>

										<div class="col-lg-2">
											<div class="form-group">
												<label for="op_Prodotto">Codice prodotto</label>
												<input readonly type="text" class="form-control form-control-sm dati-ordine" id="op_Prodotto" name="op_Prodotto" aria-label="" aria-describedby="inputGroup-sizing-lg">
											</div>
										</div>

										<div class="col-lg-3">
											<div class="form-group">
												<label for="prd_Descrizione">Prodotto</label>
												<input readonly type="text" class="form-control form-control-sm dati-ordine" id="prd_Descrizione" name="prd_Descrizione" aria-label="" aria-describedby="inputGroup-sizing-lg">
											</div>
										</div>

										<div class="col-lg-2">
											<div class="form-group">
												<label for="op_QtaRichiesta">Qta ric.<span class="ml-1 udm">[Pz]</span></label>
												<input readonly type="text" class="form-control form-control-sm dati-ordine" id="op_QtaRichiesta" name="op_QtaRichiesta" aria-label="" aria-describedby="inputGroup-sizing-lg">
											</div>
										</div>

										<div class="col-lg-2">
											<div class="form-group">
												<label for="op_DataOrdine">Compilato il</label>
												<input readonly type="datetime-local" class="form-control form-control-sm dati-ordine" id="op_DataOrdine" name="op_DataOrdine" aria-label="" aria-describedby="inputGroup-sizing-lg">
											</div>
										</div>

										<div class="col-lg-6">
											<div class="form-group">
												<label for="op_NoteProduzione">Note</label>
												<input type="text" class="form-control form-control-sm dati-ordine" id="op_NoteProduzione" name="op_NoteProduzione" aria-label="" aria-describedby="inputGroup-sizing-lg">
											</div>
										</div>

										<div class="col-2">
											<div class="form-group">
												<label for="op_Udm">Unità di misura</label><span style='color:red'> *</span>
												<select class="form-control form-control-sm dati-popup-modifica selectpicker" name="op_Udm" id="op_Udm" data-live-search="true">
													<?php
													$sth = $conn_mes->prepare(
														"SELECT * FROM unita_misura"
													);
													$sth->execute();
													$trovate = $sth->fetchAll(PDO::FETCH_ASSOC);
													foreach ($trovate as $udm) {
														echo "<option value='" . $udm['um_IdRiga'] . "'>" . $udm['um_Sigla'] . "</option>";
													}
													?>
												</select>
											</div>
										</div>

										<div class="col-lg-2">
											<div class="form-group">
												<label for="op_QtaDaProdurre">Qta da prod.<span class="ml-1 udm">[Pz]</span></label><span style='color:red'> *</span>
												<input type="number" class="form-control form-control-sm dati-popup-modifica obbligatorio" id="op_QtaDaProdurre" name="op_QtaDaProdurre" aria-label="" aria-describedby="inputGroup-sizing-lg">
											</div>
										</div>

										<div class="col-lg-2">
											<div class="form-group">
												<label for="op_DataProduzione">Programmato per</label><span style='color:red'> *</span>
												<input type="datetime-local" class="form-control form-control-sm dati-popup-modifica obbligatorio" id="op_DataProduzione" name="op_DataProduzione" aria-label="" aria-describedby="inputGroup-sizing-lg" value="">
											</div>
										</div>

										<div class="col-lg-2">
											<div class="form-group">
												<label class="label-evidenziata" for="op_Stato">Stato</label><span style='color:red'> *</span>
												<select class="form-control form-control-sm selectpicker test-disabilitato" id="op_Stato" name="op_Stato">
													<?php
													$sth = $conn_mes->prepare(
														"SELECT stati_ordine.* FROM stati_ordine
														WHERE stati_ordine.so_IdStatoOrdine < 3"
													);
													$sth->execute();
													$linee = $sth->fetchAll(PDO::FETCH_ASSOC);

													foreach ($linee as $linea) {
														echo "<option value=" . $linea['so_IdStatoOrdine'] . ">" . $linea['so_Descrizione'] . "</option>";
													}
													?>
												</select>
											</div>
										</div>

										<div class="col-lg-3">
											<div class="form-group">
												<label class="label-evidenziata" for="op_LineeProduzione">Linee disponibili</label><span style='color:red'> *</span>
												<select class="form-control form-control-sm selectpicker dati-popup-modifica" id="op_LineeProduzione" name="op_LineeProduzione">
													<?php
													$sth = $conn_mes->prepare(
														"SELECT linee_produzione.* FROM linee_produzione
														WHERE linee_produzione.lp_IdLinea != 'lin_0P'
														AND  linee_produzione.lp_IdLinea != 'lin_0X'"
													);
													$sth->execute();
													$linee = $sth->fetchAll(PDO::FETCH_ASSOC);

													if ($linee) {
														foreach ($linee as $linea) {
															echo "<option value=" . $linea['lp_IdLinea'] . ">" . $linea['lp_Descrizione'] . "</option>";
														}
													} else {
														echo "<option value='' >Nessuna linea disponibile</option>";
													}
													?>
												</select>
											</div>
										</div>

										<div class="col-lg-2">
											<div class="form-group">
												<label class="label-evidenziata" for="vel_VelocitaTeoricaLinea">Vel. t. linea .<span class="ml-1 udm-vel">[Pz]</span></label><span style='color:red'> *</span>
												<input type="number" class="form-control form-control-sm dati-ordine obbligatorio" id="vel_VelocitaTeoricaLinea" name="vel_VelocitaTeoricaLinea" aria-label="" aria-describedby="inputGroup-sizing-lg">
											</div>
										</div>

										<div class="col-lg-1">
											<div class="form-group">
												<label class="label-evidenziata" for="op_Priorita">Priorità</label><span style='color:red'> *</span>
												<input type="number" class="form-control form-control-sm dati-popup-modifica obbligatorio" id="op_Priorita" name="op_Priorita" aria-label="" aria-describedby="inputGroup-sizing-lg">
											</div>
										</div>
										<div class="col-lg-2">
											<div class="form-group">
												<label class="label-evidenziata" for="op_Lotto">Lotto</label><span style='color:red'> *</span>
												<input type="text" class="form-control form-control-sm dati-ordine obbligatorio" id="op_Lotto" name="op_Lotto" aria-label="" aria-describedby="inputGroup-sizing-lg">
											</div>
										</div>

										<div class="col-lg-2">
											<div class="form-group">
												<label for="op_DataFine">Termine previsto</label><span style='color:red'> *</span>
												<input type="datetime-local" class="form-control form-control-sm dati-popup-modifica obbligatorio" id="op_DataFine" name="op_DataFine" aria-label="" aria-describedby="inputGroup-sizing-lg" value="">
											</div>
										</div>

									</div>
								</form>

								<ul class="nav nav-tabs mt-4" id="tab-distinte" role="tablist">
									<li class="nav-item text-center" style="width: 20%;">
										<a aria-controls="risorse" aria-selected="true" class="nav-link  active show" data-toggle="tab" href="#risorse" id="tab-risorse" role="tab"><b>DISTINTA MACCHINE</b></a>
									</li>
									<li class="nav-item text-center" style="width: 20%">
										<a aria-controls="componenti" aria-selected="true" class="nav-link" data-toggle="tab" href="#componenti" id="tab-componenti" role="tab"><b>DISTINTA COMPONENTI</b></a>
									</li>
									<li class="nav-item text-center" style="width: 20%">
										<a aria-controls="consumi" aria-selected="true" class="nav-link" data-toggle="tab" href="#consumi" id="tab-consumi" role="tab"><b>DISTINTA CONSUMI</b></a>
									</li>
								</ul>

								<div class="tab-content">

									<div aria-labelledby="tab-risorse" class="tab-pane fade show active" id="risorse" role="tabpanel">

										<div class="table-responsive">

											<table id="tabellaDati-distinta-risorse" class="table table-striped" style="width:100%" data-source="gestionecommesse.php?azione=mostra-dettagli-distinte">
												<thead>
													<tr>
														<th>Id macchina</th>
														<th>Descrizione</th>
														<th>Ricetta</th>
														<th>Note iniziali</th>
														<th>Reg. misure</th>
														<th>Ultima</th>
														<th></th>
														<th>Ordinamento</th> <!-- Nascosto -->
													</tr>
												</thead>
												<tbody></tbody>

											</table>

										</div>
									</div>

									<div aria-labelledby="tab-componenti" class="tab-pane fade" id="componenti" role="tabpanel">

										<div class="table-responsive">

											<table id="tabellaDati-distinta-componenti" class="table table-striped" style="width:100%" data-source="gestionecommesse.php?azione=mostra-dettagli-componenti">
												<thead>
													<tr>
														<th>Id componente</th>
														<th>Descrizione</th>
														<th>Udm</th>
														<th>Coeff. moltipl.</th>
														<th>Pz confezione</th>
														<th>Fabbisogno</th>
														<th></th>
													</tr>
												</thead>
												<tbody></tbody>

											</table>

										</div>

									</div>

									<div aria-labelledby="tab-consumi" class="tab-pane fade" id="consumi" role="tabpanel">

										<div class="table-responsive">

											<table id="tabellaDati-distinta-consumi" class="table table-striped" style="width:100%" data-source="gestionecommesse.php?azione=mostra-dettagli-componenti">
												<thead>
													<tr>
														<th>Macchina</th>
														<th>Tipo consumo</th>
														<th>Udm</th>
														<th>Tipo calcolo</th>
														<th>Consumo Ipotetico Per Pezzo</th>
														<th></th>
													</tr>
												</thead>
												<tbody></tbody>

											</table>

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

	<button type="button" id="nuova-commessa" class="mdi mdi-button">NUOVA COMMESSA</button>

	<!-- Pulsanti -->
	<button type="button" id="aggiungi-componente-ordine" class="mdi mdi-button btn-gestione-ordine" hidden>AGGIUNGI ELEMENTO</button>
	<button type="button" id="aggiungi-risorsa-ordine" class="mdi mdi-button btn-gestione-ordine" hidden>AGGIUNGI MACCHINA</button>
	<button type="button" id="annulla-modifica-ordine" class="mdi mdi-button" hidden>ANNULLA</button>
	<button type="button" id="conferma-modifica-ordine" class="mdi mdi-button btn-gestione-ordine" hidden>CONFERMA</button>
	<button type="button" id="aggiungi-consumo-ordine" class="mdi mdi-button btn-gestione-ordine" hidden>AGGIUNGI CONSUMO</button>



	<!-- Popup modale di AGGIUNTA RISORSA -->
	<div class="modal fade" id="modal-nuova-risorsa" tabindex="-1" role="dialog" aria-labelledby="modal-nuova-risorsa-label" aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modal-nuova-risorsa-label">AGGIUNTA MACCHINA</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form class="forms-sample" id="form-nuova-risorsa">

						<div class="row">
							<div class="col-12">
								<div class="form-group">
									<label for="rc_IdProduzione">Codice commessa</label>
									<input type="text" class="form-control form-control-sm dati-popup-modifica" name="rc_IdProduzione" id="rc_IdProduzione" readonly>
								</div>
							</div>
							<div class="col-12">
								<div class="form-group">
									<label for="rc_NomeLineaProduzione">Linea di produzione selezionata</label>
									<input type="text" class="form-control form-control-sm dati-popup-modifica" name="rc_NomeLineaProduzione" id="rc_NomeLineaProduzione" readonly>
								</div>
							</div>
							<div class="col-12">
								<div class="form-group">
									<label for="rc_IdRisorsa">Macchina</label>
									<select class="form-control form-control-sm dati-popup-modifica selectpicker" id="rc_IdRisorsa" name="rc_IdRisorsa">

									</select>
								</div>
							</div>
							<div class="col-12">
								<div class="form-group">
									<label for="rc_IdRicetta">Ricetta</label>
									<select class="form-control form-control-sm dati-popup-modifica selectpicker" id="rc_IdRicetta" name="rc_IdRicetta">

									</select>
								</div>
							</div>
							<div class="col-12">
								<div class="form-group">
									<label for="rc_NoteIniziali">Note di setup</label>
									<input type="text" class="form-control form-control-sm dati-popup-modifica" name="rc_NoteIniziali" id="rc_NoteIniziali" autocomplete="off">
								</div>
							</div>
							<div class="col-6">
								<div class="form-check">
									<input id="rc_RegistraMisure" type="checkbox">
									<label for="rc_RegistraMisure">Abilita registrazione misure</label>
								</div>
							</div>
							<div class="col-6">
								<div class="form-check">
									<input id="rc_FlagUltima" type="checkbox">
									<label for="rc_FlagUltima">Ultima risorsa linea</label>
								</div>
							</div>
						</div>

						<input type="hidden" id="rc_IdLineaProduzione" name="rc_IdLineaProduzione" value="">
						<input type="hidden" id="rc_Azione" name="rc_Azione" value="nuovo">


					</form>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-success" id="salva-risorsa-ordine">Salva</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
				</div>

			</div>
		</div>
	</div>

	<!-- Popup modale di AGGIUNTA COMPONENTE -->
	<div class="modal fade" id="modal-nuovo-componente" tabindex="-1" role="dialog" aria-labelledby="modalNuovoComponenteLabel" aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modal-nuovo-componente-label">AGGIUNTA COMPONENTE</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form class="forms-sample" id="form-nuovo-componente">
						<div class="row">
							<div class="col-12">

								<div class="form-group">
									<label for="cmp_IdProduzione">Ordine di produzione</label>
									<input type="text" class="form-control form-control-sm dati-popup-modifica" name="cmp_IdProduzione" id="cmp_IdProduzione" pla readonly>
								</div>
							</div>
							<div class="col-12">
								<div class="form-group">
									<label for="cmp_Componente">Componente</label>
									<select class="form-control form-control-sm dati-popup-modifica selectpicker" id="cmp_Componente" name="cmp_Componente" data-live-search="true">

									</select>
								</div>
							</div>

							<div class="col-4">
								<div class="form-group">
									<label for="cmp_Udm">Unità di misura</label>
									<select class="form-control form-control-sm dati-popup-modifica selectpicker" name="cmp_Udm" id="cmp_Udm">
										<?php
										$sth = $conn_mes->prepare(
											"SELECT * FROM unita_misura"
										);
										$sth->execute();
										$trovate = $sth->fetchAll(PDO::FETCH_ASSOC);
										foreach ($trovate as $udm) {
											echo "<option value='" . $udm['um_IdRiga'] . "'>" . $udm['um_Sigla'] . "</option>";
										}
										?>
									</select>
								</div>
							</div>

							<div class="col-4">
								<div class="form-group">
									<label for="cmp_FattoreMoltiplicativo">Coeff. moltipl.</label>
									<input type="number" class="form-control form-control-sm dati-popup-modifica" name="cmp_FattoreMoltiplicativo" id="cmp_FattoreMoltiplicativo" autocomplete="off">
								</div>
							</div>

							<div class="col-4">
								<div class="form-group">
									<label for="cmp_PezziConfezione">Pz confezione</label>
									<input type="number" class="form-control form-control-sm dati-popup-modifica" name="cmp_PezziConfezione" id="cmp_PezziConfezione" autocomplete="off">
								</div>
							</div>

							<input type="hidden" id="cmp_IdLineaProduzione" name="cmp_IdLineaProduzione" value="">
							<input type="hidden" id="cmp_Azione" name="cmp_Azione" value="nuovo">
						</div>
					</form>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-success" id="salva-componente-ordine">Salva</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
				</div>

			</div>
		</div>
	</div>

	<!-- Popup modale di AGGIUNTA COMPONENTE -->
	<div class="modal fade" id="modal-nuovo-consumo" tabindex="-1" role="dialog" aria-labelledby="modalNuovoComponenteLabel" aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modal-nuovo-consumo-label">AGGIUNTA COMPONENTE</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form class="forms-sample" id="form-nuovo-consumo">
						<div class="row">
							<div class="col-12">
								<div class="form-group">
									<label for="con_IdProduzione">Ordine di produzione</label>
									<input type="text" class="form-control form-control-sm dati-popup-modifica" name="con_IdProduzione" id="con_IdProduzione" pla readonly>
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="con_IdRisorsa">Macchina</label>
									<select class="form-control form-control-sm dati-popup-modifica selectpicker" name="con_IdRisorsa" id="con_IdRisorsa" data-live-search="true">
									</select>
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="con_IdTipoConsumo">Consumo</label>
									<select class="form-control form-control-sm dati-popup-modifica selectpicker" id="con_IdTipoConsumo" name="con_IdTipoConsumo" data-live-search="true">

									</select>
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="con_Rilevato">Tipo calcolo</label>
									<select class="form-control form-control-sm dati-popup-modifica selectpicker" name="con_Rilevato" id="con_Rilevato">
										<option value=0>Nessun calcolo</option>
										<option value=1>Rilevato dalla macchina</option>
										<option value=2>Calcolata in base ad ipotetico</option>
									</select>
								</div>
							</div>

							<div class="col-6">
								<div class="form-group">
									<label for="con_ConsumoPezzoIpotetico">Consumo ipotetico per pezzo</label>
									<input type="number" class="form-control form-control-sm dati-popup-modifica" name="con_ConsumoPezzoIpotetico" id="con_ConsumoPezzoIpotetico" autocomplete="off">
								</div>
							</div>

							<input type="hidden" id="con_IdLineaProduzione" name="con_IdLineaProduzione" value="">
							<input type="hidden" id="con_Azione" name="con_Azione" value="nuovo">
						</div>
					</form>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-success" id="salva-consumo-ordine">Salva</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
				</div>

			</div>
		</div>
	</div>


	<!-- Popup modale di RIEPILOGO DATI COMMESSA DA GANTT -->
	<div class="modal fade" id="modal-dettagli-ordine" tabindex="-1" role="dialog" aria-labelledby="modal-dettagli-ordine-label" aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modalProdottiLabel">RIEPILOGO COMMESSA</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form class="forms-sample" id="form-dettagli-ordine">

						<div class="row">

							<div class="col-6">
								<div class="form-group">
									<label for="op_IdProduzione">Codice commessa</label>
									<input readonly type="text" class="form-control form-control-sm dati-popup-modifica obbligatorio" name="op_IdProduzione" id="op_IdProduzioneGantt">
								</div>
							</div>

							<div class="col-6">
								<div class="form-group">
									<label for="lp_Descrizione">Linea</label>
									<input readonly type="text" class="form-control form-control-sm dati-popup-modifica" name="lp_Descrizione" id="lp_DescrizioneGantt">
								</div>
							</div>

							<div class="col-8">
								<div class="form-group">
									<label for="prd_Descrizione">Prodotto</label>
									<input readonly type="text" class="form-control form-control-sm dati-popup-modifica" name="prd_Descrizione" id="prd_DescrizioneGantt">
								</div>
							</div>

							<div class="col-4">
								<div class="form-group">
									<label for="op_QtaDaProdurre">Qta da prod.</label>
									<input readonly type="text" class="form-control form-control-sm dati-popup-modifica" name="op_QtaDaProdurre" id="op_QtaDaProdurreGantt">
								</div>
							</div>

							<div class="col-12">
								<hr>
							</div>

							<div class="col-6">
								<div class="form-group">
									<label for="DataOraProduzione">Programmato per</label>
									<input type="datetime-local" class="form-control form-control-sm dati-popup-modifica" name="DataOraProduzione" id="DataOraProduzioneGantt">
								</div>
							</div>

							<div class="col-6">
								<div class="form-group">
									<label for="DataOraFineTeorica">Termine previsto</label>
									<input type="datetime-local" class="form-control form-control-sm dati-popup-modifica" name="DataOraFineTeorica" id="DataOraFineTeoricaGantt">
								</div>
							</div>

							<div class="col-6">
								<div class="form-group">
									<label for="op_Stato">Stato commessa</label>
									<select class="form-control form-control-sm selectpicker" id="op_StatoGantt" name="op_Stato">
										<?php
										$sth = $conn_mes->prepare(
											"SELECT stati_ordine.* FROM stati_ordine
											WHERE stati_ordine.so_IdStatoOrdine < 3"
										);
										$sth->execute();
										$linee = $sth->fetchAll(PDO::FETCH_ASSOC);

										foreach ($linee as $linea) {
											echo "<option value=" . $linea['so_IdStatoOrdine'] . ">" . $linea['so_Descrizione'] . "</option>";
										}
										?>
									</select>
								</div>
							</div>

							<input type="hidden" id="vel_VelocitaTeoricaLineaGantt" name="vel_VelocitaTeoricaLinea" value="">
							<input type="hidden" id="op_QtaDaProdurreValGantt" name="op_QtaDaProdurreVal" value="">

						</div>


					</form>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-success" id="salva-lavoro-gantt" data-dismiss="modal">Salva</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
				</div>

			</div>
		</div>
	</div>


	<!-- Opup modale di INSERIMENTO COMMESSA -->
	<div class="modal fade" id="modal-ordine-produzione" tabindex="-1" role="dialog" aria-labelledby="modal-ordine-produzione-label" aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modal-ordine-produzione-label">Nuova produzione</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form class="forms-sample" id="form-ordine-produzione">

						<div class="row">
							<div class="col-7">
								<div class="form-group">
									<label for="op_IdProduzioneComm">Codice commessa</label><span style='color:red'> *</span>
									<input type="text" class="form-control form-control-sm dati-popup-modifica obbligatorio" name="op_IdProduzioneComm" id="op_IdProduzioneComm" autocomplete="off">
								</div>
							</div>
							<div class="col-5">
								<div class="form-group">
									<label for="op_RiferimentoComm">Riferimento commessa</label>
									<input type="text" class="form-control form-control-sm dati-popup-modifica" name="op_RiferimentoComm" id="op_RiferimentoComm" autocomplete="off">
								</div>
							</div>

							<div class="col-12">
								<div class="form-group">
									<label for="op_ProdottoComm">Prodotto</label><span style='color:red'> *</span>
									<select class="form-control form-control-sm dati-popup-modifica selectpicker" id="op_ProdottoComm" name="op_ProdottoComm" data-live-search="true">
										<?php
										$sth = $conn_mes->prepare(
											"SELECT prodotti.* FROM prodotti
											WHERE prodotti.prd_Tipo != 'MP'
											ORDER BY prodotti.prd_Descrizione ASC"
										);
										$sth->execute();
										$linee = $sth->fetchAll(PDO::FETCH_ASSOC);

										foreach ($linee as $linea) {
											echo "<option value='" . $linea['prd_IdProdotto'] . "'>" . $linea['prd_Descrizione'] . "</option>";
										}
										?>
									</select>
								</div>
							</div>
							<div class="col-12">
								<div class="form-group">
									<label for="op_LottoComm">Lotto</label>
									<input type="text" class="form-control form-control-sm dati-popup-modifica" name="op_LottoComm" id="op_LottoComm" autocomplete="off">
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="op_QtaRichiestaComm">Qta richiesta</label><span style='color:red'> *</span>
									<input type="number" class="form-control form-control-sm dati-popup-modifica obbligatorio" name="op_QtaRichiestaComm" id="op_QtaRichiestaComm" autocomplete="off">
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="op_UdmComm">Unità di misura</label><span style='color:red'> *</span>
									<select class="form-control form-control-sm dati-popup-modifica selectpicker" name="op_UdmComm" id="op_UdmComm" data-live-search="true">
										<?php
										$sth = $conn_mes->prepare(
											"SELECT * FROM unita_misura"
										);
										$sth->execute();
										$trovate = $sth->fetchAll(PDO::FETCH_ASSOC);
										foreach ($trovate as $udm) {
											echo "<option value='" . $udm['um_IdRiga'] . "'>" . $udm['um_Sigla'] . "</option>";
										}
										?>
									</select>
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="op_DataOrdineComm">Data compilazione</label><span style='color:red'> *</span>
									<input type="date" class="form-control form-control-sm dati-popup-modifica obbligatorio" name="op_DataOrdineComm" id="op_DataOrdineComm">
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="op_OraOrdineComm">Ora compilazione</label><span style='color:red'> *</span>
									<input type="time" class="form-control form-control-sm dati-popup-modifica obbligatorio" name="op_OraOrdineComm" id="op_OraOrdineComm">
								</div>
							</div>


							<div class="col-6">
								<div class="form-group">
									<label for="op_DataProduzioneComm">Data pianificazione</label><span style='color:red'> *</span>
									<input type="date" class="form-control form-control-sm dati-popup-modifica obbligatorio" name="op_DataProduzioneComm" id="op_DataProduzioneComm">
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="op_OraProduzioneComm">Ora pianificazione</label><span style='color:red'> *</span>
									<input type="time" class="form-control form-control-sm dati-popup-modifica obbligatorio" name="op_OraProduzioneComm" id="op_OraProduzioneComm">
								</div>
							</div>

							<div class="col-12">
								<div class="form-group">
									<label for="op_NoteProduzioneComm">Note produzione</label>
									<input type="text" class="form-control form-control-sm dati-popup-modifica" name="op_NoteProduzioneComm" id="op_NoteProduzioneComm" autocomplete="off">
								</div>
							</div>

						</div>

						<input type="hidden" id="op_IdOrdine_AuxComm" name="op_IdOrdine_AuxComm" value="">
						<input type="hidden" id="azioneComm" name="azioneComm" value="nuovo">

					</form>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-success" id="salva-ordine-produzione">Salva</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
				</div>

			</div>
		</div>
	</div>


	<?php include("inc_modaleripresaordine.php") ?>

	<?php include("inc_js.php") ?>
	<script src="../js/timelineordini.js"></script>
	<script src="../js/gestionecommesse.js"></script>

</body>

</html>