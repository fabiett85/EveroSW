<?php
// in che pagina siamo
$pagina = 'inc_reportdiagnostica';
require_once('../inc/conn.php');




// AUSILIARIA: POPOLAMENTO SELECT RISORSE IN BASE ALLA LINEA SELEZIONATA
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == "rptDia-carica-select-risorse" && !empty($_REQUEST['idLineaProduzione'])) {

	if ($_REQUEST['idLineaProduzione'] == '_') {
		$sth = $conn_mes->prepare("SELECT risorse.*
										FROM risorse", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
		$sth->execute();
		$optionValueMostraTutte = "<option value='_' selected>TUTTE</option>";
	} else {
		$sth = $conn_mes->prepare("SELECT risorse.*
										FROM risorse
										WHERE risorse.ris_LineaProduzione = :IdProduzione", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
		$sth->execute([':IdProduzione' => $_REQUEST['idLineaProduzione']]);
		$optionValueMostraTutte = "<option value='_'>TUTTE</option>";
	}
	$risorse = $sth->fetchAll(PDO::FETCH_ASSOC);
	$optionValue = "";

	//Se ho trovato sottocategorie
	if ($risorse) {

		$optionValue = $optionValue . $optionValueMostraTutte;

		//Aggiungo ognuna delle sottocategorie trovate alla stringa che conterrà le possibili opzioni della select categorie, e che ritorno come risultato
		foreach ($risorse as $risorsa) {

			//Se ho già una sottocategoria selezionata (provengo da popup "di modifica"), preparo il contenuto della select con l'option value corretto selezionato altrimenti preparo solo il contenuto.
			if (!empty($_REQUEST['idRisorsa']) && $_REQUEST['idRisorsa'] == $risorsa['ris_IdRisorsa']) {
				$optionValue = $optionValue . "<option value='" . $risorsa['ris_IdRisorsa'] . "' selected>" . strtoupper($risorsa['ris_Descrizione']) . " </option>";
			} else {
				$optionValue = $optionValue . "<option value='" . $risorsa['ris_IdRisorsa'] . "'>" . strtoupper($risorsa['ris_Descrizione']) . " </option>";
			}
		}
	}

	echo $optionValue;
	exit();
}

if (!empty($_REQUEST['azione'])) {

	// REPORT DIAGNOSTICA: RECUPERO ORE PERSE PER LINEE/MACCHINE (GRAFICO)
	if ($_REQUEST['azione'] == "rptDia-popola-istogramma-linee") {


		// Controllo la risorsa selezionata
		if (!empty($_REQUEST['idRisorsa']) && trim($_REQUEST['idRisorsa']) == '_') {

			// Mostro tutte le linee
			if (trim($_REQUEST['idLineaProduzione']) == '_') {

				$sthProduzioniPeriodo = $conn_mes->prepare(
					"SELECT lp_IdLinea, lp_Descrizione,
					SUM(datediff(
						MINUTE,
						CONCAT(ac_DataInizio,'T',ac_OraInizio),
						CONCAT(ac_DataFine,'T',ac_OraFine)
					)) AS TotaleDowntime FROM attivita_casi
					LEFT JOIN risorse ON ac_IdRisorsa = ris_IdRisorsa
					LEFT JOIN linee_produzione ON ris_LineaProduzione = lp_IdLinea
					WHERE ac_DataInizio >= :DataInizioPeriodo AND ac_DataFine <= :DataFinePeriodo
					GROUP BY lp_IdLinea, lp_Descrizione
					ORDER BY TotaleDowntime DESC"
				);
				$sthProduzioniPeriodo->execute([
					':DataInizioPeriodo' => $_REQUEST['dataInizioPeriodo'],
					':DataFinePeriodo' => $_REQUEST['dataFinePeriodo']
				]);
			} else {
				// Mostro le risorse della linea
				$sthProduzioniPeriodo = $conn_mes->prepare(
					"SELECT ac_IdRisorsa, ris_Descrizione,
					SUM(datediff(
						MINUTE,
						CONCAT(ac_DataInizio,'T',ac_OraInizio),
						CONCAT(ac_DataFine,'T',ac_OraFine)
					)) AS TotaleDowntime FROM attivita_casi
					LEFT JOIN risorse ON ac_IdRisorsa = ris_IdRisorsa
					WHERE ris_LineaProduzione = :IdLineaProduzione
					AND ac_DataInizio >= :DataInizioPeriodo
					AND ac_DataFine <= :DataFinePeriodo
					GROUP BY ris_IdRisorsa, ac_IdRisorsa, ris_Descrizione
					ORDER BY TotaleDowntime DESC"
				);
				$sthProduzioniPeriodo->execute([
					':IdLineaProduzione' => $_REQUEST['idLineaProduzione'],
					':DataInizioPeriodo' => $_REQUEST['dataInizioPeriodo'],
					':DataFinePeriodo' => $_REQUEST['dataFinePeriodo']
				]);
			}
		} else {
			$sthProduzioniPeriodo = $conn_mes->prepare(
				"SELECT ac_DescrizioneCaso,
				SUM(datediff(
					MINUTE,
					CONCAT(ac_DataInizio,'T',ac_OraInizio),
					CONCAT(ac_DataFine,'T',ac_OraFine)
				)) AS TotaleDowntime FROM attivita_casi
				WHERE ac_IdRisorsa = :IdRisorsa
				AND  ac_DataInizio >= :DataInizioPeriodo
				AND ac_DataFine <= :DataFinePeriodo
				GROUP BY ac_IdCaso, ac_DescrizioneCaso
				ORDER BY TotaleDowntime DESC"
			);
			$sthProduzioniPeriodo->execute([
				':IdRisorsa' => $_REQUEST['idRisorsa'],
				':DataInizioPeriodo' => $_REQUEST['dataInizioPeriodo'],
				':DataFinePeriodo' => $_REQUEST['dataFinePeriodo']
			]);
		}
		$righe = $sthProduzioniPeriodo->fetchAll();

		$output = [];
		foreach ($righe as $riga) {
			if (!empty($_REQUEST['idRisorsa']) && trim($_REQUEST['idRisorsa']) == '_') {
				if (trim($_REQUEST['idLineaProduzione']) == '_') {
					$output[] = [
						'Label' => strtoupper($riga['lp_Descrizione']),
						'Dati' => $riga['TotaleDowntime']
					];
				} else {
					$output[] = [
						'Label' => strtoupper($riga['ris_Descrizione']),
						'Dati' => $riga['TotaleDowntime']
					];
				}
			} else {
				$output[] = [
					'Label' => strtoupper($riga['ac_DescrizioneCaso']),
					'Dati' => $riga['TotaleDowntime']
				];
			}
		}

		die(json_encode($output));
	}

	// REPORT DIAGNOSTICA: RECUPERO DETTAGLIO DOWNTIME PER MACCHINA (GRAFICO)
	if ($_REQUEST['azione'] == "rptDia-recupera-dettaglio-downtime-risorsa") {

		// ricavo informazioni sulle risorse definite
		$sthRecuperaIdCaso = $conn_mes->prepare(
			"SELECT * FROM casi
			WHERE casi.cas_IdRisorsa = :IdRisorsa"
		);
		$sthRecuperaIdCaso->execute([':IdRisorsa' => $_REQUEST['idRisorsa']]);
		$righeRecuperaIdCasi = $sthRecuperaIdCaso->fetchAll(PDO::FETCH_ASSOC);

		$outputRecuperaIdCasi = [];

		// compongo i risultati da restituire
		foreach ($righeRecuperaIdCasi as $rigaRecuperaIdCasi) {
			$outputRecuperaIdCasi[trim($rigaRecuperaIdCasi['cas_DescrizioneCaso'])] = trim($rigaRecuperaIdCasi['cas_IdCaso']);
		}

		$sthDettaglioDowntimeRisorsa = $conn_mes->prepare(
			"SELECT ac_DescrizioneEvento,
			SUM(datediff(
				MINUTE,
				CONCAT(ac_DataInizio,'T',ac_OraInizio),
				CONCAT(ac_DataFine,'T',ac_OraFine)
			)) AS TotaleDowntime
			FROM attivita_casi
			WHERE ac_IdRisorsa = :IdRisorsa
			AND ac_DataInizio >= :DataInizioPeriodo
			AND ac_DataFine <= :DataFinePeriodo
			AND ac_IdCaso = :IdCasoSelezionato
			GROUP BY ac_IdEvento, ac_DescrizioneEvento
			ORDER BY TotaleDowntime DESC"
		);
		$sthDettaglioDowntimeRisorsa->execute([':IdRisorsa' => $_REQUEST['idRisorsa'], ':DataInizioPeriodo' => $_REQUEST['dataInizioPeriodo'], ':DataFinePeriodo' => $_REQUEST['dataFinePeriodo'], ':IdCasoSelezionato' => $outputRecuperaIdCasi[$_REQUEST['tipoCasoSelezionato']]]);

		$righe = $sthDettaglioDowntimeRisorsa->fetchAll(PDO::FETCH_ASSOC);

		$output = [];


		foreach ($righe as $riga) {

			$output[] = [
				'Label' => strtoupper($riga['ac_DescrizioneEvento']),
				'Dati' => $riga['TotaleDowntime']
			];
		}

		die(json_encode($output));
	}

	// REPORT DIAGNOSTICA: RECUPERO DETTAGLIO DOWNTIME PER LINEA DI PRODUZIONE (GRAFICO)
	if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == "rptDia-recupera-dettaglio-downtime-linea" && !empty($_REQUEST['descrizioneLineaSelezionata']) && !empty($_REQUEST['dataInizioPeriodo']) && !empty($_REQUEST['dataFinePeriodo'])) {

		// ricavo informazioni sulle risorse definite
		$sthRecuperaIdLinea = $conn_mes->prepare(
			"SELECT * FROM linee_produzione"
		);
		$sthRecuperaIdLinea->execute();
		$righeRecuperaIdLinea = $sthRecuperaIdLinea->fetchAll(PDO::FETCH_ASSOC);

		$outputRecuperaIdLinea = [];


		// compongo i risultati da restituire
		foreach ($righeRecuperaIdLinea as $rigaRecuperaIdLinea) {
			$outputRecuperaIdLinea[trim(strtolower($rigaRecuperaIdLinea['lp_Descrizione']))] = trim($rigaRecuperaIdLinea['lp_IdLinea']);
		}

		//ancora mySQL controllare
		$sthDettaglioDowntimeLinea = $conn_mes->prepare(
			"SELECT ac_DescrizioneCaso, ris_IdRisorsa,
			SUM(DATEDIFF(
				MINUTE,
				CONVERT(DATETIME, CONCAT(ac_DataInizio,'T',ac_OraInizio)),
				CONVERT(DATETIME, CONCAT(ac_DataFine,'T',ac_OraFine))
			)) AS TotaleDowntime
			FROM attivita_casi
			LEFT JOIN risorse ON ac_IdRisorsa = ris_IdRisorsa
			WHERE ris_LineaProduzione = :IdLineaProduzione
			AND ac_DataInizio >= :DataInizioPeriodo AND ac_DataFine <= :DataFinePeriodo
			GROUP BY ac_IdCaso, ac_DescrizioneCaso, ris_IdRisorsa
			ORDER BY TotaleDowntime DESC",
			[PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]
		);
		$sthDettaglioDowntimeLinea->execute([
			':IdLineaProduzione' => $outputRecuperaIdLinea[strtolower($_REQUEST['descrizioneLineaSelezionata'])],
			':DataInizioPeriodo' => $_REQUEST['dataInizioPeriodo'],
			':DataFinePeriodo' => $_REQUEST['dataFinePeriodo']
		]);

		$righe = $sthDettaglioDowntimeLinea->fetchAll(PDO::FETCH_ASSOC);

		foreach ($righe as $riga) {

			$output[] = [
				'Label' => strtoupper($riga['ac_DescrizioneCaso']),
				'Dati' => $riga['TotaleDowntime']
			];
		}

		die(json_encode($output));
	}
}
