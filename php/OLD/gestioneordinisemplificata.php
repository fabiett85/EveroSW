<?php
// in che pagina siamo
$pagina = 'gestioneordinisemplificata';

include("../inc/conn.php");


function aggiornaVelocitaTeoriche (PDO $conn_mes, $idLineaProduzione, $idProdotto, $velocitaTeoricaLinea) {

	// Recupero l'elenco delle linee definite (UNA LINEA SOLTANTO)
	$idLineaProduzione = $idLineaProduzione;

	// INIZIALIZZO DISTINTA RISORSE
	// query di eliminazione da tabella 'velocita_teoriche' per l'ID prodotto e l'ID linea considerati
	$sthDeleteVelocitaTeorica = $conn_mes->prepare(
		"DELETE FROM velocita_teoriche
		WHERE vel_IdProdotto = :IdProdotto
		AND vel_IdLineaProduzione = :IdLineaProduzione"
	);
	$sthDeleteVelocitaTeorica->execute([
		'IdProdotto' => $idProdotto,
		'IdLineaProduzione' => $idLineaProduzione
	]);


	// query di inserimento in tabella 'velocita_teoriche' per l'ID prodotto e l'ID linea considerati

	$sthInsertVelocitaTeorica = $conn_mes->prepare(
		"INSERT INTO velocita_teoriche(vel_IdProdotto,vel_IdLineaProduzione,vel_VelocitaTeoricaLinea)
		VALUES(:IdProdotto,:IdLineaProduzione,:VelocitaTeoricaLinea)"
	);
	$sthInsertVelocitaTeorica->execute([
		'IdProdotto' => $idProdotto,
		'IdLineaProduzione' => $idLineaProduzione,
		'VelocitaTeoricaLinea' => (float)$velocitaTeoricaLinea
	]);

}
	
	
function inizializzaDistinte(PDO $conn_mes, $idLineaProduzione, $idProduzione, $idProdotto, $risorseSelezionate)
{
	// elimino risorsa da tabella 'risorse_coinvolte'
	$sthDeleteRisorseCoinvolte = $conn_mes->prepare(
		"DELETE FROM risorse_coinvolte
		WHERE rc_IdProduzione = :id"
	);
	$sthDeleteRisorseCoinvolte->execute(['id' => $idProduzione]);

	// elimino risorsa da tabella 'componenti'
	$sthDeleteComponenti = $conn_mes->prepare(
		"DELETE FROM componenti
		WHERE cmp_IdProduzione = :id"
	);
	$sthDeleteComponenti->execute(['id' => $idProduzione]);

	// INIZIALIZZO DISTINTA RISORSE
	foreach ($risorseSelezionate as $risorsa) {
		// INIZIALIZZO DISTINTA RISORSE
		$sthInizializzaDistintaRisorse = $conn_mes->prepare("INSERT INTO risorse_coinvolte (rc_IdProduzione, rc_IdRisorsa, rc_LineaProduzione,  rc_RegistraMisure, rc_FlagUltima, rc_IdRicetta) SELECT :IdOrdineProduzione, ris_IdRisorsa, ris_LineaProduzione, ris_AbiMisure, ris_FlagUltima, ricm_Ricetta FROM risorse LEFT JOIN ricette_macchina ON (risorse.ris_IdRisorsa = ricette_macchina.ricm_IdRisorsa AND ricm_IdProdotto = :IdProdotto) WHERE risorse.ris_IdRisorsa = :IdRisorsa");
		$sthInizializzaDistintaRisorse->execute(array(":IdRisorsa" => $risorsa, ":IdOrdineProduzione" => $idProduzione, ":IdProdotto" => $idProdotto));			
	}	


	// INIZIALIZZO DISTINTA COMPONENTI
	$sthInizializzaDistintaComponenti = $conn_mes->prepare(
		"INSERT INTO componenti(cmp_IdProduzione, cmp_Componente, cmp_Qta, cmp_Udm, cmp_FattoreMoltiplicativo, cmp_PezziConfezione)
		SELECT :IdOrdineProduzione, dpc_Componente, dpc_Quantita, dpc_Udm, dpc_FattoreMoltiplicativo, dpc_PezziConfezione
		FROM distinta_prodotti_corpo
		WHERE dpc_Prodotto = :IdProdotto"
	);
	$sthInizializzaDistintaComponenti->execute([
		'IdOrdineProduzione' => $idProduzione,
		'IdProdotto' => $idProdotto
	]);
}

