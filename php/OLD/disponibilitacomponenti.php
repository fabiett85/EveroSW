<?php

$pagina = 'disponibilitacomponenti';

require_once('../inc/conn.php');

if (isset($_REQUEST['azione'])) {
	$azione = $_REQUEST['azione'];
	unset($_REQUEST['azione']);
	unset($_REQUEST['_']);


	if ($azione == 'popola-select') {
		$parametri = [];
		parse_str($_REQUEST['data'], $parametri);

		$commesse = $_REQUEST['commesse'];
		$componenti = $_REQUEST['componenti'];
		$cluasolaCommesse = '';
		if ($commesse[0] != '%') {
			$cluasolaCommesse = ' AND cmp_IdProduzione IN (';
			foreach ($commesse as $commessa) {
				$cluasolaCommesse .= $commessa . ',';
			}
			$cluasolaCommesse .= "'')";
		}

		$cluasolaComponenti = '';
		if ($componenti[0] != '%') {
			$cluasolaComponenti = ' AND cmp_Componente IN (';
			foreach ($componenti as $componente) {
				$cluasolaComponenti .= $componente . ',';
			}
			$cluasolaComponenti .= "'')";
		}

		$sth = $conn_mes->prepare(
			"SELECT cmp_IdComponente, prd_IdProdotto FROM componenti
			LEFT JOIN prodotti ON prd_IdProdotto = cmp_Componente
			LEFT JOIN ordini_produzione ON op_IdProduzione = cmp_IdProduzione
			WHERE op_LineaProduzione = :idLinea
			AND :inizio <= op_DataProduzione
			AND op_DataProduzione <= :fine
			" . $cluasolaCommesse
		);
		$sth->execute([
			'idLinea' => $parametri['lp_IdLinea'],
			'inizio' => $parametri['inizio'],
			'fine' => $parametri['fine'],
		]);
		$componenti = $sth->fetchAll();

		$sth = $conn_mes->prepare(
			"SELECT cmp_IdProduzione FROM componenti
			LEFT JOIN prodotti ON prd_IdProdotto = cmp_Componente
			LEFT JOIN ordini_produzione ON op_IdProduzione = cmp_IdProduzione
			WHERE op_LineaProduzione = :idLinea
			AND :inizio <= op_DataProduzione
			AND op_DataProduzione <= :fine
			" . $cluasolaCommesse
		);
		$sth->execute([
			'idLinea' => $parametri['lp_IdLinea'],
			'inizio' => $parametri['inizio'],
			'fine' => $parametri['fine'],
		]);
		$componenti = $sth->fetchAll();

		die(json_encode([
			'commesse' => $commesse,
			'componenti' => $componenti,
		]));
	}
}

$sth = $conn_mes->prepare("SELECT * FROM linee_produzione");
$sth->execute();
$linee = $sth->fetchAll();

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
							<h4 class="card-title mx-2 my-2">DISPONIBILITA' COMPONENTI</h4>
						</div>

						<div class="card-body">



							<form id="form-disponibilita">
								<div class="row">
									<div class="col-2">
										<div class="form-group">
											<label for="lp_IdLinea">Linea produzione</label>
											<select name="lp_IdLinea" id="lp_IdLinea" class="selectpicker form-control form-control-sm">
												<option value="%">Tutte</option>
												<?php
												foreach ($linee as $linea) {
												?>
													<option value="<?= $linea['lp_IdLinea'] ?>"><?= $linea['lp_Descrizione'] ?></option>
												<?php
												}
												?>
											</select>
										</div>
									</div>
									<div class="col-3">
										<div class="form-group">
											<label for="sel_commesse">Commesse</label>
											<select name="sel_commesse" id="sel_commesse" class="selectpicker form-control form-control-sm" multiple>
												<option value="%">Tutte</option>
											</select>
										</div>
									</div>
									<div class="col-3">
										<div class="form-group">
											<label for="sel_componente">Componente</label>
											<select name="sel_componente" id="sel_componente" class="selectpicker form-control form-control-sm" multiple>
												<option value="%">Tutti</option>
												<option value="ds">ds</option>
												<option value="dsf">dsf</option>
												<option value="dsfw">dsfw</option>
											</select>
										</div>
									</div>
									<div class="col-2">
										<div class="form-group">
											<label for="inizio">Data inizio</label>
											<input class="form-control form-control-sm" type="date" name="inizio" id="inizio">
										</div>
									</div>
									<div class="col-2">
										<div class="form-group">
											<label for="fine">Data fine</label>
											<input class="form-control form-control-sm" type="date" name="fine" id="fine">
										</div>
									</div>
								</div>
							</form>

						</div>
					</div>

				</div>
				<?php include("inc_footer.php") ?>

			</div>

		</div>

	</div>



	<?php include("inc_js.php") ?>
	<script src="../js/<?= $pagina ?>.js"></script>

</body>

</html>