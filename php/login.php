<?php
$pagina = 'login';

include("../inc/conn.php");

// logout
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'logout') {
	unset($_SESSION['utente']);
	$sth = $conn_mes->prepare(
		"UPDATE sessioni SET
		ses_LoggedIn = 0
		WHERE ses_Id = :Id"
	);
	$sth->execute([
		'Id' => session_id(),
	]);
	header("Location: login.php");
	exit();
}


// login
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'login' && !empty($_REQUEST['login']) && !empty($_REQUEST['password'])) {
	$conn_mes->beginTransaction();
	try {
		// Estraggo l'utente in questione
		$sth = $conn_mes->prepare(
			"SELECT * FROM Utenti
			WHERE Username = :Login"
		);
		$sth->execute([
			'Login' => $_REQUEST['login']
		]);
		$utente = $sth->fetch(PDO::FETCH_ASSOC);
		
		// Estraggo i parametri di configurazione del sistema
		$sth = $conn_mes->prepare(
			"SELECT * FROM Configurazione_sistema"
		);
		$sth->execute();
		$configurazione = $sth->fetch(PDO::FETCH_ASSOC);		


		$sth = $conn_mes->prepare(
			"SELECT COUNT(*) AS contoSessioni FROM sessioni
			WHERE ses_LoggedIn = 1"
		);
		$sth->execute();
		$contoSessioni = $sth->fetch(PDO::FETCH_ASSOC)['contoSessioni'];

		if (intval($contoSessioni) >= sessioniMassime) {
			$conn_mes->rollBack();
			die('MAX_' . sessioniMassime);
		}

		// Se ho trovato un utente con questi dati
		if (password_verify($_REQUEST['password'], $utente['Password'])) {
			if (password_needs_rehash($utente['Password'], PASSWORD_DEFAULT)) {
				$sth = $conn_mes->prepare(
					"UPDATE Utenti SET
					Password = :hashPwd
					WHERE Username = :Login"
				);
				$sth->execute([
					'Login' => $_REQUEST['login'],
					'hashPwd' => password_hash($_REQUEST['password'], PASSWORD_DEFAULT)
				]);
			}
			// Sposto tutti i dati dal recordset alla sessione
			$_SESSION['utente'] = $utente;
			$_SESSION['configurazione'] = $configurazione;

			$sth = $conn_mes->prepare(
				"UPDATE sessioni SET
				ses_LoggedIn = 1
				WHERE ses_Id = :Id"
			);
			$sth->execute([
				'Id' => session_id(),
			]);

			$conn_mes->commit();
			die('OK');
		}
	} catch (\Throwable $th) {
		$conn_mes->rollBack();
		die($th->getMessage());
		//throw $th;
	}

	// Di default la chiudo qui
	die('KO');
}

session_gc();
?>
<!DOCTYPE html>
<html lang="it">

<head>
	<meta charset="utf-8">
	<meta content="width=device-width, initial-scale=1, shrink-to-fit=no" name="viewport">
	<title>PROSPECT40 | Accesso al sistema</title>
	<?php include("inc_css.php") ?>
</head>

<body>

	<div class="auth auth-img-bg content-login login-wrapper">

		<div class="row flex-grow login-row">

			<!-- sezione logo -->
			<div class="col-lg-3 d-flex align-items-center justify-content-center">
				<div class="pl-5 pt-3" style="position: absolute; top: 0;"><img alt="logo" src="../images/sfondoHome.png" style='max-width: 70%;'></div>
			</div>

			<div class="col-lg-9 d-flex align-items-center justify-content-center">
			</div>

			<!-- sezione form di login -->
			<div class="col-lg-3 d-flex align-items-center justify-content-center">
			</div>
			<div class="col-lg-6 d-flex align-items-center justify-content-center">
				<div class="auth-form-transparent text-left p-3" onKeyPress="return checkSubmit(event)">

					<form id="login-form" class="pt-6 mt-5">

						<div class="form-group">
							<label for="login">Login</label>
							<div class="input-group">
								<div class="input-group-prepend bg-transparent">
									<span class="input-group-text bg-transparent border-right-0"><i class="mdi mdi-account-outline text-primary"></i></span>
								</div><input class="form-control form-control-lg border-left-0" id="login" placeholder="Login" type="text" required>
							</div>
						</div>

						<div class="form-group">
							<label for="password">Password</label>
							<div class="input-group">
								<div class="input-group-prepend bg-transparent">
									<span class="input-group-text bg-transparent border-right-0"><i class="mdi mdi-lock-outline text-primary"></i></span>
								</div>
								<input class="form-control form-control-lg border-left-0" id="password" placeholder="Password" type="password" required>
							</div>
						</div>


						<div class="my-3">
							<a class="btn btn-block btn-primary btn-lg font-weight-medium auth-form-btn" id="accedi">ACCEDI</a>
						</div>

						<div class="text-center mt-4 font-weight-light">
							<p><strong></strong>
						</div>

					</form>
				</div>
			</div>

			<!-- sezione testo -->
			<div class="col-12 d-flex justify-content-end">
				<p class=" text-right text-black font-weight-medium flex-grow align-self-end mr-2">Copyright &copy; <?= date("Y") ?> Mediasoft Sistemi SNC. Tutti i diritti riservati.</p>
			</div>

		</div>
	</div>


	<?php include("inc_js.php") ?>
	<script src="../js/login.js"></script>
</body>

</html>