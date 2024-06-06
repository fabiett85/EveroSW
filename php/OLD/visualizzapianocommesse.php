<?php
// in che pagina siamo
$pagina = 'ordiniproduzione';

include("../inc/conn.php");

if (isset($_REQUEST['azione'])) {
	if ($_REQUEST['azione'] == 'componenti') {
		$sth = $conn_mes->prepare(
			"SELECT componenti.*,
				ordini_produzione.*,
				so_Descrizione,
				lp_Descrizione,
				PC.prd_Descrizione AS Componente,
				PF.prd_Descrizione AS ProdottoFinito,
				cmp_Qta - PC.prd_Disponibilita AS Mancanti
			FROM ordini_produzione
			LEFT JOIN componenti ON op_IdProduzione=cmp_IdProduzione
			LEFT JOIN prodotti AS PC ON PC.prd_IdProdotto = cmp_Componente
			LEFT JOIN stati_ordine ON so_IdStatoOrdine = op_Stato
			LEFT JOIN linee_produzione ON lp_IdLinea = op_LineaProduzione
			LEFT JOIN prodotti AS PF ON PF.prd_IdProdotto = op_Prodotto
			WHERE op_Stato < 5"
		);
		$sth->execute();
		$ordini = $sth->fetchAll();

		$out = [];


		if ($ordini) {
			$count = 0;
			foreach ($ordini as $ordine) {
				$data = new DateTime($ordine['op_DataProduzione'] . ' ' . $ordine['op_OraProduzione']);
				$ordini[$count]['DataOraProgrammazione'] = $data->format('d/m/Y H:i');
				$data = new DateTime($ordine['op_DataFineTeorica'] . ' ' . $ordine['op_OraFineTeorica']);
				$ordini[$count]['DataOraFinePrevista'] = $data->format('d/m/Y H:i');
				if (!isset($ordine['Componente'])) {
					$ordini[$count]['Componente'] = 'NESSUN COMPONENTE';
				}

				$count++;
			}
			die(json_encode($ordini));
		}
		die(json_encode([]));
	}
}


?>
<!DOCTYPE html>
<html lang="it">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>PROSPECT40 | Piano commesse</title>
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
							<h4 class="card-title m-2">COMMESSE PIANIFICATE</h4>
						</div>
						<div class="card-body">

							<!-- Visualizzazione distinte prodotto presenti e dati di quella selezionata -->


							<div class="row">

								<div class="col-12 pt2">
									<div id="timeline_ordini"></div>
								</div>
								<div class="table-responsive pt-1">
									<div class="col-12">
										<table id="tabellaComponenti" class="table table-striped" style="width:100%">

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

	<!-- Opup modale di RIEPILOGO DATI COMMESSA DA GANTT -->
	<div class="modal fade" id="modal-dettagli-ordine" tabindex="-1" role="dialog"
		aria-labelledby="modal-dettagli-ordine-label" aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modalProdottiLabel">RIEPILOGO COMMESSA</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form class="forms-sample" id="form-dettagli-ordine">

						<div class="row">

							<div class="col-6">
								<div class="form-group">
									<label for="op_IdProduzione">Codice commessa</label>
									<input readonly type="text" class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="op_IdProduzione" id="op_IdProduzione">
								</div>
							</div>

							<div class="col-6">
								<div class="form-group">
									<label for="lp_Descrizione">Linea</label>
									<input readonly type="text" class="form-control form-control-sm dati-popup-modifica"
										name="lp_Descrizione" id="lp_Descrizione">
								</div>
							</div>

							<div class="col-8">
								<div class="form-group">
									<label for="prd_Descrizione">Prodotto</label>
									<input readonly type="text" class="form-control form-control-sm dati-popup-modifica"
										name="prd_Descrizione" id="prd_Descrizione">
								</div>
							</div>

							<div class="col-4">
								<div class="form-group">
									<label for="op_QtaDaProdurreUdm">Qta da prod.</label>
									<input readonly type="text" class="form-control form-control-sm dati-popup-modifica"
										name="op_QtaDaProdurreUdm" id="op_QtaDaProdurreUdm">
								</div>
							</div>

							<div class="col-12">
								<hr>
							</div>

							<div class="col-6">
								<div class="form-group">
									<label for="op_DataOraProduzione">Programmato per</label>
									<input type="datetime-local" class="form-control form-control-sm dati-popup-modifica data"
										name="op_DataOraProduzione" id="op_DataOraProduzione">
								</div>
							</div>

							<div class="col-6">
								<div class="form-group">
									<label for="op_DataOraFineTeorica">Termine previsto</label>
									<input type="datetime-local" class="form-control form-control-sm dati-popup-modifica data"
										name="op_DataOraFineTeorica" id="op_DataOraFineTeorica">
								</div>
							</div>

							<input type="hidden" id="vel_VelocitaTeoricaLinea" name="vel_VelocitaTeoricaLinea" value="">
							<input type="hidden" id="op_QtaDaProdurre" name="op_QtaDaProdurre" value="">

						</div>


					</form>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-success" id="salva-lavoro-gantt" data-dismiss="modal">Salva</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Chiudi</button>
				</div>

			</div>
		</div>
	</div>

	<?php include("inc_js.php") ?>
	<script src="../js/timelineordini_new.js"></script>
	<script src="../js/visualizzapianocommesse.js"></script>


</body>

</html>