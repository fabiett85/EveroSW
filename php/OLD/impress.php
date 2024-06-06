<?php
// in che pagina siamo
$pagina = 'impress';

include("../inc/conn.php");

if (PHP_SESSION_ACTIVE == session_status()) {
	$sth = $conn_mes->prepare(
		"UPDATE sessioni SET
		ses_LastImpress = GETDATE()
		WHERE ses_Id = :Id"
	);
	$sth->execute(['Id' => session_id()]);
}
die('OK');
