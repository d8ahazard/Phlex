var bg, bodyWrap, loginBox, loginForm, mainwrap;
jQuery(document).ready(function($) {
    bg = $('.bg');
    bodyWrap = $('#bodyWrap');
    loginBox = $('.login-box');
    loginForm = $('#loginForm');
    mainwrap = $("#mainwrap");
    if (mainwrap.length === 0){
        bg.fadeIn(2000);
        loginBox.css({"top":"50%"});
    }
    var success = false;

    loginForm.submit(function(e) {
        e.preventDefault();
        if ($('.snackbar').length !== 0) $('.snackbar').snackbar("hide");
        $.snackbar({content: "One moment...",timeout:0});
        $.post("api.php", loginForm.serialize(), function(data) {
            if (data !== 'ERROR') {
                bodyWrap.html(data);
            } else {
                $('.snackbar').hide();
                $.snackbar({content: "Invalid password."});
            }
        });
    });
});