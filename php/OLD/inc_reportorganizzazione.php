<?php
	// in che pagina siamo
	$pagina = "inc_reportorganizzazione";
	require_once("../inc/conn.php");



	// REPORT RENDIMENTO ORGANIZZAZIONE: RECUPERO VALORI OEE-D-E-Q GIORNALIERI (GRAFICO)
	if(!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "rptOrg-calcola-oee-giorno" && !empty($_REQUEST["idLineaProduzione"]) && !empty($_REQUEST["dataInizioPeriodo"]) && !empty($_REQUEST["dataFinePeriodo"]))
	{

		// ricavo informazioni sulle risorse definite
		$sthRecuperaLinee = $conn_mes->prepare("SELECT linee_produzione.*
												FROM linee_produzione", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
		$sthRecuperaLinee->execute();
		$righeRecuperaLinee = $sthRecuperaLinee->fetchAll(PDO::FETCH_ASSOC);

		$outputRecuperaLinee = array();

		$indice = 1;


		// compongo i risultati da restituire
		foreach($righeRecuperaLinee as $rigaRecuperaLinee)
		{
			$outputRecuperaLinee[$rigaRecuperaLinee["lp_IdLinea"]] = "pt_Linea".$indice;
			$indice = $indice + 1;
		}



		$sthRecuperoMonteOre = $conn_mes->prepare("SELECT SUM(".$outputRecuperaLinee[$_REQUEST["idLineaProduzione"]].") As MonteOrePeriodo
													FROM pianificazione_turni
													WHERE pt_Data >= :DataInizioPeriodo AND pt_Data <= :DataFinePeriodo", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
		$sthRecuperoMonteOre->execute(array(":DataInizioPeriodo" => $_REQUEST['dataInizioPeriodo'], ":DataFinePeriodo" => $_REQUEST['dataFinePeriodo']));

		if ($sthRecuperoMonteOre) {
			$rigaMonteOre = $sthRecuperoMonteOre->fetch(PDO::FETCH_ASSOC);



			// Seleziono giorni compresi
			$sthRecuperoGiorniPeriodo = $conn_mes->prepare("SELECT pianificazione_turni.pt_Data, pianificazione_turni.".$outputRecuperaLinee[$_REQUEST["idLineaProduzione"]]."
														FROM pianificazione_turni
														WHERE pt_Data >= :DataInizioPeriodo AND pt_Data <= :DataFinePeriodo", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
			$sthRecuperoGiorniPeriodo->execute(array(":DataInizioPeriodo" => $_REQUEST['dataInizioPeriodo'], ":DataFinePeriodo" => $_REQUEST['dataFinePeriodo']));

			if ($sthRecuperoGiorniPeriodo->rowCount() > 0) {
				$righeRecuperoGiorniPeriodo = $sthRecuperoGiorniPeriodo->fetchAll(PDO::FETCH_ASSOC);

				foreach($righeRecuperoGiorniPeriodo as $rigaRecuperoGiorniPeriodo)
				{


					$dInizioGiornoConsiderato = new DateTime(trim($rigaRecuperoGiorniPeriodo['pt_Data']));
					$giornoConsiderato = $dInizioGiornoConsiderato->format('d/m/Y');

					$monteOreDisponibiliGiorno = floatval($rigaRecuperoGiorniPeriodo[$outputRecuperaLinee[$_REQUEST["idLineaProduzione"]]]);

					$sthRecuperoMonteOreProduzione = $conn_mes->prepare("SELECT rientro_linea_produzione.*, velocita_teoriche.vel_VelocitaTeoricaLinea
																			FROM rientro_linea_produzione
																			LEFT JOIN ordini_produzione ON rientro_linea_produzione.rlp_IdProduzione = ordini_produzione.op_IdProduzione
																			LEFT JOIN velocita_teoriche ON ordini_produzione.op_Prodotto = velocita_teoriche.vel_IdProdotto AND ordini_produzione.op_LineaProduzione = velocita_teoriche.vel_IdLineaProduzione
																			WHERE rlp_DataInizio >= :DataInizioPeriodo AND rlp_DataFine <= :DataFinePeriodo AND ordini_produzione.op_LineaProduzione = :IdLineaProduzione", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
					$sthRecuperoMonteOreProduzione->execute(array(":DataInizioPeriodo" => $rigaRecuperoGiorniPeriodo['pt_Data'], ":DataFinePeriodo" => $rigaRecuperoGiorniPeriodo['pt_Data'], ":IdLineaProduzione" => $_REQUEST['idLineaProduzione']));

					$OEEGiorno = 0.0;
					$OEEPeriodo = 0.0;
					$fattoreDGiorno = 0.0;
					$fattoreEGiorno = 0.0;
					$fattoreQGiorno = 0.0;

					$monteMinutiProduzione = 0;
					$monteMinutiPeriodo = 0;
					$cumulativoUptimePeriodo = 0;
					$cumulativoPezziConformi = 0;
					$cumulativoPezziProdotti = 0;
					$cumulativoPezziTeoriciProdotti = 0;
					$cumulativoTempoProduttivo = 0;

					if ($sthRecuperoMonteOreProduzione->rowCount() > 0) {
						$righeRecuperoMonteOreProduzione = $sthRecuperoMonteOreProduzione->fetchAll(PDO::FETCH_ASSOC);

						foreach($righeRecuperoMonteOreProduzione as $rigaRecuperoMonteOreProduzione)
						{
							$minutiPezzo = round(floatval(60/$rigaRecuperoMonteOreProduzione['vel_VelocitaTeoricaLinea']), 4);
							$pezziSecondo = round(floatval($rigaRecuperoMonteOreProduzione['vel_VelocitaTeoricaLinea'] / 3600), 4);

							$monteMinutiProduzione = $monteMinutiProduzione + round(floatval($rigaRecuperoMonteOreProduzione['rlp_QtaConforme'] * $minutiPezzo), 2);

							$cumulativoUptimePeriodo = 	$cumulativoUptimePeriodo + floatval($rigaRecuperoMonteOreProduzione['rlp_TTotale'] - $rigaRecuperoMonteOreProduzione['rlp_Downtime']);
							$cumulativoPezziConformi = 	$cumulativoPezziConformi + floatval($rigaRecuperoMonteOreProduzione['rlp_QtaConforme']);
							$cumulativoPezziProdotti = 	$cumulativoPezziProdotti + floatval($rigaRecuperoMonteOreProduzione['rlp_QtaProdotta']);

							$cumulativoPezziTeoriciProdotti = $cumulativoPezziTeoriciProdotti + floatval(($rigaRecuperoMonteOreProduzione['rlp_TTotale'] - $rigaRecuperoMonteOreProduzione['rlp_Downtime']) * $pezziSecondo);

						}

						$monteOreProduzione = round(floatval($monteMinutiProduzione / 60), 2);
						$OEEGiorno = round(floatval(($monteOreProduzione / ($monteOreDisponibiliGiorno == 0 ? 1 : $monteOreDisponibiliGiorno)) * 100), 2);

						$fattoreDGiorno = round(floatval((($cumulativoUptimePeriodo / (($monteOreDisponibiliGiorno == 0 ? 1 : $monteOreDisponibiliGiorno) * 3600)) * 100)), 2);
						$fattoreEGiorno = round(floatval(($cumulativoPezziProdotti / ($cumulativoPezziTeoriciProdotti == 0 ? 1 : $cumulativoPezziTeoriciProdotti)) * 100), 2);
						$fattoreQGiorno = round(floatval(($cumulativoPezziConformi / ($cumulativoPezziProdotti == 0 ? 1 : $cumulativoPezziProdotti)) * 100), 2);

						$monteMinutiPeriodo = $monteMinutiPeriodo + $monteMinutiProduzione;
						$monteOreProduzione = 0;
					}

					$tempOEEGiorno = (isset($OEEGiorno)? $OEEGiorno : 0.0);

					$output[] = array(

						"DataGiorno" => $giornoConsiderato,
						"FattoreDGiorno" => ($fattoreDGiorno > 100 ? 100 : $fattoreDGiorno),
						"FattoreEGiorno" => ($fattoreEGiorno > 100 ? 100 : $fattoreEGiorno),
						"FattoreQGiorno" => ($fattoreQGiorno > 100 ? 100 : $fattoreQGiorno),
						"OEEGiorno" => ($tempOEEGiorno > 100 ? 100 : $tempOEEGiorno),
					);
				}

				die(json_encode($output));
			}
			else {
				die("NO_ROWS");
			}
		}
		else {
			die("NO_ROWS");
		}
	}


	// REPORT RENDIMENTO ORGANIZZAZIONE: RECUPERO VALORI OEE-D-E-Q TRACCIATE (CASELLE DI TESTO)
	if(!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "rptOrg-calcola-oee-periodo" && !empty($_REQUEST["idLineaProduzione"]) && !empty($_REQUEST["dataInizioPeriodo"]) && !empty($_REQUEST["dataFinePeriodo"]))
	{
		// ricavo informazioni sulle risorse definite
		$sthRecuperaLinee = $conn_mes->prepare("SELECT linee_produzione.*
												FROM linee_produzione", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
		$sthRecuperaLinee->execute();
		$righeRecuperaLinee = $sthRecuperaLinee->fetchAll(PDO::FETCH_ASSOC);

		$outputRecuperaLinee = array();

		$indice = 1;
		$OEEPeriodo = 0;


		// compongo i risultati da restituire
		foreach($righeRecuperaLinee as $rigaRecuperaLinee)
		{
			$outputRecuperaLinee[$rigaRecuperaLinee["lp_IdLinea"]] = "pt_Linea".$indice;
			$indice = $indice + 1;
		}


		//
		$sthRecuperoMonteOre = $conn_mes->prepare("SELECT SUM(".$outputRecuperaLinee[$_REQUEST["idLineaProduzione"]].") As MonteOrePeriodo
													FROM pianificazione_turni
													WHERE pt_Data >= :DataInizioPeriodo AND pt_Data <= :DataFinePeriodo", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
		$sthRecuperoMonteOre->execute(array(":DataInizioPeriodo" => $_REQUEST['dataInizioPeriodo'], ":DataFinePeriodo" => $_REQUEST['dataFinePeriodo']));
		$rigaMonteOre = $sthRecuperoMonteOre->fetch(PDO::FETCH_ASSOC);


		//
		$sthRecuperoMonteOreProduzione = $conn_mes->prepare("SELECT rientro_linea_produzione.*, velocita_teoriche.vel_VelocitaTeoricaLinea
																FROM rientro_linea_produzione
																LEFT JOIN ordini_produzione ON rientro_linea_produzione.rlp_IdProduzione = ordini_produzione.op_IdProduzione
																LEFT JOIN velocita_teoriche ON ordini_produzione.op_Prodotto = velocita_teoriche.vel_IdProdotto AND ordini_produzione.op_LineaProduzione = velocita_teoriche.vel_IdLineaProduzione
																WHERE rlp_DataInizio >= :DataInizioPeriodo AND rlp_DataFine <= :DataFinePeriodo AND ordini_produzione.op_LineaProduzione = :IdLineaProduzione", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
		$sthRecuperoMonteOreProduzione->execute(array(":DataInizioPeriodo" => $_REQUEST['dataInizioPeriodo'], ":DataFinePeriodo" => $_REQUEST['dataFinePeriodo'], ":IdLineaProduzione" => $_REQUEST['idLineaProduzione']));

		$monteMinutiPeriodo = 0;
		$monteMinutiProduzione = 0;
		$monteOreProduzione = 0;
		$cumulativoUptimePeriodo = 0;
		$cumulativoPezziConformi = 0;
		$cumulativoPezziProdotti = 0;
		$cumulativoPezziTeoriciProdotti = 0;

		if ($sthRecuperoMonteOreProduzione->rowCount() > 0) {
			$righeRecuperoMonteOreProduzione = $sthRecuperoMonteOreProduzione->fetchAll(PDO::FETCH_ASSOC);

			foreach($righeRecuperoMonteOreProduzione as $rigaRecuperoMonteOreProduzione)
			{
				$minutiPezzo = round(floatval(60/($rigaRecuperoMonteOreProduzione['vel_VelocitaTeoricaLinea'] != 0 ? $rigaRecuperoMonteOreProduzione['vel_VelocitaTeoricaLinea'] : 1)), 4);
				$monteMinutiProduzione = $monteMinutiProduzione + round(floatval($rigaRecuperoMonteOreProduzione['rlp_QtaConforme'] * $minutiPezzo), 2);

				$pezziSecondo = round(floatval($rigaRecuperoMonteOreProduzione['vel_VelocitaTeoricaLinea'] / 3600), 4);

				$cumulativoUptimePeriodo = 	$cumulativoUptimePeriodo + floatval($rigaRecuperoMonteOreProduzione['rlp_TTotale'] - $rigaRecuperoMonteOreProduzione['rlp_Downtime']);
				$cumulativoPezziConformi = 	$cumulativoPezziConformi + floatval($rigaRecuperoMonteOreProduzione['rlp_QtaConforme']);
				$cumulativoPezziProdotti = 	$cumulativoPezziProdotti + floatval($rigaRecuperoMonteOreProduzione['rlp_QtaProdotta']);

				$cumulativoPezziTeoriciProdotti = $cumulativoPezziTeoriciProdotti + floatval(($rigaRecuperoMonteOreProduzione['rlp_TTotale'] - $rigaRecuperoMonteOreProduzione['rlp_Downtime']) * $pezziSecondo);
			}

			$monteMinutiPeriodo = $monteMinutiPeriodo + $monteMinutiProduzione;

			$monteOreDisponibili = floatval($rigaMonteOre['MonteOrePeriodo']);
			$monteOreProduzione = round(floatval($monteMinutiPeriodo / 60), 2);

			$fattoreDPeriodo = round(floatval((($cumulativoUptimePeriodo / (($monteOreDisponibili == 0 ? 1 : $monteOreDisponibili) * 3600)) * 100)), 2);
			$fattoreEPeriodo = round(floatval(($cumulativoPezziProdotti / ($cumulativoPezziTeoriciProdotti == 0 ? 1 : $cumulativoPezziTeoriciProdotti)) * 100), 2);
			$fattoreQPeriodo = round(floatval(($cumulativoPezziConformi / ($cumulativoPezziProdotti == 0 ? 1 : $cumulativoPezziProdotti)) * 100), 2);



			$OEEPeriodo = round(floatval(($monteOreProduzione / ($monteOreDisponibili == 0 ? 1 : $monteOreDisponibili)) * 100), 2);

			$tempOEEperiodo = (isset($OEEPeriodo)? $OEEPeriodo : 0.0);

			$output = array(
				"FattoreDPeriodo" => ($fattoreDPeriodo > 100 ? 100 : $fattoreDPeriodo),
				"FattoreEPeriodo" => ($fattoreEPeriodo > 100 ? 100 : $fattoreEPeriodo),
				"FattoreQPeriodo" => ($fattoreQPeriodo > 100 ? 100 : $fattoreQPeriodo),
				"OEEPeriodo" => ($tempOEEperiodo > 100 ? 100 : $tempOEEperiodo)
			);
			die(json_encode($output));
		}
		else {
			die("NO_ROWS");
		}
	}


	// REPORT OEE ORGANIZZAZIONE: RECUPERO DETTAGLIO COMMESSE PER LA GIORNATA SELEZIONATA
	if(!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "rptOrg-recupera-ordini-giorno" && !empty($_REQUEST["idLineaProduzione"]) && !empty($_REQUEST["dataInizioPeriodo"]) && !empty($_REQUEST["dataFinePeriodo"]))
	{
		$sthProduzioniPeriodo = $conn_mes->prepare("SELECT ordini_produzione.*, rientro_linea_produzione.*, prodotti.prd_Descrizione
													FROM ordini_produzione
													LEFT JOIN rientro_linea_produzione ON ordini_produzione.op_IdProduzione = rientro_linea_produzione.rlp_IdProduzione
													LEFT JOIN prodotti ON ordini_produzione.op_Prodotto = prodotti.prd_IdProdotto
													WHERE ordini_produzione.op_LineaProduzione = :IdLineaProduzione AND rlp_DataInizio >= :DataInizioPeriodo AND rlp_DataFine <= :DataFinePeriodo
													ORDER BY rlp_DataInizio ASC", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
		$sthProduzioniPeriodo->execute(array(":IdLineaProduzione" => $_REQUEST['idLineaProduzione'], ":DataInizioPeriodo" => $_REQUEST['dataInizioPeriodo'], ":DataFinePeriodo" => $_REQUEST['dataFinePeriodo']));



		if ($sthProduzioniPeriodo->rowCount() > 0) {

			$righe = $sthProduzioniPeriodo->fetchAll(PDO::FETCH_ASSOC);

			foreach($righe as $riga)
			{

				// formatto adeguatamente le stringhe per la 'data/ora inizio' e per la 'data/ora fine'
				if (isset($riga["rlp_DataInizio"])) {
					$dInizio = new DateTime(trim($riga["rlp_DataInizio"])." ".trim($riga["rlp_OraInizio"]));
					$stringaDataInizio = $dInizio->format('d/m/Y H:i:s');
				}


				if (isset($riga["rlp_DataFine"])) {
					$dFine = new DateTime(trim($riga["rlp_DataFine"])." ".trim($riga["rlp_OraFine"]));
					$stringaDataFine = $dFine->format('d/m/Y H:i:s');
				}


				$dDataOrdine = new DateTime($riga["rlp_DataInizio"]);
				$stringDataordine = $dDataOrdine->format('d/m/Y');

				//Preparo i dati da visualizzare
				$output[] = array(

					"IdProduzione" => $riga["op_IdProduzione"],
					"DescrizioneProdotto" => $riga["prd_Descrizione"],
					"QtaProdotta" => $riga["rlp_QtaProdotta"],
					"QtaConforme" => $riga["rlp_QtaConforme"],
					"DataInizio" => $stringaDataInizio,
					"DataFine" => $stringaDataFine,
					"D" => $riga["rlp_D"],
					"E" => $riga["rlp_E"],
					"Q" => $riga["rlp_Q"],
					"OEELinea" =>$riga["rlp_OEELinea"]
				);
			}


			die(json_encode($output));
		}
		else {
			die("NO_ROWS");
		}

	}

?>
