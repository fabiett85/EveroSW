<?php
// in che pagina siamo
$pagina = 'gestioneordini';

require_once("../inc/conn.php");

function distintaRisorse(PDO $conn_mes, string $idProduzione, string | null $idLineaProduzione, string | null $idProdotto)
{

	$sth = $conn_mes->prepare(
		"SELECT * FROM risorse_coinvolte
		LEFT JOIN risorse ON ris_IdRisorsa = rc_IdRisorsa
		LEFT JOIN ricette_macchina ON ricm_Ricetta = rc_IdRicetta
		WHERE rc_IdProduzione = :idProduzione"
	);
	$sth->execute(['idProduzione' => $idProduzione]);
	$risorseCoinvolte = $sth->fetchAll();

	if (!$risorseCoinvolte) {
		if (empty($idLineaProduzione)) {
			return [];
		}

		$risorseCoinvolte = [];
		$sth = $conn_mes->prepare(
			"SELECT * FROM distinta_risorse_corpo AS DRC
			LEFT JOIN risorse AS R ON R.ris_IdRisorsa = DRC.drc_IdRisorsa
			LEFT JOIN ricette_macchina ON ricm_Ricetta = DRC.drc_IdRicetta
			WHERE DRC.drc_LineaProduzione = :idLineaProduzione
			AND DRC.drc_IdProdotto = :idProdotto"
		);
		$sth->execute([
			'idLineaProduzione' => $idLineaProduzione,
			'idProdotto' => $idProdotto,
		]);
		$drcs = $sth->fetchAll();

		if (!$drcs) {
			$sth = $conn_mes->prepare(
				"SELECT * FROM risorse
				WHERE ris_LineaProduzione = :idLineaProduzione"
			);
			$sth->execute([
				'idLineaProduzione' => $idLineaProduzione,
			]);
			$risorse = $sth->fetchAll();

			foreach ($risorse as $risorsa) {
				$risorseCoinvolte[] = [
					'rc_IdRisorsa' => $risorsa['ris_IdRisorsa'],
					'rc_IdProduzione' => $idProduzione,
					'rc_LineaProduzione' => $idLineaProduzione,
					'rc_NoteIniziali' => null,
					'rc_RegistraMisure' => $risorsa['ris_AbiMisure'],
					'rc_FlagUltima' => $risorsa['ris_FlagUltima'],
					'rc_FattoreConteggi' => $risorsa['ris_FattoreConteggi'],
					'rc_IdRicetta' => null,
					'rc_IdRicettaEseguita' => null,
					'rc_OrdineCaricato' => 0,
					'ris_Ordinamento' => $risorsa['ris_Ordinamento'],
					'ris_Descrizione' => $risorsa['ris_Descrizione'],
					'ricm_Descrizione' => null,
				];
			}
		} else {
			foreach ($drcs as $drc) {
				$risorseCoinvolte[] = [
					'rc_IdRisorsa' => $drc['ris_IdRisorsa'],
					'rc_IdProduzione' => $idProduzione,
					'rc_LineaProduzione' => $idLineaProduzione,
					'rc_NoteIniziali' => $drc['drc_NoteSetup'],
					'rc_RegistraMisure' => $drc['ris_AbiMisure'],
					'rc_FlagUltima' => $drc['drc_FlagUltima'],
					'rc_FattoreConteggi' => $drc['drc_FattoreConteggi'],
					'rc_IdRicetta' => $drc['drc_IdRicetta'],
					'rc_IdRicettaEseguita' => null,
					'rc_OrdineCaricato' => 0,
					'ris_Ordinamento' => $drc['ris_Ordinamento'],
					'ris_Descrizione' => $drc['ris_Descrizione'],
					'ricm_Descrizione' => $drc['ricm_Descrizione'],
				];
			}
		}
	}

	$output = [];
	$marked = '<div class="d-flex justify-content-center align-items-center" style="max-height:18px"><i class="mdi mdi-checkbox-marked mdi-18px"></i></div>';
	$unmarked = '<div class="d-flex justify-content-center align-items-center" style="max-height:18px"><span class="mdi mdi-checkbox-blank-outline mdi-18px"></span></div>';

	foreach ($risorseCoinvolte as $risorsaCoinvolta) {
		$output[] = [
			'rc_IdRisorsa' => $risorsaCoinvolta['rc_IdRisorsa'],
			'rc_IdProduzione' => $risorsaCoinvolta['rc_IdProduzione'],
			'rc_LineaProduzione' => $risorsaCoinvolta['rc_LineaProduzione'],
			'rc_NoteIniziali' => $risorsaCoinvolta['rc_NoteIniziali'],
			'rc_RegistraMisure' => $risorsaCoinvolta['rc_RegistraMisure'],
			'rc_FlagUltima' => $risorsaCoinvolta['rc_FlagUltima'],
			'rc_FattoreConteggi' => $risorsaCoinvolta['rc_FattoreConteggi'],
			'rc_IdRicetta' => $risorsaCoinvolta['rc_IdRicetta'],
			'rc_IdRicettaEseguita' => $risorsaCoinvolta['rc_IdRicettaEseguita'],
			'rc_OrdineCaricato' => $risorsaCoinvolta['rc_OrdineCaricato'],
			'ris_Ordinamento' => $risorsaCoinvolta['ris_Ordinamento'],
			'ris_Descrizione' => $risorsaCoinvolta['ris_Descrizione'],
			'ricm_Descrizione' => $risorsaCoinvolta['ricm_Descrizione'],
			'RegistraMisure' => ($risorsaCoinvolta['rc_RegistraMisure'] ? $marked : $unmarked),
			'FlagUltima' => ($risorsaCoinvolta['rc_FlagUltima'] ? $marked : $unmarked),
			'azioni' =>
			'<div class="dropdown">
				<button class="btn btn-primary dropdown-toggle btn-gestione-ordine" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Modifica">
				<span class="mdi mdi-lead-pencil mdi-18px"></span>
				</button>
				<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
					<a class="dropdown-item modifica-risorsa"><i class="mdi mdi-account-edit"></i> Modifica</a>
					<a class="dropdown-item cancella-risorsa"><i class="mdi mdi-trash-can"></i> Elimina</a>
				</div>
			</div>'
		];
	}
	return $output;
}

function distintaComponenti(PDO $conn_mes, string $idProduzione, string | null $idProdotto, float | null $qtaDaProdurre)
{

	$sth = $conn_mes->prepare(
		"SELECT * FROM componenti
		LEFT JOIN prodotti ON prd_IdProdotto = cmp_Componente
		LEFT JOIN unita_misura ON cmp_Udm = um_IdRiga
		WHERE cmp_IdProduzione = :idProduzione"
	);
	$sth->execute(['idProduzione' => $idProduzione]);
	$componenti = $sth->fetchAll();

	if (!$componenti) {

		$componenti = [];
		$sth = $conn_mes->prepare(
			"SELECT * FROM distinta_prodotti_corpo
			LEFT JOIN prodotti ON prd_IdProdotto = dpc_Componente
			LEFT JOIN unita_misura ON prd_UnitaMisura = um_IdRiga
			WHERE dpc_Prodotto = :idProdotto"
		);
		$sth->execute([
			'idProdotto' => $idProdotto,
		]);
		$dpcs = $sth->fetchAll();

		if (!$dpcs) {
			return [];
		} else {
			foreach ($dpcs as $dpc) {
				$componenti[] = [
					'cmp_IdProduzione' => $idProduzione,
					'cmp_Componente' => $dpc['dpc_Componente'],
					'cmp_Qta' => null,
					'cmp_Udm' => $dpc['dpc_Udm'],
					'cmp_FattoreMoltiplicativo' => $dpc['dpc_FattoreMoltiplicativo'],
					'cmp_PezziConfezione' => $dpc['dpc_PezziConfezione'],
					'prd_Descrizione' => $dpc['prd_Descrizione'],
					'prd_Disponibilita' => $dpc['prd_Disponibilita'],
					'um_Descrizione' => $dpc['um_Descrizione'],
					'um_Sigla' => $dpc['um_Sigla'],
				];
			}
		}
	}



	$output = [];
	foreach ($componenti as $componente) {
		$disponibili = $componente['prd_Disponibilita'];

		if (!empty($qtaDaProdurre)) {
			$fabbisogno = ceil(($qtaDaProdurre * $componente['cmp_FattoreMoltiplicativo']) / $componente['cmp_PezziConfezione']);
			$mancanti = $fabbisogno - $disponibili;
			$mancanti = $mancanti < 0 ? 0 : $mancanti;
		} else {
			$fabbisogno = '';
			$mancanti = '';
		}


		$output[] = [
			'cmp_IdProduzione' => $idProduzione,
			'cmp_Componente' => $componente['cmp_Componente'],
			'cmp_Qta' => $componente['cmp_Qta'],
			'cmp_Udm' => $componente['cmp_Udm'],
			'cmp_FattoreMoltiplicativo' => $componente['cmp_FattoreMoltiplicativo'],
			'cmp_PezziConfezione' => $componente['cmp_PezziConfezione'],
			'Disponibili' => $disponibili . ' ' . $componente['um_Sigla'],
			'Fabbisogno' => !empty($fabbisogno) ? $fabbisogno . ' ' . $componente['um_Sigla'] : '',
			'Mancanti' => !empty($fabbisogno) ? $mancanti . ' ' . $componente['um_Sigla'] : '',
			'prd_Descrizione' => $componente['prd_Descrizione'],
			'UdmComponente' => $componente['um_Descrizione'] . ' (' . $componente['um_Sigla'] . ')',
			'azioni' =>
			'<div class="dropdown">
				<button class="btn btn-primary dropdown-toggle btn-gestione-ordine" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Modifica riga">
				<span class="mdi mdi-lead-pencil mdi-18px"></span>
				</button>
				<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
					<a class="dropdown-item modifica-componente"><i class="mdi mdi-account-edit"></i> Modifica</a>
					<a class="dropdown-item cancella-componente"><i class="mdi mdi-trash-can"></i> Elimina</a>
				</div>
			</div>'
		];
	}
	return $output;
}

