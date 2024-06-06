<!-- <link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/datatables.bootstrap4.min.css">
<link rel="stylesheet" href="../css/select.dataTables.min.css"> -->
<link rel="stylesheet" href="../css/datatables.min.css">
<link rel="stylesheet" href="../css/bootstrap-select.css">
<link rel="stylesheet" href="../node_modules/@mdi/font/css/materialdesignicons.min.css">
<link rel="stylesheet" href="../css/fullcalendar.css">
<link rel="stylesheet" href="../css/vis.css">
<link rel="stylesheet" href="../css/mes.css">
<link rel="stylesheet" href="../node_modules/leaflet/dist/leaflet.css">

<!-- <link rel="shortcut icon" href="" /> -->



<?php if (isset($_COOKIE["zoom_ui"]) && $_COOKIE["zoom_ui"] != 100) { ?>
	<style>
		body {
			zoom: <?= (intval($_COOKIE["zoom_ui"]) / 100) ?>;
		}
	</style>
<?php } ?>