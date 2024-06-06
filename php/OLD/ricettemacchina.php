<?php
// in che pagina siamo
$pagina = 'ricettemacchina';

include("../inc/conn.php");


// RICETTE MACCHINA: VISUALIZZAZIONE CATEGORIE CENSITE
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'mostra-ricm') {
	// estraggo la lista
	$sth = $conn_mes->prepare("SELECT ricette_macchina.*, prodotti.prd_Descrizione, risorse.ris_Descrizione
									FROM ricette_macchina
									LEFT JOIN prodotti ON ricette_macchina.ricm_IdProdotto = prodotti.prd_IdProdotto
									LEFT JOIN risorse ON ricette_macchina.ricm_IdRisorsa = risorse.ris_IdRisorsa", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
	$sth->execute();
	$righe = $sth->fetchAll(PDO::FETCH_ASSOC);

	$output = [];

	foreach ($righe as $riga) {
		//Preparo i dati da visualizzare
		$output[] = [
			'DescrizioneRisorsa' => $riga['ris_Descrizione'],
			'DescrizioneProdotto' => $riga['prd_Descrizione'],
			'IdRicetta' => $riga['ricm_Ricetta'],
			'DescrizioneRicetta' => $riga['ricm_Descrizione'],
			'azioni' => '<div class="dropdown">
					<button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					<span class="mdi mdi-lead-pencil mdi-18px"></span>
					</button>
					<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
						<a class="dropdown-item modifica-entry-ricm" data-id_riga="' . $riga['ricm_IdRiga'] . '"><i class="mdi mdi-account-edit"></i> Modifica</a>
						<a class="dropdown-item cancella-entry-ricm" data-id_riga="' . $riga['ricm_IdRiga'] . '"><i class="mdi mdi-trash-can"></i> Elimina</a>
					</div>
				</div>'
		];
	}

	die(json_encode($output));
}



// RICETTE MACCHINA: RECUPERO VALORI DELLA CATEGORIA SELEZIONATA
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'recupera-ricm' && !empty($_REQUEST['idRiga'])) {
	// estraggo la lista
	$sth = $conn_mes->prepare("SELECT * FROM ricette_macchina WHERE ricm_IdRiga = :idRiga", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
	$sth->execute([':idRiga' => $_REQUEST['idRiga']]);
	$riga = $sth->fetch(PDO::FETCH_ASSOC);

	//debug($riga,'RIGA');

	die(json_encode($riga));
}

// RICETTE MACCHINA: GESTIONE CANCELLAZIONE
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'cancella-ricm' && !empty($_REQUEST['idRiga'])) {
	// estraggo la lista
	$sth = $conn_mes->prepare("DELETE FROM ricette_macchina WHERE ricm_IdRiga = :idRiga");
	$sth->execute([':idRiga' => $_REQUEST['idRiga']]);

	die('OK');
}