function distintaConsumi(PDO $conn_mes, string $idProduzione)
{

	$sth = $conn_mes->prepare(
		"SELECT * FROM consumi
		LEFT JOIN risorse ON ris_IdRisorsa = con_IdRisorsa
		LEFT JOIN tipo_consumo ON tc_IdRiga = con_IdTipoConsumo
		LEFT JOIN unita_misura ON tc_Udm = um_IdRiga
		WHERE con_IdProduzione = :idProduzione"
	);
	$sth->execute(['idProduzione' => $idProduzione]);
	$consumi = $sth->fetchAll();

	if (!$consumi) {

		$consumi = [];
		$sth = $conn_mes->prepare(
			"SELECT * FROM distinta_consumi
			LEFT JOIN risorse ON ris_IdRisorsa = dc_IdRisorsa
			LEFT JOIN tipo_consumo ON tc_IdRiga = dc_IdTipoConsumo
			LEFT JOIN unita_misura ON tc_Udm = um_IdRiga"
		);
		$sth->execute();
		$dcs = $sth->fetchAll();

		if (!$dcs) {
			return [];
		} else {
			foreach ($dcs as $dc) {
				$consumi[] = [
					'con_IdProduzione' => $idProduzione,
					'con_IdRisorsa' => $dc['dc_IdRisorsa'],
					'con_IdTipoConsumo' => $dc['dc_IdTipoConsumo'],
					'con_ConsumoTotale' => null,
					'con_ConsumoPezzoIpotetico' => $dc['dc_ValoreIpotetico'],
					'con_ConsumoPezzoRilevato' => null,
					'con_Rilevato' => $dc['dc_TipoCalcolo'],
					'ris_Descrizione' => $dc['ris_Descrizione'],
					'um_Sigla' => $dc['um_Sigla'],
					'tc_Descrizione' => $dc['tc_Descrizione'],
				];
			}
		}
	}

	$output = [];
	foreach ($consumi as $consumo) {
		$output[] = [
			'con_IdProduzione' => $idProduzione,
			'con_IdRisorsa' => $consumo['con_IdRisorsa'],
			'con_IdTipoConsumo' => $consumo['con_IdTipoConsumo'],
			'con_ConsumoTotale' => $consumo['con_ConsumoTotale'],
			'con_ConsumoPezzoIpotetico' => $consumo['con_ConsumoPezzoIpotetico'],
			'con_ConsumoPezzoRilevato' => $consumo['con_ConsumoPezzoRilevato'],
			'con_Rilevato' => $consumo['con_Rilevato'],
			'ris_Descrizione' => $consumo['ris_Descrizione'],
			'um_Sigla' => $consumo['um_Sigla'],
			'tc_Descrizione' => $consumo['tc_Descrizione'],
			'azioni' =>
			'<div class="dropdown">
				<button class="btn btn-primary dropdown-toggle btn-gestione-ordine" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Modifica riga">
				<span class="mdi mdi-lead-pencil mdi-18px"></span>
				</button>
				<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
					<a class="dropdown-item modifica-consumo"><i class="mdi mdi-account-edit"></i> Modifica</a>
					<a class="dropdown-item cancella-consumo"><i class="mdi mdi-trash-can"></i> Elimina</a>
				</div>
			</div>'
		];
	}
	return $output;
}

function clienti(PDO $conn_mes, string $idProduzione)
{

	$sth = $conn_mes->prepare(
		"SELECT * FROM clienti_ordine
		LEFT JOIN clienti ON co_IdCliente = cl_IdRiga
		WHERE co_IdProduzione = :idProduzione"
	);
	$sth->execute(['idProduzione' => $idProduzione]);
	$clienti = $sth->fetchAll();

	if (!$clienti) {

		$clienti = [];



		return $clienti;
	}

	$output = [];
	foreach ($clienti as $cliente) {
		$output[] = [
			'co_IdProduzione' => $idProduzione,
			'cl_Descrizione' => $cliente['cl_Descrizione'],
			'co_Qta' => $cliente['co_Qta'],
			'co_Note' => $cliente['co_Note'],
			'co_IdCliente' => $cliente['co_IdCliente'],
			'cl_IdRiga' => $cliente['cl_IdRiga'],
			'azioni' =>
			'<div class="dropdown">
				<button class="btn btn-primary dropdown-toggle btn-gestione-ordine" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Modifica riga">
				<span class="mdi mdi-lead-pencil mdi-18px"></span>
				</button>
				<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
					<a class="dropdown-item modifica-cliente"><i class="mdi mdi-account-edit"></i> Modifica</a>
					<a class="dropdown-item cancella-cliente"><i class="mdi mdi-trash-can"></i> Elimina</a>
				</div>
			</div>'
		];
	}
	return $output;
}


