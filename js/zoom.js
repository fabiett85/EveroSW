/* CONTROLLO ZOOM */

var zoomMinimo = 50;
var zoomMassimo = 100;

$(function () {
	/*
	if(!$('body').hasClass('zoomed'))
	{
		$('#aumenta-zoom').addClass('disabled').attr('disabled','disabled');
	}
	*/
});

$('#zoom').on('change', function () {
	var valoreZoomAttuale = parseInt($('#zoom').val());
	var zoomCss = valoreZoomAttuale / 100;
	$('body').css('zoom', zoomCss);
	$.post('../inc/conn.php', { azione: 'zoom', zoom: valoreZoomAttuale }).done(function () {
		location.reload();
	});
});

/* BOTTONI */
$('#diminuisci-zoom').on('click', function () {
	var valoreZoomAttuale = parseInt($('#zoom').val());
	if (valoreZoomAttuale > zoomMinimo) {
		$('body').addClass('zoomed');
		var valoreZoomDesiderato = valoreZoomAttuale - 10;
		$('#zoom').val(valoreZoomDesiderato);
		$('body').data('zoom', valoreZoomDesiderato);
		$('#zoom').trigger('change');
		if (valoreZoomDesiderato == zoomMinimo) {
			$(this).addClass('disabled').attr('disabled', 'disabled');
		}
		$('#aumenta-zoom').removeClass('disabled').removeAttr('disabled');
	}
});

$('#aumenta-zoom').on('click', function () {
	var valoreZoomAttuale = parseInt($('#zoom').val());
	if (valoreZoomAttuale < zoomMassimo) {
		var valoreZoomDesiderato = valoreZoomAttuale + 10;
		$('#zoom').val(valoreZoomDesiderato);
		$('#zoom').trigger('change');
		$('body').data('zoom', valoreZoomDesiderato);
		if (valoreZoomDesiderato == zoomMassimo) {
			$(this).addClass('disabled').attr('disabled', 'disabled');
		}
		$('#diminuisci-zoom').removeClass('disabled').removeAttr('disabled');
		if (valoreZoomDesiderato == 100) {
			$('body').removeClass('zoomed');
		}
	}
});
