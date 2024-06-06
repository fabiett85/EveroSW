<?php

include("../inc/conn.php");
$pagina = $_REQUEST['pagina'];


$sth = $conn_mes->prepare(
	"SELECT TABLE_NAME
	FROM INFORMATION_SCHEMA.TABLES"
);
$sth->execute();
$tabelle = $sth->fetchAll(PDO::FETCH_COLUMN, 0);

$strutture = [];

foreach ($tabelle as $tabella) {
	$nome = ucwords(str_replace('_', ' ', $tabella));


	$sth = $conn_mes->prepare(
		"SELECT COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE
		WHERE CONSTRAINT_NAME LIKE 'PK%' AND TABLE_NAME = :Tabella"
	);
	$sth->execute(['Tabella' => $tabella]);
	$chiavi = $sth->fetchAll(PDO::FETCH_COLUMN, 0);

	$sth = $conn_mes->prepare(
		"SELECT COLUMN_NAME, DATA_TYPE
		FROM INFORMATION_SCHEMA.COLUMNS
		WHERE TABLE_NAME = :Tabella
		ORDER BY ORDINAL_POSITION"
	);
	$sth->execute(['Tabella' => $tabella]);
	$colonne = $sth->fetchAll(PDO::FETCH_ASSOC);

	$campi = [];

	foreach ($colonne as $colonna) {
		$strArray = explode('_', $colonna['COLUMN_NAME']);
		$campi[$colonna['COLUMN_NAME']] = [
			'label' => count($strArray) >= 2 ? $strArray[1] : $strArray[0],
			'tipo' => $colonna['DATA_TYPE']
		];
	}

	$strutture[$tabella] = [
		'titolo_pagina' => $nome,
		'titolo_modale' => $nome,
		'tabella' => $tabella,
		'sql_elenco' => "SELECT * FROM " . $tabella,
		'chiavi' => $chiavi,
		'ammetti_nuovo' => true,
		'campi' => $campi
	];
}


// GRIGLIA
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'elenco') {
	// estraggo la lista
	$sth = $conn_mes->prepare($strutture[$pagina]['sql_elenco']);
	$sth->execute();
	$righe = $sth->fetchAll(PDO::FETCH_ASSOC);
	$chiaviEValori = [];
	$chiavi = $strutture[$pagina]['chiavi'];



	$contatore = 0;
	$output = [];
	foreach ($righe as $riga) {
		$questa_riga = [];
		foreach ($strutture[$pagina]['campi'] as $chiave => $dati) {

			$valore = $riga[$chiave];
			if ($dati['tipo'] == 'date') {
				$valore = date_format(new DateTime($riga[$chiave]), 'd/m/Y');
			}
			if ($dati['tipo'] == 'datetime') {
				$valore = date_format(new DateTime($riga[$chiave]), 'd/m/Y H:i:s');
			}
			$questa_riga[] = $valore;
		}
		foreach ($chiavi as $chiave) {
			$chiaviEValori[$chiave] = $riga[$chiave];
		}
		$questa_riga[] =
			'<a href="manutenzionearchivi.php?pagina=' . $pagina . '&azione=modifica&chiavi=' . base64_encode(json_encode($chiaviEValori)) . '" class="modifica-riga">
			<i class="mdi mdi-pencil mdi-24px"></i>
		</a>
		<a href="manutenzionearchivi.php?pagina=' . $pagina . '&azione=cancella&chiavi=' . base64_encode(json_encode($chiaviEValori)) . '" class="cancella-riga">
			<i class="mdi mdi-trash-can mdi-24px"></i>
		</a>';
		$output[] = $questa_riga;
		$contatore++;
	}


	die(json_encode(array('data' => $output)));
}

