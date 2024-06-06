// Funzione per verifica stato di funzionamento SCADA (watchdog)
function WDVerifica() {
	// Invoco la funzione di verifica Watchdog
	$.post('utilities.php', { azione: 'verifica-watchdog-SCADA' }).done(function (data) {
		if (data == 'AVARIA_SCADA') {
			swal({
				title: 'ATTENZIONE!',
				text: 'Sistema SCADA non attivo: ricezione dati macchina interrotta.',
				icon: 'warning',
				button: 'OK',

				closeModal: true,
			});
		}
	});
}

// Funzione (Read Data From SCADA)
function readDataFromSCADA() {
	// Invoco la funzione di ricezione segnali da SCADA
	$.post('dialogo_SCADA.php', { azione: 'read-data-from-SCADA' }).done(function (data) {
		// Nessun ritorno
	});
}

// Funzione (Write Downtime)
function writeDowntime() {
	// Invoco la funzione di ricezione segnali da SCADA
	/* $.post('registrazione_downtime.php', { azione: 'write-downtime' }).done(function (data) {
		// Nessun ritorno
	}); */
}

/**
 * @param numOfSteps: Total number steps to get color, means total colors
 * @param step: The step number, means the order of the color
 */
function rainbow(numOfSteps, step) {
	// This function generates vibrant, "evenly spaced" colours (i.e. no clustering). This is ideal for creating easily distinguishable vibrant markers in Google Maps and other apps.
	// Adam Cole, 2011-Sept-14
	// HSV to RBG adapted from: http://mjijackson.com/2008/02/rgb-to-hsl-and-rgb-to-hsv-color-model-conversion-algorithms-in-javascript
	var r, g, b;
	var h = step / numOfSteps;
	var i = ~~(h * 6);
	var f = h * 6 - i;
	var q = 1 - f;
	switch (i % 6) {
		case 0:
			r = 1;
			g = f;
			b = 0;
			break;
		case 1:
			r = q;
			g = 1;
			b = 0;
			break;
		case 2:
			r = 0;
			g = 1;
			b = f;
			break;
		case 3:
			r = 0;
			g = q;
			b = 1;
			break;
		case 4:
			r = f;
			g = 0;
			b = 1;
			break;
		case 5:
			r = 1;
			g = 0;
			b = q;
			break;
	}
	var c =
		'#' +
		('00' + (~~(r * 255)).toString(16)).slice(-2) +
		('00' + (~~(g * 255)).toString(16)).slice(-2) +
		('00' + (~~(b * 255)).toString(16)).slice(-2);
	return c;
}

function buttonFilename(nome, dataInizio = null, dataFine = null) {
	var filename = nome;
	if (dataInizio) {
		dataInizio = moment(dataInizio);
		filename += '_' + dataInizio.format('YYYYMMDD');
	}
	if (dataFine) {
		dataFine = moment(dataFine);
		filename += '_' + dataFine.format('YYYYMMDD');
	}
	return filename;
}

function buttonTitle(nome, dataInizio = null, dataFine = null) {
	var filename = nome;
	if (dataInizio) {
		dataInizio = moment(dataInizio);
		filename += ' - ' + dataInizio.format('DD/MM/YYYY');
	}
	if (dataFine) {
		dataFine = moment(dataFine);
		filename += ' - ' + dataFine.format('DD/MM/YYYY');
	}
	return filename;
}

var linguaItaliana = {
	processing: 'Caricamento...',
	search: 'Ricerca: ',
	lengthMenu: '_MENU_ righe per pagina',
	zeroRecords: 'Nessun record presente',
	info: 'Pagina _PAGE_ di _PAGES_',
	infoEmpty: 'Nessun dato disponibile',
	infoFiltered: '(filtrate da _MAX_ righe totali)',
	paginate: {
		first: 'Prima',
		last: 'Ultima',
		next: 'Prossima',
		previous: 'Precedente',
	},
};

//VALORE DEFAULT SELECTPICKER
$('.selectpicker').selectpicker({
	noneSelectedText: 'Seleziona...',
});

