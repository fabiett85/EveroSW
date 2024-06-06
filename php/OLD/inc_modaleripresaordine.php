	<!-- Opup modale di RIPRESA ORDINE GIA' ESEGUITO -->
	<div class="modal fade" id="modal-riprendi-ordine" tabindex="-1" role="dialog" aria-labelledby="modal-riprendi-ordine-label" aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modal-riprendi-ordine-label">RIPRENDI COMMESSA TERMINATA</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>

				<div class="modal-body">
					<form class="" id="form-riprendi-ordine">

						<div class="row">
							<div class="col-12">
								<div class="form-group">
									<label for="rop_IdProduzione">Codice commessa</label>
									<input readonly type="text" class="form-control form-control-sm dati-popup-modifica" name="rop_IdProduzione" id="rop_IdProduzione">
								</div>
							</div>
							<div class="col-12">
								<div class="form-group">
									<label for="rop_Prodotto">Prodotto</label>
									<input readonly type="text" class="form-control form-control-sm dati-popup-modifica" name="rop_Prodotto" id="rop_Prodotto">
								</div>
							</div>
							<div class="col-4">
								<div class="form-group">
									<label for="rop_QtaRichiesta">Qta originale richiesta</label>
									<input readonly type="number" class="form-control form-control-sm dati-popup-modifica" name="rop_QtaRichiesta" id="rop_QtaRichiesta">
								</div>
							</div>
							<div class="col-4">
								<div class="form-group">
									<label for="rop_QtaProdotta">Qta prodotta</label>
									<input readonly type="number" class="form-control form-control-sm dati-popup-modifica" name="rop_QtaProdotta" id="rop_QtaProdotta">
								</div>
							</div>
							<div class="col-4">
								<div class="form-group">
									<label for="rop_QtaDaProdurre">Qta da produrre</label><span style='color:red'> *</span>
									<input type="number" class="form-control form-control-sm dati-popup-modifica  obbligatorio" name="rop_QtaDaProdurre" id="rop_QtaDaProdurre" placeholder="Qta richiesta">
								</div>
							</div>

							<div class="col-6">
								<div class="form-group">
									<label for="rop_DataOrdine">Data compilazione</label><span style='color:red'> *</span>
									<input type="date" class="form-control form-control-sm dati-popup-modifica  obbligatorio" name="rop_DataOrdine" id="rop_DataOrdine" placeholder="Data compilazione">
								</div>
							</div>

							<div class="col-6">
								<div class="form-group">
									<label for="rop_OraOrdine">Ora compilazione</label><span style='color:red'> *</span>
									<input type="time" class="form-control form-control-sm dati-popup-modifica  obbligatorio" name="rop_OraOrdine" id="rop_OraOrdine" placeholder="Ora compilazione">
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="rop_DataProduzione">Data pianificazione</label><span style='color:red'> *</span>
									<input type="date" class="form-control form-control-sm dati-popup-modifica obbligatorio" name="rop_DataProduzione" id="rop_DataProduzione" placeholder="Data pianificazione">
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="rop_OraProduzione">Ora pianificazione</label><span style='color:red'> *</span>
									<input type="time" class="form-control form-control-sm dati-popup-modifica obbligatorio" name="rop_OraProduzione" id="rop_OraProduzione" placeholder="Ora pianificazione">
								</div>
							</div>
							<div class="col-12">
								<div class="form-group">
									<label for="rop_Lotto">Lotto</label>
									<input type="text" class="form-control form-control-sm dati-popup-modifica " name="rop_Lotto" id="rop_Lotto" placeholder="Lotto ordine">
								</div>
							</div>
							<div class="col-12">
								<div class="form-group">
									<label for="rop_Note">Note</label>
									<input type="text" class="form-control form-control-sm dati-popup-modifica " name="rop_Note" id="rop_Note" placeholder="Note ordine">
								</div>
							</div>
						</div>

						<input type="hidden" id="rop_IdLinea" name="rop_IdLinea" value="">
						<input type="hidden" id="rop_IdProdotto" name="rop_IdProdotto" value="">
						<input type="hidden" id="rop_Riferimento" name="rop_Riferimento" value="">
						<input type="hidden" id="rop_ProgressivoParziale" name="rop_ProgressivoParziale" value="">
						<input type="hidden" id="rop_VelocitaTeorica" name="rop_VelocitaTeorica" value="">
						<input type="hidden" id="rop_Udm" name="rop_Udm" value="">
					</form>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-success" id="salva-ordine-ripreso">Salva</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
				</div>

			</div>
		</div>
	</div>