// RICETTE MACCHINA: GESTIONE SALVATAGGIO DATI DA POPUP MODIFICA/INSERIMENTO
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'salva-ricm' && !empty($_REQUEST['data'])) {

	// recupero i parametri dal POST
	$parametri = [];
	parse_str($_REQUEST['data'], $parametri);
	$parametri['ricm_Ricetta'] = str_replace(' ', '', $parametri['ricm_Ricetta']);
	// se devo modificare
	if ($parametri['ricm_azione'] == 'modifica') {

		$id_modifica = $parametri['ricm_IdRiga'];

		// verifico che il codice cliente inserito, che mi funge da ID, non esista in altri record se non in quello che sto modificando
		$sth = $conn_mes->prepare("SELECT *
										FROM ricette_macchina
										WHERE ricm_IdProdotto = :IdProdotto
										AND ricm_IdRisorsa = :IdRisorsa
										AND ricm_Ricetta = :IdRicetta
										AND ricm_IdRiga != :IdRiga", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
		$sth->execute([
			':IdRisorsa' => $parametri['ricm_IdRisorsa'],
			':IdProdotto' => $parametri['ricm_IdProdotto'],
			':IdRicetta' => $parametri['ricm_Ricetta'],
			':IdRiga' => $id_modifica
		]);
		$trovati = $sth->fetch(PDO::FETCH_ASSOC);

		if (!$trovati) {

			$sqlUpdate = "UPDATE ricette_macchina SET
							ricm_IdRisorsa = :IdRisorsa,
							ricm_IdProdotto = :IdProdotto,
							ricm_Ricetta = :IdRicetta,
							ricm_Descrizione = :DescrizioneRicetta
							WHERE ricm_IdRiga = :IdRiga";

			$sthUpdate = $conn_mes->prepare($sqlUpdate);
			$sthUpdate->execute([
				':IdRisorsa' => $parametri['ricm_IdRisorsa'],
				':IdProdotto' => $parametri['ricm_IdProdotto'],
				':IdRicetta' => $parametri['ricm_Ricetta'],
				':DescrizioneRicetta' => $parametri['ricm_Descrizione'],
				':IdRiga' => $id_modifica
			]);
		} else {
			die("La linea e il prodotto inseriti sono già presenti in tabella.");
		}
	} else // nuovo inserimento
	{

		// verifico che il codice cliente inserito, che mi funge da ID, non esista in altri record
		$sthSelect = $conn_mes->prepare("SELECT * FROM ricette_macchina WHERE ricm_IdProdotto = :IdProdotto AND ricm_IdRisorsa = :IdRisorsa AND ricm_Ricetta = :Ricetta", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
		$sthSelect->execute([':IdProdotto' => $parametri['ricm_IdProdotto'], ':IdRisorsa' => $parametri['ricm_IdRisorsa'], ':Ricetta' => $parametri['ricm_Ricetta']]);
		$trovati = $sthSelect->fetch(PDO::FETCH_ASSOC);

		if (!$trovati) {

			$sqlInsert = "INSERT INTO ricette_macchina(ricm_IdRisorsa,ricm_IdProdotto,ricm_Ricetta,ricm_Descrizione) VALUES(:IdRisorsa,:IdProdotto,:IdRicetta,:DescrizioneRicetta)";

			$sthInsert = $conn_mes->prepare($sqlInsert);
			$sthInsert->execute([
				':IdRisorsa' => $parametri['ricm_IdRisorsa'],
				':IdProdotto' => $parametri['ricm_IdProdotto'],
				':IdRicetta' => $parametri['ricm_Ricetta'],
				':DescrizioneRicetta' => $parametri['ricm_Descrizione'],
			]);
		} else {
			die("La ricetta inserita per la macchina e il prodotto in oggetto è già presente in archivio.");
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
							<h4 class="card-title m-2">RICETTE MACCHINA</h4>
						</div>
						<div class="card-body">

							<div class="row">

								<div class="col-12">

									<div class="table-responsive pt-1">

										<table id="tabellaDati-ricm" class="table table-striped" style="width:100%"
											data-source="ricettemacchina.php?azione=mostra-ricm">
											<thead>
												<tr>
													<th>Macchina</th>
													<th>Prodotto</th>
													<th>Ricetta</th>
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

	<button type="button" id="nuova-ricm" class="mdi mdi-button">NUOVA RICETTA</button>






	<!-- Popup modale di modifica/inserimento RICETTA MACCHINA-->
	<div class="modal fade" id="modal-ricm" tabindex="-1" role="dialog" aria-labelledby="modal-ricm-label"
		aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modal-ricm-label">NUOVA RICETTA</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form class="forms-sample" id="form-ricm">

						<div class="row">
							<div class="col-12">
								<div class="form-group">
									<label for="ricm_IdRisorsa">Macchina</label><span style='color:red'> *</span>
									<select class="form-control form-control-sm dati-popup-modifica selectpicker" name="ricm_IdRisorsa"
										id="ricm_IdRisorsa" data-live-search="true">
										<?php
										$sth = $conn_mes->prepare("SELECT *
																		FROM risorse
																		ORDER BY risorse.ris_IdRisorsa ASC", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
										$sth->execute();
										$prodotti = $sth->fetchAll(PDO::FETCH_ASSOC);

										foreach ($prodotti as $prodotto) {
											echo "<option value='" . $prodotto['ris_IdRisorsa'] . "'>" . $prodotto['ris_Descrizione'] . "</option>";
										}
										?>
									</select>
								</div>
							</div>
							<div class="col-12">
								<div class="form-group">
									<label for="ricm_IdProdotto">Prodotto</label><span style='color:red'> *</span>
									<select class="form-control form-control-sm dati-popup-modifica selectpicker" name="ricm_IdProdotto"
										id="ricm_IdProdotto" data-live-search="true">
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
									<label for="ricm_Ricetta">Nome ricetta</label><span style='color:red'> *</span>
									<input type="text" class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="ricm_Ricetta" id="ricm_Ricetta" placeholder="Nome ricetta" autocomplete="off">
								</div>
							</div>
							<div class="col-12">
								<div class="form-group">
									<label for="ricm_Descrizione">Descrizione ricetta</label>
									<input type="text" class="form-control form-control-sm dati-popup-modifica" name="ricm_Descrizione"
										id="ricm_Descrizione" placeholder="Descrizione ricetta" autocomplete="off">
								</div>
							</div>
						</div>


						<input type="hidden" id="ricm_IdRiga" name="ricm_IdRiga" value="">
						<input type="hidden" id="ricm_azione" name="ricm_azione" value="nuovo">

					</form>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-success" id="salva-entry-ricm">Salva</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
				</div>

			</div>
		</div>
	</div>


	<?php include("inc_js.php") ?>
	<script src="../js/ricettemacchina.js"></script>

</body>

</html>