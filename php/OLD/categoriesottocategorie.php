<?php
// in che pagina siamo
$pagina = "categoriesottocategorie";

include("../inc/conn.php");

// CATEGORIE: VISUALIZZAZIONE CATEGORIE CENSITE
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "mostra-cat") {
	// estraggo la lista
	$sth = $conn_mes->prepare(
		"SELECT * FROM categoria_prodotti
		WHERE categoria_prodotti.cat_IdCategoria <> 0"
	);
	$sth->execute();
	$righe = $sth->fetchAll();

	$output = [];

	foreach ($righe as $riga) {
		//Preparo i dati da visualizzare
		$output[] = [
			"NomeCategoria" => $riga["cat_Nome"],
			"DescrizioneCategoria" => $riga["cat_Descrizione"],
			"azioni" => '<div class="dropdown">
					<button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					<span class="mdi mdi-lead-pencil mdi-18px"></span>
					</button>
					<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
						<a class="dropdown-item modifica-categoria" data-id_riga="' . $riga["cat_IdCategoria"] . '"><i class="mdi mdi-account-edit"></i> Modifica</a>
						<a class="dropdown-item cancella-categoria" data-id_riga="' . $riga["cat_IdCategoria"] . '"><i class="mdi mdi-trash-can"></i> Elimina</a>
					</div>
				</div>'
		];
	}

	die(json_encode(['data' => $output]));
}


// SOTTOCATEGORIE: VISUALIZZAZIONE SOTTOCATEGORIE CENSITE
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "mostra-sot") {
	// estraggo la lista
	$sth = $conn_mes->prepare(
		"SELECT SP.*, CP.cat_Nome AS NomeCategoria
		FROM sottocategoria_prodotti AS SP
		LEFT JOIN categoria_prodotti AS CP ON SP.sot_Categoria = CP.cat_IdCategoria
		WHERE SP.sot_IdSottocategoria <> 0"
	);
	$sth->execute();
	$righe = $sth->fetchAll();

	$output = [];

	foreach ($righe as $riga) {
		//Preparo i dati da visualizzare

		$output[] = [
			"Categoria" => $riga["NomeCategoria"],
			"NomeSottocategoria" => $riga["sot_Nome"],
			"DescrizioneSottocategoria" => $riga["sot_Descrizione"],
			"azioni" => '<div class="dropdown">
					<button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Modifica riga">
					<span class="mdi mdi-lead-pencil mdi-18px"></span>
					</button>
					<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
						<a class="dropdown-item modifica-sottocategoria" data-id_riga="' . $riga["sot_IdSottocategoria"] . '"><i class="mdi mdi-account-edit"></i> Modifica</a>
						<a class="dropdown-item cancella-sottocategoria" data-id_riga="' . $riga["sot_IdSottocategoria"] . '"><i class="mdi mdi-trash-can"></i> Elimina</a>
					</div>
				</div>'
		];
	}

	die(json_encode(['data' => $output]));
}


// CATEGORIE: RECUPERO VALORI DELLA CATEGORIA SELEZIONATA
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "recupera-cat" && !empty($_REQUEST["idRiga"])) {
	// estraggo la lista
	$sth = $conn_mes->prepare(
		"SELECT * FROM categoria_prodotti
		WHERE cat_IdCategoria = :idRiga"
	);
	$sth->execute([":idRiga" => $_REQUEST["idRiga"]]);
	$riga = $sth->fetch();

	die(json_encode($riga));
}

// CATEGORIE: GESTIONE CANCELLAZIONE
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "cancella-cat" && !empty($_REQUEST["idRiga"])) {
	// estraggo la lista
	$sth = $conn_mes->prepare(
		"DELETE FROM categoria_prodotti
		WHERE cat_IdCategoria = :idRiga"
	);
	$sth->execute([":idRiga" => $_REQUEST["idRiga"]]);

	die("OK");
}




// SOTTOCATEGORIE: RECUPERO VALORI DELLA CATEGORIA SELEZIONATA
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "recupera-sot" && !empty($_REQUEST["idRiga"])) {
	// estraggo la lista
	$sth = $conn_mes->prepare(
		"SELECT * FROM sottocategoria_prodotti
		WHERE sot_IdSottocategoria = :idRiga"
	);
	$sth->execute([":idRiga" => $_REQUEST["idRiga"]]);
	$riga = $sth->fetch();

	die(json_encode($riga));
}

