<?php
	// in che pagina siamo
	$pagina = "dialogo_SCADA";

	include("../inc/conn.php");

	// Registro Data e ora di avvio procedura
	$dataInizioProcedura = date('Y-m-d');
	$oraInizioProcedura = date('H:i:s');
	$log = $dataInizioProcedura." ".$oraInizioProcedura.": PROCEDURA DI AGGIORNAMENTO DATI DA SCADA ESEGUITA CORRETTAMENTE\n";
	file_put_contents('C:\MES_log\SCADAInterface\SCADAInterface_Web_log_'.date("Ymd").'.log', $log, FILE_APPEND);


	// FUNZIONE: INIZIO LAVORO E COMMESSA
	function inizioLavoroECommessa($dataOdierna, $oraOdierna, $dataSuccessiva, $conn_mes, $idRisorsa, $idProduzione, $qtaRichiesta) {

		$conn_mes->beginTransaction();

		// flag sentinella per ritorno risultati
		$flagPrimaRisorsa = false;

		// VERIFICO SE PRODUZIONE GIA' ESEGUITA DA ALTRE RISORSE
		// interrogo la tabella 'rientro_linea_produzione' per verificare se la produzione è già stata presa in carico da altre risorse
		$sthVerificaRisorsaProduzione = $conn_mes->prepare("SELECT COUNT(rp_IdProduzione) AS Trovati
															FROM risorsa_produzione
															WHERE risorsa_produzione.rp_IdProduzione = :IdProduzione AND risorsa_produzione.rp_IdRisorsa = :IdRisorsa", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
		$sthVerificaRisorsaProduzione->execute(array(":IdProduzione" => $idProduzione, ":IdRisorsa" => $idRisorsa));
		$rigaVerificaRisorsaProduzione = $sthVerificaRisorsaProduzione->fetch(PDO::FETCH_ASSOC);

		// se sono la prima risorsa a eseguire la produzione in oggetto...
		if ($rigaVerificaRisorsaProduzione['Trovati'] == 0) {


			// INSERT IN TABELLA 'RISORSA_PRODUZIONE'
			// inserisco entry in tabella 'risorsa_produzione' con informazioni su produzione iniziata (ID risorsa, ID produzione, Data inizio, Ora inizio)
			$sqlInsertRisorsaProduzione = "INSERT INTO risorsa_produzione(rp_IdProduzione,rp_IdRisorsa,rp_DataInizio,rp_OraInizio) VALUES(:IdProduzione,:IdRisorsa,:DataInizio,:OraInizio)";

			$sthInsertRisorsaProduzione = $conn_mes->prepare($sqlInsertRisorsaProduzione);
			$sthInsertRisorsaProduzione->execute(array(
											":IdProduzione" => $idProduzione,
											":IdRisorsa" => $idRisorsa,
											":DataInizio" => $dataOdierna,
											":OraInizio" => $oraOdierna
			));



			// UPDATE TABELLA 'ORDINI_PRODUZIONE'
			// aggiorno la entry nella tabella 'ordini_produzione' impostando l'ordine in oggetto come 'OK' (in esecuzione, id = 4) dove lo stato precendente era 'ATTIVO (id = 2)
			$sqlUpdateStatoOrdine = "UPDATE ordini_produzione SET
									op_Stato = 4
									WHERE op_IdProduzione = :IdProduzione";
			$sthUpdateStatoOrdine = $conn_mes->prepare($sqlUpdateStatoOrdine);
			$sthUpdateStatoOrdine->execute(array(":IdProduzione" => $idProduzione));



			// UPDATE TABELLA 'RISORSE'
			// aggiorno la entry nella tabella 'risorse' per la risorsa in oggetto impostando come 'OK' (inserisco direttamente il testo) lo stato dell'ordine di produzione caricato
			$sqlUpdateStatoRisorsa = "UPDATE risorse SET
									ris_StatoOrdine = :StatoOrdine
									WHERE ris_IdRisorsa = :IdRisorsa";
			$sthUpdateStatoRisorsa = $conn_mes->prepare($sqlUpdateStatoRisorsa);
			$sthUpdateStatoRisorsa->execute(array(":StatoOrdine" => "OK", ":IdRisorsa" => $idRisorsa));


			// VERIFICO SE PRODUZIONE GIA' ESEGUITA DA ALTRE RISORSE
			// interrogo la tabella 'rientro_linea_produzione' per verificare se la produzione è già stata presa in carico da altre risorse
			$sthVerificaRientroLinea = $conn_mes->prepare("SELECT COUNT(rlp_IdProduzione) AS Trovati
															FROM rientro_linea_produzione
															WHERE rientro_linea_produzione.rlp_IdProduzione = :IdProduzione", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
			$sthVerificaRientroLinea->execute(array(":IdProduzione" => $idProduzione));
			$rigaVerificaRientroLinea = $sthVerificaRientroLinea->fetch(PDO::FETCH_ASSOC);

			// se sono la prima risorsa a eseguire la produzione in oggetto...
			if ($rigaVerificaRientroLinea['Trovati'] == 0) {

				// setto il flag sentinella
				$flagPrimaRisorsa = true;

				// INSERT IN TABELLA 'RIENTRO_LINEA_PRODUZIONE'
				// inserisco entry in tabella 'rientro_linea_produzione' con informazioni su produzione iniziata (ID produzione, Data inizio, Ora inizio, Quantità richiesta)
				$sqlInsertRientroLinea = "INSERT INTO rientro_linea_produzione(rlp_IdProduzione,rlp_DataInizio,rlp_OraInizio,rlp_QtaRichiesta) VALUES(:IdProduzione,:DataInizio,:OraInizio,:QtaRichiesta)";

				$sthInsertRientroLinea = $conn_mes->prepare(
					"INSERT INTO rientro_linea_produzione(rlp_IdProduzione, rlp_IdLinea, rlp_DataInizio, rlp_OraInizio, rlp_QtaRichiesta)
					SELECT (op_IdProduzione, op_LineaProduzione,:DataInizio,:OraInizio,:QtaRichiesta)
					FROM ordini_produzione WHERE op_IdProduzione = :IdProduzione"
				);
				$sthInsertRientroLinea->execute(array(
										":IdProduzione" => $idProduzione,
										":DataInizio" => $dataOdierna,
										":OraInizio" => $oraOdierna,
										":QtaRichiesta" => (float)$qtaRichiesta
				));

			}

			// SINTESI ESITO OPERAZIONI
			// in base ai valori ritornati dall'esecuzione delle query, eseguo commit/rollout della transazione SQL
			if (($sthInsertRisorsaProduzione && $sthUpdateStatoOrdine && $sthUpdateStatoRisorsa && !$flagPrimaRisorsa ) || ($sthInsertRisorsaProduzione && $sthUpdateStatoOrdine && $sthUpdateStatoRisorsa && $flagPrimaRisorsa && $sthInsertRientroLinea)) {
				$conn_mes->commit();
				$log = $dataOdierna." ".$oraOdierna.": ".$idRisorsa." - ESEGUITO START NUOVO LAVORO\n";
			}
			else {
				$conn_mes->rollBack();
				$log = $dataOdierna." ".$oraOdierna.": ".$idRisorsa." - ERRORE! START LAVORO NON ANDATO A BUON FINE\n";
			}
			file_put_contents('C:\MES_log\SCADAInterface\SCADAInterface_log_'.date("Ymd").'.log', $log, FILE_APPEND);
		}
		else {
			$log = $dataOdierna." ".$oraOdierna.": ".$idRisorsa." - ERRORE! LAVORO GIA' AVVIATO\n";
			file_put_contents('C:\MES_log\SCADAInterface\SCADAInterface_log_'.date("Ymd").'.log', $log, FILE_APPEND);
		}
	}



	// FUNZIONE: TERMINAZIONE LAVORO RISORSA
	function terminazioneLavoroRisorsa ($dataOdierna, $oraOdierna, $dataSuccessiva, $conn_mes, $idRisorsa, $idProduzione) {

		$sentinellaLavoroIniziato = 0;

		// STEP 0: recupero valore velocità teorica di LINEA e di RISORSA (è il medesimo)
		$sthRecuperaVelocitaTeoricaLinea = $conn_mes->prepare("SELECT VT.vel_VelocitaTeoricaLinea, VT.vel_IdProdotto, ODP.op_LineaProduzione, ODP.op_Prodotto
																FROM ordini_produzione AS ODP
																LEFT JOIN velocita_teoriche AS VT ON ODP.op_Prodotto = VT.vel_IdProdotto AND ODP.op_LineaProduzione = VT.vel_IdLineaProduzione
																WHERE ODP.op_IdProduzione = :IdProduzione", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
		$sthRecuperaVelocitaTeoricaLinea->execute(array(":IdProduzione" => $idProduzione));
		$rigaVelocitaTeoricaLinea = $sthRecuperaVelocitaTeoricaLinea->fetch(PDO::FETCH_ASSOC);

		// Calcolo quindi il T. TEORICO PEZZO (in secondi)
		$tempoTeoricoPezzoLinea_pzh = isset($rigaVelocitaTeoricaLinea["vel_VelocitaTeoricaLinea"]) ? floatval($rigaVelocitaTeoricaLinea["vel_VelocitaTeoricaLinea"]) : 1;
		$tempoTeoricoPezzoLinea_sec = floatval($tempoTeoricoPezzoLinea_pzh / 3600);
		$idProdotto = $rigaVelocitaTeoricaLinea["op_Prodotto"];





		// STEP 1: verifico se risorsa ha effettivamente iniziato il lavoro con l'ID in oggetto

		// Estraggo dalla tabella 'risorsa_produzione' la entry relativa alla produzione e alla risorsa in oggetto
		$sthDatiRisorsaProduzione = $conn_mes->prepare("SELECT RP.rp_QtaProdotta, RP.rp_QtaScarti, RP.rp_QtaConforme, RP.rp_DataInizio, RP.rp_OraInizio, R.ris_TTeoricoAttrezzaggio, R.ris_FlagUltima
														FROM risorsa_produzione AS RP
														LEFT JOIN risorse AS R ON RP.rp_IdRisorsa = R.ris_IdRisorsa
														WHERE RP.rp_IdProduzione = :IdOrdineProduzione AND RP.rp_DataFine IS NULL
														AND RP.rp_IdRisorsa = :IdRisorsa", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
		$sthDatiRisorsaProduzione->execute(array(":IdOrdineProduzione" => $idProduzione, ":IdRisorsa" => $idRisorsa));



		// DIRAMAZIONE TERMINAZIONE COMMESSA: se ho trovato entry in 'risorsa_produzione' => Risorsa ha già iniziato il lavoro in oggetto, seguo percorso canonico
		if ($sthDatiRisorsaProduzione->rowCount() > 0) {


			// Segnalazione ausiliaria
			$sentinellaLavoroIniziato = 1;

			$rigaDatiRisorsaProduzione = $sthDatiRisorsaProduzione->fetch(PDO::FETCH_ASSOC);



			// STEP 2:predispongo |QTA TOTALE, QTA SCARTI, QTA CONFORMI|

			// Imposto le variabili relative alle qta pezzi (TOTALI, SCARTI e OK) sulla base di quanto inserito dall'operatore nel popup
			if(isset($_REQUEST["numeroPezziProdotti"])) {
				$numPezziTotaliRisorsa = intval($_REQUEST["numeroPezziProdotti"]);
			}
			else {
				$numPezziTotaliRisorsa = intval($rigaDatiRisorsaProduzione['rp_QtaProdotta']);
			}

			if(isset($_REQUEST["numeroScarti"])) {
				$numPezziScartatiRisorsa = intval($_REQUEST["numeroScarti"]);
			}
			else {
				$numPezziScartatiRisorsa = intval($rigaDatiRisorsaProduzione['rp_QtaScarti']);
			}

			if(isset($_REQUEST["numeroConformi"])) {
				$numPezziConformiRisorsa = intval($_REQUEST["numeroConformi"]);
			}
			else {
				$numPezziConformiRisorsa = intval($rigaDatiRisorsaProduzione['rp_QtaConforme']);
			}



			// STEP 3: calcolo |T. TOTALE LAVORO RISORSA|

			// Eseguo differenza tra la marca oraria attuale e quella di inizio produzione, per la produzione in oggetto
			$dataOraInizioRisorsa = new DateTime($rigaDatiRisorsaProduzione['rp_DataInizio']." ".$rigaDatiRisorsaProduzione['rp_OraInizio']);
			$dataOraFineRisorsa = new DateTime($dataOdierna." ".$oraOdierna);
			$tempoTotaleRisorsa = $dataOraFineRisorsa->diff($dataOraInizioRisorsa);

			// Formatto opportunamente la variabile di tipo 'DateInterval' ottenuta, estraendo il corrispondente in secondi
			$tempoTotaleRisorsa_sec =  intval(($tempoTotaleRisorsa->days * 3600 * 24) + ($tempoTotaleRisorsa->h * 3600) + ($tempoTotaleRisorsa->i * 60) + $tempoTotaleRisorsa->s);
			$tempoTotaleRisorsa_min = round(floatval($tempoTotaleRisorsa_sec / 60), 0);
			$tempoTotaleRisorsa_h = floatval($tempoTotaleRisorsa_min / 60);



			// STEP 4: aggiorno tabella 'RISORSE'

			// - imposto ordine come 'CHIUSO' e resetto a null campi legati (inserisco direttamente il testo);
			// - sommo il T. totale dell'ordine in oggetto, al totale delle ore di funzionamento;
			// - sottraggo il T. totale dell'ordine in oggetto, al contatore delle ore mancanti alla prossima manutenzione
			$sqlUpdateStatoRisorsa = "UPDATE risorse SET
										ris_IdProduzione = 'ND',
										ris_RiepilogoOrdineRisorsa = 'ND',
										ris_IdRicetta = NULL,
										ris_DescrizioneRicetta = NULL,
										ris_OrdineCaricato_Scada = NULL,
										ris_StatoOrdine = 'ND',
										ris_StatoRisorsa = 'ND',
										ris_OreFunzTotali = ris_OreFunzTotali + round(:OreLavorate,0),
										ris_OreFunz_NextMan = ris_OreFunz_NextMan - round(:OreLavorate,0)
										WHERE ris_IdRisorsa = :IdRisorsa";

			$sthUpdateStatoRisorsa = $conn_mes->prepare($sqlUpdateStatoRisorsa);
			$sthUpdateStatoRisorsa->execute(array(":IdRisorsa" => $idRisorsa, ":OreLavorate" => $tempoTotaleRisorsa_h));



			// STEP 5: predispongo |T. TEORICO ATTREZZAGGIO RISORSA|
			$tempoTeoricoAttrezzaggioRisorsa_min =  $rigaDatiRisorsaProduzione['ris_TTeoricoAttrezzaggio'];
			$tempoTeoricoAttrezzaggioRisorsa_sec =  ($tempoTeoricoAttrezzaggioRisorsa_min * 60);

			// calcolo |T. TOTALE LAVORO RISORSA - T. ATTREZZAGGIO TEORICO|
			//$tempoTotaleRisorsaMenoAttrezzaggioTeorico_min = $tempoTotaleRisorsa_min - $tempoTeoricoAttrezzaggioRisorsa_min;
			//$tempoTotaleRisorsaMenoAttrezzaggioTeorico_sec = $tempoTotaleRisorsa_sec - $tempoTeoricoAttrezzaggioRisorsa_sec;



			// STEP 6: calcolo |DOWNTIME DI RISORSA|

			// Predispongo variabili di tipo 'Datetime' necessarie per eseguire i calcoli
			$dtDowntimeRisorsa = new DateTime();
			$dtAttrezzaggioRisorsa = new DateTime();
			$dtFermoRisorsa = new DateTime();
			$dtDurataEventiBloccantiRisorsa = new DateTime();
			$dtDurataPausaPrevistaRisorsa = new DateTime();
			$dt_diff = new DateTime();

			// Estraggo dalla tabella 'risorsa_downtime' l'elenco dei periodi di downtime per la risorsa e la produzione in oggetto
			$sthElencoDowntime = $conn_mes->prepare("SELECT RDT.rdt_IdRisorsa, RDT.rdt_DataInizio, RDT.rdt_OraInizio, RDT.rdt_DataFine, RDT.rdt_OraFine
													FROM risorsa_downtime AS RDT
													WHERE RDT.rdt_IdProduzione = :IdOrdineProduzione", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
			$sthElencoDowntime->execute(array(":IdOrdineProduzione" => $idProduzione));

			// Se ho trovato periodi di downtime
			if ($sthElencoDowntime->rowCount() > 0) {

				$periodiDowntime = $sthElencoDowntime->fetchAll(PDO::FETCH_ASSOC);

				// Scorro il recordset dei casi bloccanti trovati
				foreach($periodiDowntime as $periodoDowntime) {

					// Eseguo differenza tra la marca oraria finale e iniziale  del caso in esame per ottenere la durata del singolo caso
					$dataOraInizioDt = new DateTime($periodoDowntime['rdt_DataInizio']." ".$periodoDowntime['rdt_OraInizio']);
					$dataOraFineDt = new DateTime($periodoDowntime['rdt_DataFine']." ".$periodoDowntime['rdt_OraFine']);
					$intervalloDateDt = $dataOraFineDt->diff($dataOraInizioDt);

					// Distinguo tra i casi bloccanti relativi alla risorsa in oggetto e sommo le durate calcolate riferite alla sola risorsa
					if (trim($periodoDowntime['rdt_IdRisorsa']) == trim($idRisorsa)) {
						$dtDowntimeRisorsa->add($intervalloDateDt);
					}
				}

				// Calcolo totale Downtime di risorsa (nota tecnica: durata totale = (data attuale + somma durate calcolate) - data attuale)
				// Estraggo il valore in secondi e in minuti
				$totaleDowntimeRisorsa = $dtDowntimeRisorsa->diff($dt_diff);
				$downtimeRisorsa_sec =  intval(($totaleDowntimeRisorsa->days * 3600 * 24) + ($totaleDowntimeRisorsa->h * 3600) + ($totaleDowntimeRisorsa->i * 60) + $totaleDowntimeRisorsa->s);
				$downtimeRisorsa_min = round(floatval($downtimeRisorsa_sec / 60), 0);

			}
			else {
				$downtimeRisorsa_sec = 0;
				$downtimeRisorsa_min = 0;

			}



			// STEP 7: calcolo |T. ATTREZZAGGIO RISORSA| - |T. FERMO RISORSA| - |DURATA TOTALE CASI BLOCCANTI RISORSA|

			// Estraggo dalla tabella 'attivita_casi' l'elenco dei casi bloccanti presenti per la produzione in oggetto 	(*** N.B CONVENZIONE: id caso non bloccante = 'OK'. Da modificare eventualmente in sede di installazione ***)
			$sthElencoCasiBloccanti = $conn_mes->prepare("SELECT AC.ac_IdRisorsa, AC.ac_DataInizio, AC.ac_DataFine, AC.ac_OraInizio, AC.ac_OraFine, AC.ac_IdEvento, C.cas_Tipo
														FROM attivita_casi AS AC
														LEFT JOIN Casi AS C ON AC.ac_IdEvento = C.cas_IdEvento AND AC.ac_IdRisorsa = C.cas_IdRisorsa
														WHERE C.cas_Tipo != 'OK'
														AND AC.ac_IdProduzione = :IdOrdineProduzione", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
			$sthElencoCasiBloccanti->execute(array(":IdOrdineProduzione" => $idProduzione));

			// Se ho trovati casi bloccanti, li scorro uno alla volta e, tipo per tipo, calcolo una somma delle durate
			if ($sthElencoCasiBloccanti->rowCount() > 0) {

				$casiBloccanti = $sthElencoCasiBloccanti->fetchAll(PDO::FETCH_ASSOC);

				foreach($casiBloccanti as $caso) {

					// Eseguo differenza tra la marca oraria finale e iniziale  del caso in esame per ottenere la durata del singolo caso
					$dataOraInizio = new DateTime($caso['ac_DataInizio']." ".$caso['ac_OraInizio']);
					$dataOraFine = new DateTime($caso['ac_DataFine']." ".$caso['ac_OraFine']);
					$intervalloDate = $dataOraFine->diff($dataOraInizio);

					// Distinguo tra i casi bloccanti relativi alla risorsa in oggetto
					if (trim($caso['ac_IdRisorsa']) == trim($idRisorsa)) {

						// Distinguo i casi bloccanti in base al tipo ("AT" = attrezzaggio, "KK" = fermo macchina generico, "KO" = avaria, "KJ" = pausa prevista) per conteggiarne i tempi separatamente dai downtime generici  	(*** Da modificare eventualmente in sede di installazione ***)
						if ((trim($caso['cas_Tipo']) == "AT") || (trim($caso['ac_IdEvento']) == "at" && trim($caso['ac_IdEvento']) == "at_scada")) {
							$dtAttrezzaggioRisorsa->add($intervalloDate);
						}
						else if (trim($caso['cas_Tipo']) == "KK") {
							$dtFermoRisorsa->add($intervalloDate);
						}
						else if (trim($caso['cas_Tipo']) == "KO"){
							$dtDurataEventiBloccantiRisorsa->add($intervalloDate);
						}
						else if (trim($caso['cas_Tipo']) == "KJ"){
							$dtDurataPausaPrevistaRisorsa->add($intervalloDate);
						}
					}
				}

				// Calcolo totale tempi di ATTREZZAGGIO, FERMO MACCHINA, PAUSA PREVISTA e TOTALE DURATA EVENTI BLOCCANTI (nota tecnica: durata totale = (data attuale + somma durate calcolate) - data attuale)
				// Estraggo i relativi valori in secondi e in minuti
				$totaleTempoAttrezzaggioRisorsa = $dtAttrezzaggioRisorsa->diff($dt_diff);
				$totaleTFermoRisorsa = $dtFermoRisorsa->diff($dt_diff);
				$totaleDurataEventiBloccantiRisorsa = $dtDurataEventiBloccantiRisorsa->diff($dt_diff);
				$totaeTPausaPrevistaRisorsa =  $dtDurataPausaPrevistaRisorsa->diff($dt_diff);

				$tempoAttrezzaggioRisorsa_sec = intval(($totaleTempoAttrezzaggioRisorsa->days * 3600 * 24) + ($totaleTempoAttrezzaggioRisorsa->h * 3600) + ($totaleTempoAttrezzaggioRisorsa->i * 60) + $totaleTempoAttrezzaggioRisorsa->s);
				$tempoFermoRisorsa_sec = intval(($totaleTFermoRisorsa->days * 3600 * 24) + ($totaleTFermoRisorsa->h * 3600) + ($totaleTFermoRisorsa->i * 60) + $totaleTFermoRisorsa->s);
				$durataEventiBloccantiRisorsa_sec = intval(($totaleDurataEventiBloccantiRisorsa->days * 3600 * 24) + ($totaleDurataEventiBloccantiRisorsa->h * 3600) + ($totaleDurataEventiBloccantiRisorsa->i * 60) + $totaleDurataEventiBloccantiRisorsa->s);
				$tempoPausaPrevistaRisorsa_sec = intval(($totaeTPausaPrevistaRisorsa->days * 3600 * 24) + ($totaeTPausaPrevistaRisorsa->h * 3600) + ($totaeTPausaPrevistaRisorsa->i * 60) + $totaeTPausaPrevistaRisorsa->s);

				$tempoAttrezzaggioRisorsa_min = round(floatval($tempoAttrezzaggioRisorsa_sec / 60), 0);
				$tempoFermoRisorsa_min = round(floatval($tempoFermoRisorsa_sec / 60), 0);
				$durataEventiBloccantiRisorsa_min = round(floatval($durataEventiBloccantiRisorsa_sec / 60), 0);
				$tempoPausaPrevistaRisorsa_min = round(floatval($tempoPausaPrevistaRisorsa_sec / 60), 0);

			}
			else { // se non ho casi bloccanti, imposto le variabili di conteggio a 0

				$tempoAttrezzaggioRisorsa_sec = 0;
				$tempoFermoRisorsa_sec = 0;
				$durataEventiBloccantiRisorsa_sec = 0;
				$tempoPausaPrevistaRisorsa_sec = 0;

				$tempoAttrezzaggioRisorsa_min = 0;
				$tempoFermoRisorsa_min = 0;
				$durataEventiBloccantiRisorsa_min = 0;
				$tempoPausaPrevistaRisorsa_min = 0;
			}



			// STEP 8: calcolo |OEE|, |D|, |E|, |Q|, |VELOCITA'|  per la RISORSA

			// Calcolo |VELOCITA' RISORSA|
			$tempoTotaleRisorsa_sec_ore = floatval($tempoTotaleRisorsa_sec / 3600);
			$velocitaRisorsa = round(intval($numPezziTotaliRisorsa / ($tempoTotaleRisorsa_sec_ore != 0 ? $tempoTotaleRisorsa_sec_ore : 1)), 2);

			// Calcolo |OEE RISORSA| (N.B: formula modificata senza utilizzo delle 3 componenti: n° pezzi conformi / n° pezzi teorici nel tempo totale)
			$numPezziTeoriciRisorsa = intval($tempoTotaleRisorsa_sec * $tempoTeoricoPezzoLinea_sec);
			$OEERisorsa = round(floatval($numPezziConformiRisorsa / ($numPezziTeoriciRisorsa != 0 ? $numPezziTeoriciRisorsa : 1)), 4);
			$OEERisorsa_perc = floatval($OEERisorsa * 100);

			// Fattore |D RISORSA|
			$uptimeRisorsa_sec = round(floatval($tempoTotaleRisorsa_sec - $downtimeRisorsa_sec), 2);
			$sommaUptimeDowntimeRisorsa_sec = $tempoTotaleRisorsa_sec;
			$fattoreDRisorsa = round(floatval($uptimeRisorsa_sec / ($sommaUptimeDowntimeRisorsa_sec != 0 ? $sommaUptimeDowntimeRisorsa_sec : 1)), 4);
			$fattoreDRisorsa_perc = floatval($fattoreDRisorsa * 100);

			// Fattore |E RISORSA|
			$numPezziTeoriciRisorsaUptime = intval($uptimeRisorsa_sec * $tempoTeoricoPezzoLinea_sec);
			$fattoreERisorsa = round(floatval($numPezziTotaliRisorsa / ($numPezziTeoriciRisorsaUptime != 0 ? $numPezziTeoriciRisorsaUptime : 1)), 4);
			$fattoreERisorsa_perc = floatval($fattoreERisorsa * 100);

			// Fattore |Q RISORSA|
			if($fattoreDRisorsa != 0 && $fattoreERisorsa != 0) {
				$prodTempFattoriRisorsa = floatval($fattoreDRisorsa * $fattoreERisorsa);
			}
			else {
				$prodTempFattoriRisorsa = 1;
			}
			$fattoreQRisorsa = round(floatval($OEERisorsa / ($prodTempFattoriRisorsa != 0 ? $prodTempFattoriRisorsa : 1)), 4);
			$fattoreQRisorsa_perc = floatval($fattoreQRisorsa * 100);





			// STEP 9  => AGGIORNO TABELLA 'RISORSA_PRODUZIONE'   //*** MODIFICA PER TEST: AGGIUNTI DUE PARAMETRI: QTA PRODOTTA e QTA SCARTI ***
			// completo la entry in tabella 'risorsa_produzione' con le informazioni di chiusura per la produzione e la risorsa in oggetto (Data fine, Ora fine, TTotale, Downtime, T Attrezzaggio, Fattore D, Fattore Q, Fattore E, OEE)
			$sqlUpdateRisorsaProduzione = "UPDATE risorsa_produzione SET
											rp_DataFine = :DataFine,
											rp_OraFine = :OraFine,
											rp_TTotale = :TempoTotaleRisorsa,
											rp_Downtime = :DowntimeRisorsa,
											rp_Attrezzaggio = :TempoAttrezzaggioRisorsa,
											rp_TFermo = :TempoFermoRisorsa,
											rp_TPausaPrevista = :TempoPausaPrevista,
											rp_DurataEventiBloccanti = :DurataEventiBloccanti,
											rp_QtaProdotta = :QtaProdotta,
											rp_QtaConforme = :QtaConforme,
											rp_QtaScarti = :QtaScarti,
											rp_D = :FattoreDRisorsa,
											rp_E = :FattoreERisorsa,
											rp_Q = :FattoreQRisorsa,
											rp_OEE = :OEERisorsa,
											rp_VelocitaRisorsa = :VelocitaRisorsa,
											rp_NoteFine = :NoteFine
											WHERE rp_IdProduzione = :IdProduzione AND rp_IdRisorsa = :IdRisorsa";

			$sthUpdateRisorsaProduzione = $conn_mes->prepare($sqlUpdateRisorsaProduzione);
			$sthUpdateRisorsaProduzione->execute(array(":DataFine" => $dataOdierna,
													":OraFine" => $oraOdierna,
													":TempoTotaleRisorsa" => (float)$tempoTotaleRisorsa_min,
													":DowntimeRisorsa" => (float)$downtimeRisorsa_min,
													":TempoAttrezzaggioRisorsa" => (float)$tempoAttrezzaggioRisorsa_min,
													":TempoFermoRisorsa" => (float)$tempoFermoRisorsa_min,
													":TempoPausaPrevista" => (float)$tempoPausaPrevistaRisorsa_min,
													":DurataEventiBloccanti" => (float)$durataEventiBloccantiRisorsa_min,
													":QtaProdotta" => (float) $numPezziTotaliRisorsa,
													":QtaConforme" => (float) $numPezziConformiRisorsa,
													":QtaScarti" => (float) $numPezziScartatiRisorsa,
													":FattoreDRisorsa" => ($fattoreDRisorsa_perc > 100 ? 100 : $fattoreDRisorsa_perc),
													":FattoreERisorsa" => ($fattoreERisorsa_perc > 100 ? 100 : $fattoreERisorsa_perc),
													":FattoreQRisorsa" => ($fattoreQRisorsa_perc > 100 ? 100 : $fattoreQRisorsa_perc),
													":OEERisorsa" => ($OEERisorsa_perc > 100 ? 100 : $OEERisorsa_perc),
													":VelocitaRisorsa" => (float)$velocitaRisorsa,
													":NoteFine" => (isset($_REQUEST["noteChiusura"])? $_REQUEST["noteChiusura"] : ""),
													":IdProduzione" => $idProduzione,
													":IdRisorsa" => $idRisorsa
			));



			// STEP 10 => VERIFICA MANUTENZIONE NECESSARIA

			$sthOreManRisorsa = $conn_mes->prepare("SELECT R.ris_OreFunz_NextMan, R.ris_OreFunz_FreqMan
													FROM risorse AS R
													WHERE R.ris_IdRisorsa = :IdRisorsa", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
			$sthOreManRisorsa->execute(array(":IdRisorsa" => $idRisorsa));
			$rigaOreManRisorsa = $sthOreManRisorsa->fetch(PDO::FETCH_ASSOC);

			$manutenzionePeriodica = false;
			if (($rigaOreManRisorsa['ris_OreFunz_FreqMan'] != 0) && ($rigaOreManRisorsa['ris_OreFunz_NextMan'] <= 0)) {

				$manutenzionePeriodica = true;

				// Recupero progressivo attuale 'manutenzioni'
				$sthProgressivoMan = $conn_mes->prepare("SELECT MAX(ac_ManOrd_Progressivo) AS IndiceProgressivoAttuale
											FROM attivita_casi AS AC", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
				$sthProgressivoMan->execute();
				$rigaProgressivoMan = $sthProgressivoMan->fetch(PDO::FETCH_ASSOC);
				$progressivoManAggiornato = intval($rigaProgressivoMan['IndiceProgressivoAttuale']) + 1;


				// Credo evento ed eseguo INSERT in tabella 'attivita_casi'
				$sqlInsertEvento = "INSERT INTO attivita_casi(ac_IdRisorsa,ac_ManOrd,ac_ManOrd_Progressivo,ac_ManOrd_DataInizioPrevista,ac_ManOrd_OraInizioPrevista,ac_ManOrd_DataFinePrevista,ac_ManOrd_OraFinePrevista,ac_ManOrd_Descrizione,ac_ManOrd_BloccoLinea) VALUES(:IdRisorsa,:Man,:ManProgressivo,:ManDataInizio,:ManOraInizio,:ManDataFine,:ManOraFine,:ManDescrizione,:ManBloccoLinea)";

				$sthInsertEvento = $conn_mes->prepare($sqlInsertEvento);
				$sthInsertEvento->execute(array(
					":IdRisorsa" => $idRisorsa,
					":Man" => 1,
					":ManProgressivo" => $progressivoManAggiornato,
					":ManDataInizio" => $dataOdierna,
					":ManOraInizio" => $oraOdierna,
					":ManDataFine" => $dataSuccessiva,
					":ManOraFine" => $oraOdierna,
					":ManDescrizione" => "MANUTENZIONE PERIODICA RICHIESTA",
					":ManBloccoLinea" => 1
				));


				// Aggiorno tabella 'risorse' e resetto contatore delle ore mancanti alla prossima manutenzione
				$sqlUpdateOreMan = "UPDATE risorse SET
										ris_OreFunz_NextMan = ris_OreFunz_FreqMan
										WHERE ris_IdRisorsa = :IdRisorsa";
				$sthUpdateOreMan = $conn_mes->prepare($sqlUpdateOreMan);
				$sthUpdateOreMan->execute(array(":IdRisorsa" => $idRisorsa));

			}



			// STEP 11 => AGGIORNO TABELLA 'EFFICIENZA_RISORSA'

			// verifico se esiste già entry per l'ID produzione considerato
			$sthVerificaEfficienzaRisorse = $conn_mes->prepare("SELECT COUNT(*) AS ERPresente
																FROM efficienza_risorse
																WHERE efficienza_risorse.er_IdRisorsa = :IdRisorsa AND efficienza_risorse.er_IdProdotto = :IdProdotto", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
			$sthVerificaEfficienzaRisorse->execute(array(":IdRisorsa" => $idRisorsa, ":IdProdotto" => $idProdotto));
			$rigaVerificaEfficienzaRisorse = $sthVerificaEfficienzaRisorse->fetch(PDO::FETCH_ASSOC);

			if ($rigaVerificaEfficienzaRisorse['ERPresente'] == 0) {

				$sqlUpdateEfficienzaRisorse = "INSERT INTO efficienza_risorse (er_IdRisorsa, er_IdProdotto, er_DMedio, er_EMedio, er_QMedio, er_OEEMedio, er_OEEMinimo, er_OEEMigliore, er_IdProduzioneMinimo, er_IdProduzioneMigliore)
													VALUES (:IdRisorsa, :IdProdotto, :DMedioRisorsa, :EMedioRisorsa, :QMedioRisorsa, :OEEMedioRisorsa, :OEEMedioRisorsa, :OEEMedioRisorsa, :IdProduzione, :IdProduzione2)";

				$sthUpdateEfficienzaRisorse = $conn_mes->prepare($sqlUpdateEfficienzaRisorse);
				$sthUpdateEfficienzaRisorse->execute(array(":IdRisorsa" => $idRisorsa,
															":IdProdotto" => $idProdotto,
															":DMedioRisorsa" => ($fattoreDRisorsa_perc > 100 ? 100 : $fattoreDRisorsa_perc),
															":EMedioRisorsa" => ($fattoreERisorsa_perc > 100 ? 100 : $fattoreERisorsa_perc),
															":QMedioRisorsa" => ($fattoreQRisorsa_perc > 100 ? 100 : $fattoreQRisorsa_perc),
															":OEEMedioRisorsa" => ($OEERisorsa_perc > 100 ? 100 : $OEERisorsa_perc),
															":IdProduzione" => $idProduzione,
															":IdProduzione2" => $idProduzione,
														));
			}
			else {
				$sqlUpdateEfficienzaRisorse = "UPDATE efficienza_risorse SET
												er_DMedio = (er_DMedio + :DMedioRisorsa) / 2,
												er_EMedio = (er_EMedio + :EMedioRisorsa) / 2,
												er_QMedio = (er_QMedio + :QMedioRisorsa) / 2,
												er_OEEMedio = (er_OEEMedio + :OEEMedioRisorsa) / 2,
												er_OEEMinimo = LEAST(er_OEEMinimo, :OEEMedioRisorsa),
												er_OEEMigliore = GREATEST(er_OEEMigliore, :OEEMedioRisorsa),
												er_IdProduzioneMinimo = IF (LEAST(er_OEEMinimo, :OEEMedioRisorsa), :IdProduzione, er_IdProduzioneMinimo),
												er_IdProduzioneMigliore = IF (LEAST(er_OEEMigliore, :OEEMedioRisorsa), :IdProduzione, er_IdProduzioneMigliore)
												WHERE efficienza_risorse.er_IdRisorsa = :IdRisorsa AND efficienza_risorse.er_IdProdotto = :IdProdotto";
				$sthUpdateEfficienzaRisorse = $conn_mes->prepare($sqlUpdateEfficienzaRisorse);
				$sthUpdateEfficienzaRisorse->execute(array(":IdRisorsa" => $idRisorsa,
															":IdProdotto" => $idProdotto,
															":DMedioRisorsa" => ($fattoreDRisorsa_perc > 100 ? 100 : $fattoreDRisorsa_perc),
															":EMedioRisorsa" => ($fattoreERisorsa_perc > 100 ? 100 : $fattoreERisorsa_perc),
															":QMedioRisorsa" => ($fattoreQRisorsa_perc > 100 ? 100 : $fattoreQRisorsa_perc),
															":OEEMedioRisorsa" => ($OEERisorsa_perc > 100 ? 100 : $OEERisorsa_perc),
															":IdProduzione" => $idProduzione));
			}
		}
		// DIRAMAZIONE TERMINAZIONE COMMESSA: se non ho trovato entry in 'risorsa_produzione' => Risorsa non ha iniziato il lavoro in oggetto
		else {


			// STEP 1  => INSERISCO ENTRY IN TABELLA 'RISORSA_PRODUZIONE'
			// Inserisco una entry fittizia con valori tutti a 0 per il lavoro e la risorsa in oggetto (Sostanzialmente, come se lavoro fosse INIZIATO e FINITO nel medesimo istante)
			$sqlUpdateRisorsaProduzione = "INSERT INTO risorsa_produzione(rp_IdProduzione,rp_IdRisorsa,rp_DataInizio,rp_OraInizio,rp_DataFine,rp_OraFine,rp_TTotale,rp_Downtime,rp_Attrezzaggio,rp_TFermo,rp_TPausaPrevista,rp_DurataEventiBloccanti,rp_QtaProdotta,rp_QtaConforme,rp_QtaScarti,rp_D,rp_E,rp_Q,rp_OEE,rp_VelocitaRisorsa,rp_NoteFine) VALUES(:IdProduzione,:IdRisorsa,:DataInizio,:OraInizio,:DataFine,:OraFine,:TempoTotaleRisorsa,:DowntimeRisorsa,:TempoAttrezzaggioRisorsa,:TempoFermoRisorsa,:TempoPausaPrevista,:DurataEventiBloccanti,:QtaProdotta,:QtaConforme,:QtaScarti,:FattoreDRisorsa,:FattoreERisorsa,:FattoreQRisorsa,:OEERisorsa,:VelocitaRisorsa,:NoteFine)";

			$sthUpdateRisorsaProduzione = $conn_mes->prepare($sqlUpdateRisorsaProduzione);
			$sthUpdateRisorsaProduzione->execute(array(
													":IdProduzione" => $idProduzione,
													":IdRisorsa" => $idRisorsa,
													":DataInizio" => $dataOdierna,
													":OraInizio" => $oraOdierna,
													":DataFine" => $dataOdierna,
													":OraFine" => $oraOdierna,
													":TempoTotaleRisorsa" => 0,
													":DowntimeRisorsa" => 0,
													":TempoAttrezzaggioRisorsa" => 0,
													":TempoFermoRisorsa" => 0,
													":TempoPausaPrevista" => 0,
													":DurataEventiBloccanti" => 0,
													":QtaProdotta" => 0,
													":QtaConforme" => 0,
													":QtaScarti" => 0,
													":FattoreDRisorsa" => 0,
													":FattoreERisorsa" => 0,
													":FattoreQRisorsa" => 0,
													":OEERisorsa" => 0,
													":VelocitaRisorsa" => 0,
													":NoteFine" => "FORZATA TERMINAZIONE"
			));


			// Aggiorno tabella 'risorse'
			// nella risorsa in oggetto, pulisco i campi relativi all'ordine impostando il valore di default 'ND' se su questa era caricato l'ordine con l'ID considerato
			$sqlUpdateStatoRisorsa = "UPDATE risorse SET
										ris_IdProduzione = 'ND',
										ris_RiepilogoOrdineRisorsa = 'ND',
										ris_StatoOrdine = 'ND',
										ris_StatoRisorsa = 'ND'
										WHERE ris_IdRisorsa = :IdRisorsa AND ris_IdProduzione = :IdProduzione";

			$sthUpdateStatoRisorsa = $conn_mes->prepare($sqlUpdateStatoRisorsa);
			$sthUpdateStatoRisorsa->execute(array(":IdRisorsa" => $idRisorsa, ":IdProduzione" => $idProduzione));


			// In questo caso la manutenzione periodica non è gestita
			$manutenzionePeriodica = false;


			// STEP OPZIONALE PER GESTIONE CHIUSURA AUTOMATICA COMMESSA: VERIFICO SE PRODUZIONE DI LINEA E' COMPLETATA
			// estraggo la lista delle risorse che non hanno completato l'esecuzione dell'ordine di produzione in oggetto (verifico se nella tabella 'risorsa_produzione' è presente la data di fine)
			$sthRisorseAppese = $conn_mes->prepare("SELECT COUNT(*) AS RisorseAppese
													FROM risorse_coinvolte AS RC
													LEFT JOIN risorsa_produzione AS RP ON (RC.rc_IdRisorsa = RP.rp_IdRisorsa AND RC.rc_IdProduzione = RP.rp_IdProduzione)
													WHERE RC.rc_IdProduzione = :IdProduzione
													AND RP.rp_DataFine IS NULL
													AND RC.rc_LineaProduzione != 'lin_0X'", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
			$sthRisorseAppese->execute(array(":IdProduzione" => $idProduzione));
			$rigaRisorseAppese = $sthRisorseAppese->fetch(PDO::FETCH_ASSOC);

			// se non ho trovato risorse, quindi le altre hanno già completato, posso ritenere completa l'intera produzione di linea
			if($rigaRisorseAppese['RisorseAppese'] == 0) {
				$ultimaRisorsa = True;
			}
			else {
				$ultimaRisorsa = False;
			}

		}


		// DEFINITIVO: sintesi esito operazioni
		$conn_mes->beginTransaction();

		if (
			$sthRecuperaVelocitaTeoricaLinea && $sthDatiRisorsaProduzione &&
			(
				($sentinellaLavoroIniziato == 1 && $sthUpdateStatoRisorsa && $sthUpdateRisorsaProduzione && $sthUpdateEfficienzaRisorse && $sthElencoDowntime && $sthElencoCasiBloccanti) ||
				($sentinellaLavoroIniziato == 0 && $sthUpdateRisorsaProduzione && $sqlUpdateStatoRisorsa)
			)
			&&
			(
				($manutenzionePeriodica && $sthInsertEvento && $sthUpdateOreMan) ||
				(!$manutenzionePeriodica))
			)
			{
			$conn_mes->commit();
			$log = $dataOdierna." ".$oraOdierna.": ".$idRisorsa." - ESEGUITA TERMINAZIONE NUOVO LAVORO\n";
		}
		else {
			$conn_mes->rollBack();
			$log = $dataOdierna." ".$oraOdierna.": ".$idRisorsa." - ERRORE! TERMINAZIONE LAVORO NON ANDATA A BUON FINE\n";
		}
		file_put_contents('C:\MES_log\SCADAInterface\SCADAInterface_log_'.date("Ymd").'.log', $log, FILE_APPEND);





	}



	// FUNZIONE: TERMINAZIONE COMMESSA LINEA
	function terminazioneCommessaLinea($dataOdierna, $oraOdierna, $dataSuccessiva, $conn_mes, $idProduzione) {

		$sentinellaLavoroIniziato = 0;

		// STEP 0: recupero valore velocità teorica di LINEA e di RISORSA (è il medesimo)
		$sthRecuperaVelocitaTeoricaLinea = $conn_mes->prepare("SELECT VT.vel_VelocitaTeoricaLinea, VT.vel_IdProdotto, ODP.op_LineaProduzione, ODP.op_Prodotto
																FROM ordini_produzione AS ODP
																LEFT JOIN velocita_teoriche AS VT ON ODP.op_Prodotto = VT.vel_IdProdotto AND ODP.op_LineaProduzione = VT.vel_IdLineaProduzione
																WHERE ODP.op_IdProduzione = :IdProduzione", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
		$sthRecuperaVelocitaTeoricaLinea->execute(array(":IdProduzione" => $idProduzione));
		$rigaVelocitaTeoricaLinea = $sthRecuperaVelocitaTeoricaLinea->fetch(PDO::FETCH_ASSOC);

		// Calcolo quindi il T. TEORICO PEZZO (in secondi)
		$tempoTeoricoPezzoLinea_pzh = isset($rigaVelocitaTeoricaLinea["vel_VelocitaTeoricaLinea"]) ? floatval($rigaVelocitaTeoricaLinea["vel_VelocitaTeoricaLinea"]) : 1;
		$tempoTeoricoPezzoLinea_sec = floatval($tempoTeoricoPezzoLinea_pzh / 3600);


		// STEP 2: calcolo |T. TOTALE LAVORO LINEA|
		$sthCalcoloTempoTotaleProduzione = $conn_mes->prepare("SELECT RLP.rlp_DataInizio, RLP.rlp_OraInizio
																FROM rientro_linea_produzione AS RLP
																WHERE RLP.rlp_IdProduzione = :IdProduzione", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
		$sthCalcoloTempoTotaleProduzione->execute(array(":IdProduzione" => $idProduzione));
		$riga = $sthCalcoloTempoTotaleProduzione->fetch(PDO::FETCH_ASSOC);

		// Eseguo differenza tra la marca oraria attuale e iniziale  della produzione in oggetto
		$dataOraInizioProduzione = new DateTime($riga['rlp_DataInizio']." ".$riga['rlp_OraInizio']);
		$dataOraFineProduzione = new DateTime($dataOdierna." ".$oraOdierna);
		$tempoTotaleLinea = $dataOraFineProduzione->diff($dataOraInizioProduzione);

		// Formatto opportunamente la variabile 'DateInterval' ottenuta ed estraggo il relativo valore in secondi
		$tempoTotaleLinea_sec = intval(($tempoTotaleLinea->days * 3600 * 24) + ($tempoTotaleLinea->h * 3600) + ($tempoTotaleLinea->i * 60) + $tempoTotaleLinea->s);
		$tempoTotaleLinea_min = round(floatval($tempoTotaleLinea_sec / 60), 0);




		// STEP 3: calcolo |T. DOWNTIME LINEA|
		$dtDowntimeLinea = new DateTime();
		$dt_diff = new DateTime();

		// Estraggo dalla tabella 'risorsa_downtime' l'elenco dei periodi di downtime per la risorsa e la produzione in oggetto
		$sthElencoDowntimeLinea = $conn_mes->prepare("SELECT LDT.ldt_IdProduzione, LDT.ldt_DataInizio, LDT.ldt_OraInizio, LDT.ldt_DataFine, LDT.ldt_OraFine
												FROM linea_downtime AS LDT
												WHERE LDT.ldt_IdProduzione = :IdOrdineProduzione", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
		$sthElencoDowntimeLinea->execute(array(":IdOrdineProduzione" => $idProduzione));

		// Se ho trovato periodi di downtime
		if ($sthElencoDowntimeLinea->rowCount() > 0) {

			$periodiDowntime = $sthElencoDowntimeLinea->fetchAll(PDO::FETCH_ASSOC);

			// Scorro il recordset dei casi bloccanti trovati
			foreach($periodiDowntime as $periodoDowntime) {

				// Eseguo differenza tra la marca oraria finale e iniziale  del caso in esame per ottenere la durata del singolo caso
				$dataOraInizioDt = new DateTime($periodoDowntime['ldt_DataInizio']." ".$periodoDowntime['ldt_OraInizio']);
				$dataOraFineDt = new DateTime($periodoDowntime['ldt_DataFine']." ".$periodoDowntime['ldt_OraFine']);
				$intervalloDateDt = $dataOraFineDt->diff($dataOraInizioDt);

				$dtDowntimeLinea->add($intervalloDateDt);
			}

			// Calcolo totale Downtime di risorsa (nota tecnica: durata totale = (data attuale + somma durate calcolate) - data attuale)
			// Estraggo il valore in secondi e in minuti
			$totaleDowntimeLinea = $dtDowntimeLinea->diff($dt_diff);
			$downtimeLinea_sec =  intval(($totaleDowntimeLinea->days * 3600 * 24) + ($totaleDowntimeLinea->h * 3600) + ($totaleDowntimeLinea->i * 60) + $totaleDowntimeLinea->s);
			$downtimeLinea_min = round(floatval($downtimeLinea_sec / 60), 0);
		}
		else {
			$downtimeLinea_sec = 0;
			$downtimeLinea_min = 0;
		}



		// STEP 4: recupero informazioni relative a produzione di linea (T. TOT, DOWNTIME, T. PAUSA, T. FERMO, T. ATTREZZAGGIO, DURATA BLOCCANTI)	*** DA VERIFICARE IL CRITERIO CON CUI CALCOLARLE ***
		$sthRecuperaInformazioniRisorseLinea = $conn_mes->prepare("SELECT MAX(ris_TTeoricoAttrezzaggio) AS TTeoricoAttrezzaggioLinea,
																	MAX(rp_Attrezzaggio) AS TAttrezzaggioLinea,
																	MAX(rp_TFermo) AS TFermoLinea,
																	MAX(rp_TPausaPrevista) AS TPausaPrevistaLinea,
																	SUM(rp_DurataEventiBloccanti) AS DurataTotaleEventiBloccantiLinea
																	FROM  risorsa_produzione
																	LEFT JOIN risorse ON risorsa_produzione.rp_IdRisorsa = risorse.ris_IdRIsorsa
																	WHERE risorsa_produzione.rp_IdProduzione = :IdProduzione", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
		$sthRecuperaInformazioniRisorseLinea->execute(array(":IdProduzione" => $idProduzione));
		$rigaInformazioniRisorseLinea = $sthRecuperaInformazioniRisorseLinea->fetch(PDO::FETCH_ASSOC);


		// STEP 4BIS: recupero informazioni relative a produzione di linea (QTA TOTALE, QTA SCARTI)
		$sthConteggioPezziLinea = $conn_mes->prepare("SELECT rp_QtaProdotta AS NumPezziTotaliLinea, rp_QtaScarti AS NumPezziScartatiLinea, rp_QtaConforme AS NumPezziConformiLinea
														FROM  risorsa_produzione
														LEFT JOIN risorse ON risorsa_produzione.rp_IdRisorsa = risorse.ris_IdRIsorsa
														WHERE risorsa_produzione.rp_IdProduzione = :IdProduzione", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
		$sthConteggioPezziLinea->execute(array(":IdProduzione" => $idProduzione));
		$rigaConteggioPezziLinea = $sthConteggioPezziLinea->fetch(PDO::FETCH_ASSOC);


		// STEP 4TRIS: estraggo i valori necessari ed eseguo le eventuali conversioni

		// |T. ATTREZZAGGIO LINEA| da considerare (MAX. tra i T. di attrezzaggio delle risorse presenti in 'risorsa_produzione')
		$tempoTeoricoAttrezzaggioLinea_min = (float) $rigaInformazioniRisorseLinea['TTeoricoAttrezzaggioLinea'];
		$tempoTeoricoAttrezzaggioLinea_sec = ($rigaInformazioniRisorseLinea['TTeoricoAttrezzaggioLinea'] * 60);

		// correggo il |T. TOTALE DI LINEA| sottraendogli il T. TEORICO DI ATTREZZAGGIO RICAVATO
		//$tempoTotaleLineaMenoAttrezzaggioTeorico_min = $tempoTotaleLinea_min - $tempoTeoricoAttrezzaggioLinea_min;
		//$tempoTotaleLineaMenoAttrezzaggioTeorico_sec = $tempoTotaleLinea_sec - $tempoTeoricoAttrezzaggioLinea_sec;

		// |T. ATTREZZAGGIO LINEA|
		$tempoAttrezzaggioLinea_min = (float) $rigaInformazioniRisorseLinea['TAttrezzaggioLinea'];

		// |T. FERMO LINEA|
		$tempoFermoLinea_min = (float) $rigaInformazioniRisorseLinea['TFermoLinea'];

		// |T. PAUSA PREVISTA LINEA|
		$tempoPausaPrevistaLinea_min = (float) $rigaInformazioniRisorseLinea['TPausaPrevistaLinea'];

		// |DURATA EVENTI BLOCCANTI LINEA|
		$durataEventiBloccantiLinea_min = (float) $rigaInformazioniRisorseLinea['DurataTotaleEventiBloccantiLinea'];



		// STEP 5: predispongo |QTA TOTALE, QTA SCARTI, QTA CONFORMI| *** DA CONFIGURARE A SECONDA DEL CRITERIO SCELTO ***

		// Imposto le variabili relative alle qta pezzi (TOTALI, SCARTI) sulla base di quanto inserito dall'operatore nel popup
		if(isset($_REQUEST["numeroPezziProdotti"])) {
			$numPezziTotaliLinea = intval($_REQUEST["numeroPezziProdotti"]);
		}
		else {
			$numPezziTotaliLinea = intval($rigaConteggioPezziLinea['NumPezziTotaliLinea']);
		}

		if(isset($_REQUEST["numeroScarti"])) {
			$numPezziScartatiLinea = intval($_REQUEST["numeroScarti"]);
		}
		else {
			$numPezziScartatiLinea = intval($rigaConteggioPezziLinea['NumPezziScartatiLinea']);
		}

		if(isset($_REQUEST["numeroConformi"])) {
			$numPezziConformiLinea = intval($_REQUEST["numeroConformi"]);
		}
		else {
			$numPezziConformiLinea = intval($rigaConteggioPezziLinea['NumPezziConformiLinea']);
		}




		// STEP 6: calcolo |OEE|, |D|, |E|, |Q| e |VELOCITA'| per la LINEA

		// Calcolo |VELOCITA' LINEA|
		$uptimeLinea_ore = floatval($tempoTotaleLinea_sec / 3600);
		$velocitaLinea = round(floatval($numPezziTotaliLinea / ($uptimeLinea_ore != 0 ? $uptimeLinea_ore : 1)), 0);

		// Calcolo |OEE LINEA| (N.B: formula modificata senza utilizzo delle 3 componenti: n° pezzi conformi / n° pezzi teorici nel tempo totale)
		$numPezziTeoriciLinea = intval($tempoTotaleLinea_sec * $tempoTeoricoPezzoLinea_sec);
		$numPezziConformiLinea = intval($numPezziTotaliLinea) - intval($numPezziScartatiLinea);
		$OEELinea = round(floatval($numPezziConformiLinea / ($numPezziTeoriciLinea != 0 ? $numPezziTeoriciLinea : 1)), 4);
		$OEELinea_perc = floatval($OEELinea * 100);

		// Fattore |D LINEA|
		$uptimeLinea_sec = round(floatval($tempoTotaleLinea_sec - $downtimeLinea_sec), 2);
		$sommaUptimeDowntimeLinea_sec = $tempoTotaleLinea_sec;
		$fattoreDLinea = round(floatval($uptimeLinea_sec / ($sommaUptimeDowntimeLinea_sec != 0 ? $sommaUptimeDowntimeLinea_sec : 1)), 4);
		$fattoreDLinea_perc = floatval($fattoreDLinea * 100);

		// Fattore |E LINEA|
		$numPezziTeoriciLineaUptime = intval($uptimeLinea_sec * $tempoTeoricoPezzoLinea_sec);
		$fattoreELinea = round(floatval($numPezziTotaliLinea / ($numPezziTeoriciLineaUptime != 0 ? $numPezziTeoriciLineaUptime : 1)), 4);
		$fattoreELinea_perc = floatval($fattoreELinea * 100);

		// Fattore |Q LINEA|
		if($fattoreDLinea != 0 && $fattoreELinea != 0) {
			$prodTempFattori = floatval($fattoreDLinea * $fattoreELinea);
		}
		else {
			$prodTempFattori = 1;
		}
		$fattoreQLinea = round(floatval($OEELinea / $prodTempFattori), 4);
		$fattoreQLinea_perc = floatval($fattoreQLinea * 100);




		// STEP 7: aggiorno tabella 'RIENTRO_LINEA_PRODUZIONE'

		// completo la entry in tabella 'rientro_linea_produzione' con le informazioni di chiusura per la produzione in oggetto (Data fine, Ora fine, TTotale, Downtime, T Attrezzaggio, QUantità prodotta, Quantità scarti, Fattore D, Fattore Q, Fattore E, OEE)
		$sqlUpdateRientroLinea = "UPDATE rientro_linea_produzione SET
								rlp_DataFine = :DataFine,
								rlp_OraFine = :OraFine,
								rlp_TTotale = :TempoTotaleProduzione,
								rlp_Downtime = :DowntimeProduzione,
								rlp_Attrezzaggio = :TempoAttrezzaggio,
								rlp_TFermo = :TempoFermoLinea,
								rlp_TPausaPrevista = :TempoPausaPrevistaLinea,
								rlp_DurataEventiBloccanti = :DurataEventiBloccantiProduzione,
								rlp_QtaProdotta = :QtaProdotta,
								rlp_QtaConforme = :QtaConforme,
								rlp_QtaScarti = :QtaScarti,
								rlp_D = :FattoreDLinea,
								rlp_E = :FattoreELinea,
								rlp_Q = :FattoreQLinea,
								rlp_OEELinea = :OEELinea,
								rlp_VelocitaLinea = :VelocitaLinea
								WHERE rlp_IdProduzione = :IdProduzione";

		$sthUpdateRientroLinea = $conn_mes->prepare($sqlUpdateRientroLinea);
		$sthUpdateRientroLinea->execute(array(":DataFine" => $dataOdierna,
												":OraFine" => $oraOdierna,
												":TempoTotaleProduzione" => (float)$tempoTotaleLinea_min,
												":DowntimeProduzione" => (float)$downtimeLinea_min,
												":TempoAttrezzaggio" => (float)$tempoAttrezzaggioLinea_min,
												":TempoFermoLinea" => (float)$tempoFermoLinea_min,
												":TempoPausaPrevistaLinea" => (float)$tempoPausaPrevistaLinea_min,
												":DurataEventiBloccantiProduzione" => (float)$durataEventiBloccantiLinea_min,
												":QtaProdotta" => (float)$numPezziTotaliLinea,
												":QtaConforme" => (float)$numPezziConformiLinea,
												":QtaScarti" => (float)$numPezziScartatiLinea,
												":FattoreDLinea" => ($fattoreDLinea_perc > 100 ? 100 : $fattoreDLinea_perc),
												":FattoreELinea" => ($fattoreELinea_perc > 100 ? 100 : $fattoreELinea_perc),
												":FattoreQLinea" => ($fattoreQLinea_perc > 100 ? 100 : $fattoreQLinea_perc),
												":OEELinea" => ($OEELinea_perc > 100 ? 100 : $OEELinea_perc),
												":VelocitaLinea" => (float)$velocitaLinea,
												":IdProduzione" => $idProduzione));




		// STEP 8: aggiorno tabella 'EFFICIENZA_LINEA'

		// Verifico se esiste già entry per l'ID produzione considerato
		$sthVerificaEfficienzaRisorse = $conn_mes->prepare("SELECT COUNT(*) AS ELPresente
													FROM efficienza_linea
													WHERE efficienza_linea.el_IdLinea = :IdLineaProduzione AND efficienza_linea.el_IdProdotto = :IdProdotto", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
		$sthVerificaEfficienzaRisorse->execute(array(":IdLineaProduzione" => $rigaVelocitaTeoricaLinea['op_LineaProduzione'], ":IdProdotto" => $rigaVelocitaTeoricaLinea['op_Prodotto']));
		$rigaVerificaEfficienzaRisorse = $sthVerificaEfficienzaRisorse->fetch(PDO::FETCH_ASSOC);

		if ($rigaVerificaEfficienzaRisorse['ELPresente'] == 0) {

			$sqlUpdateEfficienzaLinea = "INSERT INTO efficienza_linea (el_IdProdotto, el_IdLinea, el_DMedio, el_EMedio, el_QMedio, el_OEEMedio, el_OEEMinimo, el_OEEMigliore, el_IdProduzioneMinimo, el_IdProduzioneMigliore)
											VALUES (:IdProdotto, :IdLineaProduzione, :DMedioLinea, :EMedioLinea, :QMedioLinea, :OEEMedioLinea, :OEEMedioLinea, :OEEMedioLinea, :IdProduzione, :IdProduzione2)";

			$sthUpdateEfficienzaLinea = $conn_mes->prepare($sqlUpdateEfficienzaLinea);
			$sthUpdateEfficienzaLinea->execute(array(":IdProdotto" => $rigaVelocitaTeoricaLinea['op_Prodotto'],
														":IdLineaProduzione" => $rigaVelocitaTeoricaLinea['op_LineaProduzione'],
														":DMedioLinea" => ($fattoreDLinea_perc > 100 ? 100 : $fattoreDLinea_perc),
														":EMedioLinea" => ($fattoreELinea_perc > 100 ? 100 : $fattoreELinea_perc),
														":QMedioLinea" => ($fattoreQLinea_perc > 100 ? 100 : $fattoreQLinea_perc),
														":OEEMedioLinea" => ($OEELinea_perc > 100 ? 100 : $OEELinea_perc),
														":IdProduzione" => $idProduzione,
														":IdProduzione2" => $idProduzione,
													));
		}
		else {
			$sqlUpdateEfficienzaLinea = "UPDATE efficienza_linea SET
											el_DMedio =  (el_DMedio + :DMedioLinea) / 2,
											el_EMedio =  (el_EMedio + :EMedioLinea) / 2,
											el_QMedio =  (el_QMedio + :QMedioLinea) / 2,
											el_OEEMedio =  (el_OEEMedio + :OEEMedioLinea) / 2,
											el_OEEMinimo = LEAST(el_OEEMinimo, :OEEMedioLinea),
											el_OEEMigliore = GREATEST(el_OEEMigliore, :OEEMedioLinea),
											el_IdProduzioneMinimo = IF (LEAST(el_OEEMinimo, :OEEMedioLinea), :IdProduzione, el_IdProduzioneMinimo),
											el_IdProduzioneMigliore = IF (LEAST(el_OEEMigliore, :OEEMedioLinea), :IdProduzione, el_IdProduzioneMigliore)
											WHERE efficienza_linea.el_IdLinea = :IdLineaProduzione AND efficienza_linea.el_IdProdotto = :IdProdotto";

			$sthUpdateEfficienzaLinea = $conn_mes->prepare($sqlUpdateEfficienzaLinea);
			$sthUpdateEfficienzaLinea->execute(array(":IdProdotto" => $rigaVelocitaTeoricaLinea['op_Prodotto'],
														":IdLineaProduzione" => $rigaVelocitaTeoricaLinea['op_LineaProduzione'],
														":DMedioLinea" => ($fattoreDLinea_perc > 100 ? 100 : $fattoreDLinea_perc),
														":EMedioLinea" => ($fattoreELinea_perc > 100 ? 100 : $fattoreELinea_perc),
														":QMedioLinea" => ($fattoreQLinea_perc > 100 ? 100 : $fattoreQLinea_perc),
														":OEEMedioLinea" => ($OEELinea_perc > 100 ? 100 : $OEELinea_perc),
														":IdProduzione" => $idProduzione));
		}



		// STEP 9: aggiornto tabella 'ORDINI PRODUZIONE'

		// Aggiorno la entry nella tabella 'risorse" per la risorsa in oggetto, impostando come 'CHIUSO' (codice id = 5) lo stato dell'ordine di produzione caricato con stato attuale 'OK' (codice id = 4)
		$sqlUpdateStatoOrdine = "UPDATE ordini_produzione SET
								op_Stato = 5,
								op_Caricato = 0
								WHERE op_IdProduzione = :IdProduzione";
		$sthUpdateStatoOrdine = $conn_mes->prepare($sqlUpdateStatoOrdine);
		$sthUpdateStatoOrdine->execute(array(":IdProduzione" => $idProduzione));



		// DEFINITIVO: sintesi esito operazioni
		// in base ai valori ritornati dall'esecuzione delle query, eseguo commit/rollout della transazione SQL
		$conn_mes->beginTransaction();

		if ($sthRecuperaVelocitaTeoricaLinea && $sthCalcoloTempoTotaleProduzione && $sthElencoDowntimeLinea && $sthRecuperaInformazioniRisorseLinea && $sthUpdateRientroLinea && $sthUpdateStatoOrdine && $sthUpdateEfficienzaLinea) {
			$conn_mes->commit();
			$log = $dataOdierna." ".$oraOdierna.": ".$idRisorsaCoinvolta." - TERMINAZIONE COMMESSA -> ESEGUITA TERMINAZIONE COMMESSA\n";
		}
		else {
			$conn_mes->rollBack();
			$log = $dataOdierna." ".$oraOdierna.": ".$idRisorsaCoinvolta." - TERMINAZIONE COMMESSA -> ERRORE! TERMINAZIONE COMMESSA NON ANDATA A BUON FINE\n";
		}
		file_put_contents('C:\MES_log\SCADAInterface\SCADAInterface_log_'.date("Ymd").'.log', $log, FILE_APPEND);

	}







	// INVOCO PROCEDURA DI RICEZIONE SEGNALI DA SCADA
	if(!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "read-data-from-SCADA")
	{


		$dataOdierna = date('Y-m-d');
		$oraOdierna = date('H:i:s');

		$dataSuccessiva = date('Y-m-d', strtotime(' +1 day'));

		$elencoRisorse = "";


		// se connessione con DB MySql è attiva
		if ($conn_mes) {


			// ----------------------------------------------- BLOCCO RICEZIONE COMANDI DI START/STOP DA SCADA -----------------------------------------------

			//$log = $dataOdierna." ".$oraOdierna.": INVOCATA PROCEDURA\n";
			//file_put_contents('C:\MES_log\SCADAInterface\SCADAInterface_log_'.date("Ymd").'.log', $log, FILE_APPEND);

			// Recupero la lista delle risorse coinvolte per l'ordine in oggetto e che sono attualmente disponibili (non stanno eseguendo altre produzioni e non hanno ancora eseguito la produzione in oggetto)
			$sthComandiRisorseDaScada = $conn_mes->prepare("SELECT R.ris_IdRisorsa, R.ris_IdProduzione, R.ris_StatoOrdine, RP.rp_DataInizio, RP.rp_DataFine, RP.rp_OraInizio, RP.rp_OraFine, R.ris_StartLavoro_Scada, R.ris_StopLavoro_Scada, R.ris_StopCommessa_Scada, ODP.op_QtaRichiesta
													FROM risorse AS R
													LEFT JOIN ordini_produzione AS ODP ON R.ris_IdProduzione = ODP.op_IdProduzione
													LEFT JOIN risorsa_produzione AS RP ON RP.rp_IdRisorsa = R.ris_IdRisorsa AND RP.rp_IdProduzione = R.ris_IdProduzione", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
			$sthComandiRisorseDaScada->execute();

			// se ho trovato risorse corrispondenti ai criteri cercati
			if ($sthComandiRisorseDaScada->rowCount() > 0) {

				$risorseTrovate = $sthComandiRisorseDaScada->fetchAll(PDO::FETCH_ASSOC);


				// sggiungo ognuna delle risorse trovate alla stringa e la ritorno come risultato
				foreach($risorseTrovate as $risorsa) {


					$idRisorsa = $risorsa['ris_IdRisorsa'];
					$idProduzione = $risorsa['ris_IdProduzione'];
					$qtaRichiesta = (float)$risorsa["op_QtaRichiesta"];


					if (($risorsa['ris_StatoOrdine'] == 'ATTIVO') && !isset($risorsa['rp_DataInizio']) && !isset($risorsa['rp_OraInizio']) && isset($risorsa['ris_StartLavoro_Scada']) && ($risorsa['ris_StartLavoro_Scada'] == true)) {

						$log = $dataOdierna." ".$oraOdierna.": ".$idRisorsa." - RICEVUTO COMANDO START LAVORO DA SCADA\n";
						file_put_contents('C:\MES_log\SCADAInterface\SCADAInterface_log_'.date("Ymd").'.log', $log, FILE_APPEND);


						$richiestoStartLavoroDaScada = True;
						$richiestoStopLavoroDaScada = False;
						$richiestoStopCommessaDaScada = False;

						$sqlUpdateStatoRisorsa = "UPDATE risorse SET
										ris_StartLavoro_Scada = NULL
										WHERE ris_IdRisorsa = :IdRisorsa";
						$sthUpdateStatoRisorsa = $conn_mes->prepare($sqlUpdateStatoRisorsa);
						$sthUpdateStatoRisorsa->execute(array(":IdRisorsa" => $idRisorsa));




						// ------ ESEGUO OPERAZIONE DI INIZIO ORDINE ------
						inizioLavoroECommessa($dataOdierna, $oraOdierna, $dataSuccessiva, $conn_mes, $idRisorsa, $idProduzione, $qtaRichiesta);




					}
					else if (($risorsa['ris_StatoOrdine'] == 'OK') && isset($risorsa['rp_DataInizio']) && isset($risorsa['rp_OraInizio']) && !isset($risorsa['rp_DataFine']) && !isset($risorsa['rp_OraFine']) && isset($risorsa['ris_StopLavoro_Scada']) && ($risorsa['ris_StopLavoro_Scada'] == true)) {


						$log = $dataOdierna." ".$oraOdierna.": ".$idRisorsa." - RICEVUTO COMANDO TERMINAZIONE LAVORO DA SCADA\n";
						file_put_contents('C:\MES_log\SCADAInterface\SCADAInterface_log_'.date("Ymd").'.log', $log, FILE_APPEND);

						$richiestoStartLavoroDaScada = False;
						$richiestoStopLavoroDaScada = True;
						$richiestoStopCommessaDaScada = False;

						$sqlUpdateStatoRisorsa = "UPDATE risorse SET
										ris_StopLavoro_Scada = NULL
										WHERE ris_IdRisorsa = :IdRisorsa";
						$sthUpdateStatoRisorsa = $conn_mes->prepare($sqlUpdateStatoRisorsa);
						$sthUpdateStatoRisorsa->execute(array(":IdRisorsa" => $idRisorsa));



						// ------ ESEGUO OPERAZIONE DI TERMINAZIONE ORDINE ------
						terminazioneLavoroRisorsa($dataOdierna, $oraOdierna, $dataSuccessiva, $conn_mes, $idRisorsa, $idProduzione);


					}
					else if (($risorsa['ris_StatoOrdine'] == 'OK') && isset($risorsa['rp_DataInizio']) && isset($risorsa['rp_OraInizio']) && !isset($risorsa['rp_DataFine']) && !isset($risorsa['rp_OraFine']) && isset($risorsa['ris_StopCommessa_Scada']) && ($risorsa['ris_StopCommessa_Scada'] == true)) {


						$log = $dataOdierna." ".$oraOdierna.": ".$idRisorsa." - RICEVUTO COMANDO TERMINAZIONE COMMESSA DA SCADA\n";
						file_put_contents('C:\MES_log\SCADAInterface\SCADAInterface_log_'.date("Ymd").'.log', $log, FILE_APPEND);

						$richiestoStartLavoroDaScada = False;
						$richiestoStopLavoroDaScada = False;
						$richiestoStopCommessaDaScada = True;

						$sqlUpdateStatoRisorsa = "UPDATE risorse SET
										ris_StopCommessa_Scada = NULL
										WHERE ris_IdRisorsa = :IdRisorsa";
						$sthUpdateStatoRisorsa = $conn_mes->prepare($sqlUpdateStatoRisorsa);
						$sthUpdateStatoRisorsa->execute(array(":IdRisorsa" => $idRisorsa));




						// Estraggo informazioni sulla risorsa in oggetto
						$sthRisorseCoinvolte = $conn_mes->prepare("SELECT risorse_coinvolte.rc_IdRisorsa, risorse.ris_Descrizione
																	FROM risorse_coinvolte
																	LEFT JOIN risorsa_produzione ON risorse_coinvolte.rc_IdProduzione = risorsa_produzione.rp_IdProduzione AND risorse_coinvolte.rc_IdRisorsa = risorsa_produzione.rp_IdRisorsa
																	LEFT JOIN risorse ON risorse_coinvolte.rc_IdRisorsa = risorse.ris_IdRisorsa
																	WHERE risorse_coinvolte.rc_IdProduzione = :IdProduzione AND risorsa_produzione.rp_DataFine IS NULL", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
						$sthRisorseCoinvolte->execute(array(":IdProduzione" => $idProduzione));

						if ($sthRisorseCoinvolte->rowCount() > 0) {

							$righeRisorseCoinvolte = $sthRisorseCoinvolte->fetchAll(PDO::FETCH_ASSOC);

							foreach($righeRisorseCoinvolte as $rigaRisorsaCoinvolta) {

								$idRisorsaCoinvolta = $rigaRisorsaCoinvolta['rc_IdRisorsa'];

								// ------ ESEGUO OPERAZIONE DI TERMINAZIONE ORDINE ------
								terminazioneLavoroRisorsa($dataOdierna, $oraOdierna, $dataSuccessiva, $conn_mes, $idRisorsaCoinvolta, $idProduzione);

							}
						}

						// ------ ESEGUO OPERAZIONE DI TERMINAZIONE COMMESSA PER LA LINEA ------
						terminazioneCommessaLinea($dataOdierna, $oraOdierna, $dataSuccessiva, $conn_mes, $idProduzione);


					}
				}


				// Resetto a NULL tutte le variabili in tabella 'risorse'
				$sqlUpdateStatoRisorsa = "UPDATE risorse SET
								ris_StartLavoro_Scada = NULL,
								ris_StopLavoro_Scada = NULL,
								ris_StopCommessa_Scada = NULL";
				$sthUpdateStatoRisorsa = $conn_mes->prepare($sqlUpdateStatoRisorsa);
				$sthUpdateStatoRisorsa->execute();


			}

		}
		else {
			$log = $dataOdierna." ".$oraOdierna.": ERRORE_CONNESSIONE_".$oraOdierna.";\n";
		}
	}
