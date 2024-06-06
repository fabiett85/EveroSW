<?php

try {
	$dbName = "C:\G2015002.mdb";
	if (!file_exists($dbName)) {
		die("Could not find database file.");
	}
	$conn = new PDO("odbc:DRIVER={Microsoft Access Driver (*.mdb, *.accdb)}; DBQ=$dbName; Uid=; Pwd=;");
} catch (\Throwable $th) {
	die("KO");
}



if ($conn) {
	$stmt = $conn->prepare(
		"SELECT * FROM documenti"
	);
	$stmt->execute();
	$docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

	foreach ($docs as $doc) {
		foreach ($doc as $campo => $valore) {
			echo $campo . ':' . $valore . '<br>';
		}
		echo '<br>';
	}
}
