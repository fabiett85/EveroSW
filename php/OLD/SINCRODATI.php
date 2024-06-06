<?php
//*** SINCRONIZZAZIONE DATI TABELLA ESL1 ***
//Funzione dedicata alla sincronizzazione dei dati relativi alle lavagne elettroniche, con quelli provenienti dal DB del gestionale
function sincroData_Cantina_adm($data,$con,$prova){
	
	//Determino se la sincronizzazione è di tipo schedulato o richiesta dall'utente
	$tipoSincro = (int)$data['tipoSincro'];
	
	
	//Se richiesta di sincronizzazione proviene da schedulazione, aggiorno il timestamp di invocazione
	if ($tipoSincro == 0) {
		$now = microtime(true) * 1000;
		file_put_contents('config/microtime_Syncro.txt', $now);
	}	
	
	$campo_2_codice = "";
	$campo_1_capacita = "";
	$campo_4_categoriaProdotto = "";
	$campo_5_nomeProdotto = "";
	$campo_6_attoCertificazione = "";
	$campo_7_classificazioneProdotto = "";
	$campo_3_anno = "";
	$campo_8_colore = "";
	$campo_10_statoProdotto = "";
	$campo_9_biologico = "";
	$appoggio_nomeSerbatoio = "";
   
																													 
	
	//Azzeramento valori di errore
	$errorePrimoUpdateTabEsl = $errorePrimoUpdateTabContenitori = $erroreUpdateTabSerbatoi = $erroreUpdateTabEsl = $erroreAperturaSorgente = 0;		
	
	//SE MODO DI SINCRONIZZAZIONE = TABELLA DI SCAMBIO SQL SERVER: ****************************************************************************************************
	if ($_SESSION['opt_sorgenteSinc'] == 0) {
		
		//Preparazione query di selezione dati da tabella di scambio
		$paramsSelectTabScambio = array();
		$sqlSelectTabScambio = "SELECT *
								FROM ScambioDatiEsl";
		$resSelectTabScambio = sqlsrv_query($con, $sqlSelectTabScambio, $paramsSelectTabScambio);
		
		if(!sqlsrv_has_rows($resSelectTabScambio)){
								
			echo "Errore in esecuzione query di selezione da tabella Gestionale";		
			if( ($errors = sqlsrv_errors() ) != null) {
				foreach( $errors as $error ) {
					echo "SQLSTATE: ".$error[ 'SQLSTATE']."<br />";
					echo "code: ".$error[ 'code']."<br />";
					echo "message: ".$error[ 'message']."<br />";
				}
			}
			$erroreAperturaSorgente = 1;
		}
		else {
			
			//VERIFICA SE DEVO ESEGUIRE IL PRIMO AGGIORNAMENTO O SE SONO AGLI AGGIORNAMENTI SUCCESSIVI
			$paramsVerificaIAggiornamento = array();
			$sqlVerificaIAggiornamento = "SELECT * 
										  FROM Cantina
										  WHERE Cantina.Codice_contenitore_cliente IS NOT NULL";
			$numeroRighe = sqlsrv_query($con, $sqlVerificaIAggiornamento, $paramsVerificaIAggiornamento);
			
			//1° AGGIORNAMENTO: se le righe della tabella non sono provviste del codice cliente, significa che devo eseguire il primo aggiornamento...
			if (!sqlsrv_has_rows($numeroRighe)) {
				$rowTabellaCantina = 1;
							
				while($rowSelectTabScambio = sqlsrv_fetch_array($resSelectTabScambio, SQLSRV_FETCH_ASSOC)){
							
					$campo_2_codice = (string)trim($rowSelectTabScambio['Codice_contenitore_cliente']);
					
					//BLOCCO PER INSERIMENTO IN TABELLA CANTINA E CONTENITORE DEI CODICE CONTENITORI USATI DAL GESTIONALE CLIENTE
					//Codice serbatoio interno a SW Cantina per 
					$codiceSerbatoioInterno = "RecipCod_".$rowTabellaCantina;
					
					//Parametri query di aggiornamento 
					$paramsUpdateCodiciTabSerbatoi = array(&$codiceSerbatoioInterno);
					$paramsUpdateCodiciTabEsl = array(&$codiceSerbatoioInterno);
					$sqlUpdateCodiciTabSerbatoi = "UPDATE Contenitori 
												   SET Contenitori.Codice_contenitore_cliente = '$campo_2_codice', Contenitori.Nome ='$campo_2_codice'
												   WHERE Contenitori.Codice_contenitore=?";
					$sqlUpdateCodiciTabEsl = "UPDATE Cantina 
					                          SET Cantina.Codice_contenitore_cliente = '$campo_2_codice' 
											  WHERE Cantina.Numero_serbatoio=?";
					
					//Eseguo le due query di aggiornamento per:
					//tabella serbatoi
					if(!$resUpdateCodiciTabSerbatoi = sqlsrv_query($con, $sqlUpdateCodiciTabSerbatoi, $paramsUpdateCodiciTabSerbatoi)){
						$errorePrimoUpdateTabContenitori = 1;
						echo "Errore query di sincronizzazione dati tra tabella Gestionale e tabella Serbatoi";
						if( ($errors = sqlsrv_errors() ) != null) {
							foreach( $errors as $error ) {
								echo "SQLSTATE: ".$error[ 'SQLSTATE']."<br />";
								echo "code: ".$error[ 'code']."<br />";
								echo "message: ".$error[ 'message']."<br />";
							}
						}
					}
					//tabella lavagne elettroniche
					if(!$resUpdateCodiciTabEsl = sqlsrv_query($con, $sqlUpdateCodiciTabEsl, $paramsUpdateCodiciTabEsl)){
						$errorePrimoUpdateTabEsl = 1;
						echo "Errore query di sincronizzazione dati tra tabella Gestonale e tabella Esl";
						if( ($errors = sqlsrv_errors() ) != null) {
							foreach( $errors as $error ) {
								echo "SQLSTATE: ".$error[ 'SQLSTATE']."<br />";
								echo "code: ".$error[ 'code']."<br />";
								echo "message: ".$error[ 'message']."<br />";
							}
						}
					}
					$rowTabellaCantina = $rowTabellaCantina+1;	
				}
			}
			//AGGIORNAMENTI SUCCESSIVI: se almeno una riga della tabella è provvista del codice cliente, significa che sto eseguendo aggiornamenti successivi... 
			sqlsrv_fetch_array ($resSelectTabScambio, SQLSRV_FETCH_BOTH, SQLSRV_SCROLL_ABSOLUTE, 0);
			while($rowSelectTabScambio = sqlsrv_fetch_array($resSelectTabScambio, SQLSRV_FETCH_ASSOC)){	
			

				$campo_1_capacita = (float)trim($rowSelectTabScambio['Capacita']);
				$campo_2_codice = (string)trim($rowSelectTabScambio['Codice_contenitore_cliente']);		
				$campo_3_anno = (string)trim($rowSelectTabScambio['Anno_produzione']);
				$campo_4_categoriaProdotto= (string)trim($rowSelectTabScambio['Categoria_prodotto']);
				$campo_5_nomeProdotto = (string)trim($rowSelectTabScambio['Nome_prodotto']);
				$campo_6_attoCertificazione = (string)trim($rowSelectTabScambio['Atto_certificazione']);
				$campo_7_classificazioneProdotto = (string)trim($rowSelectTabScambio['Classificazione_prodotto']);
				$campo_8_colore = (string)trim($rowSelectTabScambio['Colore_prodottto']);
				$campo_9_biologico = (string)trim($rowSelectTabScambio['Gradazione_alcolica']); 				
				$campo_10_statoProdotto = (string)trim($rowSelectTabScambio['Stato_prodotto']); 
				$campo_11_menzioneProdotto = (string)trim($rowSelectTabScambio['Menzione_prodotto']); 
				$appoggio_nomeSerbatoio = (string)trim($rowSelectTabScambio['Nome_contenitore']);
				
				$codiceSerbatoioCliente = $campo_2_codice;

				//Parametri query di aggiornamento dati
				$paramsUpdateDatiTabSerbatoi = array(&$codiceSerbatoioCliente);
				$paramsUpdateDatiTabEsl = array(&$codiceSerbatoioCliente);
				$sqlUpdateDatiTabSerbatoi = "UPDATE Contenitori 
												   SET Contenitori.Capacita = $campo_1_capacita
												   WHERE Contenitori.Codice_contenitore_cliente=?";
				$sqlUpdateDatiTabEsl = "UPDATE Cantina 
										SET Cantina.ESL_campo_1 = $campo_1_capacita, Cantina.ESL_campo_2 = '$campo_4_categoriaProdotto', Cantina.ESL_campo_3 = '$campo_5_nomeProdotto', Cantina.ESL_campo_4 = '$campo_6_attoCertificazione', Cantina.ESL_campo_5 = '$campo_7_classificazioneProdotto', Cantina.ESL_campo_6 = '$campo_3_anno', Cantina.ESL_campo_7 = '$campo_8_colore', Cantina.ESL_campo_8 = '$campo_10_statoProdotto', Cantina.ESL_campo_9 = '$campo_9_biologico', Cantina.ESL_campo_10 = '$campo_11_menzioneProdotto'  
										WHERE Cantina.Codice_contenitore_cliente=?";
			
				//Eseguo le due query di aggiornamento, una per la tabella Serbatoi e una per la tabella Esl
				//tabella serbatoi
				if(!$resUpdateDatiTabSerbatoi = sqlsrv_query($con, $sqlUpdateDatiTabSerbatoi, $paramsUpdateDatiTabSerbatoi)){
					$erroreUpdateTabSerbatoi = 1;
					echo "Errore query di sincronizzazione dati tra tabella Gestionale e tabella Serbatoi.";
					if( ($errors = sqlsrv_errors() ) != null) {
						foreach( $errors as $error ) {
							echo "SQLSTATE: ".$error[ 'SQLSTATE']."<br />";
							echo "code: ".$error[ 'code']."<br />";
							echo "message: ".$error[ 'message']."<br />";
						}
					}
				}
				//tabella Esl
				if(!$resUpdateDatiTabEsl = sqlsrv_query($con, $sqlUpdateDatiTabEsl, $paramsUpdateDatiTabEsl)){
					$erroreUpdateTabEsl = 1;
					echo "Errore query di sincronizzazione dati tra tabella Gestionale e tabella Esl.";
					if( ($errors = sqlsrv_errors() ) != null) {
						foreach( $errors as $error ) {
							echo "SQLSTATE: ".$error[ 'SQLSTATE']."<br />";
							echo "code: ".$error[ 'code']."<br />";
							echo "message: ".$error[ 'message']."<br />";
						}
					}
				}
			}
		}
	}


	//SE MODO DI SINCRONIZZAZIONE FILE E' CSV ****************************************************************************************************
	else if ($_SESSION['opt_sorgenteSinc'] == 1){
		
		//Ricavo path del file CSV da cui importare i valori e relativo separatoreCSV e ne verifico l'esistenza 
		$separatoreCSV = $_SESSION['opt_segnoSeparatore'];
		$urlCSV = $_SESSION['opt_pathCSV'];
		$arrayDatiLinea = array();
		
		//Apro il file e verifico se l'apertura va a buon fine (file presente? problemi di permessi?)
		$fileCSVScambio = fopen($_SESSION['opt_pathCSV'], "r");
		if (!$fileCSVScambio) {
			$erroreAperturaSorgente = 1;
		}
		else {
			
			echo "File CSV trovato\n";
			
			//VERIFICA SE DEVO ESEGUIRE IL PRIMO AGGIORNAMENTO O SE SONO AGLI AGGIORNAMENTI SUCCESSIVI
			$paramsVerificaIAggiornamento = array();
			$sqlVerificaIAggiornamento = "SELECT TOP(10) Cantina.Codice_contenitore_cliente
										FROM Cantina 
										WHERE Cantina.Codice_contenitore_cliente <> ''";
			$numeroRighe = sqlsrv_query($con, $sqlVerificaIAggiornamento, $paramsVerificaIAggiornamento);
			
			//1° AGGIORNAMENTO: se le righe della tabella non sono provviste del codice cliente, significa che devo eseguire il primo aggiornamento...
			if (!sqlsrv_has_rows($numeroRighe)) {
				
				echo "Primo aggiornamento\n"; //+++++++++++++++++++++++++++++++++++++++++++++
				
				$rowTabellaCantina = 0;
				
				//Finché non arrivo a leggere l'intero file
				while(($lineaLetta = fgets($fileCSVScambio)) !== false) {
					
					//Salto la prima riga (quella relativa all'intestazione)
					if ($rowTabellaCantina != 0) {
					
						//Splitto e memorizzo in un array i valori ricavati dalla linea letta (utilizzando il separatore impostato)
						$arrayDatiLinea = str_getcsv ($lineaLetta, $separatoreCSV);
						
						//Ricavo campi di interesse per la tabella contenitori
						$campo_2_codice = (string)trim($arrayDatiLinea[1]);				
						$campo_1_capacita = (float)($arrayDatiLinea[2]);
						
						
						//BLOCCO PER INSERIMENTO IN TABELLA CANTINA E CONTENITORE DEI CODICE CONTENITORI USATI DAL GESTIONALE CLIENTE
						//Codice serbatoio interno a SW Cantina per 
						$codiceSerbatoioInterno = "RecipCod_".$rowTabellaCantina;
						$descrizioneSerbatoio = "Recipiente ".$campo_2_codice;
						
						//Parametri query di aggiornamento 
						$paramsUpdateCodiciTabSerbatoi = array(&$codiceSerbatoioInterno);
						$paramsUpdateCodiciTabEsl = array(&$codiceSerbatoioInterno);
						$sqlUpdateCodiciTabSerbatoi = "UPDATE Contenitori 
													   SET Contenitori.Codice_contenitore_cliente = '$campo_2_codice', Contenitori.Nome ='$campo_2_codice', Contenitori.Descrizione = '$descrizioneSerbatoio', Contenitori.Capacita = $campo_1_capacita
													   WHERE Contenitori.Codice_contenitore=?";
						$sqlUpdateCodiciTabEsl = "UPDATE Cantina 
												  SET Cantina.Codice_contenitore_cliente = '$campo_2_codice' 
												  WHERE Cantina.Numero_serbatoio=?";
						
						//Eseguo le due query di aggiornamento, una per la tabella Serbatoi e una per la tabella Esl
						//tabella serbatoi
						if(!$resUpdateCodiciTabSerbatoi = sqlsrv_query($con, $sqlUpdateCodiciTabSerbatoi, $paramsUpdateCodiciTabSerbatoi)){
							$errorePrimoUpdateTabContenitori = 1;						
							echo "Errore query di sincronizzazione dati tra tabella Gestionale e tabella Serbatoi";
							if( ($errors = sqlsrv_errors() ) != null) {
								foreach( $errors as $error ) {
									echo "SQLSTATE: ".$error[ 'SQLSTATE']."<br />";
									echo "code: ".$error[ 'code']."<br />";
									echo "message: ".$error[ 'message']."<br />";
								}
							}
						}
						
						//tabella Esl
						if(!$resUpdateCodiciTabEsl = sqlsrv_query($con, $sqlUpdateCodiciTabEsl, $paramsUpdateCodiciTabEsl)){
							$errorePrimoUpdateTabEsl = 1;						
							echo "Errore query di sincronizzazione dati tra tabella Gestonale e tabella Esl";
							if( ($errors = sqlsrv_errors() ) != null) {
								foreach( $errors as $error ) {
									echo "SQLSTATE: ".$error[ 'SQLSTATE']."<br />";
									echo "code: ".$error[ 'code']."<br />";
									echo "message: ".$error[ 'message']."<br />";
								}
							}
						}
					}
					$rowTabellaCantina = $rowTabellaCantina+1;
				}
			}
			//AGGIORNAMENTI SUCCESSIVI: se almeno una riga della tabella è provvista del codice cliente, significa che sto eseguendo aggiornamenti successivi... 
			rewind($fileCSVScambio);
			$rowTabellaCantina = 0;
			
			//Finché non arrivo a leggere l'intero file
			while(($lineaLetta = fgets($fileCSVScambio)) !== false) {
				
				//Salto la prima riga (quella relativa all'intestazione)
				if ($rowTabellaCantina != 0) {
				
					//Splitto e memorizzo in un array i valori ricavati dalla linea letta (utilizzando il separatore impostato)
					$arrayDatiLinea = str_getcsv ($lineaLetta, $separatoreCSV);
					
					//Inizializzo tutte le variabili con i relativi valori dell'array
					$campo_1_capacita = (float)trim($arrayDatiLinea[2]);
					$campo_2_codice = (string)trim($arrayDatiLinea[1]);				
					$campo_3_anno = (string)trim($arrayDatiLinea[3]); 
					$campo_4_categoriaProdotto= (string)trim($arrayDatiLinea[4]);
					$campo_5_nomeProdotto = (string)trim($arrayDatiLinea[5]);
					$campo_6_attoCertificazione = (string)trim($arrayDatiLinea[6]);
					$campo_7_classificazioneProdotto = (string)trim($arrayDatiLinea[7]);
					$campo_8_colore = (string)trim($arrayDatiLinea[8]); 
					$campo_9_biologico = (string)trim($arrayDatiLinea[9]);
					$campo_10_statoProdotto = (string)trim($arrayDatiLinea[10]); 
					$campo_11_menzioneProdotto = (string)trim($arrayDatiLinea[11]); 				
					$codiceSerbatoioCliente = trim($campo_2_codice);


					//Sostituisco i caratteri speciali con un carattere che non crei problemi nell'esecuzione delle query sql
					$campo_1_capacita = str_replace(",", ".", $campo_1_capacita);
					$campo_1_capacita = str_replace(",", ".", $campo_1_capacita);

					$campo_4_categoriaProdotto = str_replace("'", "`", $campo_4_categoriaProdotto);
					$campo_4_categoriaProdotto = str_replace('"', '`', $campo_4_categoriaProdotto);
					
					$campo_5_nomeProdotto = str_replace("'", "`", $campo_5_nomeProdotto);
					$campo_5_nomeProdotto = str_replace('"', '`', $campo_5_nomeProdotto);
					
					$campo_6_attoCertificazione = str_replace("'", "`", $campo_6_attoCertificazione);
					$campo_6_attoCertificazione = str_replace('"', '`', $campo_6_attoCertificazione);
					
					$campo_7_classificazioneProdotto = str_replace("'", "`", $campo_7_classificazioneProdotto);
					$campo_7_classificazioneProdotto = str_replace('"', '`', $campo_7_classificazioneProdotto);
					

					//Parametri query di aggiornamento dati
					$paramsUpdateDatiTabSerbatoi = array(&$codiceSerbatoioCliente);
					$paramsUpdateDatiTabEsl = array(&$codiceSerbatoioCliente);
					$sqlUpdateDatiTabSerbatoi = "UPDATE Contenitori 
													   SET Contenitori.Capacita = $campo_1_capacita
													   WHERE Contenitori.Codice_contenitore_cliente=?";
					$sqlUpdateDatiTabEsl = "UPDATE Cantina 
											SET  Cantina.ESL_campo_1 = $campo_1_capacita, Cantina.ESL_campo_2 = '$campo_4_categoriaProdotto', Cantina.ESL_campo_3 = '$campo_5_nomeProdotto', Cantina.ESL_campo_4 = '$campo_6_attoCertificazione', Cantina.ESL_campo_5 = '$campo_7_classificazioneProdotto', Cantina.ESL_campo_6 = '$campo_3_anno', Cantina.ESL_campo_7 = '$campo_8_colore', Cantina.ESL_campo_8 = '$campo_10_statoProdotto', Cantina.ESL_campo_9 = '$campo_9_biologico', Cantina.ESL_campo_10 = '$campo_11_menzioneProdotto'  
											WHERE Cantina.Codice_contenitore_cliente=?";
				
					//Eseguo le due query di aggiornamento, una per la tabella Serbatoi e una per la tabella Esl
					//tabella serbatoi
					
					//tabella serbatoi
					if(!$resUpdateDatiTabSerbatoi = sqlsrv_query($con, $sqlUpdateDatiTabSerbatoi, $paramsUpdateDatiTabSerbatoi)){
						$erroreUpdateTabSerbatoi = 1;
						echo "Errore query di sincronizzazione dati tra tabella Gestionale e tabella Serbatoi.";
						if( ($errors = sqlsrv_errors() ) != null) {
							foreach( $errors as $error ) {
								echo "SQLSTATE: ".$error[ 'SQLSTATE']."<br />";
								echo "code: ".$error[ 'code']."<br />";
								echo "message: ".$error[ 'message']."<br />";
							}
						}
					}
					
					//tabella Esl
					if(!$resUpdateDatiTabEsl = sqlsrv_query($con, $sqlUpdateDatiTabEsl, $paramsUpdateDatiTabEsl)){
						$erroreUpdateTabEsl = 1;
						echo "Errore query di sincronizzazione dati tra tabella Gestionale e tabella Esl.";
						if( ($errors = sqlsrv_errors() ) != null) {
							foreach( $errors as $error ) {
								echo "SQLSTATE: ".$error[ 'SQLSTATE']."<br />";
								echo "code: ".$error[ 'code']."<br />";
								echo "message: ".$error[ 'message']."<br />";
							}
						}
						else {
							echo "aggiornamento eseguito!!!";
						}
					}
				}
				$rowTabellaCantina = $rowTabellaCantina+1;
			}
			fclose($fileCSVScambio);
		}
	}	




	//SE MODO DI SINCRONIZZAZIONE = FILE EXCEL: ****************************************************************************************************
	else if ($_SESSION['opt_sorgenteSinc'] == 2) {
		//Ricavo path del file EXCEL da cui importare i valori e ne verifico l'esistenza 
		$urlExcel = $_SESSION['opt_pathEXCEL'];
		$filecontent = @file_get_contents($urlExcel);		
		if ($filecontent === FALSE) {
			$erroreAperturaSorgente = 1;
		}
		else {
			
			echo "File EXCEL trovato";
			
			//Preparo il parsing del file EXCEL
			$tmpfname = tempnam(sys_get_temp_dir(),"tmpxls");
			file_put_contents($tmpfname,$filecontent);
			
			$inputFileType = PHPExcel_IOFactory::identify($urlExcel);
			$excelReader = PHPExcel_IOFactory::createReader($inputFileType);					
			$excelObj = $excelReader->load($urlExcel);
			
			$worksheet = $excelObj->getSheet(0);
			$lastRow = $worksheet->getHighestRow();
		
		
			//VERIFICA SE DEVO ESEGUIRE IL PRIMO AGGIORNAMENTO O SE SONO AGLI AGGIORNAMENTI SUCCESSIVI
			$paramsVerificaIAggiornamento = array();
			$sqlVerificaIAggiornamento = "SELECT TOP(10) Cantina.Codice_contenitore_cliente
										  FROM Cantina 
										  WHERE Cantina.Codice_contenitore_cliente <> ''";
			$numeroRighe = sqlsrv_query($con, $sqlVerificaIAggiornamento, $paramsVerificaIAggiornamento);
			
			//1° AGGIORNAMENTO: se le righe della tabella non sono provviste del codice cliente, significa che devo eseguire il primo aggiornamento...
			if (!sqlsrv_has_rows($numeroRighe)) {
				
				echo "Primo aggiornamento\n"; //+++++++++++++++++++++++++++++++++++++++++++++
				
				$rowTabellaCantina = 1;
				
				for ($rowExcel = 2; $rowExcel <= $lastRow; $rowExcel++) {
					
					//Ricavo il valore dei campi del file EXCEL
					
					//$campo_2_codice = (string)$worksheet->getCell('A'.$rowExcel)->getOldCalculatedValue();
					if ($worksheet->getCell('B'.$rowExcel)->isFormula()) {
						$campo_2_codice = (string)$worksheet->getCell('B'.$rowExcel)->getOldCalculatedValue();
					}
					else {
						$campo_2_codice = (string)$worksheet->getCell('B'.$rowExcel)->getValue();
					}
					
					//$campo_1_capacita = (float)$worksheet->getCell('D'.$rowExcel)->getOldCalculatedValue();
					if ($worksheet->getCell('C'.$rowExcel)->isFormula()) {
						$campo_1_capacita = (float)$worksheet->getCell('C'.$rowExcel)->getOldCalculatedValue();
					}
					else {
						$campo_1_capacita = (float)$worksheet->getCell('C'.$rowExcel)->getValue();
					}					
					
					//BLOCCO PER INSERIMENTO IN TABELLA CANTINA E CONTENITORE DEI CODICE CONTENITORI USATI DAL GESTIONALE CLIENTE
					//Codice serbatoio interno a SW Cantina per 
					$codiceSerbatoioInterno = "RecipCod_".$rowTabellaCantina;
					$descrizioneSerbatoio = "Recipiente ".$campo_2_codice;
					
					//Parametri query di aggiornamento 
					$paramsUpdateCodiciTabSerbatoi = array(&$codiceSerbatoioInterno);
					$paramsUpdateCodiciTabEsl = array(&$codiceSerbatoioInterno);
					$sqlUpdateCodiciTabSerbatoi = "UPDATE Contenitori 
												   SET Contenitori.Codice_contenitore_cliente = '$campo_2_codice', Contenitori.Nome ='$campo_2_codice', Contenitori.Descrizione = '$descrizioneSerbatoio', Contenitori.Capacita = $campo_1_capacita
												   WHERE Contenitori.Codice_contenitore=?";
					$sqlUpdateCodiciTabEsl = "UPDATE Cantina 
											  SET Cantina.Codice_contenitore_cliente = '$campo_2_codice' 
											  WHERE Cantina.Numero_serbatoio=?";
					
					//Eseguo le due query di aggiornamento, una per la tabella Serbatoi e una per la tabella Esl
					//tabella serbatoi
					if(!$resUpdateCodiciTabSerbatoi = sqlsrv_query($con, $sqlUpdateCodiciTabSerbatoi, $paramsUpdateCodiciTabSerbatoi)){
						$errorePrimoUpdateTabContenitori = 1;						
						echo "Errore query di sincronizzazione dati tra tabella Gestionale e tabella Serbatoi";
						if( ($errors = sqlsrv_errors() ) != null) {
							foreach( $errors as $error ) {
								echo "SQLSTATE: ".$error[ 'SQLSTATE']."<br />";
								echo "code: ".$error[ 'code']."<br />";
								echo "message: ".$error[ 'message']."<br />";
							}
						}
					}
					
					//tabella Esl
					if(!$resUpdateCodiciTabEsl = sqlsrv_query($con, $sqlUpdateCodiciTabEsl, $paramsUpdateCodiciTabEsl)){
						$errorePrimoUpdateTabEsl = 1;						
						echo "Errore query di sincronizzazione dati tra tabella Gestonale e tabella Esl";
						if( ($errors = sqlsrv_errors() ) != null) {
							foreach( $errors as $error ) {
								echo "SQLSTATE: ".$error[ 'SQLSTATE']."<br />";
								echo "code: ".$error[ 'code']."<br />";
								echo "message: ".$error[ 'message']."<br />";
							}
						}
					}
					$rowTabellaCantina = $rowTabellaCantina+1;
				}
			}

			echo "Aggiornamenti successivi\n"; //+++++++++++++++++++++++++++++++++++++++++++++
			
			//AGGIORNAMENTI SUCCESSIVI: se almeno una riga della tabella è provvista del codice cliente, significa che sto eseguendo aggiornamenti successivi... 
			for ($rowExcel = 2; $rowExcel <= $lastRow; $rowExcel++) {
				
				//Ricavo il valore dei campi del file EXCEL
				
				//CAMPO 1
				if ($worksheet->getCell('C'.$rowExcel)->isFormula()) {
					$campo_1_capacita = (float)$worksheet->getCell('C'.$rowExcel)->getOldCalculatedValue();
				}
				else {
					$campo_1_capacita = (float)$worksheet->getCell('C'.$rowExcel)->getValue();
				}
				
				
				//CAMPO 2
				if ($worksheet->getCell('B'.$rowExcel)->isFormula()) {
					$campo_2_codice = (string)$worksheet->getCell('B'.$rowExcel)->getOldCalculatedValue();
				}
				else {
					$campo_2_codice = (string)$worksheet->getCell('B'.$rowExcel)->getValue();
				}
				
				
				//CAMPO 3
				if ($worksheet->getCell('D'.$rowExcel)->isFormula()) {
					$campo_3_anno = (string)$worksheet->getCell('D'.$rowExcel)->getOldCalculatedValue();
				}
				else {
					$campo_3_anno = (string)$worksheet->getCell('D'.$rowExcel)->getValue();
				}
				
				
				//CAMPO 4
				if ($worksheet->getCell('E'.$rowExcel)->isFormula()) {
					$campo_4_categoriaProdotto = (string)$worksheet->getCell('E'.$rowExcel)->getOldCalculatedValue();
				}
				else {
					$campo_4_categoriaProdotto = (string)$worksheet->getCell('E'.$rowExcel)->getValue();
				}
				
				
				//CAMPO 5
				if ($worksheet->getCell('G'.$rowExcel)->isFormula()) {
					$campo_5_nomeProdotto = (string)$worksheet->getCell('F'.$rowExcel)->getOldCalculatedValue();
				}
				else {
					$campo_5_nomeProdotto = (string)$worksheet->getCell('F'.$rowExcel)->getValue();
				}
				
				
				//CAMPO 6
				if ($worksheet->getCell('H'.$rowExcel)->isFormula()) {
					$campo_6_attoCertificazione = (string)$worksheet->getCell('G'.$rowExcel)->getOldCalculatedValue();
				}
				else {
					$campo_6_attoCertificazione = (string)$worksheet->getCell('G'.$rowExcel)->getValue();
				}
					
					
				//CAMPO 7
				if ($worksheet->getCell('I'.$rowExcel)->isFormula()) {
					$campo_7_classificazioneProdotto = (string)$worksheet->getCell('H'.$rowExcel)->getOldCalculatedValue();
				}
				else {
					$campo_7_classificazioneProdotto = (string)$worksheet->getCell('H'.$rowExcel)->getValue();
				}
							

				//CAMPO 8
				if ($worksheet->getCell('J'.$rowExcel)->isFormula()) {
					$campo_8_colore = (string)$worksheet->getCell('I'.$rowExcel)->getOldCalculatedValue();
				}
				else {
					$campo_8_colore = (string)$worksheet->getCell('I'.$rowExcel)->getValue();
				}				
								
								
				//CAMPO 9
				if ($worksheet->getCell('K'.$rowExcel)->isFormula()) {
					$campo_9_biologico = (string)$worksheet->getCell('J'.$rowExcel)->getOldCalculatedValue();
				}
				else {
					$campo_9_biologico = (string)$worksheet->getCell('J'.$rowExcel)->getValue();
				}	


				//CAMPO 10
				if ($worksheet->getCell('L'.$rowExcel)->isFormula()) {
					$campo_10_statoProdotto = (string)$worksheet->getCell('K'.$rowExcel)->getOldCalculatedValue();
				}
				else {
					$campo_10_statoProdotto = (string)$worksheet->getCell('K'.$rowExcel)->getValue();
				}	
				
				
				//CAMPO 11
				if ($worksheet->getCell('M'.$rowExcel)->isFormula()) {
					$campo_11_menzioneProdotto = (string)$worksheet->getCell('L'.$rowExcel)->getOldCalculatedValue();
				}
				else {
					$campo_11_menzioneProdotto = (string)$worksheet->getCell('L'.$rowExcel)->getValue();
				}									
		
				$codiceSerbatoioCliente = trim($campo_2_codice);
				
				//Sostituisco i caratteri speciali con un carattere che non crei problemi nell'esecuzione delle query sql
				$campo_4_categoriaProdotto = str_replace("'", "`", $campo_4_categoriaProdotto);
				$campo_4_categoriaProdotto = str_replace('"', '`', $campo_4_categoriaProdotto);
				
				$campo_5_nomeProdotto = str_replace("'", "`", $campo_5_nomeProdotto);
				$campo_5_nomeProdotto = str_replace('"', '`', $campo_5_nomeProdotto);
				
				$campo_6_attoCertificazione = str_replace("'", "`", $campo_6_attoCertificazione);
				$campo_6_attoCertificazione = str_replace('"', '`', $campo_6_attoCertificazione);
				
				$campo_7_classificazioneProdotto = str_replace("'", "`", $campo_7_classificazioneProdotto);
				$campo_7_classificazioneProdotto = str_replace('"', '`', $campo_7_classificazioneProdotto);
				
				
				
				//Parametri query di aggiornamento dati
				$paramsUpdateDatiTabSerbatoi = array(&$codiceSerbatoioCliente);
				$paramsUpdateDatiTabEsl = array(&$codiceSerbatoioCliente);
				$sqlUpdateDatiTabSerbatoi = "UPDATE Contenitori 
												   SET Contenitori.Capacita = $campo_1_capacita
												   WHERE Contenitori.Codice_contenitore_cliente=?";
				$sqlUpdateDatiTabEsl = "UPDATE Cantina 
										SET Cantina.ESL_campo_1 = $campo_1_capacita, Cantina.ESL_campo_2 = '$campo_4_categoriaProdotto', Cantina.ESL_campo_3 = '$campo_5_nomeProdotto', Cantina.ESL_campo_4 = '$campo_6_attoCertificazione', Cantina.ESL_campo_5 = '$campo_7_classificazioneProdotto', Cantina.ESL_campo_6 = '$campo_3_anno', Cantina.ESL_campo_7 = '$campo_8_colore', Cantina.ESL_campo_8 = '$campo_10_statoProdotto', Cantina.ESL_campo_9 = '$campo_9_biologico', Cantina.ESL_campo_10 = '$campo_11_menzioneProdotto'  
										WHERE Cantina.Codice_contenitore_cliente=?";
			
				//Eseguo le due query di aggiornamento, una per la tabella Serbatoi e una per la tabella Esl
				//tabella serbatoi
				
				//tabella serbatoi
				if(!$resUpdateDatiTabSerbatoi = sqlsrv_query($con, $sqlUpdateDatiTabSerbatoi, $paramsUpdateDatiTabSerbatoi)){
					$erroreUpdateTabSerbatoi = 1;
					echo "Errore query di sincronizzazione dati tra tabella Gestionale e tabella Serbatoi.";
					if( ($errors = sqlsrv_errors() ) != null) {
						foreach( $errors as $error ) {
							echo "SQLSTATE: ".$error[ 'SQLSTATE']."<br />";
							echo "code: ".$error[ 'code']."<br />";
							echo "message: ".$error[ 'message']."<br />";
						}
					}
				}
				
				
				//tabella Esl
				if(!$resUpdateDatiTabEsl = sqlsrv_query($con, $sqlUpdateDatiTabEsl, $paramsUpdateDatiTabEsl)){
					$erroreUpdateTabEsl = 1;
					echo "Errore query di sincronizzazione dati tra tabella Gestionale e tabella Esl.";
					if( ($errors = sqlsrv_errors() ) != null) {
						foreach( $errors as $error ) {
							echo "SQLSTATE: ".$error[ 'SQLSTATE']."<br />";
							echo "code: ".$error[ 'code']."<br />";
							echo "message: ".$error[ 'message']."<br />";
						}
					}
				}
			}				
		}
	}
	
	//GESTIONE ERRORE GENERATI IN FASE DI SINCRONIZZAZIONE
	if ($tipoSincro == 1) { 
		if ($errorePrimoUpdateTabContenitori == 1){
			echo '<script type="text/javascript">alert("Errore query di sincronizzazione iniziale codici tra tabella Gestionale e tabella Serbatoi.");</script>';
		}
		else if ($errorePrimoUpdateTabEsl == 1) {
			echo '<script type="text/javascript">alert("Errore query di sincronizzazione iniziale codici tra tabella Gestonale e tabella Esl.");</script>';
		}
		else if ($erroreUpdateTabSerbatoi == 1) {
			echo '<script type="text/javascript">alert("Errore query di sincronizzazione dati tra tabella Gestionale e tabella Serbatoi.");</script>';
		}
		else if ($erroreUpdateTabEsl == 1) {
			echo '<script type="text/javascript">alert("Errore query di sincronizzazione dati tra tabella Gestionale e tabella Esl.");</script>';
		}
		else if ($erroreAperturaSorgente == 1) {
		
	echo '<script type="text/javascript">alert("Errore procedura di sincronizzazione!\nFile sorgente non trovato o tabella SQL di scambio non inizializzata.");</script>';
		}
		else {
			echo '<script type="text/javascript">alert("Sincronizzazione dati conclusa correttamente.");</script>';
		}
	}
	
	
	?>