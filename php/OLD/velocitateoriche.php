<?php
// in che pagina siamo
$pagina = 'velocitateoriche';

include("../inc/conn.php");


// debug($_SESSION['utente'],'Utente');

// VELOCITA TEORICHE LINEA: VISUALIZZAZIONE CATEGORIE CENSITE
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'mostra-vt') {
	// estraggo la lista
	$sth = $conn_mes->prepare("SELECT prodotti.prd_Descrizione, linee_produzione.lp_Descrizione, velocita_teoriche.vel_VelocitaTeoricaLinea, velocita_teoriche.vel_IdRiga, unita_misura.um_Sigla
									FROM velocita_teoriche
									LEFT JOIN prodotti ON velocita_teoriche.vel_IdProdotto = prodotti.prd_IdProdotto
									LEFT JOIN linee_produzione ON velocita_teoriche.vel_IdLineaProduzione = linee_produzione.lp_IdLinea
									LEFT JOIN unita_misura ON velocita_teoriche.vel_Udm = unita_misura.um_IdRiga", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
	$sth->execute();
	$righe = $sth->fetchAll(PDO::FETCH_ASSOC);

	$output = [];

	foreach ($righe as $riga) {
		//Preparo i dati da visualizzare
		$output[] = [
			'IdProdotto' => $riga['prd_Descrizione'],
			'IdLineaProduzione' => $riga['lp_Descrizione'],
			'VelocitaTeorica' => $riga['vel_VelocitaTeoricaLinea'] . '  [' . $riga['um_Sigla'] . '/h]',
			'azioni' => '<div class="dropdown">
					<button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					<span class="mdi mdi-lead-pencil mdi-18px"></span>
					</button>
					<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
						<a class="dropdown-item modifica-entry-vt" data-id_riga="' . $riga['vel_IdRiga'] . '"><i class="mdi mdi-account-edit"></i> Modifica</a>
						<a class="dropdown-item cancella-entry-vt" data-id_riga="' . $riga['vel_IdRiga'] . '"><i class="mdi mdi-trash-can"></i> Elimina</a>
					</div>
				</div>'
		];
	}

	die(json_encode($output));
}



// VELOCITA TEORICHE LINEA: RECUPERO VALORI DELLA CATEGORIA SELEZIONATA
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'recupera-vt' && !empty($_REQUEST['idRiga'])) {
	// estraggo la lista
	$sth = $conn_mes->prepare("SELECT * FROM velocita_teoriche WHERE vel_IdRiga = :idRiga", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
	$sth->execute([':idRiga' => $_REQUEST['idRiga']]);
	$riga = $sth->fetch(PDO::FETCH_ASSOC);

	die(json_encode($riga));
}



// VELOCITA TEORICHE LINEA: GESTIONE CANCELLAZIONE
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'cancella-vt' && !empty($_REQUEST['idRiga'])) {
	// estraggo la lista
	$sth = $conn_mes->prepare("DELETE FROM velocita_teoriche WHERE vel_IdRiga = :idRiga");
	$sth->execute([':idRiga' => $_REQUEST['idRiga']]);

	die('OK');
}



