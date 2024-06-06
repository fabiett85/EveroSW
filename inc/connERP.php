<?php

$conn_erp;
$hostname = 'SRV';
$dbname = 'PRODUZIONE';
$user = 'sa';
$pass = 'M&di@2015.';
// connessione al database mes
try {
	$conn_erp = new PDO(
		"sqlsrv:server=" . $hostname . ";Database=" . $dbname,
		$user,
		$pass,
		[
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
		]
	);
} catch (PDOException $e) {
	die("Errore nella connessione al servizio mySQL: " . $e->getMessage());
}