if (!empty($_REQUEST['azione'])) {
	// GESTIONE COMMESSE SEMPLIFICATA: VISUALIZZAZIONE ELENCO COMMESSE PRESENTI
	if ($_REQUEST['azione'] == 'mostra') {

		// estraggo la lista
		// estraggo la lista
		$sth = $conn_mes->prepare("SELECT A.*, B.* FROM 
								(SELECT ODP.op_IdProduzione, ODP.op_LineaProduzione, ODP.op_ProgressivoParziale, ODP.op_Riferimento, ODP.op_Prodotto, ODP.op_DataOrdine, ODP.op_OraOrdine, ODP.op_DataProduzione, ODP.op_OraProduzione, ODP.op_DataFineTeorica, ODP.op_OraFineTeorica, ODP.op_QtaRichiesta, ODP.op_QtaDaProdurre, ODP.op_Lotto, ODP.op_NoteProduzione, ODP.op_Priorita, ODP.op_Stato, P.prd_Descrizione,  so.so_Descrizione, ODP.op_Caricato, UM.*, LDP.lp_Descrizione
								FROM ordini_produzione AS ODP
								LEFT JOIN linee_produzione AS LDP ON ODP.op_LineaProduzione = LDP.lp_IdLinea
								LEFT JOIN prodotti AS P ON ODP.op_Prodotto = P.prd_IdProdotto
								LEFT JOIN stati_ordine AS so ON ODP.op_Stato = so.so_IdStatoOrdine
								LEFT JOIN unita_misura AS UM ON ODP.op_Udm = UM.um_IdRiga
								WHERE ODP.op_Stato LIKE :IdStatoOrdine) AS A
								LEFT JOIN 
								(SELECT STRING_AGG(risorse.ris_Descrizione, ', ') AS risorseSelezionate, rc_idProduzione	
								FROM risorse_coinvolte
								LEFT JOIN risorse ON risorse_coinvolte.rc_IdRisorsa = risorse.ris_IdRisorsa
								GROUP BY rc_IdProduzione) AS B
								ON A.op_IdProduzione = B.rc_IdProduzione", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
		$sth->execute(array(":IdStatoOrdine" => $_REQUEST['idStatoOrdine']));
		$righe = $sth->fetchAll();

		$output = [];
		foreach ($righe as $riga) {
			$dp = new DateTime($riga['op_DataProduzione']);
			$op = strtotime($riga['op_OraProduzione']);
			$dft = new DateTime($riga['op_DataFineTeorica']);
			$oft = strtotime($riga['op_OraFineTeorica']);

			// Verifico la presenza di ordini caricati per la linea (risorsa) in oggetto (Caricato = True)
			$sthVerificaOrdiniCaricati = $conn_mes->prepare(
				"SELECT COUNT(*) AS OrdiniCaricati FROM ordini_produzione
					WHERE op_Caricato = 1
					AND op_LineaProduzione = :IdLineaProduzione"
			);
			$sthVerificaOrdiniCaricati->execute(['IdLineaProduzione' => $riga['op_LineaProduzione']]);
			$rigaOrdiniCaricati = $sthVerificaOrdiniCaricati->fetch();

			if ($rigaOrdiniCaricati['OrdiniCaricati'] > 0) {
				$ordiniCaricati = true;
			} else {
				$ordiniCaricati = false;
			}

			// Verifico la presenza di ordini avviati per la linea (risorsa) in oggetto (Stato = 4)
			$sthVerificaOrdiniAvviati = $conn_mes->prepare(
				"SELECT COUNT(*) AS OrdiniAvviati FROM ordini_produzione
					WHERE op_Stato = 4
					AND op_LineaProduzione = :IdLineaProduzione"
			);
			$sthVerificaOrdiniAvviati->execute(['IdLineaProduzione' => $riga['op_LineaProduzione']]);
			$rigaOrdiniAvviati = $sthVerificaOrdiniAvviati->fetch();

			if ($rigaOrdiniAvviati['OrdiniAvviati'] > 0) {
				$ordiniAvviati = true;
			} else {
				$ordiniAvviati = false;
			}

			// Gestione visualizzazione pulsante 'CARICA COMMESSA'
			if ((!$riga['op_Caricato']) && (($riga['op_Stato'] < 4)  || ($riga['op_Stato'] > 6)) && ($riga['op_Stato'] != 1) && (!$ordiniAvviati) && (!$ordiniCaricati)) {
				$stringaPulsanteCarica = '<button class="btn btn-primary mdi mdi-download mdi-24px carica-ordine-produzione" type="button" title="Carica ordine"></button>';
			} else {
				$stringaPulsanteCarica = '<button class="btn btn-primary mdi mdi-download mdi-24px carica-ordine-produzione" type="button" title="Carica ordine" disabled></button>';
			}


			// Gestione visualizzazione pulsante 'SCARICA COMMESSA'
			if (($riga['op_Caricato']) && (($riga['op_Stato'] < 4)  || ($riga['op_Stato'] > 6)) && ($riga['op_Stato'] != 1) && (!$ordiniAvviati)) {
				$stringaPulsanteScarica = '<button class="btn btn-primary mdi mdi-upload mdi-24px scarica-ordine-produzione ml-2" type="button" title="Scarica ordine"></button>';
			} else {
				$stringaPulsanteScarica = '<button class="btn btn-primary mdi mdi-upload mdi-24px scarica-ordine-produzione ml-2" type="button" title="Scarica ordine" disabled></button>';
			}


			// Gestione visualizzazione pulsanti 'GESTISCI/ELIMINA'
			if (($riga['op_Stato'] >= 3) && ($riga['op_Stato'] <= 4)) { // Ordini caricati, ordini avviati -> mostro pulsanti di AZIONE disabilitati
				$stringaPulsantiAzione =
					'<div class="dropdown">
							<button class="btn btn-primary dropdown-toggle mdi mdi-lead-pencil mdi-18px"
							type="button"
							id="dropdownMenuButton"
							data-toggle="dropdown"
							aria-haspopup="true"
							aria-expanded="false"
							title="Modifica riga"
							disabled></button>
						</div>';
			} else if ($riga['op_Stato'] == 5) { // Ordini chiusi -> mostro pulsante 'RIPRENDI'
				$stringaPulsantiAzione =
					'<button
							type="button"
							class="btn btn-primary btn-lg py-1"
							id="riprendi-ordine-parziale"
							title="Riprendi ordine">RIPRENDI
						</button>';
			} else if ($riga['op_Stato'] == 6) { // Manutenzione ordinarie -> mostro pulsante di AZIONE-ELIMINA abilitato
				$stringaPulsantiAzione =
					'<div class="dropdown">
						<button class="btn btn-primary dropdown-toggle mdi mdi-lead-pencil mdi-18px"
							type="button"
							id="dropdownMenuButton"
							data-toggle="dropdown"
							aria-haspopup="true"
							aria-expanded="false"
							title="Modifica riga"></button>
						<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
							<a class="dropdown-item cancella-commessa">
								<i class="mdi mdi-trash-can"></i>  ELIMINA
							</a>
						</div>
					</div>';
			} else { // Ordini memo, ordini attivi -> mostro pulsanti di AZIONE abilitati
				$stringaPulsantiAzione =
					'<div class="dropdown">
						<button class="btn btn-primary dropdown-toggle mdi mdi-lead-pencil mdi-18px"
							type="button"
							id="dropdownMenuButton"
							data-toggle="dropdown"
							aria-haspopup="true"
							aria-expanded="false"
							title="Modifica riga"></button>
						<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
							<a class="dropdown-item gestisci-commessa">
								<i class="mdi mdi-cog"></i>  GESTISCI
							</a>
							<a class="dropdown-item cancella-commessa">
								<i class="mdi mdi-trash-can"></i>  ELIMINA
							</a>
						</div>
					</div>';
			}

			$comandi = '';
			switch ($riga['op_Stato']) {
				case 2:


					break;
				case 3:
					$comandi .= '<div class="d-flex justify-content-around">';
					$comandi .= '<button class="btn btn-success mdi mdi-play-circle mdi-24px avvia-ordine-produzione" type="button" title="Avvia ordine"></button>';
					$comandi .= '</div>';
					break;
				case 4:
					/*$sth = $conn_mes->prepare(
						"SELECT COUNT(*) AS conto FROM risorsa_produzione_parziale
						WHERE rpp_IdProduzione = :idProduzione AND rpp_Fine IS NULL"
					);
					$sth->execute(['idProduzione' => $riga['op_IdProduzione']]);
					$conto = $sth->fetch()['conto'];
					if ($conto == 0) {*/
						$comandi .= '<button class="btn btn-danger mdi mdi-stop-circle mdi-24px termina-ordine-produzione" type="button" title="Termina ordine"></button>';
					/*
					} else {
						$comandi .= '<button class="btn btn-danger mdi mdi-stop-circle mdi-24px termina-ordine-produzione" type="button" title="Termina ordine" disabled></button>';
					}
					*/
					break;
			}


			$riga['IdProduzioneERif'] = ($riga['op_Riferimento'] != "" ?
				$riga['op_IdProduzione'] . " (" . $riga['op_Riferimento'] . ")" :
				$riga['op_IdProduzione']
			);

			$riga['DataOraProgrammazione'] = $dp->format('d/m/Y') . " - " . date('H:i', $op);
			$riga['DataOraFineTeorica'] = $dft->format('d/m/Y') . " - " . date('H:i', $oft);

			$riga['azioniCaricaScarica'] = '<div>' . $stringaPulsanteCarica . $stringaPulsanteScarica . '</div>';
			$riga['comandi'] = $comandi;
			$riga['azioni'] = $stringaPulsantiAzione;

			$output[] = $riga;
		}


		die(json_encode($output));
	}


	// GESTIONE COMMESSE SEMPLIFICATA: RECUPERO VALORI DELL'COMMESSA SELEZIONATO
	if(!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "recupera-ordine" && !empty($_REQUEST["codice"]))
	{
		// estraggo la lista
		$sth = $conn_mes->prepare("SELECT A.*, B.* FROM 
									(SELECT ordini_produzione.*, velocita_teoriche.vel_VelocitaTeoricaLinea FROM ordini_produzione
									LEFT JOIN velocita_teoriche ON ordini_produzione.op_Prodotto = velocita_teoriche.vel_IdProdotto AND ordini_produzione.op_LineaProduzione = velocita_teoriche.vel_IdLineaProduzione
									WHERE op_IdProduzione = :codice) AS A
									LEFT JOIN 
									(SELECT STRING_AGG(risorse_coinvolte.rc_IdRisorsa, ',') AS op_RisorseSelezionate, rc_idProduzione	
									FROM risorse_coinvolte
									GROUP BY rc_IdProduzione) AS B
									ON A.op_IdProduzione = B.rc_IdProduzione", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
		$sth->execute(array(":codice" => $_REQUEST["codice"]));
		$riga = $sth->fetch(PDO::FETCH_ASSOC);

		//debug($riga,"RIGA");

		die(json_encode($riga));
	}



	// GESTIONE COMMESSE SEMPLIFICATA: GESTIONE CANCELLAZIONE
	if ($_REQUEST['azione'] == 'cancella-ordine-produzione' && !empty($_REQUEST['id'])) {

		// apro transazione MySQL
		$conn_mes->beginTransaction();

		try {

			// elimino risorsa da tabella 'ordini_produzione'
			$sthDeleteOrdiniProduzione = $conn_mes->prepare(
				"DELETE FROM ordini_produzione
				WHERE op_IdProduzione = :id"
			);
			$sthDeleteOrdiniProduzione->execute(['id' => $_REQUEST['id']]);

			// elimino risorsa da tabella 'risorse_coinvolte'
			$sthDeleteRisorseCoinvolte = $conn_mes->prepare(
				"DELETE FROM risorse_coinvolte
				WHERE rc_IdProduzione = :id"
			);
			$sthDeleteRisorseCoinvolte->execute(['id' => $_REQUEST['id']]);

			// elimino risorsa da tabella 'componenti'
			$sthDeleteComponenti = $conn_mes->prepare(
				"DELETE FROM componenti
				WHERE cmp_IdProduzione = :id"
			);
			$sthDeleteComponenti->execute(['id' => $_REQUEST['id']]);

			// eseguo commit della transazione
			$conn_mes->commit();
			die('OK');
		} catch (Throwable $th) {

			// eseguo rollback della transazione
			$conn_mes->rollBack();
			die($th->getMessage());
		}
	}



	// GESTIONE COMMESSE SEMPLIFICATA: GESTIONE SALVATAGGIO DATI DA POPUP MODIFICA/INSERIMENTO
	if ($_REQUEST['azione'] == 'verifica-valori-ripetuti' && !empty($_REQUEST['idProduzione'])) {
		// verifico che il codice cliente inserito, che mi funge da ID, non esista in altri record
		$sthSelectLotto = $conn_mes->prepare(
			"SELECT * FROM ordini_produzione
			WHERE op_Lotto = :Lotto
			AND op_IdProduzione != :IdProduzione"
		);
		$sthSelectLotto->execute([
			'Lotto' => $_REQUEST['lottoInserito'],
			'IdProduzione' => $_REQUEST['idProduzione']
		]);
		$trovatiVerificaLotto = $sthSelectLotto->fetch();

		if ($trovatiVerificaLotto && !empty($_REQUEST['lottoInserito'])) {
			die('RIPETUTI');
		} else {
			die('OK');
		}
	}




	// GESTIONE COMMESSE SEMPLIFICATA: GESTIONE SALVATAGGIO DATI DA POPUP MODIFICA/INSERIMENTO
	if ($_REQUEST['azione'] == 'salva-ordine-produzione' && !empty($_REQUEST['data'])) {

		// ricavo data e ora attuali
		$now = new DateTime();
		$dataOdierna = $now->format('Y-m-d');
		$oraOdierna = $now->format('H:i:s');

		// recupero i parametri dal POST
		$parametri = [];
		parse_str($_REQUEST['data'], $parametri);

		// apro transazione MySQL
		$conn_mes->beginTransaction();

		try {

			// se devo modificare
			if ($parametri['azione'] == 'modifica') {

				// verifico che il codice cliente inserito, che mi funge da ID, non esista in altri record se non in quello che sto modificando
				$sth = $conn_mes->prepare(
					"SELECT * FROM ordini_produzione
					WHERE op_IdProduzione = :IdProduzione
					AND op_IdProduzione != :IdRiga"
				);
				$sth->execute([
					'IdProduzione' => $parametri['op_IdProduzione'],
					'IdRiga' => $parametri['op_IdOrdine_Aux']
				]);
				$righeTrovate = $sth->fetch();

				if (!$righeTrovate) {

					$velTeoricaLinea = (float)$parametri['vel_VelocitaTeoricaLinea'];
					$pezziAlSecondo = floatval($velTeoricaLinea / 3600);
					$tempoTeoricoDurataOrdine = intval(
						($parametri['op_QtaRichiesta'] > 0 ?
							$parametri['op_QtaRichiesta'] : 0) / ($pezziAlSecondo != 0 ?
							$pezziAlSecondo : 1
						)
					);


					$dtTeoricaFine = new DateTime($parametri['op_DataProduzione'] . 'T' . $parametri['op_OraProduzione']);
					$dtTeoricaFine->modify('+ ' . $tempoTeoricoDurataOrdine . ' seconds');
					$strTeoricaFine =  $dtTeoricaFine->format('Y-m-d H:i');

					$dataTeoricaFine = substr($strTeoricaFine, 0, 10);
					$oraTeoricaFine = substr($strTeoricaFine, 11, 15);

					$id_modifica = $parametri['op_IdOrdine_Aux'];

					$sthUpdate = $conn_mes->prepare(
						"UPDATE ordini_produzione SET
						op_Riferimento = :RiferimentoCommessa,
						op_Stato = :StatoOrdine,
						op_Prodotto = :IdProdotto,
						op_QtaRichiesta = :QtaRichiesta,
						op_QtaDaProdurre = :QtaRichiesta2,
						op_LineaProduzione = :LineaProduzione,
						op_Udm = :UnitaDiMisura,
						op_DataOrdine = :DataOrdine,
						op_OraOrdine = :OraOrdine,
						op_DataProduzione = :DataProduzione,
						op_OraProduzione = :OraProduzione,
						op_Lotto = :Lotto,
						op_Priorita = :Priorita,
						op_NoteProduzione = :NoteProduzione,
						op_DataFineTeorica = :DataFineTeorica,
						op_OraFineTeorica = :OraFineTeorica
						WHERE op_IdProduzione = :IdRiga"
					);
					$sthUpdate->execute([
						'RiferimentoCommessa' => $parametri['op_Riferimento'],
						'StatoOrdine' => $parametri['op_Stato'],
						'IdProdotto' => $parametri['op_Prodotto'],
						'QtaRichiesta' => (float)$parametri['op_QtaRichiesta'],
						'QtaRichiesta2' => (float)$parametri['op_QtaRichiesta'],
						'LineaProduzione' => $parametri['op_LineaProduzione'],
						'UnitaDiMisura' => $parametri['op_Udm'],
						'DataOrdine' => $dataOdierna,
						'OraOrdine' => $oraOdierna,
						'DataProduzione' => $parametri['op_DataProduzione'],
						'OraProduzione' => $parametri['op_OraProduzione'],
						'Lotto' => $parametri['op_Lotto'],
						'Priorita' => $parametri['op_Priorita'],
						'NoteProduzione' => $parametri['op_NoteProduzione'],
						'IdRiga' => $parametri['op_IdOrdine_Aux'],
						'DataFineTeorica' => $dataTeoricaFine,
						'OraFineTeorica' => $oraTeoricaFine
					]);
				} else {
					die("L'id inserito: " . $parametri['op_IdProduzione'] . ' è già assegnato ad un altro ordine.');
				}
			} else // nuovo inserimento
			{

				// verifico che il codice cliente inserito, che mi funge da ID, non esista in altri record
				$sthSelect = $conn_mes->prepare(
					"SELECT * FROM ordini_produzione
					WHERE op_IdProduzione = :IdProduzione"
				);
				$sthSelect->execute(['IdProduzione' => $parametri['op_IdProduzione']]);
				$trovati = $sthSelect->fetch();


				if (!$trovati) {

					$velTeoricaLinea = (float)$parametri['vel_VelocitaTeoricaLinea'];
					$pezziAlSecondo = floatval($velTeoricaLinea / 3600);
					$tempoTeoricoDurataOrdine = intval($parametri['op_QtaRichiesta'] / ($pezziAlSecondo != 0 ? $pezziAlSecondo : 1));


					$dtTeoricaFine = new DateTime($parametri['op_DataProduzione'] . 'T' . $parametri['op_OraProduzione']);
					$dtTeoricaFine->modify('+ ' . $tempoTeoricoDurataOrdine . ' seconds');
					$strTeoricaFine =  $dtTeoricaFine->format('Y-m-d H:i');

					$dataTeoricaFine = substr($strTeoricaFine, 0, 10);
					$oraTeoricaFine = substr($strTeoricaFine, 11, 15);

					// Quantità iniziale da cui deve partire il conteggio sulla macchina
					$qtaInizialeConteggio = 0;
					$sthInsert = $conn_mes->prepare(
						"INSERT INTO ordini_produzione(op_IdProduzione,op_Riferimento,op_Stato,op_Prodotto,op_QtaRichiesta,op_QtaDaProdurre,op_QtaInizialeConteggio,op_LineaProduzione,op_Udm,op_DataOrdine,op_OraOrdine,op_DataProduzione,op_OraProduzione,op_Lotto,op_Priorita,op_NoteProduzione,op_DataFineTeorica,op_OraFineTeorica)
						VALUES(:IdProduzione,:RiferimentoCommessa,:StatoOrdine,:IdProdotto,:QtaRichiesta,:QtaRichiesta2,:QtaIniziale,:LineaProduzione,:UnitaDiMisura,:DataOrdine,:OraOrdine,:DataProduzione,:OraProduzione,:Lotto,:Priorita,:NoteProduzione,:DataFineTeorica,:OraFineTeorica)"
					);
					$sthInsert->execute([
						'IdProduzione' => $parametri['op_IdProduzione'],
						'RiferimentoCommessa' => $parametri['op_Riferimento'],
						'StatoOrdine' => $parametri['op_Stato'],
						'IdProdotto' => $parametri['op_Prodotto'],
						'QtaRichiesta' => (float)$parametri['op_QtaRichiesta'],
						'QtaRichiesta2' => (float)$parametri['op_QtaRichiesta'],
						'QtaIniziale' => (float) $qtaInizialeConteggio,
						'LineaProduzione' => $parametri['op_LineaProduzione'],
						'UnitaDiMisura' => $parametri['op_Udm'],
						'DataOrdine' => $dataOdierna,
						'OraOrdine' => $oraOdierna,
						'DataProduzione' => $parametri['op_DataProduzione'],
						'OraProduzione' => $parametri['op_OraProduzione'],
						'Lotto' => $parametri['op_Lotto'],
						'Priorita' => $parametri['op_Priorita'],
						'NoteProduzione' => $parametri['op_NoteProduzione'],
						'DataFineTeorica' => $dataTeoricaFine,
						'OraFineTeorica' => $oraTeoricaFine
					]);
				} else {
					die("L'id inserito: " . $parametri['op_IdProduzione'] . ' è già assegnato ad un altro ordine.');
				}
			}

			inizializzaDistinte(
				$conn_mes,
				$parametri['op_LineaProduzione'],
				$parametri['op_IdProduzione'],
				$parametri['op_Prodotto'],
				$_REQUEST['risorseSelezionate']
			);
			
			aggiornaVelocitaTeoriche (
				$conn_mes,
				$parametri['op_LineaProduzione'],
				$parametri['op_Prodotto'],
				$parametri['vel_VelocitaTeoricaLinea']					
			);

			// eseguo commit della transazione
			$conn_mes->commit();
			die('OK');
		} catch (Throwable $th) {

			// eseguo rollback della transazione
			$conn_mes->rollBack();
			die($th->getMessage());
		}
	}



	// GESTIONE COMMESSE SEMPLIFICATA: RECUPERO ELENCO MACCHINE DELLA LINEA
	if ($_REQUEST['azione'] == 'recupera-risorse' && !empty($_REQUEST['idProduzione'])) {

		$elencoRisorse = "";

		// Recupero la lista delle risorse coinvolte per l'ordine in oggetto e che sono attualmente disponibili (non stanno eseguendo altre produzioni e non hanno ancora eseguito la produzione in oggetto)
		$sthRisorse = $conn_mes->prepare(
			"SELECT risorse_coinvolte.rc_IdRisorsa 
			FROM risorse_coinvolte
			WHERE rc_IdProduzione = :idProduzione"
		);
		$sthRisorse->execute(['idProduzione' => $_REQUEST['idProduzione']]);
		$risorseTrovate = $sthRisorse->fetchAll();

		// se ho trovato risorse corrispondenti ai criteri cercati
		if ($risorseTrovate) {
			die(json_encode($risorseTrovate));
		} else {
			die(json_encode([]));
		}
	}

	// GESTIONE COMMESSE SEMPLIFICATA: RECUPERO ELENCO MACCHINE DELLA LINEA
	if ($_REQUEST['azione'] == 'recupera-descrizione-risorse' && !empty($_REQUEST['idLineaProduzione'])) {

		$elencoRisorse = "";

		// Recupero la lista delle risorse coinvolte per l'ordine in oggetto e che sono attualmente disponibili (non stanno eseguendo altre produzioni e non hanno ancora eseguito la produzione in oggetto)
		$sthRisorse = $conn_mes->prepare(
			"SELECT risorse.ris_Descrizione
			FROM risorse WHERE ris_LineaProduzione = :IdLineaProduzione"
		);
		$sthRisorse->execute(['IdLineaProduzione' => $_REQUEST['idLineaProduzione']]);
		$risorseTrovate = $sthRisorse->fetchAll();

		// se ho trovato risorse corrispondenti ai criteri cercati
		if ($risorseTrovate) {

			// aggiungo ognuna delle risorse trovate alla stringa e la ritorno come risultato
			$i = 0;
			foreach ($risorseTrovate as $risorsa) {
				$output[$i] = $risorsa['ris_Descrizione'];
				$i = $i + 1;
			}
			echo json_encode($output);
			exit();
		} else {
			echo 'NO_RIS';
			exit();
		}
	}



	// GESTIONE COMMESSE SEMPLIFICATA: RECUPERO ELENCO DELLE LINEE (UNA LINEA SOLTANTO)
	if ($_REQUEST['azione'] == 'recupera-linee') {

		$elencoLinee = "";

		// Recupero la lista delle risorse coinvolte per l'ordine in oggetto e che sono attualmente disponibili (non stanno eseguendo altre produzioni e non hanno ancora eseguito la produzione in oggetto)
		$sthLinee = $conn_mes->prepare(
			"SELECT linee_produzione.lp_IdLinea FROM linee_produzione"
		);
		$sthLinee->execute();
		$righe = $sthLinee->fetchAll();

		// se ho trovato risorse corrispondenti ai criteri cercati
		if (!$righe) {
			die('NO_LIN');
			exit();
		} else if (count($righe) == 1) {
			die($righe[0]['lp_IdLinea']);
			exit();
		} else {
			die('MULT_LIN');
			exit();
		}
	}





	// GESTIONE COMMESSE SEMPLIFICATA: CARICAMENTO DELLA COMMESSA IN OGGETTO, SU TUTTE LE MACCHINE DELLA LINEA
	if ($_REQUEST['azione'] == 'carica-ordine-multiplo' && !empty($_REQUEST['elencoRisorse']) && !empty($_REQUEST['idProduzione'])) {

		// Apro la transazione MySQL
		$conn_mes->beginTransaction();

		try {


			// completo la stringa contenente l'elenco risorse trovato dall'altra funzione, aggiungendo l'ID della risorsa in oggetto
			$risorseCaricamentoOrdine = json_decode($_REQUEST['elencoRisorse'], true);

			// scorro l'array delle risorse disponibili (non hanno una produzione in corso e non hanno ancora eseguito la produzione in oggetto) ricavate dal metodo precedente
			foreach ($risorseCaricamentoOrdine as $elemento) {

				$risorsa = $elemento['rc_IdRisorsa'];

				// estraggo informazioni dell'ordine di produzione e della risorsa in oggetto
				$sthRecuperaDettaglio = $conn_mes->prepare(
					"SELECT ODP.op_IdProduzione, ODP.op_Prodotto, ODP.op_DataOrdine, ODP.op_QtaRichiesta, ODP.op_Lotto, ODP.op_Stato, ODP.op_DataProduzione, ODP.op_QtaDaProdurre, P.prd_Descrizione, RC.rc_IdRisorsa, RC.rc_NoteIniziali, UM.*, RM.ricm_Ricetta, RM.ricm_Descrizione
					FROM ordini_produzione AS ODP
					LEFT JOIN prodotti AS P ON ODP.op_Prodotto = P.prd_IdProdotto
					LEFT JOIN risorse_coinvolte AS RC ON ODP.op_IdProduzione = RC.rc_IdProduzione
					LEFT JOIN unita_misura AS UM ON ODP.op_Udm = UM.um_IdRiga
					LEFT JOIN ricette_macchina AS RM ON RC.rc_IdRicetta = RM.ricm_Ricetta
					WHERE RC.rc_IdRisorsa = :idRisorsa AND ODP.op_IdProduzione = :idProduzione"
				);
				$sthRecuperaDettaglio->execute([
					'idRisorsa' => $risorsa,
					'idProduzione' => $_REQUEST['idProduzione']
				]);
				$riga = $sthRecuperaDettaglio->fetch();

				if (!$riga) {
					$conn_mes->rollBack();
					die('NO_DISTINTA');
				}
				// compongo una stringa di riepilogo ordine, visualizzata su HMI e memorizzata nella tabella risorse
				$ordineCaricato = $riga['op_IdProduzione'] . " | " . $riga['op_Lotto'] . " | " . $riga['prd_Descrizione'] . " | " . $riga['op_QtaDaProdurre'] . ' ' . $riga['um_Descrizione'];

				// aggiorno la entry nella tabella 'risorse" per la risorsa in oggetto, in modo da memorizzarne lo stato attuale
				$sthUpdateRisorse = $conn_mes->prepare(
					"UPDATE risorse SET
					ris_IdProduzione = :ordineCaricato,
					ris_RiepilogoOrdineRisorsa = :riepilogoOrdineCaricato,
					ris_StatoOrdine = :statoOrdine,
					ris_IdRicetta = :IdRicetta,
					ris_DescrizioneRicetta = :DescrizioneRicetta,
					ris_OrdineCaricato_Scada = :FlagOrdineCaricato
					WHERE ris_IdRisorsa = :idRisorsa"
				);
				$sthUpdateRisorse->execute([
					'ordineCaricato' => $riga['op_IdProduzione'],
					'riepilogoOrdineCaricato' => $ordineCaricato,
					'statoOrdine' => 'CARICATO',
					'IdRicetta' => $riga['ricm_Ricetta'],
					'DescrizioneRicetta' => $riga['ricm_Descrizione'],
					'FlagOrdineCaricato' => True,
					'idRisorsa' => $risorsa
				]);


				// UPDATE TABELLA 'RISORSE_COINVOLTE'
				// aggiorno la entry nella tabella 'risorse' per la risorsa in oggetto impostando come 'OK' (inserisco direttamente il testo) lo stato dell'ordine di produzione caricato
				$sthUpdateRisorseCoinvolte = $conn_mes->prepare(
					"UPDATE risorse_coinvolte SET
						rc_OrdineCaricato = 1
						WHERE rc_IdProduzione = :IdOrdineProduzione
						AND rc_IdRisorsa = :IdRisorsa"
				);
				$sthUpdateRisorseCoinvolte->execute(
					[
						'IdOrdineProduzione' => $_REQUEST['idProduzione'],
						'IdRisorsa' => $risorsa
					]
				);
			}


			// UPDATE TABELLA 'ORDINI_PRODUZIONE'
			// aggiorno la entry nella tabella 'ordini_produzione' impostando l'ordine in oggetto come 'CARICATO' (id = 3) dove lo stato precendente era 'ATTIVO (id = 2)
			$sthUpdateStatoOrdine = $conn_mes->prepare(
				"UPDATE ordini_produzione SET
				op_Caricato = 1,
				op_Stato = 3
				WHERE op_IdProduzione = :IdProduzione"
			);
			$sthUpdateStatoOrdine->execute(['IdProduzione' => $_REQUEST['idProduzione']]);


			// Eseguo commit della transazione
			$conn_mes->commit();
			die('OK');
		} catch (Throwable $th) {

			// eseguo rollback della transazione
			$conn_mes->rollBack();
			die($th->getMessage());
		}
	}



	// GESTIONE COMMESSE SEMPLIFICATA: SCARICAMENTO DELLA COMMESSA IN OGGETTO, DA TUTTE LE MACCHINE DELLA LINEA
	if ($_REQUEST['azione'] == 'scarica-ordine-multiplo' && !empty($_REQUEST['elencoRisorse']) && !empty($_REQUEST['idProduzione'])) {

		// Apro la transazione MySQL
		$conn_mes->beginTransaction();

		try {

			// verifico che il codice cliente inserito, che mi funge da ID, non esista in altri record
			$sthVerificaCommessaAvviata = $conn_mes->prepare(
				"SELECT COUNT(*) AS RisorseOrdineAvviato FROM risorse
				WHERE ris_IdProduzione = :IdProduzione
				AND (ris_StatoOrdine = 'OK' OR ris_StartLavoro_Scada = 1)"
			);
			$sthVerificaCommessaAvviata->execute(['IdProduzione' => $_REQUEST['idProduzione']]);
			$rowVerificaCommessaAvviata = $sthVerificaCommessaAvviata->fetch();

			if ($rowVerificaCommessaAvviata['RisorseOrdineAvviato'] > 0) {

				// eseguo commit della transazione
				$conn_mes->commit();
				die('ORDINE_AVVIATO');
			} else {
				// completo la stringa contenente l'elenco risorse trovato dall'altra funzione, aggiungendo l'ID della risorsa in oggetto
				$risorseCaricamentoOrdine = json_decode($_REQUEST['elencoRisorse'], true);


				// scorro l'array delle risorse disponibili (non hanno una produzione in corso e non hanno ancora eseguito la produzione in oggetto) ricavate dal metodo precedente
				foreach ($risorseCaricamentoOrdine as $elemento) {
					$risorsa = $elemento['rc_IdRisorsa'];
					// aggiorno entry in tabella 'risorse' per memorizzare informazioni riguardo la produzione caricata sulla risorsa in oggetto
					$sthUpdateStatoRisorsa = $conn_mes->prepare(
						"UPDATE risorse SET
						ris_idProduzione = 'ND',
						ris_RiepilogoOrdineRisorsa = 'ND',
						ris_StatoOrdine = 'ND',
						ris_OrdineCaricato_Scada = NULL,
						ris_IdRicetta = 'ND',
						ris_DescrizioneRicetta = 'ND'
						WHERE ris_IdRisorsa = :IdRisorsa AND ris_IdProduzione = :IdProduzione"
					);
					$sthUpdateStatoRisorsa->execute([
						'IdRisorsa' => $risorsa,
						'IdProduzione' => $_REQUEST['idProduzione']
					]);



					// UPDATE TABELLA 'RISORSE_COINVOLTE'
					// aggiorno la entry nella tabella 'risorse' per la risorsa in oggetto impostando come 'OK' (inserisco direttamente il testo) lo stato dell'ordine di produzione caricato
					$sthUpdateRisorseCoinvolte = $conn_mes->prepare(
						"UPDATE risorse_coinvolte SET
						rc_OrdineCaricato = 0
						WHERE rc_IdRisorsa = :IdRisorsa
						AND rc_IdProduzione = :IdOrdineProduzione"
					);
					$sthUpdateRisorseCoinvolte->execute([
						'IdRisorsa' => $risorsa,
						'IdOrdineProduzione' => $_REQUEST['idProduzione']
					]);



					// UPDATE TABELLA 'ORDINI_PRODUZIONE'
					// aggiorno la entry nella tabella 'ordini_produzione' impostando l'ordine in oggetto come 'OK' (in esecuzione, id = 4) dove lo stato precendente era 'ATTIVO (id = 2)
					$sthUpdateStatoOrdine = $conn_mes->prepare(
						"UPDATE ordini_produzione SET
						op_Caricato = 0,
						op_Stato = 2
						WHERE op_IdProduzione = :IdProduzione"
					);
					$sthUpdateStatoOrdine->execute(['IdProduzione' => $_REQUEST['idProduzione']]);
				}


				// eseguo commit della transazione
				$conn_mes->commit();
				die('OK');
			}
		} catch (Throwable $th) {

			// eseguo rollback della transazione
			$conn_mes->rollBack();
			die($th->getMessage());
		}
	}


	// GESTIONE COMMESSE SEMPLIFICATA: RECUPERO LA VELOCITA' TEORICA RELATIVA ALLA LINEA E AL PRODOTTO SELEZIONATI
	if ($_REQUEST['azione'] == 'recupera-velocita-teorica' && !empty($_REQUEST['idProdotto'])) {

		// Recupero i dati dalla tabella 'velocita_teoriche'
		$sth = $conn_mes->prepare(
			"SELECT vel_VelocitaTeoricaLinea FROM velocita_teoriche
			WHERE vel_IdProdotto = :IdProdotto
			AND vel_IdLineaProduzione = :IdLineaProduzione"
		);
		$sth->execute(['IdProdotto' => $_REQUEST['idProdotto'], 'IdLineaProduzione' => $_REQUEST['idLineaProduzione']]);
		$riga = $sth->fetch();

		// Se ho trovato risultati, restituisco la velocità recuperata
		if ($riga) {
			die($riga['vel_VelocitaTeoricaLinea']);
		} else {
			die('NO_ROWS');
		}
	}


	// GESTIONE COMMESSE SEMPLIFICATA: GENERO ID DELLA COMMESSA
	if ($_REQUEST['azione'] == 'genera-id-produzione') {
		$anno = (new DateTime())->format('Y');
		try {

			//Recupero l'ultimo id (ordinamento lessicografico) inserito, relativo all'anno in corso
			$sth = $conn_mes->prepare(
				"SELECT TOP(1) op_IdProduzione
				FROM ordini_produzione
				WHERE op_IdProduzione LIKE :AnnoInCorso
				AND op_IdProduzione NOT LIKE '%*%'
				ORDER BY LEN(op_IdProduzione) DESC , op_IdProduzione DESC"
			);
			$sth->execute(['AnnoInCorso' => $anno . '%']);
			$riga = $sth->fetch();
			$output = [
				'messaggio' => 'OK',
				'codice' => ''
			];

			// Se ho trovato ID relativi all'anno in corso, compongo il nuovo concatenando l'anno in corso con l'indice della commessa ('ultimo indice' + 1)
			if ($riga) {
				// Genero il nuovo ID ('ultimo ID dell'anno in corso' + 1)
				$tempId = explode('_', trim($riga['op_IdProduzione']));
				if (isset($tempId[0]) && isset($tempId[1])) {
					$nuovoId = intval($tempId[1]) + 1;
					die(json_encode([
						'messaggio' => 'OK',
						'codice' => $tempId[0] . '_' . $nuovoId
					]));
				}
			}
			die(json_encode([
				'messaggio' => 'OK',
				'codice' => $anno . '_1'
			]));
		} catch (Throwable $th) {

			die($th->getMessage());
		}
	}

	// DETTAGLIO STATO PRODUZIONE: RECUPERA INFORMAZIONI SU COMMESSA SELEZIONATO
	if ($_REQUEST['azione'] == 'recupera-ordine-ripreso' && !empty($_REQUEST['idProduzione']) && isset($_REQUEST['progressivoParziale'])) {

		// estraggo la lista
		$sth = $conn_mes->prepare("SELECT A.*, B.* FROM 
									(SELECT ordini_produzione.*, velocita_teoriche.vel_VelocitaTeoricaLinea, prodotti.prd_Descrizione, rientro_linea_produzione.*
									FROM ordini_produzione
									LEFT JOIN prodotti ON ordini_produzione.op_Prodotto = prodotti.prd_IdProdotto
									LEFT JOIN rientro_linea_produzione ON ordini_produzione.op_IdProduzione = rientro_linea_produzione.rlp_IdProduzione
									LEFT JOIN velocita_teoriche ON ordini_produzione.op_Prodotto = velocita_teoriche.vel_IdProdotto AND ordini_produzione.op_LineaProduzione = velocita_teoriche.vel_IdLineaProduzione
									WHERE op_IdProduzione = :codice AND op_ProgressivoParziale = :progressivoParziale) AS A
									LEFT JOIN 
									(SELECT STRING_AGG(risorse_coinvolte.rc_IdRisorsa, ',') AS op_RisorseSelezionate, rc_idProduzione	
									FROM risorse_coinvolte
									GROUP BY rc_IdProduzione) AS B
									ON A.op_IdProduzione = B.rc_IdProduzione", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
		$sth->execute(array(":codice" => $_REQUEST["idProduzione"], ":progressivoParziale" => $_REQUEST['progressivoParziale']));
		$riga = $sth->fetch();
		


		// estraggo la lista
		$sthDatiOrdiniDuplicati = $conn_mes->prepare(
			"SELECT MAX(op_ProgressivoParziale) AS ProgressivoAttuale, SUM(rlp_QtaProdotta) AS TotaleProdotti, VT.vel_VelocitaTeoricaLinea
			FROM ordini_produzione AS ODP
			LEFT JOIN prodotti AS P ON ODP.op_Prodotto = P.prd_IdProdotto
			LEFT JOIN rientro_linea_produzione AS RLP ON ODP.op_IdProduzione = RLP.rlp_IdProduzione
			LEFT JOIN velocita_teoriche AS VT ON ODP.op_Prodotto = VT.vel_IdProdotto AND ODP.op_LineaProduzione = VT.vel_IdLineaProduzione
			WHERE op_IdProduzione LIKE :IdProduzione
			GROUP BY VT.vel_VelocitaTeoricaLinea"
		);
		$sthDatiOrdiniDuplicati->execute(['IdProduzione' => $_REQUEST['idProduzione'] . '%']);
		$rigaTotaliOrdiniDuplicati = $sthDatiOrdiniDuplicati->fetch();

		$dataOdierna = date('Y-m-d');
		$oraOdierna = date('H:i:s');

		// Aggiorno l'indice del parziale
		$progressivoParzialeAggiornato = (int) $rigaTotaliOrdiniDuplicati['ProgressivoAttuale'] + 1;

		// Calcolo il saldo da produrre (qta. richiesta a inizio ordine meno la qta. già prodotta)
		$saldoDaProdurre = floatval((float)$riga['op_QtaRichiesta'] - (float)$rigaTotaliOrdiniDuplicati['TotaleProdotti']);

		$output = [
			'op_IdProduzione' => $riga['op_IdProduzione'] . " *" . $progressivoParzialeAggiornato,
			'op_Riferimento' => $riga['op_Riferimento'],
			'op_Prodotto' => $riga['prd_Descrizione'],
			'op_Lotto' => $riga['op_Lotto'],
			'op_QtaRichiesta' => $riga['op_QtaRichiesta'],
			'op_QtaProdotta' => (float)$rigaTotaliOrdiniDuplicati['TotaleProdotti'],
			'op_QtaDaProdurre' => ($saldoDaProdurre > 0 ? $saldoDaProdurre : 0),
			'op_Prodotto' => $riga['op_Prodotto'],
			'op_LineaProduzione' => $riga['op_LineaProduzione'],
			'op_ProgressivoParziale' => $progressivoParzialeAggiornato,
			'op_VelocitaTeorica' => $riga['vel_VelocitaTeoricaLinea'],
			'op_Udm' => $riga['op_Udm'],
			'op_RisorseSelezionate' => $riga['op_RisorseSelezionate']
		];


		die(json_encode($output));
	};


	// GESTIONE RIPRESA COMMESSA: GESTIONE SALVATAGGIO DATI DA POPUP RIPRESA COMMESSA
	if ($_REQUEST['azione'] == 'salva-ordine-ripreso' && !empty($_REQUEST['data'])) {

		// Apro la transazione MySQL
		$conn_mes->beginTransaction();

		try {

			// recupero i parametri dal POST
			$parametri = [];
			parse_str($_REQUEST['data'], $parametri);


			// verifico che il codice cliente inserito, che mi funge da ID, non esista in altri record
			$sthSelect = $conn_mes->prepare(
				"SELECT * FROM ordini_produzione
				WHERE op_IdProduzione = :IdProduzione
				AND op_ProgressivoParziale = :ProgressivoParziale"
			);
			$sthSelect->execute([
				'IdProduzione' => $parametri['op_IdProduzione'],
				'ProgressivoParziale' => $parametri['op_ProgressivoParziale']
			]);
			$trovati = $sthSelect->fetch();

			$velTeoricaLinea = (float)$parametri['op_VelocitaTeorica'];
			$pezziAlSecondo = floatval($velTeoricaLinea / 3600);
			$tempoTeoricoDurataOrdine = intval(($parametri['op_QtaDaProdurre'] > 0 ? $parametri['op_QtaDaProdurre'] : 0) / ($pezziAlSecondo != 0 ? $pezziAlSecondo : 1));


			$dtTeoricaFine = new DateTime($parametri['op_DataOrdine'] . 'T' . $parametri['op_OraOrdine']);
			$dtTeoricaFine->modify('+ ' . $tempoTeoricoDurataOrdine . ' seconds');
			$strTeoricaFine =  $dtTeoricaFine->format('Y-m-d H:i');

			$dataTeoricaFine = substr($strTeoricaFine, 0, 10);
			$oraTeoricaFine = substr($strTeoricaFine, 11, 15);

			$qtaInizialeConteggio = ($parametri['op_QtaProdotta'] = 0 ? 0 : $parametri['op_QtaProdotta']);


			if (!$trovati) {
				$sthInsert = $conn_mes->prepare(
					"INSERT INTO ordini_produzione(op_IdProduzione,op_Riferimento,op_Stato,op_ProgressivoParziale,op_Prodotto,op_QtaRichiesta,op_QtaDaProdurre,op_QtaInizialeConteggio,op_LineaProduzione,op_DataOrdine,op_OraOrdine,op_Udm,op_DataProduzione,op_OraProduzione,op_DataFineTeorica,op_OraFineTeorica,op_Lotto,op_NoteProduzione)
					VALUES(:IdProduzione,:Riferimento,:StatoOrdine,:ProgressivoParziale,:IdProdotto,:QtaRichiesta,:QtaRichiesta2,:QtaIniziale,:LineaProduzione,:DataOrdine,:OraOrdine,:Udm,:DataProduzione,:OraProduzione,:DataFineTeorica,:OraFineTeorica,:Lotto,:NoteProduzione)"
				);
				$sthInsert->execute([
					'IdProduzione' => $parametri['op_IdProduzione'],
					'Riferimento' => $parametri['op_Riferimento'],
					'StatoOrdine' => 2,
					'ProgressivoParziale' => $parametri['op_ProgressivoParziale'],
					'IdProdotto' => $parametri['op_Prodotto'],
					'QtaRichiesta' => (float)$parametri['op_QtaDaProdurre'],
					'QtaRichiesta2' => (float)$parametri['op_QtaDaProdurre'],
					'QtaIniziale' => (float) $qtaInizialeConteggio,
					'LineaProduzione' => $parametri['op_LineaProduzione'],
					'DataOrdine' => $parametri['op_DataOrdine'],
					'OraOrdine' => $parametri['op_OraOrdine'],
					'Udm' =>  $parametri['op_Udm'],
					'DataProduzione' => $parametri['op_DataProduzione'],
					'OraProduzione' => $parametri['op_OraProduzione'],
					'DataFineTeorica' => $dataTeoricaFine,
					'OraFineTeorica' => $oraTeoricaFine,
					'Lotto' => $parametri['op_Lotto'],
					'NoteProduzione' => $parametri['op_Note'],
				]);

				inizializzaDistinte(
					$conn_mes,
					$parametri['op_LineaProduzione'],
					$parametri['op_IdProduzione'],
					$parametri['op_Prodotto'],
					$_REQUEST['op_risorseSelezionate']					
				);

				// eseguo commit della transazione
				$conn_mes->commit();
				die('OK');
			} else {
				// eseguo commit della transazione
				$conn_mes->commit();
				die("L'id inserito: " . $parametri['op_IdProduzione'] . " è già assegnato ad un altro ordine.");
			}
		} catch (Throwable $th) {

			// eseguo rollback della transazione
			$conn_mes->rollBack();
			die($th->getMessage());
		}
	}
}




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

					<div class="card">

						<div class="card-header">
							<div class="row">
								<div class="col-10">
									<h4 class="card-title m-2">COMPILAZIONE E GESTIONE COMMESSE</h4>
								</div>
								<div class="col-2">
									<!-- ELENCO PRODOTTI FINITI -->
									<div class="form-group m-0">
										<label for="filtro-ordini">Filtra tipologia</label>
										<select class="form-control form-control-sm selectpicker" id="filtro-ordini" name="filtro-ordini">
											<?php
											$sth = $conn_mes->prepare(
												"SELECT * FROM stati_ordine
												WHERE stati_ordine.so_IdStatoOrdine IN (1, 2, 3, 4, 5)"
											);
											$sth->execute();
											$prodotti = $sth->fetchAll();

											echo "<option value='%'>Mostra TUTTI</option>";
											foreach ($prodotti as $prodotto) {
												echo '<option value=' . $prodotto['so_IdStatoOrdine'] . '>Mostra ' . strtoupper($prodotto['so_TestoSelect']) . '</option>';
											}
											?>
										</select>
									</div>
								</div>
							</div>
						</div>
						<div class="card-body">



							<div class="row">

								<div class="col-12">

									<div class="table-responsive">

										<table id="tabellaOrdini" class="table table-striped" style="width:100%">
											<thead>
												<tr>
													<th>Codice commessa (Rif.)</th>
													<th>Linea</th>
													<th>Prodotto </th>
													<th>Tot. ric.</th>
													<th>Udm</th>
													<th>Data inizio prevista</th>
													<th>Data fine prevista</th>
													<th>Lotto</th>
													<th>Priorità</th>
													<th>Stato</th>
													<th>Macchine sel.</th>
													<th>Note</th>
													<th>Carica / Scarica</th>
													<th>Comandi</th>
													<th></th>
												</tr>
											</thead>
											<tbody>
											</tbody>

										</table>

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

	<button type="button" id="nuovo-ordine-produzione" class="mdi mdi-button">NUOVA COMMESSA</button>


	<!-- Opup modale di modifica/inserimento prodotto-->
	<div class="modal fade" id="modal-ordine-produzione" tabindex="-1" role="dialog" aria-labelledby="modal-ordine-produzione-label" aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modal-ordine-produzione-label">NUOVA COMMESSA</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form class="forms-sample" id="form-ordine-produzione">

						<div class="row">
							<div class="col-7">
								<div class="form-group">
									<label for="op_IdProduzione">Codice commessa</label><span style='color:red'> *</span>
									<input type="text" class="form-control form-control-sm dati-popup-modifica obbligatorio" name="op_IdProduzione" id="op_IdProduzione" autocomplete="off" required>
								</div>
							</div>
							<div class="col-5">
								<div class="form-group">
									<label for="op_Riferimento">Riferimento commessa</label>
									<input type="text" class="form-control form-control-sm dati-popup-modifica" name="op_Riferimento" id="op_Riferimento" autocomplete="off">
								</div>
							</div>

							<div class="col-5">
								<div class="form-group">
									<label for="op_Lotto">Lotto</label><span style='color:red'> *</span>
									<input type="text" class="form-control form-control-sm dati-popup-modifica" name="op_Lotto" id="op_Lotto" autocomplete="off" required>
								</div>
							</div>
							<div class="col-2">
								<div class="form-group">
									<label for="op_Priorita">Priorità</label><span style='color:red'> *</span>
									<input type="number" class="form-control form-control-sm dati-popup-modifica" name="op_Priorita" id="op_Priorita" autocomplete="off" value=1 required>
								</div>
							</div>

							<div class="col-5">
								<div class="form-group">
									<label for="op_Stato">Stato commessa</label><span style='color:red'> *</span>
									<select class="form-control form-control-sm selectpicker" id="op_Stato" name="op_Stato" required>
										<?php
										$sth = $conn_mes->prepare(
											"SELECT stati_ordine.* FROM stati_ordine
											WHERE stati_ordine.so_IdStatoOrdine < 3"
										);
										$sth->execute();
										$linee = $sth->fetchAll();

										foreach ($linee as $linea) {
											echo '<option value=' . $linea['so_IdStatoOrdine'] . '>' . $linea['so_Descrizione'] . '</option>';
										}
										?>
									</select>
								</div>
							</div>

							<div class="col-12">
								<div class="form-group">
									<label class="label-evidenziata-commessa" for="op_Prodotto">Prodotto</label><span style='color:red'> *</span>
									<select class="form-control form-control-sm dati-popup-modifica selectpicker" id="op_Prodotto" name="op_Prodotto" data-live-search="true" required>
										<?php
										$sth = $conn_mes->prepare(
											"SELECT * FROM prodotti
											WHERE prd_Tipo != 'MP'
											ORDER BY prd_Descrizione ASC"
										);
										$sth->execute();
										$linee = $sth->fetchAll();

										foreach ($linee as $linea) {
											echo "<option value='" . $linea['prd_IdProdotto'] . "'>" . $linea['prd_Descrizione'] . '</option>';
										}
										?>
									</select>
								</div>
							</div>

							<div class="col-6">
								<div class="form-group">
									<label class="label-evidenziata-commessa" for="op_QtaRichiesta">Qta richiesta</label><span style='color:red'> *</span>
									<input type="number" class="form-control form-control-sm dati-popup-modifica obbligatorio" name="op_QtaRichiesta" id="op_QtaRichiesta" autocomplete="off" required>
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="op_Udm">Unità di misura</label><span style='color:red'> *</span>
									<select class="form-control form-control-sm dati-popup-modifica selectpicker" name="op_Udm" id="op_Udm" data-live-search="true" required>
										<?php
										$sth = $conn_mes->prepare("SELECT * FROM unita_misura");
										$sth->execute();
										$trovate = $sth->fetchAll();
										foreach ($trovate as $udm) {
											echo "<option value='" . $udm['um_IdRiga'] . "'>" . $udm['um_Descrizione'] . " (" . $udm['um_Sigla'] . ")</option>";
										}
										?>
									</select>
								</div>
							</div>

							<div class="col-4">
								<div class="form-group">
									<label class="label-evidenziata-commessa" for="op_LineaProduzione">Linea</label><span style='color:red'> *</span>
									<select class="form-control form-control-sm dati-popup-modifica selectpicker" id="op_LineaProduzione" name="op_LineaProduzione" data-live-search="true" required>
										<?php
										$sth = $conn_mes->prepare(
											"SELECT * FROM linee_produzione
											ORDER BY lp_Descrizione ASC"
										);
										$sth->execute();
										$linee = $sth->fetchAll();

										foreach ($linee as $linea) {
											echo "<option value='" . $linea['lp_IdLinea'] . "'>" . $linea['lp_Descrizione'] . '</option>';
										}
										?>
									</select>
								</div>
							</div>



							<div class="col-3">
								<div class="form-group">
									<label for="vel_VelocitaTeoricaLinea">Vel. t. linea .<span class="ml-1 udm-vel">[ND]</span></label><span style='color:red'> *</span>
									<input type="number" class="form-control form-control-sm dati-ordine obbligatorio" id="vel_VelocitaTeoricaLinea" name="vel_VelocitaTeoricaLinea"  aria-label="" aria-describedby="inputGroup-sizing-lg">
								</div>
							</div>
							
							<div class="col-5">
								<div class="form-group">
									<label class="label-evidenziata-commessa" for="op_RisorseSelezionate">Macchine</label><span style='color:red'> *</span>
									<select multiple class="form-control form-control-sm dati-popup-modifica selectpicker" id="op_RisorseSelezionate" name="op_RisorseSelezionate"  data-live-search="true"  required>
										<?php
											$sth = $conn_mes->prepare("SELECT risorse.* FROM risorse ORDER BY risorse.ris_Ordinamento ASC", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
											$sth->execute();
											$linee = $sth->fetchAll(PDO::FETCH_ASSOC);

											foreach($linee as $linea) {
												echo "<option value='".$linea['ris_IdRisorsa']."'>".$linea['ris_Descrizione']."</option>";
											}
										?>
									</select>
								</div>
							</div>							

							<div class="col-6">
								<div class="form-group">
									<label for="op_DataProduzione">Data pianificazione</label><span style='color:red'> *</span>
									<input type="date" class="form-control form-control-sm dati-popup-modifica obbligatorio" name="op_DataProduzione" id="op_DataProduzione" required>
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="op_OraProduzione">Ora pianificazione</label><span style='color:red'> *</span>
									<input type="time" class="form-control form-control-sm dati-popup-modifica obbligatorio" name="op_OraProduzione" id="op_OraProduzione" required>
								</div>
							</div>

							<div class="col-12">
								<div class="form-group">
									<label for="op_NoteProduzione">Note produzione</label>
									<input type="text" class="form-control form-control-sm dati-popup-modifica" name="op_NoteProduzione" id="op_NoteProduzione" autocomplete="off">
								</div>
							</div>

						</div>

						<input type="hidden" id="op_IdOrdine_Aux" name="op_IdOrdine_Aux" value="">
						<input type="hidden" id="azione" name="azione" value="nuovo">

					</form>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-success" id="salva-ordine-produzione">Salva</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
				</div>

			</div>
		</div>
	</div>


	<!-- Opup modale di RIPRESA ORDINE GIA' ESEGUITO -->
	<div class="modal fade" id="modal-riprendi-ordine" tabindex="-1" role="dialog" aria-labelledby="modal-riprendi-ordine-label" aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modal-riprendi-ordine-label">RIPRENDI COMMESSA TERMINATA</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>

				<div class="modal-body">
					<form class="" id="form-riprendi-ordine">

						<div class="row">
							<div class="col-12">
								<div class="form-group">
									<label for="op_IdProduzione">Codice commessa</label>
									<input readonly type="text" class="form-control form-control-sm dati-popup-modifica" name="op_IdProduzione" id="op_IdProduzione">
								</div>
							</div>
							<div class="col-12">
								<div class="form-group">
									<label for="op_Prodotto">Prodotto</label>
									<input readonly type="text" class="form-control form-control-sm dati-popup-modifica" name="op_Prodotto" id="op_Prodotto">
								</div>
							</div>
							<div class="col-4">
								<div class="form-group">
									<label for="op_QtaRichiesta">Qta originale richiesta</label>
									<input readonly type="number" class="form-control form-control-sm dati-popup-modifica" name="op_QtaRichiesta" id="op_QtaRichiesta">
								</div>
							</div>
							<div class="col-4">
								<div class="form-group">
									<label for="op_QtaProdotta">Qta prodotta</label>
									<input readonly type="number" class="form-control form-control-sm dati-popup-modifica" name="op_QtaProdotta" id="op_QtaProdotta">
								</div>
							</div>
							<div class="col-4">
								<div class="form-group">
									<label for="op_QtaDaProdurre">Qta da produrre</label><span style='color:red'> *</span>
									<input type="number" class="form-control form-control-sm dati-popup-modifica  obbligatorio" name="op_QtaDaProdurre" id="op_QtaDaProdurre" required>
								</div>
							</div>

							<div class="col-12">
								<div class="form-group">
									<label class="label-evidenziata-commessa" for="op_RisorseSelezionate">Macchine</label><span style='color:red'> *</span>
									<select multiple class="form-control form-control-sm dati-popup-modifica selectpicker" id="op_RisorseSelezionate" name="op_RisorseSelezionate"  data-live-search="true" >
										<?php
											$sth = $conn_mes->prepare("SELECT risorse.* FROM risorse ORDER BY risorse.ris_Ordinamento ASC", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
											$sth->execute();
											$linee = $sth->fetchAll(PDO::FETCH_ASSOC);

											foreach($linee as $linea) {
												echo "<option value='".$linea['ris_IdRisorsa']."'>".$linea['ris_Descrizione']."</option>";
											}
										?>
									</select>
								</div>
							</div>	
							
							<div class="col-6">
								<div class="form-group">
									<label for="op_DataOrdine">Data compilazione</label><span style='color:red'> *</span>
									<input type="date" class="form-control form-control-sm dati-popup-modifica  obbligatorio" name="op_DataOrdine" id="op_DataOrdine" required>
								</div>
							</div>

							<div class="col-6">
								<div class="form-group">
									<label for="op_OraOrdine">Ora compilazione</label><span style='color:red'> *</span>
									<input type="time" class="form-control form-control-sm dati-popup-modifica  obbligatorio" name="op_OraOrdine" id="op_OraOrdine" required>
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="op_DataProduzione">Data pianificazione</label><span style='color:red'> *</span>
									<input type="date" class="form-control form-control-sm dati-popup-modifica obbligatorio" name="op_DataProduzione" id="op_DataProduzione" required>
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="op_OraProduzione">Ora pianificazione</label><span style='color:red'> *</span>
									<input type="time" class="form-control form-control-sm dati-popup-modifica obbligatorio" name="op_OraProduzione" id="op_OraProduzione" required>
								</div>
							</div>
							<div class="col-12">
								<div class="form-group">
									<label for="op_Lotto">Lotto</label><span style='color:red'> *</span>
									<input type="text" class="form-control form-control-sm dati-popup-modifica " name="op_Lotto" id="op_Lotto" placeholder="Lotto ordine" required>
								</div>
							</div>
							<div class="col-12">
								<div class="form-group">
									<label for="op_Note">Note</label>
									<input type="text" class="form-control form-control-sm dati-popup-modifica " name="op_Note" id="op_Note" placeholder="Note ordine">
								</div>
							</div>
						</div>

						<input type="hidden" id="op_LineaProduzione" name="op_LineaProduzione" value="">
						<input type="hidden" id="op_Prodotto" name="op_Prodotto" value="">
						<input type="hidden" id="op_Riferimento" name="op_Riferimento" value="">
						<input type="hidden" id="op_ProgressivoParziale" name="op_ProgressivoParziale" value="">
						<input type="hidden" id="op_VelocitaTeorica" name="op_VelocitaTeorica" value="">
						<input type="hidden" id="op_Udm" name="op_Udm" value="">
					</form>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-success" id="salva-ordine-ripreso">Salva</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
				</div>

			</div>
		</div>
	</div>

	<?php include("inc_js.php") ?>

	<script src="../js/gestioneordinisemplificata.js"></script>


</body>

</html>