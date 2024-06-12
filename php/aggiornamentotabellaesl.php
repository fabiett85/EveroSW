<?php
// in che pagina siamo
$pagina = 'aggiornamentotabellaesl';
include("../inc/conn.php");

	
// GENERAZIONE TRACCIATO DI TIPO IMPORT
if (!empty($_REQUEST['azione']) && $_REQUEST['azione'] == 'sincronizza-tabella') {
	
	// Apro la transazione
	$conn_mes->beginTransaction();

	try {

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
		
		//SE MODO DI SINCRONIZZAZIONE = TABELLA DI SCAMBIO SQL SERVER: ****************************************************************************************************
		if ($_SESSION['configurazione']['Sorgente1SincroDB'] == 0) {		

		}
		//SE MODO DI SINCRONIZZAZIONE FILE E' CSV
		else if ($_SESSION['configurazione']['Sorgente1SincroDB'] == 1) {

			//Ricavo path del file CSV da cui importare i valori e relativo separatoreCSV e ne verifico l'esistenza 
			$separatoreCSV = $_SESSION['configurazione']['SeparatoreCSV'];
			$urlCSV = $_SESSION['configurazione']['Path1CSV'];

			//Apro il file e verifico se l'apertura va a buon fine (file presente? problemi di permessi?)
			$fileCSVScambio = fopen($urlCSV, "r");			

			if (!$fileCSVScambio) {
				die('ERRORE APERTURA FILE');
			}
			else {


				// verifico se esiste già un caso aperto in tabella 'attivita_casi', del medesimo tipo; in questo caso l'inserimento non viene effettuato
				$sthVerificaPrimoAggiornamento = $conn_mes->prepare("SELECT COUNT(*) AS ConCodiceCliente
																FROM Cantina 
																WHERE Cantina.Codice_contenitore_cliente <> ''"

				);
				$sthVerificaPrimoAggiornamento->execute();
				$rigaVerificaPrimoAggiornamento = $sthVerificaPrimoAggiornamento->fetch();


				//PRIMO AGGIORNAMENTO
				if ($rigaVerificaPrimoAggiornamento['ConCodiceCliente'] == 0) {
				
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
							

							//Genero codice serbatoio interno a SW Cantina
							$codiceSerbatoioInterno = "RecipCod_".$rowTabellaCantina;
							$descrizioneSerbatoio = "Recipiente ".$campo_2_codice;		
							
							//Aggiorno tabella serbatoi con i nomi serbatoi usati nell'anagrafica del cliente
							$queryUpdateTabSerbatoi = "UPDATE Contenitori 
														SET Contenitori.Codice_contenitore_cliente = '$campo_2_codice', Contenitori.Nome ='$campo_2_codice', Contenitori.Descrizione = '$descrizioneSerbatoio', Contenitori.Capacita = $campo_1_capacita
														WHERE Contenitori.Codice_contenitore= :CodiceSerbatoio";
							$sthUpdateTabSerbatoi = $conn_mes->prepare($queryUpdateTabSerbatoi);
							$sthUpdateTabSerbatoi->execute([':CodiceSerbatoio' => $codiceSerbatoioInterno]);								
			
							//Aggiorno tabella lavagne elettroniche con i nomi serbatoi usati nell'anagrafica del cliente
							$queryUpdateTabEsl = "UPDATE Cantina 
														SET Cantina.Codice_contenitore_cliente = '$campo_2_codice' 
														WHERE Cantina.Numero_serbatoio= :CodiceSerbatoio";
							$sthUpdateTabEsl = $conn_mes->prepare($queryUpdateTabEsl);
							$sthUpdateTabEsl->execute([':CodiceSerbatoio' => $codiceSerbatoioInterno]);							


						}
						$rowTabellaCantina = $rowTabellaCantina+1;

					}
				}
				
				//AGGIORNAMENTI SUCCESSIVI: se almeno una riga della tabella è provvista del codice cliente, significa che sto eseguendo aggiornamenti successivi... 
				rewind($fileCSVScambio);
				$rowTabellaCantina = 0;

				//Finché non arrivo a leggere l'intero file
				while(($lineaLetta = fgets($fileCSVScambio)) != false) {
					
					//Salto la prima riga (quella relativa all'intestazione)
					if ($rowTabellaCantina !== 0) {
					
						//Splitto e memorizzo in un array i valori ricavati dalla linea letta (utilizzando il separatore impostato)
						$arrayDatiLinea = str_getcsv ($lineaLetta, $separatoreCSV);
						
						//Inizializzo tutte le variabili con i relativi valori dell'array
						$campo_1_capacita = (float)trim($arrayDatiLinea[2]);
						$campo_2_codice = (string)trim($arrayDatiLinea[1]);				
						$campo_3_anno = (string)trim($arrayDatiLinea[3]); 
						$campo_4_categoriaProdotto= (string)trim($arrayDatiLinea[4]);
						$campo_5_nomeProdotto = (string)trim($arrayDatiLinea[5]);
						echo $campo_5_nomeProdotto;
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


						//Aggiorno tabella serbatoi con i nomi serbatoi usati nell'anagrafica del cliente
						$queryUpdateTabSerbatoi = "UPDATE Contenitori 
													   SET Contenitori.Capacita = $campo_1_capacita
													   WHERE Contenitori.Codice_contenitore_cliente= :CodiceSerbatoio";
						$sthUpdateTabSerbatoi = $conn_mes->prepare($queryUpdateTabSerbatoi);
						$sthUpdateTabSerbatoi->execute([':CodiceSerbatoio' => $codiceSerbatoioCliente]);								
		
						//Aggiorno tabella lavagne elettroniche con i nomi serbatoi usati nell'anagrafica del cliente
						$queryUpdateTabEsl = "UPDATE Cantina 
												SET  Cantina.ESL_campo_1 = $campo_1_capacita, Cantina.ESL_campo_2 = '$campo_4_categoriaProdotto', Cantina.ESL_campo_3 = '$campo_5_nomeProdotto', Cantina.ESL_campo_4 = '$campo_6_attoCertificazione', Cantina.ESL_campo_5 = '$campo_7_classificazioneProdotto', Cantina.ESL_campo_6 = '$campo_3_anno', Cantina.ESL_campo_7 = '$campo_8_colore', Cantina.ESL_campo_8 = '$campo_10_statoProdotto', Cantina.ESL_campo_9 = '$campo_9_biologico', Cantina.ESL_campo_10 = '$campo_11_menzioneProdotto'  
												WHERE Cantina.Codice_contenitore_cliente= :CodiceSerbatoio";
						$sthUpdateTabEsl = $conn_mes->prepare($queryUpdateTabEsl);
						$sthUpdateTabEsl->execute([':CodiceSerbatoio' => $codiceSerbatoioCliente]);			
						
					}
					$rowTabellaCantina = $rowTabellaCantina+1;
				}
				fclose($fileCSVScambio);
		
			}



		}
		//SE MODO DI SINCRONIZZAZIONE = FILE EXCEL
		else if ($_SESSION['configurazione']['Sorgente1SincroDB'] == 2) {


		}	

		// eseguo commit della transazione
		$conn_mes->commit();
		die('OK');		
		

		
	} catch (Throwable $t) {
		$conn_mes->rollBack();
		die("ERRORE: " . $t);
	}	
}