var bg, bodyWrap, loginBox, loginForm, mainwrap, messageArray;
$(function ($) {
	bg = $('.bg');
	bodyWrap = $('#bodyWrap');
	loginBox = $('.login-box');
	loginForm = $('#loginForm');
	mainwrap = $("#mainwrap");
	if (mainwrap.length === 0) {
		bg.fadeIn(2000);
		loginBox.css({"top": "50%"});
	}
	var success = false;

	loginForm.submit(function (e) {
		e.preventDefault();
		if ($('.snackbar').length !== 0) $('.snackbar').snackbar("hide");
		$.snackbar({content: "One moment...", timeout: 0});
		$.post("api.php", loginForm.serialize(), function (data) {
			if (data !== 'ERROR') {
				bodyWrap.html(data);
			} else {
				$('.snackbar').hide();
				$.snackbar({content: "Invalid password."});
			}
		});
	});

	var messages = $('#messages').data('array');

	if (messages !== "" && messages !== undefined) {
		messages = messages.replace(/\+/g, '%20');
		messageArray = JSON.parse(decodeURIComponent(messages));
		console.log("ARRAY: ", messageArray);
		loopMessages();
	} else {
		messageArray = [];
	}

	$('#alertModal').on('hidden.bs.modal', function () {
		loopMessages();
	});


});

