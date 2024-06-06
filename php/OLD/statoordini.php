<?php
// in che pagina siamo
$pagina = 'statoordini';

include("../inc/conn.php");


function calcoloTrendCommessa(int $qtaConformi, Datetime $inizio, DateTime $fine, $velTeorica, $qtaDaProdurre, $tempoSec)
{

	$tempoTrascorso = $tempoSec;
	$prodottiTeorici = round($tempoTrascorso * $velTeorica / 3600);

	$oee = $qtaConformi / $prodottiTeorici;
	if ($oee > 1) {
		$oee = 1;
	} else if ($oee < 0) {
		$oee = 0;
	}

	$velReale = ($qtaConformi / $tempoTrascorso) * 3600;

	$rimanenti = $qtaDaProdurre - $qtaConformi;
	if (!$velReale == 0) {
		$tempoRimanente = round($rimanenti / $velReale);
		$dataFinePrevista = (new DateTime())->setTimestamp($fine->getTimestamp() + $tempoRimanente);
	} else {
		$dataFinePrevista = (new DateTime())->setTimestamp($fine->getTimestamp());
	}

	if ($qtaConformi > $qtaDaProdurre) {
		$dataFinePrevista = $fine;
	}

	return [
		'oee' => $oee,
		'velReale' => $velReale,
		'dataFinePrevista' => $dataFinePrevista,
	];
}

