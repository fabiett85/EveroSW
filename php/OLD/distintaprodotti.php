<?php
// in che pagina siamo
$pagina = 'distintaprodotti';

include('../inc/conn.php');



// D. COMPONENTI: RECUPERO INFORMAZIONI PRODOTTO FINITO SELEZIONATO
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'recuperaP') {

	if (!empty($_REQUEST['idProdotto'])) {
		// estraggo la riga relativa alla distinta selezionata
		$sthRecuperaDistinta = $conn_mes->prepare(
			"SELECT prodotti.* FROM prodotti
			WHERE (prodotti.prd_Tipo = 'F' OR prodotti.prd_Tipo = 'S')
			AND prodotti.prd_IdProdotto = :IdProdotto"
		);
		$sthRecuperaDistinta->execute([':IdProdotto' => $_REQUEST['idProdotto']]);
		$riga = $sthRecuperaDistinta->fetch(PDO::FETCH_ASSOC);

		die(json_encode($riga));
	} else {
		die('NO_ROWS');
	}
}



// D. COMPONENTI: VISUALIZZAZIONE DETTAGLIO DISTINTA
if (!empty($_REQUEST['azione'])  && $_REQUEST['azione'] == 'mostra-dpc' && !empty($_REQUEST['idProdotto'])) {
	$sth = $conn_mes->prepare(
		"SELECT distinta_prodotti_corpo.*, prodotti.prd_Descrizione AS DescrizioneProdotto, unita_misura.*
		FROM distinta_prodotti_corpo
		LEFT JOIN prodotti ON distinta_prodotti_corpo.dpc_Componente = prodotti.prd_IdProdotto
		LEFT JOIN unita_misura ON distinta_prodotti_corpo.dpc_Udm = unita_misura.um_IdRiga
		WHERE dpc_Prodotto = :IdProdotto
		ORDER BY prd_Descrizione ASC"
	);
	$sth->execute([':IdProdotto' => $_REQUEST['idProdotto']]);
	$righe = $sth->fetchAll(PDO::FETCH_ASSOC);



	$output = [];

	foreach ($righe as $riga) {
		//Preparo i dati da visualizzare
		$output[] = [
			'Componente' => $riga['DescrizioneProdotto'],
			'CodiceComponente' => $riga['dpc_Componente'],
			'UnitaDiMisura' => $riga['um_Sigla'] . ' (' . $riga['um_Descrizione'] . ')',
			'FattoreMoltiplicativo' => $riga['dpc_FattoreMoltiplicativo'],
			'PezziConfezione' => $riga['dpc_PezziConfezione'],
			'azioni' => '<div class="dropdown">
					<button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Modifica riga">
					<span class="mdi mdi-lead-pencil mdi-18px"></span>
					</button>
					<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
						<a class="dropdown-item modifica-dpc" data-id-prodotto="' . $_REQUEST['idProdotto'] . '" data-id-componente="' . $riga['dpc_Componente'] . '"><i class="mdi mdi-account-edit"></i> Modifica</a>
						<a class="dropdown-item cancella-dpc" data-id-prodotto="' . $_REQUEST['idProdotto'] . '" data-id-componente="' . $riga['dpc_Componente'] . '"><i class="mdi mdi-trash-can"></i> Elimina</a>
					</div>
				</div>'
		];
	}

	die(json_encode(['data' => $output]));
	exit();
}



// D. COMPONENTI: CANCELLAZIONE COMPONENTE DA DISTINTA IN OGGETTO
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'cancella-dpc' && !empty($_REQUEST['idProdotto']) && !empty($_REQUEST['idComponente'])) {
	// query di eliminazione della risorsa dalla distinta
	$sthDeleteDettaglio = $conn_mes->prepare(
		"DELETE FROM distinta_prodotti_corpo
		WHERE dpc_Prodotto = :IdProdotto
		AND dpc_Componente = :IdComponente"
	);
	$sthDeleteDettaglio->execute([
		':IdProdotto' => $_REQUEST['idProdotto'],
		':IdComponente' => $_REQUEST['idComponente']
	]);

	die('OK');
}



