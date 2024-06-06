<?php

try {
	$dbName = "//192.168.5.5/mediasoft/geminixp/database/G2015002.mdb";
	if (!file_exists($dbName)) {
		die("Could not find database file.");
	}
	$conn_erp = new PDO("odbc:DRIVER={Microsoft Access Driver (*.mdb, *.accdb)}; DBQ=$dbName; Uid=; Pwd=;", "", "", [
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
	]);
} catch (\Throwable $th) {
	die("KO");
}

if (empty($pagina)) {
	$pagina = 'importDati';
}
require_once('../inc/conn.php');

$conn_erp->beginTransaction();
$conn_mes->beginTransaction();

try {
	$conto = false;

	$sth = $conn_erp->prepare(
		"SELECT
			D.Documento,
			D.Numero,
			DC.Riga,
			DC.Quantita_1 AS op_QtaRichiesta,
			A.Articolo AS op_Prodotto,
			A.Descrizione AS prd_Descrizione,
			A.Unita_di_Misura AS um_Sigla,
			UM.Descrizione AS um_Descrizione,
			D.Data AS DataOrdine
		FROM ((((Documenti AS D
		LEFT JOIN Documenti_Corpo AS DC ON DC.Documento = D.Documento AND D.Numero = DC.Numero)
		LEFT JOIN Articoli AS A ON DC.Articolo = A.Articolo)
		LEFT JOIN Unita_Misura AS UM ON UM.Codice = A.Unita_di_Misura)
		LEFT JOIN Destinatari AS DST ON DST.Codice = D.Destinatario)
		WHERE D.Documento = 'FT' AND DC.Riga = 1 AND A.Articolo IS NOT NULL
		ORDER BY D.Numero, DC.Riga ASC"
	);

	$sth->execute();
	$ordiniErp = $sth->fetchAll();

	foreach ($ordiniErp as $ordineErp) {
		$ordineErp['op_IdProduzione'] = $ordineErp['Documento'] . '-' . $ordineErp['Numero'];
		$ordineErp['op_Riferimento'] = '';
		$data = date_create_from_format('Y-m-d H:i:s', $ordineErp['DataOrdine']);
		$ordineErp['op_DataOrdine'] = $data->format('Y-m-d');
		$ordineErp['op_OraOrdine'] = $data->format('H:i:s');
		$ordineErp['op_DataProduzione'] = $data->format('Y-m-d');
		$ordineErp['op_OraProduzione'] = $data->format('H:i:s');
		$ordineErp['prd_Descrizione'] = mb_convert_encoding($ordineErp['prd_Descrizione'], 'UTF-8', 'Windows-1252');

		if (true){/*
		Controllo se ho già l'ordine
		*/
		$sth = $conn_mes->prepare(
			"SELECT op_IdProduzione FROM ordini_produzione
			WHERE op_IdProduzione = :IdProduzione"
		);
		$sth->execute(['IdProduzione' => $ordineErp['op_IdProduzione']]);
		$ordineMes = $sth->fetch();

		if (!$ordineMes) {

			/*
			Importo l'unità di misura a cui è riferito il prodotto se non la ho già
			*/
			$sth = $conn_mes->prepare(
				"SELECT um_IdRiga FROM unita_misura
				WHERE um_Sigla = :Sigla"
			);
			$sth->execute(['Sigla' => $ordineErp['um_Sigla']]);
			$idUdm = $sth->fetch();

			if (!$idUdm) {
				if (empty($ordineErp['um_Sigla'])) {
					$idUdm['um_IdRiga'] = 1;
				} else {
					$sth = $conn_mes->prepare(
						"INSERT INTO unita_misura(
							um_Sigla,
							um_Descrizione
						)
						VALUES (
							:Sigla,
							:Descrizione
						)"
					);
					$sth->execute([
						'Sigla' => $ordineErp['um_Sigla'],
						'Descrizione' => $ordineErp['um_Descrizione']
					]);

					$sth = $conn_mes->prepare(
						"SELECT um_IdRiga FROM unita_misura
						WHERE um_Sigla = :Sigla"
					);
					$sth->execute(['Sigla' => $ordineErp['um_Sigla']]);
					$idUdm = $sth->fetch();
				}
			}

			/*
			Importo il prodotto a cui è riferito l'ordine se non lo ho già
			*/
			$sth = $conn_mes->prepare(
				"SELECT prd_IdProdotto FROM prodotti
				WHERE prd_IdProdotto = :IdProdotto"
			);
			$sth->execute(['IdProdotto' => $ordineErp['op_Prodotto']]);
			$prodotto = $sth->fetch();
			if (!$prodotto) {

				$sth = $conn_mes->prepare(
					"INSERT INTO prodotti(
						prd_IdProdotto,
						prd_Descrizione,
						prd_Tipo,
						prd_UnitaMisura
					)
					VALUES (
						:IdProdotto,
						:Descrizione,
						'F',
						:UnitaMisura
					)"
				);
				$sth->execute([
					'IdProdotto' => $ordineErp['op_Prodotto'],
					'Descrizione' => $ordineErp['prd_Descrizione'],
					'UnitaMisura' => $idUdm['um_IdRiga'],
				]);
			}

			/*
			Ora che ho il prodotto con la sua udm posso inserire l'ordine
			*/
			$sth = $conn_mes->prepare(
				"INSERT INTO ordini_produzione(
					op_IdProduzione,
					op_Riferimento,
					op_Stato,
					op_Prodotto,
					op_QtaRichiesta,
					op_QtaDaProdurre,
					op_LineaProduzione,
					op_DataOrdine,
					op_OraOrdine,
					op_DataProduzione,
					op_OraProduzione,
					op_Priorita,
					op_Lotto,
					op_Udm
			  	) VALUES (
					:IdProduzione,
					:Riferimento,
					1,
					:Prodotto,
					:QtaRichiesta,
					:QtaDaProdurre,
					'lin_01',
					:DataOrdine,
					:OraOrdine,
					:DataProduzione,
					:OraProduzione,
					1,
					:Lotto,
					:Udm
				)"
			);
			$sth->execute([
				'IdProduzione' => $ordineErp['op_IdProduzione'],
				'Riferimento' => $ordineErp['op_Riferimento'],
				'Prodotto' => $ordineErp['op_Prodotto'],
				'QtaRichiesta' => $ordineErp['op_QtaRichiesta'],
				'QtaDaProdurre' => $ordineErp['op_QtaRichiesta'],
				'DataOrdine' => $ordineErp['op_DataOrdine'],
				'OraOrdine' => $ordineErp['op_OraOrdine'],
				'DataProduzione' => $ordineErp['op_DataProduzione'],
				'OraProduzione' => $ordineErp['op_OraProduzione'],
				'Lotto' => $ordineErp['op_IdProduzione'],
				'Udm' => $idUdm['um_IdRiga'],
			]);

			/*
			Importo la distinta base.
			*/

			/* $sth = $conn_erp->prepare(
				"SELECT
					md_codfigli AS cmp_Componente,
					md_ump AS um_Sigla,
					md_perpz AS cmp_FattoreMoltiplicativo,
					ar_descr AS prd_Descrizione,
					tb_desumis AS um_Descrizione
				FROM movdis AS MD
				LEFT JOIN artico AS AR ON AR.ar_codart = MD.md_codfigli
				LEFT JOIN tabumis AS TB ON TB.tb_codumis = MD.md_ump
				WHERE MD.md_coddb = :Prodotto"
			);
			$sth->execute(['Prodotto' => $ordineErp['op_Prodotto']]);
			$componentiErp = $sth->fetchAll();

			foreach ($componentiErp as $componenteErp) {
				/*
				Importo l'unità di misura a cui è riferito il prodotto se non la ho già
				/
				$sth = $conn_mes->prepare(
					"SELECT um_IdRiga FROM unita_misura
					WHERE um_Sigla = :Sigla"
				);
				$sth->execute(['Sigla' => $componenteErp['um_Sigla']]);
				$idUdm = $sth->fetch();

				if (!$idUdm) {
					$sth = $conn_mes->prepare(
						"INSERT INTO unita_misura(
							um_Sigla,
							um_Descrizione
						)
						VALUES (
							:Sigla,
							:Descrizione
						)"
					);
					$sth->execute([
						'Sigla' => $componenteErp['um_Sigla'],
						'Descrizione' => $componenteErp['um_Descrizione']
					]);

					$sth = $conn_mes->prepare(
						"SELECT um_IdRiga FROM unita_misura
						WHERE um_Sigla = :Sigla"
					);
					$sth->execute(['Sigla' => $componenteErp['um_Sigla']]);
					$idUdm = $sth->fetch();
				}

				/*
				Importo il prodotto a cui è riferito l'ordine se non lo ho già
				/
				$sth = $conn_mes->prepare(
					"SELECT prd_IdProdotto FROM prodotti
					WHERE prd_IdProdotto = :IdProdotto"
				);
				$sth->execute(['IdProdotto' => $componenteErp['cmp_Componente']]);
				$prodotto = $sth->fetch();
				if (!$prodotto) {

					$sth = $conn_mes->prepare(
						"INSERT INTO prodotti(
							prd_IdProdotto,
							prd_Descrizione,
							prd_Tipo,
							prd_UnitaMisura
						)
						VALUES (
							:IdProdotto,
							:Descrizione,
							'MP',
							:UnitaMisura
						)"
					);
					$sth->execute([
						'IdProdotto' => $componenteErp['cmp_Componente'],
						'Descrizione' => $componenteErp['prd_Descrizione'],
						'UnitaMisura' => $idUdm['um_IdRiga'],
					]);
				}

				$sth = $conn_mes->prepare(
					"INSERT INTO componenti(
						cmp_IdProduzione,
						cmp_Componente,
						cmp_LineaProduzione,
						cmp_Qta,
						cmp_Udm,
						cmp_FattoreMoltiplicativo
					) VALUES (
						:IdProduzione,
						:Riferimento,
						'lin_01',
						:Qta,
						:Udm,
						:FattoreMoltiplicativo
					)"
				);
				$sth->execute([
					'IdProduzione' => $ordineErp['op_IdProduzione'],
					'Componente' => $componenteErp['op_Riferimento'],
					'Qta' => ceil(floatval($componenteErp['cmp_FattoreMoltiplicativo']) * floatval($ordineErp['op_QtaRichiesta'])),
					'Udm' => $idUdm['um_IdRiga'],
					'FattoreMoltiplicativo' => $componenteErp['cmp_FattoreMoltiplicativo'],
				]);
			} */
		}}
	}

	$conn_mes->commit();
	$conn_erp->commit();
} catch (\Throwable $th) {
	$conn_mes->rollBack();
	$conn_erp->rollBack();
	die($th->getMessage());
}
