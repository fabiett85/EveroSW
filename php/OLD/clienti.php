<?php
// in che pagina siamo
$pagina = 'clienti';

include("../inc/conn.php");


// debug($_SESSION['utente'],'Utente');

// UNITA' DI MISURA: VISUALIZZAZIONE U.D.M. CENSITE
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'mostra') {
	// estraggo la lista
	$sth = $conn_mes->prepare(
		"SELECT *
		FROM clienti"
	);
	$sth->execute();
	$righe = $sth->fetchAll(PDO::FETCH_ASSOC);

	$output = [];

	foreach ($righe as $riga) {
		//Preparo i dati da visualizzare
		$output[] = [
			'cl_IdRiga' => $riga['cl_IdRiga'],
			'cl_Descrizione' => $riga['cl_Descrizione'],
			'cl_Telefono' => $riga['cl_Telefono'],
			'cl_Mail' => $riga['cl_Mail'],
			'cl_Indirizzo' => $riga['cl_Indirizzo'],
			'azioni' => '<div class="dropdown">
					<button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					<span class="mdi mdi-lead-pencil mdi-18px"></span>
					</button>
					<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
						<a class="dropdown-item modifica-entry" data-id-riga="' . $riga['cl_IdRiga'] . '"><i class="mdi mdi-account-edit"></i> Modifica</a>
						<a class="dropdown-item cancella-entry" data-id-riga="' . $riga['cl_IdRiga'] . '"><i class="mdi mdi-trash-can"></i> Elimina</a>
					</div>
				</div>'
		];
	}

	die(json_encode($output));
}



// UNITA' DI MISURA: RECUPERO VALORI DELLA U.D.M. SELEZIONATA
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'recupera' && !empty($_REQUEST['idRiga'])) {
	// estraggo la lista
	$sth = $conn_mes->prepare(
		"SELECT * FROM clienti
		WHERE cl_IdRiga = :idRiga"
	);
	$sth->execute([':idRiga' => $_REQUEST['idRiga']]);
	$riga = $sth->fetch(PDO::FETCH_ASSOC);

	//debug($riga,'RIGA');

	die(json_encode($riga));
}

// UNITA' DI MISURA: GESTIONE CANCELLAZIONE
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'cancella' && !empty($_REQUEST['idRiga'])) {
	// estraggo la lista
	$sth = $conn_mes->prepare("DELETE FROM clienti WHERE cl_IdRiga = :idRiga");
	$sth->execute([':idRiga' => $_REQUEST['idRiga']]);

	die('OK');
}



// UNITA' DI MISURA: GESTIONE SALVATAGGIO DATI DA POPUP MODIFICA/INSERIMENTO
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'salva') {
	// recupero i parametri dal POST
	$parametri = [];
	parse_str($_REQUEST['data'], $parametri);


	// se devo modificare
	if ($parametri['cl_azione'] == 'modifica') {

		$id_modifica = $parametri['cl_IdRiga'];

		$sth = $conn_mes->prepare(
			"UPDATE clienti SET
			cl_Descrizione = :Descrizione,
			cl_Telefono = :Telefono,
			cl_Mail = :Mail,
			cl_Indirizzo = :Indirizzo
			WHERE cl_IdRiga = :IdRiga"
		);
		$sth->execute([
			':Descrizione' => $parametri['cl_Descrizione'],
			':Telefono' => $parametri['cl_Telefono'],
			':Mail' => $parametri['cl_Mail'],
			':Indirizzo' => $parametri['cl_Indirizzo'],
			':IdRiga' => $id_modifica
		]);
	} else // nuovo inserimento
	{

		$sth = $conn_mes->prepare(
			"INSERT INTO clienti(
				cl_Descrizione,
				cl_Telefono,
				cl_Mail,
				cl_Indirizzo
			) VALUES(
				:Descrizione,
				:Telefono,
				:Mail,
				:Indirizzo
			)"
		);
		$sth->execute([
			':Descrizione' => $parametri['cl_Descrizione'],
			':Telefono' => $parametri['cl_Telefono'],
			':Mail' => $parametri['cl_Mail'],
			':Indirizzo' => $parametri['cl_Indirizzo'],
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
							<h4 class="card-title mx-2 my-2">CLIENTI</h4>
						</div>
						<div class="card-body">

							<div class="row">

								<div class="col-12">

									<div class="table-responsive pt-1">

										<table id="tabellaDati" class="table table-striped" style="width:100%" data-source="clienti.php?azione=mostra">
											<thead>
												<tr>
													<th>ID</th>
													<th>Descrizione</th>
													<th>Telefono</th>
													<th>Mail</th>
													<th>Indirizzo</th>
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

	<button type="button" id="nuovo-cliente" class="mdi mdi-button bottone-basso-destra">NUOVO CLIENTE</button>






	<!-- Popup modale di modifica/inserimento U.D.M. -->
	<div class="modal fade" id="modal-inserimento" tabindex="-1" role="dialog" aria-labelledby="modal-inserimento-label" aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modal-inserimento-label">NUOVA UNIT&Agrave; DI MISURA</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form class="forms-sample" id="form-inserimento">

						<div class="row">
							<div class="col-6">
								<div class="form-group">
									<label for="cl_Descrizione">Descrizione</label><span style='color:red'> *</span>
									<input type="text" class="form-control form-control-sm dati-popup-modifica obbligatorio" name="cl_Descrizione" id="cl_Descrizione" autocomplete="off">
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="cl_Telefono">Telefono</label>
									<input type="text" class="form-control form-control-sm dati-popup-modifica" name="cl_Telefono" id="cl_Telefono" autocomplete="off">
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="cl_Mail">E-Mail</label>
									<input type="text" class="form-control form-control-sm dati-popup-modifica" name="cl_Mail" id="cl_Mail" autocomplete="off">
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="cl_Indirizzo">Indirizzo</label>
									<input type="text" class="form-control form-control-sm dati-popup-modifica" name="cl_Indirizzo" id="cl_Indirizzo" autocomplete="off">
								</div>
							</div>
						</div>


						<input type="hidden" id="cl_IdRiga" name="cl_IdRiga" value="">
						<input type="hidden" id="cl_azione" name="cl_azione" value="nuovo">

					</form>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-success" id="salva-entry">Salva</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
				</div>

			</div>
		</div>
	</div>




	<?php include("inc_js.php") ?>
	<script src="../js/clienti.js"></script>

</body>

</html>