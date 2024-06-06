<?php
// in che pagina siamo
$pagina = 'utenti';

include("../inc/conn.php");


// RICETTE MACCHINA: VISUALIZZAZIONE CATEGORIE CENSITE
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'mostra-usr') {
	// estraggo la lista
	$sth = $conn_mes->prepare(
		"SELECT utenti.usr_IdUtente, utenti.usr_Nome, utenti.usr_Cognome, utenti.usr_Login, utenti.usr_Mansione, privilegi_utente.priv_DescrizioneLivello, usr_Password, STRING_AGG(risorse.ris_Descrizione, '<br>') WITHIN GROUP (ORDER BY ris_Descrizione ASC) AS ElencoRisorse
			FROM utenti
			LEFT JOIN configurazione_pannelli ON configurazione_pannelli.cp_IdUtente = utenti.usr_IdUtente
			LEFT JOIN risorse ON configurazione_pannelli.cp_IdRisorsa = risorse.ris_IdRisorsa
			LEFT JOIN privilegi_utente ON utenti.usr_Mansione = privilegi_utente.priv_CodiceLivello
			GROUP BY utenti.usr_IdUtente, utenti.usr_Nome, utenti.usr_Cognome, utenti.usr_Login, utenti.usr_Mansione, privilegi_utente.priv_DescrizioneLivello, usr_Password
			ORDER BY usr_Login  ASC",
		[PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]
	);
	$sth->execute();
	$righe = $sth->fetchAll(PDO::FETCH_ASSOC);

	$output = [];

	$pulsanteAzioni = "";

	foreach ($righe as $riga) {
		if ($riga['usr_Mansione'] >= $_SESSION['utente']['usr_Mansione']) {
			if ($riga['usr_IdUtente'] == $_SESSION['utente']['usr_IdUtente']) {
				$pulsanteAzioni =
					'<div class="dropdown">
					<button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					<span class="mdi mdi-lead-pencil mdi-18px"></span>
					</button>
					<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
						<a class="dropdown-item modifica-entry-usr" data-id_riga="' . $riga['usr_IdUtente'] . '"><i class="mdi mdi-account-edit"></i> Modifica</a>
					</div>
				</div>';
			} else {

				$pulsanteAzioni =
					'<div class="dropdown">
						<button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
						<span class="mdi mdi-lead-pencil mdi-18px"></span>
						</button>
						<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
							<a class="dropdown-item modifica-entry-usr" data-id_riga="' . $riga['usr_IdUtente'] . '"><i class="mdi mdi-account-edit"></i> Modifica</a>
							<a class="dropdown-item cancella-entry-usr" data-id_riga="' . $riga['usr_IdUtente'] . '"><i class="mdi mdi-trash-can"></i> Elimina</a>
						</div>
					</div>';
			}
			//Preparo i dati da visualizzare
			$output[] = [
				'NomeUtente' => $riga['usr_Nome'],
				'CognomeUtente' => $riga['usr_Cognome'],
				'UsernameUtente' => $riga['usr_Login'],
				'LivelloUtente' => $riga['priv_DescrizioneLivello'],
				'MacchineUtente' => ($riga['usr_Mansione'] <= 2 ? "Intero parco macchine" : $riga['ElencoRisorse']),
				'Azioni' => $pulsanteAzioni
			];
		}
	}

	die(json_encode($output));
}