(function ($) {
	'use strict';

	// Prova
	jQuery.event.special.touchstart = {
		setup: function (_, ns, handle) {
			this.addEventListener('touchstart', handle, { passive: !ns.includes('noPreventDefault') });
		},
	};
	jQuery.event.special.touchmove = {
		setup: function (_, ns, handle) {
			this.addEventListener('touchmove', handle, { passive: !ns.includes('noPreventDefault') });
		},
	};

	$(function () {
		'use strict';

		$('body').css({
			height: $(window).height(),
			overflow: 'hidden',
		});

		$('.main-panel').css({
			height: $(window).height() - $('nav.fixed-top').height() + 'px',
			'overflow-y': 'scroll',
		});

		$('.sidebar').css({
			height: $(window).height() - $('nav.fixed-top').height() + 'px',
			'overflow-y': 'scroll',
		});

		$(window).resize(function () {
			$('body').css({
				height: $(window).height(),
				overflow: 'hidden',
			});

			$('.main-panel').css({
				height: $(window).height() - $('nav.fixed-top').height() + 'px',
				'overflow-y': 'scroll',
			});

			$('.sidebar').css({
				height: $(window).height() - $('nav.fixed-top').height() + 'px',
				'overflow-y': 'scroll',
			});
		});

		//azzeramento sessione locale su clic sul pulsante di logout
		$('#logout-sistema').on('click', function () {
			sessionStorage.clear();
		});

		// Con cadenza regolare (ogni 5 minuti) invoco la funzione di verifica stato di funzionamento SCADA
		/* setInterval(function () {
			WDVerifica;
		}, 300000); */

		writeDowntime();
		// Con cadenza regolare (ogni 20 secondi) invoco la funzione di verifica ricezione comandi da SCADA
		setInterval(writeDowntime, 20000);

		/*
		$(".voce-menu").click(function () {

			var id = $(this).attr("id");
			localStorage.setItem("selectedolditemHMI", id);
		});

		var selectedolditem = localStorage.getItem('selectedolditemHMI');

		if (selectedolditem != null) {
			$('#' + selectedolditem).siblings().find(".active").removeClass("active");
			$('#' + selectedolditem).addClass("active");
		}
		*/

		/*
		$(function(){

			// fisso le altezze espanse dei blocchi collassabili altrimenti non funziona l'animazione
			$('.collassabile').each(function(){
				$(this).css('height',$(this).height() + 'px');
			});

			// click su un collassatore
			$('[data-collassa="collassa"]').on('click',function(e){
				e.preventDefault();
				var myTarget = $(this).attr('href');
				$(myTarget + '.collassabile').toggleClass('collassato')
				return false;
			});

		});
		*/

		$('.nav-settings').on('click', function () {
			$('#right-sidebar').toggleClass('open');
		});
		$('.settings-close').on('click', function () {
			$('#right-sidebar,#theme-settings').removeClass('open');
		});

		$('#settings-trigger').on('click', function () {
			$('#theme-settings').toggleClass('open');
		});

		//background constants
		var navbar_classes =
			'navbar-danger navbar-success navbar-warning navbar-dark navbar-light navbar-primary navbar-info navbar-pink';
		var sidebar_classes = 'sidebar-light sidebar-dark';
		var $body = $('body');

		//sidebar backgrounds
		$('#sidebar-light-theme').on('click', function () {
			$body.removeClass(sidebar_classes);
			$body.addClass('sidebar-light');
			$('.sidebar-bg-options').removeClass('selected');
			$(this).addClass('selected');
		});
		$('#sidebar-dark-theme').on('click', function () {
			$body.removeClass(sidebar_classes);
			$body.addClass('sidebar-dark');
			$('.sidebar-bg-options').removeClass('selected');
			$(this).addClass('selected');
		});

		//Navbar Backgrounds
		$('.tiles.primary').on('click', function () {
			$('.navbar').removeClass(navbar_classes);
			$('.navbar').addClass('navbar-primary');
			$('.tiles').removeClass('selected');
			$(this).addClass('selected');
		});
		$('.tiles.success').on('click', function () {
			$('.navbar').removeClass(navbar_classes);
			$('.navbar').addClass('navbar-success');
			$('.tiles').removeClass('selected');
			$(this).addClass('selected');
		});
		$('.tiles.warning').on('click', function () {
			$('.navbar').removeClass(navbar_classes);
			$('.navbar').addClass('navbar-warning');
			$('.tiles').removeClass('selected');
			$(this).addClass('selected');
		});
		$('.tiles.danger').on('click', function () {
			$('.navbar').removeClass(navbar_classes);
			$('.navbar').addClass('navbar-danger');
			$('.tiles').removeClass('selected');
			$(this).addClass('selected');
		});
		$('.tiles.light').on('click', function () {
			$('.navbar').removeClass(navbar_classes);
			$('.navbar').addClass('navbar-light');
			$('.tiles').removeClass('selected');
			$(this).addClass('selected');
		});
		$('.tiles.info').on('click', function () {
			$('.navbar').removeClass(navbar_classes);
			$('.navbar').addClass('navbar-info');
			$('.tiles').removeClass('selected');
			$(this).addClass('selected');
		});
		$('.tiles.dark').on('click', function () {
			$('.navbar').removeClass(navbar_classes);
			$('.navbar').addClass('navbar-dark');
			$('.tiles').removeClass('selected');
			$(this).addClass('selected');
		});
		$('.tiles.default').on('click', function () {
			$('.navbar').removeClass(navbar_classes);
			$('.tiles').removeClass('selected');
			$(this).addClass('selected');
		});
	});

	$('body').click(function () {
		$.post('impress.php');
	})

	$('body').keypress(function () {
		$.post('impress.php');
	})
})(jQuery);


function retrieveFromStorage(key, element) {
	var tmp = sessionStorage.getItem(key);
	if (tmp) {
		$(element).val(tmp);
	}
}
