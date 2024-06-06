<?php
// in che pagina siamo
$pagina = 'sinotticolinee';

include('../inc/conn.php');

if (!empty($_REQUEST['azione'])) {
	if ($_REQUEST['azione'] == 'aggiorna') {
		$output = [
			'linee' => [],
			'risorse' => [],
		];
		$sth = $conn_mes->prepare(
			"SELECT * FROM linee_produzione AS LP
			LEFT JOIN rientro_linea_produzione AS RLP ON RLP.rlp_IdLinea = LP.lp_IdLinea
			JOIN ordini_produzione AS OP ON OP.op_idProduzione = RLP.rlp_IdProduzione AND op_Stato = 4
			LEFT JOIN prodotti AS P ON OP.op_Prodotto = P.prd_IdProdotto
			LEFT JOIN unita_misura AS UM ON OP.op_Udm = UM.um_IdRiga"
		);
		$sth->execute();
		$linee = $sth->fetchAll();


		foreach ($linee as $linea) {
			$sth = $conn_mes->prepare(
				"SELECT RP.rp_QtaProdotta FROM risorsa_produzione AS RP
				LEFT JOIN risorse AS R ON RP.rp_IdRisorsa = R.ris_IdRisorsa
				WHERE RP.rp_IdProduzione = :IdProduzione AND R.ris_FlagUltima = 1"
			);
			$sth->execute([':IdProduzione' => $linea['op_IdProduzione']]);
			$rp = $sth->fetch();

			$qta = 0;
			if ($rp) {
				$qta = $rp['rp_QtaProdotta'];
			}


			$output['linee'][] = [
				'lp_IdLinea' => $linea['lp_IdLinea'],
				'op_IdProduzione' => ($linea['op_Riferimento'] != '' ? $linea['op_IdProduzione'] . " (" . $linea['op_Riferimento'] . "]" : $linea['op_IdProduzione']),
				'prd_Descrizione' => $linea['prd_Descrizione'],
				'op_Lotto' => $linea['op_Lotto'],
				'op_QtaRichiesta' => $linea['op_QtaRichiesta'] . ' [' . $linea['um_Sigla'] . ']',
				'op_QtaProdotta' => $qta . ' [' . $linea['um_Sigla'] . ']',
			];
		}


		// ricavo informazioni sulle risorse definite
		$sth = $conn_mes->prepare(
			"SELECT * FROM risorse AS R
			LEFT JOIN ordini_produzione AS ODP ON R.ris_IdProduzione = ODP.op_IdProduzione
			LEFT JOIN prodotti AS P ON ODP.op_Prodotto = P.prd_IdProdotto"
		);
		$sth->execute();
		$righe = $sth->fetchAll(PDO::FETCH_ASSOC);

		foreach ($righe as $riga) {

			// preparo i dati da visualizzare nell'array
			$output['risorse'][] = [
				'IdRisorsa' => $riga['ris_IdRisorsa'],
				'DescrizioneRisorsa' => $riga['ris_Descrizione'],
				'Avaria' => $riga['ris_Avaria_Man'] || $riga['ris_Avaria_Scada'],
				'Attrezzaggio' => $riga['ris_Attrezzaggio_Man'] || $riga['ris_Attrezzaggio_Scada'],
				'Run' => $riga['ris_Run_Man'] || $riga['ris_Run_Scada'],
				'PausaPrevistaMan' => $riga['ris_PausaPrevista_Man'] || $riga['ris_PausaPrevista_Scada'],
				'StatoOrdine' => $riga['ris_StatoOrdine'],
				'IdProduzioneCaricata' => $riga['ris_IdProduzione']
			];
		}

		die(json_encode($output));
	}

	if ($_REQUEST['azione'] == 'dettaglio-risorsa') {
		$now = new DateTime();

		unset($_REQUEST['azione']);
		$sth = $conn_mes->prepare(
			"SELECT * FROM risorse AS R
			LEFT JOIN risorsa_produzione AS RP ON R.ris_IdRisorsa = RP.rp_IdRisorsa
			LEFT JOIN ordini_produzione AS ODP ON ODP.op_IdProduzione = R.ris_IdProduzione
			LEFT JOIN unita_misura AS UM ON UM.um_IdRiga = ODP.op_Udm
			LEFT JOIN velocita_teoriche AS VT ON VT.vel_IdProdotto = ODP.op_Prodotto AND VT.vel_IdLineaProduzione = R.ris_LineaProduzione
			WHERE R.ris_IdRisorsa = :idRisorsa"
		);
		$sth->execute($_REQUEST);
		$risorsa = $sth->fetch();
		$output = [];
		if ($risorsa) {

			if (isset($risorsa['rp_DataInizio'])) {


				$dataInizio = new DateTime($risorsa['rp_DataInizio'] . ' ' . $risorsa['rp_OraInizio']);
				$tempoTotaleSec = $now->getTimestamp() - $dataInizio->getTimestamp();

				$sth = $conn_mes->prepare(
					"SELECT SUM(DATEDIFF(
						SECOND,
						CONCAT(rdt_DataInizio,'T',rdt_OraInizio),
						IIF(
							rdt_DataFine IS NOT NULL,
							(CONCAT(rdt_DataFine,'T',rdt_OraFine)),
							GETDATE()
						)
		 			)) AS somma FROM risorsa_downtime
					WHERE rdt_IdRisorsa = :IdRisorsa AND rdt_IdProduzione = :IdProduzione"
				);
				$sth->execute([
					'IdRisorsa' => $risorsa['ris_IdRisorsa'],
					'IdProduzione' => $risorsa['ris_IdProduzione'],
				]);
				$sommaDtSec = $sth->fetch()['somma'];


				$sth = $conn_mes->prepare(
					"SELECT SUM(DATEDIFF(
						SECOND,
						(CONCAT(ac_DataInizio,'T',ac_OraInizio)),
						IIF(
							ac_DataFine IS NOT NULL,
							(CONCAT(ac_DataFine,'T',ac_OraFine)),
							GETDATE()
						)
					)) AS somma FROM attivita_casi
					WHERE ac_IdRisorsa = :IdRisorsa AND ac_IdProduzione = :IdProduzione AND ac_IdCaso = 'AT'"
				);
				$sth->execute([
					'IdRisorsa' => $risorsa['ris_IdRisorsa'],
					'IdProduzione' => $risorsa['ris_IdProduzione'],
				]);
				$sommaAttSec = $sth->fetch()['somma'];

				$velTeoricaSec = $risorsa['vel_VelocitaTeoricaLinea'] / 3600;

				$conformi = floatval($risorsa['rp_QtaProdotta'] - $risorsa['rp_QtaScarti']);
				$prodottiTeorici = floatval($tempoTotaleSec * $velTeoricaSec);
				$oee = round(($conformi / $prodottiTeorici) * 100, 2);

				$velTrendSec = round($risorsa['rp_QtaProdotta'] / $tempoTotaleSec, 2);

				$rimanente = $risorsa['op_QtaDaProdurre'] - $conformi;

				if ($velTrendSec != 0) {
					$tempoRimastoSec = $rimanente / $velTrendSec;
				} else {
					$tempoRimastoSec = $rimanente / $velTeoricaSec;
				}

				$dataFine = (new DateTime())->setTimestamp($now->getTimestamp() + $tempoRimastoSec);

				$output['risorsa'] = [
					'ris_IdRisorsa' => $risorsa['ris_IdRisorsa'],
					'ris_IdProduzione' => $risorsa['ris_IdProduzione'],
					'op_Riferimento' => $risorsa['op_Riferimento'],
					'ris_StatoOrdine' => $risorsa['ris_StatoOrdine'],
					'ris_DataInizio' => $dataInizio->format('d/m/Y H:i'),
					'ris_DataFineTeorica' => $dataFine->format('d/m/Y H:i'),
					'op_QtaDaProdurre' => $risorsa['op_QtaDaProdurre'],
					'rp_QtaProdotta' => $risorsa['rp_QtaProdotta'],
					'vel_VelocitaTrend' => round($velTrendSec * 3600, 2),
					'rp_TTotale' =>  round($tempoTotaleSec / 60) . ' [min]',
					'rp_TAttrezzaggio' => round($sommaAttSec / 60) . ' [min]',
					'rp_Downtime' => round($sommaDtSec / 60) . ' [min]',
				];
			} else {

				$dataInizio = new DateTime($risorsa['op_DataProduzione'] . ' ' . $risorsa['op_OraProduzione']);
				$dataFine = new DateTime($risorsa['op_DataFineTeorica'] . ' ' . $risorsa['op_OraFineTeorica']);

				$output['risorsa'] = [
					'ris_IdRisorsa' => $risorsa['ris_IdRisorsa'],
					'ris_IdProduzione' => $risorsa['ris_IdProduzione'],
					'op_Riferimento' => $risorsa['op_Riferimento'],
					'ris_StatoOrdine' => $risorsa['ris_StatoOrdine'],
					'ris_DataInizio' => $dataInizio->format('d/m/Y H:i'),
					'ris_DataFineTeorica' => $dataFine->format('d/m/Y H:i'),
					'op_QtaDaProdurre' => '0',
					'rp_QtaProdotta' => '0',
					'vel_VelocitaTrend' => '0',
					'rp_TTotale' => '0',
					'rp_TAttrezzaggio' => '0',
					'rp_Downtime' => '0',
				];
			}
		}

		$sth = $conn_mes->prepare(
			"SELECT mis_Descrizione, mis_ValoreIstantaneo, mis_Udm FROM misure
			WHERE mis_IdRisorsa = :idRisorsa AND mis_AbiLetturaIstantanea = 1"
		);
		$sth->execute($_REQUEST);
		$output['misure'] = $sth->fetchAll();

		die(json_encode($output));
	}
}

