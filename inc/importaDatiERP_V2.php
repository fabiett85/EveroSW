<?php

const dizionarioCentriLinee = [
	1 => 'lin_01',
];

if (empty($pagina)) {
	$pagina = 'importDati';
}
require_once('../inc/conn.php');
require_once('../inc/connERP.php');




$conn_erp->beginTransaction();
$conn_mes->beginTransaction();

try {

	$sth = $conn_erp->prepare(
		"SELECT
			CONCAT(lce_barcode, '-', lce_progr) AS op_IdProduzione,
			mo_codart AS op_Prodotto,
			mo_descr AS prd_Descrizione,
			lce_qtaresi AS op_QtaRichiesta,
			FORMAT(lce_start,'yyyy-MM-dd') AS op_DataOrdine,
			FORMAT(lce_start,'HH:mm:ss') AS op_OraOrdine,
			FORMAT(lce_start,'yyyy-MM-dd') AS op_DataProduzione,
			FORMAT(lce_start,'HH:mm:ss') AS op_OraProduzione,
			tb_codumis AS um_Sigla,
			tb_desumis AS um_Descrizione,
			as_codcent AS codiceCentro,
			FORMAT(td_datcons,'yyyy-MM-dd') AS op_DataConsegna
			FROM avlavp AS AV
		LEFT JOIN testord AS TSTO ON AV.codditt = TSTO.codditt AND lce_ornum = td_numord AND lce_orserie = td_serie AND lce_oranno = td_anno AND lce_ortipo = td_tipork
		LEFT JOIN movord AS MO ON MO.codditt = TSTO.codditt AND td_tipork = mo_tipork AND td_anno = mo_anno AND td_serie = mo_serie AND td_numord = mo_numord AND mo_riga = lce_orriga
		LEFT JOIN assris AS ASR ON ASR.codditt = TSTO.codditt AND td_tipork = as_tipork AND td_anno = as_anno AND td_serie = as_serie AND td_numord = as_numord AND mo_riga = as_riga
		LEFT JOIN artico AS AR ON AR.ar_codart = MO.mo_codart
		LEFT JOIN tabumis AS TB ON TB.tb_codumis = AR.ar_unmis
		WHERE as_codcent = 1 AND lce_stato = 'A'"
	);
	$sth->execute();
	$ordiniErp = $sth->fetchAll();

	foreach ($ordiniErp as $ordineErp) {
		/*
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
					':Sigla' => $ordineErp['um_Sigla'],
					':Descrizione' => $ordineErp['um_Descrizione']
				]);

				$sth = $conn_mes->prepare(
					"SELECT um_IdRiga FROM unita_misura
					WHERE um_Sigla = :Sigla"
				);
				$sth->execute(['Sigla' => $ordineErp['um_Sigla']]);
				$idUdm = $sth->fetch();
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
					op_Udm,
					op_DataConsegna
			  ) VALUES (
					:IdProduzione,
					1,
					:Prodotto,
					:QtaRichiesta,
					:QtaDaProdurre,
					:LineaProduzione,
					:DataOrdine,
					:OraOrdine,
					:DataProduzione,
					:OraProduzione,
					1,
					:Lotto,
					:Udm,
					:DataConsegna
				)"
			);
			$sth->execute([
				'IdProduzione' => $ordineErp['op_IdProduzione'],
				'Prodotto' => $ordineErp['op_Prodotto'],
				'QtaRichiesta' => $ordineErp['op_QtaRichiesta'],
				'QtaDaProdurre' => $ordineErp['op_QtaRichiesta'],
				'LineaProduzione' => dizionarioCentriLinee[$ordineErp['codiceCentro']],
				'DataOrdine' => $ordineErp['op_DataOrdine'],
				'OraOrdine' => $ordineErp['op_OraOrdine'],
				'DataProduzione' => $ordineErp['op_DataProduzione'],
				'OraProduzione' => $ordineErp['op_OraProduzione'],
				'Lotto' => $ordineErp['op_IdProduzione'],
				'Udm' => $idUdm['um_IdRiga'],
				'DataConsegna' => $ordineErp['op_DataConsegna'],
			]);

			/*
			Importo la distinta base.
			*/

			$sth = $conn_erp->prepare(
				"SELECT
					md_codfigli AS cmp_Componente,
					md_ump AS um_Sigla,
					md_quantump AS cmp_FattoreMoltiplicativo,
					md_perpz AS cmp_PezziConfezione,
					ar_descr AS prd_Descrizione,
					tb_codumis AS um_Sigla,
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
				*/
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
						':Sigla' => $componenteErp['um_Sigla'],
						':Descrizione' => $componenteErp['um_Descrizione']
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
				*/
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

				$fattore = $componenteErp['cmp_FattoreMoltiplicativo'] / $componenteErp['cmp_PezziConfezione'];
				$qta = $fattore * floatval($ordineErp['op_QtaRichiesta']);

				$sth = $conn_mes->prepare(
					"INSERT INTO componenti(
						cmp_IdProduzione,
						cmp_Componente,
						cmp_LineaProduzione,
						cmp_Qta,
						cmp_Udm,
						cmp_FattoreMoltiplicativo,
						cmp_PezziConfezione
					) VALUES (
						:IdProduzione,
						:Componente,
						:LineaProduzione,
						:Qta,
						:Udm,
						:FattoreMoltiplicativo,
						:PezziConfezione
					)"
				);
				$sth->execute([
					'IdProduzione' => $ordineErp['op_IdProduzione'],
					'Componente' => $componenteErp['cmp_Componente'],
					'LineaProduzione' => dizionarioCentriLinee[$ordineErp['codiceCentro']],
					'Qta' => $qta,
					'Udm' => $idUdm['um_IdRiga'],
					'FattoreMoltiplicativo' => $componenteErp['cmp_FattoreMoltiplicativo'],
					'PezziConfezione' => $componenteErp['cmp_PezziConfezione'],
				]);
			}
		}
	}

	$conn_mes->commit();
	$conn_erp->commit();
} catch (\Throwable $th) {
	$conn_mes->rollBack();
	$conn_erp->rollBack();
}
