<?php
// in che pagina siamo
$pagina = 'timelineordini';

include("../inc/conn.php");

if (!empty($_REQUEST['azione'])) {
	if ($_REQUEST['azione'] == 'dati-gantt') {

		$sth = $conn_mes->prepare(
			"SELECT lp_IdLinea AS id, lp_Descrizione AS content
			FROM linee_produzione"
		);
		$sth->execute();
		$linee = $sth->fetchAll();

		if (!$linee) {
			die(json_encode([]));
		}

		$sth = $conn_mes->prepare(
			"SELECT * FROM ordini_produzione
			WHERE op_Stato IN (1,2,3,4)"
		);
		$sth->execute();
		$ordini = $sth->fetchAll();

		$items = [];

		foreach ($ordini as $ordine) {

			$inizio = new DateTime($ordine['op_DataProduzione'] . ' ' . $ordine['op_OraProduzione']);
			$fine = new DateTime($ordine['op_DataFineTeorica'] . ' ' . $ordine['op_OraFineTeorica']);

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
				$style = 'background-color: white; border-color:silver';
			}


			$items[] = [
				'id' => $ordine['op_IdProduzione'],
				'content' => $ordine['op_IdProduzione'] . ' (' . $ordine['op_Riferimento'] . ')',
				'start' => $inizio->format('Y-m-d H:i:s'),
				'end' => $fine->format('Y-m-d H:i:s'),
				'group' => $ordine['op_LineaProduzione'],
				'style' => $style
			];
		}


		die(json_encode([
			'groups' => $linee,
			'items' => $items
		]));
	}

	// DISTINTA RISORSE COINVOLTE (TAB. DI LAVORO): RECUPERA VALORI RISORSA SELEZIONATA
	if ($_REQUEST['azione'] == 'info-ordine') {

		// recupero i dati del dettaglio distinta selezionato
		$sthRecuperaDettaglio = $conn_mes->prepare(
			"SELECT ODP.op_Stato, ODP.op_IdProduzione, ODP.op_QtaDaProdurre, ODP.op_DataProduzione, ODP.op_OraProduzione, ODP.op_DataFineTeorica, ODP.op_OraFineTeorica, LP.lp_Descrizione, P.prd_Descrizione, UM.um_Sigla, VT.vel_VelocitaTeoricaLinea
			FROM ordini_produzione AS ODP
			LEFT JOIN linee_produzione AS LP ON ODP.op_LineaProduzione = LP.lp_IdLinea
			LEFT JOIN stati_ordine AS SO ON ODP.op_Stato = SO.so_IdStatoOrdine
			LEFT JOIN prodotti AS P ON ODP.op_Prodotto = P.prd_IdProdotto
			LEFT JOIN unita_misura AS UM ON ODP.op_Udm = UM.um_IdRiga
			LEFT JOIN velocita_teoriche AS VT ON ODP.op_LineaProduzione = VT.vel_IdLineaProduzione AND ODP.op_Prodotto = VT.vel_IdProdotto
			WHERE ODP.op_IdProduzione = :IdOrdineProduzione",
			[PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]
		);
		$sthRecuperaDettaglio->execute([':IdOrdineProduzione' => $_REQUEST['idProduzione']]);
		$riga = $sthRecuperaDettaglio->fetch(PDO::FETCH_ASSOC);



		// formatto adeguatamente le stringhe per la 'data/ora inizio' e per la 'data/ora fine'
		if (isset($riga['op_DataProduzione'])) {
			$dInizio = new DateTime($riga['op_DataProduzione'] . ' ' . $riga['op_OraProduzione']);
			$stringaDataInizio = $dInizio->format('Y-m-d H:i:s');
		}

		if (isset($riga['op_DataFineTeorica'])) {
			$dFine = new DateTime($riga['op_DataFineTeorica'] . ' ' . $riga['op_OraFineTeorica']);
			$stringaDataFine = $dFine->format('Y-m-d H:i:s');
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
			'vel_VelocitaTeoricaLinea' => $riga['vel_VelocitaTeoricaLinea']
		];

		//debug($riga,'RIGA');

		die(json_encode($output));
	}

	if ($_REQUEST['azione'] == 'salva-ordine-gantt'){

		$parametri = [];
		parse_str($_REQUEST['data'], $parametri);
		$conn_mes->beginTransaction();
		try {
			$dataProduzione = new DateTime($parametri['op_DataOraProduzione']);
			$dataFine = new DateTime($parametri['op_DataOraFineTeorica']);

			$sth = $conn_mes->prepare(
				"UPDATE ordini_produzione SET
				op_DataProduzione = :dataProduzione,
				op_OraProduzione = :oraProduzione,
				op_DataFineTeorica = :dataFineTeorica,
				op_OraFineTeorica = :oraFineTeorica
				WHERE op_IdProduzione = :idProduzione"
			);

			$sth->execute([
				'dataProduzione' => $dataProduzione->format('Y-m-d'),
				'oraProduzione' => $dataProduzione->format('H:i'),
				'dataFineTeorica' => $dataFine->format('Y-m-d'),
				'oraFineTeorica' => $dataFine->format('H:i'),
				'idProduzione' => $parametri['op_IdProduzione'],
			]);

			$conn_mes->commit();
			die('OK');
		} catch (\Throwable $th) {
			$conn_mes->rollBack();
			die($th->getMessage());
		}

	}
}