if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'modifica') {
	$chiavi = json_decode(base64_decode($_REQUEST['chiavi']));


	$condizioni = $parametri = array();
	foreach ($chiavi as $key => $val) {
		$condizioni[] = $key . " = :" . $key;
		$parametri[":" . $key] = $val;
	}
	$stringa_condizioni = implode(" AND ", $condizioni);

	$sth = $conn_mes->prepare('SELECT * FROM ' . $strutture[$pagina]['tabella'] . ' WHERE ' . $stringa_condizioni);
	$sth->execute($parametri);
	$riga = $sth->fetch(PDO::FETCH_ASSOC);

	die(json_encode($riga));
}


// GRIGLIA
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'cancella') {
	$chiavi = json_decode(base64_decode($_REQUEST['chiavi']));


	$sqlDelete = "DELETE FROM " . $pagina . " WHERE ";
	foreach ($chiavi as $key => $value) {
		$sqlDelete = $sqlDelete . $key . "='" . $value . "' AND ";
	}
	$sqlDelete = $sqlDelete . "''=''";

	$sth = $conn_mes->prepare($sqlDelete);
	$sth->execute();

	die('OK');
}


// MODIFICA E NUOVO INSERIMENTO: SALVATAGGIO DATI
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'salva') {
	unset($_POST['azione']);

	$update = $_POST['update'] == 'true';
	unset($_POST['update']);


	$chiavi = [];
	foreach ($strutture[$pagina]['chiavi'] as $chiave) {
		$chiavi[$chiave] = $_POST[$chiave . '_old'];
		unset($_POST[$chiave . '_old']);
	}

	if ($update) {

		$sqlUpdate = "UPDATE " . $pagina . " SET ";

		foreach ($chiavi as $key => $value) {
			unset($_POST[$key]);
		}

		foreach ($_POST as $key => $value) {
			$sqlUpdate = $sqlUpdate . $key . "=:" . $key . ", ";
			$_POST[$key] = $value == '' ? null : $value;
		}
		$sqlUpdate = substr($sqlUpdate, 0, -2);
		$sqlUpdate = $sqlUpdate . " WHERE ";
		foreach ($chiavi as $key => $value) {
			$sqlUpdate = $sqlUpdate . $key . "='" . $value . "' AND ";
		}
		$sqlUpdate = substr($sqlUpdate, 0, -5);

		$sth = $conn_mes->prepare($sqlUpdate);
		$sth->execute($_POST);
	} else {

		$sqlInsert = "INSERT INTO " . $pagina . '(';
		foreach ($_POST as $key => $value) {
			$sqlInsert = $sqlInsert . $key . ',';
		}
		$sqlInsert = substr($sqlInsert, 0, -1) . ") VALUES(";
		foreach ($_POST as $key => $value) {
			$sqlInsert = $sqlInsert . ":" . $key . ',';
		}
		$sqlInsert = substr($sqlInsert, 0, -1) . ')';

		$sth = $conn_mes->prepare($sqlInsert);
		$sth->execute($_POST);
	}


	die('OK');
}

?>
<!DOCTYPE html>
<html lang="it">

