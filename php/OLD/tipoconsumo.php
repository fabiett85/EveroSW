<?php
// in che pagina siamo
$pagina = 'tipoconsumi';

include("../inc/conn.php");


// debug($_SESSION['utente'],'Utente');

// UNITA' DI MISURA: VISUALIZZAZIONE U.D.M. CENSITE
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'mostra-tc') {
	// estraggo la lista
	$sth = $conn_mes->prepare(
		"SELECT tc.*, um.* FROM tipo_consumo AS tc
				LEFT JOIN unita_misura AS um ON tc.tc_Udm = um.um_IdRiga"
	);
	$sth->execute();
	$righe = $sth->fetchAll(PDO::FETCH_ASSOC);

	$output = [];

	foreach ($righe as $riga) {
		//Preparo i dati da visualizzare
		$output[] = [
			'tcDescrizione' => $riga['tc_Descrizione'],
			'umDescrizione' => $riga['um_Descrizione'] . " [" . $riga['um_Sigla'] . "]",
			'azioni' => '<div class="dropdown">
					<button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					<span class="mdi mdi-lead-pencil mdi-18px"></span>
					</button>
					<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
						<a class="dropdown-item modifica-entry-tc" data-id_riga="' . $riga['tc_IdRiga'] . '"><i class="mdi mdi-account-edit"></i> Modifica</a>
						<a class="dropdown-item cancella-entry-tc" data-id_riga="' . $riga['tc_IdRiga'] . '"><i class="mdi mdi-trash-can"></i> Elimina</a>
					</div>
				</div>'
		];
	}

	die(json_encode($output));
}



// UNITA' DI MISURA: RECUPERO VALORI DELLA U.D.M. SELEZIONATA
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'recupera-tc' && !empty($_REQUEST['idRiga'])) {
	// estraggo la lista
	$sth = $conn_mes->prepare("SELECT * FROM tipo_consumo WHERE tc_IdRiga = :idRiga", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
	$sth->execute([':idRiga' => $_REQUEST['idRiga']]);
	$riga = $sth->fetch(PDO::FETCH_ASSOC);

	////debug($riga,'RIGA');

	die(json_encode($riga));
}

// UNITA' DI MISURA: GESTIONE CANCELLAZIONE
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'cancella-tc' && !empty($_REQUEST['idRiga'])) {
	// estraggo la lista
	$sth = $conn_mes->prepare("DELETE FROM tipo_consumo WHERE tc_IdRiga = :idRiga");
	$sth->execute([':idRiga' => $_REQUEST['idRiga']]);

	die('OK');
}



// UNITA' DI MISURA: GESTIONE SALVATAGGIO DATI DA POPUP MODIFICA/INSERIMENTO
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'salva-tc' && !empty($_REQUEST['data'])) {
	// recupero i parametri dal POST
	$parametri = [];
	parse_str($_REQUEST['data'], $parametri);


	// se devo modificare
	if ($parametri['tc_azione'] == 'modifica') {

		$id_modifica = $parametri['tc_IdRiga'];

		$sqlUpdate = "UPDATE tipo_consumo SET
						tc_Descrizione = :tcDescrizione,
						tc_Udm = :tcUdm
						WHERE tc_IdRiga = :IdRiga";

		$sthUpdate = $conn_mes->prepare($sqlUpdate);
		$sthUpdate->execute([
			':tcDescrizione' => $parametri['tc_Descrizione'],
			':tcUdm' => $parametri['tc_Udm'],
			':IdRiga' => $id_modifica
		]);
	} else // nuovo inserimento
	{

		$sqlInsert = "INSERT INTO tipo_consumo(tc_Udm,tc_Descrizione) VALUES(:tcUdm,:tcDescrizione)";

		$sthInsert = $conn_mes->prepare($sqlInsert);
		$sthInsert->execute([
			':tcDescrizione' => $parametri['tc_Descrizione'],
			':tcUdm' => $parametri['tc_Udm'],
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

						<div class="card-body">
							<div class="card-header">
								<h4 class="card-title m-2">TIPI CONSUMO</h4>
							</div>

							<div class="row">

								<div class="col-12">

									<div class="table-responsive pt-1">

										<table id="tabellaDati-tc" class="table table-striped" style="width:100%"
											data-source="tipoconsumo.php?azione=mostra-tc">
											<thead>
												<tr>
													<th>Descrizione</th>
													<th>Unità di misura</th>
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

	<button type="button" id="nuova-tc" class="mdi mdi-button">Nuovo Tipo</button>






	<!-- Popup modale di modifica/inserimento U.D.M. -->
	<div class="modal fade" id="modal-tc" tabindex="-1" role="dialog" aria-labelledby="modal-tc-label" aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modal-tc-label">NUOVO TIPI CONSUMO</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form class="forms-sample" id="form-tc">

						<div class="row">
							<div class="col-6">
								<div class="form-group">
									<label for="tc_Descrizione">Descrizione</label><span style='color:red'> *</span>
									<input type="text" class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="tc_Descrizione" id="tc_Descrizione" autocomplete="off">
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="tc_Udm">Unità di misura</label><span style='color:red'> *</span>
									<select class="form-control form-control-sm dati-popup-modifica selectpicker" name="tc_Udm"
										id="tc_Udm">
										<?php
										$sth = $conn_mes->prepare("SELECT * FROM unita_misura", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
										$sth->execute();
										$trovate = $sth->fetchAll(PDO::FETCH_ASSOC);
										foreach ($trovate as $udm) {
											echo "<option value='" . $udm['um_IdRiga'] . "'>" . $udm['um_Sigla'] . "</option>";
										}
										?>
									</select>
								</div>
							</div>
						</div>


						<input type="hidden" id="tc_IdRiga" name="tc_IdRiga" value="">
						<input type="hidden" id="tc_azione" name="tc_azione" value="nuovo">

					</form>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-success" id="salva-entry-tc">Salva</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
				</div>

			</div>
		</div>
	</div>




	<?php include("inc_js.php") ?>
	<script src="../js/tipoconsumo.js"></script>

</body>

</html>