if (!empty($_REQUEST['azione'])) {

	if ($_REQUEST['azione'] == 'mostra') {

		$start = new DateTime();

		if ($allineamento) {
			require_once("../inc/importaDatiGeminiXP.php");
		}

		$filtri = explode(',', $_REQUEST['filtro']);
		$stringArray = '(';
		foreach ($filtri as $filtro) {
			$stringArray .= '?,';
		}
		$stringArray .= "-1)";
		$sth = $conn_mes->prepare(
			"SELECT * FROM ordini_produzione AS OP
			LEFT JOIN prodotti AS P ON OP.op_prodotto = P.prd_IdProdotto
			LEFT JOIN stati_ordine AS SO ON OP.op_Stato = SO.so_IdStatoOrdine
			LEFT JOIN unita_misura AS UM ON UM.um_IdRiga = OP.op_Udm
			LEFT JOIN linee_produzione AS LP ON OP.op_LineaProduzione = LP.lp_IdLinea
			WHERE op_Spedito = 0 AND OP.op_Stato IN " . $stringArray
		);
		$sth->execute($filtri);

		$ordini = $sth->fetchAll();
		$output = [];
		foreach ($ordini as $ordine) {
			$dataProduzione = new DateTime($ordine['op_DataProduzione'] . ' ' . $ordine['op_OraProduzione']);
			$dataFine = '';
			if (isset($ordine['op_DataFineTeorica'])) {
				$dataFine = new DateTime($ordine['op_DataFineTeorica'] . ' ' . $ordine['op_OraFineTeorica']);
				$dataFine = $dataFine->format('d/m/Y - H:i');
			}

			$stato = intval($ordine['op_Stato']);

			$azioni = '';
			switch ($stato) {
				case 3:
				case 4:
					$azioni = '<button class="btn btn-primary dropdown-toggle mdi mdi-pencil mdi-18px" title="Modifica" disabled></button>';
					break;
				case 5:
					$azioni =
						'<button type="button"
						class="btn btn-primary btn-lg py-1"
						id="riprendi-ordine-parziale"
						data-id-ordine-produzione="' . $ordine['op_IdProduzione'] . '"
						data-id_linea_produzione="' . $ordine['op_LineaProduzione'] . '"
						data-id-progressivo-parziale="' . $ordine['op_ProgressivoParziale'] . '"
						title="Riprendi ordine"
					>RIPRENDI</button>';
					$azioni = '';
					break;
				case 6:
					$azioni =
						'<div class="dropdown">
						<button type="button"
							class="btn btn-primary dropdown-toggle mdi mdi-lead-pencil mdi-18px"
							id="dropdownMenuButton"
							data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
							title="Modifica"
						></button>
						<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
							<a class="dropdown-item cancella-manutenzione"
								data-id-produzione="' . $ordine['op_IdProduzione'] . '"
							><i class="mdi mdi-trash-can"></i>ELIMINA</a>
						</div>
					</div>';
					break;
				default:
					$azioni =
						'<div class="dropdown">
						<button type="button"
							class="btn btn-primary dropdown-toggle mdi mdi-lead-pencil mdi-18px"
							id="dropdownMenuButton"
							data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
							title="Modifica"></button>
						<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
							<a class="dropdown-item gestisci-commessa"
								data-id-produzione="' . $ordine['op_IdProduzione'] . '"
							><i class="mdi mdi-cog"></i>GESTISCI</a>
							<a class="dropdown-item cancella-commessa"
								data-id-produzione="' . $ordine['op_IdProduzione'] . '"
							><i class="mdi mdi-trash-can"></i>ELIMINA</a>
						</div>
					</div>';
					break;
			}

			$comandi = '';
			switch ($stato) {
				case 2:
					$sth = $conn_mes->prepare(
						"SELECT COUNT(*) AS conto FROM risorse
						LEFT JOIN ordini_produzione ON op_LineaProduzione = ris_LineaProduzione
						WHERE op_IdProduzione = :idProduzione AND ris_IdProduzione != 'ND'"
					);
					$sth->execute(['idProduzione' => $ordine['op_IdProduzione']]);
					$conto = $sth->fetch()['conto'];

					if ($conto == 0) {
						$comandi = '<button class="btn btn-primary mdi mdi-download mdi-24px carica-ordine-produzione" type="button" title="Carica ordine"></button>';
					} else {
						$comandi = '<button class="btn btn-primary mdi mdi-download mdi-24px carica-ordine-produzione" type="button" title="Carica ordine" disabled></button>';
					}

					break;
				case 3:
					$comandi .= '<div class="d-flex justify-content-around">';
					$comandi .= '<button class="btn btn-primary mdi mdi-upload mdi-24px scarica-ordine-produzione mr-1" type="button" title="Scarica ordine"></button>';
					$comandi .= '<button class="btn btn-success mdi mdi-play-circle mdi-24px avvia-ordine-produzione" type="button" title="Avvia ordine"></button>';
					$comandi .= '</div>';
					break;
				case 4:
					$sth = $conn_mes->prepare(
						"SELECT COUNT(*) AS conto FROM risorsa_produzione_parziale
						WHERE rpp_IdProduzione = :idProduzione AND rpp_Fine IS NULL"
					);
					$sth->execute(['idProduzione' => $ordine['op_IdProduzione']]);
					$conto = $sth->fetch()['conto'];
					if ($conto == 0) {
						$comandi .= '<button class="btn btn-danger mdi mdi-stop-circle mdi-24px termina-ordine-produzione" type="button" title="Termina ordine"></button>';
					} else {
						$comandi .= '<button class="btn btn-danger mdi mdi-stop-circle mdi-24px termina-ordine-produzione" type="button" title="Termina ordine" disabled></button>';
					}
					break;
			}

			$output[] = [
				'op_IdProduzioneEsteso' => ($ordine['op_Riferimento'] != '' ? $ordine['op_IdProduzione'] . " (" . $ordine['op_Riferimento'] . ")" : $ordine['op_IdProduzione']),
				'op_IdProduzione' => $ordine['op_IdProduzione'],
				'lp_Descrizione' => $ordine['lp_Descrizione'],
				'prd_Descrizione' => $ordine['prd_Descrizione'],
				'op_QtaRichiesta' => $ordine['op_QtaRichiesta'] . " " . $ordine['um_Sigla'],
				'op_QtaDaProdurre' => $ordine['op_QtaDaProdurre'] . " " . $ordine['um_Sigla'],
				'DataOraProgrammazione' => $dataProduzione->format('d/m/Y - H:i'),
				'DataOraFinePrevista' => $dataFine,
				'op_DataConsegna' => (new DateTime($ordine['op_DataConsegna']))->format('d/m/Y'),
				'op_Lotto' => $ordine['op_Lotto'],
				'op_Priorita' => $ordine['op_Priorita'],
				'so_Descrizione' => $ordine['so_Descrizione'],
				'azioni' => $azioni,
				'comandi' => $comandi,
			];
		}

		$finish = new DateTime();

		$diff = $finish->diff($start)->f;
		die(json_encode([
			'diff' => $diff,
			'data' => $output,
		]));
	}

	if ($_REQUEST['azione'] == 'mostra-ordine') {
		unset($_REQUEST['azione']);

		$conn_mes->beginTransaction();
		try {
			$sth = $conn_mes->prepare(
				"SELECT * FROM ordini_produzione AS OP
				LEFT JOIN prodotti AS P ON OP.op_prodotto = P.prd_IdProdotto
				LEFT JOIN stati_ordine AS SO ON OP.op_Stato = SO.so_IdStatoOrdine
				LEFT JOIN unita_misura AS UM ON UM.um_IdRiga = OP.op_Udm
				LEFT JOIN velocita_teoriche AS VT ON VT.vel_IdLineaProduzione = OP.op_LineaProduzione
				WHERE OP.op_IdProduzione = :idProduzione"
			);
			$sth->execute($_REQUEST);

			$ordine = $sth->fetch();
			$ordine['vel_VelocitaTeoricaLinea'] = round($ordine['vel_VelocitaTeoricaLinea'], 2);
			$idLineaProduzione = !empty($ordine['op_LineaProduzione']) ? $ordine['op_LineaProduzione'] : null;
			$idProdotto = !empty($ordine['op_Prodotto']) ? $ordine['op_Prodotto'] : null;
			$qtaDaProdurre = floatval($ordine['op_QtaDaProdurre']);
			$output = [
				'ordine' => $ordine,
				'distintaRisorse' => distintaRisorse($conn_mes, $ordine['op_IdProduzione'], $idLineaProduzione, $idProdotto),
				'distintaComponenti' => distintaComponenti($conn_mes, $ordine['op_IdProduzione'], $idProdotto, $qtaDaProdurre),
				'distintaConsumi' => distintaConsumi($conn_mes, $ordine['op_IdProduzione']),
				'clienti' => clienti($conn_mes, $ordine['op_IdProduzione']),
			];

			$conn_mes->commit();

			die(json_encode($output));
		} catch (\Throwable $th) {
			$conn_mes->rollBack();
			die($th->getMessage());
		}
	}

	if ($_REQUEST['azione'] == 'elimina-produzione') {
		$conn_mes->beginTransaction();
		try {
			// elimino risorsa da tabella 'ordini_produzione'
			$sth = $conn_mes->prepare(
				"DELETE FROM ordini_produzione
				WHERE op_IdProduzione = :idProduzione"
			);
			$sth->execute(['idProduzione' => $_REQUEST['idProduzione']]);

			// elimino risorsa da tabella 'risorse_coinvolte'
			$sth = $conn_mes->prepare(
				"DELETE FROM risorse_coinvolte
				WHERE rc_IdProduzione = :idProduzione"
			);
			$sth->execute(['idProduzione' => $_REQUEST['idProduzione']]);

			// elimino risorsa da tabella 'componenti'
			$sth = $conn_mes->prepare(
				"DELETE FROM componenti
				WHERE cmp_IdProduzione = :idProduzione"
			);
			$sth->execute(['idProduzione' => $_REQUEST['idProduzione']]);

			// elimino risorsa da tabella 'consumi'
			$sth = $conn_mes->prepare(
				"DELETE FROM consumi
				WHERE con_IdProduzione = :idProduzione"
			);
			$sth->execute(['idProduzione' => $_REQUEST['idProduzione']]);

			$conn_mes->commit();
		} catch (\Throwable $th) {
			$conn_mes->rollBack();
			die($th->getMessage());
		}
		die('OK');
	}

	if ($_REQUEST['azione'] == 'udm-prodotto') {
		$sth = $conn_mes->prepare(
			"SELECT prd_UnitaMisura FROM prodotti
			WHERE prd_IdProdotto = :idProdotto"
		);
		$sth->execute(['idProdotto' => $_REQUEST['idProdotto']]);
		$udm = $sth->fetch()['prd_UnitaMisura'];
		die($udm);
	}

	if ($_REQUEST['azione'] == 'nuovo-ordine') {
		$parametri = [];
		parse_str($_REQUEST['data'], $parametri);
		$parametri['DataOraOrdine'] = new DateTime($parametri['DataOraOrdine']);
		$parametri['DataOraProduzione'] = new DateTime($parametri['DataOraProduzione']);

		$conn_mes->beginTransaction();
		try {
			// verifico che il codice cliente inserito, che mi funge da ID, non esista in altri record
			$sthSelect = $conn_mes->prepare(
				"SELECT * FROM ordini_produzione
				WHERE op_IdProduzione = :IdProduzione"
			);
			$sthSelect->execute(['IdProduzione' => $parametri['op_IdProduzione']]);
			$trovati = $sthSelect->fetch(PDO::FETCH_ASSOC);


			if (!$trovati) {

				$sthInsert = $conn_mes->prepare(
					"INSERT INTO ordini_produzione(op_IdProduzione,op_Riferimento,op_Prodotto,op_QtaRichiesta,op_QtaDaProdurre,op_Udm,op_DataOrdine,op_OraOrdine,op_DataProduzione,op_OraProduzione,op_Lotto,op_NoteProduzione)
					VALUES(:IdProduzione,:Riferimento,:IdProdotto,:QtaRichiesta,:QtaDaProdurre,:UnitaDiMisura,:DataOrdine,:OraOrdine,:DataProduzione,:OraProduzione,:Lotto,:NoteProduzione)"
				);
				$sthInsert->execute([
					'IdProduzione' => $parametri['op_IdProduzione'],
					'Riferimento' => $parametri['op_Riferimento'],
					'IdProdotto' => $parametri['op_Prodotto'],
					'QtaRichiesta' => $parametri['op_QtaRichiesta'],
					'QtaDaProdurre' => $parametri['op_QtaRichiesta'],
					'UnitaDiMisura' => $parametri['op_Udm'],
					'DataOrdine' => $parametri['DataOraOrdine']->format('Y-m-d'),
					'OraOrdine' => $parametri['DataOraOrdine']->format('H:i'),
					'DataProduzione' => $parametri['DataOraProduzione']->format('Y-m-d'),
					'OraProduzione' => $parametri['DataOraProduzione']->format('H:i'),
					'Lotto' => $parametri['op_Lotto'],
					'NoteProduzione' => $parametri['op_NoteProduzione']
				]);
			} else {
				$conn_mes->rollBack();
				die('KO');
			}

			$conn_mes->commit();
		} catch (\Throwable $th) {
			$conn_mes->rollBack();
			die($th->getMessage());
		}
		die('OK');
	}

	if ($_REQUEST['azione'] == 'dati-linea') {

		$sth = $conn_mes->prepare(
			"SELECT * FROM velocita_teoriche
			WHERE vel_IdLineaProduzione = :idLineaProduzione
			AND vel_IdProdotto = :idProdotto"
		);
		$sth->execute([
			'idLineaProduzione' => $_REQUEST['idLineaProduzione'],
			'idProdotto' => $_REQUEST['idProdotto'],
		]);
		$velocita = $sth->fetch();
		['vel_VelocitaTeoricaLinea'];
		$velocita = !empty($velocita['vel_VelocitaTeoricaLinea']) ? $velocita['vel_VelocitaTeoricaLinea'] : null;

		$output = [
			'velocita' => $velocita,
			'distintaRisorse' => distintaRisorse(
				$conn_mes,
				$_REQUEST['idProduzione'],
				$_REQUEST['idLineaProduzione'],
				$_REQUEST['idProdotto']
			)
		];

		die(json_encode($output));
	}

	if ($_REQUEST['azione'] == 'salva-ordine') {
		$conn_mes->beginTransaction();
		parse_str($_REQUEST['datiForm'], $parametri);
		$risorseCoinvolte = json_decode($_REQUEST['risorseCoinvolte'], true);
		$componenti = json_decode($_REQUEST['componenti'], true);
		$consumi = json_decode($_REQUEST['consumi'], true);
		$clienti = json_decode($_REQUEST['clienti'], true);

		$dataOraProgrammazione = new DateTime($parametri['op_DataProduzione']);
		$dataOraFineTeorica = new DateTime($parametri['op_DataFine']);
		try {

			$sthUpdateOrdineProduzione = $conn_mes->prepare(
				"UPDATE ordini_produzione SET
				op_DataProduzione = :DataProduzione,
				op_OraProduzione = :OraProduzione,
				op_QtaDaProdurre = :QtaDaProdurre,
				op_Lotto = :Lotto,
				op_NoteProduzione = :NoteProduzione,
				op_Priorita = :Priorita,
				op_Stato = :StatoOrdine,
				op_LineaProduzione = :IdLineaProduzione,
				op_DataFineTeorica = :DataFineTeorica,
				op_OraFineTeorica = :OraFineTeorica,
				op_Udm = :UnitaMisura
				WHERE op_IdProduzione = :IdOrdineProduzione"
			);
			$sthUpdateOrdineProduzione->execute([
				'DataProduzione' => $dataOraProgrammazione->format('Y-m-d'),
				'OraProduzione' => $dataOraProgrammazione->format('H:i'),
				'QtaDaProdurre' => $parametri['op_QtaDaProdurre'],
				'Lotto' => $parametri['op_Lotto'],
				'NoteProduzione' => $parametri['op_NoteProduzione'],
				'Priorita' => $parametri['op_Priorita'],
				'StatoOrdine' => $parametri['op_Stato'],
				'IdLineaProduzione' => $parametri['op_LineaProduzione'],
				'DataFineTeorica' => $dataOraFineTeorica->format('Y-m-d'),
				'OraFineTeorica' => $dataOraFineTeorica->format('H:i'),
				'UnitaMisura' => $parametri['op_Udm'],
				'IdOrdineProduzione' => $parametri['op_IdProduzione']
			]);

			$sth = $conn_mes->prepare(
				"DELETE FROM risorse_coinvolte
				WHERE rc_IdProduzione = :IdProduzione"
			);
			$sth->execute(['IdProduzione' => $parametri['op_IdProduzione']]);

			$sth = $conn_mes->prepare(
				"INSERT INTO risorse_coinvolte(rc_IdProduzione, rc_IdRisorsa, rc_LineaProduzione, rc_NoteIniziali,  rc_RegistraMisure, rc_FlagUltima, rc_IdRicetta, rc_FattoreConteggi)
				VALUES(:rc_IdProduzione, :rc_IdRisorsa, :rc_LineaProduzione, :rc_NoteIniziali,  :rc_RegistraMisure, :rc_FlagUltima, :rc_IdRicetta, :rc_FattoreConteggi)"
			);
			foreach ($risorseCoinvolte as $risorsaCoinvolta) {
				unset($risorsaCoinvolta['rc_OrdineCaricato']);
				unset($risorsaCoinvolta['rc_IdRicettaEseguita']);
				unset($risorsaCoinvolta['ris_Ordinamento']);
				unset($risorsaCoinvolta['ris_Ordinamento']);
				unset($risorsaCoinvolta['ris_Descrizione']);
				unset($risorsaCoinvolta['ricm_Descrizione']);
				unset($risorsaCoinvolta['RegistraMisure']);
				unset($risorsaCoinvolta['FlagUltima']);
				unset($risorsaCoinvolta['azioni']);
				$sth->execute($risorsaCoinvolta);
			}

			$sth = $conn_mes->prepare(
				"DELETE FROM componenti
				WHERE cmp_IdProduzione = :IdProduzione"
			);
			$sth->execute(['IdProduzione' => $parametri['op_IdProduzione']]);

			$sth = $conn_mes->prepare(
				"INSERT INTO componenti (cmp_IdProduzione, cmp_Componente, cmp_LineaProduzione, cmp_Qta, cmp_Udm, cmp_FattoreMoltiplicativo, cmp_PezziConfezione)
				VALUES(:cmp_IdProduzione, :cmp_Componente, :cmp_LineaProduzione, :cmp_Qta, :cmp_Udm, :cmp_FattoreMoltiplicativo, :cmp_PezziConfezione)"
			);
			foreach ($componenti as $componente) {
				unset($componente['Disponibili']);
				unset($componente['Fabbisogno']);
				unset($componente['Mancanti']);
				unset($componente['prd_Descrizione']);
				unset($componente['UdmComponente']);
				unset($componente['azioni']);
				$componente['cmp_Qta'] = $parametri['op_QtaDaProdurre'] * $componente['cmp_FattoreMoltiplicativo'] / $componente['cmp_PezziConfezione'];
				$componente['cmp_LineaProduzione'] = $parametri['op_LineaProduzione'];
				$sth->execute($componente);
			}

			$sth = $conn_mes->prepare(
				"DELETE FROM consumi
				WHERE con_IdProduzione = :IdProduzione"
			);
			$sth->execute(['IdProduzione' => $parametri['op_IdProduzione']]);

			$sth = $conn_mes->prepare(
				"INSERT INTO consumi(con_IdProduzione, con_IdRisorsa, con_IdTipoConsumo, con_ConsumoPezzoIpotetico, con_Rilevato)
				VALUES(:con_IdProduzione, :con_IdRisorsa, :con_IdTipoConsumo, :con_ConsumoPezzoIpotetico,  :con_Rilevato)"
			);
			foreach ($consumi as $consumo) {
				$sth->execute([
					'con_IdProduzione' => $consumo['con_IdProduzione'],
					'con_IdRisorsa' => $consumo['con_IdRisorsa'],
					'con_IdTipoConsumo' => $consumo['con_IdTipoConsumo'],
					'con_ConsumoPezzoIpotetico' => $consumo['con_ConsumoPezzoIpotetico'],
					'con_Rilevato' => $consumo['con_Rilevato'],
				]);
			}


			$sth = $conn_mes->prepare(
				"DELETE FROM clienti_ordine
				WHERE co_IdProduzione = :IdProduzione"
			);
			$sth->execute(['IdProduzione' => $parametri['op_IdProduzione']]);
			$sth = $conn_mes->prepare(
				"INSERT INTO clienti_ordine(co_IdProduzione, co_IdCliente, co_Qta, co_Note)
				VALUES(:co_IdProduzione, :co_IdCliente, :co_Qta, :co_Note)"
			);
			foreach ($clienti as $cliente) {
				$sth->execute([
					'co_IdProduzione' => $cliente['co_IdProduzione'],
					'co_IdCliente' => $cliente['co_IdCliente'],
					'co_Qta' => $cliente['co_Qta'],
					'co_Note' => $cliente['co_Note'],
				]);
			}

			// query di eliminazione da tabella 'velocita_teoriche' per l'ID prodotto e l'ID linea considerati
			$sth = $conn_mes->prepare(
				"DELETE FROM velocita_teoriche
				WHERE velocita_teoriche.vel_IdProdotto = :IdProdotto
				AND velocita_teoriche.vel_IdLineaProduzione = :IdLineaProduzione"
			);
			$sth->execute([
				'IdProdotto' => $parametri['op_Prodotto'],
				'IdLineaProduzione' => $parametri['op_LineaProduzione']
			]);


			// query di inserimento in tabella 'velocita_teoriche' per l'ID prodotto e l'ID linea considerati

			$sth = $conn_mes->prepare(
				"INSERT INTO velocita_teoriche(vel_IdProdotto,vel_IdLineaProduzione,vel_VelocitaTeoricaLinea)
				VALUES(:IdProdotto,:IdLineaProduzione,:VelocitaTeoricaLinea)"
			);
			$sth->execute([
				'IdProdotto' => $parametri['op_Prodotto'],
				'IdLineaProduzione' => $parametri['op_LineaProduzione'],
				'VelocitaTeoricaLinea' => (float) $parametri['vel_VelocitaTeoricaLinea']
			]);


			$conn_mes->commit();
			die('OK');
		} catch (\Throwable $th) {
			$conn_mes->rollBack();
			die($th->getMessage());
		}
	}

	if ($_REQUEST['azione'] == 'select-ricette') {
		unset($_REQUEST['azione']);
		$sth = $conn_mes->prepare(
			"SELECT * FROM ricette_macchina
			WHERE ricm_IdRisorsa = :idRisorsa"
		);
		$sth->execute($_REQUEST);
		$ricette = $sth->fetchAll();

		$html = '<option value="ND" default>Ricetta non definita</option>';

		foreach ($ricette as $ricetta) {
			$html .= '<option value="' . $ricetta['ricm_Ricetta'] . '">' . $ricetta['ricm_Descrizione'] . '</option>';
		}
		die(json_encode($html));
	}

	if ($_REQUEST['azione'] == 'macchine-disponibili') {


		$risorseImpegnate = json_decode($_REQUEST['risorseImpegnate'], true);
		$stringaArray = '(';
		$arrayQuery = [$_REQUEST['idLineaProduzione']];
		foreach ($risorseImpegnate as $risorsa) {
			$stringaArray .= '?,';
			$arrayQuery[] = $risorsa;
		}
		$stringaArray .= "'')";

		$sth = $conn_mes->prepare(
			"SELECT * FROM risorse
			WHERE ris_LineaProduzione = ?
			AND ris_IdRisorsa NOT IN " . $stringaArray
		);
		$sth->execute($arrayQuery);
		$risorse = $sth->fetchAll();


		die(json_encode($risorse));
	}

	if ($_REQUEST['azione'] == "componenti-disponibili") {
		unset($_REQUEST['azione']);

		$componentiUsati = json_decode($_REQUEST['componenti'], true);

		$strArray = '(';
		foreach ($componentiUsati as $componenteUsato) {
			$strArray .= "?, ";
		}
		$strArray .= "'')";

		$sth = $conn_mes->prepare(
			"SELECT * FROM prodotti
			WHERE prodotti.prd_Tipo = 'MP'
			AND prd_IdProdotto NOT IN " . $strArray
		);
		$sth->execute($componentiUsati);
		$righe = $sth->fetchAll(PDO::FETCH_ASSOC);
		die(json_encode($righe));
	}

	if ($_REQUEST['azione'] == 'tipi-consumo') {

		$sth = $conn_mes->prepare(
			"SELECT * FROM tipo_consumo"
		);
		$sth->execute();
		$tipi = $sth->fetchAll();


		die(json_encode($tipi));
	}

	if ($_REQUEST['azione'] == 'dati-consumo') {
		unset($_REQUEST['azione']);



		$sth = $conn_mes->prepare(
			"SELECT * FROM tipo_consumo
			LEFT JOIN unita_misura ON tc_Udm = um_IdRiga
			WHERE tc_IdRiga = :tipoConsumo"
		);
		$sth->execute($_REQUEST);
		$tipo = $sth->fetch();


		die(json_encode($tipo));
	}

	if ($_REQUEST['azione'] == 'ordine-risorsa') {
		unset($_REQUEST['azione']);



		$sth = $conn_mes->prepare(
			"SELECT * FROM risorse
			WHERE ris_IdRisorsa = :idRisorsa"
		);
		$sth->execute($_REQUEST);
		$risorsa = $sth->fetch();


		die($risorsa['ris_Ordinamento']);
	}

	if ($_REQUEST['azione'] == 'clienti') {

		$sth = $conn_mes->prepare(
			"SELECT * FROM clienti"
		);
		$sth->execute();
		$clienti = $sth->fetchAll();


		die(json_encode($clienti));
	}

	if ($_REQUEST['azione'] == 'disponibilita-componente') {
		unset($_REQUEST['azione']);
		$sth = $conn_mes->prepare(
			"SELECT prd_Disponibilita FROM prodotti
			WHERE prd_IdProdotto = :idProdotto"
		);
		$sth->execute($_REQUEST);
		$dispo = $sth->fetch()['prd_Disponibilita'];


		die($dispo);
	}

	require_once('../../prospect40_HMI/php/inc_funzioni.php');

	if ($_REQUEST['azione'] == 'scarica-ordine') {
		// Apro la transazione MySQL
		$conn_mes->beginTransaction();

		try {

			$sth = $conn_mes->prepare(
				"SELECT ris_IdRisorsa FROM risorse
				WHERE ris_IdProduzione = :idProduzione"
			);
			$sth->execute(['idProduzione' => $_REQUEST['idProduzione']]);
			$risorse = $sth->fetchAll(PDO::FETCH_COLUMN, 0);

			foreach ($risorse as $risorsa) {
				scaricamentoOrdineSingolo($conn_mes, $risorsa, $_REQUEST['idProduzione']);
			}

			// Eseguo commit della transazione
			$conn_mes->commit();
			die('OK');
		} catch (Throwable $t) {
			// Eseguo rollback della transazione
			$conn_mes->rollBack();
			die('ERRORE');
		}
	}

	if ($_REQUEST['azione'] == 'carica-ordine') {
		// Apro la transazione MySQL
		$conn_mes->beginTransaction();

		try {

			$sth = $conn_mes->prepare(
				"SELECT ris_IdRisorsa FROM risorse
				LEFT JOIN ordini_produzione ON op_LineaProduzione = ris_LineaProduzione
				WHERE op_IdProduzione = :idProduzione"
			);
			$sth->execute(['idProduzione' => $_REQUEST['idProduzione']]);
			$risorse = $sth->fetchAll(PDO::FETCH_COLUMN, 0);

			foreach ($risorse as $risorsa) {
				caricamentoOrdineSingolo($conn_mes, $risorsa, $_REQUEST['idProduzione']);
			}

			// Eseguo commit della transazione
			$conn_mes->commit();
			die('OK');
		} catch (Throwable $t) {
			// Eseguo rollback della transazione
			$conn_mes->rollBack();
			die('ERRORE');
		}
	}

	if ($_REQUEST['azione'] == 'termina-ordine') {
		$now = new DateTime();
		// Apro la transazione MySQL
		$conn_mes->beginTransaction();

		try {

			$sth = $conn_mes->prepare(
				"SELECT rpp_IdRisorsa FROM risorsa_produzione_parziale
				WHERE rpp_IdProduzione = :idProduzione AND rpp_Fine IS NULL"
			);
			$sth->execute(['idProduzione' => $_REQUEST['idProduzione']]);
			$risorse = $sth->fetchAll(PDO::FETCH_COLUMN, 0);

			foreach ($risorse as $risorsa) {
				terminaLavoroRisorsa($conn_mes, $risorsa, $_REQUEST['idProduzione'], $now);
			}

			terminaCommessa($conn_mes, $_REQUEST['idProduzione'], $now);

			// Eseguo commit della transazione
			$conn_mes->commit();

			//require_once('../../prospect40/inc/connERP.php');

			//spedisciDati($conn_mes, $conn_erp);

			die('OK');
		} catch (Throwable $t) {
			// Eseguo rollback della transazione
			$conn_mes->rollBack();
			die('ERRORE '.$t);
		}
	}

	if ($_REQUEST['azione'] == 'avvia-ordine') {
		$now = new DateTime();
		// Apro la transazione MySQL
		$conn_mes->beginTransaction();

		try {

			$sth = $conn_mes->prepare(
				"SELECT ris_IdRisorsa, op_QtaDaProdurre FROM risorse
				LEFT JOIN ordini_produzione ON op_IdProduzione = ris_IdProduzione
				WHERE ris_IdProduzione = :idProduzione"
			);
			$sth->execute(['idProduzione' => $_REQUEST['idProduzione']]);
			$risorse = $sth->fetchAll();

			foreach ($risorse as $risorsa) {
				inizioParzialeRisorsa($conn_mes, $risorsa['ris_IdRisorsa'], $_REQUEST['idProduzione'], $now, $risorsa['op_QtaDaProdurre']);
			}


			// Eseguo commit della transazione
			$conn_mes->commit();
			die('OK');
		} catch (Throwable $t) {
			// Eseguo rollback della transazione
			$conn_mes->rollBack();
			die('ERRORE');
		}
	}

	if ($_REQUEST['azione'] == 'spedisci-dati') {
		require_once('../../prospect40/inc/connERP.php');
		require_once('../../prospect40_HMI/php/inc_funzioni.php');
		spedisciDati($conn_mes, $conn_erp);
		die('OK');
	}
}

