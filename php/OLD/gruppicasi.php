<?php
// in che pagina siamo
$pagina = 'gruppicasi';

include("../inc/conn.php");


// debug($_SESSION['utente'],'Utente');

if (!empty($_REQUEST['azione'])) {
	// UNITA' DI MISURA: VISUALIZZAZIONE U.D.M. CENSITE
	if ($_REQUEST['azione'] == 'mostra-gc') {
		// estraggo la lista
		$sth = $conn_mes->prepare(
			"SELECT * FROM gruppi_casi"
		);
		$sth->execute();
		$righe = $sth->fetchAll(PDO::FETCH_ASSOC);

		$output = [];

		foreach ($righe as $riga) {
			//Preparo i dati da visualizzare
			$output[] = [
				'gcDescrizione' => $riga['gc_Descrizione'],
				'azioni' => '<div class="dropdown">
					<button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					<span class="mdi mdi-lead-pencil mdi-18px"></span>
					</button>
					<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
						<a class="dropdown-item modifica-entry-gc" data-id_riga="' . $riga['gc_IdRiga'] . '"><i class="mdi mdi-account-edit"></i> Modifica</a>
						<a class="dropdown-item cancella-entry-gc" data-id_riga="' . $riga['gc_IdRiga'] . '"><i class="mdi mdi-trash-can"></i> Elimina</a>
					</div>
				</div>'
			];
		}

		die(json_encode(['data' => $output]));
	}

	// UNITA' DI MISURA: RECUPERO VALORI DELLA U.D.M. SELEZIONATA
	if ($_REQUEST['azione'] == 'recupera-gc' && !empty($_REQUEST['idRiga'] + 1)) {
		// estraggo la lista
		$sth = $conn_mes->prepare(
			"SELECT * FROM gruppi_casi
		WHERE gc_IdRiga = :idRiga"
		);
		$sth->execute(['idRiga' => $_REQUEST['idRiga']]);
		$riga = $sth->fetch(PDO::FETCH_ASSOC);

		die(json_encode($riga));
	}
	// UNITA' DI MISURA: GESTIONE CANCELLAZIONE
	if ($_REQUEST['azione'] == 'cancella-gc' && !empty($_REQUEST['idRiga'])) {
		// estraggo la lista
		$sth = $conn_mes->prepare(
			"DELETE FROM gruppi_casi
		WHERE gc_IdRiga = :idRiga"
		);
		$sth->execute(['idRiga' => $_REQUEST['idRiga']]);

		die('OK');
	}
	// UNITA' DI MISURA: GESTIONE SALVATAGGIO DATI DA POPUP MODIFICA/INSERIMENTO
	if ($_REQUEST['azione'] == 'salva-gc' && !empty($_REQUEST['data'])) {
		// recupero i parametri dal POST
		$parametri = [];
		parse_str($_REQUEST['data'], $parametri);


		// se devo modificare
		if ($parametri['gc_azione'] == 'modifica') {

			$id_modifica = $parametri['gc_IdRiga'];


			$sth = $conn_mes->prepare(
				"UPDATE gruppi_casi SET
			gc_Descrizione = :gcDescrizione
			WHERE gc_IdRiga = :IdRiga"
			);
			$sth->execute([
				'gcDescrizione' => $parametri['gc_Descrizione'],
				'IdRiga' => $id_modifica
			]);
		} else // nuovo inserimento
		{
			$sth = $conn_mes->prepare(
				"INSERT INTO gruppi_casi(gc_Descrizione)
			VALUES(:gcDescrizione)"
			);
			$sth->execute([
				'gcDescrizione' => $parametri['gc_Descrizione']
			]);
		}

		die('OK');
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
							<h4 class="card-title m-2">GRUPPI CASI</h4>
						</div>

						<div class="card-body">

							<div class="row">

								<div class="col-12">

									<div class="table-responsive pt-1">

										<table id="tabellaDati-gc" class="table table-striped" style="width:100%">
											<thead>
												<tr>
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

	<button type="button" id="nuova-gc" class="mdi mdi-button">Nuovo Gruppo</button>






	<!-- Popup modale di modifica/inserimento U.D.M. -->
	<div class="modal fade" id="modal-gc" tabindex="-1" role="dialog" aria-labelledby="modal-gc-label" aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modal-gc-label">NUOVO GRUPPO</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form class="forms-sample" id="form-gc">

						<div class="row">
							<div class="col-12">
								<div class="form-group">
									<label for="gc_Descrizione">Descrizione</label><span style='color:red'> *</span>
									<input type="text" class="form-control form-control-sm dati-popup-modifica obbligatorio" name="gc_Descrizione" id="gc_Descrizione" autocomplete="off" required>
								</div>
							</div>
						</div>


						<input type="hidden" id="gc_IdRiga" name="gc_IdRiga" value="">
						<input type="hidden" id="gc_azione" name="gc_azione" value="nuovo">

					</form>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-success" id="salva-entry-gc">Salva</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
				</div>

			</div>
		</div>
	</div>




	<?php include("inc_js.php") ?>
	<script src="../js/gruppicasi.js"></script>

</body>

</html>