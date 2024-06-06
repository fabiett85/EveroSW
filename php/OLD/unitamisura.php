<?php
// in che pagina siamo
$pagina = 'unitamisura';

include("../inc/conn.php");


// debug($_SESSION['utente'],'Utente');

// UNITA' DI MISURA: VISUALIZZAZIONE U.D.M. CENSITE
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'mostra-um') {
	// estraggo la lista
	$sth = $conn_mes->prepare("SELECT unita_misura.*
									FROM unita_misura", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
	$sth->execute();
	$righe = $sth->fetchAll(PDO::FETCH_ASSOC);

	$output = [];

	foreach ($righe as $riga) {
		//Preparo i dati da visualizzare
		$output[] = [
			'UmSigla' => $riga['um_Sigla'],
			'UmDescrizione' => $riga['um_Descrizione'],
			'azioni' => '<div class="dropdown">
					<button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					<span class="mdi mdi-lead-pencil mdi-18px"></span>
					</button>
					<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
						<a class="dropdown-item modifica-entry-um" data-id_riga="' . $riga['um_IdRiga'] . '"><i class="mdi mdi-account-edit"></i> Modifica</a>
						<a class="dropdown-item cancella-entry-um" data-id_riga="' . $riga['um_IdRiga'] . '"><i class="mdi mdi-trash-can"></i> Elimina</a>
					</div>
				</div>'
		];
	}

	die(json_encode($output));
}



// UNITA' DI MISURA: RECUPERO VALORI DELLA U.D.M. SELEZIONATA
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'recupera-um' && !empty($_REQUEST['idRiga'])) {
	// estraggo la lista
	$sth = $conn_mes->prepare("SELECT * FROM unita_misura WHERE um_IdRiga = :idRiga", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
	$sth->execute([':idRiga' => $_REQUEST['idRiga']]);
	$riga = $sth->fetch(PDO::FETCH_ASSOC);

	//debug($riga,'RIGA');

	die(json_encode($riga));
}

// UNITA' DI MISURA: GESTIONE CANCELLAZIONE
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'cancella-um' && !empty($_REQUEST['idRiga'])) {
	// estraggo la lista
	$sth = $conn_mes->prepare("DELETE FROM unita_misura WHERE um_IdRiga = :idRiga");
	$sth->execute([':idRiga' => $_REQUEST['idRiga']]);

	die('OK');
}



// UNITA' DI MISURA: GESTIONE SALVATAGGIO DATI DA POPUP MODIFICA/INSERIMENTO
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'salva-um' && !empty($_REQUEST['data'])) {
	// recupero i parametri dal POST
	$parametri = [];
	parse_str($_REQUEST['data'], $parametri);


	// se devo modificare
	if ($parametri['um_azione'] == 'modifica') {

		$id_modifica = $parametri['um_IdRiga'];

		$sqlUpdate = "UPDATE unita_misura SET
						um_Sigla = :UmSigla,
						um_Descrizione = :UmDescrizione
						WHERE um_IdRiga = :IdRiga";

		$sthUpdate = $conn_mes->prepare($sqlUpdate);
		$sthUpdate->execute([
			':UmSigla' => $parametri['um_Sigla'],
			':UmDescrizione' => $parametri['um_Descrizione'],
			':IdRiga' => $id_modifica
		]);
	} else // nuovo inserimento
	{

		$sqlInsert = "INSERT INTO unita_misura(um_Sigla,um_Descrizione) VALUES(:UmSigla,:UmDescrizione)";

		$sthInsert = $conn_mes->prepare($sqlInsert);
		$sthInsert->execute([
			':UmSigla' => $parametri['um_Sigla'],
			':UmDescrizione' => $parametri['um_Descrizione']
		]);
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
							<h4 class="card-title m-2">UNIT&Agrave; DI MISURA CENSITE</h4>
						</div>
						<div class="card-body">

							<div class="row">

								<div class="col-12">

									<div class="table-responsive pt-1">

										<table id="tabellaDati-um" class="table table-striped" style="width:100%"
											data-source="unitamisura.php?azione=mostra-um">
											<thead>
												<tr>
													<th>Sigla</th>
													<th>Descrizione</th>
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

	<button type="button" id="nuova-um" class="mdi mdi-button">NUOVA U.D.M.</button>






	<!-- Popup modale di modifica/inserimento U.D.M. -->
	<div class="modal fade" id="modal-um" tabindex="-1" role="dialog" aria-labelledby="modal-um-label" aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modal-um-label">NUOVA UNIT&Agrave; DI MISURA</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form class="forms-sample" id="form-um">

						<div class="row">
							<div class="col-12">
								<div class="form-group">
									<label for="um_Sigla">Sigla</label><span style='color:red'> *</span>
									<input type="text" class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="um_Sigla" id="um_Sigla" autocomplete="off">
								</div>
							</div>
							<div class="col-12">
								<div class="form-group">
									<label for="um_Descrizione">Descrizione</label><span style='color:red'> *</span>
									<input type="text" class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="um_Descrizione" id="um_Descrizione" autocomplete="off">
								</div>
							</div>
						</div>


						<input type="hidden" id="um_IdRiga" name="um_IdRiga" value="">
						<input type="hidden" id="um_azione" name="um_azione" value="nuovo">

					</form>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-success" id="salva-entry-um">Salva</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
				</div>

			</div>
		</div>
	</div>




	<?php include("inc_js.php") ?>
	<script src="../js/unitamisura.js"></script>

</body>

</html>