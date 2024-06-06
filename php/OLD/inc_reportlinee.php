<?php
// in che pagina siamo
$pagina = 'inc_reportlinee';
require_once("../inc/conn.php");

if (!empty($_REQUEST['azione'])) {
	// REPORT RENDIMENTO LINEE: RECUPERO VALORI OEE (GRAFICO E TABELLA)
	if ($_REQUEST['azione'] == 'rptLin-recupera-oee-periodo') {
		unset($_REQUEST['azione']);

		$sthProduzioniPeriodo = $conn_mes->prepare(
			"SELECT * FROM ordini_produzione AS ODP
			LEFT JOIN rientro_linea_produzione AS RLP ON ODP.op_IdProduzione = RLP.rlp_IdProduzione
			LEFT JOIN prodotti AS P ON ODP.op_Prodotto = P.prd_IdProdotto
			LEFT JOIN unita_misura AS UDM ON ODP.op_Udm = UDM.um_IdRiga
			WHERE ODP.op_LineaProduzione = :IdLineaProduzione
			AND ODP.op_Prodotto LIKE :IdProdotto
			AND RLP.rlp_DataInizio >= :DataInizioPeriodo
			AND RLP.rlp_DataFine <= :DataFinePeriodo
			ORDER BY RLP.rlp_DataInizio ASC",
			[PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]
		);
		$sthProduzioniPeriodo->execute([
			':IdLineaProduzione' => $_REQUEST['idLineaProduzione'],
			':IdProdotto' => $_REQUEST['idProdotto'],
			':DataInizioPeriodo' => $_REQUEST['dataInizioPeriodo'],
			':DataFinePeriodo' => $_REQUEST['dataFinePeriodo']
		]);

		$righe = $sthProduzioniPeriodo->fetchAll(PDO::FETCH_ASSOC);
		$output = [];
		foreach ($righe as $riga) {

			// formatto adeguatamente le stringhe per la 'data/ora inizio' e per la 'data/ora fine'
			if (isset($riga['rlp_DataInizio'])) {
				$dInizio = new DateTime(trim($riga['rlp_DataInizio']) . ' ' . trim($riga['rlp_OraInizio']));
				$stringaDataInizio = $dInizio->format('d/m/Y H:i:s');
			}


			if (isset($riga['rlp_DataFine'])) {
				$dFine = new DateTime(trim($riga['rlp_DataFine']) . ' ' . trim($riga['rlp_OraFine']));
				$stringaDataFine = $dFine->format('d/m/Y H:i:s');
			}


			$dDataOrdine = new DateTime($riga['rlp_DataInizio']);
			$stringDataordine = $dDataOrdine->format('d/m/Y');

			//Preparo i dati da visualizzare
			$output[] = [

				'IdProduzione' => $riga['op_IdProduzione'],
				'DescrizioneProdotto' => $riga['prd_Descrizione'],
				'QtaProdotta' => $riga['rlp_QtaProdotta'] . ' ' . $riga['um_Sigla'],
				'QtaConforme' => $riga['rlp_QtaConforme'] . ' ' . $riga['um_Sigla'],
				'DataInizio' => $stringaDataInizio,
				'DataFine' => $stringaDataFine,
				'Lotto' => $riga['op_Lotto'],
				'VelocitaLinea' => $riga['rlp_VelocitaLinea'],
				'D' => round($riga['rlp_D'], 2),
				'E' => round($riga['rlp_E'], 2),
				'Q' => round($riga['rlp_Q'], 2),
				'OEELinea' => round($riga['rlp_OEELinea'], 2),
				'IdProduzioneGrafico' => $riga['op_IdProduzione'] . ' (' . $stringDataordine . ')',
			];
		}


		die(json_encode($output));
	}

	// REPORT RENDIMENTO LINEE: RECUPERO VALORI MEDI (D, E, Q, OEE)
	if ($_REQUEST['azione'] == 'rptLin-calcola-valori-medi') {

		$sthValoriMedi = $conn_mes->prepare(
			"SELECT COUNT(*) AS TotaleOrdini, SUM(rlp_D) AS SommaD, SUM(rlp_E) AS SommaE, SUM(rlp_Q) AS SommaQ, SUM(rlp_OEELinea) AS SommaOEE
			FROM rientro_linea_produzione
			LEFT JOIN ordini_produzione ON rientro_linea_produzione.rlp_IdProduzione = ordini_produzione.op_IdProduzione
			WHERE ordini_produzione.op_LineaProduzione = :IdLineaProduzione AND ordini_produzione.op_Prodotto LIKE :IdProdotto AND rlp_DataInizio >= :DataInizioPeriodo AND rlp_DataFine <= :DataFinePeriodo"
		);
		$sthValoriMedi->execute([
			':IdLineaProduzione' => $_REQUEST['idLineaProduzione'],
			':IdProdotto' => $_REQUEST['idProdotto'],
			':DataInizioPeriodo' => $_REQUEST['dataInizioPeriodo'],
			':DataFinePeriodo' => $_REQUEST['dataFinePeriodo']
		]);


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

	// REPORT RENDIMENTO LINEE: RECUPERA INFORMAZIONI SU COMMESSA SELEZIONATO
	if ($_REQUEST['azione'] == 'rptLin-mostra-dettaglio-ordine') {

		// recupero i dati del dettaglio distinta selezionato
		$sthRecuperaDettaglio = $conn_mes->prepare(
			"SELECT ODP.op_IdProduzione, ODP.op_Lotto, RLP.rlp_QtaProdotta, RLP.rlp_QtaConforme, RLP.rlp_TTotale, RLP.rlp_Downtime, RLP.rlp_Attrezzaggio, RLP.rlp_VelocitaLinea, RLP.rlp_D, RLP.rlp_E, RLP.rlp_Q, RLP.rlp_OEELinea, RLP.rlp_DataInizio, RLP.rlp_DataFine, RLP.rlp_OraFine, RLP.rlp_OraInizio, P.prd_Descrizione, P.prd_IdProdotto, LP.lp_Descrizione, UDM.um_Sigla
			FROM ordini_produzione AS ODP
			LEFT JOIN prodotti AS P ON ODP.op_Prodotto = P.prd_IdProdotto
			LEFT JOIN rientro_linea_produzione AS RLP ON ODP.op_IdProduzione = RLP.rlp_IdProduzione
			LEFT JOIN linee_produzione AS LP ON ODP.op_LineaProduzione = LP.lp_IdLinea
			LEFT JOIN unita_misura AS UDM ON ODP.op_Udm = UDM.um_IdRiga
			WHERE ODP.op_IdProduzione = :IdProduzione"
		);
		$sthRecuperaDettaglio->execute([
			':IdProduzione' => $_REQUEST['idProduzione']
		]);
		$rigaDettaglio = $sthRecuperaDettaglio->fetch(PDO::FETCH_ASSOC);

		// formatto adeguatamente le stringhe per la 'data/ora inizio' e per la 'data/ora fine'
		if (isset($rigaDettaglio['rlp_DataInizio'])) {
			$dInizio = new DateTime($rigaDettaglio['rlp_DataInizio'] . ' ' . $rigaDettaglio['rlp_OraInizio']);
			$stringaDataInizio = $dInizio->format('d/m/Y - H:i:s');
		}


		if (isset($rigaDettaglio['rlp_DataFine'])) {
			$dFine = new DateTime($rigaDettaglio['rlp_DataFine'] . ' ' . $rigaDettaglio['rlp_OraFine']);
			$stringaDataFine = $dFine->format('d/m/Y H:i:s');
		}

		$output = [];

		//Preparo i dati da visualizzare
		$output[] = [
			'IdProduzione' => $rigaDettaglio['op_IdProduzione'],
			'DescrizioneProdotto' => $rigaDettaglio['prd_Descrizione'],
			'DataInizio' => $stringaDataInizio,
			'DataFine' => $stringaDataFine,
			'QtaProdotta' => $rigaDettaglio['rlp_QtaProdotta'] . ' ' . $rigaDettaglio['um_Sigla'],
			'QtaConforme' => $rigaDettaglio['rlp_QtaConforme'] . ' ' . $rigaDettaglio['um_Sigla'],
			'Lotto' => $rigaDettaglio['op_Lotto'],
			'TTotale' => $rigaDettaglio['rlp_TTotale'],
			'Downtime' => $rigaDettaglio['rlp_Downtime'],
			'TAttrezzaggio' => $rigaDettaglio['rlp_Attrezzaggio'],
			'VelocitaLinea' => $rigaDettaglio['rlp_VelocitaLinea'],
			'D' => round($rigaDettaglio['rlp_D'], 2),
			'E' => round($rigaDettaglio['rlp_E'], 2),
			'Q' => round($rigaDettaglio['rlp_Q'], 2),
			'OEELinea' => round($rigaDettaglio['rlp_OEELinea'], 2)
		];

		die(json_encode($output));
	}

	// REPORT RENDIMENTO LINEE: RECUPERA DISTINTA RISORSE PER COMMESSA SELEZIONATO
	if ($_REQUEST['azione'] == 'rptLin-mostra-distinta-risorse-ordine') {


		// seleziono i dati della tabella di lavoro 'risorse_coinvolte'
		$sth = $conn_mes->prepare(
			"SELECT R.ris_Descrizione, RP.rp_DataInizio, RP.rp_DataFine, RP.rp_OraInizio, RP.rp_OraFine, RP.rp_TTotale, RP.rp_Attrezzaggio, RP.rp_Downtime, RP.rp_OEE, RP.rp_VelocitaRisorsa, R.ris_TTeoricoAttrezzaggio
			FROM risorse_coinvolte AS RC
			LEFT JOIN risorse AS R ON RC.rc_IdRisorsa = R.ris_IdRisorsa
			LEFT JOIN risorsa_produzione AS RP ON RC.rc_IdRisorsa = RP.rp_IdRisorsa AND RP.rp_IdProduzione = RC.rc_IdProduzione
			WHERE RC.rc_IdProduzione = :IdOrdineProduzione"
		);
		$sth->execute([':IdOrdineProduzione' => $_REQUEST['idProduzione']]);


		$output = [];


		$righe = $sth->fetchAll(PDO::FETCH_ASSOC);

		foreach ($righe as $riga) {

			if (isset($riga['rp_DataInizio'])) {
				$dInizio = new DateTime($riga['rp_DataInizio'] . ' ' . $riga['rp_OraInizio']);
				$stringaDataInizio = $dInizio->format('d/m/Y H:i:s');
			} else {
				$stringaDataInizio = 'IN ATTESA';
			}

			if (isset($riga['rp_DataFine'])) {
				$dFine = new DateTime($riga['rp_DataFine'] . ' ' . $riga['rp_OraFine']);
				$stringaDataFine = $dFine->format('d/m/Y H:i:s');
			} else {
				if (!isset($riga['rp_DataInizio'])) {
					$stringaDataFine = 'IN ATTESA';
				} else {
					$stringaDataFine = "IN CORSO...";
				}
			}


			//Preparo i dati da visualizzare
			$output[] = [
				'Descrizione' => $riga['ris_Descrizione'],
				'DataInizio' => $stringaDataInizio,
				'DataFine' => $stringaDataFine,
				'TTotale' => $riga['rp_TTotale'],
				'Attrezzaggio' => $riga['rp_Attrezzaggio'],
				'DeltaAttrezzaggio' => floatval($riga['rp_Attrezzaggio'] - $riga['ris_TTeoricoAttrezzaggio']),
				'Downtime' => $riga['rp_Downtime'],
				'OEERisorsa' => round($riga['rp_OEE'], 2),
				'Velocita' => $riga['rp_VelocitaRisorsa']
			];
		}

		die(json_encode($output));
	}

	// REPORT RENDIMENTO LINEE: RECUPERA DISTINTA COMPONENTI PER COMMESSA SELEZIONATO
	if ($_REQUEST['azione'] == 'rptLin-mostra-distinta-componenti-ordine') {

		// seleziono i dati della tabella di lavoro 'componenti_work'
		$sth = $conn_mes->prepare(
			"SELECT componenti.*, prodotti.prd_Descrizione FROM componenti
			LEFT JOIN prodotti ON componenti.cmp_Componente = prodotti.prd_IdProdotto
			WHERE componenti.cmp_IdProduzione = :IdOrdineProduzione"
		);
		$sth->execute([':IdOrdineProduzione' => $_REQUEST['idProduzione']]);


		$output = [];


		$righe = $sth->fetchAll(PDO::FETCH_ASSOC);

		foreach ($righe as $riga) {

			//Preparo i dati da visualizzare
			$output[] = [
				'IdProdotto' => $riga['cmp_Componente'],
				'Descrizione' => $riga['prd_Descrizione'],
				'QuantitaComponente' => $riga['cmp_Qta']
			];
		}

		die(json_encode($output));
	}

	// DETTAGLIO CASI REGISTRATI PER PRODUZIONE IN OGGETTO
	if ($_REQUEST['azione'] == 'rptLin-mostra-casi-produzione-cumulativo') {

		$sth = $conn_mes->prepare(
			"SELECT AC.ac_DescrizioneEvento, AC.ac_Note, R.ris_Descrizione , C.cas_Tipo, COUNT(*) As TotaleEventi,
			SUM(
				DATEDIFF(MINUTE,
					CONVERT(Datetime, (CONCAT(AC.ac_DataInizio, 'T', AC.ac_OraInizio))),
					CONVERT(Datetime, (CONCAT(AC.ac_DataFine, 'T', AC.ac_OraFine)))
				)
			) AS TotaleDowntimeTipo
			FROM attivita_casi AS AC
			LEFT JOIN casi AS C ON AC.ac_IdEvento = C.cas_IdEvento AND AC.ac_IdRisorsa = C.cas_IdRisorsa
			LEFT JOIN risorse AS R ON AC.ac_IdRisorsa = R.ris_IdRisorsa
			WHERE AC.ac_IdProduzione = :IdOrdineProduzione
			GROUP BY AC.ac_DescrizioneEvento, AC.ac_Note, R.ris_Descrizione , C.cas_Tipo"
		);
		$sth->execute([':IdOrdineProduzione' => $_REQUEST['idProduzione']]);


		// seleziono i dati della tabella di lavoro 'risorse_coinvolte_work'

		$output = [];


		$righe = $sth->fetchAll(PDO::FETCH_ASSOC);

		foreach ($righe as $riga) {


			if ($riga['cas_Tipo'] == 'KO') {
				$tipoEvento = 'AVARIA';
			} else if ($riga['cas_Tipo'] == 'KK') {
				$tipoEvento = 'FERMO';
			} else if ($riga['cas_Tipo'] == 'OK') {
				$tipoEvento = "NON BLOC.";
			} else if ($riga['cas_Tipo'] == 'AT') {
				$tipoEvento = "ATTR.";
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

	// REPORT RENDIMENTO LINEE: RECUPERA DETTAGLIO CASI PER COMMESSA SELEZIONATO
	if ($_REQUEST['azione'] == 'rptLin-mostra-casi-ordine') {

		// seleziono i dati della tabella di lavoro 'risorse_coinvolte_work'
		$sth = $conn_mes->prepare(
			"SELECT AC.ac_DescrizioneEvento, AC.ac_Note, AC.ac_DataInizio, AC.ac_DataFine, AC.ac_OraInizio, AC.ac_OraFine, R.ris_Descrizione , C.cas_Tipo
			FROM attivita_casi AS AC
			LEFT JOIN casi AS C ON AC.ac_IdEvento = C.cas_IdEvento AND AC.ac_IdRisorsa = C.cas_IdRisorsa
			LEFT JOIN risorse AS R ON AC.ac_IdRisorsa = R.ris_IdRisorsa
			WHERE AC.ac_IdProduzione = :IdOrdineProduzione"
		);
		$sth->execute([':IdOrdineProduzione' => $_REQUEST['idProduzione']]);

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
				$tipoEvento = "NON BLOC.";
			} else if ($riga['cas_Tipo'] == 'AT') {
				$tipoEvento = "ATTR.";
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

	// REPORT RENDIMENTO LINEE: RECUPERA DETTAGLIO DOWNTIME PER COMMESSA SELEZIONATO
	if ($_REQUEST['azione'] == 'rptLin-mostra-downtime-ordine') {

		// seleziono i dati della tabella di lavoro 'risorse_coinvolte_work'
		$sth = $conn_mes->prepare(
			"SELECT risorsa_downtime.*, risorse.ris_Descrizione
			FROM risorsa_downtime LEFT JOIN risorse ON risorsa_downtime.rdt_IdRisorsa = risorse.ris_IdRisorsa
			WHERE risorsa_downtime.rdt_IdProduzione = :IdOrdineProduzione"
		);
		$sth->execute([':IdOrdineProduzione' => $_REQUEST['idProduzione']]);

		$output = [];

		$righe = $sth->fetchAll(PDO::FETCH_ASSOC);

		foreach ($righe as $riga) {
			if (isset($riga['rdt_DataInizio'])) {
				$dInizio = new DateTime($riga['rdt_DataInizio'] . ' ' . $riga['rdt_OraInizio']);
				$stringaDataInizio = $dInizio->format('d/m/Y H:i:s');
			} else {
				$stringaDataInizio = "";
			}

			if (isset($riga['rdt_DataFine'])) {
				$dFine = new DateTime($riga['rdt_DataFine'] . ' ' . $riga['rdt_OraFine']);
				$stringaDataFine = $dFine->format('d/m/Y H:i:s');
			} else {
				$dataOdierna = date('Y-m-d');
				$oraOdierna = date('H:i:s');
				$dFine = new DateTime($dataOdierna . ' ' . $oraOdierna);
				$stringaDataFine = "";
			}
			$durataEvento = $dFine->diff($dInizio);

			$durataEvento_sec = floatval(($durataEvento->days * 3600 * 24) + ($durataEvento->h * 3600) + ($durataEvento->i * 60) + $durataEvento->s);
			$durataEvento_min = intval($durataEvento_sec / 60);
			//Preparo i dati da visualizzare
			$output[] = [

				'DescrizioneRisorsa' => $riga['ris_Descrizione'],
				'OrarioInizio' => $stringaDataInizio,
				'OrarioFine' => $stringaDataFine,
				'Durata' => (float)$durataEvento_min
			];
		}

		die(json_encode($output));
	}

	// AUSILIARIA: POPOLAMENTO SELECT PRODOTTI IN BASE ALLA LINEA SELEZIONATA
	if ($_REQUEST['azione'] == 'rptLin-carica-select-prodotti') {
		unset($_REQUEST['azione']);
		// estraggo gli eventuali prodotti aggiuntivi
		$sth = $conn_mes->prepare(
			"SELECT velocita_teoriche.*, prodotti.prd_Descrizione FROM velocita_teoriche
			JOIN prodotti ON velocita_teoriche.vel_IdProdotto = prodotti.prd_IdProdotto
			WHERE velocita_teoriche.vel_IdLineaProduzione = :idLineaProduzione"
		);
		$sth->execute(['idLineaProduzione' => $_REQUEST['idLineaProduzione']]);
		$prodotti = $sth->fetchAll(PDO::FETCH_ASSOC);
		$optionValue = "";

		//Se ho trovato sottocategorie
		if ($prodotti) {

			$optionValue = $optionValue . "<option value='%'>TUTTI</option>";

			//Aggiungo ognuna delle sottocategorie trovate alla stringa che conterrà le possibili opzioni della select categorie, e che ritorno come risultato
			foreach ($prodotti as $prodotto) {

				//Se ho già una sottocategoria selezionata (provengo da popup "di modifica"), preparo il contenuto della select con l'option value corretto selezionato altrimenti preparo solo il contenuto.
				if (!empty($_REQUEST['idProdotto']) && $_REQUEST['idProdotto'] == $prodotto['vel_IdProdotto']) {
					$optionValue = $optionValue . "<option value='" . $prodotto['vel_IdProdotto'] . "' selected>" . strtoupper($prodotto['prd_Descrizione']) . " </option>";
				} else {
					$optionValue = $optionValue . "<option value='" . $prodotto['vel_IdProdotto'] . "'>" . strtoupper($prodotto['prd_Descrizione']) . " </option>";
				}
			}
		} else {
			$optionValue = "<option value='%'>TUTTI</option>";
		}
		die($optionValue);
	}
}
