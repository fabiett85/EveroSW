<?php
// in che pagina siamo
$pagina = "documentiprodotti";

include("../inc/conn.php");

if (isset($_REQUEST['azione'])) {
	$azione = $_REQUEST['azione'];
	unset($_REQUEST['azione']);
	unset($_REQUEST['_']);

	if ($azione == 'mostra') {
		$output = [];
		$sth = $conn_mes->prepare(
			"SELECT * FROM documenti_prodotto
			LEFT JOIN prodotti ON prd_IdProdotto = dp_IdProdotto
			WHERE dp_IdProdotto LIKE :idProdotto"
		);
		$sth->execute($_REQUEST);
		$documenti = $sth->fetchAll();
		foreach ($documenti as $documento) {
			$cartellaProdotto = str_replace(['/', '\\', '*', ':', '?', '"', '>', '<', '|'], '-', $documento['dp_IdProdotto']);
			$nomeFile = $documento['dp_NomeFile'];
			$output[] = [
				'dp_IdProdotto' => $documento['dp_IdProdotto'],
				'prd_Descrizione' => $documento['prd_Descrizione'],
				'dp_NomeFile' => $documento['dp_NomeFile'],
				'dp_Descrizione' => $documento['dp_Descrizione'],
				'azioni' => '<div class="dropdown">
						<button class="btn btn-primary dropdown-toggle mdi mdi-lead-pencil mdi-18px"
							type="button"
							id="dropdownMenuButton"
							data-toggle="dropdown"
							aria-haspopup="true"
							aria-expanded="false"
							title="Modifica riga"></button>
						<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
							<a class="dropdown-item" href="../../files/prodotti/'. "$cartellaProdotto/$nomeFile" .'" target="_blank">
								<i class="mdi mdi-eye"></i>  VISUALIZZA
							</a>
							<a class="dropdown-item elimina-file">
								<i class="mdi mdi-trash-can"></i>  ELIMINA
							</a>
						</div>
					</div>',
			];
		}

		die(json_encode($output));
	}

	if ($azione == 'carica-file') {
		$idProdotto = $_REQUEST['dp_IdProdotto'];
		$descrizione = $_REQUEST['dp_Descrizione'];
		$cartellaProdotto = str_replace(['/', '\\', '*', ':', '?', '"', '>', '<', '|'], '-', $idProdotto);




		$conn_mes->beginTransaction();
		try {
			$tmp_name = $_FILES["file"]["tmp_name"];
			$name = basename($_FILES['file']['name']);


			$sth = $conn_mes->prepare(
				"SELECT COUNT(*) AS conto FROM documenti_prodotto
				WHERE dp_IdProdotto = :IdProdotto AND dp_NomeFile = :NomeFile"
			);
			$sth->execute([
				'IdProdotto' => $idProdotto,
				'NomeFile' => $name,
			]);
			$conto = $sth->fetch()['conto'];

			if ($conto != 0) {
				$conn_mes->rollBack();
				die('FILE_PRESENTE');
			}

			$sth = $conn_mes->prepare(
				"INSERT INTO documenti_prodotto(dp_IdProdotto,dp_NomeFile,dp_Descrizione)
				VALUES(:IdProdotto, :NomeFile, :Descrizione)"
			);
			$sth->execute([
				'IdProdotto' => $idProdotto,
				'NomeFile' => $name,
				'Descrizione' => $descrizione,
			]);

			$path = '../../files/prodotti/' . $cartellaProdotto;
			if (!is_dir($path)) {
				mkdir($path, 0777, false);
			}
			move_uploaded_file($tmp_name, "$path/$name");
			$conn_mes->commit();
			die('OK');
		} catch (\Throwable $th) {
			$conn_mes->rollBack();
			die($th->getMessage());
		}






		die('OK');
	}

	if ($azione == 'elimina-file') {
		$idProdotto = $_REQUEST['dp_IdProdotto'];
		$nome = $_REQUEST['dp_NomeFile'];
		$cartellaProdotto = str_replace(['/', '\\', '*', ':', '?', '"', '>', '<', '|'], '-', $idProdotto);

		$conn_mes->beginTransaction();
		try {
			$sth = $conn_mes->prepare(
				"DELETE FROM documenti_prodotto
				WHERE dp_IdProdotto = :IdProdotto AND dp_NomeFile = :NomeFile"
			);
			$sth->execute([
				'IdProdotto' => $idProdotto,
				'NomeFile' => $nome,
			]);

			$path = '../../files/prodotti/' . $cartellaProdotto;
			if (!unlink("$path/$nome")) {
				$conn_mes->rollBack();
				die('KO');
			}

			$conn_mes->commit();
			die('OK');
		} catch (\Throwable $th) {
			$conn_mes->rollBack();
			die($th->getMessage());
		}

		die('OK');
	}
}


