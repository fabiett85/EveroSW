<?php
// in che pagina siamo
$pagina = 'prodotti';

include("../inc/conn.php");

// PRODOTTI: VISUALIZZAZIONE PRODOTTI MAGAZZINO
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'mostra') {
	// estraggo la lista
	$sth = $conn_mes->prepare(
		"SELECT * FROM prodotti AS P
		LEFT JOIN categoria_prodotti AS CP ON P.prd_Categoria = CP.cat_IdCategoria
		LEFT JOIN sottocategoria_prodotti AS SCP ON P.prd_Sottocategoria = SCP.sot_IdSottocategoria
		LEFT JOIN unita_misura AS UM ON P.prd_UnitaMisura = UM.um_IdRiga"
	);
	$sth->execute();
	$righe = $sth->fetchAll(PDO::FETCH_ASSOC);

	$output = [];
	$tipoProdotto = "";
	foreach ($righe as $riga) {
		switch ($riga['prd_Tipo']) {
			case '0':
				$tipoProdotto = "";
				break;
			case 'F':
				$tipoProdotto = "P. FINITO";
				break;
			case 'MP':
				$tipoProdotto = "MAT. PRIMA";
				break;
			case 'S':
				$tipoProdotto = 'SEMILAVORATO';
				break;
		}

		//Preparo i dati da visualizzare
		$output[] = [
			'IdProdotto' => $riga['prd_IdProdotto'],
			'Descrizione' => $riga['prd_Descrizione'],
			'IdTipo' => $riga['prd_Tipo'],
			'Tipo' => $tipoProdotto,
			'IdCategoria' => $riga['prd_Categoria'],
			'Categoria' => $riga['cat_Nome'],
			'IdSottocategoria' => $riga['prd_Sottocategoria'],
			'Sottocategoria' => $riga['sot_Nome'],
			'PezziConfezione' => number_format($riga['prd_PezziConfezione'], 2, ',', ''),
			'IdUnitaMisura' => $riga['um_IdRiga'],
			'UnitaMisura' => $riga['um_Sigla'] . " (" . $riga['um_Descrizione'] . ")",
			'FattoreMoltiplicativo' => number_format($riga['prd_FattoreMoltiplicativo'], 2, ',', ''),
			'Quantita' => $riga['prd_Quantita'],
			'azioni' => '<div class="dropdown">
					<button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Modifica riga">
					<span class="mdi mdi-lead-pencil mdi-18px"></span>
					</button>
					<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
						<a class="dropdown-item modifica-prodotto" data-id_riga="' . $riga['prd_IdProdotto'] . '"><i class="mdi mdi-account-edit"></i> Modifica</a>
						<a class="dropdown-item cancella-prodotto" data-id_riga="' . $riga['prd_IdProdotto'] . '"><i class="mdi mdi-trash-can"></i> Elimina</a>
					</div>
				</div>'
		];
	}

	die(json_encode($output));
}


// PRODOTTI: RECUPERO VALORI DEL PRODOTTO SELEZIONATO
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'recupera' && !empty($_REQUEST['codice'])) {
	// estraggo la lista
	$sth = $conn_mes->prepare(
		"SELECT prodotti.* FROM prodotti
		WHERE prodotti.prd_IdProdotto = :codice"
	);
	$sth->execute([":codice" => $_REQUEST['codice']]);
	$riga = $sth->fetch(PDO::FETCH_ASSOC);

	die(json_encode($riga));
}