//require_once('../inc/importaDatiERP_V2.php');

?>
<!DOCTYPE html>
<html lang="it">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>PROSPECT40 | Gestione commesse</title>
	<?php include("inc_css.php") ?>


</head>

<body>

	<div class="container-scroller">

		<?php include("inc_testata.php") ?>

		<div class="container-fluid page-body-wrapper">



			<div class="main-panel">

				<div class="content-wrapper">

					<div class="card" id="blocco-elenco">
						<button type="button" id="spedisci-ordini" class="mdi mdi-button bottone-basso-sinistra">SPEDISCI
							ORDINI</button>

						<div class="card-header">
							<h4 class="card-title my-2 mx-2">COMMESSE DA GESTIRE</h4>
						</div>

						<div id="collapseOne" class="collapse multi-collapse show" aria-labelledby="headingOne">

							<div class="card-body">

								<div class="row">

									<!-- Gantt commesse pianificate -->
									<div class="col-12 pt2">
										<div id="timeline_ordini"></div>
									</div>

								</div>
								<div class="row mt-3">
									<div class="col-10">
									</div>
									<div class="col-2">
										<!-- Filtro stato ordini -->
										<div class="form-group">
											<select class="form-control form-control-sm selectpicker" id="filtro-ordini" name="filtro-ordini">
												<?php
												$sth = $conn_mes->prepare(
													"SELECT * FROM stati_ordine
													WHERE stati_ordine.so_IdStatoOrdine IN (1,2,3,4,5)"
												);
												$sth->execute();
												$prodotti = $sth->fetchAll(PDO::FETCH_ASSOC);

												echo "<option value='1,2,3,4,5'>Mostra TUTTE</option>";
												echo "<option value='1,2'>Mostra MEMO e ATTIVI</option>";
												foreach ($prodotti as $prodotto) {
													echo "<option value='" . $prodotto['so_IdStatoOrdine'] . "'>Mostra " . strtoupper($prodotto['so_TestoSelect']) . "</option>";
												}
												?>
											</select>
										</div>
									</div>

								</div>



								<div class="row">

									<div class="col-12">

										<div class="table-responsive">

											<table id="tabellaOrdini" class="table table-striped" style="width:100%">
												<thead>
													<tr>
														<th>Codice commessa (Rif.)</th>
														<th>Linea </th>
														<th>Prodotto </th>
														<th>Qta ric.</th>
														<th>Qta da prod.</th>
														<th>Data programmazione</th>
														<th>Data fine prevista</th>
														<th>Data consegna</th>
														<th>Lotto</th>
														<th>Priorità</th>
														<th>Stato</th>
														<th>Comandi</th>
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


					<div class="card mt-2" id="blocco-modifica">

						<div class="card-header">
							<h4 class="card-title my-2 mx-2">DETTAGLIO COMMESSA</h4>
						</div>

						<div id="collapseTwo" class="collapse multi-collapse" aria-labelledby="headingOne">

							<div class="card-body">

								<form class="forms-sample" id="form-dati-ordine">
									<!-- Visualizzazione distinte prodotto presenti e dati di quella selezionata -->
									<div class="row">

										<div class="col-lg-3">
											<div class="form-group">
												<label for="op_IdProduzione">Codice commessa</label>
												<input readonly type="text" class="form-control form-control-sm dati-ordine"
													id="op_IdProduzione" name="op_IdProduzione" aria-label=""
													aria-describedby="inputGroup-sizing-lg">
											</div>
										</div>

										<div class="col-lg-2">
											<div class="form-group">
												<label for="op_Prodotto">Codice prodotto</label>
												<input readonly type="text" class="form-control form-control-sm dati-ordine" id="op_Prodotto"
													name="op_Prodotto" aria-label="" aria-describedby="inputGroup-sizing-lg">
											</div>
										</div>

										<div class="col-lg-3">
											<div class="form-group">
												<label for="prd_Descrizione">Prodotto</label>
												<input readonly type="text" class="form-control form-control-sm dati-ordine"
													id="prd_Descrizione" name="prd_Descrizione" aria-label=""
													aria-describedby="inputGroup-sizing-lg">
											</div>
										</div>

										<div class="col-lg-2">
											<div class="form-group">
												<label for="op_QtaRichiesta">Qta ric.<span class="ml-1 udm">[Pz]</span></label>
												<input readonly type="text" class="form-control form-control-sm dati-ordine"
													id="op_QtaRichiesta" name="op_QtaRichiesta" aria-label=""
													aria-describedby="inputGroup-sizing-lg">
											</div>
										</div>

										<div class="col-lg-2">
											<div class="form-group">
												<label for="op_DataOrdine">Compilato il</label>
												<input readonly type="datetime-local" class="form-control form-control-sm dati-ordine"
													id="op_DataOrdine" name="op_DataOrdine" aria-label="" aria-describedby="inputGroup-sizing-lg">
											</div>
										</div>

										<div class="col-lg-6">
											<div class="form-group">
												<label for="op_NoteProduzione">Note</label>
												<input type="text" class="form-control form-control-sm dati-ordine" id="op_NoteProduzione"
													name="op_NoteProduzione" aria-label="" aria-describedby="inputGroup-sizing-lg">
											</div>
										</div>

										<div class="col-2">
											<div class="form-group">
												<label for="op_Udm">Unità di misura</label><span style='color:red'> *</span>
												<select class="form-control form-control-sm dati-popup-modifica selectpicker" name="op_Udm"
													id="op_Udm" data-live-search="true" required>
													<?php
													$sth = $conn_mes->prepare(
														"SELECT * FROM unita_misura"
													);
													$sth->execute();
													$trovate = $sth->fetchAll(PDO::FETCH_ASSOC);
													foreach ($trovate as $udm) {
														echo "<option value='" . $udm['um_IdRiga'] . "'>" . $udm['um_Sigla'] . "</option>";
													}
													?>
												</select>
											</div>
										</div>

										<div class="col-lg-2">
											<div class="form-group">
												<label for="op_QtaDaProdurre">Qta da prod.<span class="ml-1 udm">[Pz]</span></label><span
													style='color:red'> *</span>
												<input type="number" class="form-control form-control-sm dati-popup-modifica obbligatorio"
													id="op_QtaDaProdurre" name="op_QtaDaProdurre" aria-label=""
													aria-describedby="inputGroup-sizing-lg" required>
											</div>
										</div>

										<div class="col-lg-2">
											<div class="form-group">
												<label for="op_DataProduzione">Programmato per</label><span style='color:red'> *</span>
												<input type="datetime-local"
													class="form-control form-control-sm dati-popup-modifica obbligatorio" id="op_DataProduzione"
													name="op_DataProduzione" aria-label="" aria-describedby="inputGroup-sizing-lg" value=""
													required>
											</div>
										</div>

										<div class="col-lg-2">
											<div class="form-group">
												<label class="label-evidenziata" for="op_Stato">Stato</label><span style='color:red'> *</span>
												<select class="form-control form-control-sm selectpicker test-disabilitato" id="op_Stato"
													name="op_Stato" required>
													<?php
													$sth = $conn_mes->prepare(
														"SELECT stati_ordine.* FROM stati_ordine
														WHERE stati_ordine.so_IdStatoOrdine < 3"
													);
													$sth->execute();
													$linee = $sth->fetchAll(PDO::FETCH_ASSOC);

													foreach ($linee as $linea) {
														echo "<option value=" . $linea['so_IdStatoOrdine'] . ">" . $linea['so_Descrizione'] . "</option>";
													}
													?>
												</select>
											</div>
										</div>

										<div class="col-lg-3">
											<div class="form-group">
												<label class="label-evidenziata" for="op_LineaProduzione">Linee disponibili</label><span
													style='color:red'> *</span>
												<select class="form-control form-control-sm selectpicker dati-popup-modifica"
													id="op_LineaProduzione" name="op_LineaProduzione" required>
													<?php
													$sth = $conn_mes->prepare(
														"SELECT linee_produzione.* FROM linee_produzione
														WHERE linee_produzione.lp_IdLinea != 'lin_0P'
														AND linee_produzione.lp_IdLinea != 'lin_0X'"
													);
													$sth->execute();
													$linee = $sth->fetchAll(PDO::FETCH_ASSOC);

													if ($linee) {
														foreach ($linee as $linea) {
															echo "<option value=" . $linea['lp_IdLinea'] . ">" . $linea['lp_Descrizione'] . "</option>";
														}
													} else {
														echo "<option value='' >Nessuna linea disponibile</option>";
													}
													?>
												</select>
											</div>
										</div>

										<div class="col-lg-2">
											<div class="form-group">
												<label class="label-evidenziata" for="vel_VelocitaTeoricaLinea">Vel. t. linea .<span
														class="ml-1 udm-vel">[Pz]</span></label><span style='color:red'> *</span>
												<input type="number" class="form-control form-control-sm dati-ordine obbligatorio"
													id="vel_VelocitaTeoricaLinea" name="vel_VelocitaTeoricaLinea" aria-label=""
													aria-describedby="inputGroup-sizing-lg" required step="0.01">
											</div>
										</div>

										<div class="col-lg-1">
											<div class="form-group">
												<label class="label-evidenziata" for="op_Priorita">Priorità</label><span style='color:red'>
													*</span>
												<input type="number" class="form-control form-control-sm dati-popup-modifica obbligatorio"
													id="op_Priorita" name="op_Priorita" aria-label="" aria-describedby="inputGroup-sizing-lg"
													required>
											</div>
										</div>
										<div class="col-lg-2">
											<div class="form-group">
												<label class="label-evidenziata" for="op_Lotto">Lotto</label><span style='color:red'> *</span>
												<input type="text" class="form-control form-control-sm dati-ordine obbligatorio" id="op_Lotto"
													name="op_Lotto" aria-label="" aria-describedby="inputGroup-sizing-lg" required>
											</div>
										</div>

										<div class="col-lg-2">
											<div class="form-group">
												<label for="op_DataFine">Termine previsto</label><span style='color:red'> *</span>
												<input type="datetime-local"
													class="form-control form-control-sm dati-popup-modifica obbligatorio" id="op_DataFine"
													name="op_DataFine" aria-label="" aria-describedby="inputGroup-sizing-lg" value="" required>
											</div>
										</div>

									</div>
								</form>

								<ul class="nav nav-tabs mt-4" id="tab-distinte" role="tablist">
									<li class="nav-item text-center" style="width: 20%;">
										<a aria-controls="risorse" aria-selected="true" class="nav-link active show" data-toggle="tab"
											href="#risorse" id="tab-risorse" role="tab"><b>DISTINTA MACCHINE</b></a>
									</li>
									<li class="nav-item text-center" style="width: 20%">
										<a aria-controls="componenti" aria-selected="true" class="nav-link" data-toggle="tab"
											href="#componenti" id="tab-componenti" role="tab"><b>DISTINTA COMPONENTI</b></a>
									</li>
									<li class="nav-item text-center" style="width: 20%">
										<a aria-controls="consumi" aria-selected="true" class="nav-link" data-toggle="tab" href="#consumi"
											id="tab-consumi" role="tab"><b>DISTINTA CONSUMI</b></a>
									</li>
									<li class="nav-item text-center" style="width: 20%">
										<a aria-controls="clienti" aria-selected="true" class="nav-link" data-toggle="tab" href="#clienti"
											id="tab-clienti" role="tab"><b>CLIENTI</b></a>
									</li>
								</ul>

								<div class="tab-content">

									<div aria-labelledby="tab-risorse" class="tab-pane fade show active" id="risorse" role="tabpanel">

										<div class="table-responsive">

											<table id="tabellaDistintaRisorse" class="table table-striped" style="width:100%"
												data-source="gestionecommesse.php?azione=mostra-dettagli-distinte">
												<thead>
													<tr>
														<th>Id macchina</th>
														<th>Descrizione</th>
														<th>Ricetta</th>
														<th>Note iniziali</th>
														<th>Reg. misure</th>
														<th>Ultima</th>
														<th>Ordinamento</th>
														<th>Fattore conteggi</th>
														<th></th>
													</tr>
												</thead>
												<tbody></tbody>

											</table>

										</div>
									</div>

									<div aria-labelledby="tab-componenti" class="tab-pane fade" id="componenti" role="tabpanel">

										<div class="table-responsive">

											<table id="tabellaDistintaComponenti" class="table table-striped" style="width:100%"
												data-source="gestionecommesse.php?azione=mostra-dettagli-componenti">
												<thead>
													<tr>
														<th>Id componente</th>
														<th>Descrizione</th>
														<th>Udm</th>
														<th>Coeff. moltipl.</th>
														<th>Pz confezione</th>
														<th>Disponibili</th>
														<th>Fabbisogno</th>
														<th>Mancanti</th>
														<th></th>
													</tr>
												</thead>
												<tbody></tbody>

											</table>

										</div>

									</div>

									<div aria-labelledby="tab-consumi" class="tab-pane fade" id="consumi" role="tabpanel">

										<div class="table-responsive">

											<table id="tabellaDistintaConsumi" class="table table-striped" style="width:100%"
												data-source="gestionecommesse.php?azione=mostra-dettagli-componenti">
												<thead>
													<tr>
														<th>Macchina</th>
														<th>Tipo consumo</th>
														<th>Udm</th>
														<th>Tipo calcolo</th>
														<th>Consumo Ipotetico Per Pezzo</th>
														<th></th>
													</tr>
												</thead>
												<tbody></tbody>

											</table>

										</div>

									</div>

									<div aria-labelledby="tab-clienti" class="tab-pane fade" id="clienti" role="tabpanel">

										<div class="table-responsive">

											<table id="tabellaClienti" class="table table-striped" style="width:100%">
												<thead>
													<tr>
														<th>Descrizione</th>
														<th>Quantità</th>
														<th>Note</th>
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

				<?php include("inc_footer.php") ?>

			</div>

		</div>

	</div>

	<button type="button" id="nuova-commessa" class="mdi mdi-button">NUOVA COMMESSA</button>

	<!-- Pulsanti -->
	<button type="button" id="aggiungi-componente-ordine" class="mdi mdi-button btn-gestione-ordine" hidden>AGGIUNGI
		ELEMENTO</button>
	<button type="button" id="aggiungi-risorsa-ordine" class="mdi mdi-button btn-gestione-ordine" hidden>AGGIUNGI
		MACCHINA</button>
	<button type="button" id="annulla-modifica-ordine" class="mdi mdi-button" hidden>ANNULLA</button>
	<button type="button" id="conferma-modifica-ordine" class="mdi mdi-button btn-gestione-ordine"
		hidden>CONFERMA</button>
	<button type="button" id="aggiungi-consumo-ordine" class="mdi mdi-button btn-gestione-ordine" hidden>AGGIUNGI
		CONSUMO</button>
	<button type="button" id="aggiungi-cliente" class="mdi mdi-button btn-gestione-ordine bottone-basso-sinistra"
		hidden>AGGIUNGI CLIENTE</button>

	<!-- Popup modale di INSERIMENTO COMMESSA -->
	<div class="modal fade" id="modal-ordine-produzione" tabindex="-1" role="dialog"
		aria-labelledby="modal-ordine-produzione-label" aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modal-ordine-produzione-label">Nuova produzione</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form class="forms-sample" id="form-nuovo-ordine">

						<div class="row">
							<div class="col-7">
								<div class="form-group">
									<label for="op_IdProduzione">Codice commessa</label><span style='color:red'> *</span>
									<input type="text" class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="op_IdProduzione" id="op_IdProduzione" autocomplete="off" required>
								</div>
							</div>
							<div class="col-5">
								<div class="form-group">
									<label for="op_Riferimento">Riferimento commessa</label>
									<input type="text" class="form-control form-control-sm dati-popup-modifica" name="op_Riferimento"
										id="op_Riferimento" autocomplete="off">
								</div>
							</div>

							<div class="col-12">
								<div class="form-group">
									<label for="op_Prodotto">Prodotto</label><span style='color:red'> *</span>
									<select class="form-control form-control-sm dati-popup-modifica selectpicker" id="op_Prodotto"
										name="op_Prodotto" data-live-search="true" required>
										<?php
										$sth = $conn_mes->prepare(
											"SELECT prodotti.* FROM prodotti
											WHERE prodotti.prd_Tipo != 'MP'
											ORDER BY prodotti.prd_Descrizione ASC"
										);
										$sth->execute();
										$linee = $sth->fetchAll(PDO::FETCH_ASSOC);

										foreach ($linee as $linea) {
											echo "<option value='" . $linea['prd_IdProdotto'] . "'>" . $linea['prd_Descrizione'] . "</option>";
										}
										?>
									</select>
								</div>
							</div>
							<div class="col-12">
								<div class="form-group">
									<label for="op_Lotto">Lotto</label>
									<input type="text" class="form-control form-control-sm dati-popup-modifica" name="op_Lotto"
										id="op_Lotto" autocomplete="off">
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="op_QtaRichiesta">Qta richiesta</label><span style='color:red'> *</span>
									<input type="number" class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="op_QtaRichiesta" id="op_QtaRichiesta" autocomplete="off" required>
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="op_Udm">Unità di misura</label><span style='color:red'> *</span>
									<select class="form-control form-control-sm dati-popup-modifica selectpicker" name="op_Udm"
										id="op_Udm" data-live-search="true" required>
										<?php
										$sth = $conn_mes->prepare(
											"SELECT * FROM unita_misura"
										);
										$sth->execute();
										$trovate = $sth->fetchAll(PDO::FETCH_ASSOC);
										foreach ($trovate as $udm) {
											echo "<option value=" . $udm['um_IdRiga'] . ">" . $udm['um_Sigla'] . "</option>";
										}
										?>
									</select>
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="DataOraOrdine">Data compilazione</label><span style='color:red'> *</span>
									<input type="datetime-local" class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="DataOraOrdine" id="DataOraOrdine" required>
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="DataOraProduzione">Data pianificazione</label><span style='color:red'> *</span>
									<input type="datetime-local" class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="DataOraProduzione" id="DataOraProduzione" required>
								</div>
							</div>
							<div class="col-12">
								<div class="form-group">
									<label for="op_NoteProduzione">Note produzione</label>
									<input type="text" class="form-control form-control-sm dati-popup-modifica" name="op_NoteProduzione"
										id="op_NoteProduzione" autocomplete="off">
								</div>
							</div>
						</div>
					</form>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-success" id="salva-nuovo-ordine">Salva</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
				</div>

			</div>
		</div>
	</div>

	<!-- Popup modale di AGGIUNTA RISORSA -->
	<div class="modal fade" id="modal-nuova-risorsa" tabindex="-1" role="dialog"
		aria-labelledby="modal-nuova-risorsa-label" aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modal-nuova-risorsa-label">AGGIUNTA MACCHINA</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form class="forms-sample" id="form-nuova-risorsa">

						<div class="row">
							<div class="col-12">
								<div class="form-group">
									<label for="rc_IdProduzione">Codice commessa</label>
									<input type="text" class="form-control form-control-sm dati-popup-modifica" name="rc_IdProduzione"
										id="rc_IdProduzione" readonly>
								</div>
							</div>
							<div class="col-12">
								<div class="form-group">
									<label for="rc_NomeLineaProduzione">Linea di produzione selezionata</label>
									<input type="text" class="form-control form-control-sm dati-popup-modifica"
										name="rc_NomeLineaProduzione" id="rc_NomeLineaProduzione" readonly>
								</div>
							</div>
							<div class="col-12">
								<div class="form-group">
									<label for="rc_IdRisorsa">Macchina</label><span style='color:red'> *</span>
									<select class="form-control form-control-sm dati-popup-modifica selectpicker" id="rc_IdRisorsa"
										name="rc_IdRisorsa" required>

									</select>
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="rc_IdRicetta">Ricetta</label><span style='color:red'> *</span>
									<select class="form-control form-control-sm dati-popup-modifica selectpicker" id="rc_IdRicetta"
										name="rc_IdRicetta" required>

									</select>
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="rc_FattoreConteggi">Fattore conteggi</label>
									<input type="text" class="form-control form-control-sm dati-popup-modifica" name="rc_FattoreConteggi"
										id="rc_FattoreConteggi" autocomplete="off">
								</div>
							</div>
							<div class="col-12">
								<div class="form-group">
									<label for="rc_NoteIniziali">Note di setup</label>
									<input type="text" class="form-control form-control-sm dati-popup-modifica" name="rc_NoteIniziali"
										id="rc_NoteIniziali" autocomplete="off">
								</div>
							</div>
							<div class="col-6">
								<div class="form-check">
									<input class="form-check-input" id="rc_RegistraMisure" type="checkbox">
									<label for="rc_RegistraMisure">Abilita registrazione misure</label>
								</div>
							</div>
							<div class="col-6">
								<div class="form-check">
									<input class="form-check-input" id="rc_FlagUltima" type="checkbox">
									<label for="rc_FlagUltima">Ultima risorsa linea</label>
								</div>
							</div>
						</div>

						<input type="hidden" id="rc_IdLineaProduzione" name="rc_IdLineaProduzione" value="">
						<input type="hidden" id="rc_Azione" name="rc_Azione" value="nuovo">


					</form>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-success" id="salva-risorsa">Salva</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
				</div>

			</div>
		</div>
	</div>

	<!-- Popup modale di AGGIUNTA COMPONENTE -->
	<div class="modal fade" id="modal-nuovo-componente" tabindex="-1" role="dialog"
		aria-labelledby="modalNuovoComponenteLabel" aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modal-nuovo-componente-label">AGGIUNTA COMPONENTE</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form class="forms-sample" id="form-nuovo-componente">
						<div class="row">
							<div class="col-12">

								<div class="form-group">
									<label for="cmp_IdProduzione">Ordine di produzione</label>
									<input type="text" class="form-control form-control-sm dati-popup-modifica" name="cmp_IdProduzione"
										id="cmp_IdProduzione" readonly>
								</div>
							</div>
							<div class="col-12">
								<div class="form-group">
									<label for="cmp_Componente">Componente</label><span style='color:red'> *</span>
									<select class="form-control form-control-sm dati-popup-modifica selectpicker" id="cmp_Componente"
										name="cmp_Componente" data-live-search="true" required>

									</select>
								</div>
							</div>

							<div class="col-4">
								<div class="form-group">
									<label for="cmp_Udm">Unità di misura</label><span style='color:red'> *</span>
									<select class="form-control form-control-sm dati-popup-modifica selectpicker" name="cmp_Udm"
										id="cmp_Udm" required>
										<?php
										$sth = $conn_mes->prepare(
											"SELECT * FROM unita_misura"
										);
										$sth->execute();
										$trovate = $sth->fetchAll(PDO::FETCH_ASSOC);
										foreach ($trovate as $udm) {
											echo "<option value='" . $udm['um_IdRiga'] . "'>" . $udm['um_Descrizione'] . ' (' .  $udm['um_Sigla'] .  ')' . "</option>";
										}
										?>
									</select>
								</div>
							</div>

							<div class="col-4">
								<div class="form-group">
									<label for="cmp_FattoreMoltiplicativo">Coeff. moltipl.</label>
									<input type="number" class="form-control form-control-sm dati-popup-modifica"
										name="cmp_FattoreMoltiplicativo" id="cmp_FattoreMoltiplicativo" autocomplete="off">
								</div>
							</div>

							<div class="col-4">
								<div class="form-group">
									<label for="cmp_PezziConfezione">Pz confezione</label>
									<input type="number" class="form-control form-control-sm dati-popup-modifica"
										name="cmp_PezziConfezione" id="cmp_PezziConfezione" autocomplete="off">
								</div>
							</div>

							<input type="hidden" id="cmp_IdLineaProduzione" name="cmp_IdLineaProduzione" value="">
							<input type="hidden" id="cmp_Azione" name="cmp_Azione" value="nuovo">
						</div>
					</form>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-success" id="salva-componente">Salva</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
				</div>

			</div>
		</div>
	</div>

	<!-- Popup modale di AGGIUNTA CONSUMO -->
	<div class="modal fade" id="modal-nuovo-consumo" tabindex="-1" role="dialog"
		aria-labelledby="modalNuovoComponenteLabel" aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modal-nuovo-consumo-label">AGGIUNTA COMPONENTE</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form class="forms-sample" id="form-nuovo-consumo">
						<div class="row">
							<div class="col-12">
								<div class="form-group">
									<label for="con_IdProduzione">Ordine di produzione</label>
									<input type="text" class="form-control form-control-sm dati-popup-modifica" name="con_IdProduzione"
										id="con_IdProduzione" pla readonly>
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="con_IdRisorsa">Macchina</label><span style='color:red'> *</span>
									<select class="form-control form-control-sm dati-popup-modifica selectpicker" name="con_IdRisorsa"
										id="con_IdRisorsa" data-live-search="true" required>
									</select>
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="con_IdTipoConsumo">Consumo</label><span style='color:red'> *</span>
									<select class="form-control form-control-sm dati-popup-modifica selectpicker" id="con_IdTipoConsumo"
										name="con_IdTipoConsumo" data-live-search="true" required>

									</select>
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="con_Rilevato">Tipo calcolo</label><span style='color:red'> *</span>
									<select class="form-control form-control-sm dati-popup-modifica selectpicker" name="con_Rilevato"
										id="con_Rilevato" required>
										<option value=0>Nessun calcolo</option>
										<option value=1>Rilevato dalla macchina</option>
										<option value=2>Calcolata in base ad ipotetico</option>
									</select>
								</div>
							</div>

							<div class="col-6">
								<div class="form-group">
									<label for="con_ConsumoPezzoIpotetico">Consumo ipotetico per pezzo</label><span style='color:red'>
										*</span>
									<input type="number" class="form-control form-control-sm dati-popup-modifica"
										name="con_ConsumoPezzoIpotetico" id="con_ConsumoPezzoIpotetico" autocomplete="off" required>
								</div>
							</div>

							<input type="hidden" id="con_IdLineaProduzione" name="con_IdLineaProduzione" value="">
							<input type="hidden" id="con_Azione" name="con_Azione" value="nuovo">
						</div>
					</form>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-success" id="salva-consumo">Salva</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
				</div>

			</div>
		</div>
	</div>

	<!-- Popup modale di AGGIUNTA CLIENTE -->
	<div class="modal fade" id="modal-nuovo-cliente" tabindex="-1" role="dialog"
		aria-labelledby="modalNuovoComponenteLabel" aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modal-nuovo-cliente-label">AGGIUNTA COMPONENTE</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form class="forms-sample" id="form-nuovo-cliente">
						<div class="row">
							<div class="col-12">
								<div class="form-group">
									<label for="co_IdProduzione">Ordine di produzione</label>
									<input type="text" class="form-control form-control-sm dati-popup-modifica" name="co_IdProduzione"
										id="co_IdProduzione" pla readonly>
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="cl_IdRiga">Cliente</label><span style='color:red'> *</span>
									<select class="form-control form-control-sm dati-popup-modifica selectpicker" id="cl_IdRiga"
										name="cl_IdRiga" data-live-search="true" required>

									</select>
								</div>
							</div>

							<div class="col-6">
								<div class="form-group">
									<label for="co_Qta">Quantità</label>
									<input type="number" class="form-control form-control-sm dati-popup-modifica" name="co_Qta"
										id="co_Qta" autocomplete="off">
								</div>
							</div>

							<div class="col-12">
								<div class="form-group">
									<label for="co_Note">Note</label>
									<input type="text" class="form-control form-control-sm dati-popup-modifica" name="co_Note"
										id="co_Note" autocomplete="off">
								</div>
							</div>

							<input type="hidden" id="co_Azione" name="co_Azione" value="nuovo">
						</div>
					</form>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-success" id="salva-cliente">Salva</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
				</div>

			</div>
		</div>
	</div>

	<!-- Opup modale di RIEPILOGO DATI COMMESSA DA GANTT -->
	<div class="modal fade" id="modal-dettagli-ordine" tabindex="-1" role="dialog"
		aria-labelledby="modal-dettagli-ordine-label" aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modalProdottiLabel">RIEPILOGO COMMESSA</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form class="forms-sample" id="form-dettagli-ordine">

						<div class="row">

							<div class="col-6">
								<div class="form-group">
									<label for="op_IdProduzione">Codice commessa</label>
									<input readonly type="text" class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="op_IdProduzione" id="op_IdProduzione">
								</div>
							</div>

							<div class="col-6">
								<div class="form-group">
									<label for="lp_Descrizione">Linea</label>
									<input readonly type="text" class="form-control form-control-sm dati-popup-modifica"
										name="lp_Descrizione" id="lp_Descrizione">
								</div>
							</div>

							<div class="col-8">
								<div class="form-group">
									<label for="prd_Descrizione">Prodotto</label>
									<input readonly type="text" class="form-control form-control-sm dati-popup-modifica"
										name="prd_Descrizione" id="prd_Descrizione">
								</div>
							</div>

							<div class="col-4">
								<div class="form-group">
									<label for="op_QtaDaProdurreUdm">Qta da prod.</label>
									<input readonly type="text" class="form-control form-control-sm dati-popup-modifica"
										name="op_QtaDaProdurreUdm" id="op_QtaDaProdurreUdm">
								</div>
							</div>

							<div class="col-12">
								<hr>
							</div>

							<div class="col-6">
								<div class="form-group">
									<label for="op_DataOraProduzione">Programmato per</label>
									<input type="datetime-local" class="form-control form-control-sm dati-popup-modifica data"
										name="op_DataOraProduzione" id="op_DataOraProduzione">
								</div>
							</div>

							<div class="col-6">
								<div class="form-group">
									<label for="op_DataOraFineTeorica">Termine previsto</label>
									<input type="datetime-local" class="form-control form-control-sm dati-popup-modifica data"
										name="op_DataOraFineTeorica" id="op_DataOraFineTeorica">
								</div>
							</div>

							<input type="hidden" id="vel_VelocitaTeoricaLinea" name="vel_VelocitaTeoricaLinea" value="">
							<input type="hidden" id="op_QtaDaProdurre" name="op_QtaDaProdurre" value="">

						</div>


					</form>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-success" id="salva-lavoro-gantt" data-dismiss="modal">Salva</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Chiudi</button>
				</div>

			</div>
		</div>
	</div>

	<?php include("inc_js.php") ?>
	<script src="../js/timelineordini_new.js"></script>
	<script src="../js/gestionecommesse.js"></script>

</body>

</html>