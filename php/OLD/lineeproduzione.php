<?php
// in che pagina siamo
$pagina = ['lineeproduzione'];

include("../inc/conn.php");

// debug($_SESSION['utente'],'Utente');

// LINEE: VISUALIZZAZIONE LINEE CENSITE
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'mostra-linee') {
	// Recupero elenco delle linee definite
	$sth = $conn_mes->prepare(
		"SELECT linee_produzione.*
		FROM linee_produzione
		WHERE linee_produzione.lp_IdLinea != 'lin_0P'
		AND linee_produzione.lp_IdLinea != 'lin_0X'"
	);
	$sth->execute();
	$righe = $sth->fetchAll(PDO::FETCH_ASSOC);

	$output = [];

	foreach ($righe as $riga) {
		//Preparo i dati da visualizzare
		$output[] = [
			'IdLinea' => $riga['lp_IdLinea'],
			'DescrizioneLinea' => $riga['lp_Descrizione'],
			'NoteLinea' => $riga['lp_Note'],
			'Costo' => $riga['lp_CostoOrario'] . ' [€/h]',
			'azioni' => '<div class="dropdown">
					<button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					<span class="mdi mdi-lead-pencil mdi-18px"></span>
					</button>
					<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
						<a class="dropdown-item modifica-entry-linee" data-id_riga="' . $riga['lp_IdLinea'] . '"><i class="mdi mdi-account-edit"></i> Modifica</a>
						<a class="dropdown-item cancella-entry-linee" data-id_riga="' . $riga['lp_IdLinea'] . '"><i class="mdi mdi-trash-can"></i> Elimina</a>
					</div>
				</div>'
		];
	}

	die(json_encode($output));
}


// LINEE: RECUPERO VALORI DELLA LINEA SELEZIONATA
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'recupera-linee' && !empty($_REQUEST['idRiga'])) {
	// Recupero i dati relativi alla linea selezionata
	$sth = $conn_mes->prepare(
		"SELECT * FROM linee_produzione
		WHERE lp_IdLinea = :idRiga"
	);
	$sth->execute(['idRiga' => $_REQUEST['idRiga']]);
	$riga = $sth->fetch(PDO::FETCH_ASSOC);

	die(json_encode($riga));
}


// LINEE: GESTIONE CANCELLAZIONE
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'cancella-linee' && !empty($_REQUEST['idRiga'])) {

	// Verifico se ho risorse associate alla linea che desidero eliminare
	$sthVerificaRisorseAssociate = $conn_mes->prepare(
		"SELECT COUNT(*) AS NRisorseAssociate FROM risorse
		WHERE risorse.ris_LineaProduzione = :idLinea"
	);
	$sthVerificaRisorseAssociate->execute(['idLinea' => $_REQUEST['idRiga']]);
	$riga = $sthVerificaRisorseAssociate->fetch(PDO::FETCH_ASSOC);

	// Se la linea non ha risorse associate...
	if ($riga['NRisorseAssociate'] == 0) {

		// ...procedo con la cancellazione della linea in oggetto
		$sth = $conn_mes->prepare(
			"DELETE FROM linee_produzione
			WHERE lp_IdLinea = :idRiga"
		);
		$sth->execute(['idRiga' => $_REQUEST['idRiga']]);

		// ...e delle relative velocità di linea
		$sth = $conn_mes->prepare(
			"DELETE FROM velocita_teoriche
			WHERE vel_IdLineaProduzione = :idRiga"
		);
		$sth->execute(['idRiga' => $_REQUEST['idRiga']]);

		die('OK');
		exit();
	} // ... altrimenti segnalo l'impossibilità di procedere
	else {
		die('RISORSE_ASSOCIATE');
		exit();
	}
}



