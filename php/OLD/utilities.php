<?php

	// in che pagina siamo
	$pagina = 'utilities';

	include("../inc/conn.php");

	// WATCHDOG: rilevazione stato di anomalia SCADA per sistema MES
	if(!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'verifica-watchdog-SCADA')
	{
		// aggiorno lo stato risorsa nella tabella 'risorse', impostandola come 'KO'
		$sqlWDVerifica = "SELECT watchdog.* FROM watchdog";
		$sthWDVerifica = $conn_mes->prepare($sqlWDVerifica);
		$sthWDVerifica->execute();

		$riga = $sthWDVerifica->fetch(PDO::FETCH_ASSOC);

		// sintesi esito, ritorno segnalazione di 'caso bloccante', inizia conteggio di downtime
		if ($riga['wd_Abilitazione'] == false) {
			die('DISABILITATO');
		}
		else {

			if ($riga['wd_AnomaliaSCADA'] == true) {
				die('AVARIA_SCADA');
			}
			else {
				die('OK');
			}
		}
	}




	// INSERIMENTO COMMESSE: RECUPERO UDM PRODOTTO SELEZIONATO
	if(!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'recupera-udm' && !empty($_REQUEST['codice']))
	{
		// estraggo la lista
		$sth = $conn_mes->prepare("SELECT prodotti.prd_UnitaMisura FROM prodotti WHERE prodotti.prd_IdProdotto = :codice", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
		$sth->execute([':codice' => $_REQUEST['codice']]);
		$riga = $sth->fetch(PDO::FETCH_ASSOC);

		die($riga['prd_UnitaMisura']);
	}







?>

