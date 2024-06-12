// Funzione per verifica stato di funzionamento SCADA (watchdog)


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

})(jQuery);


function retrieveFromStorage(key, element) {
	var tmp = sessionStorage.getItem(key);
	if (tmp) {
		$(element).val(tmp);
	}
}
