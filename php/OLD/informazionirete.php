<?php
// in che pagina siamo
$pagina = "dashboard";

include("../inc/conn.php");

// LINEE: VISUALIZZAZIONE LINEE CENSITE
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "mostra-indirizzi") {
	// Recupero elenco delle linee definite
	$sth = $conn_mes->prepare(
		"SELECT configurazione_rete.*
		FROM configurazione_rete"
	);
	$sth->execute();
	$righe = $sth->fetchAll(PDO::FETCH_ASSOC);

	$output = [];

	foreach ($righe as $riga) {
		//Preparo i dati da visualizzare
		$output[] = [
			"IdRisorsa" => $riga["cr_IdRisorsa"],
			"DescrizioneRisorsa" => $riga["cr_DescrizioneRisorsa"],
			"Indirizzo1Risorsa" => $riga["cr_IndirizzoIP_1"],
			"NoteRisorsa" => $riga["cr_Note"],
		];
	}

	die(json_encode($output));
}

?>
<!DOCTYPE html>
<html lang="it">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>PROSPECT40 | Dashboard</title>
	<?php include("inc_css.php") ?>
</head>

<body class="<?= $classe_body_zoom ?>" <?= $data_body_zoom ?>>

	<div class="container-scroller">

		<?php include("inc_testata.php") ?>

		<div class="container-fluid page-body-wrapper">

			<div class="main-panel">

				<div class="content-wrapper">

					<div class="card">
						<div class="card-header">
							<h4 class="card-title m-2">INDIRIZZAMENTO DISPOSITIVI INTERCONNESSI</h4>
						</div>

						<div class="card-body">


							<div class="table-responsive pt-1">

								<table id="tabellaDati-indirizzi" class="table table-striped" style="width:100%" data-source="informazionirete.php?azione=mostra-indirizzi">
									<thead>
										<tr>
											<th>Id macchina</th>
											<th>Descrizione macchina</th>
											<th>Indirizzo IP</th>
											<th>Note</th>
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

	</div>

	<?php include("inc_js.php") ?>
	<script src="../js/informazionirete.js"></script>

</body>

</html>