// LINEE: GESTIONE SALVATAGGIO DATI DA POPUP MODIFICA/INSERIMENTO
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'salva-linee' && !empty($_REQUEST['data'])) {
	// Recupero i parametri dal POST
	$parametri = [];
	parse_str($_REQUEST['data'], $parametri);


	// Se devo modificare
	if ($parametri['lp_azione'] == 'modifica') {

		$id_modifica = $parametri['lp_IdLinea'];

		$sthUpdate = $conn_mes->prepare(
			"UPDATE linee_produzione SET
			lp_Descrizione = :DescrizioneLinea,
			lp_CostoOrario = :CostoOrario,
			lp_Note = :NoteLinea
			WHERE lp_IdLinea = :IdRiga"
		);
		$sthUpdate->execute([
			'DescrizioneLinea' => $parametri['lp_Descrizione'],
			'CostoOrario' => $parametri['lp_CostoOrario'],
			'NoteLinea' => $parametri['lp_Note'],
			'IdRiga' => $id_modifica
		]);
	} else // nuovo inserimento
	{

		// Estraggo ultimo codice utilizzato
		$sthRecuperaId = $conn_mes->prepare(
			"SELECT TOP(1) lp_IdLinea FROM linee_produzione
			WHERE lp_IdLinea != 'lin_0P'
			AND lp_IdLinea != 'lin_0X'
			ORDER BY lp_IdLinea DESC"
		);
		$sthRecuperaId->execute();
		$riga = $sthRecuperaId->fetch(PDO::FETCH_ASSOC);

		$nuovoIndice = 1;
		$nuovoIdLinea = "lin_01";

		// Se ho già entry esistenti, formatto opportunamente il nuovo ID
		if ($riga) {
			$temp = explode('_', $riga['lp_IdLinea']);
			$nuovoIndice = str_pad(intval($temp[1]) + 1, 2, '0', STR_PAD_LEFT);
			$nuovoIdLinea = 'lin_' . $nuovoIndice;
		}

		$sthInsert = $conn_mes->prepare(
			"INSERT INTO linee_produzione(lp_IdLinea,lp_IndiceNumericoLinea,lp_Descrizione,lp_Note,lp_CostoOrario)
			VALUES(:IdLinea,:IndiceNumericoLinea,:DescrizioneLinea,:NoteLinea,:CostoOrario)"
		);
		$sthInsert->execute([
			'IdLinea' => $nuovoIdLinea,
			'IndiceNumericoLinea' => $nuovoIndice,
			'DescrizioneLinea' => $parametri['lp_Descrizione'],
			'CostoOrario' => $parametri['lp_CostoOrario'],
			'NoteLinea' => $parametri['lp_Note']
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
							<h4 class="card-title m-2">LINEE DI PRODUZIONE</h4>
						</div>
						<div class="card-body">


							<div class="row">

								<div class="col-12">

									<div class="table-responsive pt-1">

										<table id="tabellaDati-linee" class="table table-striped" style="width:100%"
											data-source="lineeproduzione.php?azione=mostra-linee">
											<thead>
												<tr>
													<th>Aux Id Linea</th>
													<th>Descrizione</th>
													<th>CostoOrario</th>
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
				<?php include("inc_footer.php") ?>

			</div>

		</div>

	</div>

	<button type="button" id="nuova-linea" class="mdi mdi-button">NUOVA LINEA</button>






	<!-- Popup modale di modifica/inserimento LINEA -->
	<div class="modal fade" id="modal-linee" tabindex="-1" role="dialog" aria-labelledby="modal-linee-label"
		aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modal-linee-label">NUOVA LINEA</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form class="forms-sample" id="form-linee">

						<div class="row">
							<div class="col-12">
								<div class="form-group">
									<label for="lp_Descrizione">Descrizione</label><span style='color:red'> *</span>
									<input type="text" class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="lp_Descrizione" id="lp_Descrizione" autocomplete="off" required>
								</div>
							</div>
							<div class="col-12">
								<div class="form-group">
									<label for="lp_CostoOrario">Costo orario [€/h]</label>
									<input type="number" class="form-control form-control-sm dati-popup-modifica" name="lp_CostoOrario"
										id="lp_CostoOrario" autocomplete="off">
								</div>
							</div>
							<div class="col-12">
								<div class="form-group">
									<label for="lp_Note">Note</label>
									<input type="text" class="form-control form-control-sm dati-popup-modifica " name="lp_Note"
										id="lp_Note" autocomplete="off">
								</div>
							</div>
						</div>


						<input type="hidden" id="lp_IdLinea" name="lp_IdLinea" value="">
						<input type="hidden" id="lp_azione" name="lp_azione" value="nuovo">

					</form>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-success" id="salva-entry-linee">Salva</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
				</div>

			</div>
		</div>
	</div>




	<?php include("inc_js.php") ?>
	<script src="../js/lineeproduzione.js"></script>

</body>

</html>