<?php
// in che pagina siamo
$pagina = 'profimaxfunction';
include("../inc/conn.php");

// debug($_SESSION['utente'],'Utente');
date_default_timezone_set('Europe/Rome');
	
// GENERAZIONE TRACCIATO DI TIPO IMPORT
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'sincronizza-tabella') {
	
	// Apro la transazione MySQL
	$conn_mes->beginTransaction();

	try {
		
		
		die('OK');
		
	} catch (Throwable $t) {
		$conn_mes->rollBack();
		die("ERRORE: " . $t);
	}	
}