$sth = $conn_mes->prepare(
	"SELECT * FROM prodotti"
);
$sth->execute($_REQUEST);
$prodotti = $sth->fetchAll();


?>
<!DOCTYPE html>
<html lang="it">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>PROSPECT40 | Documenti prodotti</title>
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
								<div class="col-8">
									<h4 class="card-title mx-2 my-2">DOCUMENTI PRODOTTI</h4>
								</div>
								<div class="col-4">
									<div class="form-group m-0">
										<label for="prd_IdProdotto">Prodotto</label>
										<select class="form-control form-control-sm selectpicker" name="prd_IdProdotto" id="prd_IdProdotto" data-live-search="true">
											<option value="%">Tutti</option>
											<?php
											foreach ($prodotti as $prodotto) {
											?>
												<option value="<?= $prodotto['prd_IdProdotto'] ?>"><?= $prodotto['prd_Descrizione'] ?></option>
											<?php
											}
											?>
										</select>
									</div>
								</div>
							</div>
						</div>
						<div class="card-body">

							<div class="table-responsive">

								<table id="tabellaFiles" class="table table-striped" style="width:100%">
									<thead>
										<tr>
											<th>Codice prodotto</th>
											<th>Descrizione prodotto</th>
											<th>Nome file</th>
											<th>Descrizione file</th>
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

				<?php include("inc_footer.php") ?>

			</div>

		</div>

	</div>

	<button type="button" id="carica-file" class="mdi mdi-button bottone-basso-destra">CARICA FILE</button>

	<div class="modal fade" id="modal-carica-file" tabindex="-1" role="dialog" aria-labelledby="modal-carica-file-label" aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modal-carica-file-label">Carica file</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form id="form-file">

						<div class="row">
							<div class="col-12">
								<div class="form-group">
									<label for="dp_IdProdotto">Prodotto</label>
									<select class="form-control form-control-sm selectpicker" name="dp_IdProdotto" id="dp_IdProdotto" data-live-search="true" required>
										<?php
										foreach ($prodotti as $prodotto) {
										?>
											<option value="<?= $prodotto['prd_IdProdotto'] ?>"><?= $prodotto['prd_Descrizione'] ?></option>
										<?php
										}
										?>
									</select>
								</div>
							</div>
							<div class="col-12">
								<div class="form-group">
									<label for="dp_Descrizione">Descrizione file</label>
									<input type="text" class="form-control form-control-sm" id="dp_Descrizione" name="dp_Descrizione">
								</div>
							</div>
							<div class="col-12">
								<div class="form-group">
									<label class="" for="file">File</label>
									<div class="custom-file">
										<input type="file" class="custom-file-input" id="file" accept=".pdf">
										<label class="custom-file-label" for="file" data-browse="Sfoglia">Scegli file</label>
									</div>
								</div>
							</div>
						</div>


					</form>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-success" id="salva-file">Salva</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
				</div>

			</div>
		</div>
	</div>

	<?php include("inc_js.php") ?>
	<script src="../js/documentiprodotti.js"></script>

</body>

</html>