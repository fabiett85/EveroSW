<?php

try {
	$conn_mes = new PDO(
		"sqlsrv:server=(local)\SQLEXPRESS2019;Database=db_mes_liveli",
		"",
		"",
		[
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		]
	);
	//$conn_mes->setAttribute(PDO::ATTR_STATEMENT_CLASS, ["EPDOStatement\EPDOStatement", [$conn_mes]]);
} catch (PDOException $e) {
	die("Errore nella connessione al servizio SQL: " . $e->getMessage());
}


try {
	$conn_mes->beginTransaction();

	$sth = $conn_mes->prepare("UPDATE prodotti SET counter = counter - 1");

	$sth->execute();

	$conn_mes->commit();
} catch (\Throwable $th) {
	$conn_mes->rollBack();
}