(function ($) {
	$(function () {});

	$('#accedi').on('click', function (e) {
		e.preventDefault();
		login();
	});
})(jQuery);

function login() {
	sessionStorage.clear();

	var login = $('#login').val();
	var password = $('#password').val();

	if ($('#login-form')[0].checkValidity()) {
		$.post('login.php', {
			azione: 'login',
			login: login,
			password: password,
		}).done(function (data) {
			if (data == 'KO') {
				swal({
					title: 'ATTENZIONE!',
					text: 'Combinazione user/password non corretta!',
					icon: 'warning',
				});
			} else if (data == 'OK') {
				document.location.href = 'dashboard.php';
			} else if (data.substring(0,3) == 'MAX') {
				swal({
					title: 'ATTENZIONE!',
					text: 'Numero massimo di sessioni('+data.substring(4)+') raggiunto. Attendere che almeno un altro utente collegato effettui il logout.',
					icon: 'warning',
				});;
			} else {
				swal({
					title: 'ERRORE',
					text: 'Errore nel login',
					icon: 'error',
				});
			}
		});
	}
	return false;
}

function checkSubmit(e) {
	if (e && e.keyCode == 13) {
		e.preventDefault();
		login();
	}
}