// VELOCITA TEORICHE LINEA: GESTIONE SALVATAGGIO DATI DA POPUP MODIFICA/INSERIMENTO
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'salva-vt' && !empty($_REQUEST['data'])) {
	// recupero i parametri dal POST
	$parametri = [];
	parse_str($_REQUEST['data'], $parametri);

	// verifico che il codice cliente inserito, che mi funge da ID, non esista in altri record
	$sthRecuperoUdmProdotot = $conn_mes->prepare("SELECT prodotti.prd_UnitaMisura FROM prodotti WHERE prd_IdProdotto = :IdProdotto", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
	$sthRecuperoUdmProdotot->execute([':IdProdotto' => $parametri['vel_IdProdotto']]);
	$rigaUdmProdotto = $sthRecuperoUdmProdotot->fetch(PDO::FETCH_ASSOC);


	// se devo modificare
	if ($parametri['vel_azione'] == 'modifica') {

		$id_modifica = $parametri['vel_IdRiga'];

		// verifico che il codice cliente inserito, che mi funge da ID, non esista in altri record se non in quello che sto modificando
		$sth = $conn_mes->prepare("SELECT *
										FROM velocita_teoriche
										WHERE vel_IdProdotto = :IdProdotto
										AND vel_IdLineaProduzione = :IdLineaProduzione
										AND vel_IdRiga != :IdRiga", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
		$sth->execute([
			':IdProdotto' => $parametri['vel_IdProdotto'],
			':IdLineaProduzione' => $parametri['vel_IdLineaProduzione'],
			':IdRiga' => $id_modifica
		]);
		$trovati = $sth->fetch(PDO::FETCH_ASSOC);

		if (!$trovati) {
			$sqlUpdate = "UPDATE velocita_teoriche SET
							vel_IdProdotto = :IdProdotto,
							vel_IdLineaProduzione = :IdLineaProduzione,
							vel_VelocitaTeoricaLinea = :VelocitaTeorica,
							vel_Udm = :UnitaMisura
							WHERE vel_IdRiga = :IdRiga";

			$sthUpdate = $conn_mes->prepare($sqlUpdate);
			$sthUpdate->execute([
				':IdProdotto' => $parametri['vel_IdProdotto'],
				':IdLineaProduzione' => $parametri['vel_IdLineaProduzione'],
				':VelocitaTeorica' => $parametri['vel_VelocitaTeoricaLinea'],
				':UnitaMisura' => $rigaUdmProdotto['prd_UnitaMisura'],
				':IdRiga' => $id_modifica
			]);
		} else {
			die("La linea e il prodotto inseriti sono già presenti in tabella.");
		}
	} else // nuovo inserimento
	{

		// verifico che il codice cliente inserito, che mi funge da ID, non esista in altri record
		$sthSelect = $conn_mes->prepare("SELECT * FROM velocita_teoriche WHERE vel_IdProdotto = :IdProdotto AND vel_IdLineaProduzione = :IdLineaProduzione", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
		$sthSelect->execute([':IdProdotto' => $parametri['vel_IdProdotto'], ':IdLineaProduzione' => $parametri['vel_IdLineaProduzione']]);
		$trovati = $sthSelect->fetch(PDO::FETCH_ASSOC);

		if (!$trovati) {
			$sqlInsert = "INSERT INTO velocita_teoriche(vel_IdProdotto,vel_IdLineaProduzione,vel_VelocitaTeoricaLinea,vel_Udm) VALUES(:IdProdotto,:IdLineaProduzione,:VelocitaTeorica,:UnitaMisura)";

			$sthInsert = $conn_mes->prepare($sqlInsert);
			$sthInsert->execute([
				':IdProdotto' => $parametri['vel_IdProdotto'],
				':IdLineaProduzione' => $parametri['vel_IdLineaProduzione'],
				':VelocitaTeorica' => $parametri['vel_VelocitaTeoricaLinea'],
				':UnitaMisura' => $rigaUdmProdotto['prd_UnitaMisura'],
			]);
		} else {
			die("La linea e il prodotto inseriti sono già presenti in tabella.");
		}
	}

	die('OK');
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
							<h4 class="card-title">VELOCIT&Agrave; TEORICHE DI LINEA</h4>
						</div>
						<div class="card-body">

							<div class="row">

								<div class="col-12">

									<div class="table-responsive pt-1">

										<table id="tabellaDati-vt" class="table table-striped" style="width:100%"
											data-source="velocitateoriche.php?azione=mostra-vt">
											<thead>
												<tr>
													<th>Prodotto</th>
													<th>Linea</th>
													<th>Velocità teorica</th>
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
				<?php include("inc_footer.php") ?>

			</div>

		</div>

	</div>

	<button type="button" id="nuova-vt" class="mdi mdi-button">NUOVA RIGA</button>






	<!-- Popup modale di modifica/inserimento VEOCITA' TEORICA LINEA -->
	<div class="modal fade" id="modal-vt" tabindex="-1" role="dialog" aria-labelledby="modal-vt-label" aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modal-vt-label">NUOVA VELOCIT&Agrave; TEORICA DI LINEA</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form class="forms-sample" id="form-vt">

						<div class="row">
							<div class="col-12">
								<div class="form-group">
									<label for="vel_IdProdotto">Prodotto</label><span style='color:red'> *</span>
									<select class="form-control form-control-sm dati-popup-modifica selectpicker" name="vel_IdProdotto"
										id="vel_IdProdotto" data-live-search="true">
										<?php
										$sth = $conn_mes->prepare("SELECT *
																		FROM prodotti
																		WHERE prodotti.prd_Tipo = 'F' OR prodotti.prd_Tipo = 'S'
																		ORDER BY prodotti.prd_Descrizione ASC", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
										$sth->execute();
										$prodotti = $sth->fetchAll(PDO::FETCH_ASSOC);

										foreach ($prodotti as $prodotto) {
											echo "<option value='" . $prodotto['prd_IdProdotto'] . "'>" . $prodotto['prd_Descrizione'] . "</option>";
										}
										?>
									</select>
								</div>
							</div>
							<div class="col-12">
								<div class="form-group">
									<label for="vel_IdLineaProduzione">Linea</label><span style='color:red'> *</span>
									<select class="form-control form-control-sm dati-popup-modifica selectpicker"
										name="vel_IdLineaProduzione" id="vel_IdLineaProduzione" data-live-search="true">
										<?php
										$sth = $conn_mes->prepare("SELECT linee_produzione.*
																		FROM linee_produzione
																		WHERE linee_produzione.lp_IdLinea != 'lin_0P' AND linee_produzione.lp_IdLinea != 'lin_0X' ORDER BY linee_produzione.lp_Descrizione ASC", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
										$sth->execute();
										$linee = $sth->fetchAll(PDO::FETCH_ASSOC);

										foreach ($linee as $linea) {
											echo "<option value='" . $linea['lp_IdLinea'] . "'>" . strtoupper($linea['lp_Descrizione']) . "</option>";
										}
										?>
									</select>
								</div>
							</div>
							<div class="col-12">
								<div class="form-group">
									<label for="vel_VelocitaTeoricaLinea">Velocita teorica</label><span style='color:red'> *</span>
									<input type="number" class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="vel_VelocitaTeoricaLinea" id="vel_VelocitaTeoricaLinea" autocomplete="off">
								</div>
							</div>
						</div>


						<input type="hidden" id="vel_IdRiga" name="vel_IdRiga" value="">
						<input type="hidden" id="vel_azione" name="vel_azione" value="nuovo">

					</form>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-success" id="salva-entry-vt">Salva</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
				</div>

			</div>
		</div>
	</div>




	<?php include("inc_js.php") ?>
	<script src="../js/velocitateoriche.js"></script>

</body>

</html>