// PRODOTTI: GESTIONE CANCELLAZIONE
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'cancella-prodotto' && !empty($_REQUEST['id'])) {

	$conn_mes->beginTransaction();

	try {

		// Verifico se ho ordini caricati o in esecuzione sulla macchina in oggetto
		$sthVerificaUtilizzoProdotto = $conn_mes->prepare(
			"SELECT (
				SELECT COUNT(*) FROM ordini_produzione AS ODP
				WHERE ODP.op_Prodotto = :IdProdotto AND (
					ODP.op_Stato = 3 OR ODP.op_Stato = 4
				)
			) AS OrdiniInCorso, (
				SELECT COUNT(*) FROM rientro_linea_produzione AS RLP
				LEFT JOIN ordini_produzione AS ODP ON RLP.rlp_IdProduzione = ODP.op_IdProduzione
				WHERE ODP.op_Prodotto = :IdProdotto2
			) AS OrdiniTerminati"
		);
		$sthVerificaUtilizzoProdotto->execute([
			":IdProdotto" => $_REQUEST['id'],
			":IdProdotto2" => $_REQUEST['id'],
		]);
		$rigaVerificaUtilizzoProdotto = $sthVerificaUtilizzoProdotto->fetch(PDO::FETCH_ASSOC);

		if (($rigaVerificaUtilizzoProdotto['OrdiniInCorso'] == 0) && ($rigaVerificaUtilizzoProdotto['OrdiniTerminati'] == 0)) {

			// cancellazione entry da tabella 'prodotti'
			$sthDelProdotto = $conn_mes->prepare(
				"DELETE FROM prodotti
				WHERE prd_IdProdotto = :id"
			);
			$sthDelProdotto->execute([":id" => $_REQUEST['id']]);

			// cancellazione entry da tabella 'distinta_prodotti'
			$sthDelDistinta = $conn_mes->prepare(
				"DELETE FROM distinta_prodotti_corpo
				WHERE dpc_Prodotto = :id"
			);
			$sthDelDistinta->execute([":id" => $_REQUEST['id']]);

			// cancellazione entry da tabella 'velocita_teoriche'
			$sthDelVelocita = $conn_mes->prepare(
				"DELETE FROM velocita_teoriche
				WHERE vel_IdProdotto = :id"
			);
			$sthDelVelocita->execute([":id" => $_REQUEST['id']]);

			// Eseguo commit della transazione
			$conn_mes->commit();
			die('OK');
		} else {
			die('PRODOTTO_OCCUPATO');
		}
	} catch (Throwable $t) {
		// Eseguo rollback della transazione
		$conn_mes->rollBack();
		die('ERRORE');
	}
}


// PRODOTTI: GESTIONE SALVATAGGIO DATI DA POPUP MODIFICA/INSERIMENTO
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'salva-prodotto' && !empty($_REQUEST['data'])) {

	$conn_mes->beginTransaction();
	try {

		// recupero i parametri dal POST
		$parametri = [];
		parse_str($_REQUEST['data'], $parametri);

		if (isset($parametri['prd_Sottocategoria'])) {
			$sottocategoria = (int)$parametri['prd_Sottocategoria'];
		} else {
			$sottocategoria = 0;
		}

		if (!empty($parametri['prd_Categoria'])) {
			$categoria = (int)$parametri['prd_Categoria'];
		} else {
			$categoria = 0;
		}

		// se devo modificare
		if ($parametri['azione'] == 'modifica') {
			// verifico che il codice cliente inserito, che mi funge da ID, non esista in altri record se non in quello che sto modificando
			$sth = $conn_mes->prepare(
				"SELECT * FROM prodotti
				WHERE prd_IdProdotto = :IdProdotto
				AND prd_IdProdotto != :IdRiga"
			);
			$sth->execute([
				":IdProdotto" => $parametri['prd_IdProdotto'],
				":IdRiga" => $parametri['prd_IdProdotto_Aux']
			]);
			$righeTrovate = $sth->fetch(PDO::FETCH_ASSOC);

			if (!$righeTrovate) {
				$id_modifica = $parametri['prd_IdProdotto_Aux'];

				$sthUpdate = $conn_mes->prepare(
					"UPDATE prodotti SET
					prd_IdProdotto = :IdProdotto,
					prd_Tipo = :Tipo,
					prd_Descrizione = :Descrizione,
					prd_Categoria = :Categoria,
					prd_Sottocategoria = :Sottocategoria,
					prd_PezziConfezione = :PezziConfezione,
					prd_Quantita = :Quantita,
					prd_UnitaMisura = :UnitaMisura
					WHERE prd_IdProdotto = :IdRiga"
				);
				$sthUpdate->execute([
					":IdProdotto" => $parametri['prd_IdProdotto'],
					":Tipo" => $parametri['prd_Tipo'],
					":Descrizione" => $parametri['prd_Descrizione'],
					":Categoria" => $categoria,
					":Sottocategoria" => $sottocategoria,
					":PezziConfezione" => $parametri['prd_PezziConfezione'],
					":Quantita" => $parametri['prd_Quantita'],
					":UnitaMisura" => $parametri['prd_UnitaMisura'],
					":IdRiga" => $id_modifica
				]);
			} else {
				die("Il codice inserito: " . $parametri['prd_IdProdotto'] . " è già assegnato ad un altro prodotto.");
			}
		} else // nuovo inserimento
		{

			// verifico che il codice cliente inserito, che mi funge da ID, non esista in altri record
			$sthSelect = $conn_mes->prepare(
				"SELECT * FROM prodotti
				WHERE prd_IdProdotto = :IdProdotto"
			);
			$sthSelect->execute([":IdProdotto" => $parametri['prd_IdProdotto']]);
			$trovati = $sthSelect->fetch(PDO::FETCH_ASSOC);

			if (!$trovati) {

				$sthInsert = $conn_mes->prepare(
					"INSERT INTO prodotti(prd_IdProdotto,prd_Tipo,prd_Descrizione,prd_Categoria,prd_Sottocategoria,prd_PezziConfezione,prd_Quantita,prd_UnitaMisura)
					VALUES(:IdProdotto,:Tipo,:Descrizione,:Categoria,:Sottocategoria,:PezziConfezione,:Quantita,:UnitaMisura)"
				);
				$sthInsert->execute([
					":IdProdotto" => $parametri['prd_IdProdotto'],
					":Tipo" => $parametri['prd_Tipo'],
					":Descrizione" => $parametri['prd_Descrizione'],
					":Categoria" => $parametri['prd_Categoria'],
					":Sottocategoria" => $sottocategoria,
					":PezziConfezione" => $parametri['prd_PezziConfezione'],
					":Quantita" => $parametri['prd_Quantita'],
					":UnitaMisura" => $parametri['prd_UnitaMisura']
				]);
			} else {
				die("Il codice inserito: " . $parametri['prd_IdProdotto'] . " è già assegnato ad un altro prodotto.");
			}
		}

		// Eseguo commit della transazione
		$conn_mes->commit();
		die('OK');
	} catch (Throwable $t) {
		// Eseguo rollback della transazione
		$conn_mes->rollBack();
		die('ERRORE');
	}
}



