<?php
	// in che pagina siamo
	$pagina = 'registrazione_downtime';

	include("../inc/conn.php");


	// INVOCO PROCEDURA DI RICEZIONE SEGNALI DA SCADA
	if(!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'write-downtime')
	{

		// ----------------------------------------------- BLOCCO REGISTRAZIONE DOWNTIME DA SCADA -----------------------------------------------

		//$log = $dataOdierna." ".$oraOdierna.": INVOCATA PROCEDURA\n";
		//file_put_contents('C:\MES_log\DTLog\DTUpdate_log_'.date('Ymd').'.log', $log, FILE_APPEND);

		$aggiornaDTLinea = False;
		$aggiornaDTRisorsa = False;
		$dtProduzioneRilevato = False;

		try {
			$conn_mes->beginTransaction();

			// recupero informazioni sulla risorsa che sta eseguendo l'ordine
			$sthRecuperaRisorse = $conn_mes->prepare(
				"SELECT risorse.* FROM risorse
				WHERE risorse.ris_StatoOrdine = 'OK'",
				array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL)
			);
			$sthRecuperaRisorse->execute();

			if ($sthRecuperaRisorse->rowCount() > 0) {

				$risorse = $sthRecuperaRisorse->fetchAll(PDO::FETCH_ASSOC);

				// Scorro il recordset delle risorse trovate
				foreach($risorse as $risorsa) {

					$idProduzioneInCorso = $risorsa['ris_IdProduzione'];
					$idLineaProduzione = $risorsa['ris_LineaProduzione'];

					// Recupero informazioni sulla risorsa che sta eseguendo l'ordine
					$sthRecuperaDTLinea = $conn_mes->prepare("SELECT COUNT(*) AS DtAperto
															FROM linea_downtime
															WHERE linea_downtime.ldt_IdProduzione = :IdProduzione AND ldt_DataFine IS NULL AND ldt_OraFine IS NULL", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
					$sthRecuperaDTLinea->execute(array(':IdProduzione' => $idProduzioneInCorso));
					$rigaRecuperaDTLinea = $sthRecuperaDTLinea->fetch(PDO::FETCH_ASSOC);
					$statoDTLinea = $rigaRecuperaDTLinea['DtAperto'];


					// Calcolo OR (manuale e da scada) dei segnali di AVARIA, RUN e ATTREZZAGGIO
					$orAvaria = $risorsa['ris_Avaria_Man'] || $risorsa['ris_Avaria_Scada'];
					$orRun = $risorsa['ris_Run_Man'] || $risorsa['ris_Run_Scada'];
					$orAttrezzaggio = $risorsa['ris_Attrezzaggio_Man'] || $risorsa['ris_Attrezzaggio_Scada'];

					// Imposto data e ora odierne
					$dataOdierna = date('Y-m-d');
					$oraOdierna = date('H:i:s');

					$aggiornaDTRisorsa = False;
					$aggiornaDTLinea = False;


					// Se (NO DOWNTIME e (NO RUN o AVARIA o ATTREZZAGGIO))
					if (!$risorsa['ris_Downtime']) {

						if (!$orRun || $orAvaria || $orAttrezzaggio) {

							$dtRilevato = True;
							$aggiornaDTRisorsa = True;

							$sqlGestioneDowntime = "INSERT INTO risorsa_downtime(rdt_IdProduzione,rdt_IdRisorsa,rdt_DataInizio,rdt_OraInizio) VALUES(:IdProduzione,:IdRisorsa,:DataInizio,:OraInizio)";

							$sthGestioneDowntime = $conn_mes->prepare($sqlGestioneDowntime);
							$sthGestioneDowntime->execute(array(
								':IdProduzione' => $risorsa['ris_IdProduzione'],
								':IdRisorsa' => $risorsa['ris_IdRisorsa'],
								':DataInizio' => $dataOdierna,
								':OraInizio' => $oraOdierna
							));


							// Aggiorno lo stato di DOWNTIME della risorsa settandolo a TRUE
							$sqlUpdateRisorse = "UPDATE risorse SET
									ris_Downtime = 1
									WHERE ris_IdRisorsa = :IdRisorsa";

							$sthUpdateRisorse = $conn_mes->prepare($sqlUpdateRisorse);
							$sthUpdateRisorse->execute(array(
								':IdRisorsa' => $risorsa['ris_IdRisorsa']
							));


							// Se non esiste una registrazione di downtime di linea già aperta
							if ($statoDTLinea == 0) {

								$aggiornaDTLinea = true;

								$sqlGestioneDowntimeLinea = "INSERT INTO linea_downtime(ldt_IdProduzione,ldt_IdLinea,ldt_DataInizio,ldt_OraInizio) VALUES(:IdProduzione,:IdLineaProduzione,:DataInizio,:OraInizio)";

								$sthGestioneDowntimeLinea = $conn_mes->prepare($sqlGestioneDowntimeLinea);
								$sthGestioneDowntimeLinea->execute(array(
									':IdProduzione' => $idProduzioneInCorso,
									':IdLineaProduzione' => $idLineaProduzione,
									':DataInizio' => $dataOdierna,
									':OraInizio' => $oraOdierna
								));
							}
						}


					}
					// se (DOWNTIME e RUN e NO AVARIA e NO ATTREZZAGGIO )
					else if ($risorsa['ris_Downtime']) {

						if ($orRun && !$orAvaria && !$orAttrezzaggio) {

							$aggiornaDTRisorsa = True;

							// Chiudo la entry di DOWNTIME aperta nella tabella 'risorsa_downtime'
							$sqlGestioneDowntime = "UPDATE risorsa_downtime SET
									rdt_DataFine = :DataFine,
									rdt_OraFine = :OraFine
									WHERE rdt_IdProduzione = :IdProduzione AND rdt_IdRisorsa = :IdRisorsa AND rdt_dataFine IS NULL";


							// Aggiorno lo stato di DOWNTIME della risorsa settandolo a FALSE
							$sthGestioneDowntime = $conn_mes->prepare($sqlGestioneDowntime);
							$sthGestioneDowntime->execute(array(
								':DataFine' => $dataOdierna,
								':OraFine' => $oraOdierna,
								':IdProduzione' => $risorsa['ris_IdProduzione'],
								':IdRisorsa' => $risorsa['ris_IdRisorsa']
							));


							// Aggiorno lo stato di DOWNTIME della risorsa settandolo a FALSE
							$sqlUpdateRisorse = "UPDATE risorse SET
									ris_Downtime = 0
									WHERE ris_IdRisorsa = :IdRisorsa";

							$sthUpdateRisorse = $conn_mes->prepare($sqlUpdateRisorse);
							$sthUpdateRisorse->execute(array(
								':IdRisorsa' => $risorsa['ris_IdRisorsa']
							));
						}
						else {
							$dtRilevato = True;
						}

					}
				}


				$sqlGestioneDowntimeLineaRientro =
						"UPDATE linea_downtime SET
						ldt_DataFine = :DataFine,
						ldt_OraFine = :OraFine
						WHERE ldt_DataFine IS NULL AND ldt_IdProduzione NOT IN
						(SELECT risorse.ris_IdProduzione
						FROM risorse
						WHERE risorse.ris_Downtime != 0)";

				$sthGestioneDowntimeLineaRientro = $conn_mes->prepare($sqlGestioneDowntimeLineaRientro);
				$sthGestioneDowntimeLineaRientro->execute(array(
					':DataFine' => $dataOdierna,
					':OraFine' => $oraOdierna
				));

				$conn_mes->commit();

			}
		}
		catch (Throwable $th) {
			$conn_mes->rollBack();
			$log = "ERRORE REGISTRAZIONE DOWNTIME - ".$oraOdierna.";\n";
			file_put_contents('C:\MES_log\DTLog\DTUpdate_log_'.date('Ymd').'.log', $log, FILE_APPEND);
		}
	}


?>