// D. COMPONENTI: RECUPERO INFORMAZIONI SU COMPONENTE DISTINTA IN OGGETTO
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'recupera-dpc' && !empty($_REQUEST['idComponente'])  && !empty($_REQUEST['idProdotto'])) {
	// recupero i dati del dettaglio distinta selezionato
	$sthRecuperaDettaglio = $conn_mes->prepare(
		"SELECT * FROM distinta_prodotti_corpo
		WHERE dpc_Prodotto = :IdProdotto
		AND dpc_Componente = :IdComponente"
	);
	$sthRecuperaDettaglio->execute([':IdComponente' => $_REQUEST['idComponente'], ':IdProdotto' => $_REQUEST['idProdotto']]);
	$riga = $sthRecuperaDettaglio->fetch(PDO::FETCH_ASSOC);

	die(json_encode($riga));
}



// D. COMPONENTI: SALVATAGGIO DI MODIFICA/INSERIMENTO COMPONENTE SU DISTINTA IN OGGETTO
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'salva-dpc'  && !empty($_REQUEST['data'])) {
	// recupero i parametri dal POST
	$parametri = [];
	parse_str($_REQUEST['data'], $parametri);

	// se devo modificare
	if ($parametri['dpc_Azione'] == 'modifica') {

		$sthUpdate = $conn_mes->prepare(
			"UPDATE distinta_prodotti_corpo SET
			dpc_Udm = :UnitaDiMisura,
			dpc_FattoreMoltiplicativo = :FattoreMoltiplicativo,
			dpc_PezziConfezione = :PezziConfezione
			WHERE dpc_Prodotto = :IdProdotto
			AND dpc_Componente = :Componente"
		);
		$sthUpdate->execute([
			':UnitaDiMisura' => $parametri['dpc_Udm'],
			':FattoreMoltiplicativo' => $parametri['dpc_FattoreMoltiplicativo'],
			':PezziConfezione' => $parametri['dpc_PezziConfezione'],
			':IdProdotto' => $parametri['dpc_Prodotto'],
			':Componente' => $parametri['dpc_Componente']
		]);
		die('OK');
	} else // nuovo inserimento
	{

		// verifico che nella distinta del prodotto in oggetto, non esista già il componente che sto cercando di inserire
		$sthSelect = $conn_mes->prepare(
			"SELECT distinta_prodotti_corpo.*
			FROM distinta_prodotti_corpo
			WHERE dpc_Componente = :Componente
			AND dpc_Prodotto = :IdProdotto"
		);
		$sthSelect->execute([
			':Componente' => $parametri['dpc_Componente'],
			':IdProdotto' => $parametri['dpc_Prodotto']
		]);
		$trovati = $sthSelect->fetch(PDO::FETCH_ASSOC);

		if (!$trovati) {

			$sqlInsert = 'INSERT INTO distinta_prodotti_corpo(dpc_Prodotto,dpc_Componente,dpc_Udm,dpc_FattoreMoltiplicativo,dpc_PezziConfezione) VALUES(:IdProdotto,:Componente,:UnitaDiMisura,:FattoreMoltiplicativo,:PezziConfezione)';
			$sthInsert = $conn_mes->prepare($sqlInsert);
			$sthInsert->execute([
				':IdProdotto' => $parametri['dpc_Prodotto'],
				':Componente' => $parametri['dpc_Componente'],
				':FattoreMoltiplicativo' => $parametri['dpc_FattoreMoltiplicativo'],
				':PezziConfezione' => $parametri['dpc_PezziConfezione'],
				':UnitaDiMisura' => $parametri['dpc_Udm']
			]);
			die('OK');
		} else {
			die('Il componente inserito ' . $parametri['dpc_Componente'] . ' fa già parte della distinta. Inserirne un altro e riprovare. ');
		}
	}
}



