<?php
	// in che pagina siamo
	$pagina = "dashboard";
	
	include("../inc/conn.php");
	

?>
<!DOCTYPE html>
<html lang="it">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>PROSPECT40 | Dashboard</title>
	<?php include("inc_css.php") ?>
</head>

<body class="<?=$classe_body_zoom?>"<?=$data_body_zoom?>>
	
	<div class="container-scroller">
	  
		<?php include("inc_testata.php") ?>
    
		<div class="container-fluid page-body-wrapper">
	
			<div class="main-panel">
				
				<div class="content-wrapper">
	          
					<div class="card" style="min-height: 100%;">
						
						<div class="card-body text-center" >
							<img class="rounded mx-auto d-block" style="position:absolute; left: 0; right: 0; display: block; margin: auto; top: 0; bottom: 0; max-width: 50%;" alt="logo" src="../images/sfondoHome_6.png">	
						</div>
						<div class="card-body text-center">
							<!-- <p style="font-size: 5rem;"><strong>CANTINA VINI</strong> </p> -->
		

						</div>
						
					</div>
					
			    </div>
	    
			</div>
			
		</div>
	    
	</div>

	<?php include("inc_js.php") ?>
	<!-- <script src="../js/dashboard.js"></script> -->
	
</body>
</html>