// SOTTOCATEGORIE: GESTIONE CANCELLAZIONE
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "cancella-sot" && !empty($_REQUEST["idRiga"])) {
	// estraggo la lista
	$sth = $conn_mes->prepare(
		"DELETE FROM sottocategoria_prodotti
		WHERE sot_IdSottocategoria = :idRiga"
	);
	$sth->execute([":idRiga" => $_REQUEST["idRiga"]]);

	die("OK");
}




// CATEGORIE: GESTIONE SALVATAGGIO DATI DA POPUP MODIFICA/INSERIMENTO
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "salva-cat" && !empty($_REQUEST["data"])) {
	// recupero i parametri dal POST
	$parametri = [];
	parse_str($_REQUEST["data"], $parametri);

	// se devo modificare
	if ($parametri["cat_azione"] == "modifica") {

		$id_modifica = $parametri["cat_IdCategoria_Aux"];

		$sthUpdate = $conn_mes->prepare(
			"UPDATE categoria_prodotti SET
			cat_Nome = :NomeCategoria,
			cat_Descrizione = :DescrizioneCategoria
			WHERE cat_IdCategoria = :IdRiga"
		);
		$sthUpdate->execute([
			":NomeCategoria" => $parametri["cat_Nome"],
			":DescrizioneCategoria" => $parametri["cat_Descrizione"],
			":IdRiga" => $id_modifica
		]);
	} else // nuovo inserimento
	{

		$sthInsert = $conn_mes->prepare(
			"INSERT INTO categoria_prodotti(cat_Nome,cat_Descrizione)
			VALUES(:NomeCategoria,:DescrizioneCategoria)"
		);
		$sthInsert->execute([
			":NomeCategoria" => $parametri["cat_Nome"],
			":DescrizioneCategoria" => $parametri["cat_Descrizione"]
		]);
	}

	die("OK");
}


// CATEGORIE: GESTIONE SALVATAGGIO DATI DA POPUP MODIFICA/INSERIMENTO
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "salva-sot" && !empty($_REQUEST["data"])) {
	// recupero i parametri dal POST
	$parametri = [];
	parse_str($_REQUEST["data"], $parametri);

	// se devo modificare
	if ($parametri["sot_azione"] == "modifica") {

		$id_modifica = $parametri["sot_IdSottocategoria_Aux"];


		$sthUpdate = $conn_mes->prepare(
			"UPDATE sottocategoria_prodotti SET
			sot_Categoria = :CategoriaAppartenenza,
			sot_Nome = :NomeSottocategoria,
			sot_Descrizione = :DescrizioneSottocategoria
			WHERE sot_IdSottocategoria = :IdRiga"
		);
		$sthUpdate->execute([
			":CategoriaAppartenenza" => $parametri["sot_Categoria"],
			":NomeSottocategoria" => $parametri["sot_Nome"],
			":DescrizioneSottocategoria" => $parametri["sot_Descrizione"],
			":IdRiga" => $id_modifica
		]);
	} else // nuovo inserimento
	{

		$sthInsert = $conn_mes->prepare(
			"INSERT INTO sottocategoria_prodotti(sot_Categoria,sot_Nome,sot_Descrizione)
			VALUES(:CategoriaAppartenenza,:NomeSottocategoria,:DescrizioneSottocategoria)"
		);
		$sthInsert->execute([
			":CategoriaAppartenenza" => $parametri["sot_Categoria"],
			":NomeSottocategoria" => $parametri["sot_Nome"],
			":DescrizioneSottocategoria" => $parametri["sot_Descrizione"]
		]);
	}

	die("OK");
}