?>
<!DOCTYPE html>
<html lang="it">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>PROSPECT40 | Dashboard linee</title>
	<?php include("inc_css.php") ?>
</head>

<body>

	<div class="container-scroller">

		<?php include("inc_testata.php") ?>

		<div class="container-fluid page-body-wrapper">

			<div class="main-panel">

				<div class="content-wrapper">

					<div class="card">

						<div class="card-body" id="card-sinottico-linee">
							<?php
							$sth = $conn_mes->prepare(
								"SELECT * FROM linee_produzione"
							);
							$sth->execute();
							$linee = $sth->fetchAll();

							foreach ($linee as $linea) {
							?>
								<div class="row">
									<div class="col-12 intestazione-linea mt-1 mb-1">
										<b><?= $linea['lp_Descrizione'] ?></b>
										<div class="dettagli-lavoro pt-1 d-flex flex-column" id="<?= $linea['lp_IdLinea'] ?>">

										</div>
									</div>
								</div>
								<div class="row pt-2 mb-3">
									<?php
									$sth = $conn_mes->prepare(
										"SELECT * FROM risorse
										WHERE ris_LineaProduzione = '" . $linea['lp_IdLinea'] . "'"
									);
									$sth->execute();
									$risorse = $sth->fetchAll();

									foreach ($risorse as $risorsa) {
										$immagine = file_exists('../images/' . $risorsa['ris_ImgSinottico'] . '.png') ? strtolower($risorsa['ris_ImgSinottico'] . '.png') : 'default.png';
									?>
										<div class="riquadro-risorsa-sinottico col-2 pt-3 d-flex flex-column" id="<?= $risorsa['ris_IdRisorsa'] ?>" style="color: white;">
											<img class="text-center" src="../images/<?= $immagine ?>" alt="" style='align-self:center;max-width: 40%;'>
											<hr class="stato-macchina pb-1" style="width: 100%;">
											<div class="mb-1"><?= strtoupper($risorsa['ris_Descrizione']) ?></div>
											<div class="sin-ordine-risorsa">
												<span>Commessa: </span>
												<span class="ml-2" id="ris_IdProduzione"></span>
											</div>
											<div class="sin-stato-ordine-risorsa">
												<span>Stato commessa: </span>
												<span class="ml-2" id="ris_StatoOrdine"></span>
											</div>
										</div>
									<?php
									}
									?>
								</div>
								<hr>
							<?php
							}

							?>
						</div>
					</div>
				</div>

				<?php include("inc_footer.php") ?>

			</div>

		</div>

	</div>


	<!-- Opup modale di DETTAGLIO RISORSA-->
	<div class="modal fade" id="modal-dettagli-risorsa" tabindex="-1" role="dialog" aria-labelledby="modal-dettagli-risorsa-label" aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content" id="contenuto-dettagli-risorsa-sinottico">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modal-dettagli-ordine-label">DETTAGLIO MACCHINA</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span class="close-dettagli-risorsa-sinottico" aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">

					<div class="row">

						<div class="col-md-6">

							<div class="row">

								<div class="col-6">
									<div class='blocco-misura'><span class='nome-dettaglio'>COMMESSA: </span></div>
								</div>
								<div class="col-6">
									<div class='blocco-misura'><span class='valore-dettaglio' id="ris_IdProduzione"></span></div>
								</div>

								<div class="col-6">
									<div class='blocco-misura'><span class='nome-dettaglio'>RIFERIMENTO: </span></div>
								</div>
								<div class="col-6">
									<div class='blocco-misura'><span class='valore-dettaglio' id="op_Riferimento"></span></div>
								</div>

								<div class="col-6 pt-1">
									<div class='blocco-misura'><span class='nome-dettaglio'>STATO COMMESSA: </span></div>
								</div>

								<div class="col-6 pt-1">
									<div class='blocco-misura'><span class='valore-dettaglio' id="ris_StatoOrdine"></span></div>
								</div>
							</div>

							<div class="row pt-3">

								<div class="col-6">
									<div class='blocco-misura'><span class='nome-dettaglio'>DATA INIZIO: </span></div>
								</div>

								<div class="col-6">
									<div class='blocco-misura'><span class='valore-dettaglio' id="ris_DataInizio"></span></div>
								</div>


								<div class="col-6 pt-1">
									<div class='blocco-misura'><span class='nome-dettaglio'>DATA FINE PREVISTA: </span></div>
								</div>

								<div class="col-6 pt-1">
									<div class='blocco-misura'><span class='valore-dettaglio' id="ris_DataFineTeorica"></span></div>
								</div>
							</div>
							<div class="row pt-3">

								<div class="col-6">
									<div class='blocco-misura'><span class='nome-dettaglio'>QTA RICHIESTA: </span></div>
								</div>

								<div class="col-6">
									<div class='blocco-misura'><span class='valore-dettaglio' id="op_QtaDaProdurre"></span></div>
								</div>

								<div class="col-6 pt-1">
									<div class='blocco-misura'><span class='nome-dettaglio'>QTA PRODOTTA: </span></div>
								</div>

								<div class="col-6 pt-1">
									<div class='blocco-misura'><span class='valore-dettaglio' id="rp_QtaProdotta"></span></div>
								</div>

								<div class="col-6 pt-1">
									<div class='blocco-misura'><span class='nome-dettaglio'>VELOCITA' ATTUALE: </span></div>
								</div>

								<div class="col-6 pt-1">
									<div class='blocco-misura'><span class='valore-dettaglio' id="vel_VelocitaTrend"></span></div>
								</div>

							</div>
							<div class="row pt-3">

								<div class="col-6">
									<div class='blocco-misura'><span class='nome-dettaglio'>T. TOTALE: </span></div>
								</div>

								<div class="col-6">
									<div class='blocco-misura'><span class='valore-dettaglio' id="rp_TTotale"></span></div>
								</div>

								<div class="col-6 pt-1">
									<div class='blocco-misura'><span class='nome-dettaglio'>T. ATTREZZAGGIO: </span></div>
								</div>

								<div class="col-6 pt-1">
									<div class='blocco-misura'><span class='valore-dettaglio' id="rp_TAttrezzaggio"></span></div>
								</div>

								<div class="col-6 pt-1">
									<div class='blocco-misura'><span class='nome-dettaglio'>DOWNTIME: </span></div>
								</div>

								<div class="col-6 pt-1">
									<div class='blocco-misura'><span class='valore-dettaglio' id="rp_Downtime"></span></div>
								</div>

							</div>
						</div>
						<div class="col-md-6">

							<div class="row dettagli-risorsa-misure ml-2">



							</div>
						</div>
					</div>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Chiudi</button>
				</div>

			</div>
		</div>
	</div>


	<?php include("inc_js.php") ?>
	<script src="../js/sinotticolinee.js"></script>


</body>

</html>