if (!empty($_REQUEST['azione'])) {

	if ($_REQUEST['azione'] == 'avviati') {
		$now = new DateTime();
		$sth = $conn_mes->prepare(
			"SELECT * FROM ordini_produzione AS ODP
			LEFT JOIN prodotti AS P ON P.prd_IdProdotto = ODP.op_prodotto
			LEFT JOIN rientro_linea_produzione AS RLP ON RLP.rlp_IdProduzione = ODP.op_IdProduzione
			LEFT JOIN velocita_teoriche AS VT ON VT.vel_IdProdotto = P.prd_IdProdotto AND VT.vel_IdLineaProduzione = ODP.op_LineaProduzione
			LEFT JOIN linee_produzione AS LP ON LP.lp_IdLinea = ODP.op_LineaProduzione
			LEFT JOIN unita_misura AS UM ON UM.um_IdRiga = ODP.op_Udm
			WHERE ODP.op_Stato = 4"
		);
		$sth->execute();
		$ordini = $sth->fetchAll();

		$output = [];
		foreach ($ordini as $ordine) {
			$dataInizio = new DateTime($ordine['rlp_DataInizio'] . ' ' . $ordine['rlp_OraInizio']);

			$dataFine = 'ND';

			$azioni =
				'<button type="button" class="btn btn-primary espandi-dettaglio-ordine py-1"
					data-id-produzione="' . $ordine['op_IdProduzione'] .  '" title="Vedi dettaglio">
					<span class="mdi mdi-eye mdi-18px"></span>
				</button>';


			$sth = $conn_mes->prepare(
				"SELECT SUM(rpp_QtaTot) AS qtaTot,
					SUM(rpp_QtaConforme) AS qtaConformi,
					SUM(rpp_QtaScarti) AS qtaScarti,
					SUM(DATEDIFF(
						SECOND,
						rpp_Inizio,
						IIF(rpp_Fine IS NULL, GETDATE(), rpp_Fine)
					)) AS tempoTotale,
					MAX(rpp_Fine) AS dataFine
					FROM risorsa_produzione_parziale
					WHERE rpp_IdProduzione = :IdProduzione
					AND rpp_IdRisorsa = (
						SELECT TOP(1) ris_IdRisorsa FROM risorse_coinvolte
						LEFT JOIN risorse ON ris_IdRisorsa = rc_IdRisorsa
						WHERE rc_IdProduzione = :IdProduzione2
						ORDER BY ris_Ordinamento DESC
					)"
			);
			$sth->execute([
				'IdProduzione' => ($ordine['op_IdProduzione']),
				'IdProduzione2' => ($ordine['op_IdProduzione'])
			]);
			$datiRpp = $sth->fetch();
			if (!empty($datiRpp['tempoTotale'])) {
				$tempoSec = $datiRpp['tempoTotale'];
				$ordine['rp_QtaProdotta'] = $datiRpp['qtaTot'];
				$qtaConformi = $datiRpp['qtaConformi'];
				$ordine['rp_QtaScarti'] = $datiRpp['qtaScarti'];
			} else {
				$ordine['rp_QtaProdotta'] = $ordine['rlp_QtaProdotta'];
				$qtaConformi = $ordine['rlp_QtaConforme'];
				$ordine['rp_QtaScarti'] = $ordine['rlp_QtaScarti'];

				$tempoSec = $now->getTimestamp() - $dataInizio->getTimestamp();
			}


			if (!empty($ordine['rp_QtaProdotta']) && $ordine['rp_QtaProdotta'] > 0) {
				$qtaProdotta = $ordine['rp_QtaProdotta'];
				$qtaScarti = $ordine['rp_QtaScarti'];
				$qtaConformi = $qtaProdotta - $qtaScarti;

				$datiOrdine = calcoloTrendCommessa($qtaConformi, $dataInizio, $now, $ordine['vel_VelocitaTeoricaLinea'], $ordine['op_QtaDaProdurre'], $tempoSec);
				$oee = $datiOrdine['oee'];
				$velReale = $datiOrdine['velReale'];
				$dataFinePrevista = $datiOrdine['dataFinePrevista'];

				$indicatoreRendimento = "<img class='indicatori' src='../images/DownArrow_3.png' style='float: right; height:40px; width:40px' hidden>";
				if ($velReale > $ordine["vel_VelocitaTeoricaLinea"]) {
					$indicatoreRendimento = "<img class='indicatori' src='../images/UpArrow_3.png' style='float: right; height:40px; width:40px' hidden>";
				}
				$velocita =
					'<div class="d-flex align-items-center">
						<div class="mr-4">
							<b>Teorica:</b> &nbsp;' . $ordine['vel_VelocitaTeoricaLinea'] . ' [' . $ordine['um_Sigla'] . '/h]<br>
							<b>Reale:</b> &nbsp;' . round($velReale, 2) . ' [' . $ordine['um_Sigla'] . '/h]
						</div>' .
					$indicatoreRendimento .
					'</div>';

				$sth = $conn_mes->prepare(
					"SELECT COUNT(*) AS conto FROM attivita_casi
					WHERE ac_IdProduzione = :IdProduzione
					AND ac_DataFine IS NULL AND ac_IdCaso = 'KO'"
				);
				$sth->execute([':IdProduzione' => $ordine['op_IdProduzione']]);
				$conto_ko = $sth->fetch()['conto'];

				$sth = $conn_mes->prepare(
					"SELECT COUNT(*) AS conto FROM attivita_casi
					WHERE ac_IdProduzione = :IdProduzione
					AND ac_DataFine IS NULL AND ac_IdCaso = 'AT'"
				);
				$sth->execute([':IdProduzione' => $ordine['op_IdProduzione']]);
				$conto_at = $sth->fetch()['conto'];

				$stato = 'ok';
				if ($conto_at > 0) {
					$stato = 'at';
				}
				if ($conto_ko > 0) {
					$stato = 'ko';
				}

				$output[] = [
					'lp_Descrizione' => $ordine['lp_Descrizione'],
					'op_IdProduzione' => !empty($ordine['op_Riferimento']) ? $ordine['op_IdProduzione'] . ' (' . $ordine['op_Riferimento'] . ')' : $ordine['op_IdProduzione'],
					'prd_Descrizione' => $ordine['prd_Descrizione'],
					'op_Lotto' => $ordine['op_Lotto'],
					'op_QtaDaProdurre' => $ordine['op_QtaDaProdurre'] . ' [' . $ordine['um_Sigla'] . ']',
					'rp_QtaProdotta' =>  $qtaProdotta . ' [' . $ordine['um_Sigla'] . ']',
					'rp_qtaScarti' => $qtaScarti . ' [' . $ordine['um_Sigla'] . ']',
					'rp_qtaConformi' => $qtaConformi . ' [' . $ordine['um_Sigla'] . ']',
					'DataOraInizio' => $dataInizio->format('Y-m-d H:i:s'),
					'DataOraFine' => $dataFinePrevista->format('Y-m-d H:i:s'),
					'Velocita' => $velocita,
					'ValoreOee' => round($oee * 100, 2),
					'Oee' => '<canvas id="grOEE_' . trim($ordine['op_IdProduzione']) . '" width="100" height="100" style="text-align:center;"></canvas>',
					'azioni' => $azioni,
					'StatoLinea' => $stato
				];
			} else {
				$output[] = [
					'lp_Descrizione' => $ordine['lp_Descrizione'],
					'op_IdProduzione' => !empty($ordine['op_Riferimento']) ? $ordine['op_IdProduzione'] . ' (' . $ordine['op_Riferimento'] . ')' : $ordine['op_IdProduzione'],
					'prd_Descrizione' => $ordine['prd_Descrizione'],
					'op_Lotto' => $ordine['op_Lotto'],
					'op_QtaDaProdurre' => $ordine['op_QtaDaProdurre'] . ' [' . $ordine['um_Sigla'] . ']',
					'rp_QtaProdotta' => 'ND',
					'rp_qtaScarti' => 'ND',
					'rp_qtaConformi' => 'ND',
					'DataOraInizio' => $dataInizio->format('Y-m-d H:i:s'),
					'DataOraFine' => $dataFine,
					'Velocita' => 'ND',
					'ValoreOee' => 0,
					'Oee' => '<canvas id="grOEE_' . trim($ordine['op_IdProduzione']) . '" width="100" height="100" style="text-align:center;"></canvas>',
					'azioni' => $azioni,
					'StatoLinea' => 'ok'
				];
			}
		}

		die(json_encode($output));
	}

	// VISUALIZZAZIONE COMMESSE ATTIVI MA NON AVVIATI (STATO = 2 = 'ATTIVO')
	if ($_REQUEST['azione'] == 'attivi') {
		// estraggo la lista
		$sth = $conn_mes->prepare(
			"SELECT * FROM ordini_produzione AS ODP
			LEFT JOIN stati_ordine AS SO ON ODP.op_Stato = SO.so_IdStatoOrdine
			LEFT JOIN prodotti AS P ON ODP.op_Prodotto = P.prd_IdProdotto
			LEFT JOIN linee_produzione AS LP ON ODP.op_LineaProduzione = LP.lp_IdLinea
			LEFT JOIN unita_misura AS UM ON ODP.op_Udm = UM.um_IdRiga
			WHERE ODP.op_Stato = 2 OR ODP.op_Stato = 3"
		);
		$sth->execute();

		$righe = $sth->fetchAll(PDO::FETCH_ASSOC);

		$output = [];

		foreach ($righe as $riga) {

			if (isset($riga['op_DataOrdine'])) {
				$dInizio = new DateTime($riga['op_DataOrdine'] . ' ' . $riga['op_OraOrdine']);
				$stringaDataOrdine = $dInizio->format('d/m/Y - H:i');
			} else {
				$stringaDataOrdine = "";
			}

			if (isset($riga['op_DataProduzione'])) {
				$dInizio = new DateTime($riga['op_DataProduzione'] . ' ' . $riga['op_OraProduzione']);
				$stringaDataProgrammata = $dInizio->format('d/m/Y - H:i');
			} else {
				$stringaDataProgrammata = "";
			}

			if (isset($riga['op_DataFineTeorica'])) {
				$dInizio = new DateTime($riga['op_DataFineTeorica'] . ' ' . $riga['op_OraFineTeorica']);
				$stringaDataFinePrevista = $dInizio->format('d/m/Y - H:i');
			} else {
				$stringaDataFinePrevista = "";
			}


			//Preparo i dati da visualizzare
			$output[] = [
				'IdProduzione' => ($riga['op_Riferimento'] != "" ? $riga['op_IdProduzione'] . ' (' . $riga['op_Riferimento'] . ')' : $riga['op_IdProduzione']),
				'Prodotto' => $riga['prd_Descrizione'],
				'Lotto' => $riga['op_Lotto'],
				'DescrizioneLinea' => $riga['lp_Descrizione'],
				'QtaRichiesta' => $riga['op_QtaDaProdurre'] . "&nbsp;&nbsp;" . $riga['um_Sigla'],
				'DataOraProgrammazione' => $stringaDataProgrammata,
				'DataOraFinePrevista' => $stringaDataFinePrevista,
				'azioni' => '<button type="button" class="btn btn-primary btn-lg espandi-dettaglio-ordine py-1" data-id-ordine-produzione="' . $riga['op_IdProduzione'] . '" title="Vedi dettaglio" disabled><span class="mdi mdi-eye mdi-18px"></span></button>'
			];
		}

		die(json_encode($output));
	}

	// VISUALIZZAZIONE COMMESSE CONCLUSI (STATO = 4 = 'CHIUSO')
	if ($_REQUEST['azione'] == 'chiusi') {
		// estraggo la lista
		$sth = $conn_mes->prepare(
			"SELECT * FROM ordini_produzione AS ODP
			LEFT JOIN stati_ordine AS SO ON ODP.op_Stato = SO.so_IdStatoOrdine
			LEFT JOIN prodotti AS P ON ODP.op_Prodotto = P.prd_IdProdotto
			LEFT JOIN rientro_linea_produzione AS RLP ON ODP.op_IdProduzione = RLP.rlp_IdProduzione
			LEFT JOIN linee_produzione AS LP ON ODP.op_LineaProduzione = LP.lp_IdLinea
			LEFT JOIN unita_misura AS UM ON ODP.op_Udm = UM.um_IdRiga
			WHERE ODP.op_Stato = 5
			ORDER BY RLP.rlp_OraInizio DESC"
		);
		$sth->execute();

		$righe = $sth->fetchAll(PDO::FETCH_ASSOC);


		$output = [];

		foreach ($righe as $riga) {
			$dInizio = new DateTime($riga['rlp_DataInizio'] . ' ' . $riga['rlp_OraInizio']);
			$stringaDataInizio = $dInizio->format('d/m/Y - H:i');


			$dInizio = new DateTime($riga['rlp_DataFine'] . ' ' . $riga['rlp_OraFine']);
			$stringaDataFine = $dInizio->format('d/m/Y - H:i');


			//Preparo i dati da visualizzare
			$output[] = [

				'IdProduzione' => ($riga['op_Riferimento'] != "" ? $riga['op_IdProduzione'] . ' (' . $riga['op_Riferimento'] . ')' : $riga['op_IdProduzione']),
				'Prodotto' => $riga['prd_Descrizione'],
				'Lotto' => $riga['op_Lotto'],
				'DescrizioneLinea' => $riga['lp_Descrizione'],
				'QtaRichiesta' => $riga['op_QtaDaProdurre'] . ' ' . $riga['um_Sigla'],
				'QtaProdotta' => ceil($riga['rlp_QtaProdotta']) . ' ' . $riga['um_Sigla'],
				'QtaConforme' => ceil($riga['rlp_QtaConforme']) . ' ' . $riga['um_Sigla'],
				'QtaScarti' => ceil($riga['rlp_QtaScarti']) . ' ' . $riga['um_Sigla'],
				'DataOraInizio' => $stringaDataInizio,
				'DataOraFine' => $stringaDataFine,
				//'StatoOrdine' => $riga['so_Descrizione'],
				'Oee' => isset($riga['rlp_OEELinea']) ? $riga['rlp_OEELinea'] : 0,
				'azioni' => '<button type="button" class="btn btn-primary btn-lg py-1 espandi-dettaglio-ordine" data-id-produzione="' . $riga['op_IdProduzione'] . '" title="Vedi dettaglio"><span class="mdi mdi-eye mdi-18px"></span></button>'
			];
		}

		die(json_encode($output));
	}

	if ($_REQUEST['azione'] == 'recupera-ordine') {
		unset($_REQUEST['azione']);
		$output = [];
		$now = new DateTime();
		$sth = $conn_mes->prepare(
			"SELECT TOP (1) * FROM ordini_produzione AS ODP
			LEFT JOIN stati_ordine AS SO ON SO.so_IdStatoOrdine = ODP.op_Stato
			LEFT JOIN prodotti AS P ON P.prd_IdProdotto = ODP.op_prodotto
			LEFT JOIN rientro_linea_produzione AS RLP ON RLP.rlp_IdProduzione = ODP.op_IdProduzione
			LEFT JOIN velocita_teoriche AS VT ON VT.vel_IdProdotto = P.prd_IdProdotto AND VT.vel_IdLineaProduzione = ODP.op_LineaProduzione
			LEFT JOIN risorsa_produzione AS RP ON RP.rp_IdProduzione = ODP.op_IdProduzione
			LEFT JOIN linee_produzione AS LP ON LP.lp_IdLinea = ODP.op_LineaProduzione
			LEFT JOIN unita_misura AS UM ON UM.um_IdRiga = ODP.op_Udm
			LEFT JOIN risorsa_produzione_parziale AS RPP ON RPP.rpp_IdProduzione = ODP.op_IdProduzione
			LEFT JOIN risorse AS R ON RPP.rpp_IdRisorsa = R.ris_IdRisorsa AND RPP.rpp_Inizio IS NOT NULL
			WHERE op_IdProduzione = :idProduzione
			ORDER BY ris_Ordinamento DESC "
		);
		$sth->execute($_REQUEST);
		$ordine = $sth->fetch();


		if (isset($ordine['rlp_DataFine'])) {

			$qtaProdotta = $ordine['rlp_QtaProdotta'];
			$qtaScarti = $ordine['rlp_QtaScarti'];
			$qtaConformi = $qtaProdotta - $qtaScarti;
			$dataInizio = new DateTime($ordine['rlp_DataInizio'] . ' ' . $ordine['rlp_OraInizio']);
			$dataFine = new DateTime($ordine['rlp_DataFine'] . ' ' . $ordine['rlp_OraFine']);

			$output = [
				'lp_Descrizione' => $ordine['lp_Descrizione'],
				'op_IdProduzione' => !empty($ordine['op_Riferimento']) ? $ordine['op_IdProduzione'] . ' (' . $ordine['op_Riferimento'] . ')' : $ordine['op_IdProduzione'],
				'prd_Descrizione' => $ordine['prd_Descrizione'],
				'op_Lotto' => $ordine['op_Lotto'],
				'op_QtaDaProdurre' => $ordine['op_QtaDaProdurre'],
				'rp_QtaProdotta' => $qtaProdotta . ' [' . $ordine['um_Sigla'] . ']',
				'rp_qtaScarti' => $qtaScarti . ' [' . $ordine['um_Sigla'] . ']',
				'rp_qtaConformi' => $qtaConformi . ' [' . $ordine['um_Sigla'] . ']',
				'op_DataOraInizio' => $dataInizio->format('Y-m-d H:i:s'),
				'op_DataOraFine' => $dataFine->format('Y-m-d H:i:s'),
				'vel_VelocitaTeoricaLinea' => $ordine['vel_VelocitaTeoricaLinea'],
				'op_NoteProduzione' => $ordine['op_NoteProduzione'],
				'op_Stato' => $ordine['so_Descrizione'],
				'vel_VelReale' => $ordine['rlp_VelocitaLinea'],
				'rlp_TTotale' => $ordine['rlp_TTotale'],
				'rlp_Attrezzaggio' => $ordine['rlp_Downtime'],
				'rlp_Downtime' => $ordine['rlp_Attrezzaggio'],
				'op_QtaDaProdurre' => $ordine['op_QtaDaProdurre'],
				'rlp_QtaProdotta' => $qtaProdotta,
				'rlp_QtaConforme' => $qtaConformi,
				'rlp_QtaScarti' => $qtaScarti,
				'rlp_D' => round($ordine['rlp_D'], 2),
				'rlp_E' => round($ordine['rlp_E'], 2),
				'rlp_Q' => round($ordine['rlp_Q'], 2),
				'rlp_OeeLinea' => round($ordine['rlp_OEELinea'], 2),
			];
		} else {
			$dataInizio = new DateTime($ordine['rlp_DataInizio'] . ' ' . $ordine['rlp_OraInizio']);

			$sth = $conn_mes->prepare(
				"SELECT SUM(rpp_QtaTot) AS qtaTot,
					SUM(rpp_QtaConforme) AS qtaConformi,
					SUM(rpp_QtaScarti) AS qtaScarti,
					SUM(DATEDIFF(
						SECOND,
						rpp_Inizio,
						IIF(rpp_Fine IS NULL, GETDATE(), rpp_Fine)
					)) AS tempoTotale,
					MAX(rpp_Fine) AS dataFine
					FROM risorsa_produzione_parziale
					WHERE rpp_IdProduzione = :IdProduzione
					AND rpp_IdRisorsa = (
						SELECT TOP(1) ris_IdRisorsa FROM risorse_coinvolte
						LEFT JOIN risorse ON ris_IdRisorsa = rc_IdRisorsa
						ORDER BY ris_Ordinamento DESC
					)"
			);
			$sth->execute([
				'IdProduzione' => ($ordine['op_IdProduzione']),
			]);
			$datiRpp = $sth->fetch();
			if (!empty($datiRpp['tempoTotale'])) {
				$tempoSec = $datiRpp['tempoTotale'];
				$qtaProdotta = $datiRpp['qtaTot'];
				$qtaScarti = $datiRpp['qtaScarti'];
			} else {
				$qtaProdotta = $ordine['rlp_QtaProdotta'];
				$qtaScarti = $ordine['rlp_QtaScarti'];

				$tempoSec = $now->getTimestamp() - $dataInizio->getTimestamp();
			}
			$qtaConformi = $qtaProdotta - $qtaScarti;
			$tempoTotale = round($tempoSec / 60);


			$datiOrdine = calcoloTrendCommessa($qtaConformi, $dataInizio, $now, $ordine['vel_VelocitaTeoricaLinea'], $ordine['op_QtaDaProdurre'], $tempoSec);
			$oee = $datiOrdine['oee'];
			$velReale = $datiOrdine['velReale'];
			$dataFinePrevista = $datiOrdine['dataFinePrevista'];

			$indicatoreRendimento = "<img class='indicatori' src='../images/DownArrow_3.png' style='float: right; height:40px; width:40px' hidden>";
			if ($velReale > $ordine["vel_VelocitaTeoricaLinea"]) {
				$indicatoreRendimento = "<img class='indicatori' src='../images/UpArrow_3.png' style='float: right; height:40px; width:40px' hidden>";
			}


			$sth = $conn_mes->prepare(
				"SELECT COUNT(*) AS conto FROM attivita_casi
					WHERE ac_IdProduzione = :IdProduzione
					AND ac_DataFine IS NULL AND ac_IdCaso = 'KO'"
			);
			$sth->execute([':IdProduzione' => $ordine['op_IdProduzione']]);
			$conto_ko = $sth->fetch()['conto'];

			$sth = $conn_mes->prepare(
				"SELECT COUNT(*) AS conto FROM attivita_casi
					WHERE ac_IdProduzione = :IdProduzione
					AND ac_DataFine IS NULL AND ac_IdCaso = 'AT'"
			);
			$sth->execute([':IdProduzione' => $ordine['op_IdProduzione']]);
			$conto_at = $sth->fetch()['conto'];

			$stato = 'ok';
			if ($conto_at > 0) {
				$stato = 'at';
			}
			if ($conto_ko > 0) {
				$stato = 'ko';
			}


			$sth = $conn_mes->prepare(
				"SELECT SUM(DATEDIFF(MINUTE,
					CONVERT(datetime, CONCAT(ldt_DataInizio, 'T', ldt_OraInizio)),
					IIF(
						ldt_DataFine IS NOT NULL,
						CONVERT(datetime, CONCAT(ldt_DataFine, 'T', ldt_OraFine)),
						GETDATE()
					)
				)) AS tempoDown FROM linea_downtime
				WHERE ldt_IdProduzione = :idProduzione"
			);
			$sth->execute($_REQUEST);
			$tempoDown = $sth->fetch()['tempoDown'];
			if (!$tempoDown) {
				$tempoDown = 0;
			}

			$sth = $conn_mes->prepare(
				"SELECT SUM(DATEDIFF(MINUTE,
					CONVERT(datetime, CONCAT(ac_DataInizio, 'T', ac_OraInizio)),
					IIF(
						ac_DataFine IS NOT NULL,
						CONVERT(datetime, CONCAT(ac_DataFine, 'T', ac_OraFine)),
						GETDATE()
					)
				)) AS tempoAttr FROM attivita_casi
				WHERE ac_IdProduzione = :idProduzione AND ac_IdCaso = 'AT'"
			);
			$sth->execute($_REQUEST);
			$tempoAttr = $sth->fetch()['tempoAttr'];
			if (!$tempoAttr) {
				$tempoAttr = 0;
			}

			$output = [
				'lp_Descrizione' => $ordine['lp_Descrizione'],
				'op_IdProduzione' => !empty($ordine['op_Riferimento']) ? $ordine['op_IdProduzione'] . ' (' . $ordine['op_Riferimento'] . ')' : $ordine['op_IdProduzione'],
				'prd_Descrizione' => $ordine['prd_Descrizione'],
				'op_Lotto' => $ordine['op_Lotto'],
				'op_QtaDaProdurre' => $ordine['op_QtaDaProdurre'],
				'rp_QtaProdotta' => $qtaProdotta . ' [' . $ordine['um_Sigla'] . ']',
				'rp_qtaScarti' => $qtaScarti . ' [' . $ordine['um_Sigla'] . ']',
				'rp_qtaConformi' => $qtaConformi . ' [' . $ordine['um_Sigla'] . ']',
				'op_DataOraInizio' => $dataInizio->format('Y-m-d H:i:s'),
				'op_DataOraFine' => $dataFinePrevista->format('Y-m-d H:i:s'),
				'vel_VelocitaTeoricaLinea' => $ordine['vel_VelocitaTeoricaLinea'],
				'op_NoteProduzione' => $ordine['op_NoteProduzione'],
				'op_Stato' => $ordine['so_Descrizione'],
				'vel_VelReale' => round($velReale, 2),
				'rlp_TTotale' => $tempoTotale,
				'rlp_Attrezzaggio' => $tempoAttr,
				'rlp_Downtime' => $tempoDown,
				'op_QtaDaProdurre' => $ordine['op_QtaDaProdurre'],
				'rlp_QtaProdotta' => $qtaProdotta,
				'rlp_QtaConforme' => $qtaConformi,
				'rlp_QtaScarti' => $qtaScarti,
				'rlp_D' => '...',
				'rlp_E' => '...',
				'rlp_Q' => '...',
				'rlp_OeeLinea' => round($oee * 100, 2),
			];
		}
		die(json_encode($output));
	}

	// DETTAGLIO DISTINTA RISORSE PER L'COMMESSA DI PRODUZIONE CONSIDERATO (PREDISPONGO DATI NELLA RELATIVA TABELLA DI LAVORO E LA VISUALIZZO)
	if ($_REQUEST['azione'] == 'mostra-distinta-risorse') {
		unset($_REQUEST['azione']);
		unset($_REQUEST['_']);
		// seleziono i dati della tabella di lavoro 'risorse_coinvolte'
		$sth = $conn_mes->prepare(
			"SELECT risorse_coinvolte.*, risorse.*, risorsa_produzione.*, ordini_produzione.*, unita_misura.*, um2.um_Sigla AS udmRisorsa
			FROM risorse_coinvolte
			LEFT JOIN risorse ON rc_IdRisorsa = ris_IdRisorsa
			LEFT JOIN risorsa_produzione ON rc_IdRisorsa = rp_IdRisorsa AND rp_IdProduzione = rc_IdProduzione
			LEFT JOIN ordini_produzione ON rp_IdProduzione = op_IdProduzione
			LEFT JOIN unita_misura ON op_Udm = um_IdRiga
			LEFT JOIN unita_misura AS um2 ON risorse.ris_Udm = um2.um_IdRiga
			WHERE rc_IdProduzione = :idProduzione"
		);
		$sth->execute($_REQUEST);
		$output = [];
		$risorse = $sth->fetchAll(PDO::FETCH_ASSOC);

		foreach ($risorse as $risorsa) {

			if (isset($risorsa['rp_DataInizio'])) {
				$dInizio = new DateTime($risorsa['rp_DataInizio'] . ' ' . $risorsa['rp_OraInizio']);
				$stringaDataInizio = $dInizio->format('d/m/Y - H:i:s');
			} else {
				$stringaDataInizio = 'IN ATTESA';
			}

			if (isset($risorsa['rp_DataFine'])) {
				$dFine = new DateTime($risorsa['rp_DataFine'] . ' ' . $risorsa['rp_OraFine']);
				$stringaDataFine = $dFine->format('d/m/Y - H:i:s');
			} else {
				if (!isset($risorsa['rp_DataInizio'])) {
					$stringaDataFine = 'IN ATTESA';
				} else {
					$stringaDataFine = "IN CORSO...";
				}
			}



			if (isset($risorsa['rp_DataInizio']) && !isset($risorsa['rp_DataFine'])) {

				//calcolo totali di produzione e tempo di lavorazione
				$sth = $conn_mes->prepare(
					"SELECT SUM(rpp_QtaTot) AS qtaTot,
					SUM(rpp_QtaConforme) AS qtaConformi,
					SUM(rpp_QtaScarti) AS qtaScarti,
					SUM(DATEDIFF(
						SECOND,
						rpp_Inizio,
						IIF (rpp_Fine IS NOT NULL, rpp_Fine, GETDATE())
					)) AS tempoTotale,
					IIF (MAX(rpp_Fine) IS NOT NULL, MAX(rpp_Fine), GETDATE()) AS dataFine
					FROM risorsa_produzione_parziale
					WHERE rpp_IdProduzione = :IdProduzione AND rpp_IdRisorsa = :IdRisorsa"
				);
				$sth->execute([
					'IdProduzione' => $_REQUEST['idProduzione'],
					'IdRisorsa' => $risorsa['rc_IdRisorsa'],
				]);
				$datiRpp = $sth->fetch();
				$tempoSec = $datiRpp['tempoTotale'];
				$qtaTot = $datiRpp['qtaTot'];
				$qtaConformi = $datiRpp['qtaConformi'];
				$qtaScarti = $datiRpp['qtaScarti'];
				$dataFine = new DateTime($datiRpp['dataFine']);


				$_REQUEST['idRisorsa'] = $risorsa['rc_IdRisorsa'];
				// INDICAZIONE APPROSSIMATIVA DELL'OEE RISORSA
				$now = new DateTime();
				$dataOdierna = $now->format('Y-m-d');
				$oraOdierna = $now->format('H:i:s');
				$risorsa['rp_QtaConforme'] = $qtaConformi;
				// eseguo differenza tra la marca oraria attuale e quella di inizio produzione, per la produzione in oggetto
				$tempoTotaleRisorsa = $tempoSec;

				$sth = $conn_mes->prepare(
					"SELECT SUM(DATEDIFF(SECOND,
						CONVERT(datetime, CONCAT(rdt_DataInizio, 'T', rdt_OraInizio)),
						IIF(
							rdt_DataFine IS NOT NULL,
							CONVERT(datetime, CONCAT(rdt_DataFine, 'T', rdt_OraFine)),
							GETDATE()
						)
					)) AS tempoDown FROM risorsa_downtime
					WHERE rdt_IdProduzione = :idProduzione AND rdt_IdRisorsa = :idRisorsa"
				);
				$sth->execute($_REQUEST);
				$tempoDown = $sth->fetch()['tempoDown'];

				$sth = $conn_mes->prepare(
					"SELECT SUM(DATEDIFF(SECOND,
						CONVERT(datetime, CONCAT(ac_DataInizio, 'T', ac_OraInizio)),
						IIF(
							ac_DataFine IS NOT NULL,
							CONVERT(datetime, CONCAT(ac_DataFine, 'T', ac_OraFine)),
							GETDATE()
						)
					)) AS tempoAttr FROM attivita_casi
					WHERE ac_IdProduzione = :idProduzione AND ac_IdRisorsa = :idRisorsa AND ac_IdCaso = 'AT'"
				);
				$sth->execute($_REQUEST);
				$tempoAttr = $sth->fetch()['tempoAttr'];

				$tempoTotale = round($tempoTotaleRisorsa / 60);

				$velReale = 0;
				if ($tempoTotale != 0) {
					$velReale = $risorsa['rp_QtaConforme'] / ($tempoTotale / 60);
				}

				// recupero il valore della velocità teorica linea
				$sth = $conn_mes->prepare(
					"SELECT vel_VelocitaTeoricaLinea FROM velocita_teoriche
					LEFT JOIN ordini_produzione ON vel_IdProdotto = op_Prodotto
					WHERE op_IdProduzione = :IdProduzione AND vel_IdLineaProduzione = :IdLineaProduzione"
				);
				$sth->execute([
					':IdProduzione' => $_REQUEST['idProduzione'],
					':IdLineaProduzione' => $risorsa['op_LineaProduzione']
				]);
				$velTeorica = $sth->fetch(PDO::FETCH_ASSOC)['vel_VelocitaTeoricaLinea'];


				$tempoTeoricoPezzoLinea_pzh = isset($velTeorica) ? floatval($velTeorica) : 1;

				// converto il tempo teorico per la realizzazione del pezzo da (pezzi/h) a (pezzi/sec)
				$tempoTeoricoPezzoLinea_sec = floatval($tempoTeoricoPezzoLinea_pzh / 3600);

				// CALCOLO |OEE| RISORSA (N.B: formula modificata senza utilizzo delle 3 componenti: n° pezzi conformi / n° pezzi teorici nel tempo totale)
				$numeroPezziTeoriciRisorsa = intval($tempoTotaleRisorsa * $tempoTeoricoPezzoLinea_sec);
				$OEERisorsa = round(floatval(($risorsa['rp_QtaConforme'] / ($numeroPezziTeoriciRisorsa != 0 ? $numeroPezziTeoriciRisorsa : 1)) * 100), 2);

				//Preparo i dati da visualizzare
				$output[] = [
					'Descrizione' => $risorsa['ris_Descrizione'],
					'DataInizio' => $stringaDataInizio,
					'DataFine' => $stringaDataFine,
					'QtaConforme' => $risorsa['rp_QtaConforme'] . ' ' . $risorsa['udmRisorsa'],
					'QtaScarti' => $risorsa['rp_QtaScarti'] . ' ' . $risorsa['udmRisorsa'],
					'TTotale' => $tempoTotale,
					'Attrezzaggio' => round($tempoAttr / 60),
					'Downtime' => round($tempoDown / 60),
					'OEERisorsa' => $OEERisorsa > 100 ? $OEERisorsa : $OEERisorsa,
					'DRisorsa' => '...',
					'ERisorsa' => '...',
					'QRisorsa' => '...',
					'Velocita' => round($velReale),
					'NoteFine' => '',
					'AuxStatoDowntime_Man' => $risorsa['ris_Avaria_Man'],
					'AuxStatoDowntime_Auto' => $risorsa['ris_Avaria_Scada'],
					'AuxStatoAttrezzaggio_Man' => $risorsa['ris_Attrezzaggio_Man'],
					'AuxStatoAttrezzaggio_Auto' => $risorsa['ris_Attrezzaggio_Scada'],
					'AuxStatoPPrevista_Man' => $risorsa['ris_PausaPrevista_Man'],
					'AuxStatoPPrevista_Auto' => $risorsa['ris_PausaPrevista_Scada'],
					'Ordinamento' => $risorsa['ris_Ordinamento']

				];
			} else if (isset($risorsa['rp_DataInizio']) && isset($risorsa['rp_DataFine'])) {

				//Preparo i dati da visualizzare
				$output[] = [
					'Descrizione' => $risorsa['ris_Descrizione'],
					'DataInizio' => $stringaDataInizio,
					'DataFine' => $stringaDataFine,
					'QtaConforme' => $risorsa['rp_QtaConforme'] . ' ' . $risorsa['udmRisorsa'],
					'QtaScarti' => $risorsa['rp_QtaScarti'] . ' ' . $risorsa['udmRisorsa'],
					'TTotale' => $risorsa['rp_TTotale'],
					'Attrezzaggio' => $risorsa['rp_Attrezzaggio'],
					'Downtime' => $risorsa['rp_Downtime'],
					'DRisorsa' =>  round($risorsa['rp_D'], 2),
					'ERisorsa' =>  round($risorsa['rp_E'], 2),
					'QRisorsa' =>  round($risorsa['rp_Q'], 2),
					'OEERisorsa' =>  round($risorsa['rp_OEE'], 2),
					'Velocita' => $risorsa['rp_VelocitaRisorsa'],
					'NoteFine' => $risorsa['rp_NoteFine'],
					'AuxStatoDowntime_Man' => $risorsa['ris_Avaria_Man'],
					'AuxStatoDowntime_Auto' => $risorsa['ris_Avaria_Scada'],
					'AuxStatoAttrezzaggio_Man' => $risorsa['ris_Attrezzaggio_Man'],
					'AuxStatoAttrezzaggio_Auto' => $risorsa['ris_Attrezzaggio_Scada'],
					'AuxStatoPPrevista_Man' => $risorsa['ris_PausaPrevista_Man'],
					'AuxStatoPPrevista_Auto' => $risorsa['ris_PausaPrevista_Scada'],
					'Ordinamento' => $risorsa['ris_Ordinamento']
				];
			}
		}

		die(json_encode($output));
	}


	if ($_REQUEST['azione'] == 'mostra-distinta-componenti') {
		// recupero quantità di pezzi da produrre, secondo commessa
		$qtaRichiestaCommessa = floatval($_REQUEST['qtaRichiesta']);

		// seleziono i dati della tabella di lavoro 'componenti_work'
		$sth = $conn_mes->prepare(
			"SELECT * FROM componenti
			LEFT JOIN prodotti ON cmp_Componente = prd_IdProdotto
			LEFT JOIN unita_misura ON cmp_Udm = um_IdRiga
			WHERE cmp_IdProduzione = :idProduzione"
		);
		$sth->execute(['idProduzione' => $_REQUEST['idProduzione']]);

		$output = [];

		$righe = $sth->fetchAll(PDO::FETCH_ASSOC);

		foreach ($righe as $riga) {

			// calcolo il fabbisogno relativo al componente applicando la formula: 'fabbisogno = ((qta pezzi commessa * fattore moltiplicativo componente) / pezzi scatola componente)'
			$fabbisogno = ceil(($qtaRichiestaCommessa * $riga['cmp_FattoreMoltiplicativo']) / $riga['cmp_PezziConfezione']);

			//Preparo i dati da visualizzare
			$output[] = [
				'IdProdotto' => $riga['cmp_Componente'],
				'Descrizione' => $riga['prd_Descrizione'],
				'QuantitaComponente' => $fabbisogno . ' ' . $riga['um_Sigla']
			];
		}

		die(json_encode($output));
	}

	// DETTAGLIO CASI REGISTRATI PER PRODUZIONE IN OGGETTO
	if ($_REQUEST['azione'] == 'mostra-casi-produzione-cumulativo') {

		$dataOdierna = date('Y-m-d');
		$oraOdierna = date('H:i:s');

		// seleziono i dati della tabella di lavoro 'risorse_coinvolte_work'
		$sth = $conn_mes->prepare(
			"SELECT AC.ac_DescrizioneEvento, R.ris_Descrizione , C.cas_Tipo, GC.gc_Descrizione, AC.ac_Note, COUNT(*) As TotaleEventi,
			SUM(
				datediff(MINUTE,
					(CONVERT(Datetime, CONCAT(AC.ac_DataInizio,'T',AC.ac_OraInizio))),
					IIF (AC.ac_DataFine IS NOT NULL,
						(CONVERT(Datetime, CONCAT(AC.ac_DataFine, 'T', AC.ac_OraFine))),
						(CONVERT(Datetime, CONCAT(:DataOdierna, 'T', :OraOdierna)))
					)
				)
			) AS TotaleDowntimeTipo
			FROM attivita_casi AS AC
			LEFT JOIN casi AS C ON AC.ac_IdEvento = C.cas_IdEvento AND AC.ac_IdRisorsa = C.cas_IdRisorsa
			LEFT JOIN risorse AS R ON AC.ac_IdRisorsa = R.ris_IdRisorsa
			LEFT JOIN gruppi_casi AS GC ON C.cas_Gruppo = GC.gc_IdRiga
			WHERE AC.ac_IdProduzione = :IdOrdineProduzione
			GROUP BY AC.ac_DescrizioneEvento, R.ris_Descrizione , C.cas_Tipo, GC.gc_Descrizione, AC.ac_Note"
		);
		$sth->execute(
			[
				':IdOrdineProduzione' => $_REQUEST['idProduzione'],
				':DataOdierna' => $dataOdierna,
				':OraOdierna' => $oraOdierna
			]
		);

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
				'Gruppo' => $riga['gc_Descrizione'],
				'NumeroEventi' => $riga['TotaleEventi'],
				'Durata' => (float)$riga['TotaleDowntimeTipo'],
				'Note' =>  $riga['ac_Note']
			];
		}

		die(json_encode($output));
	}

	// DETTAGLIO CASI REGISTRATI PER PRODUZIONE IN OGGETTO
	if ($_REQUEST['azione'] == 'mostra-casi-produzione') {

		// seleziono i dati della tabella di lavoro 'risorse_coinvolte_work'
		$sth = $conn_mes->prepare(
			"SELECT * FROM attivita_casi
			LEFT JOIN casi ON ac_IdEvento = cas_IdEvento AND ac_IdRisorsa = cas_IdRisorsa
			LEFT JOIN risorse ON ac_IdRisorsa = ris_IdRisorsa
			LEFT JOIN gruppi_casi ON cas_Gruppo = gc_IdRiga
			WHERE ac_IdProduzione = :IdProduzione"
		);
		$sth->execute([':IdProduzione' => $_REQUEST['idProduzione']]);

		$output = [];

		$righe = $sth->fetchAll(PDO::FETCH_ASSOC);

		foreach ($righe as $riga) {

			if (isset($riga['ac_DataInizio'])) {
				$dInizio = new DateTime($riga['ac_DataInizio'] . ' ' . $riga['ac_OraInizio']);
				$stringaDataInizio = $dInizio->format('d/m/Y - H:i:s');
			} else {
				$stringaDataInizio = "";
			}

			if (isset($riga['ac_DataFine'])) {
				$dFine = new DateTime($riga['ac_DataFine'] . ' ' . $riga['ac_OraFine']);
				$stringaDataFine = $dFine->format('d/m/Y - H:i:s');
			} else {
				$dFine = new DateTime();
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
			$durataEvento_min = ceil($durataEvento_sec / 60);
			//Preparo i dati da visualizzare
			$output[] = [

				'DescrizioneRisorsa' => $riga['ris_Descrizione'],
				'DescrizioneCaso' => $riga['ac_DescrizioneEvento'],
				'TipoEvento' => $tipoEvento,
				'Gruppo' => $riga['gc_Descrizione'],
				'DataInizio' => $stringaDataInizio,
				'DataFine' => $stringaDataFine,
				'Durata' => (float)$durataEvento_min,
				'Note' =>  $riga['ac_Note']
			];
		}

		die(json_encode($output));
	}

	// DETTAGLIO CLIENTI PER PRODUZIONE IN OGGETTO
	if ($_REQUEST['azione'] == 'mostra-clienti') {

		$sth = $conn_mes->prepare(
			"SELECT * FROM clienti_ordine
			LEFT JOIN clienti ON co_IdCliente = cl_IdRiga
			WHERE co_IdProduzione = :idProduzione"
		);
		$sth->execute(['idProduzione' => $_REQUEST['idProduzione']]);
		$clienti = $sth->fetchAll();

		$output = [];
		foreach ($clienti as $cliente) {
			$output[] = [
				'cl_Descrizione' => $cliente['cl_Descrizione'],
				'co_Qta' => $cliente['co_Qta'],
				'co_Note' => $cliente['co_Note'],
			];
		}

		die(json_encode($output));
	}
}

