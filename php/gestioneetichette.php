<?php
// in che pagina siamo
$pagina = 'gestioneEtichette';
include("../inc/conn.php");

// debug($_SESSION['utente'],'Utente');

//VISUALIZZAZIONE ESL 
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'mostra') {
	// estraggo la lista
	$sth = $conn_mes->prepare("SELECT Cantina.*, Contenitori.Nome AS Nome_contenitore 
								FROM Cantina 
								LEFT JOIN Contenitori ON Contenitori.Codice_contenitore = Cantina.Numero_serbatoio
								ORDER BY Codice_contenitore_cliente ASC", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
	$sth->execute();
	$righe = $sth->fetchAll(PDO::FETCH_ASSOC);

	$output = [];

	$marked = '<div class="d-flex justify-content-center align-items-center" style="max-height:18px"><i class="mdi mdi-checkbox-marked mdi-18px"></i></div>';
	$unmarked = '<div class="d-flex justify-content-center align-items-center" style="max-height:18px"><span class="mdi mdi-checkbox-blank-outline"></span></div>';


	foreach ($righe as $riga) {
		//Preparo i dati da visualizzare
		$output[] = array(		
			"IdRiga" => trim($riga["Id"]),		
			"CodiceEsl" => trim($riga["Codice_etichetta"]),
			"TipoESl" => trim($riga["Tipo_etichetta"]),
			"AbiEsl" => ($riga["Abilitazione"] ? $marked : $unmarked),
			"Serbatoio" => trim($riga["Nome_contenitore"]),
			"Campo1" => trim($riga["ESL_campo_1"]),
			"Campo2" => trim($riga["ESL_campo_2"]),	
			"Campo3" => trim($riga["ESL_campo_3"]),
			"Campo4" => trim($riga["ESL_campo_4"]),
			"Campo5" => trim($riga["ESL_campo_5"]),
			"Campo6" => trim($riga["ESL_campo_6"]),
			"Campo7" => trim($riga["ESL_campo_7"]),
			"Campo8" => trim($riga["ESL_campo_8"]),
			"Campo9" => trim($riga["ESL_campo_9"]),		
			"Campo10" => trim($riga["ESL_campo_10"]),
			'azioni' => '<div class="dropdown">
					<button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Modifica riga">
					<span class="mdi mdi-lead-pencil mdi-18px"></span>
					</button>
					<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
						<a class="dropdown-item modifica-risorsa" data-id_riga="' . $riga['Id'] . '"><i class="mdi mdi-account-edit"></i> Modifica</a>
						<a class="dropdown-item cancella-risorsa" data-id_riga="' . $riga['Id'] . '"><i class="mdi mdi-trash-can"></i> Elimina</a>
					</div>
				</div>'
		);
	}

	die(json_encode($output));
}


// RISORSE: RECUPERO VALORI DELLA RISORSA SELEZIONATA
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'recupera' && !empty($_REQUEST['codice'])) {
	// estraggo la lista
	$sth = $conn_mes->prepare("SELECT * FROM risorse WHERE ris_IdRisorsa = :codice", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
	$sth->execute([':codice' => $_REQUEST['codice']]);
	$riga = $sth->fetch(PDO::FETCH_ASSOC);

	die(json_encode($riga));
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
							<div class="row">
								<div class="col-9">
									<h4 class="card-title m-2">GESTIONE LAVAGNE ELETTRONICHE</h4>
								</div>
								<div class="col-3 d-flex justify-content-between">
									<button type="button" title="Aggiorna tabella" id="btn-update-tabella" class="btn btn-primary btn-comandi-lav mdi mdi-table my-auto mx-2"></button>
									<button type="button" title="Aggiorna lavagne" id="btn-update-lavagne" class="btn btn-primary btn-comandi-lav mdi mdi-access-point my-auto mx-2"></button>
									<button type="button" title="Stampa" class="btn btn-primary btn-comandi-lav mdi mdi-printer my-auto mx-2"></button>
								</div>
							</div>
						</div>
						<div class="card-body">


							<div class="row">

								<div class="col-12">
									<div class="table-responsive pt-1">

										<table id="tabella-esl" class="table table-striped" style="width:100%"
											data-source="gestioneetichette.php?azione=mostra">
											<thead>
												<tr>
													<th>IdRiga</th>
													<th>Id</th>
													<th>Tipo</th>
													<th>Abilitazione</th>
													<th>Recipiente</th>
													<th>Cap (Hl)</th>
													<th>Categoria</th>
													<th>Nome prodotto</th>
													<th>Atto/Certificato</th>
													<th>Classificazione</th>
													<th>Anno</th>
													<th>Colore</th>
													<th>Stato</th>
													<th>Bio</th>
													<th>Menzioni</th>		
													<th></th>
												</tr>
											</thead>
											<tbody>
											</tbody>

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

	<button type="button" id="nuova-esl" class="mdi mdi-button">NUOVA LAVAGNA</button>




	<?php include("inc_js.php") ?>
	<script src="../js/gestioneetichette.js"></script>

</body>

</html>