// AUSILIARIA: POPOLAMENTO SELECT 'COMPONENTI DISPONIBILI'
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'caricaSelectComponenti' && !empty($_REQUEST['idProdotto'])) {
	// estraggo i prodotti disponibili che non fanno ancora parte della distinta per quel prodotto
	if (empty($_REQUEST['componente'])) {
		$sth = $conn_mes->prepare(
			"SELECT prodotti.* FROM prodotti
			WHERE prodotti.prd_Tipo != 'F'
			AND prodotti.prd_IdProdotto NOT IN (
				SELECT distinta_prodotti_corpo.dpc_Componente FROM distinta_prodotti_corpo
				WHERE distinta_prodotti_corpo.dpc_Prodotto = :idProdotto
			)"
		);
		$sth->execute([':idProdotto' => $_REQUEST['idProdotto']]);
	} else {
		$sth = $conn_mes->prepare(
			"SELECT prodotti.* FROM prodotti
			WHERE prodotti.prd_Tipo != 'F'
			AND (
				prodotti.prd_IdProdotto NOT IN (
					SELECT distinta_prodotti_corpo.dpc_Componente FROM distinta_prodotti_corpo
					WHERE distinta_prodotti_corpo.dpc_Prodotto = :idProdotto
				)
				OR prodotti.prd_IdProdotto = :ComponenteSelezionato
			)"
		);
		$sth->execute([
			':idProdotto' => $_REQUEST['idProdotto'],
			':ComponenteSelezionato' => $_REQUEST['componente']
		]);
	}
	$componentiDisponibili = $sth->fetchAll(PDO::FETCH_ASSOC);
	$optionValue = "";

	//Se ho trovato prodotti disponibili
	if ($componentiDisponibili) {

		//Aggiungo ognuno dei prodotti trovati alla select
		foreach ($componentiDisponibili as $componente) {
			if (!empty($_REQUEST['componente']) && $_REQUEST['componente'] == $componente['prd_IdProdotto']) {
				$optionValue = $optionValue . "<option value='" . $componente['prd_IdProdotto'] . "' selected >" . $componente['prd_Descrizione'] . ' (' . $componente['prd_IdProdotto'] . ') </option>';
			} else {
				$optionValue = $optionValue . "<option value='" . $componente['prd_IdProdotto'] . "'>" . $componente['prd_Descrizione'] . ' (' . $componente['prd_IdProdotto'] . ') </option>';
			}
		}
		die($optionValue);
		exit();
	} else {
		die('NO_CMP');
		exit();
	}
}


?>