?>
<!DOCTYPE html>
<html lang="it">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>PROSPECT40 | Dashboard commesse</title>
	<?php include("inc_css.php") ?>
</head>

<body>

	<div class="container-scroller">

		<?php include("inc_testata.php") ?>

		<div class="container-fluid page-body-wrapper">

			<div class="main-panel">

				<div class="content-wrapper">

					<div class="card" id="blocco-elenco">

						<div class="card-header">
							<h4 class="card-title m-2">DASHBOARD COMMESSE</h4>
						</div>
						<div id="pannelloElencoOrdini" class="collapse multi-collapse" aria-labelledby="headingOne">
							<div class="card-body">

								<ul class="nav nav-tabs" id="tab-stato-ordini" role="tablist">
									<li class="nav-item text-center" style="width: calc(100% / 3);">
										<a aria-controls="stato-ordini-avviati" aria-selected="true" class="nav-link rounded-2 show"
											data-toggle="tab" href="#stato-ordini-avviati" id="tab-stato-ordini-avviati"
											role="tab"><b>COMMESSE IN CORSO</b></a>
									</li>
									<li class="nav-item text-center" style="width: calc(100% / 3);">
										<a aria-controls="stato-ordini-attivi" aria-selected="true" class="nav-link rounded-2"
											data-toggle="tab" href="#stato-ordini-attivi" id="tab-stato-ordini-attivi" role="tab"><b>COMMESSE
												PROGRAMMATE</b></a>
									</li>
									<li class="nav-item text-center" style="width: calc(100% / 3);">
										<a aria-controls="stato-ordini-chiusi" aria-selected="true" class="nav-link rounded-2"
											data-toggle="tab" href="#stato-ordini-chiusi" id="tab-stato-ordini-chiusi" role="tab"><b>COMMESSE
												COMPLETATE</b></a>
									</li>
								</ul>


								<div class="tab-content tab-stato-ordini">

									<!-- Tab VISUALIZZAZIONE COMMESSE AVVIATI -->
									<div aria-labelledby="tab-stato-ordini-avviati" class="tab-pane show" id="stato-ordini-avviati"
										role="tabpanel">
										<div class="row">

											<div class="col-12">

												<div class="table-responsive">

													<table id="tabellaDati-ordini-avviati" class="table table-striped" style="width:100%"
														data-source="">
														<thead>
															<tr>
																<th>Linea</th>
																<th>Commessa (rif.)</th>
																<th>Prodotto </th>
																<th>Lotto</th>
																<th>Qta da prod.</th>
																<th>Qta tot.</th>
																<th>Qta conforme</th>
																<th>Qta scarti</th>
																<th>Data-ora inizio</th>
																<th>Fine prevista</th>
																<th>Vel. linea</th>
																<th>Valore OEE</th>
																<th>Indice OEE [%]</th>
																<th></th>
																<th></th>
															</tr>
														</thead>
														<tbody></tbody>

													</table>


												</div>
												<!-- <button type="button" class="test" id="test" style="font-size: 0.6vw; " >TEST</button> -->
											</div>
										</div>
									</div>

									<!-- Tab VISUALIZZAZIONE COMMESSE ATTIVI -->
									<div aria-labelledby="tab-stato-ordini-attivi" class="tab-pane" id="stato-ordini-attivi"
										role="tabpanel">
										<div class="row">

											<div class="col-12">

												<div class="table-responsive">

													<table id="tabellaDati-ordini-attivi" class="table table-striped" style="width:100%"
														data-source="">
														<thead>
															<tr>
																<th>Commessa (Rif.)</th>
																<th>Prodotto </th>
																<th>Lotto</th>
																<th>Linea</th>
																<th>Qta da prod.</th>
																<th>Inizio previsto</th>
																<th>Fine prevista</th>
																<th></th>
															</tr>
														</thead>
														<tbody></tbody>

													</table>

												</div>
											</div>
										</div>
									</div>


									<!-- Tab VISUALIZZAZIONE COMMESSE CHIUSI -->
									<div aria-labelledby="tab-stato-ordini-chiusi" class="tab-pane" id="stato-ordini-chiusi"
										role="tabpanel">


										<div class="row">

											<div class="col-12">

												<div class="table-responsive">

													<table id="tabellaDati-ordini-chiusi" class="table table-striped" style="width:100%"
														data-source="">
														<thead>
															<tr>
																<th>Commessa (Rif.)</th>
																<th>Prodotto </th>
																<th>Lotto</th>
																<th>Linea</th>
																<th>Qta da prod.</th>
																<th>Qta tot.</th>
																<th>Qta conformi</th>
																<th>Qta scarti</th>
																<th>Data-ora inizio</th>
																<th>Data-ora fine</th>
																<th>OEE [%]</th>
																<th></th>
															</tr>
														</thead>
														<tbody></tbody>

													</table>

												</div>
											</div>
										</div>

									</div>
								</div>
							</div>

						</div>

					</div>


					<div class="card mt-2" id="blocco-modifica">

						<div class="card-header">
							<h4 class="card-title m-2">DETTAGLIO COMMESSA</h4>
						</div>

						<div id="pannelloDettaglioOrdine" class="collapse multi-collapse" aria-labelledby="headingOne">

							<div class="card-body flex flex-column">

								<form class="" id="form-dati-ordine">



									<div class="row">

										<div class="col-md-6">

											<div class="row">

												<div class="col-3">
													<div class="form-group ">
														<label for="op_IdProduzione" class="">Codice commessa</label>
														<input readonly type="text" class="form-control form-control-sm dati-ordine"
															id="op_IdProduzione" name="op_IdProduzione" aria-label=""
															aria-describedby="inputGroup-sizing-lg">
													</div>
												</div>

												<div class="col-9">
													<div class="form-group ">
														<label for="prd_Descrizione" class="">Prodotto</label>
														<input readonly type="text" class="form-control form-control-sm dati-ordine"
															id="prd_Descrizione" name="prd_Descrizione" aria-label=""
															aria-describedby=" inputGroup-sizing-lg">
													</div>
												</div>
												<div class="col-2">
													<div class="form-group ">
														<label for="op_Lotto" class="">Lotto</label>
														<input readonly type="text" class="form-control form-control-sm dati-ordine" id="op_Lotto"
															name="op_Lotto aria-label="" aria-describedby=" inputGroup-sizing-lg">
													</div>
												</div>
												<div class="col-3">
													<div class="form-group ">
														<label for="lp_Descrizione" class="">Linea</label>
														<input readonly type="text" class="form-control form-control-sm dati-ordine"
															id="lp_Descrizione" name="lp_Descrizione" aria-label=""
															aria-describedby="inputGroup-sizing-lg">
													</div>
												</div>

												<div class="col-7">
													<div class="form-group">
														<label for="op_NoteProduzione" class="">Note di produzione</b></label>
														<input readonly type="text" class="form-control form-control-sm dati-ordine"
															id="op_NoteProduzione" name="op_NoteProduzione" aria-label=""
															aria-describedby="inputGroup-sizing-lg">
													</div>
												</div>



												<!--
											<div class="col-4 form-group m-0 d-flex flex-column justify-content-end">
												<div class="form-group">
													<button type="button" class="btn btn-primary btn-lg btn-block float-right gestione-generale mt-2" id="riprendi-ordine-parziale" style="font-size: 0.7vw;"  >RIPRENDI COMMESSA</button>
												</div>
											</div>
											-->

												<div class="col-2">
													<div class="form-group ">
														<label for="rlp_D" class="">(D)isp. [%]</label>
														<input readonly type="text" class="form-control form-control-sm dati-ordine sfondo-d"
															id="rlp_D" name="rlp_D" aria-label="" aria-describedby="inputGroup-sizing-lg">
													</div>
												</div>
												<div class="col-2">
													<div class="form-group ">
														<label for="rlp_E" class="">(E)ffic. [%]</label>
														<input readonly type="text" class="form-control form-control-sm dati-ordine sfondo-e"
															id="rlp_E" name="rlp_E" aria-label="" aria-describedby="inputGroup-sizing-lg">
													</div>
												</div>
												<div class="col-2">
													<div class="form-group ">
														<label for="rlp_Q" class="">(Q)ual. [%]</label>
														<input readonly type="text" class="form-control form-control-sm dati-ordine sfondo-q"
															id="rlp_Q" name="rlp_Q" aria-label="" aria-describedby="inputGroup-sizing-lg">
													</div>
												</div>
												<div class="col-2">
													<div class="form-group ">
														<label for="rlp_OeeLinea" class="">OEE [%]</label>
														<input readonly type="text" class="form-control form-control-sm dati-ordine sfondo-oee"
															id="rlp_OeeLinea" name="rlp_OeeLinea" aria-label=""
															aria-describedby="inputGroup-sizing-lg">
													</div>
												</div>


												<div class="col-4 form-group m-0 d-flex flex-column justify-content-end">
												</div>
											</div>
										</div>

										<div class="col-md-6">
											<div class="row">

												<div class="col-3">
													<div class="form-group ">
														<label for="op_DataOraInizio" class="">Ora inizio</label>
														<input readonly type="text" class="form-control form-control-sm dati-ordine"
															id="op_DataOraInizio" name="op_DataOraInizio" aria-label=""
															aria-describedby="inputGroup-sizing-lg">
													</div>
												</div>

												<div class="col-3">
													<div class="form-group ">
														<label for="op_DataOraFine" class="">Ora fine</label>
														<input readonly type="text" class="form-control form-control-sm dati-ordine"
															id="op_DataOraFine" name="op_DataOraFine" aria-label=""
															aria-describedby="inputGroup-sizing-lg">
													</div>
												</div>

												<div class="col-3">
													<div class="form-group ">
														<label for="vel_VelocitaTeoricaLinea" class="">V. teorica <span
																class="ml-1 udm-vel">[Pz]</span></label>
														<input readonly type="text" class="form-control form-control-sm dati-ordine"
															id="vel_VelocitaTeoricaLinea" name="vel_VelocitaTeoricaLinea" aria-label=""
															aria-describedby="inputGroup-sizing-lg">
													</div>
												</div>

												<div class="col-3">
													<div class="form-group ">
														<label for="vel_VelReale" class="">V. reale <span class="ml-1 udm-vel">[Pz]</span></label>
														<input readonly type="text" class="form-control form-control-sm dati-ordine"
															id="vel_VelReale" name="vel_VelReale" aria-label=""
															aria-describedby="inputGroup-sizing-lg">
													</div>
												</div>

												<div class="col-3">
													<div class="form-group ">
														<label for="op_Stato" class="">Stato</label>
														<input readonly type="text" class="form-control form-control-sm dati-ordine" id="op_Stato"
															name="op_Stato" aria-label="" aria-describedby="inputGroup-sizing-lg">
													</div>
												</div>

												<div class="col-3">
													<div class="form-group ">
														<label for="rlp_TTotale" class="">T. tot. [min]</label>
														<input readonly type="text" class="form-control form-control-sm dati-ordine"
															id="rlp_TTotale" name="rlp_TTotale" aria-label="" aria-describedby="inputGroup-sizing-lg">
													</div>
												</div>

												<div class="col-3">
													<div class="form-group">
														<label for="rlp_Attrezzaggio" class="">T. attr. [min]</label>
														<input readonly type="text" class="form-control form-control-sm dati-ordine"
															id="rlp_Attrezzaggio" name="rlp_Attrezzaggio" aria-label=""
															aria-describedby="inputGroup-sizing-lg">
													</div>
												</div>

												<div class="col-3">
													<div class="form-group">
														<label for="rlp_Downtime" class="">Downtime [min]</label>
														<input readonly type="text" class="form-control form-control-sm dati-ordine"
															id="rlp_Downtime" name="rlp_Downtime" aria-label=""
															aria-describedby="inputGroup-sizing-lg">
													</div>
												</div>


												<div class="col-3">
													<div class="form-group ">
														<label for="op_QtaDaProdurre" class="">Qta ric. <span class="ml-1 udm">[Pz]</span></label>
														<input readonly type="text" class="form-control form-control-sm dati-ordine"
															id="op_QtaDaProdurre" name="op_QtaDaProdurre" aria-label=""
															aria-describedby="inputGroup-sizing-lg">
													</div>
												</div>

												<div class="col-3">
													<div class="form-group ">
														<label for="rlp_QtaProdotta" class="">Qta prod. <span class="ml-1 udm">[Pz]</span></label>
														<input readonly type="text" class="form-control form-control-sm dati-ordine"
															id="rlp_QtaProdotta" name="rlp_QtaProdotta" aria-label=""
															aria-describedby="inputGroup-sizing-lg">
													</div>
												</div>

												<div class="col-3">
													<div class="form-group ">
														<label for="rlp_QtaConforme" class="">Qta conforme<span class="ml-1 udm">[Pz]</span></label>
														<input readonly type="text" class="form-control form-control-sm dati-ordine"
															id="rlp_QtaConforme" name="rlp_QtaConforme" aria-label=""
															aria-describedby="inputGroup-sizing-lg">
													</div>
												</div>

												<div class="col-3">
													<div class="form-group ">
														<label for="rlp_QtaScarti" class="">Qta scarti <span class="ml-1 udm">[Pz]</span></label>
														<input readonly type="text" class="form-control form-control-sm dati-ordine"
															id="rlp_QtaScarti" name="rlp_QtaScarti" aria-label=""
															aria-describedby="inputGroup-sizing-lg">
													</div>
												</div>



											</div>
										</div>

									</div>


								</form>

								<ul class="nav nav-tabs mt-4" id="tab-elenchi" role="tablist">
									<li class="nav-item text-center" style="width: calc(100% / 5);">
										<a aria-controls="risorse" aria-selected="true" class="nav-link active show" data-toggle="tab"
											href="#risorse" id="tab-risorse" role="tab"><b>ELENCO MACCHINE</b></a>
									</li>
									<li class="nav-item text-center" style="width: calc(100% / 5);" hidden>
										<a aria-controls="componenti" aria-selected="true" class="nav-link" data-toggle="tab"
											href="#componenti" id="tab-componenti" role="tab"><b>ELENCO COMPONENTI</b></a>
									</li>
									<li class="nav-item text-center" style="width: calc(100% / 5);">
										<a aria-controls="casi" aria-selected="true" class="nav-link" data-toggle="tab"
											href="#casi-cumulativo" id="tab-casi-cumulativo" role="tab"><b>CUMULATIVO EVENTI</b></a>
									</li>
									<li class="nav-item text-center" style="width: calc(100% / 5);">
										<a aria-controls="casi" aria-selected="true" class="nav-link" data-toggle="tab" href="#casi"
											id="tab-casi" role="tab"><b>DETTAGLIO EVENTI</b></a>
									</li>
									<li class="nav-item text-center" style="width: calc(100% / 5);" hidden>
										<a aria-controls="clienti" aria-selected="true" class="nav-link" data-toggle="tab" href="#clienti"
											id="tab-clienti" role="tab"><b>CLIENTI</b></a>
									</li>
								</ul>

								<div class="tab-content">

									<div aria-labelledby="tab-risorse" class="tab-pane fade show active" id="risorse" role="tabpanel">

										<div class="table-responsive">

											<table id="tabellaDati-distinta-risorse" class="table table-striped" style="width:100%"
												data-source="statoordini.php?azione=mostra-dettagli-distinte">
												<thead>
													<tr>
														<th>Descrizione</th>
														<th>Orario inizio</th>
														<th>Orario fine</th>
														<th>Qta conf</th>
														<th>Qta scarti</th>
														<th>T. tot. [min]</th>
														<th>T. attr. [min]</th>
														<th>T. down [min]</th>
														<th>(D)isp. [%]</th>
														<th>(E)ffic. [%]</th>
														<th>(Q)ual. [%]</th>
														<th>OEE [%]</th>
														<th>Vel. reale [pz/h]</th>
														<th>Aux stato downtime man</th>
														<th>Aux stato downtime auto</th>
														<th>Aux stato attrezzaggio man</th>
														<th>Aux stato attrezzaggio auto</th>
														<th>Aux stato pausa man</th>
														<th>Aux stato pausa auto</th>
														<th>Ordinamento</th> <!-- Non visibile -->

													</tr>
												</thead>
												<tbody></tbody>

											</table>

										</div>
									</div>

									<div aria-labelledby="tab-componenti" class="tab-pane fade" id="componenti" role="tabpanel">

										<div class="table-responsive">

											<table id="tabellaDati-distinta-componenti" class="table table-striped" style="width:100%"
												data-source="statoordini.php?azione=mostra-dettagli-componenti">
												<thead>
													<tr>
														<th>Codice componente</th>
														<th>Descrizione</th>
														<th>Fabbisogno</th>
													</tr>
												</thead>
												<tbody></tbody>

											</table>

										</div>

									</div>

									<div aria-labelledby="tab-casi-cumulativo" class="tab-pane fade" id="casi-cumulativo" role="tabpanel">

										<div class="table-responsive">

											<table id="tabellaDati-casi-produzione-cumulativo" class="table table-striped" style="width:100%"
												data-source="statoordini.php?azione=mostra-dettagli-casi-cumulativo">
												<thead>
													<th>Macchina</th>
													<th>Descrizione evento</th>
													<th>Tipo</th>
													<th>Gruppo</th>
													<th>N° eventi</th>
													<th>Durata [min]</th>
													</tr>
												</thead>
												<tbody></tbody>

											</table>

										</div>

									</div>

									<div aria-labelledby="tab-casi" class="tab-pane fade" id="casi" role="tabpanel">

										<div class="table-responsive">

											<table id="tabellaDati-casi-produzione" class="table table-striped" style="width:100%"
												data-source="statoordini.php?azione=mostra-dettagli-casi">
												<thead>
													<tr>
														<th>Macchina</th>
														<th>Descrizione evento</th>
														<th>Tipo</th>
														<th>Gruppo</th>
														<th>Orario inizio</th>
														<th>Orario fine</th>
														<th>Durata [min]</th>
														<th>Note e segnalazioni</th>
													</tr>
												</thead>
												<tbody></tbody>

											</table>

										</div>

									</div>



									<div aria-labelledby="tab-clienti" class="tab-pane fade" id="clienti" role="tabpanel">

										<div class="table-responsive">

											<table id="tabellaDati-clienti" class="table table-striped" style="width:100%">
												<thead>
													<tr>
														<th>Descrizione</th>
														<th>Quantità</th>
														<th>Note</th>
													</tr>
												</thead>
												<tbody></tbody>

											</table>

										</div>

									</div>



								</div>
							</div>
						</div>
					</div>
				</div>

				<?php include("inc_footer.php") ?>

			</div>

		</div>

	</div>


	<!-- Pulsante aggiunta nuova risorsa alla distinta -->
	<button type="button" id="ritorna-elenco-ordini" class="mdi mdi-button" hidden>CHIUDI</button>

	<?php include("inc_modaleripresaordine.php") ?>

	<?php include("inc_js.php") ?>
	<script src="../js/statoordini.js"></script>

</body>

</html>