// RICETTE MACCHINA: RECUPERO VALORI DELLA CATEGORIA SELEZIONATA
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'recupera-usr' && !empty($_REQUEST['idRiga'])) {
	// estraggo la lista
	$sth = $conn_mes->prepare(
		"SELECT utenti.usr_IdUtente, utenti.usr_Nome, utenti.usr_Cognome, utenti.usr_Login, utenti.usr_Mansione, privilegi_utente.priv_DescrizioneLivello, usr_Password, STRING_AGG(risorse.ris_IdRisorsa, ',') WITHIN GROUP (ORDER BY ris_Descrizione ASC) AS ElencoRisorse
			FROM utenti
			LEFT JOIN configurazione_pannelli ON configurazione_pannelli.cp_IdUtente = utenti.usr_IdUtente
			LEFT JOIN risorse ON configurazione_pannelli.cp_IdRisorsa = risorse.ris_IdRisorsa
			LEFT JOIN privilegi_utente ON utenti.usr_Mansione = privilegi_utente.priv_CodiceLivello
			WHERE usr_IdUtente = :idRiga
			GROUP BY utenti.usr_IdUtente, utenti.usr_Nome, utenti.usr_Cognome, utenti.usr_Login, utenti.usr_Mansione, privilegi_utente.priv_DescrizioneLivello, usr_Password
			ORDER BY usr_Login  ASC",
		[PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]
	);
	$sth->execute([':idRiga' => $_REQUEST['idRiga']]);
	$riga = $sth->fetch(PDO::FETCH_ASSOC);
	$riga['usr_Password'] = '';


	die(json_encode($riga));
}

