<?php
// in che pagina siamo
$pagina = 'inc_reportrisorse';
require_once('../inc/conn.php');

if (!empty($_REQUEST['azione'])) {
	// REPORT RENDIMENTO RISORSE: RECUPERO VALORI OEE (GRAFICO E TABELLA)
	if ($_REQUEST['azione'] == 'rptMis-recupera-misure') {


		$sthMisurePeriodo = $conn_mes->prepare(
			"SELECT * FROM risorsa_misure
			LEFT JOIN misure ON rm_IdMisura = mis_IdMisura AND mis_IdRisorsa = rm_IdRisorsa
			WHERE rm_IdRisorsa = :IdRisorsa AND rm_IdProduzione LIKE :IdProduzione AND rm_IdMisura = :IdMisura
			AND rm_Data >= :DataInizioPeriodo AND rm_Data <= :DataFinePeriodo
			ORDER BY rm_Data ASC, rm_Ora ASC"
		);
		$sthMisurePeriodo->execute([
			':IdRisorsa' => $_REQUEST['idRisorsa'],
			':IdProduzione' => $_REQUEST['idProduzione'],
			':IdMisura' => $_REQUEST['idMisura'],
			':DataInizioPeriodo' => $_REQUEST['dataInizioPeriodo'],
			':DataFinePeriodo' => $_REQUEST['dataFinePeriodo']
		]);
		$righe = $sthMisurePeriodo->fetchAll();

		$output = [];
		foreach ($righe as $riga) {

			// formatto adeguatamente le stringhe per la 'data/ora inizio' e per la 'data/ora fine'
			if (isset($riga['rm_Data'])) {
				$dMisura = new DateTime(trim($riga['rm_Data']) . ' ' . trim($riga['rm_Ora']));
				$stringaDataMisura = $dMisura->format('Y-m-d H:i:s');
			}


			//Preparo i dati da visualizzare
			$output[] = [
				'DataOraMisura' => $stringaDataMisura,
				'ValoreMisura' => $riga['rm_Valore'],
				'UnitaMisura' => $riga['mis_Udm'],
				'DescrizioneMisura' => $riga['mis_Descrizione'],
				'IdProduzione' => $riga['rm_IdProduzione'],
				'ColoreLinea' =>  $riga['mis_ColoreGrafico']
			];
		}

		die(json_encode($output));
	}

	// REPORT RENDIMENTO RISORSE: RECUPERO VALORI OEE (GRAFICO E TABELLA)
	if ($_REQUEST['azione'] == 'rptMis-recupera-misure-tabella') {


		$sthMisurePeriodo = $conn_mes->prepare(
			"SELECT mis_Descrizione, ris_Descrizione , mis_Udm, risorsa_misure.* FROM risorsa_misure
			LEFT JOIN misure ON rm_IdMisura = mis_IdMisura AND mis_IdRisorsa = rm_IdRisorsa
			LEFT JOIN risorse ON rm_IdRisorsa = ris_IdRisorsa
			WHERE rm_IdRisorsa = :IdRisorsa AND rm_IdProduzione LIKE :IdProduzione AND rm_IdMisura = :IdMisura
			AND rm_Data >= :DataInizioPeriodo AND rm_Data <= :DataFinePeriodo
			ORDER BY misure.mis_Descrizione ASC"
		);
		$sthMisurePeriodo->execute([
			':IdRisorsa' => $_REQUEST['idRisorsa'],
			':IdProduzione' => $_REQUEST['idProduzione'],
			':IdMisura' => $_REQUEST['idMisura'],
			':DataInizioPeriodo' => $_REQUEST['dataInizioPeriodo'],
			':DataFinePeriodo' => $_REQUEST['dataFinePeriodo']
		]);

		$auxIndiceRiga = 0;
		$auxMemMisura = "";
		$output = [];


		$righe = $sthMisurePeriodo->fetchAll(PDO::FETCH_ASSOC);

		foreach ($righe as $riga) {

			// formatto adeguatamente le stringhe per la 'data/ora inizio' e per la 'data/ora fine'
			if (isset($riga['rm_Data'])) {
				$dMisura = new DateTime(trim($riga['rm_Data']) . ' ' . trim($riga['rm_Ora']));
				$stringaDataMisura = $dMisura->format('d/m/Y H:i:s');
			}

			$valoreUdmMisura = $riga['rm_Valore'] . ' ' . $riga['mis_Udm'];


			if ($auxMemMisura != $riga['mis_Descrizione']) {
				$auxMemMisura = $riga['mis_Descrizione'];

				if ($auxIndiceRiga == 0) {
					$auxIndiceRiga = 1;
				} else {
					$auxIndiceRiga = 0;
				}
			}

			//Preparo i dati da visualizzare
			$output[] = [
				'DescrizioneMisura' => $riga['mis_Descrizione'],
				'DescrizioneRisorsa' => $riga['ris_Descrizione'],
				'IdProduzione' => $riga['rm_IdProduzione'],
				'ValoreMisura' => $valoreUdmMisura,
				'DataOraMisura' => $stringaDataMisura,
				'IndiceRiga' => $auxIndiceRiga
			];
		}

		die(json_encode($output));
	}

	// AUSILIARIA: POPOLAMENTO SELECT RISORSE IN BASE A LINEA SELEZIONATA
	if ($_REQUEST['azione'] == 'rptMis-carica-select-misure') {


		$sth = $conn_mes->prepare(
			"SELECT DISTINCT mis_IdMisura, mis_Descrizione FROM misure
			LEFT JOIN risorsa_misure ON rm_IdMisura = mis_IdMisura AND rm_IdRisorsa = mis_IdRisorsa
			WHERE mis_IdRisorsa = :IdRisorsa"
		);
		$sth->execute([':IdRisorsa' => $_REQUEST['idRisorsa']]);


		$misure = $sth->fetchAll(PDO::FETCH_ASSOC);
		$optionValue = "";

		//Se ho trovato misure
		if ($misure) {
			//Aggiungo ognuna delle sottocategorie trovate alla stringa che conterrà le possibili opzioni della select categorie, e che ritorno come risultato
			foreach ($misure as $misura) {
				$optionValue = $optionValue . "<option value='" . $misura['mis_IdMisura'] . "'>" . strtoupper($misura['mis_Descrizione']) . " </option>";
			}
		} else {
			$optionValue = "<option value=''>Nessuna misura disponibile</option>";
		}

		die($optionValue);
	}

	// AUSILIARIA: POPOLAMENTO SELECT PRODOTTI IN BASE A RISORSA SELEZIONATA
	if ($_REQUEST['azione'] == 'rptMis-carica-select-commesse') {
		// estraggo gli eventuali prodotti aggiuntivi
		$sth = $conn_mes->prepare(
			"SELECT DISTINCT rm_IdProduzione FROM risorsa_misure
			WHERE risorsa_misure.rm_IdRisorsa = :IdRisorsa"
		);
		$sth->execute([':IdRisorsa' => $_REQUEST['idRisorsa']]);
		$commesse = $sth->fetchAll(PDO::FETCH_ASSOC);
		$optionValue = "";

		//Se ho trovato sottocategorie
		if ($commesse) {

			$optionValue = $optionValue . "<option value='%'>TUTTI</option>";

			//Aggiungo ognuna delle sottocategorie trovate alla stringa che conterrà le possibili opzioni della select categorie, e che ritorno come risultato
			foreach ($commesse as $commessa) {

				//Se ho già una sottocategoria selezionata (provengo da popup 'di modifica'), preparo il contenuto della select con l'option value corretto selezionato altrimenti preparo solo il contenuto.
				if (!empty($_REQUEST['idProduzione']) && $_REQUEST['idProduzione'] == $commessa['rm_IdProduzione']) {
					$optionValue = $optionValue . "<option value='" . $commessa['rm_IdProduzione'] . "' selected>" . $commessa['rm_IdProduzione'] . " </option>";
				} else {
					$optionValue = $optionValue . "<option value='" . $commessa['rm_IdProduzione'] . "'>" . $commessa['rm_IdProduzione'] . " </option>";
				}
			}
		} else {
			$optionValue = "<option value=''>Nessuna commessa disponibile</option>";
		}
		die($optionValue);
	}
}