//AUSILIARIA PER INSERIMENTO SOTTOCATEGORIA: POPOLAMENTO SELECT CON LE POSSIBILI CATEGORIE DI APPARTENENZA
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "caricaSelectCategoriaAppartenenza") {
	// estraggo gli eventuali prodotti aggiuntivi
	$sth = $conn_mes->prepare(
		'SELECT * FROM categoria_prodotti
		WHERE categoria_prodotti.cat_IdCategoria <> 0'
	);
	$sth->execute();
	$categorie = $sth->fetchAll();
	$optionValue = "";

	//Se ho trovato categorie
	if ($categorie) {

		//Aggiungo ognuna delle categorie trovate alla stringa che conterrà le possibili opzioni della select categorie di appartenenza
		foreach ($categorie as $categoria) {
			$optionValue = $optionValue . "<option value='" . $categoria['cat_IdCategoria'] . "'>" . $categoria['cat_Nome'] . " </option>";
		}
		die($optionValue);
	} else {
		die("NO_CAT");
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
							<h4 class="card-title mx-2 my-2">CATEGORIE CENSITE</h4>
						</div>

						<div class="card-body">

							<div class="row">

								<div class="col-6">

									<div class="table-responsive pt-1">

										<table id="tabellaDati-cat" class="table table-striped" style="width:100%">
											<thead>
												<tr>
													<th>Nome categoria</th>
													<th>Descrizione</th>
													<th></th>
												</tr>
											</thead>
											<tbody></tbody>

										</table>

									</div>

								</div>


								<div class="col-6">

									<h4 class="card-title">SOTTOCATEGORIE CENSITE</h4>

									<div class="table-responsive pt-1">

										<table id="tabellaDati-sot" class="table table-striped" style="width:100%" data-source="">
											<thead>
												<tr>
													<th>Categoria di appartenenza</th>
													<th>Nome sottocategoria</th>
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

	<button type="button" id="nuova-cat" class="mdi mdi-button">NUOVA CAT.</button>
	<button type="button" id="nuova-sot" class="mdi mdi-button">NUOVA SOT.</button>


	<!-- Popup modale di modifica/inserimento CATEGORIA-->
	<div class="modal fade" id="modal-cat" tabindex="-1" role="dialog" aria-labelledby="modal-cat-label" aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modal-cat-label">Nuova categoria</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form class="forms-sample" id="form-cat">

						<div class="row">
							<div class="col-12">
								<div class="form-group">
									<label for="cat_Nome">Nome categoria</label><span style='color:red'> *</span>
									<input type="text" class="form-control form-control-sm dati-popup-modifica obbligatorio" name="cat_Nome" id="cat_Nome" autocomplete="off">
								</div>
							</div>
							<div class="col-12">
								<div class="form-group">
									<label for="cat_Descrizione">Descrizione</label>
									<input type="text" class="form-control form-control-sm dati-popup-modifica" name="cat_Descrizione" id="cat_Descrizione" autocomplete="off">
								</div>
							</div>
						</div>


						<input type="hidden" id="cat_IdCategoria_Aux" name="cat_IdCategoria_Aux" value="">
						<input type="hidden" id="cat_azione" name="cat_azione" value="nuovo">

					</form>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-success" id="salva-categoria">Salva</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
				</div>

			</div>
		</div>
	</div>


	<!-- Popup modale di modifica/inserimento SOTTOCATEGORIA -->
	<div class="modal fade" id="modal-sot" tabindex="-1" role="dialog" aria-labelledby="modal-sot-label" aria-hidden="true">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modal-sot-label">Nuova sottocategoria</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form class="forms-sample" id="form-sot">

						<div class="row">
							<div class="col-12">
								<div class="form-group">
									<label for="sot_Categoria">Categoria di appartenenza</label><span style='color:red'> *</span>
									<select class="form-control form-control-sm dati-popup-modifica selectpicker" name="sot_Categoria" id="sot_Categoria" data-live-search="true">

									</select>
								</div>
							</div>
							<div class="col-12">
								<div class="form-group">
									<label for="sot_Nome">Nome sottocategoria</label><span style='color:red'> *</span>
									<input type="text" class="form-control form-control-sm dati-popup-modifica obbligatorio" name="sot_Nome" id="sot_Nome" autocomplete="off">
								</div>
							</div>
							<div class="col-12">
								<div class="form-group">
									<label for="sot_Descrizione">Descrizione</label>
									<input type="text" class="form-control form-control-sm dati-popup-modifica" name="sot_Descrizione" id="sot_Descrizione" autocomplete="off">
								</div>
							</div>
						</div>


						<input type="hidden" id="sot_IdSottocategoria_Aux" name="sot_IdSottocategoria_Aux" value="">
						<input type="hidden" id="sot_azione" name="sot_azione" value="nuovo">

					</form>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-success" id="salva-sottocategoria">Salva</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
				</div>

			</div>
		</div>
	</div>


	<?php include("inc_js.php") ?>
	<script src="../js/categoriesottocategorie.js"></script>

</body>

</html>