<nav class="navbar navbar-expand-md col-lg-12 col-12 p-0 fixed-top d-flex flex-row">

	<div class="navbar-menu-wrapper d-flex align-items-center justify-content-end">

		<!--
		<button class="navbar-toggler navbar-toggler align-self-center" type="button" style="color: #ffffff;" data-toggle="minimize">
			<span class="mdi mdi-apps"></span>
		</button>
		-->

		<!--
		<a class="navbar-brand" href="#"><img src="../images/logo_MS.png" alt="logo"/></a>
		-->

		<div class="titolo-barra ml-2"><a href="dashboard.php" style="color: inherit; text-decoration: none;">E-VERO 4.0</a></div>

		<button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" data-toggle="collapse"
			data-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false"
			aria-label="Toggle navigation">
			<span class="mdi mdi-menu"></span>
		</button>



		<div class="collapse navbar-collapse" id="navbarNavDropdown">
			<!-- Links -->
			<ul class="navbar-nav pl-4">


				<?php if (($_SESSION["utente"]['Livello'] >= 4) && ($_SESSION["utente"]['Livello'] < 5)) { ?>

				<!-- Se sono AMMINISTRATORRE, vedo tutte le voci del menu -->


				<!-- Dropdown -->
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle navbardrop" href="#" role="button" data-toggle="dropdown"
						aria-haspopup="true" aria-expanded="false">LAVAGNE ELETTRONICHE</a>
					<ul class="dropdown-menu dd_menu" aria-labelledby="navbarDropdownMenuLink">

						<li><a class="dropdown-item dd_voce" href="gestioneetichette.php"><i class="mdi mdi-chart-line menu-icon"></i><span>GESTIONE LAVAGNE</span></a></li>
						<li><a class="dropdown-item dd_voce" href="reportcosti.php"><i class="mdi mdi-chart-line menu-icon"></i><span>CONFIGURA LAVAGNE</span></a>
						
					</ul>
				</li>

	

				<!-- Dropdown -->
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle navbardrop" href="#" role="button" data-toggle="dropdown"
						aria-haspopup="true" aria-expanded="false">CONFIGURAZIONE ARCHIVI</a>
					<ul class="dropdown-menu dd_menu" aria-labelledby="navbarDropdownMenuLink">
						<li><a class="dropdown-item dd_voce" href="lineeproduzione.php"><i class="mdi mdi-table"></i><span>Serbatoi</span></a></li>
					</ul>
				</li>

				<!-- Dropdown -->
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle navbardrop" href="#" role="button" data-toggle="dropdown"
						aria-haspopup="true" aria-expanded="false">INFORMAZIONI</a>
					<ul class="dropdown-menu dd_menu" aria-labelledby="navbarDropdownMenuLink">
						<li><a class="dropdown-item dd_voce" href="help.php"><i class="mdi mdi-table"></i><span>Manuale</span></a>
						</li>

					</ul>
				</li>

			<?php
				}
		?>
			</ul>
		</div>



		<!-- Gestione ZOOM pagine -->
		<!--
		<div class="input-group" id="controllo-zoom">
			<span class="input-group-btn">
				<button type="button" class="btn btn-default btn-number<?= $classe_diminuisci_zoom ?>" <?= $disabled_diminuisci_zoom ?> id="diminuisci-zoom" <?php if (isset($_COOKIE["zoom_ui"]) && $_COOKIE["zoom_ui"] == 50) {
																																																																												echo "disabled";
																																																																											} ?>>
					<i class="mdi mdi-minus"></i>
				</button>
			</span>
			<input type="text" name="zoom" id="zoom" class="form-control input-number" value="<?= isset($_COOKIE["zoom_ui"]) ? $_COOKIE["zoom_ui"] : "100" ?>" min="50" max="100">
			<div class="input-group-append"><span class="input-group-text">%</span></div>
			<span class="input-group-btn">
				<button type="button" class="btn btn-default btn-number<?= $classe_aumenta_zoom ?>" <?= $disabled_aumenta_zoom ?> id="aumenta-zoom" <?= isset($_COOKIE["zoom_ui"]) && $_COOKIE["zoom_ui"] != 50 ? $_COOKIE["zoom_ui"] : "" ?>>
					<i class="mdi mdi-plus"></i>
				</button>
			</span>
		</div>
		-->


		<!-- Menu comandi uscita sistema -->
		<ul class="navbar-nav navbar-nav-right">
			<li class="nav-item dropdown d-flex align-items-center">
				<span class="info-utente-loggato"><?= $_SESSION["utente"]["Username"] ?></span>
				<a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">
					<span class="mdi mdi-account"></span>
				</a>
				<div class="dropdown-menu dd_menu_profile">
					<a class="dropdown-item" href="login.php?azione=logout">
						<i class="mdi mdi-logout text-primary"></i>
						Esci
					</a>
				</div>
			</li>
		</ul>

	</div>

</nav>