<?php
// in che pagina siamo
$pagina = "help";

include("../inc/conn.php");

?>
<!DOCTYPE html>
<html lang="it">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>PROSPECT40 | Help</title>
	<?php include("inc_css.php") ?>
</head>

<body>

	<div class="container-scroller">

		<?php include("inc_testata.php") ?>

		<div class="container-fluid page-body-wrapper">

			<div class="main-panel">

				<div class="content-wrapper">

					<embed src="../Manuale_Prospect_4.0.pdf" width="100%" height="100%" />

				</div>


			</div>

		</div>

	</div>

	<?php include("inc_js.php") ?>

</body>

</html>