// RICETTE MACCHINA: GESTIONE CANCELLAZIONE
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'cancella-usr' && !empty($_REQUEST['idRiga'])) {
	// elimino l'utente
	$sthDeleteUtente = $conn_mes->prepare("DELETE FROM utenti WHERE usr_IdUtente = :idRiga");
	$sthDeleteUtente->execute([':idRiga' => $_REQUEST['idRiga']]);

	// elimino eventuali permessi associati
	$sthDeletePrivilegiEsistenti = $conn_mes->prepare("DELETE
									FROM configurazione_pannelli
									WHERE configurazione_pannelli.cp_IdUtente = :IdUtente");
	$sthDeletePrivilegiEsistenti->execute([':IdUtente' => $_REQUEST['idRiga']]);

	// eseguo query e verifico esito
	$conn_mes->beginTransaction();

	if ($sthDeleteUtente && $sthDeletePrivilegiEsistenti) {
		$conn_mes->commit();
		die('OK');
	} else {
		$conn_mes->rollBack();
		die('ERRORE');
	}
}



// RICETTE MACCHINA: GESTIONE SALVATAGGIO DATI DA POPUP MODIFICA/INSERIMENTO
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'salva-usr' && !empty($_REQUEST['data'])) {
	// recupero i parametri dal POST
	$parametri = [];
	parse_str($_REQUEST['data'], $parametri);
	$conn_mes->beginTransaction();
	try {
		// Se devo modificare
		if ($parametri['usr_azione'] == 'modifica') {

			$id_modifica = $parametri['usr_IdUtente'];

			// Verifico che il codice cliente inserito, che mi funge da ID, non esista in altri record se non in quello che sto modificando
			$sth = $conn_mes->prepare(
				"SELECT * FROM utenti
					WHERE usr_Login = :LoginUtente
					AND usr_IdUtente != :IdUtente",
				[PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]
			);
			$sth->execute([
				':LoginUtente' => $parametri['usr_Login'],
				':IdUtente' => $id_modifica
			]);
			$trovati = $sth->fetch(PDO::FETCH_ASSOC);

			if (!$trovati) {

				// Se la verifica della password inserita dà esito positivo (password = password di conferma)
				if ($parametri['usr_Password'] == $parametri['usr_ConfermaPassword']) {

					// Eseguo query di UPDATE della tabella 'utenti'

					$sthUpdate = $conn_mes->prepare(
						"UPDATE utenti SET
							usr_Nome = :NomeUtente,
							usr_Cognome = :CognomeUtente,
							usr_Mansione = :MansioneUtente,
							usr_Login = :LoginUtente,
							usr_Password = :Password
							WHERE usr_IdUtente = :IdRiga"
					);
					$sthUpdate->execute([
						':NomeUtente' => $parametri['usr_Nome'],
						':CognomeUtente' => $parametri['usr_Cognome'],
						':MansioneUtente' => $parametri['usr_Mansione'],
						':LoginUtente' => $parametri['usr_Login'],
						':Password' => password_hash($parametri['usr_Password'], PASSWORD_DEFAULT),
						':IdRiga' => $id_modifica
					]);


					// Eseguo query di DELETE sulla tabella 'configurazione_pannelli' per eliminare i precedenti permessi
					$sthDeletePrivilegiEsistenti = $conn_mes->prepare("DELETE
													FROM configurazione_pannelli
													WHERE configurazione_pannelli.cp_IdUtente = :IdUtente");
					$sthDeletePrivilegiEsistenti->execute([':IdUtente' => $id_modifica]);

					// Se l'utente ha i diritti di 'AMMINISTRATORE'
					if ($parametri['usr_Mansione'] <= 2) {

						// Eseguo query di INSERT in tabella 'configurazione_pannelli' conferendo i permnessi su tutte le macchine definite
						$sthConfiguraParcoMacchine = $conn_mes->prepare(
							"INSERT INTO configurazione_pannelli (cp_IdUtente, cp_IdRisorsa)
								SELECT :IdUtente, ris_IdRisorsa FROM risorse"
						);
						$sthConfiguraParcoMacchine->execute([':IdUtente' => $id_modifica]);
					} else { // Se l'utente ha invece i diritti di 'OPERATORE'

						// Ricavo l'elenco delle macchine su cui ha diritto, come impostato da popup
						$elencoMacchine = $_REQUEST['elencoRisorse'];
						if (isset($elencoMacchine)) {

							foreach ($elencoMacchine as $value) {

								// Eseguo query di INSERT in tabella 'configurazione_pannelli' conferendo i permnessi su tutte le macchine selezionate
								$sqlConfiguraParcoMacchine =
									"INSERT INTO configurazione_pannelli(cp_IdUtente, cp_IdRisorsa)
										VALUES (:IdUtente,:IdRisorsa)";

								$sthConfiguraParcoMacchine = $conn_mes->prepare($sqlConfiguraParcoMacchine);
								$sthConfiguraParcoMacchine->execute([
									':IdUtente' => $id_modifica,
									':IdRisorsa' => $value
								]);
							}
						}
					}


					$conn_mes->commit();
					die('OK');
				} else {
					$conn_mes->rollBack();
					die("Attenzione! I campi 'password' e 'conferma password' devono coincidere.");
				}
			} else {
				$conn_mes->rollBack();
				die("Nel sistema è già attivo un utente con lo username scelto.");
			}
		} else // Se nuovo inserimento
		{

			// Ricavo l'ultimno codice utente utilizzato, per generare quello nuovo.
			$sthUltimoIdUtente = $conn_mes->prepare("SELECT TOP(1) utenti.usr_IdUtente
											FROM utenti
											ORDER BY usr_IdUtente DESC", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
			$sthUltimoIdUtente->execute([]);
			$rigaUltimoIdUtente = $sthUltimoIdUtente->fetch(PDO::FETCH_ASSOC);

			// Se ho già entry esistenti, formatto opportunamente il nuovo ID
			if ($rigaUltimoIdUtente) {
				$ultimoIdUtente = (int)$rigaUltimoIdUtente['usr_IdUtente'];
				$idUtente = intval($ultimoIdUtente + 1);
			} else {
				$idUtente = '1';
			}


			// Verifico che il codice cliente inserito, che mi funge da ID, non esista in altri record
			$sthSelect = $conn_mes->prepare("SELECT utenti.* FROM utenti WHERE usr_Login = :LoginUtente", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
			$sthSelect->execute([':LoginUtente' => $parametri['usr_Login']]);
			$trovati = $sthSelect->fetch(PDO::FETCH_ASSOC);

			if (!$trovati) {

				// Se la verifica della password inserita dà esito positivo (password = password di conferma)
				if ($parametri['usr_Password'] == $parametri['usr_ConfermaPassword']) {

					// Eseguo query di INSERT in tabella 'utenti'
					$sqlInsert = "INSERT INTO utenti(usr_Nome,usr_Cognome,usr_Mansione,usr_Login,usr_Password) VALUES
							(:NomeUtente,:CognomeUtente,:MansioneUtente,:LoginUtente,:Password)";

					$sthInsert = $conn_mes->prepare($sqlInsert);
					$sthInsert->execute([
						':NomeUtente' => $parametri['usr_Nome'],
						':CognomeUtente' => $parametri['usr_Cognome'],
						':MansioneUtente' => $parametri['usr_Mansione'],
						':LoginUtente' => $parametri['usr_Login'],
						':Password' => password_hash($parametri['usr_Password'], PASSWORD_DEFAULT)
					]);


					// Se l'utente ha i diritti di 'AMMINISTRATORE'
					if ($parametri['usr_Mansione'] <= 2) {

						// Eseguo query di INSERT in tabella 'configurazione_pannelli' conferendo i permessi su tutte le macchine definite
						$sthConfiguraParcoMacchine = $conn_mes->prepare(
							"INSERT INTO configurazione_pannelli(cp_IdUtente, cp_IdRisorsa)
								SELECT :IdUtente, ris_IdRisorsa FROM risorse"
						);
						$sthConfiguraParcoMacchine->execute([':IdUtente' => $idUtente]);
					} else { // Se l'utente ha invece i diritti di 'OPERATORE'

						// Ricavo l'elenco delle macchine su cui ha diritto, come impostato da popup
						$elencoMacchine = $_REQUEST['elencoRisorse'];
						if (isset($elencoMacchine)) {

							foreach ($elencoMacchine as $value) {

								// Eseguo query di INSERT in tabella 'configurazione_pannelli' conferendo i permnessi su tutte le macchine selezionate
								$sqlConfiguraParcoMacchine = "INSERT INTO configurazione_pannelli(cp_IdUtente,cp_IdRisorsa) VALUES(:IdUtente,:IdRisorsa)";

								$sthConfiguraParcoMacchine = $conn_mes->prepare($sqlConfiguraParcoMacchine);
								$sthConfiguraParcoMacchine->execute([
									':IdUtente' => $idUtente,
									':IdRisorsa' => $value
								]);
							}
						}
					}


					$conn_mes->commit();
					die('OK');
				} else {
					$conn_mes->rollBack();
					die("Attenzione! I campi 'password' e 'conferma password' devono coincidere.");
				}
			} else {
				$conn_mes->rollBack();
				die("Nel sistema è già attivo un utente con lo username scelto.");
			}
		}
	} catch (Throwable $t) {
		$conn_mes->rollBack();
		die('KO');
	}
}


// AUSILIARIA: POPOLAMENTO SELECT RISORSE IN BASE A LINEA SELEZIONATA
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'usr-carica-select-risorse') {

	$sth = $conn_mes->prepare("SELECT risorse.*
								FROM risorse", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
	$sth->execute();


	$risorse = $sth->fetchAll(PDO::FETCH_ASSOC);
	$optionValue = "";

	//Se ho trovato sottocategorie
	if ($risorse) {

		//Aggiungo ognuna delle sottocategorie trovate alla stringa che conterrà le possibili opzioni della select categorie, e che ritorno come risultato
		foreach ($risorse as $risorsa) {

			//Se ho già una sottocategoria selezionata (provengo da popup "di modifica"), preparo il contenuto della select con l'option value corretto selezionato altrimenti preparo solo il contenuto.
			if (!empty($_REQUEST['idRisorsa']) && $_REQUEST['idRisorsa'] == $risorsa['ris_IdRisorsa']) {
				$optionValue = $optionValue . "<option value='" . $risorsa['ris_IdRisorsa'] . "' selected>" . strtoupper($risorsa['ris_Descrizione']) . " </option>";
			} else {
				$optionValue = $optionValue . "<option value='" . $risorsa['ris_IdRisorsa'] . "'>" . strtoupper($risorsa['ris_Descrizione']) . " </option>";
			}
		}
	}
	echo $optionValue;
	exit();
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
							<h4 class="card-title m-2">UTENTI SISTEMA</h4>
						</div>
						<div class="card-body">


							<div class="row">

								<div class="col-12">

									<div class="table-responsive pt-1">

										<table id="tabellaDati-usr" class="table table-striped" style="width:100%"
											data-source="utenti.php?azione=mostra-usr">
											<thead>
												<tr>
													<th>Nome</th>
													<th>Cognome</th>
													<th>Username</th>
													<th>Livello</th>
													<th>Macchine accessibili</th>
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

	<button type="button" id="nuovo-usr" class="mdi mdi-button">NUOVO UTENTE</button>



	<!-- Popup modale di modifica/inserimento UTENTE-->
	<div class="modal fade" id="modal-usr" tabindex="-1" role="dialog" aria-labelledby="modal-usr-label"
		aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header py-1">
					<h5 class="modal-title" id="modal-usr-label">NUOVO UTENTE</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form class="forms-sample" id="form-usr">

						<div class="row">
							<div class="col-4">
								<div class="form-group">
									<label for="usr_Nome">Nome</label>
									<input type="text" class="form-control form-control-sm dati-popup-modifica" name="usr_Nome"
										id="usr_Nome" autocomplete="off">
								</div>
							</div>
							<div class="col-4">
								<div class="form-group">
									<label for="usr_Cognome">Cognome</label>
									<input type="text" class="form-control form-control-sm dati-popup-modifica" name="usr_Cognome"
										id="usr_Cognome" autocomplete="off">
								</div>
							</div>
							<div class="col-4">
								<div class="form-group">
									<label for="usr_Note">Note</label>
									<input type="text" class="form-control form-control-sm dati-popup-modifica" name="usr_Note"
										id="usr_Note" autocomplete="off">
								</div>
							</div>
							<div class="col-4">
								<div class="form-group">
									<label for="usr_Login">Username</label><span style='color:red'> *</span>
									<input type="text" class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="usr_Login" id="usr_Login" autocomplete="off">
								</div>
							</div>
							<div class="col-4">
								<div class="form-group">
									<label for="usr_Password">Password</label><span style='color:red'> *</span>
									<input type="password" class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="usr_Password" id="usr_Password">
								</div>
							</div>
							<div class="col-4">
								<div class="form-group">
									<label for="usr_ConfermaPassword">Conferma password</label><span style='color:red'> *</span>
									<input type="password" class="form-control form-control-sm dati-popup-modifica obbligatorio"
										name="usr_ConfermaPassword" id="usr_ConfermaPassword">
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="usr_Mansione">Livello privilegi</label><span style='color:red'> *</span>
									<select class="form-control form-control-sm dati-popup-modifica selectpicker" name="usr_Mansione"
										id="usr_Mansione" data-live-search="true">
										<?php
										$sth = $conn_mes->prepare(
											"SELECT privilegi_utente.*
												FROM privilegi_utente
												WHERE priv_CodiceLivello >= :Livello",
											[PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]
										);
										$sth->execute([':Livello' => $_SESSION['utente']['usr_Mansione']]);
										$prodotti = $sth->fetchAll(PDO::FETCH_ASSOC);

										foreach ($prodotti as $prodotto) {
											echo "<option value='" . $prodotto['priv_CodiceLivello'] . "'>" . $prodotto['priv_DescrizioneLivello'] . "</option>";
										}
										?>
									</select>
								</div>
							</div>
							<div class="col-6">
								<div class="form-group" id="privilegi-macchine-user">
									<label for="usr_ElencoRisorse">Macchine accessibili</label>
									<select class="form-control form-control-sm selectpicker dati-report" id="usr_ElencoRisorse"
										name="usr_ElencoRisorse" multiple data-live-search="true">
									</select>
								</div>
								<div class="form-group" id="privilegi-macchine-admin">
									<div class="pt-4"><small>Controllo intero parco macchine.</small></div>
								</div>
							</div>

						</div>

						<input type="hidden" id="usr_IdUtente" name="usr_IdUtente" value="">
						<input type="hidden" id="usr_azione" name="usr_azione" value="nuovo">

					</form>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-success" id="salva-entry-usr">Salva</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
				</div>

			</div>
		</div>
	</div>




	<?php include("inc_js.php") ?>
	<script src="../js/utenti.js"></script>

</body>

</html>