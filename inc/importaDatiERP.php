<?php

if (empty($pagina)) {
	$pagina = 'importDati';
}
require_once('../inc/conn.php');

$conn_erp;
$hostname = '192.168.5.230';
$dbname = 'db_mes_erp';
$user = 'root';
$pass = 'media2021.';
// connessione al database mes
try {
	$conn_erp = new PDO(
		"mysql:host=$hostname;dbname=$dbname",
		$user,
		$pass,
		[
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		]
	);
} catch (PDOException $e) {
	die("Errore nella connessione al servizio mySQL: " . $e->getMessage());
}


$conn_erp->beginTransaction();
$conn_mes->beginTransaction();

try {
	$sth = $conn_erp->prepare(
		"SELECT * FROM ordini_mes
		WHERE letto_da_mes = 0"
	);
	$sth->execute();
	$ordiniDaCopiare = $sth->fetchAll();

	foreach ($ordiniDaCopiare as $ordine) {
		$sth = $conn_mes->prepare(
			"SELECT * FROM prodotti
			WHERE prd_IdProdotto = :IdProdotto"
		);
		$sth->execute(['IdProdotto' => $ordine['Prodotto']]);
		$prodotto = $sth->fetch();

		if ($prodotto) {
			$sth = $conn_mes->prepare(
				"UPDATE prodotti SET
				prd_Descrizione = :Descrizione
				WHERE prd_IdProdotto = :IdProdotto"
			);
			$sth->execute([
				'Descrizione' => $ordine['Prodotto'],
				'IdProdotto' => $ordine['descrizione_prodotto'],
			]);
		} else {
			$sth = $conn_mes->prepare(
				"INSERT INTO prodotti (prd_Descrizione, prd_IdProdotto, prd_Tipo, prd_UnitaMisura)
				VALUES (:Descrizione, :IdProdotto, 'F', 1)"
			);
			$sth->execute([
				'Descrizione' => $ordine['descrizione_prodotto'],
				'IdProdotto' => $ordine['Prodotto'],
			]);
		}

		$sth = $conn_mes->prepare(
			"SELECT * FROM ordini_produzione
			WHERE op_IdProduzione = :IdProduzione"
		);
		$sth->execute(['IdProduzione' => $ordine['numero']]);
		$ordineMes = $sth->fetch();

		if (!$ordineMes) {
			$dataOrdine = new DateTime($ordine['data_ordine']);
			$sth = $conn_mes->prepare(
				"INSERT INTO ordini_produzione(op_IdProduzione, op_Stato, op_Prodotto, op_QtaRichiesta, op_QtaDaProdurre, op_LineaProduzione, op_DataOrdine, op_OraOrdine, op_DataProduzione, op_OraProduzione, op_Priorita, op_Lotto, op_Udm)
				VALUES (:IdProduzione, 1, :Prodotto, :QtaRichiesta, :QtaDaProdurre, 'lin_01', :DataOrdine, :OraOrdine, :DataProduzione, :OraProduzione, 1, :Lotto, 1)"
			);
			$sth->execute([
				'IdProduzione' => $ordine['numero'],
				'Prodotto' => $ordine['Prodotto'],
				'QtaRichiesta' => floatVal($ordine['Quantita']),
				'QtaDaProdurre' => floatVal($ordine['Quantita']),
				'DataOrdine' => $dataOrdine->format('Y-m-d'),
				'OraOrdine' => $dataOrdine->format('H:i:s'),
				'DataProduzione' => $dataOrdine->format('Y-m-d'),
				'OraProduzione' => $dataOrdine->format('H:i:s'),
				'Lotto' => $ordine['commessa'],
			]);
		}

		$sth = $conn_erp->prepare(
			"UPDATE ordini_mes SET
			letto_da_mes = 1
			WHERE numero = :numero"
		);
		$sth->execute(['numero' => $ordine['numero']]);
	}
	$conn_mes->commit();
	$conn_erp->commit();
} catch (\Throwable $th) {
	$conn_mes->rollBack();
	$conn_erp->rollBack();
}
