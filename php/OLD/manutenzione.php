<?php
// in che pagina siamo
$pagina = "manutenzione";

include("../inc/conn.php");


// VISUALIZZAZIONE COMMESSE IN CORSO (STATO = 4 = 'AVVIATO')
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "mostra-ordini-avviati") {
	// estraggo la lista
	$sth = $conn_mes->prepare("SELECT ODP.op_IdProduzione, ODP.op_Riferimento, ODP.op_QtaRichiesta, ODP.op_QtaDaProdurre, ODP.op_Lotto, P.prd_Descrizione, SO.so_Descrizione, RLP.rlp_DataInizio, RLP.rlp_DataFine, RLP.rlp_OraInizio, RLP.rlp_OraFine, RLP.rlp_OEELinea, LP.lp_Descrizione, UM.um_Sigla, VT.vel_VelocitaTeoricaLinea
									FROM ordini_produzione AS ODP
									LEFT JOIN stati_ordine AS SO ON ODP.op_Stato = SO.so_IdStatoOrdine
									LEFT JOIN prodotti AS P ON ODP.op_Prodotto = P.prd_IdProdotto
									LEFT JOIN rientro_linea_produzione AS RLP ON ODP.op_IdProduzione = RLP.rlp_IdProduzione
									LEFT JOIN linee_produzione AS LP ON ODP.op_LineaProduzione = LP.lp_IdLinea
									LEFT JOIN velocita_teoriche AS VT ON ODP.op_Prodotto = VT.vel_IdProdotto AND LP.lp_IdLinea = VT.vel_IdLineaProduzione
									LEFT JOIN unita_misura AS UM ON ODP.op_Udm = UM.um_IdRiga
									WHERE ODP.op_Stato = 4
									ORDER BY RLP.rlp_OraInizio DESC", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
	$sth->execute();
	if ($sth->rowCount() > 0) {
		$righe = $sth->fetchAll(PDO::FETCH_ASSOC);


		$output = array();

		foreach ($righe as $riga) {

			// Recupero il valore del 'numero pezzi prodotti' e del 'numero pezzi scartati', per l'intera linea (somma dei pezzi prodotti e scartati dalle varie risorse coinvolte)
			$sthRecuperaConteggioPezziLinea = $conn_mes->prepare("SELECT rp_QtaProdotta AS NumeroPezziProdotti, rp_QtaScarti AS NumeroPezziScartati, rp_QtaConforme AS NumeroPezziConformi
															FROM  risorse
															LEFT JOIN risorsa_produzione ON risorsa_produzione.rp_IdRisorsa = risorse.ris_IdRisorsa
															WHERE risorsa_produzione.rp_IdProduzione = :IdProduzione AND risorse.ris_FlagUltima = 1", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
			$sthRecuperaConteggioPezziLinea->execute(array(":IdProduzione" => $riga["op_IdProduzione"]));
			$rigaConteggioPezziLinea = $sthRecuperaConteggioPezziLinea->fetch(PDO::FETCH_ASSOC);

			// Verifico dati ritornati dalla query (ultima macchina di linea potrebbe non avere caricato/iniziato l'ordine)
			if ($rigaConteggioPezziLinea) {
				$numPezziProdotti = $rigaConteggioPezziLinea["NumeroPezziProdotti"];
				$numPezziConformi = $rigaConteggioPezziLinea["NumeroPezziConformi"];
				$numPezziScartati = $rigaConteggioPezziLinea["NumeroPezziScartati"];
			} else {
				$numPezziProdotti = 0;
				$numPezziConformi = 0;
				$numPezziScartati = 0;
			}

			// RECUPERO STATO EVENTI DI AVARIA PER LA LINEA
			// Verifico presenza di stati di 'attrezzaggio' sulle risorse coinvolte nell'ordine in oggetto
			$sthRecuperaStatoAttrezzaggio = $conn_mes->prepare("SELECT COUNT(*) AS EventiAttrezzaggio
																FROM risorsa_produzione
																LEFT JOIN risorse ON risorsa_produzione.rp_IdRisorsa = risorse.ris_IdRIsorsa AND risorse.ris_IdProduzione = risorsa_produzione.rp_IdProduzione
																WHERE risorsa_produzione.rp_IdProduzione = :IdProduzione AND (ris_Attrezzaggio_Man = 1 OR ris_Attrezzaggio_Scada = 1)", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
			$sthRecuperaStatoAttrezzaggio->execute(array(":IdProduzione" => $riga["op_IdProduzione"]));
			$rigaRecuperaStatoAttrezzaggio = $sthRecuperaStatoAttrezzaggio->fetch(PDO::FETCH_ASSOC);


			// Verifico presenza di stati di 'avaria' sulle risorse coinvolte nell'ordine in oggetto
			$sthRecuperaStatoAvaria = $conn_mes->prepare("SELECT COUNT(*) AS EventiAvaria
																FROM risorsa_produzione
																LEFT JOIN risorse ON risorsa_produzione.rp_IdRisorsa = risorse.ris_IdRIsorsa AND risorse.ris_IdProduzione = risorsa_produzione.rp_IdProduzione
																WHERE risorsa_produzione.rp_IdProduzione = :IdProduzione AND (ris_Avaria_Man = 1 OR ris_Avaria_Scada = 1)", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
			$sthRecuperaStatoAvaria->execute(array(":IdProduzione" => $riga["op_IdProduzione"]));
			$rigaRecuperaStatoAvaria = $sthRecuperaStatoAvaria->fetch(PDO::FETCH_ASSOC);

			// Imposto opportunamente la variabile di stato
			$statoLinea = "";
			if ($rigaRecuperaStatoAttrezzaggio['EventiAttrezzaggio'] != 0) {
				$statoLinea = "at";
			} else if ($rigaRecuperaStatoAvaria['EventiAvaria'] != 0) {
				$statoLinea = "av";
			} else {
				$statoLinea = "ok";
			}


			// CALCOLO DATA DI FINE TEORICA ISTANTANEA
			$dataOdierna = date('Y-m-d');
			$oraOdierna = date('H:i');
			$dataOraInizioDt = new DateTime($riga["rlp_DataInizio"] . " " . $riga["rlp_OraInizio"]);
			$dataOraFineDt = new DateTime($dataOdierna . " " . $oraOdierna);

			$tempoTrascorsoDt = $dataOraFineDt->diff($dataOraInizioDt);
			$tempoTrascorso_sec =  intval(($tempoTrascorsoDt->days * 3600 * 24) + ($tempoTrascorsoDt->h * 3600) + ($tempoTrascorsoDt->i * 60) + $tempoTrascorsoDt->s);
			$tempoTrascorso_min =  intval(($tempoTrascorsoDt->days * 3600 * 24) + ($tempoTrascorsoDt->h * 3600) + ($tempoTrascorsoDt->i * 60));

			$numeroPezziDaProdurre = $riga['op_QtaDaProdurre'];
			$tempoNecessario = intval(($numeroPezziDaProdurre * $tempoTrascorso_min) / ($numPezziProdotti == 0 ? 1 : $numPezziProdotti));
			$interval = new DateInterval('PT' . $tempoNecessario . 'S');

			$dataOraFineTDt = new DateTime($riga['rlp_DataInizio'] . " " . $riga['rlp_OraInizio']);
			$dataOraFineTDt->add($interval);
			$stringaDataInizio = $dataOraInizioDt->format('d/m/Y - H:i');
			$stringaDataFineTeorica = $dataOraFineTDt->format('d/m/Y - H:i');

			// converto tempo teorico per la realizzazione del pezzo da (pezzi/h) a (pezzi/sec)
			// estraggo |T. TEORICO PZ DELLA LINEA|
			$tempoTeoricoPezzoLinea_pzh = isset($riga["vel_VelocitaTeoricaLinea"]) ? floatval($riga["vel_VelocitaTeoricaLinea"]) : 1;
			$tempoTeoricoPezzoProduzione_min = floatval($tempoTeoricoPezzoLinea_pzh / 60);

			// calcolo |OEE DI LINEA - TREND| (N.B: formula modificata senza utilizzo delle 3 componenti: n° pezzi conformi / n° pezzi teorici nel tempo totale)
			$numeroPezziTeorici = intval($tempoTrascorso_min * $tempoTeoricoPezzoProduzione_min);
			$OEELineaV2_Trend = round(floatval(($numPezziConformi / ($numeroPezziTeorici != 0 ? $numeroPezziTeorici : 1)) * 100), 2);

			// calcolo |VELOCITA' DI LINEA - TREND|
			$uptimeProduzione_ore = floatval($tempoTrascorso_min / 60);
			$velocitaLinea_Trend = round(intval($numPezziProdotti / ($uptimeProduzione_ore != 0 ? $uptimeProduzione_ore : 1)), 2);

			if ($velocitaLinea_Trend > $riga["vel_VelocitaTeoricaLinea"]) {
				$indicatoreRendimento = "<img class='indicatori' src='../images/UpArrow_3.png' style='float: right; height:40px; width:40px'>";
			} else {
				$indicatoreRendimento = "<img class='indicatori' src='../images/DownArrow_3.png' style='float: right; height:40px; width:40px'>";
			}


			//Preparo i dati da visualizzare
			$output[] = array(

				"IdProduzione" => ($riga["op_Riferimento"] != "" ? $riga["op_IdProduzione"] . " (" . $riga["op_Riferimento"] . ")" : $riga["op_IdProduzione"]),
				"DescrizioneLinea" => "<b>" . $riga["lp_Descrizione"] . "</b>",
				"Prodotto" => $riga["prd_Descrizione"],
				"Lotto" => $riga["op_Lotto"],
				"QtaRichiesta" => $riga["op_QtaDaProdurre"] . "&nbsp;&nbsp;" . $riga["um_Sigla"],
				"QtaProdotta" => intval($numPezziProdotti) . "&nbsp;&nbsp;" . $riga["um_Sigla"],
				"QtaConforme" => intval($numPezziConformi) . "&nbsp;&nbsp;" . $riga["um_Sigla"],
				"QtaScarti" => intval($numPezziScartati) . "&nbsp;&nbsp;" . $riga["um_Sigla"],
				"DataOraInizio" => $stringaDataInizio,
				"DataOraFine" => $stringaDataFineTeorica,
				"ValoreOee" => (float)($OEELineaV2_Trend > 100 ? 100 : $OEELineaV2_Trend),
				"Oee" => '<canvas id="grOEE_' . trim($riga["op_IdProduzione"]) . '" width="100" height="100" style="text-align:center;"></canvas>',
				"azioni" => '<button type="button" class="btn btn-primary espandi-dettaglio-ordine py-1" data-id-ordine-produzione="' . $riga["op_IdProduzione"] . '" title="Vedi dettaglio"><span class="mdi mdi-eye mdi-18px"></span></button>',
				"StatoLinea" => $statoLinea,
				"IdProduzioneOEE" => $riga["op_IdProduzione"],
				"VelocitaLinea" => "<div style='float:left;'><b>Teorica</b>:&nbsp;" . $riga["vel_VelocitaTeoricaLinea"] . " [Pz/h]<br><br><b>Reale:</b> &nbsp;" . $velocitaLinea_Trend . " [Pz/h]</div>" . $indicatoreRendimento,
			);
		}

		die(json_encode($output));
	} else {
		die("NO_ROWS");
	}
}


// VISUALIZZAZIONE COMMESSE ATTIVI MA NON AVVIATI (STATO = 2 = 'ATTIVO')
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "mostra-ordini-attivi") {
	// estraggo la lista
	$sth = $conn_mes->prepare("SELECT ODP.op_IdProduzione, ODP.op_Riferimento, ODP.op_ProgressivoParziale, ODP.op_DataOrdine, ODP.op_OraOrdine, ODP.op_DataProduzione, ODP.op_OraProduzione, ODP.op_DataFineTeorica, ODP.op_OraFineTeorica, ODP.op_Lotto, ODP.op_QtaRichiesta, ODP.op_QtaDaProdurre, P.prd_Descrizione, SO.so_Descrizione, LP.lp_Descrizione, UM.um_Sigla
									FROM ordini_produzione AS ODP
									LEFT JOIN stati_ordine AS SO ON ODP.op_Stato = SO.so_IdStatoOrdine
									LEFT JOIN prodotti AS P ON ODP.op_Prodotto = P.prd_IdProdotto
									LEFT JOIN linee_produzione AS LP ON ODP.op_LineaProduzione = LP.lp_IdLinea
									LEFT JOIN unita_misura AS UM ON ODP.op_Udm = UM.um_IdRiga
									WHERE ODP.op_Stato = 2 OR ODP.op_Stato = 3", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
	$sth->execute();
	if ($sth->rowCount() > 0) {
		$righe = $sth->fetchAll(PDO::FETCH_ASSOC);


		$output = array();

		foreach ($righe as $riga) {

			if (isset($riga["op_DataOrdine"])) {
				$dInizio = new DateTime($riga["op_DataOrdine"] . " " . $riga["op_OraOrdine"]);
				$stringaDataOrdine = $dInizio->format('d/m/Y - H:i');
			} else {
				$stringaDataOrdine = "";
			}

			if (isset($riga["op_DataProduzione"])) {
				$dInizio = new DateTime($riga["op_DataProduzione"] . " " . $riga["op_OraProduzione"]);
				$stringaDataProgrammata = $dInizio->format('d/m/Y - H:i');
			} else {
				$stringaDataProgrammata = "";
			}

			if (isset($riga["op_DataFineTeorica"])) {
				$dInizio = new DateTime($riga["op_DataFineTeorica"] . " " . $riga["op_OraFineTeorica"]);
				$stringaDataFinePrevista = $dInizio->format('d/m/Y - H:i');
			} else {
				$stringaDataFinePrevista = "";
			}


			//Preparo i dati da visualizzare
			$output[] = array(

				"IdProduzione" => ($riga["op_Riferimento"] != "" ? $riga["op_IdProduzione"] . " (" . $riga["op_Riferimento"] . ")" : $riga["op_IdProduzione"]),
				"Prodotto" => $riga["prd_Descrizione"],
				"Lotto" => $riga["op_Lotto"],
				"DescrizioneLinea" => $riga["lp_Descrizione"],
				"QtaRichiesta" => $riga["op_QtaDaProdurre"] . "&nbsp;&nbsp;" . $riga["um_Sigla"],
				"DataOraProgrammazione" => $stringaDataProgrammata,
				"DataOraFinePrevista" => $stringaDataFinePrevista,
				"azioni" => '<button type="button" class="btn btn-primary btn-lg espandi-dettaglio-ordine py-1" data-id-ordine-produzione="' . $riga["op_IdProduzione"] . '" title="Vedi dettaglio" disabled><span class="mdi mdi-eye mdi-18px"></span></button>'
			);
		}

		die(json_encode($output));
	} else {
		die("NO_ROWS");
	}
}



// VISUALIZZAZIONE COMMESSE CONCLUSI (STATO = 4 = 'CHIUSO')
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "mostra-ordini-chiusi") {
	// estraggo la lista
	$sth = $conn_mes->prepare("SELECT ODP.op_IdProduzione, ODP.op_Riferimento, ODP.op_ProgressivoParziale, ODP.op_DataOrdine, ODP.op_OraOrdine, ODP.op_DataProduzione, ODP.op_OraProduzione, ODP.op_Lotto, ODP.op_QtaRichiesta, P.prd_Descrizione, SO.so_Descrizione, RLP.rlp_DataInizio, RLP.rlp_DataFine, RLP.rlp_OraInizio, RLP.rlp_OraFine, RLP.rlp_QtaProdotta, RLP.rlp_OEELinea, LP.lp_Descrizione, UM.um_Sigla
									FROM ordini_produzione  AS ODP
									LEFT JOIN stati_ordine AS SO ON ODP.op_Stato = SO.so_IdStatoOrdine
									LEFT JOIN prodotti AS P ON ODP.op_Prodotto = P.prd_IdProdotto
									LEFT JOIN rientro_linea_produzione AS RLP ON ODP.op_IdProduzione = RLP.rlp_IdProduzione
									LEFT JOIN linee_produzione AS LP ON ODP.op_LineaProduzione = LP.lp_IdLinea
									LEFT JOIN unita_misura AS UM ON ODP.op_Udm = UM.um_IdRiga
									WHERE ODP.op_Stato = 5
									ORDER BY RLP.rlp_OraInizio DESC", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
	$sth->execute();
	if ($sth->rowCount() > 0) {
		$righe = $sth->fetchAll(PDO::FETCH_ASSOC);


		$output = array();

		foreach ($righe as $riga) {
			if (isset($riga["rlp_DataInizio"])) {
				$dInizio = new DateTime($riga["rlp_DataInizio"] . " " . $riga["rlp_OraInizio"]);
				$stringaDataInizio = $dInizio->format('d/m/Y - H:i');
			} else {
				$stringaDataInizio = "";
			}

			if (isset($riga["rlp_DataFine"])) {
				$dInizio = new DateTime($riga["rlp_DataFine"] . " " . $riga["rlp_OraFine"]);
				$stringaDataFine = $dInizio->format('d/m/Y - H:i');
			} else {
				$stringaDataFine = "IN CORSO...";
			}

			// recupero il valore del 'numero pezzi prodotti' e del 'numero pezzi scartati', per l'intera linea (somma dei pezzi prodotti e scartati dalle varie risorse coinvolte)
			$sthRecuperaConteggioPezziLinea = $conn_mes->prepare("SELECT MIN(rp_QtaProdotta) AS NumeroPezziProdotti, MIN(rp_QtaScarti) AS NumeroPezziScartati
																FROM  risorsa_produzione
																WHERE risorsa_produzione.rp_IdProduzione = :IdProduzione", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
			$sthRecuperaConteggioPezziLinea->execute(array(":IdProduzione" => $riga["op_IdProduzione"]));
			$rigaConteggioPezziLinea = $sthRecuperaConteggioPezziLinea->fetch(PDO::FETCH_ASSOC);


			//Preparo i dati da visualizzare
			$output[] = array(

				"IdProduzione" => ($riga["op_Riferimento"] != "" ? $riga["op_IdProduzione"] . " (" . $riga["op_Riferimento"] . ")" : $riga["op_IdProduzione"]),
				"Prodotto" => $riga["prd_Descrizione"],
				"DescrizioneLinea" => $riga["lp_Descrizione"],
				"QtaRichiesta" => $riga["op_QtaRichiesta"] . "&nbsp;&nbsp;" . $riga["um_Sigla"],
				"QtaProdotta" => intval($riga["rlp_QtaProdotta"]) == 0 ? $rigaConteggioPezziLinea["NumeroPezziProdotti"] . "&nbsp;&nbsp;" . $riga["um_Sigla"] : $riga["rlp_QtaProdotta"] . "&nbsp;&nbsp;" . $riga["um_Sigla"],
				"DataOraInizio" => $stringaDataInizio,
				"DataOraFine" => $stringaDataFine,
				//"StatoOrdine" => $riga["so_Descrizione"],
				"Oee" => isset($riga["rlp_OEELinea"]) ? $riga["rlp_OEELinea"] : 0,
				"azioni" => '<button type="button" class="btn btn-primary btn-lg py-1 espandi-dettaglio-ordine" data-id-ordine-produzione="' . $riga["op_IdProduzione"] . '"  data-progressivo-parziale="' . $riga["op_ProgressivoParziale"] . '" title="Vedi dettaglio"><span class="mdi mdi-eye mdi-18px"></span></button>'
			);
		}

		die(json_encode($output));
	} else {
		die("NO_ROWS");
	}
}









// *** MANUTENZIONI STRAORDINARIE ***

// MANUTENZIONI STRAORDINARIE: VISUALIZZAZIONE
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "manutenzione-mostra-stato-eventi") {

	if ($_REQUEST["statoEvento"] == 'aperti') {
		// seleziono i dati della tabella di lavoro 'risorse_coinvolte_work'
		$sth = $conn_mes->prepare("SELECT AC.*, RIS.ris_Descrizione, RIS.ris_LineaProduzione , C.cas_Tipo, LP.lp_Descrizione, IM.*, gc.gc_Descrizione
										FROM attivita_casi AS AC
										LEFT JOIN interventi_manutenzione AS IM ON AC.ac_IdRisorsa = IM.im_IdRisorsa AND AC.ac_IdEvento = IM.im_IdEvento AND AC.ac_DataInizio = IM.im_DataEvento AND AC.ac_OraInizio = IM.im_OraEvento
										LEFT JOIN casi AS C ON AC.ac_IdEvento = C.cas_IdEvento AND AC.ac_IdRisorsa = C.cas_IdRisorsa
										LEFT JOIN risorse AS RIS ON AC.ac_IdRisorsa = RIS.ris_IdRisorsa
										LEFT JOIN linee_produzione AS LP ON RIS.ris_LineaProduzione = LP.lp_IdLinea
										LEFT JOIN gruppi_casi AS gc ON C.cas_Gruppo = gc.gc_IdRiga
										WHERE ris_LineaProduzione LIKE :IdLineaProduzione AND cas_Tipo LIKE :TipoCaso AND cas_Tipo != 'AT' AND (IM.im_Risolto = 0 OR IM.im_Risolto IS NULL)", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
		$sth->execute(array(":IdLineaProduzione" => $_REQUEST["idLineaProduzione"], ":TipoCaso" => $_REQUEST["tipoEvento"]));
	} else if ($_REQUEST["statoEvento"] == 'risolti') {
		// seleziono i dati della tabella di lavoro 'risorse_coinvolte_work'
		$sth = $conn_mes->prepare("SELECT AC.*, RIS.ris_Descrizione, RIS.ris_LineaProduzione , C.cas_Tipo, LP.lp_Descrizione, IM.*, gc.gc_Descrizione
										FROM attivita_casi AS AC
										LEFT JOIN interventi_manutenzione AS IM ON AC.ac_IdRisorsa = IM.im_IdRisorsa AND AC.ac_IdEvento = IM.im_IdEvento AND AC.ac_DataInizio = IM.im_DataEvento AND AC.ac_OraInizio = IM.im_OraEvento
										LEFT JOIN casi AS C ON AC.ac_IdEvento = C.cas_IdEvento AND AC.ac_IdRisorsa = C.cas_IdRisorsa
										LEFT JOIN risorse AS RIS ON AC.ac_IdRisorsa = RIS.ris_IdRisorsa
										LEFT JOIN linee_produzione AS LP ON RIS.ris_LineaProduzione = LP.lp_IdLinea
										LEFT JOIN gruppi_casi AS gc ON C.cas_Gruppo = gc.gc_IdRiga
										WHERE ris_LineaProduzione LIKE :IdLineaProduzione AND cas_Tipo LIKE :TipoCaso AND cas_Tipo != 'AT' AND IM.im_Risolto = 1", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
		$sth->execute(array(":IdLineaProduzione" => $_REQUEST["idLineaProduzione"], ":TipoCaso" => $_REQUEST["tipoEvento"]));
	} else if ($_REQUEST["statoEvento"] == '%') {
		// seleziono i dati della tabella di lavoro 'risorse_coinvolte_work'
		$sth = $conn_mes->prepare("SELECT AC.*, RIS.ris_Descrizione, RIS.ris_LineaProduzione , C.cas_Tipo, LP.lp_Descrizione, IM.*, gc.gc_Descrizione
										FROM attivita_casi AS AC
										LEFT JOIN interventi_manutenzione AS IM ON AC.ac_IdRisorsa = IM.im_IdRisorsa AND AC.ac_IdEvento = IM.im_IdEvento AND AC.ac_DataInizio = IM.im_DataEvento AND AC.ac_OraInizio = IM.im_OraEvento
										LEFT JOIN casi AS C ON AC.ac_IdEvento = C.cas_IdEvento AND AC.ac_IdRisorsa = C.cas_IdRisorsa
										LEFT JOIN risorse AS RIS ON AC.ac_IdRisorsa = RIS.ris_IdRisorsa
										LEFT JOIN linee_produzione AS LP ON RIS.ris_LineaProduzione = LP.lp_IdLinea
										LEFT JOIN gruppi_casi AS gc ON C.cas_Gruppo = gc.gc_IdRiga
										WHERE ris_LineaProduzione LIKE :IdLineaProduzione AND cas_Tipo LIKE :TipoCaso AND cas_Tipo != 'AT' AND (IM.im_Risolto = 0 OR IM.im_Risolto = 1 OR IM.im_Risolto IS NULL)", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
		$sth->execute(array(":IdLineaProduzione" => $_REQUEST["idLineaProduzione"], ":TipoCaso" => $_REQUEST["tipoEvento"]));
	}


	$output = array();

	if ($sth->rowCount() > 0) {
		$righe = $sth->fetchAll(PDO::FETCH_ASSOC);

		foreach ($righe as $riga) {
			$casellaRiconosciuto = "";
			$casellaRisolto = "";

			// Formatto la stringa che conterrà la data di 'inizio evento'
			if (isset($riga["ac_DataInizio"])) {
				$dInizio = new DateTime($riga["ac_DataInizio"] . " " . $riga["ac_OraInizio"]);
				$stringaDataInizio = $dInizio->format('d/m/Y - H:i:s');
			} else {
				$stringaDataInizio = "";
			}

			// Formatto la stringa che conterrà la data di 'inizio evento'
			if (isset($riga["ac_DataFine"])) {
				$dFine = new DateTime($riga["ac_DataFine"] . " " . $riga["ac_OraFine"]);
				$stringaDataFine = $dFine->format('d/m/Y - H:i:s');
			} else {
				$stringaDataFine = "";
			}

			// Formatto la stringhe che conterrà il pulsante di 'riconosci evento'
			if (((isset($riga["im_Riconosciuto"])) && ($riga["im_Riconosciuto"] == false)) || (!isset($riga["im_Riconosciuto"]))) {
				$casellaRiconosciuto = '<button class="btn btn-secondary riconosci-man-str" type="button" data-id_riga="' . $riga["ac_IdRiga"] . '" title="Riconosci evento">
											<span class="mdi mdi-check mdi-18px"></span>
											</button>';
			} else if ((isset($riga["im_Riconosciuto"])) && ($riga["im_Riconosciuto"] == true)) {
				$casellaRiconosciuto = '<span class="mdi mdi-check-all mdi-18px"></span>';
			}


			// Formatto la stringhe che conterrà il pulsante di 'risolvi evento'
			if (((isset($riga["im_Risolto"])) && ($riga["im_Risolto"] == false)) || (!isset($riga["im_Risolto"]))) {
				$casellaRisolto = '<button class="btn btn-secondary risolvi-man-str" type="button" data-id_riga="' . $riga["ac_IdRiga"] . '" title="Risolvi evento">
											<span class="mdi mdi-clipboard-check mdi-18px"></span>
											</button>';
			} else if ((isset($riga["im_Risolto"])) && ($riga["im_Risolto"] == true)) {
				$casellaRisolto = '<button class="btn btn-success vedi-rapporto-man-str" type="button" data-id_riga="' . $riga["ac_IdRiga"] . '" title="Apri rapporto di lavoro">
											<span class="mdi mdi-eye mdi-18px"></span>
											</button>';
			}

			// Formatto la stringa che conterrà il tipo di caso in oggetto
			if ($riga["cas_Tipo"] == "KO") {
				$tipoEvento = "AVARIA";
			} else if ($riga["cas_Tipo"] == "KK") {
				$tipoEvento = "FERMO";
			} else if ($riga["cas_Tipo"] == "OK") {
				$tipoEvento = "NON BLOC.";
			} else if ($riga["cas_Tipo"] == "AT") {
				$tipoEvento = "ATTR.";
			}

			//Preparo i dati da visualizzare
			$output[] = array(

				"Linea" => $riga["lp_Descrizione"],
				"Risorsa" => $riga["ris_Descrizione"],
				"DataInizio" => $stringaDataInizio,
				"DataFine" => $stringaDataFine,
				"Evento" => $riga["ac_DescrizioneEvento"],
				"Tipo" => $tipoEvento,
				"Gruppo" => $riga["gc_Descrizione"],
				"Riconosciuto" => $riga["im_Riconosciuto"],
				"Risolto" =>  $riga["im_Risolto"],
				"PulsanteRiconosciuto" => $casellaRiconosciuto,
				"PulsanteRisolto" =>  $casellaRisolto
			);
		}

		die(json_encode($output));
	} else {
		die("NO_ROWS");
	}
}



// MANUTENZIONI STRAORDINARIE: RICONOSCIMENTO EVENTO SELEZIONATO
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "riconosci-man-str" && !empty($_REQUEST["idRiga"])) {

	// ricavo data e ora attuali
	$dataOdierna = date('Y-m-d');
	$oraOdierna = date('H:i:s');

	// Recupero da tabella 'attivita_casi' i dati relativi all'evento in oggetto
	$sthInformazioniCaso = $conn_mes->prepare("SELECT AC.ac_IdRisorsa, AC.ac_IdEvento, AC.ac_IdProduzione, AC.ac_DescrizioneEvento, AC.ac_DataInizio, AC.ac_OraInizio
									FROM attivita_casi AS AC
									WHERE AC.ac_IdRiga = :IdRiga", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
	$sthInformazioniCaso->execute(array(":IdRiga" => $_REQUEST["idRiga"]));
	$rigaInformazioniCaso = $sthInformazioniCaso->fetch(PDO::FETCH_ASSOC);



	// Eseguo INSERT del rapporto di lavoro in tabella 'interventi_manutenzione'
	$sqlRegistraRiconoscimento = "INSERT INTO interventi_manutenzione(im_IdRisorsa,im_IdProduzione,im_IdEvento,im_DescrizioneEvento,im_DataEvento,im_OraEvento,im_DataRiconoscimento,im_OraRiconoscimento,im_Riconosciuto,im_CodicePersonaleRic,im_PersonaleRic) VALUES(:IdRisorsa,:IdProduzione,:IdEvento,:DescrizioneEvento,:DataEvento,:OraEvento,:DataRiconoscimento,:OraRiconoscimento,:FlagRiconosciuto,:CodiceRiconoscimento,:PersonaleRiconoscimento)";

	$sthRegistraRiconoscimento = $conn_mes->prepare($sqlRegistraRiconoscimento);
	$sthRegistraRiconoscimento->execute(array(
		":IdRisorsa" => $rigaInformazioniCaso['ac_IdRisorsa'],
		":IdProduzione" => $rigaInformazioniCaso['ac_IdProduzione'],
		":IdEvento" => $rigaInformazioniCaso['ac_IdEvento'],
		":DescrizioneEvento" => $rigaInformazioniCaso['ac_DescrizioneEvento'],
		":DataEvento" => $rigaInformazioniCaso['ac_DataInizio'],
		":OraEvento" => $rigaInformazioniCaso['ac_OraInizio'],
		":DataRiconoscimento" => $dataOdierna,
		":OraRiconoscimento" => $oraOdierna,
		":FlagRiconosciuto" => true,
		":CodiceRiconoscimento" => $_SESSION["utente"]['usr_IdUtente'],
		":PersonaleRiconoscimento" => $_SESSION["utente"]['usr_Nome'] . " " . $_SESSION["utente"]['usr_Cognome']
	));


	// Verifica esito
	if ($sthInformazioniCaso && $sthRegistraRiconoscimento) {
		die("OK");
	} else {
		die("ERRORE");
	}
}


// MANUTENZIONI STRAORDINARIE: RECUPERO INFORMAZIONI EVENTO
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "recupera-man-str" && !empty($_REQUEST["idRiga"])) {

	// Recupero informazioni evento selezionato
	$sth = $conn_mes->prepare("SELECT AC.*, RIS.ris_Descrizione, RIS.ris_LineaProduzione , C.cas_Tipo, LP.lp_Descrizione, IM.*
									FROM attivita_casi AS AC
									LEFT JOIN interventi_manutenzione AS IM ON AC.ac_IdRisorsa = IM.im_IdRisorsa AND AC.ac_IdEvento = IM.im_IdEvento AND AC.ac_DataInizio = IM.im_DataEvento AND AC.ac_OraInizio = IM.im_OraEvento
									LEFT JOIN casi AS C ON AC.ac_IdEvento = C.cas_IdEvento AND AC.ac_IdRisorsa = C.cas_IdRisorsa
									LEFT JOIN risorse AS RIS ON AC.ac_IdRisorsa = RIS.ris_IdRisorsa
									LEFT JOIN linee_produzione AS LP ON RIS.ris_LineaProduzione = LP.lp_IdLinea
									WHERE ac_IdRiga = :IdRiga", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
	$sth->execute(array(":IdRiga" => $_REQUEST["idRiga"]));
	$riga = $sth->fetch(PDO::FETCH_ASSOC);

	die(json_encode($riga));
}


// MANUTENZIONI STRAORDINARIE: SALVATAGGIO DATI DA POPUP 'RISOLVI'
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "salva-rapporto-man-str" && !empty($_REQUEST["data"])) {

	// Recupero i parametri dal POST
	$parametri = array();
	parse_str($_REQUEST["data"], $parametri);

	// Ricavo data e ora attuali
	$dataOdierna = date('Y-m-d');
	$oraOdierna = date('H:i:s');

	$dataInizioPrevista = substr($parametri['manStrRis_DataInizioPrevista'], 0, 10);
	$oraInizioPrevista = substr($parametri['manStrRis_DataInizioPrevista'], 11, 15);

	$dataInterventoInizio = substr($parametri['manStrRis_DataInizioIntervento'], 0, 10);
	$oraInterventoInizio = substr($parametri['manStrRis_DataInizioIntervento'], 11, 15);

	$dataInterventoFine = substr($parametri['manStrRis_DataFineIntervento'], 0, 10);
	$oraInterventoFine = substr($parametri['manStrRis_DataFineIntervento'], 11, 15);


	$id_modifica = $parametri["manStrRis_NumeroRapporto"];
	$statoRiconosciuto = $parametri["manStrRis_Riconosciuto"];


	// Se evento è già stato riconosciuto,
	if ((isset($statoRiconosciuto)) && ($statoRiconosciuto == true)) {


		// Eseguo UPDATE su tabella 'interventi_manutenzione'
		$sqlRegistraRapportoLavoro = "UPDATE interventi_manutenzione SET
						im_TipoRapporto = :TipoRapporto,
						im_DataInizioPrevista = :DataInizioPrevista,
						im_OraInizioPrevista = :OraInizioPrevista,
						im_DataInterventoInizio = :DataInterventoInizio,
						im_OraInterventoInizio = :OraInterventoInizio,
						im_DataInterventoFine = :DataInterventoFine,
						im_OraInterventoFine = :OraInterventoFine,
						im_Risolto = :FlagRisolto,
						im_CodicePersonaleRis = :CodiceRisoluzione,
						im_PersonaleRis = :PersonaleRisoluzione,
						im_DescrizioneIntervento = :DescrizioneIntervento
						WHERE im_NumeroRapporto = :IdRiga";

		$sthRegistraRapportoLavoro = $conn_mes->prepare($sqlRegistraRapportoLavoro);
		$sthRegistraRapportoLavoro->execute(array(
			":TipoRapporto" => "STR",
			":DataInizioPrevista" => $dataInizioPrevista,
			":OraInizioPrevista" => $oraInizioPrevista,
			":DataInterventoInizio" => $dataInterventoInizio,
			":OraInterventoInizio" => $oraInterventoInizio,
			":DataInterventoFine" => $dataInterventoFine,
			":OraInterventoFine" => $oraInterventoFine,
			":FlagRisolto" => true,
			":CodiceRisoluzione" => $_SESSION["utente"]['usr_IdUtente'],
			":PersonaleRisoluzione" => $_SESSION["utente"]['usr_Nome'] . " " . $_SESSION["utente"]['usr_Cognome'],
			":DescrizioneIntervento" => $parametri["manStrRis_NoteIntervento"],
			":IdRiga" => $id_modifica
		));
	}

	// Se evento non è stato riconosciuto,
	else {

		// Recupero informazioni sull'evento in oggetto
		$sthInformazioniCaso = $conn_mes->prepare("SELECT AC.ac_IdRisorsa, AC.ac_IdEvento, AC.ac_IdProduzione, AC.ac_DescrizioneEvento, AC.ac_DataInizio, AC.ac_OraInizio
										FROM attivita_casi AS AC
										WHERE AC.ac_IdRiga = :IdRiga", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
		$sthInformazioniCaso->execute(array(":IdRiga" => $parametri["manStrRis_IdRiga"]));
		$rigaInformazioniCaso = $sthInformazioniCaso->fetch(PDO::FETCH_ASSOC);


		// Eseguo INSERT in tabella 'interventi_manutenzione' (RICONOSCIMENTO IMPLICITO)
		$sqlRegistraRapportoLavoro = "INSERT INTO interventi_manutenzione(im_TipoRapporto,im_IdRisorsa,im_IdProduzione,im_IdEvento,im_DescrizioneEvento,im_DataEvento,im_OraEvento,im_DataRiconoscimento,im_OraRiconoscimento,im_DataInizioPrevista,im_OraInizioPrevista,im_DataInterventoInizio,im_OraInterventoInizio,im_DataInterventoFine,im_OraInterventoFine,im_Riconosciuto,im_Risolto,im_CodicePersonaleRic,im_PersonaleRic,im_CodicePersonaleRis,im_PersonaleRis,im_DescrizioneIntervento) VALUES(:TipoRapporto,:IdRisorsa,:IdProduzione,:IdEvento,:DescrizioneEvento,:DataEvento,:OraEvento,:DataRiconoscimento,:OraRiconoscimento,:DataInizioPrevista,:OraFinePrevista,:DataInterventoInizio,:OraInterventoInizio,:DataInterventoFine,:OraInterventoFine,:FlagRiconosciuto,:FlagRisolto,:CodiceRiconoscimento,:PersonaleRiconoscimento,:CodiceRisoluzione,:PersonaleRisoluzione,:DescrizioneIntervento)";

		$sthRegistraRapportoLavoro = $conn_mes->prepare($sqlRegistraRapportoLavoro);
		$sthRegistraRapportoLavoro->execute(array(
			":IdRisorsa" => $rigaInformazioniCaso['ac_IdRisorsa'],
			":IdProduzione" => $rigaInformazioniCaso['ac_IdProduzione'],
			":TipoRapporto" => "STR",
			":IdEvento" => $rigaInformazioniCaso['ac_IdEvento'],
			":DescrizioneEvento" => $rigaInformazioniCaso['ac_DescrizioneEvento'],
			":DataEvento" => $rigaInformazioniCaso['ac_DataInizio'],
			":OraEvento" => $rigaInformazioniCaso['ac_OraInizio'],
			":DataRiconoscimento" => $dataOdierna,
			":OraRiconoscimento" => $oraOdierna,
			":DataInizioPrevista" => $dataInizioPrevista,
			":OraFinePrevista" => $oraInizioPrevista,
			":DataInterventoInizio" => $dataInterventoInizio,
			":OraInterventoInizio" => $oraInterventoInizio,
			":DataInterventoFine" => $dataInterventoFine,
			":OraInterventoFine" => $oraInterventoFine,
			":FlagRiconosciuto" => true,
			":FlagRisolto" => true,
			":CodiceRiconoscimento" => $_SESSION["utente"]['usr_IdUtente'],
			":PersonaleRiconoscimento" => $_SESSION["utente"]['usr_Nome'] . " " . $_SESSION["utente"]['usr_Cognome'],
			":CodiceRisoluzione" => $_SESSION["utente"]['usr_IdUtente'],
			":PersonaleRisoluzione" => $_SESSION["utente"]['usr_Nome'] . " " . $_SESSION["utente"]['usr_Cognome'],
			":DescrizioneIntervento" => $parametri["manStrRis_NoteIntervento"]
		));
	}


	if ($sthRegistraRapportoLavoro) {
		die("OK");
	} else {
		die("ERRORE");
	}
}









// *** MANUTENZIONI ORDINARIE ***

// MANUTENZIONI ORDINARIE: VISUALIZZAZIONE
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "manutenzione-ordinaria-mostra") {

	if ($_REQUEST["statoEvento"] == 'aperti') {
		// seleziono i dati della tabella di lavoro 'risorse_coinvolte_work'
		$sth = $conn_mes->prepare("SELECT STRING_AGG(ris_Descrizione , '/') AS ElencoRisorse, STRING_AGG(ris_IdRisorsa , ',') AS ElencoIdRisorse, AC.ac_ManOrd, AC.ac_ManOrd_Progressivo, AC.ac_ManOrd_DataInizioPrevista, AC.ac_ManOrd_OraInizioPrevista, AC.ac_ManOrd_DataFinePrevista, AC.ac_ManOrd_OraFinePrevista, AC.ac_ManOrd_Descrizione, AC.ac_ManOrd_BloccoLinea, IM.im_Risolto, IM.im_Riconosciuto
									FROM attivita_casi AS AC
									LEFT JOIN interventi_manutenzione AS IM ON AC.ac_ManOrd_Progressivo = IM.im_ProgressivoManOrdinaria AND AC.ac_ManOrd_DataInizioPrevista = IM.im_DataInizioPrevista AND AC.ac_ManOrd_OraInizioPrevista = IM.im_OraInizioPrevista AND AC.ac_ManOrd_DataFinePrevista = IM.im_DataFinePrevista AND AC.ac_ManOrd_OraFinePrevista = IM.im_OraFinePrevista
									LEFT JOIN casi AS C ON AC.ac_IdEvento = C.cas_IdEvento AND AC.ac_IdRisorsa = C.cas_IdRisorsa
									LEFT JOIN risorse AS RIS ON AC.ac_IdRisorsa = RIS.ris_IdRisorsa
									WHERE AC.ac_ManOrd = 1  AND (IM.im_Risolto = 0 OR IM.im_Risolto IS NULL)
									GROUP BY ac_ManOrd_DataInizioPrevista, ac_ManOrd_OraInizioPrevista, AC.ac_ManOrd, AC.ac_ManOrd_Progressivo, AC.ac_ManOrd_DataInizioPrevista, AC.ac_ManOrd_OraInizioPrevista, AC.ac_ManOrd_DataFinePrevista, AC.ac_ManOrd_OraFinePrevista, AC.ac_ManOrd_Descrizione, AC.ac_ManOrd_BloccoLinea, IM.im_Risolto, IM.im_Riconosciuto
									HAVING STRING_AGG(ris_IdRisorsa , ',') LIKE :IdRisorsa", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
		$sth->execute(array(":IdRisorsa" => "%" . $_REQUEST["idRisorsa"] . "%"));
	} else if ($_REQUEST["statoEvento"] == 'risolti') {
		// seleziono i dati della tabella di lavoro 'risorse_coinvolte_work'
		$sth = $conn_mes->prepare("SELECT STRING_AGG(ris_Descrizione , '/') AS ElencoRisorse, STRING_AGG(ris_IdRisorsa , ',') AS ElencoIdRisorse, AC.ac_ManOrd, AC.ac_ManOrd_Progressivo, AC.ac_ManOrd_DataInizioPrevista, AC.ac_ManOrd_OraInizioPrevista, AC.ac_ManOrd_DataFinePrevista, AC.ac_ManOrd_OraFinePrevista, AC.ac_ManOrd_Descrizione, AC.ac_ManOrd_BloccoLinea, IM.im_Risolto, IM.im_Riconosciuto
									FROM attivita_casi AS AC
									LEFT JOIN interventi_manutenzione AS IM ON AC.ac_ManOrd_Progressivo = IM.im_ProgressivoManOrdinaria AND AC.ac_ManOrd_DataInizioPrevista = IM.im_DataInizioPrevista AND AC.ac_ManOrd_OraInizioPrevista = IM.im_OraInizioPrevista AND AC.ac_ManOrd_DataFinePrevista = IM.im_DataFinePrevista AND AC.ac_ManOrd_OraFinePrevista = IM.im_OraFinePrevista
									LEFT JOIN casi AS C ON AC.ac_IdEvento = C.cas_IdEvento AND AC.ac_IdRisorsa = C.cas_IdRisorsa
									LEFT JOIN risorse AS RIS ON AC.ac_IdRisorsa = RIS.ris_IdRisorsa
									WHERE AC.ac_ManOrd = 1 AND IM.im_Risolto = 1
									GROUP BY ac_ManOrd_DataInizioPrevista, ac_ManOrd_OraInizioPrevista, AC.ac_ManOrd, AC.ac_ManOrd_Progressivo, AC.ac_ManOrd_DataInizioPrevista, AC.ac_ManOrd_OraInizioPrevista, AC.ac_ManOrd_DataFinePrevista, AC.ac_ManOrd_OraFinePrevista, AC.ac_ManOrd_Descrizione, AC.ac_ManOrd_BloccoLinea, IM.im_Risolto, IM.im_Riconosciuto
									HAVING STRING_AGG(ris_IdRisorsa , ',') LIKE :IdRisorsa", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
		$sth->execute(array(":IdRisorsa" => "%" . $_REQUEST["idRisorsa"] . "%"));
	} else if ($_REQUEST["statoEvento"] == '%') {
		// seleziono i dati della tabella di lavoro 'risorse_coinvolte_work'
		$sth = $conn_mes->prepare("SELECT STRING_AGG(ris_Descrizione , '/') AS ElencoRisorse, STRING_AGG(ris_IdRisorsa , ',') AS ElencoIdRisorse, AC.ac_ManOrd, AC.ac_ManOrd_Progressivo, AC.ac_ManOrd_DataInizioPrevista, AC.ac_ManOrd_OraInizioPrevista, AC.ac_ManOrd_DataFinePrevista, AC.ac_ManOrd_OraFinePrevista, AC.ac_ManOrd_Descrizione, AC.ac_ManOrd_BloccoLinea, IM.im_Risolto, IM.im_Riconosciuto
									FROM attivita_casi AS AC
									LEFT JOIN interventi_manutenzione AS IM ON AC.ac_ManOrd_Progressivo = IM.im_ProgressivoManOrdinaria AND AC.ac_ManOrd_DataInizioPrevista = IM.im_DataInizioPrevista AND AC.ac_ManOrd_OraInizioPrevista = IM.im_OraInizioPrevista AND AC.ac_ManOrd_DataFinePrevista = IM.im_DataFinePrevista AND AC.ac_ManOrd_OraFinePrevista = IM.im_OraFinePrevista
									LEFT JOIN casi AS C ON AC.ac_IdEvento = C.cas_IdEvento AND AC.ac_IdRisorsa = C.cas_IdRisorsa
									LEFT JOIN risorse AS RIS ON AC.ac_IdRisorsa = RIS.ris_IdRisorsa
									WHERE AC.ac_ManOrd = 1 AND (IM.im_Risolto = 1 OR IM.im_Risolto = 0 OR IM.im_Risolto IS NULL)
									GROUP BY AC.ac_ManOrd_DataInizioPrevista, AC.ac_ManOrd_OraInizioPrevista, AC.ac_ManOrd, AC.ac_ManOrd_Progressivo, AC.ac_ManOrd_DataInizioPrevista, AC.ac_ManOrd_OraInizioPrevista, AC.ac_ManOrd_DataFinePrevista, AC.ac_ManOrd_OraFinePrevista, AC.ac_ManOrd_Descrizione, AC.ac_ManOrd_BloccoLinea, IM.im_Risolto, IM.im_Riconosciuto
									HAVING STRING_AGG(ris_IdRisorsa , ',') LIKE  :IdRisorsa", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
		$sth->execute(array(":IdRisorsa" => "%" . $_REQUEST["idRisorsa"] . "%"));
	}



	$output = array();

	if ($sth->rowCount() > 0) {
		$righe = $sth->fetchAll(PDO::FETCH_ASSOC);

		foreach ($righe as $riga) {


			$casellaRiconosciuto = "";
			$casellaRisolto = "";

			// Formatto la stringa che conterrà la data di 'inizio evento'
			if (isset($riga["ac_ManOrd_DataInizioPrevista"])) {
				$dInizio = new DateTime($riga["ac_ManOrd_DataInizioPrevista"] . " " . $riga["ac_ManOrd_OraInizioPrevista"]);
				$stringaDataInizio = $dInizio->format('d/m/Y - H:i:s');
			} else {
				$stringaDataInizio = "";
			}

			// Formatto la stringa che conterrà la data di 'inizio evento'
			if (isset($riga["ac_ManOrd_DataFinePrevista"])) {
				$dFine = new DateTime($riga["ac_ManOrd_DataFinePrevista"] . " " . $riga["ac_ManOrd_OraFinePrevista"]);
				$stringaDataFine = $dFine->format('d/m/Y - H:i:s');
			} else {
				$stringaDataFine = "";
			}



			// Formatto la stringhe che conterrà il pulsante di 'riconosci evento'
			if (((isset($riga["im_Riconosciuto"])) && ($riga["im_Riconosciuto"] == false)) || (!isset($riga["im_Riconosciuto"]))) {
				$casellaRiconosciuto = '<button class="btn btn-secondary riconosci-man-ord" type="button" data-id_riga="' . $riga["ac_ManOrd_Progressivo"] . '" title="Riconosci evento">
											<span class="mdi mdi-check mdi-18px"></span>
											</button>';
			} else if ((isset($riga["im_Riconosciuto"])) && ($riga["im_Riconosciuto"] == true)) {
				$casellaRiconosciuto = '<span class="mdi mdi-check-all mdi-18px"></span>';
			}


			// Formatto la stringhe che conterrà il pulsante di 'risolvi evento'
			if (((isset($riga["im_Risolto"])) && ($riga["im_Risolto"] == false)) || (!isset($riga["im_Risolto"]))) {
				$casellaRisolto = '<button class="btn btn-secondary risolvi-man-ord" type="button" data-id_riga="' . $riga["ac_ManOrd_Progressivo"] . '" title="Risolvi evento">
											<span class="mdi mdi-clipboard-check mdi-18px"></span>
										</button>';

				$casellaAzioni = '<div class="dropdown">
										<button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Modifica riga">
											<span class="mdi mdi-lead-pencil mdi-18px"></span>
										</button>
										<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
											<a class="dropdown-item modifica-man-ord" data-id_riga="' . $riga["ac_ManOrd_Progressivo"] . '"><i class="mdi mdi-account-edit"></i> Modifica</a>
											<a class="dropdown-item cancella-man-ord" data-id_riga="' . $riga["ac_ManOrd_Progressivo"] . '"><i class="mdi mdi-trash-can"></i> Elimina</a>
										</div>
									</div>';
			} else if ((isset($riga["im_Risolto"])) && ($riga["im_Risolto"] == true)) {
				$casellaRisolto = '<button class="btn btn-success vedi-rapporto-man-ord" type="button" data-id_riga="' . $riga["ac_ManOrd_Progressivo"] . '" title="Apri rapporto di lavoro">
											<span class="mdi mdi-eye mdi-18px"></span>
										</button>';

				$casellaAzioni = '<div class="dropdown">
										<button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Modifica riga" disabled>
											<span class="mdi mdi-lead-pencil mdi-18px"></span>
										</button>
										<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
											<a class="dropdown-item modifica-man-ord" data-id_riga="' . $riga["ac_ManOrd_Progressivo"] . '"><i class="mdi mdi-account-edit"></i> Modifica</a>
											<a class="dropdown-item cancella-man-ord" data-id_riga="' . $riga["ac_ManOrd_Progressivo"] . '"><i class="mdi mdi-trash-can"></i> Elimina</a>
										</div>
									</div>';
			}


			$marked = '<div class="d-flex justify-content-center align-items-center" style="max-height:18px"><i class="mdi mdi-checkbox-marked mdi-18px"></i></div>';
			$unmarked = '<div class="d-flex justify-content-center align-items-center" style="max-height:18px"><span class="mdi mdi-checkbox-blank-outline "></span></div>';


			//Preparo i dati da visualizzare
			$output[] = array(

				"Risorsa" => $riga["ElencoRisorse"],
				"DataOraInizio" => $stringaDataInizio,
				"DataOraFine" => $stringaDataFine,
				"Descrizione" => $riga["ac_ManOrd_Descrizione"],
				"BloccoLinea" => ($riga["ac_ManOrd_BloccoLinea"] == 0 ? $marked : $unmarked),
				"Riconosciuto" => $riga["im_Riconosciuto"],
				"Risolto" =>  $riga["im_Risolto"],
				"PulsanteRisolto" =>  $casellaRisolto,
				"Azioni" => $casellaAzioni
			);
		}

		die(json_encode($output));
	} else {
		die("NO_ROWS");
	}
}

// MANUTENZIONI ORDINARIE: RECUPERO INFO MANUTENZIONE ORDINARIA SELEZIONATA
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "recupera-man-ord" && !empty($_REQUEST["codice"])) {
	// estraggo la lista
	$sth = $conn_mes->prepare("SELECT GROUP_CONCAT(R.ris_Descrizione SEPARATOR ' / ') AS ElencoRisorse, AC.ac_IdRisorsa, AC.ac_IdRiga, AC.ac_ManOrd_DataInizioPrevista, AC.ac_ManOrd_OraInizioPrevista, AC.ac_ManOrd_DataFinePrevista, AC.ac_ManOrd_OraFinePrevista, AC.ac_ManOrd_Descrizione, AC.ac_ManOrd_BloccoLinea, AC.ac_ManOrd, LP.lp_Descrizione, IM.im_DataInizioPrevista, IM.im_OraInizioPrevista, IM.im_DataFinePrevista, IM.im_OraFinePrevista, IM.im_DataInterventoInizio, IM.im_OraInterventoInizio, IM.im_DataInterventoFine, IM.im_OraInterventoFine, IM.im_PersonaleRic, IM.im_PersonaleRis, IM.im_DescrizioneIntervento
									FROM attivita_casi AS AC
									LEFT JOIN interventi_manutenzione AS IM ON AC.ac_ManOrd_Progressivo = IM.im_ProgressivoManOrdinaria AND AC.ac_ManOrd_DataInizioPrevista = IM.im_DataInizioPrevista AND AC.ac_ManOrd_OraInizioPrevista = IM.im_OraInizioPrevista AND AC.ac_ManOrd_DataFinePrevista = IM.im_DataFinePrevista AND AC.ac_ManOrd_OraFinePrevista = IM.im_OraFinePrevista
									LEFT JOIN risorse AS R ON AC.ac_IdRisorsa = R.ris_IdRIsorsa
									LEFT JOIN linee_produzione AS LP ON R.ris_LineaProduzione = LP.lp_IdLinea
									WHERE ac_ManOrd_Progressivo = :codice AND ac_ManOrd = 1
									GROUP BY ac_ManOrd_DataInizioPrevista, ac_ManOrd_OraFinePrevista
									ORDER BY R.ris_Descrizione ASC", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
	$sth->execute(array(":codice" => $_REQUEST["codice"]));
	$riga = $sth->fetch(PDO::FETCH_ASSOC);

	die(json_encode($riga));
}



// MANUTENZIONI ORDINARIE: CANCELLAZIONE ORDINARIA SELEZIONATA
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "cancella-man-ord" && !empty($_REQUEST["id"])) {

	// Cancellazione dalla tabella 'attivita_casi'
	$sthDeleteManOrdinaria = $conn_mes->prepare("DELETE FROM attivita_casi WHERE ac_ManOrd_Progressivo = :id");
	$sthDeleteManOrdinaria->execute(array(":id" => $_REQUEST["id"]));

	// Cancellazione dalla tabella 'ordini_produzione' (se era previsto blocco linea)
	$sthDeleteCommessaMan = $conn_mes->prepare("DELETE FROM ordini_produzione WHERE op_ProgressivoMan = :id");
	$sthDeleteCommessaMan->execute(array(":id" => $_REQUEST["id"]));

	// Cancellazione dalla tabella 'riorse_coinvolte' (se era previsto blocco linea)
	$sthDeleteRisorseCoinvolte = $conn_mes->prepare("DELETE FROM risorse_coinvolte WHERE rc_ProgressivoMan = :id");
	$sthDeleteRisorseCoinvolte->execute(array(":id" => $_REQUEST["id"]));


	// definisco transazione SQL
	$conn_mes->beginTransaction();

	// sintesi esito operazioni e valori di ritorno
	if ($sthDeleteManOrdinaria && $sthDeleteCommessaMan && $sthDeleteRisorseCoinvolte) {
		$conn_mes->commit();
		die("OK");
	} else {
		$conn_mes->rollback();
		die("ERRORE");
	}
}



// MANUTENZIONI ORDINARIE:  SALVATAGGIO DATI DA POPUP 'INSERIMENTO'
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "salva-ins-man-ord" && !empty($_REQUEST["data"])) {

	// Recupero i parametri dal POST
	$parametri = array();
	parse_str($_REQUEST["data"], $parametri);

	// Ricavo data e ora attuali e le formatto opportunamente
	$dataOdierna = date('Y-m-d');
	$oraOdierna = date('H:i:s');

	$dataOdiernaNomeOrdine = date('Ymd');
	$oraOdiernaNomeOrdine = date('His');

	$dataInterventoInizio = substr($parametri['manOrdIns_DataInizioPrevista'], 0, 10);
	$oraInterventoInizio = substr($parametri['manOrdIns_DataInizioPrevista'], 11, 15);

	$dataInterventoFine = substr($parametri['manOrdIns_DataFinePrevista'], 0, 10);
	$oraInterventoFine = substr($parametri['manOrdIns_DataFinePrevista'], 11, 15);


	$bloccoLinea = $_REQUEST["bloccoLinea"];
	$elencoMacchine = $_REQUEST["elencoMacchine"];


	// Recupero progressivo attuale 'manutenzioni'
	$sthProgressivoMan = $conn_mes->prepare("SELECT MAX(ac_ManOrd_Progressivo) AS IndiceProgressivoAttuale
									FROM attivita_casi AS AC", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
	$sthProgressivoMan->execute();
	$rigaProgressivoMan = $sthProgressivoMan->fetch(PDO::FETCH_ASSOC);
	$progressivoManAggiornato = intval($rigaProgressivoMan['IndiceProgressivoAttuale']) + 1;


	// Se ho macchine in elenco
	if (isset($elencoMacchine)) {

		// Per ognuna delle macchine da 'manutenere'
		foreach ($elencoMacchine as $value) {

			// Credo evento ed eseguo INSERT in tabella 'attivita_casi'
			$sqlInsertEvento = "INSERT INTO attivita_casi(ac_IdRisorsa,ac_ManOrd,ac_ManOrd_Progressivo,ac_ManOrd_DataInizioPrevista,ac_ManOrd_OraInizioPrevista,ac_ManOrd_DataFinePrevista,ac_ManOrd_OraFinePrevista,ac_ManOrd_Descrizione,ac_ManOrd_BloccoLinea) VALUES(:IdRisorsa,:Man,:ManProgressivo,:ManDataInizio,:ManOraInizio,:ManDataFine,:ManOraFine,:ManDescrizione,:ManBloccoLinea)";

			$sthInsertEvento = $conn_mes->prepare($sqlInsertEvento);
			$sthInsertEvento->execute(array(
				":IdRisorsa" => $value,
				":Man" => 1,
				":ManProgressivo" => $progressivoManAggiornato,
				":ManDataInizio" => $dataInterventoInizio,
				":ManOraInizio" => $oraInterventoInizio,
				":ManDataFine" => $dataInterventoFine,
				":ManOraFine" => $oraInterventoFine,
				":ManDescrizione" => $parametri["manOrdIns_DescrizioneIntervento"],
				":ManBloccoLinea" => $bloccoLinea
			));


			// Se la manutenzione prevede di bloccare la linea
			if ($bloccoLinea == 1) {

				$sqlInsertRisorseCoinvolte = "INSERT INTO risorse_coinvolte(rc_IdRisorsa,rc_IdProduzione,rc_LineaProduzione,rc_NoteIniziali,rc_ProgressivoMan) VALUES(:IdRisorsa,:IdProduzione,:IdLineaProduzione,:NoteIniziali,:ProgressivoMan)";

				$sthInsertRisorsaCoinvolta = $conn_mes->prepare($sqlInsertRisorseCoinvolte);
				$sthInsertRisorsaCoinvolta->execute(array(
					":IdRisorsa" => $value,
					":IdProduzione" => "MAN_" . $dataOdiernaNomeOrdine . "_" . $oraOdiernaNomeOrdine,
					":IdLineaProduzione" => $parametri['manOrdIns_LineeProduzione'],
					":NoteIniziali" => "MANUTENZIONE ORDINARIA",
					":ProgressivoMan" => $progressivoManAggiornato
				));
			}
		}


		// Se la manutenzione prevede di bloccare la linea
		if ($bloccoLinea == 1) {

			$sqlInsertCommessaManutenzione = "INSERT INTO ordini_produzione(op_IdProduzione,op_Prodotto,op_DataOrdine,op_OraOrdine,op_DataProduzione,op_OraProduzione,op_NoteProduzione,op_Stato,op_LineaProduzione,op_ProgressivoMan,op_DataFineTeorica,op_OraFineTeorica) VALUES(:IdProduzione,:IdProdotto,:DataOrdine,:OraOrdine,:DataProduzione,:OraProduzione,:NoteProduzione,:StatoOrdine,:LineaProduzione,:ProgressivoMan,:DataFineTeorica,:OraFineTeorica)";

			$sthInsertCommessaManutenzione = $conn_mes->prepare($sqlInsertCommessaManutenzione);
			$sthInsertCommessaManutenzione->execute(array(
				":IdProduzione" => "MAN_" . $dataOdiernaNomeOrdine . "_" . $oraOdiernaNomeOrdine,
				":IdProdotto" => "ND",
				":DataOrdine" => $dataOdierna,
				":OraOrdine" => $oraOdierna,
				":DataProduzione" => $dataInterventoInizio,
				":OraProduzione" => $oraInterventoInizio,
				":NoteProduzione" => "MANUTENZIONE PROGRAMMATA",
				":StatoOrdine" => 6,
				":LineaProduzione" => $parametri['manOrdIns_LineeProduzione'],
				":ProgressivoMan" => $progressivoManAggiornato,
				":DataFineTeorica" => $dataInterventoFine,
				":OraFineTeorica" => $oraInterventoFine
			));
		}

		// Definisco transazione SQL
		$conn_mes->beginTransaction();

		// Sintesi esito operazioni e valori di ritorno
		if (($bloccoLinea && ($sthInsertCommessaManutenzione && $sthInsertEvento && $sthInsertRisorsaCoinvolta)) || (!$bloccoLinea && $sthInsertEvento)) {
			$conn_mes->commit();
			die("OK");
		} else {
			$conn_mes->rollback();
			die("ERRORE");
		}
	} else {
		die("ERRORE");
	}
}





// MANUTENZIONI ORDINARIE: SALVATAGGIO DATI DA POPUP 'MODIFICA' MANUTENZIONE ORDINARIA
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "salva-mod-man-ord" && !empty($_REQUEST["data"])) {
	// Recupero i parametri dal POST
	$parametri = array();
	parse_str($_REQUEST["data"], $parametri);

	$id_modifica = $parametri["manOrdMod_IdRiga"];

	$dataInizioPrevista = substr($parametri['manOrdMod_DataInizioPrevista'], 0, 10);
	$oraInizioPrevista = substr($parametri['manOrdMod_DataInizioPrevista'], 11, 15);

	$dataFinePrevista = substr($parametri['manOrdMod_DataFinePrevista'], 0, 10);
	$oraFinePrevista = substr($parametri['manOrdMod_DataFinePrevista'], 11, 15);


	// Eseguo UPDATE della entry in 'attivita_casi'
	$sqlUpdateManOrdinaria = "UPDATE attivita_casi SET
					ac_ManOrd_DataInizioPrevista = :ManDataInizio,
					ac_ManOrd_OraInizioPrevista = :ManOraInizio,
					ac_ManOrd_DataFinePrevista = :ManDataFine,
					ac_ManOrd_OraFinePrevista = :ManOraFine,
					ac_ManOrd_Descrizione = :ManDescrizione
					WHERE ac_ManOrd_Progressivo = :IdRiga";

	$sthUpdateManOrdinaria = $conn_mes->prepare($sqlUpdateManOrdinaria);
	$sthUpdateManOrdinaria->execute(array(
		":ManDataInizio" => $dataInizioPrevista,
		":ManOraInizio" => $oraInizioPrevista,
		":ManDataFine" => $dataFinePrevista,
		":ManOraFine" => $oraFinePrevista,
		":ManDescrizione" => $parametri["manOrdMod_DescrizioneIntervento"],
		":IdRiga" => $id_modifica
	));


	// Verifica esito
	if ($sthUpdateManOrdinaria) {
		die("OK");
	} else {
		die("ERRORE");
	}
}


// MANUTENZIONI ORDINARIE: SALVATAGGIO DATI DA POPUP 'RISOLVI'
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "salva-rapporto-man-ord" && !empty($_REQUEST["data"])) {

	// Recupero i parametri dal POST
	$parametri = array();
	parse_str($_REQUEST["data"], $parametri);

	// Ricavo data e ora attuali
	$dataOdierna = date('Y-m-d');
	$oraOdierna = date('H:i:s');

	$dataInizioPrevisto = substr($parametri['manOrdRis_DataInizioPrevista'], 0, 10);
	$oraInizioPrevisto = substr($parametri['manOrdRis_DataInizioPrevista'], 11, 15);

	$dataFinePrevista = substr($parametri['manOrdRis_DataFinePrevista'], 0, 10);
	$oraFinePrevista = substr($parametri['manOrdRis_DataFinePrevista'], 11, 15);

	$dataInterventoInizio = substr($parametri['manOrdRis_DataInterventoInizio'], 0, 10);
	$oraInterventoInizio = substr($parametri['manOrdRis_DataInterventoInizio'], 11, 15);

	$dataInterventoFine = substr($parametri['manOrdRis_DataInterventoFine'], 0, 10);
	$oraInterventoFine = substr($parametri['manOrdRis_DataInterventoFine'], 11, 15);



	// Eseguo INSERT rapporto in tabella 'interventi_manutenzione'
	$sqlRegistraRapportoLavoro = "INSERT INTO interventi_manutenzione(im_TipoRapporto,im_IdEvento,im_DescrizioneEvento,im_DataInizioPrevista,im_OraInizioPrevista,im_DataFinePrevista,im_OraFinePrevista,im_DataRiconoscimento,im_OraRiconoscimento,im_DataInterventoInizio,im_OraInterventoInizio,im_DataInterventoFine,im_OraInterventoFine,im_Riconosciuto,im_Risolto,im_CodicePersonaleRic,im_PersonaleRic,im_CodicePersonaleRis,im_PersonaleRis,im_DescrizioneIntervento,im_ProgressivoManOrdinaria) VALUES(:TipoRapporto,:IdEvento,:DescrizioneEvento,:DataInizioPrevista,:OraInizioPrevista,:DataFinePrevista,:OraFinePrevista,:DataRiconoscimento,:OraRiconoscimento,:DataInterventoInizio,:OraInterventoInizio,:DataInterventoFine,:OraInterventoFine,:FlagRiconosciuto,:FlagRisolto,:CodiceRiconoscimento,:PersonaleRiconoscimento,:CodiceRisoluzione,:PersonaleRisoluzione,:DescrizioneIntervento,:ProgressivoManOrdinaria)";

	$sthRegistraRapportoLavoro = $conn_mes->prepare($sqlRegistraRapportoLavoro);
	$sthRegistraRapportoLavoro->execute(array(
		":TipoRapporto" => "ORD",
		":IdEvento" => "man_ordinaria",
		":DescrizioneEvento" => $parametri['manOrdRis_DescrizioneIntervento'],
		":DataInizioPrevista" => $dataInizioPrevisto,
		":OraInizioPrevista" => $oraInizioPrevisto,
		":DataFinePrevista" => $dataFinePrevista,
		":OraFinePrevista" => $oraFinePrevista,
		":DataRiconoscimento" => $dataOdierna,
		":OraRiconoscimento" => $oraOdierna,
		":DataInterventoInizio" => $dataInterventoInizio,
		":OraInterventoInizio" => $oraInterventoInizio,
		":DataInterventoFine" => $dataInterventoFine,
		":OraInterventoFine" => $oraInterventoFine,
		":FlagRiconosciuto" => true,
		":FlagRisolto" => true,
		":CodiceRiconoscimento" => $_SESSION["utente"]['usr_IdUtente'],
		":PersonaleRiconoscimento" => $_SESSION["utente"]['usr_Nome'] . " " . $_SESSION["utente"]['usr_Cognome'],
		":CodiceRisoluzione" => $_SESSION["utente"]['usr_IdUtente'],
		":PersonaleRisoluzione" => $_SESSION["utente"]['usr_Nome'] . " " . $_SESSION["utente"]['usr_Cognome'],
		":DescrizioneIntervento" => $parametri["manOrdRis_NoteIntervento"],
		":ProgressivoManOrdinaria" => $parametri["manOrdRis_IdRiga"]
	));

	// Verifica esito
	if ($sthRegistraRapportoLavoro) {
		die("OK");
	} else {
		die("ERRORE");
	}
}



// AUSILIARIA: POPOLAMENTO SELECT RISORSE IN BASE A LINEA SELEZIONATA
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "mntOrd-carica-select-risorse" && !empty($_REQUEST["idLineaProduzione"])) {
	if ($_REQUEST["idLineaProduzione"] == "_") {
		$sth = $conn_mes->prepare("SELECT risorse.*
									FROM risorse", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
		$sth->execute();
	} else {

		$sth = $conn_mes->prepare("SELECT risorse.*
							FROM risorse
							WHERE risorse.ris_LineaProduzione = :IdLineaProduzione", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
		$sth->execute(array(":IdLineaProduzione" => $_REQUEST["idLineaProduzione"]));
	}

	$risorse = $sth->fetchAll(PDO::FETCH_ASSOC);
	$optionValue = "";

	//Se ho trovato sottocategorie
	if ($risorse) {

		//Aggiungo ognuna delle sottocategorie trovate alla stringa che conterrà le possibili opzioni della select categorie, e che ritorno come risultato
		foreach ($risorse as $risorsa) {

			//Se ho già una sottocategoria selezionata (provengo da popup "di modifica"), preparo il contenuto della select con l'option value corretto selezionato altrimenti preparo solo il contenuto.
			if (!empty($_REQUEST["idRisorsa"]) && $_REQUEST["idRisorsa"] == $risorsa['ris_IdRisorsa']) {
				$optionValue = $optionValue . "<option value='" . $risorsa['ris_IdRisorsa'] . "' selected>" . strtoupper($risorsa['ris_Descrizione']) . " </option>";
			} else {
				$optionValue = $optionValue . "<option value='" . $risorsa['ris_IdRisorsa'] . "'>" . strtoupper($risorsa['ris_Descrizione']) . " </option>";
			}
		}
	}
	echo $optionValue;
	exit();
}





?>
<!DOCTYPE html>
<html lang="it">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>PROSPECT40 | Gestione manutenzione</title>
	<?php include("inc_css.php") ?>
</head>

<body>

	<div class="container-scroller">

		<?php include("inc_testata.php") ?>

		<div class="container-fluid page-body-wrapper">

			<div class="main-panel">

				<div class="content-wrapper">

					<div class="card">
						<div class="car-header">
							<h4 class="card-title m-2">MANUTENZIONE</h4>
						</div>

						<div class="card-body">

							<ul class="nav nav-tabs pt-2" id="tab-manutenzione-stato-ordini" role="tablist">
								<li class="nav-item text-center" style="width: calc(100% / 4);">
									<a aria-controls="manutenzione-ordinaria" aria-selected="true" class="nav-link rounded-2"
										data-toggle="tab" href="#manutenzione-ordinaria" id="tab-manutenzione-ordinaria" role="tab"><b>MAN.
											PROGRAMMATE</b></a>
								</li>

								<li class="nav-item text-center" style="width: calc(100% / 4);">
									<a aria-controls="manutenzione-stato-eventi" aria-selected="true" class="nav-link rounded-2 show"
										data-toggle="tab" href="#manutenzione-stato-eventi" id="tab-manutenzione-stato-eventi"
										role="tab"><b>MAN. RICHIESTE</b></a>
								</li>

								<li class="nav-item text-center" style="width: calc(100% / 4);">
									<a aria-controls="manutenzione-stato-ordini-avviati" aria-selected="true"
										class="nav-link rounded-2 show" data-toggle="tab" href="#manutenzione-stato-ordini-avviati"
										id="tab-manutenzione-stato-ordini-avviati" role="tab"><b>COMMESSE IN CORSO</b></a>
								</li>

								<li class="nav-item text-center" style="width: calc(100% / 4);">
									<a aria-controls="manutenzione-stato-ordini-attivi" aria-selected="true" class="nav-link rounded-2"
										data-toggle="tab" href="#manutenzione-stato-ordini-attivi" id="tab-manutenzione-stato-ordini-attivi"
										role="tab"><b>COMMESSE PROGRAMMATE</b></a>
								</li>

								<!--
								<li class="nav-item text-center" style = "width: calc(100% / 5);">
									<a aria-controls="manutenzione-stato-ordini-chiusi" aria-selected="true" class="nav-link rounded-2" data-toggle="tab" href="#manutenzione-stato-ordini-chiusi" id="tab-manutenzione-stato-ordini-chiusi" role="tab"><b>COMMESSE COMPLETATE</b></a>
								</li>
								-->
							</ul>


							<div class="tab-content tab-manutenzione-stato-ordini">

								<!-- Tab MANUTENZIONI ORDINARIE -->
								<div aria-labelledby="tab-manutenzione-ordinaria" class="tab-pane" id="manutenzione-ordinaria"
									role="tabpanel">
									<div class="row mt-1">
										<div class="col-4">

											<div class="form-group">
												<label for="mntOrd_FiltroRisorse">Macchine</label>
												<select class="form-control form-control-sm selectpicker dati-report" id="mntOrd_FiltroRisorse"
													name="mntOrd_FiltroRisorse" data-live-search="true">
													<?php
													$sth = $conn_mes->prepare("SELECT risorse.ris_IdRisorsa, risorse.ris_Descrizione , linee_produzione.lp_Descrizione
																				FROM risorse
																				LEFT JOIN linee_produzione ON risorse.ris_LineaProduzione = linee_produzione.lp_IdLinea
																				ORDER BY linee_produzione.lp_Descrizione ASC, risorse.ris_Descrizione ASC", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
													$sth->execute();
													$linee = $sth->fetchAll(PDO::FETCH_ASSOC);
													echo "<option value='%'>TUTTE</option>";
													foreach ($linee as $linea) {
														echo "<option value='" . $linea['ris_IdRisorsa'] . "'>" . strtoupper($linea['lp_Descrizione'] . " - " . $linea['ris_Descrizione']) . "</option>";
													}
													?>
												</select>
											</div>
										</div>

										<div class="col-4">

											<div class="form-group">
												<label for="mntOrd_StatoEvento">Stato evento</label>
												<select class="form-control form-control-sm selectpicker dati-report" name="mntOrd_StatoEvento"
													id="mntOrd_StatoEvento" data-live-search="true">
													<option value="%">TUTTI</option>
													<option value="aperti">APERTI</option>
													<option value="risolti">RISOLTI</option>
												</select>
											</div>
										</div>

									</div>
									<div class="row">

										<div class="col-12">

											<div class="table-responsive">

												<table id="tabellaDati-manutenzione-ordinaria" class="table table-striped" style="width:100%"
													data-source="">
													<thead>
														<tr>
															<th>Macchina </th>
															<th>Inizio previsto</th>
															<th>Termine previsto</th>
															<th>Descrizione</th>
															<th>Linea non disp.</th>
															<th>Aux Stato Ric.</th>
															<th>Aux Stato Ris.</th>
															<th>Ris.</th>
															<th>Azioni</th>
														</tr>
													</thead>
													<tbody></tbody>

												</table>

											</div>
										</div>
									</div>

									<button type="button" id="crea-manutenzione-ordinaria" class="mdi mdi-button">PROGRAMMA MAN.
										ORDINARIA</button>

								</div>


								<!-- Tab MANUTENZIONI STRAORDINARIE (STATO EVENTI) -->
								<div aria-labelledby="tab-manutenzione-stato-eventi" class="tab-pane show"
									id="manutenzione-stato-eventi" role="tabpanel">


									<div class="row mt-1">
										<div class="col-4">

											<div class="form-group">
												<label for="mnt_LineeProduzione">Linea</label>
												<select class="form-control form-control-sm selectpicker dati-report" id="mnt_LineeProduzione"
													name="mnt_LineeProduzione" data-live-search="true" required>
													<?php
													$sth = $conn_mes->prepare("SELECT linee_produzione.*
																					FROM linee_produzione
																					WHERE linee_produzione.lp_IdLinea != 'lin_0P' AND linee_produzione.lp_IdLinea != 'lin_0X'
																					ORDER BY linee_produzione.lp_Descrizione ASC", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
													$sth->execute();
													$linee = $sth->fetchAll(PDO::FETCH_ASSOC);
													echo "<option value='%'>TUTTE</option>";
													foreach ($linee as $linea) {
														echo "<option value='" . $linea['lp_IdLinea'] . "'>" . strtoupper($linea['lp_Descrizione']) . "</option>";
													}
													?>
												</select>
											</div>
										</div>

										<div class="col-4">

											<div class="form-group">
												<label for="mnt_TipoEvento">Tipologia evento</label>
												<select class="form-control form-control-sm selectpicker dati-report" id="mnt_TipoEvento"
													name="mnt_TipoEvento" data-live-search="true" required>

													<?php
													$sth = $conn_mes->prepare("SELECT tipi_evento.*
																					FROM tipi_evento
																					WHERE tipi_evento.te_IdTipoEvento = 'OK' OR tipi_evento.te_IdTipoEvento = 'KO'", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
													$sth->execute();
													$linee = $sth->fetchAll(PDO::FETCH_ASSOC);
													echo "<option value='%'>TUTTI</option>";
													foreach ($linee as $linea) {
														echo "<option value='" . $linea['te_IdTipoEvento'] . "'>" . strtoupper($linea['te_Descrizione']) . "</option>";
													}
													?>
												</select>
											</div>
										</div>

										<div class="col-4">

											<div class="form-group">
												<label for="mnt_StatoEvento">Stato evento</label>
												<select class="form-control form-control-sm selectpicker dati-report" name="mnt_StatoEvento"
													id="mnt_StatoEvento">
													<option value="%">TUTTI</option>
													<option value="aperti">APERTI</option>
													<option value="risolti">RISOLTI</option>
												</select>
											</div>
										</div>

									</div>
									<div class="row">

										<div class="col-12">

											<div class="table-responsive">

												<table id="tabellaDati-manutenzione-stato-eventi" class="table table-striped" style="width:100%"
													data-source="">
													<thead>
														<tr>
															<th>Linea</th>
															<th>Macchina</th>
															<th>Data/ora uscita</th>
															<th>Data/ora rientro</th>
															<th>Evento</th>
															<th>Tipo evento</th>
															<th>Gruppo</th>
															<th>Aux Stato Ric.</th>
															<th>Aux Stato Ris.</th>
															<th>Ric.</th>
															<th>Ris.</th>
														</tr>
													</thead>
													<tbody></tbody>

												</table>


											</div>
										</div>
									</div>
								</div>


								<!-- Tab COMMESSE AVVIATI -->
								<div aria-labelledby="tab-manutenzione-stato-ordini-avviati" class="tab-pane show"
									id="manutenzione-stato-ordini-avviati" role="tabpanel">
									<div class="row pt-3">

										<div class="col-12">

											<div class="table-responsive">

												<table id="tabellaDati-ordini-avviati" class="table table-striped" style="width:100%"
													data-source="">
													<thead>
														<tr>
															<th>Linea</th>
															<th>Commessa (rif.)</th>
															<th>Prodotto </th>
															<th>Lotto</th>
															<th>Qta da prod.</th>
															<th>Qta tot.</th>
															<th>Qta conforme</th>
															<th>Qta scarti</th>
															<th>Data-ora inizio</th>
															<th>Fine prevista</th>
															<th>Vel. linea</th>
															<th>Valore OEE</th>
															<th>Indice OEE [%]</th>
															<th></th>
															<th></th>
														</tr>
													</thead>
													<tbody></tbody>

												</table>

											</div>
										</div>
									</div>
								</div>

								<!-- Tab COMMESSE ATTIVI -->
								<div aria-labelledby="tab-manutenzione-stato-ordini-attivi" class="tab-pane"
									id="manutenzione-stato-ordini-attivi" role="tabpanel">
									<div class="row pt-3">

										<div class="col-12">

											<div class="table-responsive">

												<table id="tabellaDati-ordini-attivi" class="table table-striped" style="width:100%"
													data-source="">
													<thead>
														<tr>
															<th>Commessa (Rif.)</th>
															<th>Prodotto </th>
															<th>Lotto</th>
															<th>Linea</th>
															<th>Qta da prod.</th>
															<th>Inizio previsto</th>
															<th>Fine prevista</th>
															<th></th>
														</tr>
													</thead>
													<tbody></tbody>

												</table>

											</div>
										</div>
									</div>
								</div>


								<!-- Tab COMMESSE CHIUSI -->
								<div aria-labelledby="tab-manutenzione-stato-ordini-chiusi" class="tab-pane"
									id="manutenzione-stato-ordini-chiusi" role="tabpanel">


									<div class="row pt-3">

										<div class="col-12">

											<div class="table-responsive">

												<table id="tabellaDati-ordini-chiusi" class="table table-striped" style="width:100%"
													data-source="">
													<thead>
														<tr>
															<th>Codice commessa (rif.)</th>
															<th>Prodotto </th>
															<th>Linea</th>
															<th>Qta ric.</th>
															<th>Qta prd.</th>
															<th>Data/ora inizio</th>
															<th>Data/ora fine</th>
															<th>OEE [%]</th>
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


				</div>

				<?php include("inc_footer.php") ?>

			</div>

		</div>

	</div>




	<!-- *** MANUTENZIONI STRAORDINARIE *** -->

	<!-- Popup RISOLUZIONE EVENTO MANUTENZIONI STRAORDINARIE -->
	<div class="modal fade" id="modal-risolvi-evento" tabindex="-1" role="dialog"
		aria-labelledby="modal-risolvi-evento-label" aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modal-risolvi-evento-label">MANUTENZIONE - RAPPORTO DI LAVORO</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form class="forms-sample" id="form-risolvi-man-str">

						<div class="row">

							<div class="col-12">
								<div class="form-group">
									<label for="manStrRis_DescrizioneRisorsa">Macchina</label>
									<input readonly type="text" class="form-control form-control-sm dati-popup-modifica"
										name="manStrRis_DescrizioneRisorsa" id="manStrRis_DescrizioneRisorsa">
								</div>
							</div>

							<div class="col-6">
								<div class="form-group">
									<label for="manStrRis_IdEvento">Codice evento</label>
									<input readonly type="text" class="form-control form-control-sm dati-popup-modifica"
										name="manStrRis_IdEvento" id="manStrRis_IdEvento">
								</div>
							</div>

							<div class="col-6">
								<div class="form-group">
									<label for="manStrRis_DataEvento">Data evento</label>
									<input readonly type="datetime-local" class="form-control form-control-sm dati-popup-modifica"
										name="manStrRis_DataEvento" id="manStrRis_DataEvento">
								</div>
							</div>

							<div class="col-12">
								<div class="form-group">
									<label for="manStrRis_DescrizioneEvento">Evento</label>
									<input readonly type="text" class="form-control form-control-sm dati-popup-modifica"
										name="manStrRis_DescrizioneEvento" id="manStrRis_DescrizioneEvento">
								</div>
							</div>

							<div class="col-12">
								<hr>
							</div>

							<div class="col-6">
								<div class="form-group">
									<label for="manStrRis_DataInizioPrevista">Intervento previsto il</label>
									<input type="datetime-local" class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="manStrRis_DataInizioPrevista" id="manStrRis_DataInizioPrevista" autocomplete="off">
								</div>
							</div>

							<div class="col-6">
							</div>

							<div class="col-6">
								<div class="form-group">
									<label for="manStrRis_DataInizioIntervento">Inizio intervento</label>
									<input type="datetime-local" class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="manStrRis_DataInizioIntervento" id="manStrRis_DataInizioIntervento" autocomplete="off">
								</div>
							</div>

							<div class="col-6">
								<div class="form-group">
									<label for="manStrRis_DataFineIntervento">Fine intervento</label>
									<input type="datetime-local" class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="manStrRis_DataFineIntervento" id="manStrRis_DataFineIntervento" autocomplete="off">
								</div>
							</div>

							<div class="col-12">
								<div class="form-group">
									<label for="manStrRis_NoteIntervento">Descrizione intervento</label>
									<textarea class="form-control" name="manStrRis_NoteIntervento" id="manStrRis_NoteIntervento" rows="3"
										autocomplete="off"></textarea>
								</div>
							</div>

						</div>

						<input type="hidden" id="manStrRis_IdRiga" name="manStrRis_IdRiga" value="">
						<input type="hidden" id="manStrRis_IdRisorsa" name="manStrRis_IdRisorsa" value="">
						<input type="hidden" id="manStrRis_NumeroRapporto" name="manStrRis_NumeroRapporto" value="">
						<input type="hidden" id="manStrRis_Riconosciuto" name="manStrRis_Riconosciuto" value="">


					</form>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-success" id="salva-rapporto-man-str">Salva</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
				</div>

			</div>
		</div>
	</div>


	<!-- Popup VISUALIZZAZIONE RAPPORTO DI LAVORO MANUTENZIONI STRAORDINARIE -->
	<div class="modal fade" id="modal-vedi-rapporto-man-str" tabindex="-1" role="dialog"
		aria-labelledby="modal-vedi-rapporto-man-str-label" aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modal-vedi-rapporto-man-str-label">MANUTENZIONE - RAPPORTO DI LAVORO</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form class="forms-sample" id="form-vedi-rapporto-man-str">

						<div class="row">
							<div class="col-12">
								<div class="form-group">
									<label for="manStrVis_DescrizioneRisorsa">Macchina</label>
									<input readonly type="text" class="form-control form-control-sm dati-popup-modifica"
										name="manStrVis_DescrizioneRisorsa" id="manStrVis_DescrizioneRisorsa">
								</div>
							</div>

							<div class="col-6">
								<div class="form-group">
									<label for="manStrVis_IdEvento">Codice evento</label>
									<input readonly type="text" class="form-control form-control-sm dati-popup-modifica"
										name="manStrVis_IdEvento" id="manStrVis_IdEvento">
								</div>
							</div>

							<div class="col-6">
								<div class="form-group">
									<label for="manStrVis_DataEvento">Data evento</label>
									<input readonly type="datetime-local" class="form-control form-control-sm dati-popup-modifica"
										name="manStrVis_DataEvento" id="manStrVis_DataEvento">
								</div>
							</div>

							<div class="col-12">
								<div class="form-group">
									<label for="manStrVis_DescrizioneEvento">Evento</label>
									<input readonly type="text" class="form-control form-control-sm dati-popup-modifica"
										name="manStrVis_DescrizioneEvento" id="manStrVis_DescrizioneEvento">
								</div>
							</div>

							<div class="col-12">
								<hr>
							</div>

							<div class="col-6">
								<div class="form-group">
									<label for="manStrVis_DataInizioPrevista">Intervento previsto il</label>
									<input readonly type="datetime-local"
										class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="manStrVis_DataInizioPrevista" id="manStrVis_DataInizioPrevista">
								</div>
							</div>

							<div class="col-6">
							</div>

							<div class="col-6">
								<div class="form-group">
									<label for="manStrVis_DataInizioIntervento">Inizio intervento</label>
									<input readonly type="datetime-local"
										class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="manStrVis_DataInizioIntervento" id="manStrVis_DataInizioIntervento">
								</div>
							</div>

							<div class="col-6">
								<div class="form-group">
									<label for="manStrVis_DataFineIntervento">Fine intervento</label>
									<input readonly type="datetime-local"
										class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="manStrVis_DataFineIntervento" id="manStrVis_DataFineIntervento">
								</div>
							</div>

							<div class="col-12">
								<div class="form-group">
									<label for="manStrVis_NoteIntervento">Descrizione intervento</label>
									<textarea readonly class="form-control" name="manStrVis_NoteIntervento" id="manStrVis_NoteIntervento"
										rows="3"></textarea>
								</div>
							</div>

							<div class="col-6">
								<div class="form-group">
									<label for="manStrVis_PersonaleRic">Riconosciuto da</label>
									<input readonly type="text" class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="manStrVis_PersonaleRic" id="manStrVis_PersonaleRic">
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="manStrVis_PersonaleRis">Risolto da</label>
									<input readonly type="text" class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="manStrVis_PersonaleRis" id="manStrVis_PersonaleRis">
								</div>
							</div>

						</div>

					</form>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Chiudi</button>
				</div>

			</div>
		</div>
	</div>





	<!-- *** MANUTENZIONI ORDINARIE *** -->

	<!-- Popup PROGRAMMAZIONE MANUTENZIONE ORDINARIA -->
	<div class="modal fade" id="modal-ins-man-ordinaria" tabindex="-1" role="dialog"
		aria-labelledby="modal-ins-man-ordinaria-label" aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modal-ins-man-ordinaria-label">Programma manutenzione</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form class="forms-sample" id="form-ins-man-ord">

						<div class="row">
							<div class="col-12">
								<div class="form-group">
									<label for="manOrdIns_LineeProduzione">Linea</label>
									<select class="form-control form-control-sm selectpicker dati-report" id="manOrdIns_LineeProduzione"
										name="manOrdIns_LineeProduzione" data-live-search="true" required>
										<?php
										$sth = $conn_mes->prepare("SELECT linee_produzione.*
																		FROM linee_produzione
																		ORDER BY linee_produzione.lp_IdLinea ASC", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
										$sth->execute();
										$linee = $sth->fetchAll(PDO::FETCH_ASSOC);

										foreach ($linee as $linea) {
											echo "<option value='" . $linea['lp_IdLinea'] . "'>" . strtoupper($linea['lp_Descrizione']) . "</option>";
										}
										?>
									</select>
								</div>
							</div>

							<div class="col-12">
								<div class="form-group">
									<label for="manOrdIns_Risorse">Macchine</label>
									<select class="form-control form-control-sm selectpicker dati-report" id="manOrdIns_Risorse"
										name="manOrdIns_Risorse" multiple data-live-search="true">
									</select>
								</div>
							</div>
							<div class="col-12">
								<div class="form-check">
									<input id="manOrdIns_LineaBloccata" type="checkbox">
									<label for="manOrdIns_LineaBloccata" style="font-weight: normal;">Considera linea NON
										DISPONIBILE</label>
								</div>
							</div>

							<div class="col-12">
								<hr>
							</div>

							<div class="col-6">
								<div class="form-group">
									<label for="manOrdIns_DataInizioPrevista">Inizio previsto il</label>
									<input type="datetime-local" class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="manOrdIns_DataInizioPrevista" id="manOrdIns_DataInizioPrevista">
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="manOrdIns_DataFinePrevista">Termine previsto il</label>
									<input type="datetime-local" class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="manOrdIns_DataFinePrevista" id="manOrdIns_DataFinePrevista">
								</div>
							</div>
							<div class="col-12">
								<div class="form-group">
									<label for="manOrdIns_DescrizioneIntervento">Descrizione intervento</label>
									<textarea class="form-control obbligatorio" name="manOrdIns_DescrizioneIntervento"
										id="manOrdIns_DescrizioneIntervento" rows="3" autocomplete="off"></textarea>
								</div>
							</div>
						</div>


					</form>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-success" id="salva-ins-man-ord">Salva</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
				</div>

			</div>
		</div>
	</div>



	<!-- Popup MODIFICA MANUTENZIONE ORDINARIA -->
	<div class="modal fade" id="modal-mod-man-ord" tabindex="-1" role="dialog" aria-labelledby="modal-mod-man-ord-label"
		aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modal-mod-man-ord-label">Modifica manutenzione</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form class="forms-sample" id="form-mod-man-ord">

						<div class="row">
							<div class="col-12">
								<div class="form-group">
									<label for="manOrdMod_Linea">Linea</label>
									<input readonly type="text" class="form-control form-control-sm dati-report" id="manOrdMod_Linea"
										name="manOrdMod_Linea">
								</div>
							</div>
							<div class="col-12">
								<div class="form-group">
									<label for="manOrdMod_Risorse">Macchine</label>
									<input readonly type="text" class="form-control form-control-sm dati-report" id="manOrdMod_Risorse"
										name="manOrdMod_Risorse">
								</div>
							</div>
							<div class="col-12">
								<div class="form-check">
									<input disabled id="manOrdMod_LineaBloccata" type="checkbox">
									<label for="manOrdMod_LineaBloccata" style="font-weight: normal;">Considera linea NON
										DISPONIBILE</label>
								</div>
							</div>

							<div class="col-12">
								<hr>
							</div>

							<div class="col-6">
								<div class="form-group">
									<label for="manOrdMod_DataInizioPrevista">Inizio previsto il </label>
									<input type="datetime-local" class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="manOrdMod_DataInizioPrevista" id="manOrdMod_DataInizioPrevista">
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="manOrdMod_DataFinePrevista">Termine previsto il</label>
									<input type="datetime-local" class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="manOrdMod_DataFinePrevista" id="manOrdMod_DataFinePrevista">
								</div>
							</div>
							<div class="col-12">
								<div class="form-group">
									<label for="manOrdMod_DescrizioneIntervento">Descrizione intervento</label>
									<textarea class="form-control obbligatorio" name="manOrdMod_DescrizioneIntervento"
										id="manOrdMod_DescrizioneIntervento" rows="3" autocomplete="off"></textarea>
								</div>
							</div>
						</div>

						<input type="hidden" id="manOrdMod_IdRiga" name="manOrdMod_IdRiga" value="">

					</form>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-success" id="salva-mod-man-ord">Salva</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
				</div>

			</div>
		</div>
	</div>



	<!-- Popup modale di RISOLUZIONE MANUTENZIONE ORDINARIA -->
	<div class="modal fade" id="modal-risolvi-man-ordinaria" tabindex="-1" role="dialog"
		aria-labelledby="modal-risolvi-man-ordinaria-label" aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modal-risolvi-man-ordinaria-label">MANUTENZIONE PROGRAMMATA - RAPPORTO DI LAVORO
					</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form class="forms-sample" id="form-risolvi-man-ord">

						<div class="row">

							<div class="col-12">
								<div class="form-group">
									<label for="manOrdRis_Risorse">Macchina</label>
									<input readonly type="text" class="form-control form-control-sm dati-popup-modifica"
										name="manOrdRis_Risorse" id="manOrdRis_Risorse">
								</div>
							</div>

							<div class="col-12">
								<div class="form-group">
									<label for="manOrdRis_DescrizioneIntervento">Manutenzione richiesta</label>
									<input readonly type="text" class="form-control form-control-sm dati-popup-modifica"
										name="manOrdRis_DescrizioneIntervento" id="manOrdRis_DescrizioneIntervento">
								</div>
							</div>


							<div class="col-6">
								<div class="form-group">
									<label for="manOrdRis_DataInizioPrevista">Inizio previsto il</label>
									<input readonly type="datetime-local" class="form-control form-control-sm dati-popup-modifica"
										name="manOrdRis_DataInizioPrevista" id="manOrdRis_DataInizioPrevista">
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="manOrdRis_DataFinePrevista">Termine previsto</label>
									<input readonly type="datetime-local" class="form-control form-control-sm dati-popup-modifica"
										name="manOrdRis_DataFinePrevista" id="manOrdRis_DataFinePrevista">
								</div>
							</div>

							<div class="col-12">
								<hr>
							</div>

							<div class="col-6">
								<div class="form-group">
									<label for="manOrdRis_DataInterventoInizio">Inizio intervento</label>
									<input type="datetime-local" class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="manOrdRis_DataInterventoInizio" id="manOrdRis_DataInterventoInizio">
								</div>
							</div>

							<div class="col-6">
								<div class="form-group">
									<label for="manOrdRis_DataInterventoFine">Termine intervento</label>
									<input type="datetime-local" class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="manOrdRis_DataInterventoFine" id="manOrdRis_DataInterventoFine">
								</div>
							</div>
							<div class="col-12">
								<div class="form-group">
									<label for="manOrdRis_NoteIntervento">Descrizione intervento</label>
									<textarea class="form-control obbligatorio" name="manOrdRis_NoteIntervento"
										id="manOrdRis_NoteIntervento" rows="3" autocomplete="off"></textarea>
								</div>
							</div>

						</div>

						<input type="hidden" id="manOrdRis_IdRiga" name="manOrdRis_IdRiga" value="">
						<input type="hidden" id="manOrdRis_IdRisorsa" name="manOrdRis_IdRisorsa" value="">

					</form>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-success" id="salva-rapporto-man-ord">Salva</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
				</div>

			</div>
		</div>
	</div>



	<!-- Popup modale di VISUALIZZAZIONE RAPPORTO DI LAVORO (MANUTENZIONI STRAORDINARIE) -->
	<div class="modal fade" id="modal-vedi-rapporto-man-ord" tabindex="-1" role="dialog"
		aria-labelledby="modal-vedi-rapporto-man-ord-label" aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modal-vedi-rapporto-man-ord-label">MANUTENZIONE PROGRAMMATA - RAPPORTO DI LAVORO
					</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form class="forms-sample" id="form-vedi-rapporto-man-ord">

						<div class="row">

							<div class="col-12">
								<div class="form-group">
									<label for="manOrdVis_Risorse">Macchina</label>
									<input readonly type="text" class="form-control form-control-sm dati-popup-modifica"
										name="manOrdVis_Risorse" id="manOrdVis_Risorse">
								</div>
							</div>

							<div class="col-12">
								<div class="form-group">
									<label for="manOrdVis_DescrizioneIntervento">Manutenzione richiesta</label>
									<input readonly type="text" class="form-control form-control-sm dati-popup-modifica"
										name="manOrdVis_DescrizioneIntervento" id="manOrdVis_DescrizioneIntervento">
								</div>
							</div>

							<div class="col-6">
								<div class="form-group">
									<label for="manOrdVis_DataInizioPrevista">Inizio previsto il </label>
									<input readonly type="datetime-local" class="form-control form-control-sm dati-popup-modifica"
										name="manOrdVis_DataInizioPrevista" id="manOrdVis_DataInizioPrevista">
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="ac_ManOrd_DataFinePrevista">Termine previsto</label>
									<input readonly type="datetime-local" class="form-control form-control-sm dati-popup-modifica"
										name="manOrdVis_DataFinePrevista" id="manOrdVis_DataFinePrevista">
								</div>
							</div>

							<div class="col-12">
								<hr>
							</div>

							<div class="col-6">
								<div class="form-group">
									<label for="manOrdVis_DataInizioIntervento">Inizio intervento</label>
									<input readonly type="datetime-local"
										class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="manOrdVis_DataInizioIntervento" id="manOrdVis_DataInizioIntervento">
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="manOrdVis_DataFineIntervento">Termine intervento</label>
									<input readonly type="datetime-local"
										class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="manOrdVis_DataFineIntervento" id="manOrdVis_DataFineIntervento">
								</div>
							</div>
							<div class="col-12">
								<div class="form-group">
									<label for="manOrdVis_NoteIntervento">Descrizione intervento</label>
									<textarea readonly class="form-control" name="manOrdVis_NoteIntervento" id="manOrdVis_NoteIntervento"
										rows="3"></textarea>
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="manOrdVis_PersonaleRic">Riconosciuto da</label>
									<input readonly type="text" class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="manOrdVis_PersonaleRic" id="manOrdVis_PersonaleRic">
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="manOrdVis_PersonaleRis">Risolto da</label>
									<input readonly type="text" class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="manOrdVis_PersonaleRis" id="manOrdVis_PersonaleRis">
								</div>
							</div>

						</div>


					</form>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Chiudi</button>
				</div>

			</div>
		</div>
	</div>



	<?php include("inc_js.php") ?>
	<script src="../js/manutenzione.js"></script>

</body>

</html>