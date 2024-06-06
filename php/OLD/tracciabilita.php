<?php
// in che pagina siamo
$pagina = 'tracciabilita';

include("../inc/conn.php");


// debug($_SESSION['utente'],'Utente');

// : VISUALIZZAZIONE ELENCO COMMESSE DI PRODUZIONE PRESENTI
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'mostra-ordini') {

	// estraggo la lista
	$sth = $conn_mes->prepare("SELECT ordini_produzione.*, prodotti.prd_Descrizione, stati_ordine.so_Descrizione, rientro_linea_produzione.rlp_DataInizio, rientro_linea_produzione.rlp_OraInizio, rientro_linea_produzione.rlp_DataFine, rientro_linea_produzione.rlp_OraFine, um_Sigla
									FROM ordini_produzione
									LEFT JOIN rientro_linea_produzione ON ordini_produzione.op_IdProduzione = rientro_linea_produzione.rlp_IdProduzione
									LEFT JOIN stati_ordine ON ordini_produzione.op_Stato = stati_ordine.so_IdStatoOrdine
									LEFT JOIN prodotti ON ordini_produzione.op_Prodotto = prodotti.prd_IdProdotto
									LEFT JOIN unita_misura ON ordini_produzione.op_Udm = unita_misura.um_IdRiga
									WHERE ordini_produzione.op_Stato >= 4 AND ordini_produzione.op_Stato != 6
									ORDER BY op_OraOrdine DESC, op_Dataordine DESC", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
	$sth->execute();



	$output = [];

	if ($sth->rowCount() > 0) {

		$righe = $sth->fetchAll(PDO::FETCH_ASSOC);

		foreach ($righe as $riga) {
			$do = new DateTime($riga['rlp_DataInizio']);
			$oo = strtotime($riga['rlp_OraInizio']);
			$dp = new DateTime($riga['rlp_DataFine']);
			$op = strtotime($riga['rlp_OraFine']);

			//Preparo i dati da visualizzare
			$output[] = [

				'IdProduzione' => ($riga['op_Riferimento'] != "" ? $riga['op_IdProduzione'] . " (" . $riga['op_Riferimento'] . ')' : $riga['op_IdProduzione']),
				'Prodotto' => $riga['prd_Descrizione'],
				'QtaRichiesta' => $riga['op_QtaRichiesta'] . " " . $riga['um_Sigla'],
				'DataOraInizio' => $do->format('d/m/Y') . " - " . date('H:i', $oo),
				'DataOraFine' => $dp->format('d/m/Y') . " - " . date('H:i', $op),
				'Lotto' => $riga['op_Lotto'],
				'IdProduzioneAux' => $riga['op_IdProduzione']
			];
		}

		die(json_encode($output));
	} else {
		die('NO_ROWS');
	}
}


// : VISUALIZZAZIONE ELENCO COMMESSE DI PRODUZIONE PRESENTI
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'mostra-componenti') {

	// estraggo la lista
	$sth = $conn_mes->prepare("SELECT DISTINCT prodotti.*, categoria_prodotti.cat_Descrizione, sottocategoria_prodotti.sot_Descrizione, lotti.lot_Lotto
									FROM prodotti
									LEFT JOIN lotti ON prodotti.prd_IdProdotto = lotti.lot_Componente
									LEFT JOIN categoria_prodotti ON prodotti.prd_Categoria = categoria_prodotti.cat_IdCategoria
									LEFT JOIN sottocategoria_prodotti ON prodotti.prd_Sottocategoria = sottocategoria_prodotti.sot_IdSottocategoria
									WHERE prodotti.prd_Tipo <> 'F'
									ORDER BY prd_Descrizione ASC, lot_Lotto ASC", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
	$sth->execute();
	$output = [];

	if ($sth->rowCount() > 0) {

		$righe = $sth->fetchAll(PDO::FETCH_ASSOC);

		foreach ($righe as $riga) {

			//Preparo i dati da visualizzare
			$output[] = [
				'IdProdotto' => $riga['prd_IdProdotto'],
				'DescrizioneProdotto' => $riga['prd_Descrizione'],
				'CategoriaProdotto' => $riga['cat_Descrizione'],
				'SottocategoriaProdotto' => $riga['sot_Descrizione'],
				'Lotto' => $riga['lot_Lotto']
			];
		}

		die(json_encode($output));
	} else {
		die('NO_ROWS');
	}
}


// : VISUALIZZAZIONE ELENCO COMMESSE DI PRODUZIONE PRESENTI
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'mostra-dettaglio-ordini' && !empty($_REQUEST['idProduzione'])) {

	// estraggo la lista
	$sth = $conn_mes->prepare("SELECT lotti.lot_IdProduzione, lotti.lot_Componente, prodotti.prd_Descrizione, lotti.lot_Lotto, SUM(lotti.lot_Qta) AS TotaleQtaLotto, unita_misura.um_Sigla
									FROM lotti
									LEFT JOIN componenti ON componenti.cmp_Componente = lotti.lot_Componente AND componenti.cmp_IdProduzione = lotti.lot_IdProduzione
									LEFT JOIN prodotti ON lotti.lot_Componente = prodotti.prd_IdProdotto
									LEFT JOIN unita_misura ON prodotti.prd_UnitaMisura = unita_misura.um_IdRiga
									WHERE lotti.lot_IdProduzione = :IdProduzione
									GROUP BY lotti.lot_IdProduzione, lotti.lot_Componente, prodotti.prd_Descrizione, lotti.lot_Lotto, unita_misura.um_Sigla
									ORDER BY prodotti.prd_Descrizione ASC", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
	$sth->execute([':IdProduzione' => $_REQUEST['idProduzione']]);
	$output = [];

	$auxIndiceRiga = 0;
	$auxMemCodiceComponente = "";

	if ($sth->rowCount() > 0) {


		$righe = $sth->fetchAll(PDO::FETCH_ASSOC);

		foreach ($righe as $riga) {

			if ($auxMemCodiceComponente != $riga['lot_Componente']) {
				$auxMemCodiceComponente = $riga['lot_Componente'];

				if ($auxIndiceRiga == 0) {
					$auxIndiceRiga = 1;
				} else {
					$auxIndiceRiga = 0;
				}
			}

			//Preparo i dati da visualizzare
			$output[] = [
				'IdProduzione' => $riga['lot_IdProduzione'],
				'CodiceComponente' => $riga['lot_Componente'],
				'DescrizioneComponente' => $riga['prd_Descrizione'],
				'LottoComponente' => $riga['lot_Lotto'],
				'QtaUsata' => $riga['TotaleQtaLotto'] . " " . $riga['um_Sigla'],
				'IndiceRiga' => $auxIndiceRiga
			];
		}

		die(json_encode($output));
	} else {
		die('NO_ROWS');
	}
}


// : VISUALIZZAZIONE ELENCO COMMESSE DI PRODUZIONE PRESENTI
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'mostra-dettaglio-componenti' && !empty($_REQUEST['idComponente'])) {

	// estraggo la lista
	$sth = $conn_mes->prepare("SELECT ordini_produzione.*, rientro_linea_produzione.rlp_DataInizio, rientro_linea_produzione.rlp_OraInizio, rientro_linea_produzione.rlp_DataFine, rientro_linea_produzione.rlp_OraFine, prodotti.prd_Descrizione, unita_misura.um_Sigla
									FROM ordini_produzione
									LEFT JOIN rientro_linea_produzione ON ordini_produzione.op_IdProduzione = rientro_linea_produzione.rlp_IdProduzione
									LEFT JOIN prodotti ON ordini_produzione.op_Prodotto = prodotti.prd_IdProdotto
									LEFT JOIN unita_misura ON prodotti.prd_UnitaMisura = unita_misura.um_IdRiga
									WHERE ordini_produzione.op_IdProduzione IN

									(SELECT lotti.lot_IdProduzione
									FROM lotti
									WHERE lotti.lot_Componente = :IdComponente AND lotti.lot_Lotto = :Lotto)", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
	$sth->execute([':IdComponente' => $_REQUEST['idComponente'], ':Lotto' => $_REQUEST['lotto']]);
	$output = [];

	$auxIndiceRiga = 0;
	$auxMemCodiceComponente = "";

	if ($sth->rowCount() > 0) {


		$righe = $sth->fetchAll(PDO::FETCH_ASSOC);

		foreach ($righe as $riga) {

			if ($auxMemCodiceComponente != $riga['op_IdProduzione']) {
				$auxMemCodiceComponente = $riga['op_IdProduzione'];

				if ($auxIndiceRiga == 0) {
					$auxIndiceRiga = 1;
				} else {
					$auxIndiceRiga = 0;
				}
			}

			$do = new DateTime($riga['rlp_DataInizio']);
			$oo = strtotime($riga['rlp_OraInizio']);
			$dp = new DateTime($riga['rlp_DataFine']);
			$op = strtotime($riga['rlp_OraFine']);

			//Preparo i dati da visualizzare
			$output[] = [
				'IdProduzione' => ($riga['op_Riferimento'] != "" ? $riga['op_IdProduzione'] . " (" . $riga['op_Riferimento'] . ')' : $riga['op_IdProduzione']),
				'Prodotto' => $riga['prd_Descrizione'],
				'QtaRichiesta' => $riga['op_QtaRichiesta'] . " " . $riga['um_Sigla'],
				'DataOraInizio' => $do->format('d/m/Y') . " - " . date('H:i', $oo),
				'DataOraFine' => $dp->format('d/m/Y') . " - " . date('H:i', $op),
				'Lotto' => $riga['op_Lotto'],
				'IndiceRiga' => $auxIndiceRiga
			];
		}

		die(json_encode($output));
	} else {
		die('NO_ROWS');
	}
}



?>
<!DOCTYPE html>
<html lang="it">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>PROSPECT40 | Gestione tracciabilità</title>
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
							<h4 class="card-title m-2">TRACCIABILIT&Aacute;</h4>
						</div>
						<div class="card-body">



							<ul class="nav nav-tabs pt-2" id="tab-tracciabilita" role="tablist">
								<li class="nav-item text-center" style="width: calc(100% / 2);">
									<a aria-controls="tracciabilita-ordini" aria-selected="true" class="nav-link rounded-2 show"
										data-toggle="tab" href="#tracciabilita-ordini" id="tab-tracciabilita-ordini"
										role="tab"><b>TRACCIABILIT&Agrave; COMMESSE </b></a>
								</li>
								<li class="nav-item text-center" style="width: calc(100% / 2);">
									<a aria-controls="tracciabilita-componenti" aria-selected="true" class="nav-link rounded-2"
										data-toggle="tab" href="#tracciabilita-componenti" id="tab-tracciabilita-componenti"
										role="tab"><b>TRACCIABILIT&Agrave; MATERIE PRIME</b></a>
								</li>
							</ul>

							<div class="tab-content tab-tracciabilita-ordini">

								<!-- Tab LOGISTICA - MONITORAGGIO STATO COMMESSE -->
								<div aria-labelledby="tab-tracciabilita-ordini" class="tab-pane show" id="tracciabilita-ordini"
									role="tabpanel">

									<!-- Visualizzazione distinte prodotto presenti e dati di quella selezionata -->
									<div class="row pt-3">

										<div class="col-12">

											<h6>ELENCO COMMESSE</h6>
											<div class="table-responsive pt-2">

												<table id="tabellaDati-tracciabilita-ordini" class="table table-striped" style="width:100%">
													<thead>
														<tr>
															<th>Codice commessa (Rif.)</th>
															<th>Prodotto </th>
															<th>Qta richiesta</th>
															<th>Data / ora inizio</th>
															<th>Data / ora fine</th>
															<th>Lotto</th>
															<th>Codice commessa aux</th>
														</tr>
													</thead>
													<tbody>
													</tbody>

												</table>

											</div>
										</div>

										<div class="col-12 pt-4">

											<h6>DETTAGLIO COMPONENTI TRACCIATI</h6>
											<div class="table-responsive pt-2">

												<table id="tabellaDati-tracciabilita-ordini-dettaglio" class="table table-striped"
													style="width:100%">
													<thead>
														<tr>
															<!-- <th>Codice commessa</th> -->
															<th>Codice componente </th>
															<th>Descrizione componente</th>
															<th>Lotto</th>
															<th>Qta usata</th>
															<th>Aux</th>
														</tr>
													</thead>
													<tbody>
													</tbody>

												</table>

											</div>
										</div>

									</div>

								</div>

								<!-- Tab LOGISTICA - MONITORAGGIO STATO COMMESSE -->
								<div aria-labelledby="tab-tracciabilita-componenti" class="tab-pane show" id="tracciabilita-componenti"
									role="tabpanel">

									<!-- Visualizzazione distinte prodotto presenti e dati di quella selezionata -->
									<div class="row pt-3">

										<div class="col-12">

											<h6>ELENCO COMPONENTI</h6>
											<div class="table-responsive pt-2">

												<table id="tabellaDati-tracciabilita-componenti" class="table table-striped" style="width:100%">
													<thead>
														<tr>
															<th>Codice componente</th>
															<th>Descrizione </th>
															<th>Categoria</th>
															<th>Sottocategoria</th>
															<th>Lotto</th>
														</tr>
													</thead>
													<tbody>
													</tbody>
												</table>
											</div>
										</div>

										<div class="col-12 pt-4">

											<h6>DETTAGLIO COMMESSE TRACCIATE</h6>
											<div class="table-responsive pt-2">

												<table id="tabellaDati-tracciabilita-componenti-dettaglio" class="table table-striped"
													style="width:100%">
													<thead>
														<tr>
															<th>Codice commessa (Rif.)</th>
															<th>Prodotto </th>
															<th>Qta richiesta</th>
															<th>Data / ora inizio</th>
															<th>Data / ora fine</th>
															<th>Lotto</th>
															<th>Aux</th>
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
					</div>
				</div>

				<?php include("inc_footer.php") ?>

			</div>

		</div>

	</div>



	<!-- Opup modale di modifica/inserimento prodotto-->
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
					<form class="forms-sample" id="form-ordine-produzione">

						<div class="row">
							<div class="col-12">
								<div class="form-group">
									<label for="op_IdProduzione">Codice commessa (Rif.)</label>
									<input type="text" class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="op_IdProduzione" id="op_IdProduzione" placeholder="Id ordine produzione">
								</div>
							</div>
							<div class="col-12">
								<div class="form-group">
									<label for="op_Descrizione">Descrizione</label>
									<input type="text" class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="op_Descrizione" id="op_Descrizione" placeholder="Descrizione">
								</div>
							</div>
							<div class="col-12">
								<div class="form-group">
									<label for="op_Prodotto">Prodotto</label>
									<select class="form-control form-control-sm dati-popup-modifica" id="op_Prodotto" name="op_Prodotto"
										required>
										<?php
										$sth = $conn_mes->prepare("SELECT prodotti.* FROM prodotti WHERE prodotti.prd_Tipo != 'MP' ORDER BY prodotti.prd_Descrizione ASC", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
										$sth->execute();
										$linee = $sth->fetchAll(PDO::FETCH_ASSOC);

										foreach ($linee as $linea) {
											echo "<option value='" . $linea['prd_IdProdotto'] . "'>" . $linea['prd_Descrizione'] . "</option>";
										}
										?>
									</select>
								</div>
							</div>

							<div class="col-6">
								<div class="form-group">
									<label for="op_QtaRichiesta">Qta richiesta</label>
									<input type="number" class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="op_QtaRichiesta" id="op_QtaRichiesta" placeholder="Qta richiesta">
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="op_Lotto">Lotto</label>
									<input type="text" class="form-control form-control-sm dati-popup-modifica" name="op_Lotto"
										id="op_Lotto" placeholder="Id ordine produzione">
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="op_DataOrdine">Data compilazione</label>
									<input type="date" class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="op_DataOrdine" id="op_DataOrdine" placeholder="Data compilazione">
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="op_OraOrdine">Ora compilazione</label>
									<input type="time" class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="op_OraOrdine" id="op_OraOrdine" placeholder="Ora compilazione">
								</div>
							</div>


							<div class="col-6">
								<div class="form-group">
									<label for="op_DataProduzione">Data pianificazione</label>
									<input type="date" class="form-control form-control-sm dati-popup-modifica" name="op_DataProduzione"
										id="op_DataProduzione" placeholder="Data pianificazione">
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="op_OraProduzione">Ora pianificazione</label>
									<input type="time" class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="op_OraProduzione" id="op_OraProduzione" placeholder="Ora pianificazione">
								</div>
							</div>

							<div class="col-12">
								<div class="form-group">
									<label for="op_NoteProduzione">Note produzione</label>
									<input type="text" class="form-control form-control-sm dati-popup-modifica" name="op_NoteProduzione"
										id="op_NoteProduzione" placeholder="Descrizione">
								</div>
							</div>
							<div class="col-4">
								<div class="form-group">
									<label for="op_Stato">Stato</label>
									<select class="form-control form-control-sm dati-popup-modifica" id="op_Stato" name="op_Stato"
										required>
										<?php
										$sth = $conn_mes->prepare("SELECT stati_ordine.* FROM stati_ordine", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
										$sth->execute();
										$linee = $sth->fetchAll(PDO::FETCH_ASSOC);

										foreach ($linee as $linea) {
											echo "<option value=" . $linea['so_IdStatoOrdine'] . ">" . $linea['so_Descrizione'] . "</option>";
										}
										?>
									</select>
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


	<?php include("inc_js.php") ?>
	<script src="../js/tracciabilita.js"></script>

</body>

</html>