if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'importa-prodotti') {
	die(0);
}

//AUSILIARIA: POPOLAMENTO SELECT SOTTOCATEGORIA IN BASE A CATEGORIA SELEZIONATA
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'caricaSelectSottocategorie') {
	// estraggo gli eventuali prodotti aggiuntivi
	$sth = $conn_mes->prepare(
		"SELECT * FROM sottocategoria_prodotti
		WHERE sot_Categoria = :categoria"
	);
	$sth->execute([":categoria" => $_REQUEST['categoria']]);
	$sottocategorie = $sth->fetchAll(PDO::FETCH_ASSOC);
	$optionValue = "";

	//Se ho trovato sottocategorie
	if ($sottocategorie) {

		//Aggiungo ognuna delle sottocategorie trovate alla stringa che conterrà le possibili opzioni della select categorie, e che ritorno come risultato
		foreach ($sottocategorie as $sottocategoria) {

			//Se ho già una sottocategoria selezionata (provengo da popup "di modifica"), preparo il contenuto della select con l'option value corretto selezionato altrimenti preparo solo il contenuto.
			if (!empty($_REQUEST['sottocategoria']) && $_REQUEST['sottocategoria'] == $sottocategoria['sot_IdSottocategoria']) {
				$optionValue .= "<option value=" . $sottocategoria['sot_IdSottocategoria'] . " selected>" . $sottocategoria['sot_Nome'] . " </option>";
			} else {
				$optionValue .= "<option value=" . $sottocategoria['sot_IdSottocategoria'] . ">" . $sottocategoria['sot_Nome'] . " </option>";
			}
		}
		die($optionValue);
	} else {
		$optionValue = "<option value=0>ND</option>";
		die($optionValue);
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

						<div class="card-body">

							<h4 class="card-title">PRODOTTI CENSITI</h4>

							<div class="row">

								<div class="col-12">

									<div class="table-responsive pt-1">

										<table id="tabellaDati-prodotti" class="table table-striped" style="width:100%"
											data-source="prodotti.php?azione=mostra">
											<thead>
												<tr>
													<th>Id prodotto</th>
													<th>Descrizione</th>
													<th>IdTipo</th>
													<th>Tipo</th>
													<th>IdCategoria</th>
													<th>Categoria</th>
													<th>IdSottocategoria</th>
													<th>Sottocategoria</th>
													<th>IdUdm</th>
													<th>Udm</th>
													<th>N° pz conf.</th>
													<th>Quantità</th>
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

	<button type="button" id="nuovo-prodotto" class="mdi mdi-button">NUOVO PRODOTTO</button>


	<!-- Opup modale di modifica/inserimento prodotto-->
	<div class="modal fade" id="modalProdotti" tabindex="-1" role="dialog" aria-labelledby="modalProdottiLabel"
		aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modalProdottiLabel">Nuovo prodotto</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form class="forms-sample" id="form-prodotti">

						<div class="row">
							<div class="col-6">
								<div class="form-group">
									<label for="prd_IdProdotto">Identificativo</label><span style='color:red'> *</span>
									<input type="text" class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="prd_IdProdotto" id="prd_IdProdotto" autocomplete="off" required>
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="prd_Tipo">Tipo</label><span style='color:red'> *</span>
									<select class="form-control form-control-sm dati-popup-modifica	selectpicker" name="prd_Tipo"
										id="prd_Tipo" data-live-search="true" required>
										<option value="MP">MATERIA PRIMA</option>
										<option value="S">SEMILAVORATO</option>
										<option value="F">PRODOTTO FINITO</option>
									</select>
								</div>
							</div>
							<div class="col-12">
								<div class="form-group">
									<label for="prd_Descrizione">Descrizione</label><span style='color:red'> *</span>
									<input type="text" class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="prd_Descrizione" id="prd_Descrizione" autocomplete="off" required>
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="prd_Categoria">Categoria</label>
									<select class="form-control form-control-sm dati-popup-modifica selectpicker" name="prd_Categoria"
										id="prd_Categoria">
										<?php
										$sth = $conn_mes->prepare(
											"SELECT * FROM categoria_prodotti"
										);
										$sth->execute();
										$categorie = $sth->fetchAll(PDO::FETCH_ASSOC);
										foreach ($categorie as $categoria) {
											echo "<option value='" . $categoria['cat_IdCategoria'] . "'>" . $categoria['cat_Nome'] . "</option>";
										}
										?>
									</select>
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="prd_Sottocategoria">Sottocategoria</label>
									<select class="form-control form-control-sm dati-popup-modifica selectpicker"
										name="prd_Sottocategoria" id="prd_Sottocategoria" autocomplete="off">

									</select>
								</div>
							</div>


							<div class="col-4" hidden>
								<div class="form-group">
									<label for="prd_Quantita">Quantità</label>
									<input type="number" class="form-control form-control-sm dati-popup-modifica" name="prd_Quantita"
										id="prd_Quantita" placeholder="Quantità" autocomplete="off">
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="prd_UnitaMisura">Unità di misura</label><span style='color:red'> *</span>
									<select class="form-control form-control-sm dati-popup-modifica selectpicker" name="prd_UnitaMisura"
										id="prd_UnitaMisura" required>
										<?php
										$sth = $conn_mes->prepare(
											"SELECT * FROM unita_misura"
										);
										$sth->execute();
										$trovate = $sth->fetchAll(PDO::FETCH_ASSOC);
										foreach ($trovate as $udm) {
											echo "<option value='" . $udm['um_IdRiga'] . "'>" . $udm['um_Sigla'] . "</option>";
										}
										?>
									</select>
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="prd_PezziConfezione">Pezzi confezione</label>
									<input name="prd_PezziConfezione" id="prd_PezziConfezione" type="number"
										class="form-control form-control-sm dati-popup-modifica" autocomplete="off">
								</div>
							</div>

						</div>

						<input type="hidden" id="prd_IdProdotto_Aux" name="prd_IdProdotto_Aux" value="">
						<input type="hidden" id="azione" name="azione" value="nuovo">

					</form>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-success" id="salva-prodotto">Salva</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
				</div>

			</div>
		</div>
	</div>

	<!-- Opup modale di modifica/inserimento prodotto-->
	<div class="modal fade" id="modal-import" tabindex="-1" role="dialog" aria-labelledby="modal-import-label"
		aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modal-import-label">Importazione da CSV</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form class="forms-sample" id="form-importazione" name="form-importazione" enctype="multipart/form-data"
						method="post">

						<div class="row">
							<div class="col-12">
								<div class="form-group">
									<label for="file">File</label><span style='color:red'> *</span>
									<input class='form-control form-control-sm dati-popup-modifica' type="file" name="file" id="file"
										accept=".xls,.xlsx">
								</div>
							</div>


						</div>
					</form>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-success" id="importa-prodotti">Importa</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
				</div>

			</div>
		</div>
	</div>


	<?php include("inc_js.php") ?>
	<script src="../js/prodotti.js"></script>

</body>

</html>