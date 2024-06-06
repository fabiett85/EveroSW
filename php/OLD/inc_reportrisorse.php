<?php
// in che pagina siamo
$pagina = 'inc_reportrisorse';
require_once('../inc/conn.php');

if (!empty($_REQUEST['azione'])) {
	// REPORT RENDIMENTO RISORSE: RECUPERO VALORI OEE (GRAFICO E TABELLA)
	if ($_REQUEST['azione'] == 'rptRis-recupera-oee-periodo') {

		$sthProduzioniPeriodo = $conn_mes->prepare(
			"SELECT * FROM risorsa_produzione
			LEFT JOIN risorse ON rp_IdRisorsa = ris_IdRisorsa
			LEFT JOIN ordini_produzione ON rp_IdProduzione = op_IdProduzione
			LEFT JOIN prodotti ON op_Prodotto = prd_idProdotto
			LEFT JOIN efficienza_risorse ON rp_IdRisorsa = er_IdRisorsa AND prd_IdProdotto = er_IdProdotto
			LEFT JOIN unita_misura ON op_Udm = um_IdRiga
			WHERE rp_IdRisorsa = :IdRisorsa AND op_Prodotto LIKE :IdProdotto
			AND rp_DataInizio >= :DataInizioPeriodo AND rp_DataFine <= :DataFinePeriodo
			ORDER BY rp_DataInizio ASC"
		);
		$sthProduzioniPeriodo->execute([
			':IdRisorsa' => $_REQUEST['idRisorsa'],
			':IdProdotto' => $_REQUEST['idProdotto'],
			':DataInizioPeriodo' => $_REQUEST['dataInizioPeriodo'],
			':DataFinePeriodo' => $_REQUEST['dataFinePeriodo']
		]);

		$righe = $sthProduzioniPeriodo->fetchAll(PDO::FETCH_ASSOC);
		$output = [];
		foreach ($righe as $riga) {

			// formatto adeguatamente le stringhe per la 'data/ora inizio' e per la 'data/ora fine'
			if (isset($riga['rp_DataInizio'])) {
				$dInizio = new DateTime(trim($riga['rp_DataInizio']) . ' ' . trim($riga['rp_OraInizio']));
				$stringaDataInizio = $dInizio->format('d/m/Y H:i:s');
			}


			if (isset($riga['rp_DataFine'])) {
				$dFine = new DateTime(trim($riga['rp_DataFine']) . ' ' . trim($riga['rp_OraFine']));
				$stringaDataFine = $dFine->format('d/m/Y H:i:s');
			}

			$dDataOrdine = new DateTime($riga['rp_DataInizio']);
			$stringDataordine = $dDataOrdine->format('d/m/Y');

			//Preparo i dati da visualizzare
			$output[] = [
				'IdProduzione' => $riga['rp_IdProduzione'],
				'DescrizioneProdotto' => $riga['prd_Descrizione'],
				'QtaProdotta' => $riga['rp_QtaProdotta'] . ' ' . $riga['um_Sigla'],
				'QtaConforme' => $riga['rp_QtaConforme'] . ' ' . $riga['um_Sigla'],
				'DataInizio' => $stringaDataInizio,
				'DataFine' => $stringaDataFine,
				'VelocitaRisorsa' => $riga['rp_VelocitaRisorsa'],
				'DRisorsa' => round($riga['rp_D'], 2),
				'ERisorsa' => round($riga['rp_E'], 2),
				'QRisorsa' => round($riga['rp_Q'], 2),
				'OEERisorsa' => round($riga['rp_OEE'], 2),
				'OEEMedioProdotto' => round($riga['er_OEEMedio'], 2),
				'NoteRisorsa' => $riga['rp_NoteFine'],
				'IdProduzioneGrafico' => $riga['rp_IdProduzione'] . ' (' . $stringDataordine . ')'
			];
		}

		die(json_encode($output));
	}

	// REPORT RENDIMENTO RISORSE: RECUPERO VALORI MEDI (D, E, Q, OEE)
	if ($_REQUEST['azione'] == 'rptRis-calcola-valori-medi') {

		$sthValoriMedi = $conn_mes->prepare(
			"SELECT COUNT(*) AS TotaleOrdini, SUM(rp_D) AS SommaD, SUM(rp_E) AS SommaE, SUM(rp_Q) AS SommaQ, SUM(rp_OEE) AS SommaOEE
			FROM risorsa_produzione
			LEFT JOIN ordini_produzione ON risorsa_produzione.rp_IdProduzione = ordini_produzione.op_IdProduzione
			LEFT JOIN prodotti ON ordini_produzione.op_Prodotto = prodotti.prd_idProdotto
			WHERE risorsa_produzione.rp_IdRisorsa = :IdRisorsa AND ordini_produzione.op_Prodotto LIKE :IdProdotto
			AND rp_DataInizio >= :DataInizioPeriodo AND rp_DataFine <= :DataFinePeriodo"
		);
		$sthValoriMedi->execute(
			[
				':IdRisorsa' => $_REQUEST['idRisorsa'],
				':IdProdotto' => $_REQUEST['idProdotto'],
				':DataInizioPeriodo' => $_REQUEST['dataInizioPeriodo'],
				':DataFinePeriodo' => $_REQUEST['dataFinePeriodo']
			]
		);

		$rigaValoriMedi = $sthValoriMedi->fetch(PDO::FETCH_ASSOC);

		$fattoreDMedio = round(floatval($rigaValoriMedi['SommaD'] / ($rigaValoriMedi['TotaleOrdini'] == 0 ? 1 : $rigaValoriMedi['TotaleOrdini'])), 2);
		$fattoreEMedio = round(floatval($rigaValoriMedi['SommaE'] / ($rigaValoriMedi['TotaleOrdini'] == 0 ? 1 : $rigaValoriMedi['TotaleOrdini'])), 2);
		$fattoreQMedio = round(floatval($rigaValoriMedi['SommaQ'] / ($rigaValoriMedi['TotaleOrdini'] == 0 ? 1 : $rigaValoriMedi['TotaleOrdini'])), 2);
		$OEEMedio = round(floatval($rigaValoriMedi['SommaOEE'] / ($rigaValoriMedi['TotaleOrdini'] == 0 ? 1 : $rigaValoriMedi['TotaleOrdini'])), 2);

		$output = [
			'FattoreDMedio' => ($fattoreDMedio > 100 ? 100 : $fattoreDMedio),
			'FattoreEMedio' => ($fattoreEMedio > 100 ? 100 : $fattoreEMedio),
			'FattoreQMedio' => ($fattoreQMedio > 100 ? 100 : $fattoreQMedio),
			'OEEMedio' => ($OEEMedio > 100 ? 100 : $OEEMedio)
		];

		die(json_encode($output));
	}

	// REPORT RENDIMENTO RISORSE: RECUPERA INFORMAZIONI SU COMMESSA SELEZIONATO
	if ($_REQUEST['azione'] == 'rptRis-mostra-dettaglio-ordine') {

		// recupero i dati del dettaglio distinta selezionato
		$sthRecuperaDettaglio = $conn_mes->prepare(
			"SELECT * FROM risorsa_produzione
			LEFT JOIN risorse ON rp_IdRisorsa = ris_IdRisorsa
			LEFT JOIN ordini_produzione ON rp_IdProduzione = op_IdProduzione
			LEFT JOIN prodotti ON op_Prodotto = prd_IdProdotto
			LEFT JOIN unita_misura ON op_Udm = um_IdRiga
			WHERE rp_IdProduzione = :IdProduzione"
		);
		$sthRecuperaDettaglio->execute([':IdProduzione' => $_REQUEST['idProduzione']]);
		$rigaDettaglio = $sthRecuperaDettaglio->fetch(PDO::FETCH_ASSOC);

		// formatto adeguatamente le stringhe per la 'data/ora inizio' e per la 'data/ora fine'
		if (isset($rigaDettaglio['rp_DataInizio'])) {
			$dInizio = new DateTime($rigaDettaglio['rp_DataInizio'] . ' ' . $rigaDettaglio['rp_OraInizio']);
			$stringaDataInizio = $dInizio->format('d/m/Y - H:i:s');
		}

		if (isset($rigaDettaglio['rp_DataFine'])) {
			$dFine = new DateTime($rigaDettaglio['rp_DataFine'] . ' ' . $rigaDettaglio['rp_OraFine']);
			$stringaDataFine = $dFine->format('d/m/Y H:i:s');
		}

		$output = [];

		//Preparo i dati da visualizzare
		$output[] = [
			'IdProduzione' => $rigaDettaglio['rp_IdProduzione'],
			'DescrizioneProdotto' => $rigaDettaglio['prd_Descrizione'],
			'DataInizio' => $stringaDataInizio,
			'DataFine' => $stringaDataFine,
			'QtaProdotta' => $rigaDettaglio['rp_QtaProdotta'] . ' ' . $rigaDettaglio['um_Sigla'],
			'QtaConforme' => $rigaDettaglio['rp_QtaConforme'] . ' ' . $rigaDettaglio['um_Sigla'],
			'TTotale' => $rigaDettaglio['rp_TTotale'],
			'Downtime' => $rigaDettaglio['rp_Downtime'],
			'TAttrezzaggio' => $rigaDettaglio['rp_Attrezzaggio'],
			'DeltaAttrezzaggio' => floatval($rigaDettaglio['rp_Attrezzaggio'] - $rigaDettaglio['ris_TTeoricoAttrezzaggio']),
			'VelocitaLinea' => $rigaDettaglio['rp_VelocitaRisorsa'],
			'D' => round($rigaDettaglio['rp_D'], 2),
			'E' => round($rigaDettaglio['rp_E'], 2),
			'Q' => round($rigaDettaglio['rp_Q'], 2),
			'OEELinea' => round($rigaDettaglio['rp_OEE'], 2),
			'NoteFine' => $rigaDettaglio['rp_NoteFine']
		];

		die(json_encode($output));
	}

	// REPORT RENDIMENTO RISORSE: DETTAGLIO CASI PER COMMESSA SELEZIONATO
	if ($_REQUEST['azione'] == 'rptRis-mostra-casi-ordine') {

		$idRisorsa = $_REQUEST['idRisorsa'];
		// seleziono i dati della tabella di lavoro 'risorse_coinvolte_work'
		$sth = $conn_mes->prepare(
			"SELECT * FROM attivita_casi
			LEFT JOIN casi ON ac_IdEvento = cas_IdEvento AND ac_IdRisorsa = cas_IdRisorsa
			LEFT JOIN risorse ON ac_IdRisorsa = ris_IdRisorsa
			WHERE ac_IdProduzione = :IdOrdineProduzione AND ac_idRisorsa = :IdRisorsa"
		);
		$sth->execute(
			[
				':IdOrdineProduzione' => $_REQUEST['idProduzione'],
				':IdRisorsa' => $idRisorsa
			]
		);


		$output = [];


		$righe = $sth->fetchAll(PDO::FETCH_ASSOC);

		foreach ($righe as $riga) {

			if (isset($riga['ac_DataInizio'])) {
				$dInizio = new DateTime($riga['ac_DataInizio'] . ' ' . $riga['ac_OraInizio']);
				$stringaDataInizio = $dInizio->format('d/m/Y H:i:s');
			} else {
				$stringaDataInizio = "";
			}

			if (isset($riga['ac_DataFine'])) {
				$dFine = new DateTime($riga['ac_DataFine'] . ' ' . $riga['ac_OraFine']);
				$stringaDataFine = $dFine->format('d/m/Y H:i:s');
			} else {
				$dataOdierna = date('Y-m-d');
				$oraOdierna = date('H:i:s');
				$dFine = new DateTime($dataOdierna . ' ' . $oraOdierna);
				$stringaDataFine = "";
			}
			$durataEvento = $dFine->diff($dInizio);

			if ($riga['cas_Tipo'] == 'KO') {
				$tipoEvento = 'AVARIA';
			} else if ($riga['cas_Tipo'] == 'KK') {
				$tipoEvento = 'FERMO';
			} else if ($riga['cas_Tipo'] == 'OK') {
				$tipoEvento = 'NON BLOC.';
			} else if ($riga['cas_Tipo'] == 'AT') {
				$tipoEvento = 'ATTR.';
			}

			$durataEvento_sec = floatval(($durataEvento->days * 3600 * 24) + ($durataEvento->h * 3600) + ($durataEvento->i * 60) + $durataEvento->s);
			$durataEvento_min = intval($durataEvento_sec / 60);

			//Preparo i dati da visualizzare
			$output[] = [

				'DescrizioneRisorsa' => $riga['ris_Descrizione'],
				'DescrizioneCaso' => $riga['ac_DescrizioneEvento'],
				'TipoEvento' => $tipoEvento,
				'DataInizio' => $stringaDataInizio,
				'DataFine' => $stringaDataFine,
				'Durata' => (float)$durataEvento_min,
				'Note' =>  $riga['ac_Note']
			];
		}

		die(json_encode($output));
	}

	// DETTAGLIO CASI REGISTRATI PER PRODUZIONE IN OGGETTO
	if ($_REQUEST['azione'] == 'rptRis-mostra-casi-produzione-cumulativo') {

		$idRisorsa = $_REQUEST['idRisorsa'];
		// seleziono i dati della tabella di lavoro 'risorse_coinvolte_work'
		$sth = $conn_mes->prepare(
			"SELECT ac_DescrizioneEvento, ac_Note, ris_Descrizione, cas_Tipo, COUNT(*) As TotaleEventi,
			SUM(
				DATEDIFF(MINUTE,
					CONVERT(Datetime, (CONCAT(ac_DataInizio, 'T', ac_OraInizio))),
					CONVERT(Datetime, (CONCAT(ac_DataFine, 'T', ac_OraFine)))
				)
			) AS TotaleDowntimeTipo
			FROM attivita_casi
			LEFT JOIN casi ON ac_IdEvento = cas_IdEvento AND ac_IdRisorsa = cas_IdRisorsa
			LEFT JOIN risorse ON ac_IdRisorsa = ris_IdRisorsa
			WHERE ac_IdProduzione = :IdOrdineProduzione AND ac_idRisorsa = :IdRisorsa
			GROUP BY ac_DescrizioneEvento, ac_Note, ris_Descrizione , cas_Tipo"
		);
		$sth->execute([
			':IdOrdineProduzione' => $_REQUEST['idProduzione'],
			':IdRisorsa' => $idRisorsa
		]);



		// seleziono i dati della tabella di lavoro 'risorse_coinvolte_work'

		$output = [];


		$righe = $sth->fetchAll(PDO::FETCH_ASSOC);

		foreach ($righe as $riga) {


			if ($riga['cas_Tipo'] == 'KO') {
				$tipoEvento = 'AVARIA';
			} else if ($riga['cas_Tipo'] == 'KK') {
				$tipoEvento = 'FERMO';
			} else if ($riga['cas_Tipo'] == 'OK') {
				$tipoEvento = 'NON BLOC.';
			} else if ($riga['cas_Tipo'] == 'AT') {
				$tipoEvento = 'ATTR.';
			}

			//Preparo i dati da visualizzare
			$output[] = [
				'DescrizioneRisorsa' => $riga['ris_Descrizione'],
				'DescrizioneCaso' => $riga['ac_DescrizioneEvento'],
				'TipoEvento' => $tipoEvento,
				'NumeroEventi' => $riga['TotaleEventi'],
				'Durata' => (float)$riga['TotaleDowntimeTipo'],
				'Note' =>  $riga['ac_Note']
			];
		}

		die(json_encode($output));
	}

	// AUSILIARIA: POPOLAMENTO SELECT RISORSE IN BASE A LINEA SELEZIONATA
	if ($_REQUEST['azione'] == 'rptRis-carica-select-risorse') {
		if ($_REQUEST['idLineaProduzione'] == '_') {
			$sth = $conn_mes->prepare(
				"SELECT * FROM risorse"
			);
			$sth->execute();
		} else {
			$sth = $conn_mes->prepare(
				"SELECT * FROM risorse
				WHERE risorse.ris_LineaProduzione = :IdLineaProduzione"
			);
			$sth->execute([':IdLineaProduzione' => $_REQUEST['idLineaProduzione']]);
		}

		$risorse = $sth->fetchAll(PDO::FETCH_ASSOC);
		$optionValue = "";


		foreach ($risorse as $risorsa) {

			if (!empty($_REQUEST['idRisorsa']) && $_REQUEST['idRisorsa'] == $risorsa['ris_IdRisorsa']) {
				$optionValue = $optionValue . "<option value='" . $risorsa['ris_IdRisorsa'] . "' selected>" . strtoupper($risorsa['ris_Descrizione']) . " </option>";
			} else {
				$optionValue = $optionValue . "<option value='" . $risorsa['ris_IdRisorsa'] . "'>" . strtoupper($risorsa['ris_Descrizione']) . " </option>";
			}
		}

		die($optionValue);
	}

	// AUSILIARIA: POPOLAMENTO SELECT PRODOTTI IN BASE A RISORSA SELEZIONATA
	if ($_REQUEST['azione'] == 'rptRis-carica-select-prodotti') {
		// estraggo gli eventuali prodotti aggiuntivi
		$sth = $conn_mes->prepare(
			"SELECT DISTINCT prd_IdProdotto, prd_Descrizione FROM risorsa_produzione
			LEFT JOIN ordini_produzione ON rp_IdProduzione = op_IdProduzione
			LEFT JOIN prodotti ON op_Prodotto = prd_IdProdotto
			WHERE rp_IdRisorsa = :IdRisorsa
			AND rp_DataFine IS NOT NULL"
		);
		$sth->execute([':IdRisorsa' => $_REQUEST['idRisorsa']]);
		$prodotti = $sth->fetchAll(PDO::FETCH_ASSOC);
		$optionValue = "";

		//Se ho trovato sottocategorie
		if ($prodotti) {

			$optionValue = $optionValue . "<option value='%'>TUTTI</option>";

			//Aggiungo ognuna delle sottocategorie trovate alla stringa che conterrà le possibili opzioni della select categorie, e che ritorno come risultato
			foreach ($prodotti as $prodotto) {

				//Se ho già una sottocategoria selezionata (provengo da popup 'di modifica'), preparo il contenuto della select con l'option value corretto selezionato altrimenti preparo solo il contenuto.
				if (!empty($_REQUEST['idProdotto']) && $_REQUEST['idProdotto'] == $prodotto['prd_IdProdotto']) {
					$optionValue = $optionValue . "<option value='" . $prodotto['prd_IdProdotto'] . "' selected>" . $prodotto['prd_Descrizione'] . " </option>";
				} else {
					$optionValue = $optionValue . "<option value='" . $prodotto['prd_IdProdotto'] . "'>" . $prodotto['prd_Descrizione'] . " </option>";
				}
			}
		} else {
			$optionValue = "<option value='%'>TUTTI</option>";
		}
		die($optionValue);
	}
}