<!DOCTYPE html>
<html lang="it">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>PROSPECT40 | Compilazione distinte</title>
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

							<form class="" id="form-dati-prodotto-finito">

								<!-- Visualizzazione distinte prodotto presenti e dati di quella selezionata -->
								<div class="row">
									<div class="col-8">
										<h4 class="card-title mx-2 my-2">COMPILAZIONE DISTINTA BASE</h4>
									</div>
									<div class="col-4">
										<!-- ELENCO PRODOTTI FINITI -->
										<div class="form-group m-0">
											<label for="dp_ProdottoSelezionato">Elenco prodotti</label>
											<select class="form-control form-control-sm selectpicker" id="dp_ProdottoSelezionato" name="dp_ProdottoSelezionato" data-live-search="true" required>
												<?php
												$sth = $conn_mes->prepare(
													"SELECT prodotti.prd_IdProdotto, prodotti.prd_Descrizione
													FROM prodotti
													WHERE prodotti.prd_Tipo = 'F' OR prodotti.prd_Tipo = 'S'
													ORDER BY prodotti.prd_Descrizione ASC"
												);
												$sth->execute();
												$prodotti = $sth->fetchAll(PDO::FETCH_ASSOC);
												if ($prodotti) {
													foreach ($prodotti as $prodotto) {
														echo "<option value='" . $prodotto['prd_IdProdotto'] . "'>" . $prodotto['prd_Descrizione'] . "</option>";
													}
												} else {
													echo "<option value=''>Nessun prodotto definito</option>";
												}
												?>
											</select>
										</div>
									</div>

								</div>

							</form>
						</div>
						<div class="card-body">





							<!-- Visualizzazione dettaglio della distinta risorse selezionata -->
							<div class="row mt-1">

								<div class="col-12">

									<div class="table-responsive">

										<table id="tabellaDati-DPC" class="table table-striped" data-source="distintaprodotti.php?azione=mostraDPC">
											<thead>
												<tr>
													<th>Componente</th>
													<th>Cod. componente</th>
													<th>Unità di misura</th>
													<th>Coeff. moltipl.</th>
													<th>Pz confezione</th>
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

	<!-- Pulsante aggiunta nuova risorsa alla distinta -->
	<button type="button" id="aggiungi-componente" class="mdi mdi-button">AGGIUNGI ELEMENTO</button>



	<!-- Popup modale di aggiunta componente alla distinta selezioanta-->
	<div class="modal fade" id="modal-nuovo-componente" tabindex="-1" role="dialog" aria-labelledby="modalNuovoComponenteLabel" aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modal-nuovo-componente-label">AGGIUNTA COMPONENTE DISTINTA BASE</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form class="forms-sample" id="form-nuovo-componente">

						<div class="row">
							<div class="col-12">

								<div class="form-group">
									<label for="dpc_NomeProdotto">Prodotto finito</label>
									<input type="text" class="form-control form-control-sm dati-popup-modifica" name="dpc_NomeProdotto" id="dpc_NomeProdotto" autocomplete="off" readonly>
								</div>
							</div>
							<div class="col-12">
								<div class="form-group">
									<label for="dpc_Componente">Componente</label><span style='color:red'> *</span>
									<select class="form-control form-control-sm dati-popup-modifica selectpicker" id="dpc_Componente" name="dpc_Componente" data-live-search="true">

									</select>
								</div>
							</div>

							<div class="col-4">
								<div class="form-group">
									<label for="dpc_Udm">Unità di misura</label><span style='color:red'> *</span>
									<select class="form-control form-control-sm dati-popup-modifica selectpicker" name="dpc_Udm" id="dpc_Udm">
										<?php
										$sth = $conn_mes->prepare("SELECT * FROM unita_misura");
										$sth->execute();
										$trovate = $sth->fetchAll(PDO::FETCH_ASSOC);
										foreach ($trovate as $udm) {
											echo "<option value='" . $udm['um_IdRiga'] . "'>" . $udm['um_Sigla'] . "</option>";
										}
										?>
									</select>
								</div>
							</div>

							<div class="col-4">
								<div class="form-group">
									<label for="dpc_FattoreMoltiplicativo">Coeff. Moltipl.</label><span style='color:red'> *</span>
									<input type="number" class="form-control form-control-sm dati-popup-modifica obbligatorio" name="dpc_FattoreMoltiplicativo" id="dpc_FattoreMoltiplicativo" autocomplete="off">
								</div>
							</div>

							<div class="col-4">
								<div class="form-group">
									<label for="dpc_PezziConfezione">Pz confezione</label><span style='color:red'> *</span>
									<input type="number" class="form-control form-control-sm dati-popup-modifica obbligatorio" name="dpc_PezziConfezione" id="dpc_PezziConfezione" autocomplete="off">
								</div>
							</div>
						</div>

						<input type="hidden" id="dpc_Prodotto" name="dpc_Prodotto" value="">
						<input type="hidden" id="dpc_NumeroRiga" name="dpc_NumeroRiga" value="">
						<input type="hidden" id="dpc_Azione" name="dpc_Azione" value="nuovo">

					</form>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-success" id="salva-dpc">Salva</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
				</div>

			</div>
		</div>
	</div>


	<?php include("inc_js.php") ?>
	<script src="../js/distintaprodotti.js"></script>

</body>

</html>