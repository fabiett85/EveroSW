<?php
// in che pagina siamo
$pagina = 'profimaxfunction';
include("../inc/conn.php");

// debug($_SESSION['utente'],'Utente');
date_default_timezone_set('Europe/Rome');
	
// GENERAZIONE TRACCIATO DI TIPO IMPORT
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'update-import') {
	
	// Apro la transazione MySQL
	$conn_mes->beginTransaction();

	try {
		
		$sth = $conn_mes->prepare("SELECT
								Cantina.*,
								Cantina_Modo2.ESL_campo_1 AS ESL_campo_1_Tab2,
								Cantina_Modo2.ESL_campo_2 AS ESL_campo_2_Tab2,
								Cantina_Modo2.ESL_campo_3 AS ESL_campo_3_Tab2,
								Cantina_Modo2.ESL_campo_4 AS ESL_campo_4_Tab2,
								Cantina_Modo2.ESL_campo_5 AS ESL_campo_5_Tab2,
								Cantina_Modo2.ESL_campo_6 AS ESL_campo_6_Tab2,
								Cantina_Modo2.ESL_campo_7 AS ESL_campo_7_Tab2,
								Cantina_Modo2.ESL_campo_8 AS ESL_campo_8_Tab2,
								Cantina_Modo2.ESL_campo_9 AS ESL_campo_9_Tab2,
								Cantina_Modo2.ESL_campo_10 AS ESL_campo_10_Tab2,
								Contenitori.Nome AS Nome_Cont, Contenitori.Tipo_contenitore 
						 FROM  Cantina
						 LEFT JOIN Contenitori
						 ON Contenitori.Codice_contenitore = Cantina.Numero_serbatoio
						 LEFT JOIN Cantina_Modo2
						 ON Contenitori.Codice_contenitore = Cantina_Modo2.Numero_serbatoio
						 WHERE Cantina.Abilitazione = 1
						 AND Cantina_Modo2.Abilitazione = 1
						 ORDER BY Cantina.Id ASC", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
		$sth->execute();
		$righeDatiESL = $sth->fetchAll(PDO::FETCH_ASSOC);
			
		$datiSerbatoioToEsl = "";
		
		foreach ($righeDatiESL as $riga) {	
					
			//DATI RELATIVI AL SERBATOIO
			//Determino dati etichette comuni alle due tabelle (Codice Esl, codice interno serbatoio, codice EAN, nome del contenitore))
			$idEsl = trim($riga['Codice_etichetta']);
			$codiceSerbatoio = trim($riga['Numero_serbatoio']);				
			$codiceTemp = explode("_", $codiceSerbatoio);
			$codiceEAN = "000000_".$codiceTemp[1];				
			$nomeSerbatoio = trim($riga['Nome_Cont']);
			
			//SE NON E' PREVISTO L'ALLINEAMENTO DATI SULLE DUE PAGINE
			if($_SESSION['configurazione']['AllineaMod2'] == 0) {
				
				//Determino i dati etichette relativi alla tabella numero 1 (UFFICIALE)
				$fTracciatoCampo1_Tab1 = (float)trim($riga['ESL_campo_1']);
				$sTracciatoCampo2_Tab1 = strtoupper(trim($riga['ESL_campo_2']));
				$sTracciatoCampo3_Tab1 = strtoupper(trim($riga['ESL_campo_3']));
				$sTracciatoCampo4_Tab1 = strtoupper(trim($riga['ESL_campo_4']));
				$sTracciatoCampo5_Tab1 = UcFirst(trim($riga['ESL_campo_5']));
				$sTracciatoCampo6_Tab1 = trim($riga['ESL_campo_6']);
				$sTracciatoCampo8_Tab1 = strtoupper(trim($riga['ESL_campo_8']));
				$sTracciatoCampo7_Tab1 = strtoupper(trim($riga['ESL_campo_7']));
				$sTracciatoCampo9_Tab1 = strtoupper(trim($riga['ESL_campo_9']));
				$sTracciatoCampo10_Tab1 = strtoupper(trim($riga['ESL_campo_10']));

				//Determino i dati etichette relativi alla tabella numero 2 (UFFICIOSA)
				$fTracciatoCampo1_Tab2 = (float)trim($riga['ESL_campo_1_Tab2']);
				$sTracciatoCampo2_Tab2 = strtoupper(trim($riga['ESL_campo_2_Tab2']));
				$sTracciatoCampo3_Tab2 = strtoupper(trim($riga['ESL_campo_3_Tab2']));
				$sTracciatoCampo4_Tab2 = strtoupper(trim($riga['ESL_campo_4_Tab2']));
				$sTracciatoCampo5_Tab2 = UcFirst(trim($riga['ESL_campo_5_Tab2']));
				$sTracciatoCampo6_Tab2 = trim($riga['ESL_campo_6_Tab2']);
				$sTracciatoCampo8_Tab2 = strtoupper(trim($riga['ESL_campo_8_Tab2']));
				$sTracciatoCampo7_Tab2 = strtoupper(trim($riga['ESL_campo_7_Tab2']));
				$sTracciatoCampo9_Tab2 = strtoupper(trim($riga['ESL_campo_9_Tab2']));
				$sTracciatoCampo10_Tab2 = strtoupper(trim($riga['ESL_campo_10_Tab2']));	
						
			}
			
			//SE E' PREVISTO L'ALLINEAMENTO DATI SULLE DUE PAGINE
			if($_SESSION['configurazione']['AllineaMod2'] == 1) {
				
				//Determino i dati etichette relativi alla tabella numero 1 (UFFICIALE)
				$fTracciatoCampo1_Tab1 = (float)trim($riga['ESL_campo_1']);
				$sTracciatoCampo2_Tab1 = strtoupper(trim($riga['ESL_campo_2']));
				$sTracciatoCampo3_Tab1 = strtoupper(trim($riga['ESL_campo_3']));
				$sTracciatoCampo4_Tab1 = strtoupper(trim($riga['ESL_campo_4']));
				$sTracciatoCampo5_Tab1 = UcFirst(trim($riga['ESL_campo_5']));
				$sTracciatoCampo6_Tab1 = trim($riga['ESL_campo_6']);
				$sTracciatoCampo8_Tab1 = strtoupper(trim($riga['ESL_campo_8']));
				$sTracciatoCampo7_Tab1 = strtoupper(trim($riga['ESL_campo_7']));
				$sTracciatoCampo9_Tab1 = strtoupper(trim($riga['ESL_campo_9']));
				$sTracciatoCampo10_Tab1 = strtoupper(trim($riga['ESL_campo_10']));

				//Determino i dati etichette relativi alla tabella numero 2 (UFFICIOSA)
				$fTracciatoCampo1_Tab2 = (float)trim($riga['ESL_campo_1']);
				$sTracciatoCampo2_Tab2 = strtoupper(trim($riga['ESL_campo_2']));
				$sTracciatoCampo3_Tab2 = strtoupper(trim($riga['ESL_campo_3']));
				$sTracciatoCampo4_Tab2 = strtoupper(trim($riga['ESL_campo_4']));
				$sTracciatoCampo5_Tab2 = UcFirst(trim($riga['ESL_campo_5']));
				$sTracciatoCampo6_Tab2 = trim($riga['ESL_campo_6']);
				$sTracciatoCampo8_Tab2 = strtoupper(trim($riga['ESL_campo_8']));
				$sTracciatoCampo7_Tab2 = strtoupper(trim($riga['ESL_campo_7']));
				$sTracciatoCampo9_Tab2 = strtoupper(trim($riga['ESL_campo_9']));
				$sTracciatoCampo10_Tab2 = strtoupper(trim($riga['ESL_campo_10']));
						
			}
									
										
			//DATI LAVORAZIONI LEGATE AL SERBATOIO
			//Recupero dati relativi alle operazioni da effettuare sul serbatoio considerato
			$aDatiLavorazioni = array("--", "--", "--", "--", "--", "--", "--", "--", "--", "--");
			$aDatiOperatori = array(" ", " ", " ", " ", " ", " ", " ", " ", " ", " ");
			$aStatoLavorazioni = array("0D", "1D", "2D", "3D", "4D", "5D", "6D", "--", "--", "--");
			$sDidascaliaLavorazioni = "Elenco lavorazioni:";
			$sDidascaliaOperatore = "Cod. conferma:";
					
				
					
			// verifico se esiste già un caso aperto in tabella 'attivita_casi', del medesimo tipo; in questo caso l'inserimento non viene effettuato
			$sthVerificaLavorazioni = $conn_mes->prepare("SELECT COUNT(*) AS LavoriPianificati
															FROM Lav_pianificate
															WHERE (Stato_Esl = '1a' OR  Stato_Esl = '1c') AND Cod_SDestinazione = :CodiceSerbatoio"

			);
			$sthVerificaLavorazioni->execute([':CodiceSerbatoio' => $codiceSerbatoio]);
			$rigaVerificaLavorazioni = $sthVerificaLavorazioni->fetch();


			// se non ho trovato casi aperti con quell'ID, procedo all'inserimento
			if ($rigaVerificaLavorazioni['LavoriPianificati'] == 0) {
				
				$sthRecuperaLavorazioni = $conn_mes->prepare("SELECT Contenitori.Nome AS Nome_Cont, Operazioni.Nome AS Nome_op, Operazioni.Codice_operazione AS Cod_oper, Op_inc.Cognome AS Cognome_op_inc, Op_inc.Nome AS Nome_op_inc, Op_es.Cognome AS Cognome_op_es, Op_es.Nome AS Nome_op_es, Lav_pianificate.*
																FROM Lav_pianificate
																LEFT JOIN Contenitori 
																ON Contenitori.Codice_contenitore = Lav_pianificate.Cod_SDestinazione
																LEFT JOIN Operazioni 
																ON Operazioni.Codice_operazione = Lav_pianificate.Operazione
																LEFT JOIN Operatori Op_inc 
																ON Op_inc.Codice_operatore = Lav_pianificate.Operatore
																LEFT JOIN Operatori Op_es 
																ON Op_es.Codice_operatore = Lav_pianificate.Operatore_effettivo
																WHERE (Lav_pianificate.Stato_Esl = '0a')
																AND Lav_pianificate.Cod_SDestinazione = :CodiceSerbatoio
																ORDER BY Lav_pianificate.Data_operazione ASC", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
				
				
			}
			else {
				$sthRecuperaLavorazioni = $conn_mes->prepare("SELECT Contenitori.Nome AS Nome_Cont, Operazioni.Nome AS Nome_op, Operazioni.Codice_operazione AS Cod_oper, Op_inc.Cognome AS Cognome_op_inc, Op_inc.Nome AS Nome_op_inc, Op_es.Cognome AS Cognome_op_es, Op_es.Nome AS Nome_op_es, Lav_pianificate.*
																FROM Lav_pianificate
																LEFT JOIN Contenitori 
																ON Contenitori.Codice_contenitore = Lav_pianificate.Cod_SDestinazione
																LEFT JOIN Operazioni 
																ON Operazioni.Codice_operazione = Lav_pianificate.Operazione
																LEFT JOIN Operatori Op_inc 
																ON Op_inc.Codice_operatore = Lav_pianificate.Operatore
																LEFT JOIN Operatori Op_es 
																ON Op_es.Codice_operatore = Lav_pianificate.Operatore_effettivo
																WHERE (Lav_pianificate.Stato_Esl = '1a' 
																OR Lav_pianificate.Stato_Esl = '0a')
																AND Lav_pianificate.Cod_SDestinazione = :CodiceSerbatoio
																ORDER BY Lav_pianificate.Data_operazione ASC", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);		
			
			}
				
			$sthRecuperaLavorazioni->execute([':CodiceSerbatoio' => $codiceSerbatoio]);
			$righeLavorazioni = $sthRecuperaLavorazioni->fetchAll(PDO::FETCH_ASSOC);
				
			$numeroLavorazioniVisualizzate = 1;
			
			foreach ($righeLavorazioni as $rigaLavorazioni) {	
			
							
				//Finché non ho raggiunto il numero massimo di lavorazioni visualizzabili...
				if ($numeroLavorazioniVisualizzate <= 6) {
					$codiceLavPianificata = (int)$rigaLavorazioni['Id_pianificata'];
					$codiceDettaglio = (int)$rigaLavorazioni['Id_dettaglio'];
					
					$codiceOperazione = (int)$rigaLavorazioni['Cod_oper'];
					if ($codiceOperazione != 8) {
						$sOperazione = trim(ucfirst(strtolower($rigaLavorazioni['Nome_op'])));									
					}
					else {
						$sOperazione = trim(ucfirst(strtolower($rigaLavorazioni['Nome_operazione_personalizzata'])));	
					}

					$sNomeOperatore = trim($rigaLavorazioni['Nome_op_inc']);
					$sCognomeOperatore = trim($rigaLavorazioni['Cognome_op_inc']);
					$sDatiLavorazione = $sOperazione;
					$sDatiOperatore = $sNomeOperatore[0].". ".$sCognomeOperatore;
					$aDatiLavorazioni[$numeroLavorazioniVisualizzate] = $sDatiLavorazione;
					$aDatiOperatori[$numeroLavorazioniVisualizzate] = $sDatiOperatore;
								
								
					$queryUpdateLavorazione = "UPDATE Lav_pianificate
												SET Lav_pianificate.Stato_Esl = '1a'
												WHERE Lav_pianificate.Id_pianificata = :CodiceOpPianificata AND Lav_pianificate.Id_dettaglio = :CodiceDettaglio";
					$sthUpdateLavorazione = $conn_mes->prepare($queryUpdateLavorazione);
					$sthUpdateLavorazione->execute([':CodiceOpPianificata' => $codiceLavPianificata, ':CodiceDettaglio' => $codiceDettaglio]);								
								
				}
				$numeroLavorazioniVisualizzate = $numeroLavorazioniVisualizzate+1;
			}		
					
			//SCRITTURA FILE DEI TRACCIATI
			//Preparo la stringa da scrivere sul file da passare al sistema profimax, componendola con i dati ricavati sopra
			if ($_SESSION['configurazione']['AbiMod2'] == 0) {
				//Tracciato per modalità: LAVORAZIONI SU SECONDA PAGINA
				$datiSerbatoioToEsl .= $codiceSerbatoio.";".$codiceEAN.";;".$nomeSerbatoio.";".$sTracciatoCampo3_Tab1.";;".$fTracciatoCampo1_Tab1.";;;;;;;".$sTracciatoCampo4_Tab1.";".$sTracciatoCampo5_Tab1.";".$sTracciatoCampo7_Tab1.";;;;;;;0;;;;;;;;;;0;;;".$idEsl.";".$codiceSerbatoio.";".$sTracciatoCampo2_Tab1.";".$sTracciatoCampo6_Tab1.";".$sTracciatoCampo9_Tab1.";".$sTracciatoCampo8_Tab1.";".$sTracciatoCampo10_Tab1.";".$aDatiLavorazioni[1].";".$aDatiLavorazioni[2].";".$aDatiLavorazioni[3].";".$aDatiLavorazioni[4].";".$aDatiLavorazioni[5].";".$aDatiLavorazioni[6].";;;;;;\n";
			}
			if ($_SESSION['configurazione']['AbiMod2'] == 1) {
				//Tracciato per modalità: DATI SERBATOI SU SECONDA PAGINA
				$datiSerbatoioToEsl .= $codiceSerbatoio.";".$codiceEAN.";;".$nomeSerbatoio.";".$sTracciatoCampo3_Tab1.";;".$fTracciatoCampo1_Tab1.";;;;;;;".$sTracciatoCampo4_Tab1.";".$sTracciatoCampo5_Tab1.";".$sTracciatoCampo7_Tab1.";;;;;;;0;;;;;;;;;;0;;;".$idEsl.";".$codiceSerbatoio.";".$sTracciatoCampo2_Tab1.";".$sTracciatoCampo6_Tab1.";".$sTracciatoCampo9_Tab1.";".$sTracciatoCampo8_Tab1.";".$sTracciatoCampo10_Tab1.";".$fTracciatoCampo1_Tab2.";".$sTracciatoCampo2_Tab2.";".$sTracciatoCampo3_Tab2.";".$sTracciatoCampo4_Tab2.";".$sTracciatoCampo5_Tab2.";".$sTracciatoCampo6_Tab2.";".$sTracciatoCampo7_Tab2.";".$sTracciatoCampo8_Tab2.";".$sTracciatoCampo9_Tab2.";".$sTracciatoCampo10_Tab2.";;\n";
			}
		}

		
		//Ottengo l'ora di sistema per gestire la nomenclatura dei file
		$data = time();
		date_default_timezone_set('Europe/Rome');
		$dataSistemaAttuale = date('YmdHis', $data);

		//GESTIONE IMPORTAZIONE/AGGIORNAMENTO DATI	
		//Apertura in scrittura del file tracciato Profimax IMPORT, relativo all'esecuzione dell'aggiornamento dati relaitivi ai Serbatoi
		$fileESLUpdate = fopen("C:\\Import_SwMediasoft\\Import_".$dataSistemaAttuale.".txt", "w") or die("Errore nell'apertura del file di configurazione!");
		//$fileESLUpdate = fopen("\\\\10.8.2.102\\Import\\Import_".$dataSistemaAttuale.".txt", "w") or die("Errore nell'apertura del file di configurazione!");
		
		
		//Scrittura su file della necessaria intestazione
		fwrite($fileESLUpdate, pack("CCC",0xef,0xbb,0xbf));
		
		//Scrivo su file la stringa relativa al tracciato di IMPORT
		fwrite($fileESLUpdate, $datiSerbatoioToEsl);
		
		//Scrivo su file il blocco di chiusura generale
		fclose($fileESLUpdate);	
		
		$conn_mes->commit();
		die('OK');
		
	} catch (Throwable $t) {
		$conn_mes->rollBack();
		die("ERRORE: " . $t);
	}
		
}

// GENERAZIONE TRACCIATO DI TIPO EXECUTE
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'update-execute') {
	
	// Apro la transazione MySQL
	$conn_mes->beginTransaction();

	try {
		
		//Determino la tabella Esl a cui fare riferimento, in base alla modalità impostata nel file di cvonfigurazione utente
		if ($_SESSION['configurazione']['AbiMod2'] == 0) {
			$nomeTabella = "Cantina";
			$nomeTabellaAppoggio = "Esl_appoggio";
		}
		else if ($_SESSION['configurazione']['AbiMod2'] == 1) {
			$nomeTabella = "Cantina_Modo2";
			$nomeTabellaAppoggio = "Esl_appoggio_Modo2";	
		}
		
		//ASSOCIAZIONE ESL-SERBATOI 
		$datiAssociazione = "";
		$datiDissociazione = "";
		
		$sthAssociazioneESL = $conn_mes->prepare("SELECT Esl_appoggio.*, ".$nomeTabella.".*, Contenitori.Codice_contenitore 
								 FROM Esl_appoggio 
								 LEFT JOIN ".$nomeTabella." 
								 ON Esl_appoggio.Codice_esl = ".$nomeTabella.".Codice_etichetta 
								 LEFT JOIN Contenitori 
								 ON ".$nomeTabella.".Numero_serbatoio = Contenitori.Codice_contenitore 
								 WHERE (Esl_appoggio.Stato_associazione = 1 
								 AND Esl_appoggio.Stato_associazione != Esl_appoggio.Stato_associazione_precedente) 
								 OR (Esl_appoggio.Stato_associazione = 1 
								 AND Esl_appoggio.Articolo_precedente != Esl_appoggio.Articolo_attuale) 
								 AND ".$nomeTabella.".Abilitazione = 1", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
		$sthAssociazioneESL->execute();
		$righeAssociazioneESL = $sthAssociazioneESL->fetchAll(PDO::FETCH_ASSOC);		

		foreach ($righeAssociazioneESL as $rigaAssociazione) {	
			//Estraggo dal recordset i dati di interesse realtivi ai silos e preparo le relative stringhe da aggiungere al file che gestisce l'import degli articoli nel sistema Profimax
			//Determino l'identificativo dell'ESL considerata (Esadecimale)
			$idEsl = trim($rigaAssociazione['Codice_esl']);
			$codiceSerbatoio = trim($rigaAssociazione['Codice_contenitore']);
			
			//Per ciascuna esl, preparo la stringa contenente l'associazione 'serbatoio - esl' che devo far eseguire la sistema Profimax
			$datiAssociazione .= $codiceSerbatoio.";".$idEsl.";;;;;;\n";
		}
		
		
		$sthDissociazioneESL = $conn_mes->prepare("SELECT Esl_appoggio.*, ".$nomeTabella.".*, Contenitori.Codice_contenitore 
													  FROM Esl_appoggio 
													  LEFT JOIN ".$nomeTabella." 
													  ON Esl_appoggio.Codice_esl = ".$nomeTabella.".Codice_etichetta 
													  LEFT JOIN Contenitori 
													  ON ".$nomeTabella.".Numero_serbatoio = Contenitori.Codice_contenitore 
													  WHERE Esl_appoggio.Stato_associazione = 0 
													  AND Esl_appoggio.Stato_associazione != Esl_appoggio.Stato_associazione_precedente
													  OR (Esl_appoggio.Stato_associazione = 1 
													  AND Esl_appoggio.Articolo_precedente != Esl_appoggio.Articolo_attuale) 
													  AND ".$nomeTabella.".Abilitazione = 1", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
		$sthDissociazioneESL->execute();
		$righeDissociazioneESL = $sthDissociazioneESL->fetchAll(PDO::FETCH_ASSOC);		

		foreach ($righeDissociazioneESL as $rigaDissociazione) {	
			//Estraggo dal recordset i dati di interesse realtivi ai silos e preparo le relative stringhe da aggiungere al file che gestisce l'import degli articoli nel sistema Profimax
			//Determino l'identificativo dell'ESL considerata (Esadecimale)
			$idEsl = trim($rigaDissociazione['Codice_esl']);
			
			//Per ciascuna esl, preparo la stringa contenente l'associazione 'serbatoio - esl' che devo far eseguire la sistema Profimax
			$datiDissociazione .= "{OFF};".$idEsl.";;;;;;\n";
		}
		
		
		//Aggiornamento su DB: registrazione ultima associazione
		$queryUpdateAssociazione = "UPDATE Esl_appoggio 
									SET Stato_associazione_precedente = Stato_associazione";
		$sthUpdateAssociazione = $conn_mes->prepare($queryUpdateAssociazione);
		$sthUpdateAssociazione->execute();	
		
		
		//Aggiornamento su DB: registrazione ultimo serbatoio associato
		$queryUpdateUltimoSerbatoioAssociato = "UPDATE Esl_appoggio 
										 SET Articolo_precedente = Articolo_attuale";
		$sthUpdateUltimoSerbatoioAssociato = $conn_mes->prepare($queryUpdateUltimoSerbatoioAssociato);
		$sthUpdateUltimoSerbatoioAssociato->execute();	
	

		//Ottengo l'ora di sistema per gestire la nomenclatura dei file
		$data = time();
		
		date_default_timezone_set('Europe/Rome');
		$dataSistemaAttuale = date('YmdHis', $data);
		

		//GESTIONE ASSOCIAZIONE SERBATOI-ESL
		//Apertura in scrittura del file tracciato Profimax EXECUTE, relativo all'esecuzione dell'associazione/diassociazione ESL-Serbatoi
		//$fileESLAssociate = fopen("\\\\10.8.2.102\\Import\\Execute_".$dataSistemaAttuale.".job", "wb") or die("Errore nell'apertura del file di configurazione!");
		$fileESLAssociate = fopen("C:\\Import_SwMediasoft\\Execute_".$dataSistemaAttuale.".job", "wb") or die("Errore nell'apertura del file di configurazione!");
				
		//Scrittura su file della necessaria intestazione
		fwrite($fileESLAssociate, pack("CCC",0xef,0xbb,0xbf));
		
		//Scrivo su file la stringa relativa al tracciato di EXECUTE
		fwrite($fileESLAssociate, $datiDissociazione.$datiAssociazione);
		
		//Chiusura handle di lettura file
		fclose($fileESLAssociate);
		
		$conn_mes->commit();
		die('OK');
		
	} catch (Throwable $t) {
		$conn_mes->rollBack();
		die("ERRORE: " . $t);
	}	
}