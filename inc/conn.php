<?php
const sessioniMassime = 2;

include("../vendor/autoload.php");



//require_once('MesSessionHandler.php');

//$sessionHandler = new MesSessionHandler();
//session_set_save_handler($sessionHandler, true);

session_start();



// stato generale del debug
$_debug = true;
$_prefisso_db = "";
$allineamento = false;


// credenziali utente di sistema per la connessione al database
$db_user = "";
$db_pass = "";

// connessione al database mes
try {
	$conn_mes = new PDO(
		"sqlsrv:server=(local)\SQLEXPRESS;Database=GEST_CANTINA",
		$db_user,
		$db_pass,
		[
			//PDO::ATTR_PERSISTENT => true,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		]
	);
	//$conn_mes->setAttribute(PDO::ATTR_STATEMENT_CLASS, ["EPDOStatement\EPDOStatement", [$conn_mes]]);
} catch (PDOException $e) {
	die("Errore nella connessione al servizio SQL: " . $e->getMessage());
}

// zoom interfaccia: impostazione cookie
if (!empty($_REQUEST["azione"]) && $_REQUEST["azione"] == "zoom" && !empty($_REQUEST["zoom"])) {
	setcookie("zoom_ui", $_REQUEST["zoom"], time() + (86400 * 80), '/');
	print_r($_COOKIE);
}


// zoom interfaccia: lettura cookie
$classe_body_zoom = $data_body_zoom = $classe_diminuisci_zoom = $classe_aumenta_zoom = $disabled_aumenta_zoom = $disabled_diminuisci_zoom = "";
if (isset($_COOKIE["zoom_ui"])) {
	if ($_COOKIE["zoom_ui"] != 100) {
		$classe_body_zoom = " zoomed";
		$data_body_zoom = " data-zoom='" . $_COOKIE["zoom_ui"] . "'";
	} else {
		$classe_aumenta_zoom = "disabled";
		$disabled_aumenta_zoom = "disabled";
	}

	if ($_COOKIE["zoom_ui"] == 50) {
		$classe_diminuisci_zoom = "disabled";
		$disabled_diminuisci_zoom = "disabled";
	}
}



// se ho un utente loggato in sessione
if (empty($_SESSION["utente"]) && $pagina != "login") {
	header("Location: login.php");
	exit();
} else if (!isset($_REQUEST['azione']) && !empty($_SESSION["utente"]) && $pagina == "login") {
	header("Location: dashboard.php");
	exit();
}