<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Prospect 40 | <?= $strutture[$pagina]['titolo_pagina'] ?></title>
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
							<h4 class="card-title m-2"><?= strtoupper($strutture[$pagina]["titolo_pagina"]) ?></h4>
						</div>
						<div class="card-body">


							<div class="row">

								<div class="col-12">

									<div class="table-responsive">

										<table id="tabella" class="table table-striped"
											data-source="manutenzionearchivi.php?pagina=<?= $pagina ?>&azione=elenco" style="width: 100%;">
											<thead>
												<tr>
													<?php
													foreach ($strutture[$pagina]["campi"] as $campo => $dati) {
														echo "<th>" . $campo . "</th>";
													} ?>
													<th style="width:5%">Azioni</th>
												</tr>
											</thead>
											<tbody></tbody>

										</table>

									</div>
								</div>
							</div>

							<?php if (!empty($strutture[$pagina]["ammetti_nuovo"])) { ?>
							<button type="button" id="apriModaleNuovo" data-toggle="modal" data-target="#modalDettaglio"
								class="btn btn-primary d-inline-block">NUOVO</button>
							<?php } ?>

						</div>
					</div>
				</div>

				<?php include("inc_footer.php") ?>

			</div>

		</div>

	</div>

	<!-- Popup modale di modifica/inserimento prodotto-->
	<div class="modal fade" id="modalDettaglio" tabindex="-1" role="dialog" aria-hidden="true" data-keyboard="false">
		<div class="modal-dialog modal-lg" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="modalProdottiLabel"><?= $strutture[$pagina]["titolo_modale"] ?></h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form id="form">

						<?php
						foreach ($strutture[$pagina]['chiavi'] as $chiave) {
						?>
						<input type="hidden" id="<?= $chiave . '_old' ?>" name="<?= $chiave . '_old' ?>" value="">
						<?php
						}
						?>
						<input type="hidden" id="azione" name="azione" value="salva">
						<input type="hidden" id="update" name="update" value="">

						<div class="row">
							<?php foreach ($strutture[$pagina]["campi"] as $campo => $dati) { ?>
							<div class="col-<?= empty($dati["colonne"]) ? "3" : $dati["colonne"] ?>">
								<div class="form-group">
									<label for="<?= $campo ?>"><?= $dati["label"] ?>
										<?php if (in_array($campo, $strutture[$pagina]['chiavi'])) {
												echo "<span style='color:red'>*</span>";
											} ?></label>

									<?php
										if ($dati["tipo"] == "select") {
										?>
									<select class="select2 form-control
										<?php if (in_array($campo, $strutture[$pagina]['chiavi'])) {
												echo "obbligatorio";
											} ?>" name="<?= $campo ?>" id="<?= $campo ?>">
										<option value="">Seleziona...</option>
										<?php
												if (!empty($dati["opzioni_possibili"])) {
													foreach ($dati["opzioni_possibili"] as $opzione) {
												?>
										<option><?= $opzione ?></option>
										<?php
													}
												} elseif (!empty($dati["query"])) {
													$sth = $conn_mes->prepare($dati["query"]);
													$sth->execute();
													$opzioni = $sth->fetchAll(PDO::FETCH_ASSOC);
													foreach ($opzioni as $opzione) {
													?>
										<option value="<?= $opzione["valore"] ?>"><?= $opzione["testo"] ?></option>
										<?php
													}
												}
												?>
									</select>

									<?php
										} elseif ($dati["tipo"] == "radio") {
											$valori_possibili = explode("|", $dati["valori_possibili"]);
											foreach ($valori_possibili as $valore_possibile) {
												$valore = explode(":", $valore_possibile);
												$valore_radio = $valore[0];
												$testo_radio = $valore[1];
											?>
									<label class="radio"><input type="radio" name="<?= $campo ?>" value="<?= $valore_radio ?>"
											id="cb_<?= $campo ?>_<?= $valore_radio ?>"><?= $testo_radio ?></label>
									<?php
											}
										} else {
											?>
									<input type="<?= $dati["tipo"] ?>" <?php if ($dati["tipo"] == "time") {
																														echo "step='1'";
																													} ?> class="form-control <?php if (in_array($campo, $strutture[$pagina]['chiavi'])) {
																																											echo "obbligatorio";
																																										} ?>" name="<?= $campo ?>" id="<?= $campo ?>">
									<?php
										}
										?>
									<small class="errore-campo" data-per="<?= $campo ?>" hidden>Valorizza questo campo</small>
								</div>
							</div>
							<?php } ?>
						</div>




					</form>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-success" id="salva">Salva</button>
					<button type="button" class="btn btn-light" data-dismiss="modal">Annulla</button>
				</div>

			</div>
		</div>
	</div>

	<script>
	var g_Pagina = '<?= $pagina ?>'
	</script>
	<?php include("inc_js.php") ?>
	<script src="../js/manutenzionearchivi.js"></script>

</body>

</html>