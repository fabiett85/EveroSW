<?php
// in che pagina siamo
$pagina = 'risorse';
include("../inc/conn.php");

// debug($_SESSION['utente'],'Utente');

// : VISUALIZZAZIONE PRODOTTI MAGAZZINO
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'mostra') {
	// estraggo la lista
	$sth = $conn_mes->prepare("SELECT risorse.*, linee_produzione.lp_Descrizione, unita_misura.um_Descrizione 
								FROM risorse 
								LEFT JOIN linee_produzione ON risorse.ris_LineaProduzione = linee_produzione.lp_IdLinea
								LEFT JOIN unita_misura ON risorse.ris_Udm = unita_misura.um_IdRiga", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
	$sth->execute();
	$righe = $sth->fetchAll(PDO::FETCH_ASSOC);

	$output = [];

	$marked = '<div class="d-flex justify-content-center align-items-center" style="max-height:18px"><i class="mdi mdi-checkbox-marked mdi-18px"></i></div>';
	$unmarked = '<div class="d-flex justify-content-center align-items-center" style="max-height:18px"><span class="mdi mdi-checkbox-blank-outline"></span></div>';


	foreach ($righe as $riga) {
		//Preparo i dati da visualizzare
		$output[] = [
			'IdRisorsa' => $riga['ris_IdRisorsa'],
			'Descrizione' => $riga['ris_Descrizione'],
			'LineaProduzione' => $riga['lp_Descrizione'],
			'TTeoricoAttrezzaggio' => $riga['ris_TTeoricoAttrezzaggio'],
			'AbiMisure' => ($riga['ris_AbiMisure'] ? $marked : $unmarked),
			'FlagUltimaMacchina' => ($riga['ris_FlagUltima'] ? $marked : $unmarked),
			'FlagDisabilitaCalcoloOEE' => ($riga['ris_FlagDisabilitaOEE'] ? $marked : $unmarked),
			'ProduzioneAttivata' => $riga['ris_IdProduzione'],
			'StatoRisorsa' => $riga['ris_StatoRisorsa'],
			'StatoAllarme' => (($riga['ris_Avaria_Scada'] || $riga['ris_Avaria_Man']) ? 'SI' : 'NO'),
			'TotOreFunz' => round($riga['ris_OreFunzTotali'], 0),
			'FreqOreMan' => $riga['ris_OreFunz_FreqMan'] . " - Rim: " . $riga['ris_OreFunz_NextMan'] . "",
			'Ordinamento' => $riga['ris_Ordinamento'],
			'Udm' => $riga["um_Descrizione"],
			'azioni' => '<div class="dropdown">
					<button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Modifica riga">
					<span class="mdi mdi-lead-pencil mdi-18px"></span>
					</button>
					<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
						<a class="dropdown-item modifica-risorsa" data-id_riga="' . $riga['ris_IdRisorsa'] . '"><i class="mdi mdi-account-edit"></i> Modifica</a>
						<a class="dropdown-item cancella-risorsa" data-id_riga="' . $riga['ris_IdRisorsa'] . '"><i class="mdi mdi-trash-can"></i> Elimina</a>
					</div>
				</div>'
		];
	}

	die(json_encode($output));
}


// RISORSE: RECUPERO VALORI DELLA RISORSA SELEZIONATA
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'recupera' && !empty($_REQUEST['codice'])) {
	// estraggo la lista
	$sth = $conn_mes->prepare("SELECT * FROM risorse WHERE ris_IdRisorsa = :codice", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
	$sth->execute([':codice' => $_REQUEST['codice']]);
	$riga = $sth->fetch(PDO::FETCH_ASSOC);

	die(json_encode($riga));
}



// RISORSE: GESTIONE CANCELLAZIONE
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'cancella-risorsa' && !empty($_REQUEST['id'])) {
	$conn_mes->beginTransaction();

	try {

		// Verifico se ho ordini caricati o in esecuzione sulla macchina in oggetto
		$sthVerificaUtilizzoRisorsa = $conn_mes->prepare("SELECT
															(SELECT COUNT(*)
															FROM risorse_coinvolte AS RC
															LEFT JOIN ordini_produzione AS ODP ON RC.rc_IdProduzione = ODP.op_IdProduzione
															WHERE RC.rc_IdRisorsa = :IdRisorsa AND (ODP.op_Stato = 3 OR ODP.op_Stato = 4)) AS OrdiniInCorso,
															(SELECT COUNT(*)
															FROM risorsa_produzione AS RP
															LEFT JOIN ordini_produzione AS ODP ON RP.rp_IdProduzione = ODP.op_IdProduzione
															WHERE RP.rp_IdRisorsa = :IdRisorsa2) AS OrdiniTerminati ", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
		$sthVerificaUtilizzoRisorsa->execute([
			':IdRisorsa' => $_REQUEST['id'],
			':IdRisorsa2' => $_REQUEST['id'],
		]);
		$rigaVerificaUtilizzoRisorsa = $sthVerificaUtilizzoRisorsa->fetch(PDO::FETCH_ASSOC);

		if (($rigaVerificaUtilizzoRisorsa['OrdiniInCorso'] == 0) && ($rigaVerificaUtilizzoRisorsa['OrdiniTerminati'] == 0)) {

			// elimino risorsa da tabella 'risorse'
			$sthDistintaRisorse = $conn_mes->prepare("DELETE FROM risorse WHERE ris_IdRisorsa = :id");
			$sthDistintaRisorse->execute([':id' => $_REQUEST['id']]);

			// elimino risorsa da tabella 'ricette'
			$sthDistintaRisorse = $conn_mes->prepare("DELETE FROM ricette_macchina WHERE ricm_IdRisorsa = :id");
			$sthDistintaRisorse->execute([':id' => $_REQUEST['id']]);

			// elimino risorsa dai corpi distinta in cui è utilizzata
			$sthDeleteDistinta = $conn_mes->prepare("DELETE FROM distinta_risorse_corpo WHERE drc_IdRisorsa = :id");
			$sthDeleteDistinta->execute([':id' => $_REQUEST['id']]);

			// elimino associazione della risorsa con gli utenti
			$sthDeleteConfigPannelli = $conn_mes->prepare("DELETE FROM configurazione_pannelli WHERE cp_IdRisorsa = :id");
			$sthDeleteConfigPannelli->execute([':id' => $_REQUEST['id']]);

			// elimino gli EVENTI gestibili da SCADA per la risorsa in oggetto
			$sthDeleteAlr = $conn_mes->prepare("DELETE FROM casi WHERE cas_IdRisorsa = :id");
			$sthDeleteAlr->execute([':id' => $_REQUEST['id']]);

			// elimino le MISURE gestibili da SCADA per la risorsa in oggetto
			$sthDeleteMis = $conn_mes->prepare("DELETE FROM misure WHERE mis_IdRisorsa = :id");
			$sthDeleteMis->execute([':id' => $_REQUEST['id']]);

			$conn_mes->commit();
			die('OK');
		} else {
			die('RISORSA_OCCUPATA');
		}
	} catch (Throwable $t) {
		// Eseguo rollback della transazione
		$conn_mes->rollBack();
		die('ERRORE');
	}
}




// RISORSE: GESTIONE SALVATAGGIO DATI DA POPUP MODIFICA/INSERIMENTO
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'salva-risorsa' && !empty($_REQUEST['data'])) {

	// Apro la transazione MySQL
	$conn_mes->beginTransaction();

	try {
		// recupero i parametri dal POST
		$parametri = [];
		parse_str($_REQUEST['data'], $parametri);

		$statoAbiMisure = $_REQUEST['abiMisure'];
		$statoFlagUltimaMacchina = $_REQUEST['flagUltimaMacchina'];
		$statoFlagDisabilitaOEE = $_REQUEST['flagDisabilitaOEE'];

		if ($statoFlagUltimaMacchina == 1) {
			$aggiornatoFlagUltima = True;
			$sqlUpdateResetFlagUltima = "UPDATE risorse SET
											ris_FlagUltima = '0'
											WHERE ris_LineaProduzione = :IdLineaProduzione";
			$sthUpdateResetFlagUltima = $conn_mes->prepare($sqlUpdateResetFlagUltima);
			$sthUpdateResetFlagUltima->execute([':IdLineaProduzione' => $parametri['ris_LineaProduzione']]);
		} else {
			$aggiornatoFlagUltima = False;
		}
		$nuovaPosizione = $parametri['ris_Ordinamento'];

		// se devo modificare
		if ($parametri['azione'] == 'modifica') {
			$id_modifica = $parametri['ris_IdRisorsa_Aux'];
			/* se voglio gestione automatica delle posizioni
				//estraggo posizione attuale
				$sqlPos = "SELECT ris_Ordinamento FROM risorse
					WHERE ris_IdRisorsa = :IdRiga AND ris_LineaProduzione = :IdLineaProduzione";
				$sthPos = $conn_mes->prepare($sqlPos);
				$sthPos->execute([':IdRiga'=>$id_modifica, ':IdLineaProduzione' => $parametri['ris_LineaProduzione']]);
				$riga = $sthPos->fetch();
				$vecchiaPosizione = $riga['ris_Ordinamento'];
				//controllo se ci sono più macchine
				$sqlPos = "SELECT COUNT(*) AS conto FROM risorse
					WHERE ris_LineaProduzione = :IdLineaProduzione";
				$sthPos = $conn_mes->prepare($sqlPos);
				$sthPos->execute([':IdLineaProduzione' => $parametri['ris_LineaProduzione']]);
				$conto = $sthPos->fetch()['conto'];
				if ($conto > 1){
					//aggiorno macchina nella nuova posizione con quella vecchia
					$sqlUpdate = "UPDATE risorse SET
						ris_Ordinamento = :Vecchia
						WHERE ris_Ordinamento = :Nuova AND ris_LineaProduzione = :IdLineaProduzione";
					$sthUpdate = $conn_mes->prepare($sqlUpdate);
					$sthUpdate->execute([':Vecchia'=>$vecchiaPosizione, 'Nuova'=>$nuovaPosizione, ':IdLineaProduzione' => $parametri['ris_LineaProduzione']]);
				}
				*/
			$sqlUpdate = "UPDATE risorse SET
							ris_Descrizione = :Descrizione,
							ris_LineaProduzione = :IdLineaProduzione,
							ris_TTeoricoAttrezzaggio = :TTeoricoAttrezzaggio,
							ris_AbiMisure = :AbiMisure,
							ris_FlagUltima = :FlagUltimaMacchina,
							ris_FlagDisabilitaOEE = :FlagDisabilitaOEE,
							ris_OreFunzTotali = :OreFunzTotali,
							ris_OreFunz_FreqMan = :FreqMan,
							ris_Ordinamento = :Posizione,
							ris_OreFunz_NextMan = CASE ris_OreFunz_NextMan WHEN 0 THEN :NextMan ELSE ris_OreFunz_NextMan END,
							ris_FattoreConteggi = :FattoreConteggi,
							ris_Udm = :Udm
							WHERE ris_IdRisorsa = :IdRiga";

			$sthUpdateInsert = $conn_mes->prepare($sqlUpdate);
			$sthUpdateInsert->execute([
				':Descrizione' => $parametri['ris_Descrizione'],
				':IdLineaProduzione' => $parametri['ris_LineaProduzione'],
				':TTeoricoAttrezzaggio' => $parametri['ris_TTeoricoAttrezzaggio'],
				':AbiMisure' => $statoAbiMisure,
				':FlagUltimaMacchina' => $statoFlagUltimaMacchina,
				':FlagDisabilitaOEE' => $statoFlagDisabilitaOEE,
				':OreFunzTotali' => $parametri['ris_OreFunzTotali'],
				':FreqMan' => $parametri['ris_OreFunz_FreqMan'],
				':NextMan' => $parametri['ris_OreFunz_FreqMan'],
				':Posizione' => $nuovaPosizione,
				':FattoreConteggi' => $parametri['ris_FattoreConteggi'],
				":Udm" => $parametri["ris_Udm"],
				':IdRiga' => $id_modifica
			]);
		} else // nuovo inserimento
		{
			/* se voglio gestione automatica delle posizioni
				//aggiorno macchina nella nuova posizione con quella vecchia
				$sqlUpdate = "UPDATE risorse SET
					ris_Ordinamento = ris_Ordinamento + 1
					WHERE ris_Ordinamento >= :Nuova AND ris_LineaProduzione = :IdLineaProduzione";
				$sthUpdate = $conn_mes->prepare($sqlUpdate);
				$sthUpdate->execute(['Nuova'=>$nuovaPosizione, ':IdLineaProduzione' => $parametri['ris_LineaProduzione']]);
				*/
			// Ricavo l'ultimno codice di risorsa utilizzato per generare quello nuovo.
			$sthCodiceRisorsa = $conn_mes->prepare("SELECT TOP(1) ris_IdRisorsa
											FROM risorse
											ORDER BY LEN(ris_IdRisorsa) DESC , ris_IdRisorsa DESC", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
			$sthCodiceRisorsa->execute([]);
			$rigaCodiceRisorsa = $sthCodiceRisorsa->fetch(PDO::FETCH_ASSOC);

			// se ho già entry esistenti, formatto opportunamente il nuovo ID
			if ($rigaCodiceRisorsa) {

				$codiceTemp =  explode('_', $rigaCodiceRisorsa['ris_IdRisorsa']);
				$idRisorsaUltimo = $codiceTemp[1];


				// Formatto opportunamente il nuovo codice di risorsa
				if (($idRisorsaUltimo[0] == '0') && ($idRisorsaUltimo[1] != '9')) {
					$idRisorsaUltimoFirstCharInt = (int)$idRisorsaUltimo[1];
					$temp = intval($idRisorsaUltimoFirstCharInt + 1);
					$idRisorsa = 'RIS_0' . $temp;
				} else {
					$idRisorsaUltimoInt = (int)$idRisorsaUltimo;
					$temp = intval($idRisorsaUltimoInt + 1);
					$idRisorsa = 'RIS_' . $temp;
				}
			} else {
				$idRisorsa = 'RIS_01';
			}

			// INSERT in tabella 'risorse'
			$sqlInsert =
				"INSERT INTO risorse(ris_IdRisorsa,ris_Descrizione,ris_LineaProduzione,ris_TTeoricoAttrezzaggio,ris_Run_Man,ris_AbiMisure,ris_FlagUltima,ris_FlagDisabilitaOEE,ris_OreFunzTotali,ris_OreFunz_FreqMan,ris_OreFunz_NextMan,ris_Ordinamento,ris_ImgSinottico,ris_FattoreConteggi)
				VALUES(:IdRisorsa,:Descrizione,:IdLineaProduzione,:TTeoricoAttrezzaggio,:RunManuale,:AbiMisure,:FlagUltimaMacchina,:FlagDisabilitaOEE,:OreFunzTotali,:FreqMan,:NextMan,:Posizione,:NomeImmagine,:FattoreConteggi)";

			$sthUpdateInsert = $conn_mes->prepare($sqlInsert);
			$sthUpdateInsert->execute([
				':IdRisorsa' => $idRisorsa,
				':Descrizione' => $parametri['ris_Descrizione'],
				':IdLineaProduzione' => $parametri['ris_LineaProduzione'],
				':TTeoricoAttrezzaggio' => $parametri['ris_TTeoricoAttrezzaggio'],
				':RunManuale' => 1,
				':AbiMisure' => $statoAbiMisure,
				':FlagUltimaMacchina' => $statoFlagUltimaMacchina,
				':FlagDisabilitaOEE' => $statoFlagDisabilitaOEE,
				':OreFunzTotali' => $parametri['ris_OreFunzTotali'],
				':FreqMan' => $parametri['ris_OreFunz_FreqMan'],
				':NextMan' => $parametri['ris_OreFunz_FreqMan'],
				':Posizione' => $nuovaPosizione,
				':FattoreConteggi' => $parametri['ris_FattoreConteggi'],
				':NomeImmagine' => $parametri['ris_Descrizione'],
				":Udm" => $parametri["ris_Udm"],
			]);




			// INSERT in tabella 'configurazione pannelli'
			$sthInsertConfigPannelli = $conn_mes->prepare(
				'INSERT INTO configurazione_pannelli(cp_idUtente,cp_IdRisorsa)
				(SELECT usr_IdUtente, :IdRisorsa FROM utenti WHERE usr_Mansione <= 2)'
			);
			$sthInsertConfigPannelli->execute([
				':IdRisorsa' => $idRisorsa
			]);



			// Predispongo la lista degli EVENTI gestibili lato SCADA (10 allarmi, 1 evento di attrezzaggio, 1 evento di fermo macchina, 1 evento di pausa prevista)
			for ($i = 1; $i <= 26; $i++) {

				$indice = "";

				if ($i < 10) {
					$indice = '0' . $i;
					$idEvento = 'Alr_' . $indice;
					$descrizioneEvento = "EVENTO BLOCCANTE " . $indice . " " . $parametri['ris_Descrizione'];
					$descrizioneCaso = 'BLOCCANTE';
					$tipoCaso = 'KO';
					$flag = 0;
				} else if ($i >= 10 && $i <= 20) {
					$indice = $i;
					$idEvento = 'Alr_' . $indice;
					$descrizioneEvento = "EVENTO BLOCCANTE " . $indice . " " . $parametri['ris_Descrizione'];
					$descrizioneCaso = 'BLOCCANTE';
					$tipoCaso = 'KO';
					$flag = 0;
				} else if ($i == 21) {
					$indice = $i;
					$idEvento = 'at_scada';
					$descrizioneEvento = "ATTREZZAGGIO MACCHINA " . $parametri['ris_Descrizione'];
					$descrizioneCaso = 'ATTREZZAGGIO';
					$tipoCaso = 'AT';
					$flag = 0;
				} else if ($i == 22) {
					$indice = $i;
					$idEvento = 'pp_scada';
					$descrizioneEvento = "PAUSA PREVISTA " . $parametri['ris_Descrizione'];
					$descrizioneCaso = "PAUSA PREVISTA";
					$tipoCaso = 'KJ';
					$flag = 0;
				} else if ($i == 23) {
					$indice = $i;
					$idEvento = 'at';
					$descrizioneEvento = "ATTREZZAGGIO " . $parametri['ris_Descrizione'];
					$descrizioneCaso = 'ATTREZZAGGIO';
					$tipoCaso = 'AT';
					$flag = 1;
				} else if ($i == 24) {
					$indice = $i;
					$idEvento = 'av';
					$descrizioneEvento = "EVENTO BLOCCANTE GENERICO " . $parametri['ris_Descrizione'];
					$descrizioneCaso = 'BLOCCANTE';
					$tipoCaso = 'KO';
					$flag = 1;
				} else if ($i == 25) {
					$indice = $i;
					$idEvento = 'pp';
					$descrizioneEvento = "PAUSA PREVISTA " . $parametri['ris_Descrizione'];
					$descrizioneCaso = "PAUSA PREVISTA";
					$tipoCaso = 'KJ';
					$flag = 1;
				} else if ($i == 26) {
					$indice = $i;
					$idEvento = 'nb';
					$descrizioneEvento = "EVENTO NON BLOCCANTE GENERICO " . $parametri['ris_Descrizione'];
					$descrizioneCaso = "NON BLOCCANTE";
					$tipoCaso = 'OK';
					$flag = 1;
				}



				$sqlInsertAlr = "INSERT INTO casi(cas_IdRisorsa,cas_IdEvento,cas_DescrizioneEvento,cas_IdCaso,cas_DescrizioneCaso,cas_Flag,cas_Tipo) VALUES(:AlrIdRisorsa,:AlrIdEvento,:AlrDescrizioneEvento,:IdCaso,:DescrizioneCaso,:AlrFlag,:TipoCaso)";

				$sthInsertAlr = $conn_mes->prepare($sqlInsertAlr);
				$sthInsertAlr->execute([
					':AlrIdRisorsa' => $idRisorsa,
					':AlrIdEvento' => $idEvento,
					':AlrDescrizioneEvento' => $descrizioneEvento,
					':IdCaso' => $tipoCaso,
					':DescrizioneCaso' => $descrizioneCaso,
					':AlrFlag' => $flag,
					':TipoCaso' => $tipoCaso
				]);
			}

			// Predispongo la lista degli EVENTI NON BLOCCANTI gestibili lato SCADA (20 eventi)
			for ($i = 1; $i <= 20; $i++) {
				$doquery = true;
				$indice = "";

				if ($i < 10) {
					$indice = '0' . $i;
					$idEvento = 'Ev_' . $indice;
					$descrizioneEvento = "EVENTO NON BLOCCANTE " . $indice . " " . $parametri['ris_Descrizione'];
					$descrizioneCaso = "NON BLOCCANTE";
					$tipoCaso = 'OK';
					$flag = 0;
				} else if ($i >= 10 && $i <= 20) {
					$indice = $i;
					$idEvento = 'Ev_' . $indice;
					$descrizioneEvento = "EVENTO NON BLOCCANTE " . $indice . " " . $parametri['ris_Descrizione'];
					$descrizioneCaso = "NON BLOCCANTE";
					$tipoCaso = 'OK';
					$flag = 0;
				}




				if ($doquery) {
					$sqlInsertAlr = "INSERT INTO casi (cas_IdRisorsa, cas_IdEvento, cas_DescrizioneEvento, cas_IdCaso, cas_DescrizioneCaso, cas_Flag, cas_Tipo) VALUES (:AlrIdRisorsa, :AlrIdEvento, :AlrDescrizioneEvento, :IdCaso, :DescrizioneCaso, :AlrFlag, :TipoCaso)";
					$sthInsertAlr = $conn_mes->prepare($sqlInsertAlr);
					$sthInsertAlr->execute([
						':AlrIdRisorsa' => $idRisorsa,
						':AlrIdEvento' => $idEvento,
						':AlrDescrizioneEvento' => $descrizioneEvento,
						':IdCaso' => $tipoCaso,
						':DescrizioneCaso' => $descrizioneCaso,
						':AlrFlag' => $flag,
						':TipoCaso' => $tipoCaso
					]);
				}
			}

			// Predispongo la lista delle MISURE gestibili lato SCADA (10 misure)
			for ($i = 1; $i <= 20; $i++) {

				$indice = "";

				if ($i < 10) {
					$indice = '0' . $i;
					$idMisura = 'Mis_' . $indice;
					$descrizioneMisura = "MISURA " . $indice;
				} else if ($i >= 10 && $i <= 20) {
					$indice = $i;
					$idMisura = 'Mis_' . $indice;
					$descrizioneMisura = "MISURA " . $indice;
				}


				$sqlInsertMis = "INSERT INTO misure (mis_IdRisorsa, mis_IdMisura, mis_Descrizione, mis_Udm) VALUES(:MisIdRisorsa, :MisIdMisura, :MisDescrizione, :MisUdm)";
				$sthInsertMis = $conn_mes->prepare($sqlInsertMis);
				$sthInsertMis->execute([
					':MisIdRisorsa' => $idRisorsa,
					':MisIdMisura' => $idMisura,
					':MisDescrizione' => $descrizioneMisura,
					':MisUdm' => 'ND'
				]);
			}
		}
		$conn_mes->commit();
		die('OK');
	} catch (Throwable $t) {
		$conn_mes->rollBack();
		die("ERRORE: " . $t);
	}
}







?>
<!DOCTYPE html>
<html lang="it">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>PROSPECT40 | Gestione archivi</title>
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
							<h4 class="card-title m-2">MACCHINE</h4>
						</div>

						<div class="card-body">


							<div class="row">

								<div class="col-12">
									<div class="table-responsive pt-1">

										<table id="tabellaDati-risorse" class="table table-striped" style="width:100%"
											data-source="risorse.php?azione=mostra">
											<thead>
												<tr>
													<th>Nome macchina</th>
													<th>Linea di produzione</th>
													<th>Posizione</th>
													<th>T. teorico attr. (min)</th>
													<th>Reg. misure</th>
													<th>Ultima di linea</th>
													<th>Calcolo OEE</th>
													<th>Ordine caricato</th>
													<th>Stato macchina</th>
													<th>Allarme</th>
													<th>T. funz. tot. [h]</th>
													<th>Freq. manut. [h]</th>
													<th>Udm</th>
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

	<button type="button" id="nuova-risorsa" class="mdi mdi-button">NUOVA MACCHINA</button>


	<!-- Opup modale di modifica/inserimento prodotto-->
	<div class="modal fade" id="modal-risorsa" tabindex="-1" role="dialog" aria-labelledby="modal-risorsa-label"
		aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modal-risorsa-label">Nuova macchina</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form class="forms-sample" id="form-risorsa">

						<div class="row">
							<div class="col-6">
								<div class="form-group">
									<label for="ris_Descrizione">Nome macchina</label><span style='color:red'> *</span>
									<input type="text" class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="ris_Descrizione" id="ris_Descrizione" autocomplete="off">
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="ris_LineaProduzione">Linea di produzione</label><span style='color:red'> *</span>
									<select class="form-control form-control-sm dati-popup-modifica selectpicker" id="ris_LineaProduzione"
										name="ris_LineaProduzione">
										<?php
										$sth = $conn_mes->prepare("SELECT linee_produzione.* FROM linee_produzione", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
										$sth->execute();
										$linee = $sth->fetchAll(PDO::FETCH_ASSOC);

										foreach ($linee as $linea) {
											echo "<option value=" . $linea['lp_IdLinea'] . ">" . $linea['lp_Descrizione'] . "</option>";
										}
										?>
									</select>
								</div>
							</div>

							<div class="col-4">
								<div class="form-group">
									<label for="ris_Udm">Unità di misura di rif.</label><span style='color:red'> *</span>
									<select class="form-control form-control-sm dati-popup-modifica selectpicker" name="ris_Udm" id="ris_Udm">
										<?php
											$sth = $conn_mes->prepare("SELECT * FROM unita_misura", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
											$sth->execute();
											$trovate = $sth->fetchAll(PDO::FETCH_ASSOC);
											foreach($trovate as $udm) {
												echo "<option value='".$udm['um_IdRiga']."'>".$udm['um_Descrizione']."</option>";
											}
										?>
									</select>
								</div>
							</div>

							<div class="col-4">
								<div class="form-group">
									<label for="ris_Ordinamento">Posizione sulla linea</label><span style='color:red'> *</span>
									<input type="number" class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="ris_Ordinamento" id="ris_Ordinamento" autocomplete="off">
								</div>
							</div>
							
							<div class="col-4">
								<div class="form-group">
									<label for="ris_FattoreConteggi">Fattore conteggi</label><span style='color:red'> *</span>
									<input type="number" class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="ris_FattoreConteggi" id="ris_FattoreConteggi" autocomplete="off" value=1>
								</div>
							</div>
							
							<div class="col-4">
								<div class="form-group">
									<label for="ris_TTeoricoAttrezzaggio">T. teor. attr. (min)</label><span style='color:red'> *</span>
									<input type="number" class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="ris_TTeoricoAttrezzaggio" id="ris_TTeoricoAttrezzaggio" autocomplete="off">
								</div>
							</div>
							<div class="col-4">
								<div class="form-group">
									<label for="ris_OreFunzTotali">T. funz. totale [h]</label><span style='color:red'> *</span>
									<input type="number" class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="ris_OreFunzTotali" id="ris_OreFunzTotali" autocomplete="off">
								</div>
							</div>
							<div class="col-4">
								<div class="form-group">
									<label for="ris_OreFunz_FreqMan">Freq. manutenzione [h]</label><span style='color:red'> *</span>
									<input type="number" class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="ris_OreFunz_FreqMan" id="ris_OreFunz_FreqMan" autocomplete="off">
									<p style="font-size: 11px;">0 = nessuna manut. programmata</p>
								</div>
							</div>


							<div class="col-4">
								<div class="form-check pt-4">
									<input id="ris_AbiMisure" type="checkbox">
									<label for="ris_AbiMisure" style="font-weight: normal;">Registrazione misure</label>
								</div>
							</div>
							<div class="col-4">
								<div class="form-check pt-4">
									<input id="ris_FlagUltima" type="checkbox">
									<label for="ris_FlagUltima" style="font-weight: normal;">Ultima macchina </label>
								</div>
							</div>
							<div class="col-4">
								<div class="form-check pt-4">
									<input id="ris_FlagDisabilitaOEE" type="checkbox">
									<label for="ris_FlagDisabilitaOEE" style="font-weight: normal;">Disabilita calcolo OEE</label>
								</div>
							</div>
						</div>

						<input type="hidden" id="ris_IdRisorsa_Aux" name="ris_IdRisorsa_Aux" value="">
						<input type="hidden" id="azione" name="azione" value="nuovo">

					</form>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-success" id="salva-risorsa">Salva</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
				</div>

			</div>
		</div>
	</div>


	<?php include("inc_js.php") ?>
	<script src="../js/risorse.js"></script>

</body>

</html>