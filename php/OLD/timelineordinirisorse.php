<?php
// in che pagina siamo
$pagina = 'timelineordinirisorse';

include("../inc/conn.php");

if (!empty($_REQUEST['azione'])) {
	if ($_REQUEST['azione'] == 'dati-gantt') {

		$sth = $conn_mes->prepare(
			"SELECT ris_IdRisorsa AS id, ris_Descrizione AS content
			FROM risorse"
		);
		$sth->execute();
		$linee = $sth->fetchAll();

		if (!$linee) {
			die(json_encode([]));
		}

		$sth = $conn_mes->prepare(
			"SELECT * FROM risorse_coinvolte
			LEFT JOIN ordini_produzione ON op_IdProduzione = rc_IdProduzione
			WHERE op_Stato IN (2,3,4)"
		);
		$sth->execute();
		$ordini = $sth->fetchAll();

		$items = [];

		foreach ($ordini as $ordine) {

			$inizio = new DateTime($ordine['rc_DataOraInizio']);
			$fine = new DateTime($ordine['rc_DataOraFine']);

			$style = 'background-color: rgba(102, 204, 255, 0.5); border-color:rgba(102, 204, 255, 1)';


			if (intval($ordine['op_Stato']) == 4) {
				$sth = $conn_mes->prepare(
					"SELECT * FROM rientro_linea_produzione
					WHERE rlp_IdProduzione = :IdProduzione"
				);
				$sth->execute(['IdProduzione' => $ordine['op_IdProduzione']]);
				$rlp = $sth->fetch();
				$inizio = new DateTime($rlp['rlp_DataInizio'] . ' ' . $rlp['rlp_OraInizio']);
				$style = 'background-color: rgba(0, 205, 0, 0.5); border-color:rgba(0, 205, 0, 1)';
			}
			if (intval($ordine['op_Stato']) == 1) {
				$style = 'background-color: whitesmoke; border-color:silver';
			}


			$items[] = [
				'id' => $ordine['op_IdProduzione'] . '!' . $ordine['rc_IdRisorsa'],
				'content' => $ordine['op_IdProduzione'] . ' (' . $ordine['op_Riferimento'] . ')',
				'start' => $inizio->format('Y-m-d\TH:i:s'),
				'end' => $fine->format('Y-m-d\TH:i:s'),
				'group' => $ordine['rc_IdRisorsa'],
				'style' => ''
			];
		}


		die(json_encode([
			'groups' => $linee,
			'items' => $items
		]));
	}

	// DISTINTA RISORSE COINVOLTE (TAB. DI LAVORO): RECUPERA VALORI RISORSA SELEZIONATA
	if ($_REQUEST['azione'] == 'info-ordine') {
		unset($_REQUEST['azione']);
		$now = new DateTime();
		// recupero i dati del dettaglio distinta selezionato
		$sthRecuperaDettaglio = $conn_mes->prepare(
			"SELECT * FROM risorse_coinvolte AS RC
			LEFT JOIN ordini_produzione AS ODP ON ODP.op_IdProduzione = RC.rc_IdProduzione
			LEFT JOIN linee_produzione AS LP ON ODP.op_LineaProduzione = LP.lp_IdLinea
			LEFT JOIN stati_ordine AS SO ON ODP.op_Stato = SO.so_IdStatoOrdine
			LEFT JOIN prodotti AS P ON ODP.op_Prodotto = P.prd_IdProdotto
			LEFT JOIN unita_misura AS UM ON ODP.op_Udm = UM.um_IdRiga
			LEFT JOIN risorsa_produzione AS RP ON RP.rp_IdRisorsa = RC.rc_IdRisorsa AND RP.rp_IdProduzione = RC.rc_IdProduzione
			LEFT JOIN velocita_teoriche AS VT ON ODP.op_LineaProduzione = VT.vel_IdLineaProduzione AND ODP.op_Prodotto = VT.vel_IdProdotto
			WHERE ODP.op_IdProduzione = :idProduzione AND RC.rc_IdRisorsa = :idRisorsa"
		);
		$sthRecuperaDettaglio->execute($_REQUEST);
		$riga = $sthRecuperaDettaglio->fetch(PDO::FETCH_ASSOC);



		// formatto adeguatamente le stringhe per la 'data/ora inizio' e per la 'data/ora fine'
		if (isset($riga['rc_DataOraInizio'])) {
			$dInizio = new DateTime($riga['rc_DataOraInizio']);
			$stringaDataInizio = $dInizio->format('Y-m-d H:i:s');
		} else {
			if (isset($riga['rp_DataInizio'])) {
				$dInizio = new DateTime($riga['rp_DataInizio'] . ' ' . $riga['rp_OraInizio']);
				$stringaDataInizio = $dInizio->format('Y-m-d H:i:s');
			} else {
				$stringaDataInizio = $now->format('Y-m-d H:i:s');

			}
		}

		if (isset($riga['rc_DataOraFine'])) {
			$dFine = new DateTime($riga['rc_DataOraFine']);
			$stringaDataFine = $dFine->format('Y-m-d H:i:s');
		} else {
			$stringaDataFine = $now->format('Y-m-d H:i:s');
		}

		$quantitaUdm = $riga['op_QtaDaProdurre'] . ' [' . $riga['um_Sigla'] . ']';

		//Preparo i dati da visualizzare
		$output = [
			'op_IdProduzione' => $riga['op_IdProduzione'],
			'lp_Descrizione' => $riga['lp_Descrizione'],
			'prd_Descrizione' => $riga['prd_Descrizione'],
			'op_QtaDaProdurreUdm' => $quantitaUdm,
			'op_DataOraProduzione' => $stringaDataInizio,
			'op_DataOraFineTeorica' => $stringaDataFine,
			'op_Stato' => $riga['op_Stato'],
			'op_QtaDaProdurre' => $riga['op_QtaDaProdurre'],
			'rc_VelocitaRisorsa' => $riga['rc_VelocitaRisorsa'],
			'rc_IdRisorsa' => $riga['rc_IdRisorsa'],
		];

		//debug($riga,'RIGA');

		die(json_encode($output));
	}

	if ($_REQUEST['azione'] == 'salva') {
		unset($_REQUEST['azione']);
		$conn_mes->beginTransaction();

		$parametri = [];
		parse_str($_REQUEST['data'], $parametri);

		try {

			$sth = $conn_mes->prepare(
				"UPDATE risorse_coinvolte SET
				rc_DataOraInizio = :DataOraInizio,
				rc_DataOraFine = :DataOraFine
				WHERE rc_IdProduzione = :IdProduzione AND
				rc_IdRisorsa = :IdRisorsa"
			);
			$sth->execute([
				'DataOraInizio' => $parametri['op_DataOraProduzione'] . ':00',
				'DataOraFine' => $parametri['op_DataOraFineTeorica'] . ':00',
				'IdProduzione' => $parametri['op_IdProduzione'],
				'IdRisorsa' => $parametri['rc_IdRisorsa'],
			]);


			if ($_REQUEST['aggiornaSuccessive'] == 'true') {

				$sth = $conn_mes->prepare(
					"SELECT ris_Ordinamento FROM risorse
					WHERE ris_IdRisorsa = :IdRisorsa"
				);
				$sth->execute([
					'IdRisorsa' => $parametri['rc_IdRisorsa'],
				]);
				$ord = $sth->fetch()['ris_Ordinamento'];

				$sth = $conn_mes->prepare(
					"SELECT * FROM risorse_coinvolte
					LEFT JOIN risorse ON ris_IdRisorsa = rc_IdRisorsa
					LEFT JOIN ordini_produzione ON op_IdProduzione = rc_IdProduzione
					WHERE ris_Ordinamento > (
						SELECT ris_Ordinamento FROM risorse
						WHERE ris_IdRisorsa = :nuovaRisorsa
					) AND rc_IdProduzione = :IdProduzione
					AND rc_DataOraInizio < :DataOraInizio"
				);
				$sth->execute([
					'nuovaRisorsa' => $parametri['rc_IdRisorsa'],
					'IdProduzione' => $parametri['op_IdProduzione'],
					'DataOraInizio' => $parametri['op_DataOraProduzione'] . ':00',
				]);
				$risorseDaAggiornare = $sth->fetchAll();

				foreach ($risorseDaAggiornare as $risorsaDaAggiornare) {
					$inizio = new DateTime($parametri['op_DataOraProduzione'] . ':00');

					$diffOrd = $risorsaDaAggiornare['ris_Ordinamento'] - $ord;

					$inizio->add(new DateInterval('PT' . 5 * $diffOrd . 'M'));

					$dataInizio = $inizio->format('Y-m-d\TH:i:s');

					$qta = floatVal($risorsaDaAggiornare['op_QtaDaProdurre']);
					$vel = floatVal($risorsaDaAggiornare['rc_VelocitaRisorsa']);

					if ($vel != 0) {
						$sec = round(($qta * 3600) / $vel);

						$inizio->add(new DateInterval('PT' . $sec . 'S'));
						$dataFine = $inizio->format('Y-m-d\TH:i:s');
						$inizio->sub(new DateInterval('PT' . $sec . 'S'));
					}
					$inizio->sub(new DateInterval('PT' . 5 * $diffOrd . 'M'));

					if (!$dataFine){
						$dataFine = $dataInizio;
					}


					$sth = $conn_mes->prepare(
						"UPDATE risorse_coinvolte SET
						rc_DataOraInizio = :DataOraInizio,
						rc_DataOraFine = :DataOraFine
						WHERE rc_IdProduzione = :IdProduzione AND
						rc_IdRisorsa = :IdRisorsa"
					);
					$sth->execute([
						'DataOraInizio' => $dataInizio,
						'DataOraFine' => $dataFine,
						'IdProduzione' => $risorsaDaAggiornare['rc_IdProduzione'],
						'IdRisorsa' => $risorsaDaAggiornare['rc_IdRisorsa'],
					]);
				}
			}


			$conn_mes->commit();
			die('OK');
		} catch (\Throwable $th) {
			$conn_mes->rollBack();
			die('KO');
		}
	}

	if ($_REQUEST['azione'] == 'controllo-conflitti') {
		unset($_REQUEST['azione']);
		$conn_mes->beginTransaction();

		$parametri = [];
		parse_str($_REQUEST['data'], $parametri);

		try {
			$output = [
				'precedenti' => 0,
				'successivi' => 0,
			];
			$sth = $conn_mes->prepare(
				"SELECT * FROM risorse_coinvolte
				LEFT JOIN risorse ON ris_IdRisorsa = rc_IdRisorsa
				WHERE ris_Ordinamento < (
					SELECT ris_Ordinamento FROM risorse
					WHERE ris_IdRisorsa = :nuovaRisorsa
				) AND rc_IdProduzione = :IdProduzione
				AND rc_DataOraInizio > :DataOraInizio"
			);
			$sth->execute([
				'nuovaRisorsa' => $parametri['rc_IdRisorsa'],
				'IdProduzione' => $parametri['op_IdProduzione'],
				'DataOraInizio' => $parametri['op_DataOraProduzione'] . ':00',
			]);
			$result = $sth->fetchAll();

			if ($result) {
				$output['precedenti'] = 1;
			}

			$sth = $conn_mes->prepare(
				"SELECT * FROM risorse_coinvolte
				LEFT JOIN risorse ON ris_IdRisorsa = rc_IdRisorsa
				WHERE ris_Ordinamento > (
					SELECT ris_Ordinamento FROM risorse
					WHERE ris_IdRisorsa = :nuovaRisorsa
				) AND rc_IdProduzione = :IdProduzione
				AND rc_DataOraInizio < :DataOraInizio"
			);
			$sth->execute([
				'nuovaRisorsa' => $parametri['rc_IdRisorsa'],
				'IdProduzione' => $parametri['op_IdProduzione'],
				'DataOraInizio' => $parametri['op_DataOraProduzione'] . ':00',
			]);
			$result = $sth->fetchAll();

			if ($result) {
				$output['successivi'] = 1;
			}


			die(json_encode($output));
		} catch (\Throwable $th) {
			$conn_mes->rollBack();
			die($th->getMessage());
		}
	}
}
